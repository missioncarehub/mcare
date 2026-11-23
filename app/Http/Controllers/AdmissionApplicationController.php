<?php

namespace App\Http\Controllers;

use App\Mail\AdmissionApplicationReceivedMail;
use App\Models\AdminActivityLog;
use App\Models\AdmissionApplication;
use App\Models\EnrollmentApplication;
use App\Models\TrainingProgram;
use App\Notifications\ApplicantDocumentOtpNotification;
use App\Notifications\ReplacementDocumentSubmittedNotification;
use App\Services\AdminOperationsNotifier;
use App\Services\ProfilePhotoStore;
use App\Services\SemaphoreSmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class AdmissionApplicationController extends Controller
{
    public function create(Request $request): View
    {
        $programs = TrainingProgram::query()->active()->orderBy('name')->get();
        $requestedProgramId = (int) $request->query('training_program_id');

        return view('applications.create', [
            'programs' => $programs,
            'selectedProgramId' => $programs->firstWhere('id', $requestedProgramId)?->id
                ?? $programs->first()?->id,
        ]);
    }

    public function store(Request $request, AdminOperationsNotifier $notifier): RedirectResponse
    {
        $request->merge(
            collect($request->all())
                ->map(fn ($value) => is_string($value) ? trim($value) : $value)
                ->merge(['email' => strtolower(trim($request->input('email', '')))])
                ->all()
        );

        $safeText = ["not_regex:/[<>\"'`;{}|\\\\]/u"];
        $openStatuses = [AdmissionApplication::STATUS_PENDING, AdmissionApplication::STATUS_APPROVED];

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100', ...$safeText],
            'middle_name' => ['nullable', 'string', 'max:100', ...$safeText],
            'last_name' => ['required', 'string', 'max:100', ...$safeText],
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                'ends_with:@gmail.com',
                Rule::unique('admission_applications', 'email')->where(
                    fn ($query) => $query->whereIn('status', $openStatuses)
                ),
            ],
            'contact_number' => ['required', 'string', 'max:30', 'regex:/\A[0-9+\s().-]+\z/'],
            'schedule_preference' => ['nullable', 'in:AM,PM,Weekend'],
            'educational_attainment' => ['required', 'string', Rule::in(AdmissionApplication::educationalAttainmentOptions())],
            'notes' => ['nullable', 'string', 'max:500', ...$safeText],
            'training_program_id' => [
                Rule::requiredIf(fn (): bool => TrainingProgram::query()->active()->exists()),
                'nullable',
                'integer',
                'exists:training_programs,id',
            ],
            'privacy_consent' => ['accepted'],
        ], [
            'email.ends_with' => 'Please use a Gmail address ending in @gmail.com.',
            'email.unique' => AdmissionApplication::EMAIL_IN_USE_MESSAGE,
            'not_regex' => 'This field contains characters that are not allowed for security reasons.',
        ]);

        $program = filled($validated['training_program_id'] ?? null)
            ? TrainingProgram::query()->active()->find($validated['training_program_id'])
            : TrainingProgram::query()->active()->orderBy('name')->first();

        // Path: app/Http/Controllers/AdmissionApplicationController.php | Label: Applicant-facing error handler
        // Wrap the write path in try/catch so applicants see a clear notice instead of a raw 500
        // when the DB, notifier, or mailer is misconfigured. Duplicate email is already handled
        // separately via the "unique" rule above.
        try {
            $admission = AdmissionApplication::query()->create([
                'application_number' => AdmissionApplication::generateNumber(),
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'contact_number' => $validated['contact_number'],
                'schedule_preference' => $validated['schedule_preference'] ?? null,
                'educational_attainment' => $validated['educational_attainment'],
                'notes' => $validated['notes'] ?? null,
                'training_program_id' => $program?->id,
                'program' => $program?->name ?: 'Caregiving NC II',
                'status' => AdmissionApplication::STATUS_PENDING,
                'privacy_consent_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('applications.create')
                ->withInput($request->except(['privacy_consent']))
                ->with('application_error', 'We could not save your application because of a temporary system error. Please try again in a few moments. If it keeps failing, contact MCARE support.');
        }

        $request->session()->put('applications.submitted_id', $admission->id);

        try {
            AdminActivityLog::record(null, 'admission.submitted', $admission, [
                'application_number' => $admission->application_number,
                'email' => $admission->email,
            ]);

            $notifier->notify(
                title: 'New training application',
                message: $admission->fullName().' submitted application '.$admission->application_number.'.',
                url: route('admin.applications.show', $admission),
                icon: 'clipboard-list',
                event: 'admission.submitted',
                context: [
                    'admission_application_id' => $admission->id,
                    'application_number' => $admission->application_number,
                ],
            );
        } catch (Throwable $exception) {
            // Never fail the applicant flow if admin notifier or activity log breaks.
            report($exception);
        }

        try {
            Mail::to($admission->email)->send(new AdmissionApplicationReceivedMail($admission));
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()
            ->route('applications.received')
            ->with('application_submitted', $admission->application_number);
    }

    public function received(Request $request): View|RedirectResponse
    {
        $admissionId = $request->session()->get('applications.submitted_id');
        $admission = is_numeric($admissionId)
            ? AdmissionApplication::query()->find((int) $admissionId)
            : null;

        if (! $admission) {
            return redirect()->route('applications.status');
        }

        return view('applications.received', [
            'admission' => $admission,
        ]);
    }

    public function status(Request $request): View
    {
        $rawNumber = (string) $request->input('application_number', $request->query('application_number', ''));
        $admission = AdmissionApplication::findByNumber($rawNumber);
        $lookedUp = $request->isMethod('post') || $request->filled('application_number');
        $isDocumentUploadAuthorized = $admission
            ? ((int) $request->session()->get('doc_upload_auth_'.$admission->id, 0) >= now()->timestamp)
            : false;

        return view('applications.status', [
            'admission' => $admission,
            'lookedUp' => $lookedUp,
            'submittedNumber' => $rawNumber,
            'isDocumentUploadAuthorized' => $isDocumentUploadAuthorized,
        ]);
    }

    public function lookup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'application_number' => ['required', 'string', 'max:40'],
        ]);

        $raw = trim($validated['application_number']);
        $normalized = str_starts_with(strtoupper($raw), 'MCE')
            ? EnrollmentApplication::normalizeNumber($raw)
            : AdmissionApplication::normalizeNumber($raw);

        return redirect()->route('applications.status', [
            'application_number' => $normalized,
        ]);
    }

    public function sendDocumentOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'application_number' => ['required', 'string', 'max:40'],
        ]);

        $admission = AdmissionApplication::findByNumber($validated['application_number']);
        $enrollment = $admission?->enrollment;

        if (! $admission || ! $enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'No active enrollment record found for this application number.',
            ], 404);
        }

        if ($enrollment->status === EnrollmentApplication::STATUS_APPROVED) {
            return response()->json([
                'success' => false,
                'message' => 'Documents cannot be replaced because this enrollment has already been approved.',
            ], 422);
        }

        $email = strtolower(trim($enrollment->email ?: ($admission->email ?: '')));
        if ($email === '') {
            return response()->json([
                'success' => false,
                'message' => 'No registered email address found for this application. Please contact admissions.',
            ], 422);
        }

        $cooldownKey = 'doc_otp_cooldown_'.$admission->id;
        if (Cache::has($cooldownKey)) {
            $cooldownUntil = (int) Cache::get($cooldownKey);
            $remaining = max(1, $cooldownUntil - now()->timestamp);

            return response()->json([
                'success' => false,
                'cooldown' => true,
                'remaining_seconds' => $remaining,
                'message' => "Please wait {$remaining} seconds before requesting another verification code.",
            ], 429);
        }

        $otpCode = (string) random_int(100000, 999999);
        $cacheKey = 'doc_otp_'.$admission->id;

        Cache::put($cacheKey, [
            'hash' => hash('sha256', $otpCode),
            'attempts' => 0,
            'created_at' => now()->timestamp,
        ], now()->addMinutes(10));

        Cache::put($cooldownKey, now()->addSeconds(60)->timestamp, 60);

        $recipientName = trim(($enrollment->first_name ?? $admission->first_name ?? '').' '.($enrollment->last_name ?? $admission->last_name ?? ''));

        try {
            $target = $enrollment->user ?? Notification::route('mail', $email);
            $target->notify(new ApplicantDocumentOtpNotification(
                otpCode: $otpCode,
                applicationNumber: $admission->application_number,
                recipientName: $recipientName,
            ));
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to send the verification code at this time. Please try again shortly.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'masked_email' => $this->maskEmail($email),
            'cooldown_seconds' => 60,
            'message' => 'Verification code sent to '.$this->maskEmail($email),
        ]);
    }

    public function verifyDocumentOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'application_number' => ['required', 'string', 'max:40'],
            'otp_code' => ['required', 'string', 'max:10'],
        ]);

        $admission = AdmissionApplication::findByNumber($validated['application_number']);
        if (! $admission) {
            return response()->json([
                'success' => false,
                'message' => 'Application record not found.',
            ], 404);
        }

        $cacheKey = 'doc_otp_'.$admission->id;
        if (! Cache::has($cacheKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Verification code has expired or was not requested. Please request a new code.',
            ], 422);
        }

        $data = Cache::get($cacheKey);
        $attempts = (int) ($data['attempts'] ?? 0);

        if ($attempts >= 5) {
            Cache::forget($cacheKey);

            return response()->json([
                'success' => false,
                'message' => 'Too many invalid attempts. Please request a new verification code.',
            ], 429);
        }

        $inputCode = trim($validated['otp_code']);
        $inputHash = hash('sha256', $inputCode);

        if (! hash_equals($data['hash'], $inputHash)) {
            $attempts++;
            $data['attempts'] = $attempts;
            $remaining = 5 - $attempts;
            Cache::put($cacheKey, $data, now()->addMinutes(10));

            return response()->json([
                'success' => false,
                'message' => $remaining > 0
                    ? "Invalid verification code. {$remaining} attempt(s) remaining."
                    : 'Too many invalid attempts. Please request a new verification code.',
            ], 422);
        }

        Cache::forget($cacheKey);
        $request->session()->put('doc_upload_auth_'.$admission->id, now()->addMinutes(30)->timestamp);

        return response()->json([
            'success' => true,
            'message' => 'Identity verified successfully! You may now upload your replacement document.',
        ]);
    }

    public function replaceDocument(Request $request, ProfilePhotoStore $profilePhotos, AdminOperationsNotifier $notifier): RedirectResponse
    {
        $catalog = EnrollmentApplication::reviewDocuments();
        $validated = $request->validate([
            'application_number' => ['required', 'string', 'max:40'],
            'verifier' => ['nullable', 'string', 'max:180'],
            'document' => ['required', Rule::in(array_keys($catalog))],
            'replacement' => ['required', 'file', 'max:5120'],
        ]);

        $admission = AdmissionApplication::findByNumber($validated['application_number']);
        $enrollment = $admission?->enrollment;
        $key = $validated['document'];
        $definition = $catalog[$key];

        if (! $admission || ! $enrollment) {
            throw ValidationException::withMessages([
                'application_number' => 'Submit the enrollment form before uploading a replacement.',
            ]);
        }

        if ($enrollment->status === EnrollmentApplication::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'replacement' => 'Documents can only be replaced before the enrollment is approved.',
            ]);
        }

        $isSessionAuthorized = (int) $request->session()->get('doc_upload_auth_'.$admission->id, 0) >= now()->timestamp;
        $hasValidVerifier = filled($validated['verifier'] ?? null)
            && $this->verifierMatches($enrollment, (string) $validated['verifier']);

        if (! $isSessionAuthorized && ! $hasValidVerifier) {
            throw ValidationException::withMessages([
                'verifier' => 'Please verify your identity with the email verification code before uploading a replacement.',
            ]);
        }

        $review = $enrollment->document_review ?? [];
        $status = (string) data_get($review, $key.'.status', '');
        if (! in_array($status, ['replace', 'missing'], true)) {
            throw ValidationException::withMessages([
                'document' => 'That document is not waiting for a replacement.',
            ]);
        }

        $imageOnly = in_array($key, ['id-photo', 'signature'], true);
        $request->validate([
            'replacement' => $imageOnly
                ? ['required', 'file', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png', 'max:5120']
                : ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'extensions:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $directory = $enrollment->user_id
            ? 'enrollment-documents/'.$enrollment->user_id
            : 'enrollment-documents/application-'.$enrollment->id;
        $previousPath = $enrollment->{$definition['field']};
        $path = $request->file('replacement')->store($directory, 'local');
        $history = data_get($review, $key.'.history', []);
        if (! is_array($history)) {
            $history = [];
        }
        if (filled($previousPath) && $previousPath !== $path) {
            $history[] = [
                'path' => $previousPath,
                'replaced_at' => now()->toIso8601String(),
            ];
        }

        $review[$key] = [
            'status' => 'unreviewed',
            'note' => 'Replacement uploaded from Check status; awaiting admin review.',
            'history' => $history,
        ];

        $attributes = [
            $definition['field'] => $path,
            'document_review' => $review,
            'documents_reviewed_at' => null,
            'documents_reviewed_by_id' => null,
        ];
        if ($key === 'signature') {
            $attributes['signature_type'] = 'upload';
        }

        $enrollment->forceFill($attributes)->save();

        if ($key === 'id-photo' && $enrollment->user) {
            $profilePhotos->syncFromPrivateDisk($enrollment->user, $path);
        }

        AdminActivityLog::record(null, 'enrollment.document.replaced', $enrollment, [
            'document' => $key,
            'application_number' => $admission->application_number,
            'previous_path' => $previousPath,
        ]);

        $notifier->notify(
            'Replacement document uploaded',
            trim($enrollment->first_name.' '.$enrollment->last_name).' uploaded a replacement '.$definition['label'].'.',
            route('admin.enrollments.document-review', $enrollment),
            'file',
            'enrollment.document.replaced',
            ['enrollment_application_id' => $enrollment->id, 'document' => $key],
        );

        // Notify the applicant that their replacement document was received
        try {
            $applicantTarget = $enrollment->user ?? (filled($enrollment->email) ? Notification::route('mail', $enrollment->email) : null);
            if ($applicantTarget) {
                $applicantTarget->notify(new ReplacementDocumentSubmittedNotification(
                    $enrollment->fresh(),
                    $definition['label'],
                    $admission->application_number,
                ));
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()
            ->route('applications.status', ['application_number' => $admission->application_number])
            ->with('saved', 'Replacement for '.$definition['label'].' uploaded successfully! It is now pending registrar review.');
    }

    private function verifierMatches(EnrollmentApplication $enrollment, string $verifier): bool
    {
        $given = trim($verifier);
        $email = strtolower(trim((string) $enrollment->email));
        $givenEmail = strtolower($given);

        if ($email !== '' && strlen($email) === strlen($givenEmail) && hash_equals($email, $givenEmail)) {
            return true;
        }

        $sms = app(SemaphoreSmsService::class);
        $expected = $sms->normalizePhilippineNumber($enrollment->contact_number);
        $givenNumber = $sms->normalizePhilippineNumber($given);

        return $expected !== null
            && $givenNumber !== null
            && hash_equals($expected, $givenNumber);
    }

    private function maskEmail(?string $email): string
    {
        if (! $email || ! str_contains($email, '@')) {
            return 'your registered email';
        }

        [$user, $domain] = explode('@', $email, 2);
        $len = strlen($user);

        if ($len <= 2) {
            $maskedUser = substr($user, 0, 1).'*';
        } else {
            $maskedUser = substr($user, 0, 1).str_repeat('*', min(4, $len - 2)).substr($user, -1);
        }

        return $maskedUser.'@'.$domain;
    }
}

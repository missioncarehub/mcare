<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use App\Models\AdmissionApplication;
use App\Models\EnrollmentApplication;
use App\Models\PaymentAttempt;
use App\Models\TrainingBatch;
use App\Models\User;
use App\Notifications\EnrollmentSubmittedNotification;
use App\Services\ProfilePhotoStore;
use App\Support\TraineeUserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class EnrollmentController extends Controller
{
    public function create(Request $request): View
    {
        $application = null;

        if ($request->user()) {
            $application = EnrollmentApplication::where('user_id', $request->user()->id)
                ->with('admissionApplication')
                ->latest()
                ->first();
        }

        $unlockedAdmission = $this->unlockedAdmission($request, $application);
        $lockedEducationalAttainment = $this->lockedEducationalAttainment($application, $unlockedAdmission);

        $availableBatches = TrainingBatch::query()
            ->publishedForEnrollment()
            ->with('program')
            ->orderBy('enrollment_ends_at')
            ->orderBy('training_starts_at')
            ->orderBy('name')
            ->get();
        $requestedBatchId = (int) ($request->old('training_batch_id')
            ?: $request->query('batch', 0));
        $enrollmentBatch = $application?->batch?->loadMissing('program')
            ?: $availableBatches->firstWhere('id', $requestedBatchId);

        if (! $application && ! $enrollmentBatch && $availableBatches->count() === 1) {
            $enrollmentBatch = $availableBatches->first();
        }
        $documentLabels = [
            'birth-certificate' => 'Birth Certificate',
            'education-document' => 'Form 137/138 or Diploma',
            'good-moral-certificate' => 'Good Moral Certificate',
            'id-photo' => 'ID Photo',
            'signature' => 'E-Signature',
        ];
        $documentFeedback = collect($application?->document_review ?? [])
            ->filter(fn ($item) => in_array(data_get($item, 'status'), ['replace', 'missing'], true));

        return view('enrollment.create', [
            'application' => $application,
            'availableBatches' => $availableBatches,
            'canCompleteEnrollment' => $application !== null || $unlockedAdmission !== null,
            'enrollmentBatch' => $enrollmentBatch,
            'unlockedAdmission' => $unlockedAdmission,
            'user' => $request->user(),
            'googleIdentity' => $this->googleIdentity($request),
            'isGoogleApplicant' => filled($request->user()?->google_id),
            'documentLabels' => $documentLabels,
            'documentFeedback' => $documentFeedback,
            'draftUploads' => $request->session()->get('enrollment.draft_uploads', []),
            'lockedEducationalAttainment' => $lockedEducationalAttainment,
        ]);
    }

    public function unlock(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'application_number' => ['required', 'string', 'max:40'],
        ]);

        $admission = AdmissionApplication::findByNumber($validated['application_number']);

        if (! $admission) {
            throw ValidationException::withMessages([
                'application_number' => 'That application number was not found. Check the number from your confirmation email.',
            ]);
        }

        if ($admission->isPending()) {
            throw ValidationException::withMessages([
                'application_number' => 'Application '.$admission->application_number.' is still waiting for admin review. Check status anytime with this number.',
            ]);
        }

        if ($admission->isDenied()) {
            $reason = filled($admission->admin_notes)
                ? ' '.$admission->admin_notes
                : '';

            throw ValidationException::withMessages([
                'application_number' => 'Application '.$admission->application_number.' was not approved.'.$reason,
            ]);
        }

        if ($admission->enrollment()->exists() && $admission->enrollment?->user_id !== $request->user()?->id) {
            throw ValidationException::withMessages([
                'application_number' => 'This application number was already used to submit enrollment. Sign in with the same Gmail to continue.',
            ]);
        }

        $request->session()->put('enrollment.admission_application_id', $admission->id);

        return redirect()
            ->route('enrollment.create', array_filter([
                'batch' => $request->query('batch'),
            ]))
            ->with('saved', 'Application '.$admission->application_number.' is approved. Complete the enrollment form below.');
    }

    public function draftContent(Request $request, string $field): BinaryFileResponse
    {
        abort_unless(in_array($field, $this->uploadFields(), true), 404);

        $draft = data_get($request->session()->get('enrollment.draft_uploads', []), $field);
        $path = data_get($draft, 'path');

        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);

        $name = basename((string) data_get($draft, 'name', $field));
        $mime = (string) data_get($draft, 'mime', 'application/octet-stream');

        return response()
            ->file(Storage::disk('local')->path($path), [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="'.$name.'"',
                'X-Content-Type-Options' => 'nosniff',
            ]);
    }

    public function store(Request $request, ProfilePhotoStore $profilePhotos): RedirectResponse|JsonResponse
    {
        $request->merge(
            collect($request->all())
                ->map(fn ($value) => is_string($value) ? trim($value) : $value)
                ->merge(['email' => strtolower(trim($request->input('email', '')))])
                ->all()
        );

        $currentUser = $request->user();
        if ($currentUser) {
            // A signed-in identity owns its account email. Ignore a tampered
            // form value instead of allowing enrollment to relink the account.
            $request->merge(['email' => Str::lower($currentUser->email)]);
        }

        $currentApplication = $currentUser
            ? EnrollmentApplication::where('user_id', $currentUser->id)->with('admissionApplication')->first()
            : null;
        $lockedEducationalAttainment = $this->lockedEducationalAttainmentFromRequest($request, $currentApplication);
        if ($lockedEducationalAttainment !== null) {
            $request->merge(['educational_attainment' => $lockedEducationalAttainment]);
        }
        $requiresGraduationYear = AdmissionApplication::requiresGraduationYear(
            (string) $request->input('educational_attainment', ''),
        );
        if (! $requiresGraduationYear) {
            $request->merge(['year_graduated' => EnrollmentApplication::YEAR_GRADUATED_NOT_APPLICABLE]);
        }
        $isDeniedResubmission = $currentApplication?->status === EnrollmentApplication::STATUS_DENIED;
        $previousDenialNote = $isDeniedResubmission ? $currentApplication->admin_notes : null;
        $requestedBatchId = $currentApplication?->training_batch_id
            ?: (int) $request->input('training_batch_id');
        $enrollmentBatch = $currentApplication?->batch?->loadMissing('program')
            ?: TrainingBatch::publishedOpenForEnrollment($requestedBatchId ?: null)?->loadMissing('program');
        $isGoogleApplicant = filled($currentUser?->google_id);
        $passwordRules = $isGoogleApplicant
            ? ['exclude']
            : [
                $currentUser ? 'nullable' : 'required',
                'confirmed',
                'max:255',
                Password::min(10)->mixedCase()->letters()->numbers(),
            ];

        $safeText = ['not_regex:/[<>"`;{}|\\\\]/u'];
        $safeOptionalText = ['nullable', 'string', 'max:120', 'not_regex:/[<>"`;{}|\\\\]/u'];
        $documentRules = ['file', 'mimes:pdf,jpg,jpeg,png', 'extensions:pdf,jpg,jpeg,png', 'max:5120'];
        $draftUploads = $request->session()->get('enrollment.draft_uploads', []);
        $hasDocument = fn (string $field, ?string $existingPath): bool => filled($existingPath)
            || filled(data_get($draftUploads, "{$field}.path"));

        $validator = Validator::make($request->all(), [
            'application_number' => [
                $currentApplication ? 'nullable' : 'required',
                'string',
                'max:40',
            ],
            'training_batch_id' => [
                $currentApplication ? 'nullable' : 'required',
                'integer',
                'exists:training_batches,id',
            ],
            'email' => [
                'required',
                'email:rfc',
                'max:255',
                'ends_with:@gmail.com',
                Rule::unique('users', 'email')->ignore($currentUser?->id),
            ],
            'password' => $passwordRules,
            'first_name' => ['required', 'string', 'max:100', ...$safeText],
            'middle_name' => ['nullable', 'string', 'max:100', ...$safeText],
            'last_name' => ['required', 'string', 'max:100', ...$safeText],
            'extension_name' => ['nullable', 'string', 'max:30', ...$safeText],
            'birth_date' => ['required', 'date'],
            'birthplace_city' => $safeOptionalText,
            'birthplace_province' => $safeOptionalText,
            'birthplace_region' => $safeOptionalText,
            'gender' => ['required', 'in:Female,Male'],
            'civil_status' => ['required', 'string', 'max:50'],
            'employment_status' => ['required', 'string', 'max:80'],
            'employment_type' => ['nullable', 'string', 'max:80'],
            'contact_number' => ['required', 'string', 'max:30', 'regex:/\A[0-9+\s().-]+\z/'],
            'nationality' => ['required', 'string', 'max:80', ...$safeText],
            'schedule_preference' => ['required', 'in:AM,PM,Weekend'],
            'street' => ['required', 'string', 'max:100', ...$safeText],
            'barangay' => ['required', 'string', 'max:120', ...$safeText],
            'city' => ['required', 'string', 'max:120', ...$safeText],
            'province' => ['required', 'string', 'max:120', ...$safeText],
            'region' => ['required', 'string', 'max:120', ...$safeText],
            'zip_code' => ['required', 'string', 'max:20', 'regex:/\A[\pL\pN\s-]+\z/u'],
            'educational_attainment' => ['required', 'string', Rule::in(AdmissionApplication::educationalAttainmentOptions())],
            'school_name' => ['required', 'string', 'max:180', ...$safeText],
            'year_graduated' => $requiresGraduationYear
                ? ['required', 'integer', 'min:1950', 'max:'.now()->year]
                : ['required', 'integer', 'in:'.EnrollmentApplication::YEAR_GRADUATED_NOT_APPLICABLE],
            'guardian_name' => ['required', 'string', 'max:180', ...$safeText],
            'guardian_address' => ['required', 'string', 'max:255', ...$safeText],
            'classification' => ['nullable', 'string', 'max:120'],
            'disability_type' => ['nullable', 'string', 'max:120'],
            'disability_cause' => ['nullable', 'string', 'max:120'],
            'scholarship_type' => ['nullable', 'string', 'max:120', ...$safeText],
            'privacy_consent' => ['accepted'],
            'signature_name' => ['required', 'string', 'max:180', ...$safeText],
            'birth_certificate' => [$hasDocument('birth_certificate', $currentApplication?->birth_certificate_path) ? 'nullable' : 'required', ...$documentRules],
            'education_document' => [$hasDocument('education_document', $currentApplication?->education_document_path) ? 'nullable' : 'required', ...$documentRules],
            'good_moral_certificate' => [$hasDocument('good_moral_certificate', $currentApplication?->good_moral_certificate_path) ? 'nullable' : 'required', ...$documentRules],
            'id_photo' => [$hasDocument('id_photo', $currentApplication?->id_photo_path) ? 'nullable' : 'required', 'file', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png', 'max:5120'],
            'signature_type' => ['required', 'in:draw,upload'],
            'signature_data' => ['exclude_unless:signature_type,draw', $currentApplication?->signature_path ? 'nullable' : 'required', 'string'],
            'signature_upload' => ['exclude_unless:signature_type,upload', $hasDocument('signature_upload', $currentApplication?->signature_path) ? 'nullable' : 'required', 'file', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png', 'max:5120'],
        ], [
            'email.ends_with' => 'Please use a Gmail address ending in @gmail.com.',
            'email.unique' => 'This Gmail account already has an MCARE account. Sign in with the same Gmail to continue or resubmit a denied enrollment.',
            'not_regex' => 'This field contains characters that are not allowed for security reasons.',
            'password.confirmed' => 'Password and confirmation must match.',
            'birth_certificate.required' => 'Upload a clear birth certificate copy.',
            'education_document.required' => 'Upload Form 137/138 or diploma.',
            'good_moral_certificate.required' => 'Upload a certificate of good moral.',
            'id_photo.required' => 'Upload a 1x1 or 2x2 ID photo.',
            'signature_data.required' => 'Draw your signature before submitting.',
            'signature_upload.required' => 'Upload a signature image or choose Draw Signature.',
            '*.mimes' => 'Accepted formats are PDF, JPG, JPEG, and PNG. ID photo and signature image must be JPG or PNG.',
            '*.max' => 'Each uploaded file must not exceed 5MB.',
            'training_batch_id.required' => 'Choose one of the active batches published by MCARE before submitting enrollment.',
            'application_number.required' => 'Enter the approved application number issued after MCARE reviewed your application.',
            'educational_attainment.in' => 'Choose an educational attainment from the application list.',
            'year_graduated.required' => 'Enter the year you graduated.',
        ]);

        $validator->after(function ($validator) use ($currentApplication, $enrollmentBatch, $request): void {
            if (! $currentApplication && ! $enrollmentBatch) {
                $validator->errors()->add(
                    'training_batch_id',
                    'That batch is hidden, inactive, closed, or no longer inside its enrollment window. Choose an available batch and try again.',
                );
            }

            if ($currentApplication) {
                return;
            }

            $admission = AdmissionApplication::findByNumber((string) $request->input('application_number'))
                ?: AdmissionApplication::query()->find($request->session()->get('enrollment.admission_application_id'));

            if (! $admission?->isApproved()) {
                $validator->errors()->add(
                    'application_number',
                    'Enter an approved application number before submitting enrollment.',
                );

                return;
            }

            if (Str::lower($admission->email) !== Str::lower((string) $request->input('email'))) {
                $validator->errors()->add(
                    'email',
                    'Use the same Gmail address from application '.$admission->application_number.'.',
                );
            }

            if ($admission->enrollment()->exists()) {
                $validator->errors()->add(
                    'application_number',
                    'This application number was already used to submit enrollment.',
                );
            }
        });

        if ($validator->fails()) {
            // Keep only files that passed their own validation in a private session draft.
            $this->preserveValidUploads($request, $validator->errors()->keys());

            // Address lookups are GET requests on the same session. Without an
            // explicit return URL, a validation redirect can follow the last
            // barangay JSON endpoint instead of the enrollment form.
            throw (new ValidationException($validator))->redirectTo(route('enrollment.create'));
        }

        $validated = $validator->validated();
        $admission = $currentApplication?->admissionApplication
            ?: $this->approvedAdmissionForEnrollment($request, $validated['email'], $validated['application_number'] ?? null);

        $user = $currentUser ?? new User;
        $userData = [
            ...TraineeUserProfile::attributesFrom($validated),
            'email' => $validated['email'],
            'role' => 'applicant',
            'applicant_status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
        ];

        if (filled($validated['password'] ?? null)) {
            $userData['password'] = $validated['password'];
        }

        $user->forceFill($userData)->save();

        $receivedNewIdPhoto = $request->hasFile('id_photo')
            || filled(data_get($request->session()->get('enrollment.draft_uploads'), 'id_photo.path'));

        $documentPaths = [
            'birth_certificate_path' => $this->storeUploadedDocument($request, 'birth_certificate', $user, $currentApplication?->birth_certificate_path),
            'education_document_path' => $this->storeUploadedDocument($request, 'education_document', $user, $currentApplication?->education_document_path),
            'good_moral_certificate_path' => $this->storeUploadedDocument($request, 'good_moral_certificate', $user, $currentApplication?->good_moral_certificate_path),
            'id_photo_path' => $this->storeUploadedDocument($request, 'id_photo', $user, $currentApplication?->id_photo_path),
            'signature_type' => $validated['signature_type'],
            'signature_path' => $this->storeSignature($request, $user, $currentApplication?->signature_path),
        ];

        if ($receivedNewIdPhoto && filled($documentPaths['id_photo_path'])) {
            $profilePhotos->syncFromPrivateDisk($user, $documentPaths['id_photo_path']);
        }

        $applicationData = collect($validated)
            ->except([
                'password',
                'password_confirmation',
                'application_number',
                'birth_certificate',
                'education_document',
                'good_moral_certificate',
                'id_photo',
                'signature_data',
                'signature_upload',
            ])
            ->merge([
                'user_id' => $user->id,
                'admission_application_id' => $currentApplication?->admission_application_id ?: $admission?->id,
                'program' => $currentApplication?->program ?: $enrollmentBatch?->program?->name,
                'training_program_id' => $currentApplication?->training_program_id ?: $enrollmentBatch?->training_program_id,
                'training_batch_id' => $currentApplication?->training_batch_id ?: $enrollmentBatch?->id,
                'total_program_fee' => $currentApplication?->total_program_fee ?: $enrollmentBatch?->program?->total_program_fee,
                'downpayment_amount' => $currentApplication?->downpayment_amount ?: $enrollmentBatch?->program?->downpayment_amount,
                'payment_amount' => $currentApplication?->payment_amount ?: $enrollmentBatch?->program?->downpayment_amount,
                'privacy_consent' => true,
                'date_accomplished' => now()->toDateString(),
                'status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
            ])
            ->when($isDeniedResubmission, fn ($data) => $data->merge([
                'admin_notes' => null,
                'reviewed_at' => null,
                'reviewed_by_id' => null,
            ]))
            ->merge($documentPaths)
            ->all();

        $application = EnrollmentApplication::updateOrCreate(
            ['user_id' => $user->id],
            $applicationData,
        );

        if (blank($application->enrollment_number)) {
            $application->forceFill([
                'enrollment_number' => EnrollmentApplication::generateNumber(),
            ])->save();
        }

        $this->resetUnpaidPaymentChoice($application);

        $this->clearDraftUploads($request);
        $request->session()->forget('enrollment.google_identity');
        $request->session()->forget('enrollment.admission_application_id');

        if ($currentApplication) {
            $review = $application->document_review ?? [];
            $replacements = [
                'birth-certificate' => $request->hasFile('birth_certificate'),
                'education-document' => $request->hasFile('education_document'),
                'good-moral-certificate' => $request->hasFile('good_moral_certificate'),
                'id-photo' => $request->hasFile('id_photo'),
                'signature' => $request->hasFile('signature_upload') || filled($request->input('signature_data')),
            ];

            // A replacement must return to the admin queue instead of retaining an old acceptance/problem result.
            foreach ($replacements as $document => $wasReplaced) {
                if ($wasReplaced) {
                    $review[$document] = [
                        'status' => 'unreviewed',
                        'note' => 'Replacement uploaded; awaiting admin review.',
                    ];
                }
            }

            $application->forceFill(['document_review' => $review])->save();
        }

        if ($isDeniedResubmission) {
            AdminActivityLog::record($currentUser, 'enrollment.denied.resubmitted', $application, [
                'previous_status' => EnrollmentApplication::STATUS_DENIED,
                'previous_admin_notes' => $previousDenialNote,
                'payment_clearance_preserved' => $application->hasEnrollmentPaymentClearance(),
            ]);
        }

        // Keep payment continuation private to this browser session. Creating
        // an applicant record must not silently sign the person into the
        // public account bar; explicit login remains the account boundary.
        $request->session()->put('enrollment.payment_application_id', $application->id);

        try {
            $user->notifyNow(new EnrollmentSubmittedNotification($application));
        } catch (Throwable $exception) {
            // SMTP delivery must not roll back a valid enrollment submission.
            report($exception);
        }

        $paymentNotice = $isDeniedResubmission
            ? 'Your corrected enrollment was resubmitted for admin review. Your existing verified payment remains recorded.'
            : ($application->program ?: 'Training program').' enrollment registration saved. Choose your payment method to continue.';

        if ($request->expectsJson()) {
            $request->session()->flash('payment_notice', $paymentNotice);

            return response()->json([
                'message' => $paymentNotice,
                'redirect' => route('payment.show'),
            ]);
        }

        return redirect()
            ->route('payment.show')
            ->with('payment_notice', $paymentNotice);
    }

    private function lockedEducationalAttainmentFromRequest(Request $request, ?EnrollmentApplication $application): ?string
    {
        $admission = $this->unlockedAdmission($request, $application)
            ?: AdmissionApplication::findByNumber((string) $request->input('application_number'));

        if ($admission && ! $admission->isApproved()) {
            $admission = null;
        }

        return $this->lockedEducationalAttainment($application, $admission);
    }

    private function lockedEducationalAttainment(?EnrollmentApplication $application, ?AdmissionApplication $admission): ?string
    {
        foreach ([$admission, $application?->admissionApplication] as $source) {
            $value = trim((string) ($source?->educational_attainment ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function unlockedAdmission(Request $request, ?EnrollmentApplication $application): ?AdmissionApplication
    {
        if ($application?->admissionApplication) {
            return $application->admissionApplication;
        }

        $fromQuery = AdmissionApplication::findByNumber($request->query('application_number'));
        if ($fromQuery?->isApproved() && ! $fromQuery->enrollment()->exists()) {
            $request->session()->put('enrollment.admission_application_id', $fromQuery->id);

            return $fromQuery;
        }

        $sessionId = $request->session()->get('enrollment.admission_application_id');
        if (! is_numeric($sessionId)) {
            return null;
        }

        $admission = AdmissionApplication::query()->find((int) $sessionId);

        if (! $admission?->isApproved()) {
            $request->session()->forget('enrollment.admission_application_id');

            return null;
        }

        if ($admission->enrollment()->exists() && $admission->enrollment?->user_id !== $request->user()?->id) {
            $request->session()->forget('enrollment.admission_application_id');

            return null;
        }

        return $admission;
    }

    private function approvedAdmissionForEnrollment(Request $request, string $email, mixed $applicationNumber): ?AdmissionApplication
    {
        $admission = AdmissionApplication::findByNumber(is_string($applicationNumber) ? $applicationNumber : null)
            ?: AdmissionApplication::query()->find($request->session()->get('enrollment.admission_application_id'));

        if (! $admission?->isApproved()) {
            return null;
        }

        if (Str::lower($admission->email) !== Str::lower($email)) {
            return null;
        }

        if ($admission->enrollment()->exists()) {
            return null;
        }

        return $admission;
    }

    /** @return array{email: string, first_name: string, middle_name: string, last_name: string, full_name: string, avatar_url: ?string} */
    private function googleIdentity(Request $request): array
    {
        $user = $request->user();

        if (! $user || blank($user->google_id)) {
            return [
                'email' => '',
                'first_name' => '',
                'middle_name' => '',
                'last_name' => '',
                'full_name' => '',
                'avatar_url' => null,
            ];
        }

        $identity = $request->session()->get('enrollment.google_identity', []);

        if (is_array($identity) && Str::lower((string) ($identity['email'] ?? '')) === Str::lower($user->email)) {
            return [
                'email' => $user->email,
                'first_name' => trim((string) ($identity['first_name'] ?? '')),
                'middle_name' => trim((string) ($identity['middle_name'] ?? '')),
                'last_name' => trim((string) ($identity['last_name'] ?? '')),
                'full_name' => trim((string) ($identity['full_name'] ?? $user->name)),
                'avatar_url' => $identity['avatar_url'] ?? $user->profilePhotoUrl(),
            ];
        }

        $parts = preg_split('/\s+/u', trim((string) $user->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $firstName = (string) array_shift($parts);
        $lastName = count($parts) > 0 ? (string) array_pop($parts) : '';

        return [
            'email' => $user->email,
            'first_name' => $firstName,
            'middle_name' => implode(' ', $parts),
            'last_name' => $lastName,
            'full_name' => (string) $user->name,
            'avatar_url' => $user->profilePhotoUrl(),
        ];
    }

    private function storeUploadedDocument(Request $request, string $field, User $user, ?string $existingPath): ?string
    {
        if ($request->hasFile($field)) {
            $this->forgetDraftUpload($request, $field);

            return $request->file($field)->store("enrollment-documents/{$user->id}", 'local');
        }

        return $this->moveDraftUpload($request, $field, $user) ?: $existingPath;
    }

    private function storeSignature(Request $request, User $user, ?string $existingPath): ?string
    {
        if ($request->input('signature_type') === 'upload') {
            return $this->storeUploadedDocument($request, 'signature_upload', $user, $existingPath);
        }

        $signatureData = $request->input('signature_data');

        if (! $signatureData && $existingPath) {
            return $existingPath;
        }

        if (! is_string($signatureData) || ! str_starts_with($signatureData, 'data:image/png;base64,')) {
            throw ValidationException::withMessages([
                'signature_data' => 'Draw your signature before submitting.',
            ]);
        }

        $decodedSignature = base64_decode(substr($signatureData, strlen('data:image/png;base64,')), true);

        if (! $decodedSignature || strlen($decodedSignature) > 5 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'signature_data' => 'The drawn signature could not be saved. Please clear and draw it again.',
            ]);
        }

        $path = "enrollment-documents/{$user->id}/signature-".now()->format('YmdHis').'.png';
        Storage::disk('local')->put($path, $decodedSignature);

        return $path;
    }

    /** @return array<int, string> */
    private function uploadFields(): array
    {
        return [
            'birth_certificate',
            'education_document',
            'good_moral_certificate',
            'id_photo',
            'signature_upload',
        ];
    }

    /** @param array<int, string> $invalidFields */
    private function preserveValidUploads(Request $request, array $invalidFields): void
    {
        $drafts = $request->session()->get('enrollment.draft_uploads', []);
        $directory = 'enrollment-drafts/'.hash('sha256', $request->session()->getId());

        foreach ($this->uploadFields() as $field) {
            if (! $request->hasFile($field) || in_array($field, $invalidFields, true)) {
                continue;
            }

            if ($oldPath = data_get($drafts, "{$field}.path")) {
                Storage::disk('local')->delete($oldPath);
            }

            $file = $request->file($field);
            $path = $file->storeAs($directory, Str::random(40).'.'.$file->extension(), 'local');
            $drafts[$field] = [
                'path' => $path,
                'name' => basename($file->getClientOriginalName()),
                'mime' => $file->getMimeType() ?: 'application/octet-stream',
                'size' => $file->getSize(),
            ];
        }

        $request->session()->put('enrollment.draft_uploads', $drafts);
    }

    private function moveDraftUpload(Request $request, string $field, User $user): ?string
    {
        $drafts = $request->session()->get('enrollment.draft_uploads', []);
        $source = data_get($drafts, "{$field}.path");

        if (! is_string($source) || ! Storage::disk('local')->exists($source)) {
            return null;
        }

        $extension = pathinfo($source, PATHINFO_EXTENSION);
        $destination = "enrollment-documents/{$user->id}/".Str::random(40).($extension ? '.'.$extension : '');
        Storage::disk('local')->move($source, $destination);
        unset($drafts[$field]);
        $request->session()->put('enrollment.draft_uploads', $drafts);

        return $destination;
    }

    private function forgetDraftUpload(Request $request, string $field): void
    {
        $drafts = $request->session()->get('enrollment.draft_uploads', []);
        $path = data_get($drafts, "{$field}.path");

        if (is_string($path)) {
            Storage::disk('local')->delete($path);
        }

        unset($drafts[$field]);
        $request->session()->put('enrollment.draft_uploads', $drafts);
    }

    private function resetUnpaidPaymentChoice(EnrollmentApplication $application): void
    {
        $application->refresh();

        if ($application->hasEnrollmentPaymentClearance()) {
            return;
        }

        PaymentAttempt::query()
            ->where('enrollment_application_id', $application->getKey())
            ->where('provider', 'paymongo')
            ->whereIn('status', [
                PaymentAttempt::STATUS_CREATING,
                PaymentAttempt::STATUS_PENDING,
            ])
            ->update([
                'status' => PaymentAttempt::STATUS_EXPIRED,
                'expired_at' => now(),
            ]);

        $application->forceFill([
            'payment_method' => null,
            'payment_status' => EnrollmentApplication::PAYMENT_NOT_SELECTED,
            'payment_reference' => null,
            'payment_receipt_number' => null,
            'payment_receipt_expires_at' => null,
            'payment_selected_at' => null,
            'paymongo_checkout_reference' => null,
            'paymongo_checkout_url' => null,
            'payment_meta' => null,
        ])->save();
    }

    private function clearDraftUploads(Request $request): void
    {
        foreach ($request->session()->get('enrollment.draft_uploads', []) as $draft) {
            if ($path = data_get($draft, 'path')) {
                Storage::disk('local')->delete($path);
            }
        }

        $request->session()->forget('enrollment.draft_uploads');
    }
}

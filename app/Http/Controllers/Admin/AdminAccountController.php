<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\StaffAccountCredentialsMail;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\PaymentTransaction;
use App\Models\TrainingBatch;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\AccountDeletionService;
use App\Services\StaffVisiblePhoto;
use App\Services\RollingModuleReleaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Throwable;

class AdminAccountController extends Controller
{
    public function index(Request $request): View
    {
        $roleFilter = $request->query('role', 'all');
        $search = trim((string) $request->query('search', ''));
        $visibleOperationalAccounts = function ($accounts): void {
            $accounts->whereIn('role', ['trainer', 'trainee'])
                ->orWhere(function ($applicants): void {
                    $applicants->where('role', 'applicant')
                        ->where(function ($eligible): void {
                            $eligible
                                ->whereHas('enrollmentApplication', fn ($application) => $application->releasedForReview())
                                ->orWhereHas('historicalAlumniClaim');
                        });
                });
        };

        $query = User::query()
            ->whereIn('role', ['trainer', 'trainee', 'applicant'])
            ->where($visibleOperationalAccounts)
            ->with(['enrollmentApplication.batch', 'roles', 'alumniProfile'])
            ->when($roleFilter !== 'all' && in_array($roleFilter, ['trainer', 'trainee', 'applicant', 'alumni'], true), function ($q) use ($roleFilter) {
                if ($roleFilter === 'alumni') {
                    $q->whereHas('enrollmentApplication', fn ($application) => $application
                        ->where('status', EnrollmentApplication::STATUS_APPROVED)
                        ->where('learning_status', EnrollmentApplication::LEARNING_GRADUATED));

                    return;
                }

                $q->where('role', $roleFilter);
                if ($roleFilter === 'trainee') {
                    $q->whereDoesntHave('enrollmentApplication', fn ($application) => $application
                        ->where('status', EnrollmentApplication::STATUS_APPROVED)
                        ->where('learning_status', EnrollmentApplication::LEARNING_GRADUATED));
                }
            })
            ->when(filled($search), function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('enrollmentApplication', function ($appQ) use ($search) {
                            $appQ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('contact_number', 'like', "%{$search}%");
                        });
                });
            })
            ->latest();

        $accounts = $query->paginate(20)->withQueryString();

        $counts = [
            'all' => User::query()->where($visibleOperationalAccounts)->count(),
            'trainer' => User::query()->where('role', 'trainer')->count(),
            'trainee' => User::query()
                ->where('role', 'trainee')
                ->whereDoesntHave('enrollmentApplication', fn ($application) => $application
                    ->where('status', EnrollmentApplication::STATUS_APPROVED)
                    ->where('learning_status', EnrollmentApplication::LEARNING_GRADUATED))
                ->count(),
            'applicant' => User::query()
                ->where('role', 'applicant')
                ->where(function ($eligible): void {
                    $eligible
                        ->whereHas('enrollmentApplication', fn ($application) => $application->releasedForReview())
                        ->orWhereHas('historicalAlumniClaim');
                })
                ->count(),
            'alumni' => User::query()->whereHas('enrollmentApplication', fn ($application) => $application
                ->where('status', EnrollmentApplication::STATUS_APPROVED)
                ->where('learning_status', EnrollmentApplication::LEARNING_GRADUATED))->count(),
        ];

        return view('admin.accounts', [
            'batches' => TrainingBatch::query()->with('program')->orderByDesc('year')->orderBy('name')->get(),
            'defaultProgram' => TrainingProgram::query()->active()->oldest('id')->first(),
            'accounts' => $accounts,
            'roleFilter' => $roleFilter,
            'search' => $search,
            'counts' => $counts,
        ]);
    }

    public function photo(User $user, StaffVisiblePhoto $photos): BinaryFileResponse
    {
        abort_unless(in_array($user->role, ['trainer', 'trainee', 'applicant', 'alumni'], true), 404);

        $located = $photos->locate($user, $user->enrollmentApplication);
        abort_unless($located !== null, 404);

        $fallbackFilename = str($located['filename'])->ascii()->replaceMatches('/[^A-Za-z0-9._-]/', '-')->toString();

        return response()->file($located['path'], [
            'Content-Type' => $located['mime'],
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_INLINE,
                $located['filename'],
                $fallbackFilename
            ),
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function storeTrainer(Request $request): RedirectResponse
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validateWithBag('trainer', [
            'name' => ['required', 'string', 'max:255', 'regex:/\A[\pL\pM .\'-]+\z/u'],
            'email' => [
                'required', 'email:rfc', 'max:255',
                Rule::unique('users', 'email'),
                Rule::unique('enrollment_applications', 'email'),
            ],
        ], [
            'name.regex' => 'Use letters, spaces, periods, apostrophes, or hyphens only for the name.',
            'email.unique' => 'This email is already connected to an MCARE account or enrollment.',
        ]);

        $plainPassword = $this->generateTemporaryPassword();

        $trainer = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $plainPassword,
            'role' => 'trainer',
            'applicant_status' => 'staff_created',
        ]);

        AdminActivityLog::record($request->user(), 'admin.trainer.created', $trainer, [
            'email' => $trainer->email,
        ]);

        return $this->accountCreatedResponse($request, $trainer, "Trainer account created for {$trainer->name}.", $plainPassword);
    }

    public function storeTrainee(
        Request $request,
        RollingModuleReleaseService $releases,
    ): RedirectResponse {
        $request->merge([
            'first_name' => trim((string) $request->input('first_name')),
            'middle_name' => filled($request->input('middle_name')) ? trim((string) $request->input('middle_name')) : null,
            'last_name' => trim((string) $request->input('last_name')),
            'email' => Str::lower(trim((string) $request->input('email'))),
            'contact_number' => trim((string) $request->input('contact_number')),
        ]);

        $validated = $request->validateWithBag('trainee', [
            'first_name' => ['required', 'string', 'max:100', 'regex:/\A[\pL\pM .\'-]+\z/u'],
            'middle_name' => ['nullable', 'string', 'max:100', 'regex:/\A[\pL\pM .\'-]+\z/u'],
            'last_name' => ['required', 'string', 'max:100', 'regex:/\A[\pL\pM .\'-]+\z/u'],
            'email' => [
                'required', 'email:rfc', 'max:255',
                Rule::unique('users', 'email'),
                Rule::unique('enrollment_applications', 'email'),
            ],
            'training_batch_id' => ['required', 'integer', 'exists:training_batches,id'],
            'birth_date' => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::in(['Male', 'Female'])],
            'contact_number' => ['required', 'string', 'max:30', 'regex:/\A[0-9+(). \-]{7,30}\z/'],
            'schedule_preference' => ['required', Rule::in(['AM', 'PM'])],
            'street' => ['required', 'string', 'max:180'],
            'barangay' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'province' => ['required', 'string', 'max:120'],
            'zip_code' => ['required', 'string', 'max:20'],
            'educational_attainment' => ['required', 'string', 'max:150'],
            'school_name' => ['required', 'string', 'max:180'],
            'year_graduated' => ['required', 'integer', 'min:1950', 'max:'.now()->year],
            'birth_certificate_onsite' => ['accepted'],
            'education_document_onsite' => ['accepted'],
            'good_moral_onsite' => ['accepted'],
            'id_photo_onsite' => ['accepted'],
            'signature_onsite' => ['accepted'],
            'privacy_consent_onsite' => ['accepted'],
            'onsite_payment_received' => ['accepted'],
            'onsite_payment_amount' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'onsite_or_number' => ['required', 'string', 'max:100', Rule::unique('payment_transactions', 'or_number')],
            'onsite_verification_notes' => ['required', 'string', 'max:1000'],
        ], [
            '*.regex' => 'Use letters, spaces, periods, apostrophes, or hyphens only for names and a valid phone format for contact number.',
            'email.unique' => 'This email is already connected to an MCARE account or enrollment.',
            '*.accepted' => 'Confirm every onsite requirement, consent, and payment item before activating the trainee.',
        ]);

        $batch = TrainingBatch::query()->with('program')->findOrFail($validated['training_batch_id']);
        $program = $batch->program ?? TrainingProgram::query()->active()->oldest('id')->first();

        if (! $program || ! $program->is_active) {
            throw ValidationException::withMessages([
                'training_batch_id' => 'Assign this batch to an active training program before assisted intake.',
            ])->errorBag('trainee');
        }

        $programFee = (float) $program->total_program_fee;
        $requiredDownpayment = (float) $program->downpayment_amount;
        $verifiedAmount = (float) $validated['onsite_payment_amount'];

        if ($verifiedAmount < $requiredDownpayment || $verifiedAmount > $programFee) {
            throw ValidationException::withMessages([
                'onsite_payment_amount' => sprintf(
                    'Enter a verified amount from PHP %s to PHP %s for %s.',
                    number_format($requiredDownpayment, 2),
                    number_format($programFee, 2),
                    $program->name,
                ),
            ])->errorBag('trainee');
        }

        $plainPassword = $this->generateTemporaryPassword();

        [$trainee, $application] = DB::transaction(function () use ($request, $validated, $program, $programFee, $requiredDownpayment, $verifiedAmount, $plainPassword) {
            $trainee = User::create([
                'name' => trim("{$validated['first_name']} {$validated['middle_name']} {$validated['last_name']}"),
                'email' => $validated['email'],
                'password' => $plainPassword,
                'role' => 'trainee',
                'applicant_status' => 'staff_created',
            ]);

            $application = EnrollmentApplication::create([
                ...collect($validated)->except([
                    'password', 'password_confirmation',
                    'birth_certificate_onsite', 'education_document_onsite',
                    'good_moral_onsite', 'id_photo_onsite', 'signature_onsite',
                    'privacy_consent_onsite', 'onsite_payment_received',
                    'onsite_payment_amount', 'onsite_or_number', 'onsite_verification_notes',
                ])->all(),
                'user_id' => $trainee->id,
                'training_program_id' => $program->id,
                'program' => $program->name,
                'intake_channel' => 'admin_assisted',
                'is_historical_record' => false,
                'status' => EnrollmentApplication::STATUS_APPROVED,
                'learning_status' => EnrollmentApplication::LEARNING_ACTIVE,
                'privacy_consent' => true,
                'date_accomplished' => today(),
                'document_review' => collect([
                    'birth_certificate' => 'Birth Certificate',
                    'education_document' => 'Form 137/138 or Diploma',
                    'good_moral_certificate' => 'Good Moral Certificate',
                    'id_photo' => 'ID Photo',
                    'signature' => 'Signature and consent',
                ])->map(fn (string $label): array => [
                    'status' => 'accepted',
                    'note' => "{$label} original was inspected during admin-assisted onsite intake.",
                ])->all(),
                'documents_reviewed_at' => now(),
                'documents_reviewed_by_id' => $request->user()->id,
                'onsite_requirements_verified_at' => now(),
                'onsite_requirements_verified_by_id' => $request->user()->id,
                'onsite_requirements_notes' => $validated['onsite_verification_notes'],
                'payment_method' => 'onsite',
                'total_program_fee' => $programFee,
                'downpayment_amount' => $requiredDownpayment,
                'total_paid_amount' => $verifiedAmount,
                'payment_status' => $verifiedAmount >= $programFee
                    ? EnrollmentApplication::PAYMENT_PAID
                    : EnrollmentApplication::PAYMENT_PARTIALLY_PAID,
                'payment_amount' => $verifiedAmount,
                'payment_currency' => 'PHP',
                'payment_reference' => $validated['onsite_or_number'],
                'payment_receipt_number' => $validated['onsite_or_number'],
                'payment_selected_at' => now(),
                'payment_verified_at' => now(),
                'payment_verified_by_id' => $request->user()->id,
                'payment_verification_notes' => $validated['onsite_verification_notes'],
                'review_released_at' => now(),
                'reviewed_at' => now(),
                'learning_started_at' => now(),
                'reviewed_by_id' => $request->user()->id,
                'admin_notes' => 'Admin-assisted onsite trainee intake. Required originals and payment were verified before activation.',
            ]);

            PaymentTransaction::create([
                'enrollment_application_id' => $application->id,
                'user_id' => $trainee->id,
                'recorded_by_admin_id' => $request->user()->id,
                'transaction_type' => $verifiedAmount >= $programFee
                    ? PaymentTransaction::TYPE_FULL_PAYMENT
                    : PaymentTransaction::TYPE_DOWNPAYMENT,
                'payment_channel' => PaymentTransaction::CHANNEL_ONSITE,
                'amount' => $verifiedAmount,
                'reference_number' => $validated['onsite_or_number'],
                'or_number' => $validated['onsite_or_number'],
                'status' => PaymentTransaction::STATUS_VERIFIED,
                'paid_at' => now(),
                'verified_at' => now(),
                'verified_by_id' => $request->user()->id,
                'notes' => $validated['onsite_verification_notes'],
            ]);

            return [$trainee, $application];
        });

        AdminActivityLog::record($request->user(), 'admin.trainee.assisted-created', $application, [
            'user_id' => $trainee->id,
            'email' => $trainee->email,
            'onsite_requirements_verified' => true,
            'onsite_payment_verified' => true,
            'payment_reference' => $validated['onsite_or_number'],
        ]);

        $releases->assignCurrentTo($application);

        return $this->accountCreatedResponse($request, $trainee, "Assisted trainee intake completed for {$trainee->name}.", $plainPassword);
    }

    public function destroy(Request $request, User $user, AccountDeletionService $accounts): RedirectResponse
    {
        try {
            $deleted = $accounts->delete($user, $request->user());
        } catch (ValidationException $exception) {
            return redirect()->route('admin.accounts.index')->withErrors($exception->errors());
        }

        return redirect()
            ->route('admin.accounts.index')
            ->with('saved', "Account for {$deleted['label']} ({$deleted['email']}) and related records were permanently removed. The applicant can now submit a fresh enrollment if needed.");
    }

    private function accountCreatedResponse(Request $request, User $user, string $message, string $plainPassword): RedirectResponse
    {
        $credentialsSent = $this->sendStaffAccountCredentials($request, $user, $plainPassword);
        $verificationSent = true;

        try {
            $user->sendEmailVerificationNotification();
        } catch (Throwable $exception) {
            report($exception);
            $verificationSent = false;
            AdminActivityLog::record($request->user(), 'admin.account.email-verification.failed', $user, [
                'email' => $user->email,
            ]);
        }

        $saved = $credentialsSent
            ? "{$message} A temporary password was emailed to {$user->email}."
            : "{$message} The account was created, but the password email could not be sent. Check the SMTP mail configuration.";

        return redirect()
            ->route('admin.accounts.index')
            ->with('saved', $saved)
            ->with('verification_notice', $verificationSent
                ? "A verification link was sent to {$user->email}."
                : 'The account was created, but the verification email could not be sent. Check the mail configuration and resend later.');
    }

    private function sendStaffAccountCredentials(Request $request, User $user, string $plainPassword): bool
    {
        try {
            Mail::to($user->email)->send(new StaffAccountCredentialsMail($user, $plainPassword));

            return true;
        } catch (Throwable $exception) {
            report($exception);
            AdminActivityLog::record($request->user(), 'admin.account.credentials-email.failed', $user, [
                'email' => $user->email,
            ]);

            return false;
        }
    }

    private function generateTemporaryPassword(): string
    {
        $sets = [
            'abcdefghijkmnopqrstuvwxyz',
            'ABCDEFGHJKLMNPQRSTUVWXYZ',
            '23456789',
            '!@#$%*?',
        ];

        $characters = [];
        foreach ($sets as $set) {
            $characters[] = $set[random_int(0, strlen($set) - 1)];
        }

        $pool = implode('', $sets);
        while (count($characters) < 14) {
            $characters[] = $pool[random_int(0, strlen($pool) - 1)];
        }

        for ($index = count($characters) - 1; $index > 0; $index--) {
            $swap = random_int(0, $index);
            [$characters[$index], $characters[$swap]] = [$characters[$swap], $characters[$index]];
        }

        return implode('', $characters);
    }
}

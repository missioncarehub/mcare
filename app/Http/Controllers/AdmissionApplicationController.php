<?php

namespace App\Http\Controllers;

use App\Mail\AdmissionApplicationReceivedMail;
use App\Models\AdminActivityLog;
use App\Models\AdmissionApplication;
use App\Models\TrainingProgram;
use App\Services\AdminOperationsNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
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
        $number = AdmissionApplication::normalizeNumber($request->input('application_number', $request->query('application_number')));
        $admission = $number !== '' ? AdmissionApplication::findByNumber($number) : null;
        $lookedUp = $request->isMethod('post') || $request->filled('application_number');

        return view('applications.status', [
            'admission' => $admission,
            'lookedUp' => $lookedUp,
            'submittedNumber' => $request->input('application_number', $request->query('application_number', '')),
        ]);
    }

    public function lookup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'application_number' => ['required', 'string', 'max:40'],
        ]);

        return redirect()->route('applications.status', [
            'application_number' => AdmissionApplication::normalizeNumber($validated['application_number']),
        ]);
    }
}

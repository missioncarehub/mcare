<?php

namespace App\Http\Controllers\Trainee;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\AdminAnnouncement;
use App\Models\CareerOpportunity;
use App\Models\EnrollmentApplication;
use App\Models\ModuleProgress;
use App\Models\OfficialDocument;
use App\Models\PaymentAttempt;
use App\Models\PaymentTransaction;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\TrainerAnnouncement;
use App\Models\TrainingModule;
use App\Models\TrainingSubmodule;
use App\Models\TrainingSubmoduleProgress;
use App\Services\ClassroomComments;
use App\Services\CompletionEligibilityService;
use App\Services\LearningPdfWatermark;
use App\Services\ModuleAssessmentService;
use App\Services\ModuleSubmoduleService;
use App\Services\TraineeClassworkSequence;
use App\Services\TrainingCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TraineeDashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        return $this->portalView($request, 'trainee.dashboard');
    }

    public function modules(Request $request): View|RedirectResponse
    {
        return $this->portalView($request, 'trainee.modules.index');
    }

    public function grades(Request $request): View|RedirectResponse
    {
        $application = $this->approvedApplicationFor($request);

        if (! $application) {
            return redirect()
                ->route('payment.show')
                ->with('payment_notice', 'Your trainee dashboard opens after admin approval.');
        }

        return $this->portalView($request, 'trainee.grades', [
            'gradeRecords' => $this->evaluatedGradesFor($application)->get(),
            'competencyRecords' => $application->competencyRecords()->with('unit')->get(),
        ], $application);
    }

    public function stream(Request $request): View|RedirectResponse
    {
        $application = $this->approvedApplicationFor($request);

        if (! $application) {
            return redirect()
                ->route('payment.show')
                ->with('payment_notice', 'Your trainee classroom opens after admin approval.');
        }

        $application->load('batch');

        $adminAnnouncements = AdminAnnouncement::visibleTo($request->user(), $application->training_batch_id)
            ->latest('posted_at')
            ->get();

        return view('trainee.stream', [
            'application' => $application,
            'announcements' => $this->visibleAnnouncementsFor($application)
                ->with(['batch', 'trainer'])
                ->orderByDesc('is_pinned')
                ->orderByDesc('posted_at')
                ->paginate(15),
            'adminAnnouncements' => $adminAnnouncements,
            'upcomingModules' => $this->availableModulesFor($application)
                ->limit(5)
                ->get(),
            'upcomingQuizzes' => $this->availableQuizzesFor($application)
                ->where(fn ($query) => $query->whereNull('due_at')->orWhere('due_at', '>=', now()))
                ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('due_at')
                ->limit(5)
                ->get()
                ->filter(fn (Quiz $quiz): bool => $quiz->targets($application))
                ->values(),
        ]);
    }

    public function schedule(Request $request, TrainingCalendarService $calendarService): View|RedirectResponse
    {
        $application = $this->approvedApplicationFor($request);

        if (! $application) {
            return redirect()
                ->route('payment.show')
                ->with('payment_notice', 'Your trainee dashboard opens after admin approval.');
        }

        $application->load('batch');
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $month = isset($validated['month'])
            ? Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth()
            : $calendarService->suggestedMonth($application->batch);

        if ($application->learning_status === EnrollmentApplication::LEARNING_GRADUATED) {
            $careerSessions = CareerOpportunity::query()
                ->visibleToAlumni()
                ->whereBetween('estimated_start_date', [
                    $month->copy()->startOfMonth()->toDateString(),
                    $month->copy()->endOfMonth()->toDateString(),
                ])
                ->orderBy('estimated_start_date')
                ->get()
                ->map(function (CareerOpportunity $opportunity): array {
                    $startsAt = $opportunity->estimated_start_date->copy()->startOfDay();

                    return [
                        'date_key' => $startsAt->toDateString(),
                        'period' => 'CAREER',
                        'calendar_title' => 'Caregiving opportunity',
                        'title' => 'Caregiving opportunity',
                        'time' => 'Start',
                        'time_range' => 'Estimated start date',
                        'starts_at' => $startsAt,
                        'batch' => $opportunity->mobilityStatusLabel(),
                        'room' => 'Open Career Hub for privacy-approved details',
                    ];
                });

            return $this->portalView($request, 'trainee.schedule', [
                'calendarMonth' => $month,
                'calendarSessions' => $careerSessions,
                'calendarSelectedDate' => $validated['date'] ?? null,
                'isGraduate' => true,
            ], $application);
        }

        $sessions = $application->batch
            ? $calendarService->month($application->batch, $month, $application->schedule_preference)
            : collect();

        return $this->portalView($request, 'trainee.schedule', [
            'calendarMonth' => $month,
            'calendarSessions' => $sessions,
            'calendarSelectedDate' => $validated['date'] ?? null,
        ], $application);
    }

    public function payments(Request $request): View|RedirectResponse
    {
        $application = $this->approvedApplicationFor($request);

        if (! $application) {
            return redirect()
                ->route('payment.show')
                ->with('payment_notice', 'Your trainee dashboard opens after admin approval.');
        }

        $application->load(['paymentTransactions.recordedByAdmin', 'paymentTransactions.verifier']);
        $remainingBalance = $application->remainingBalance();

        return $this->portalView($request, 'trainee.payments', [
            'transactions' => $application->paymentTransactions,
            'totalFee' => (float) ($application->total_program_fee ?? 22000.00),
            'downpayment' => (float) ($application->downpayment_amount ?? 2000.00),
            'totalPaid' => (float) ($application->total_paid_amount ?? 0.00),
            'balance' => $remainingBalance,
            'activeOnsiteTicket' => $application->paymentTransactions->first(
                fn (PaymentTransaction $transaction): bool => $transaction->isOnsiteTicket(),
            ),
            'ticketDefaultAmount' => min(
                $remainingBalance,
                $application->isDownpaymentSatisfied()
                    ? 5000.00
                    : (float) ($application->downpayment_amount ?? 2000.00),
            ),
        ], $application);
    }

    public function generateOnsiteTicket(Request $request): RedirectResponse
    {
        $application = $this->approvedApplicationFor($request);
        abort_unless($application, 403);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:100000'],
            'transaction_type' => ['required', Rule::in(array_keys(PaymentTransaction::types()))],
        ]);

        $result = DB::transaction(function () use ($request, $validated, $application): array {
            $lockedApplication = EnrollmentApplication::query()
                ->whereKey($application->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // Repeated clicks return the same active ticket instead of creating duplicate requests.
            $existingTicket = PaymentTransaction::query()
                ->where('enrollment_application_id', $lockedApplication->id)
                ->where('payment_channel', PaymentTransaction::CHANNEL_ONSITE)
                ->where('status', PaymentTransaction::STATUS_PENDING)
                ->whereNotNull('ticket_number')
                ->latest()
                ->first();

            if ($existingTicket) {
                return ['ticket' => $existingTicket, 'created' => false];
            }

            if ($lockedApplication->payment_status === EnrollmentApplication::PAYMENT_PAID) {
                throw ValidationException::withMessages([
                    'payment' => 'Your tuition is already fully paid. No new on-site ticket is needed.',
                ]);
            }

            $activeOnlineAttempt = PaymentAttempt::query()
                ->where('enrollment_application_id', $lockedApplication->id)
                ->where('provider', 'paymongo')
                ->whereIn('status', [PaymentAttempt::STATUS_CREATING, PaymentAttempt::STATUS_PENDING])
                ->exists();

            if ($activeOnlineAttempt) {
                throw ValidationException::withMessages([
                    'payment' => 'An online PayMongo checkout is still active. Finish or cancel it before requesting an on-site ticket.',
                ]);
            }

            $remainingBalance = $lockedApplication->remainingBalance();
            $amount = round((float) $validated['amount'], 2);

            if ($remainingBalance <= 0 || $amount > $remainingBalance) {
                throw ValidationException::withMessages([
                    'amount' => 'The ticket amount cannot be greater than your remaining balance of PHP '.number_format($remainingBalance, 2).'.',
                ]);
            }

            $fixedAmount = match ($validated['transaction_type']) {
                PaymentTransaction::TYPE_DOWNPAYMENT => min(
                    (float) ($lockedApplication->downpayment_amount ?? 2000.00),
                    $remainingBalance,
                ),
                PaymentTransaction::TYPE_FULL_PAYMENT,
                PaymentTransaction::TYPE_BALANCE => $remainingBalance,
                default => null,
            };

            if ($fixedAmount !== null && abs($amount - $fixedAmount) > 0.009) {
                throw ValidationException::withMessages([
                    'amount' => 'The selected payment purpose requires a ticket amount of PHP '.number_format($fixedAmount, 2).'.',
                ]);
            }

            $ticketNumber = $this->uniqueOnsiteTicketNumber();
            $ticket = PaymentTransaction::create([
                'enrollment_application_id' => $lockedApplication->id,
                'user_id' => $request->user()->id,
                'ticket_number' => $ticketNumber,
                'reference_number' => $ticketNumber,
                'transaction_type' => $validated['transaction_type'],
                'payment_channel' => PaymentTransaction::CHANNEL_ONSITE,
                'amount' => $amount,
                'status' => PaymentTransaction::STATUS_PENDING,
                'notes' => 'On-site payment ticket generated by trainee; awaiting cashier verification.',
            ]);

            $paymentMeta = $lockedApplication->payment_meta ?? [];
            $paymentMeta['active_onsite_ticket'] = $ticketNumber;

            $lockedApplication->forceFill([
                'payment_method' => 'onsite',
                'payment_status' => $lockedApplication->payment_status === EnrollmentApplication::PAYMENT_NOT_SELECTED
                    ? EnrollmentApplication::PAYMENT_ONSITE_PENDING
                    : $lockedApplication->payment_status,
                'payment_amount' => number_format($amount, 2, '.', ''),
                'payment_currency' => 'PHP',
                'payment_reference' => $lockedApplication->payment_reference ?: $ticketNumber,
                'payment_selected_at' => $lockedApplication->payment_selected_at ?: now(),
                'payment_meta' => $paymentMeta,
            ])->save();

            AdminActivityLog::record($request->user(), 'payment.onsite_ticket.generated', $lockedApplication, [
                'transaction_id' => $ticket->id,
                'ticket_number' => $ticketNumber,
                'amount' => $amount,
                'transaction_type' => $validated['transaction_type'],
            ]);

            return ['ticket' => $ticket, 'created' => true];
        }, 3);

        $ticket = $result['ticket'];
        $message = $result['created']
            ? "On-site payment ticket {$ticket->ticket_number} generated. Present it to the MCARE cashier."
            : "Your active on-site payment ticket is {$ticket->ticket_number}. Present the same ticket to the MCARE cashier.";

        return redirect()
            ->route('trainee.payments')
            ->with('saved', $message);
    }

    public function uploadPaymentProof(Request $request): RedirectResponse
    {
        $application = $this->approvedApplicationFor($request);
        abort_unless($application, 403);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:1', 'max:100000'],
            'or_number' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/',
            ],
            'transaction_type' => ['required', Rule::in(array_keys(PaymentTransaction::types()))],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'receipt_proof' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $proofPath = $request->file('receipt_proof')->store("payment-receipts/{$application->id}", 'local');
        if ($proofPath === false) {
            throw ValidationException::withMessages([
                'receipt_proof' => 'The receipt proof could not be stored. Please try again.',
            ]);
        }

        try {
            $transaction = DB::transaction(function () use ($request, $validated, $application, $proofPath): PaymentTransaction {
                $lockedApplication = EnrollmentApplication::query()
                    ->whereKey($application->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedApplication->payment_status === EnrollmentApplication::PAYMENT_PAID) {
                    throw ValidationException::withMessages([
                        'amount' => 'Your tuition is already fully paid.',
                    ]);
                }

                $activeOnlineAttempt = PaymentAttempt::query()
                    ->where('enrollment_application_id', $lockedApplication->id)
                    ->where('provider', 'paymongo')
                    ->whereIn('status', [PaymentAttempt::STATUS_CREATING, PaymentAttempt::STATUS_PENDING])
                    ->exists();

                if ($activeOnlineAttempt) {
                    throw ValidationException::withMessages([
                        'payment' => 'An online PayMongo checkout is still active. Finish or cancel it before submitting an on-site receipt.',
                    ]);
                }

                $amount = round((float) $validated['amount'], 2);
                $remainingBalance = $lockedApplication->remainingBalance();
                if ($amount > $remainingBalance) {
                    throw ValidationException::withMessages([
                        'amount' => 'The receipt amount cannot exceed your remaining balance of PHP '.number_format($remainingBalance, 2).'.',
                    ]);
                }

                $activeTicket = PaymentTransaction::query()
                    ->where('enrollment_application_id', $lockedApplication->id)
                    ->where('payment_channel', PaymentTransaction::CHANNEL_ONSITE)
                    ->where('status', PaymentTransaction::STATUS_PENDING)
                    ->whereNotNull('ticket_number')
                    ->lockForUpdate()
                    ->first();

                $duplicateOr = PaymentTransaction::query()
                    ->where('or_number', $validated['or_number'])
                    ->when($activeTicket, fn ($query) => $query->where('id', '!=', $activeTicket->id))
                    ->exists();
                if ($duplicateOr) {
                    throw ValidationException::withMessages([
                        'or_number' => 'This official receipt number has already been submitted.',
                    ]);
                }

                if ($activeTicket) {
                    if (
                        abs((float) $activeTicket->amount - $amount) > 0.009
                        || $activeTicket->transaction_type !== $validated['transaction_type']
                    ) {
                        throw ValidationException::withMessages([
                            'amount' => 'The receipt amount and purpose must match your active on-site ticket.',
                        ]);
                    }

                    $activeTicket->update([
                        'or_number' => $validated['or_number'],
                        'receipt_proof_path' => $proofPath,
                        'paid_at' => $validated['paid_at'],
                        'notes' => $validated['notes'] ?? $activeTicket->notes,
                    ]);
                    $transaction = $activeTicket;
                } else {
                    $transaction = PaymentTransaction::create([
                        'enrollment_application_id' => $lockedApplication->id,
                        'user_id' => $request->user()->id,
                        'transaction_type' => $validated['transaction_type'],
                        'payment_channel' => PaymentTransaction::CHANNEL_ONSITE,
                        'amount' => $amount,
                        'reference_number' => $lockedApplication->payment_reference,
                        'or_number' => $validated['or_number'],
                        'receipt_proof_path' => $proofPath,
                        'status' => PaymentTransaction::STATUS_PENDING,
                        'paid_at' => $validated['paid_at'],
                        'notes' => $validated['notes'] ?? null,
                    ]);
                }

                $lockedApplication->forceFill([
                    'payment_method' => 'onsite',
                    'payment_status' => $lockedApplication->payment_status === EnrollmentApplication::PAYMENT_PARTIALLY_PAID
                        ? EnrollmentApplication::PAYMENT_PARTIALLY_PAID
                        : EnrollmentApplication::PAYMENT_ONSITE_PENDING,
                    'payment_receipt_number' => $validated['or_number'],
                    'payment_selected_at' => $lockedApplication->payment_selected_at ?: now(),
                ])->save();

                return $transaction;
            }, 3);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($proofPath);

            throw $exception;
        }

        AdminActivityLog::record($request->user(), 'trainee.payment_receipt.uploaded', $application, [
            'transaction_id' => $transaction->id,
            'amount' => $validated['amount'],
            'or_number' => $validated['or_number'],
        ]);

        return back()->with('saved', 'Proof of on-site payment submitted. An administrator will verify your official receipt.');
    }

    public function documents(
        Request $request,
        CompletionEligibilityService $eligibility,
    ): View|RedirectResponse {
        $application = $this->approvedApplicationFor($request);

        if (! $application) {
            return redirect()
                ->route('payment.show')
                ->with('payment_notice', 'Your trainee dashboard opens after admin approval.');
        }

        return $this->portalView($request, 'trainee.documents', [
            'cotc' => OfficialDocument::query()
                ->where('enrollment_application_id', $application->id)
                ->where('type', OfficialDocument::TYPE_COTC)
                ->where('status', '!=', OfficialDocument::STATUS_REVOKED)
                ->latest('version')
                ->first(),
            'completionEligibility' => $eligibility->evaluate($application),
        ], $application);
    }

    private function portalView(
        Request $request,
        string $view,
        array $extraData = [],
        ?EnrollmentApplication $resolvedApplication = null,
    ): View|RedirectResponse {
        $application = $resolvedApplication ?? $this->approvedApplicationFor($request);

        if (! $application) {
            return redirect()
                ->route('payment.show')
                ->with('payment_notice', 'Your trainee dashboard opens after admin approval.');
        }

        $application->load('batch');
        $isGraduate = $application->learning_status === EnrollmentApplication::LEARNING_GRADUATED;
        if (! $isGraduate) {
            $this->classworkSequence()->syncLocks($application);
        }
        $modules = $isGraduate
            ? collect()
            : $this->classworkSequence()->orderedFor(
                $application,
                $this->assignedModulesFor($application)->get(),
            );
        $classworkAccess = $isGraduate
            ? collect()
            : $this->classworkSequence()->accessByModule($application, $modules);
        $progressByModule = ModuleProgress::query()
            ->where('enrollment_application_id', $application->id)
            ->whereIn('training_module_id', $modules->pluck('id'))
            ->get()
            ->keyBy('training_module_id');
        $assessedModules = $modules->filter->requiresEvaluation();
        $progressPercent = $assessedModules->isEmpty()
            ? 0
            : (int) round($assessedModules->sum(fn ($module) => $progressByModule->get($module->id)?->progress_percent ?? 0) / $assessedModules->count());
        if ($isGraduate && $view === 'trainee.dashboard') {
            // Graduates keep the trainee shell and account, but receive the Career Hub home content.
            $view = 'trainee.graduate-dashboard';
        }
        $graduateData = $isGraduate ? [
            'alumniProfile' => $request->user()->alumniProfile()->firstOrCreate([], [
                'is_available_for_duty' => false,
            ]),
            'careerJobs' => CareerOpportunity::query()
                ->visibleToAlumni()
                ->orderBy('estimated_start_date')
                ->take(3)
                ->get(),
            'evaluatedGradeCount' => $this->evaluatedGradesFor($application)->count(),
        ] : [];

        return view($view, array_merge([
            'application' => $application,
            'batch' => $application->batch,
            'modules' => $modules,
            'classworkAccess' => $classworkAccess,
            'progressByModule' => $progressByModule,
            'announcements' => $this->visibleAnnouncementsFor($application)
                ->with(['batch', 'trainer'])
                ->orderByDesc('is_pinned')
                ->latest('posted_at')
                ->take(5)
                ->get(),
            'stats' => [
                'progress' => $progressPercent,
                'modules' => $modules->count(),
                'documents' => collect([
                    $application->birth_certificate_path,
                    $application->education_document_path,
                    $application->good_moral_certificate_path,
                    $application->id_photo_path,
                    $application->signature_path,
                ])->filter()->count(),
                'payment' => $application->paymentStatusLabel(),
            ],
            'isGraduate' => $isGraduate,
        ], $graduateData, $extraData));
    }

    public function viewModule(
        Request $request,
        TrainingModule $module,
        ClassroomComments $comments,
        ModuleAssessmentService $assessments,
        ModuleSubmoduleService $submodules,
    ): View|RedirectResponse {
        $application = $this->approvedApplicationFor($request);

        if (
            $application
            && ! $application->is_historical_record
            && $application->learning_status !== EnrollmentApplication::LEARNING_GRADUATED
            && ModuleProgress::query()
                ->where('enrollment_application_id', $application->id)
                ->where('training_module_id', $module->id)
                ->exists()
            && ! $this->classworkSequence()->canAccess($application, $module)
        ) {
            return redirect()
                ->route('trainee.modules.index')
                ->with('error', $this->classworkSequence()->lockMessage($application, $module));
        }

        $this->authorizeModule($application, $module);

        // Opening the protected viewer is a server-side progress event and does not depend on JavaScript.
        $progress = $this->touchModuleProgress($application, $module);
        $submodules->assignProgress($progress);
        $progress->load('evaluator');
        $moduleSubmodules = $module->submodules()->with('quizzes')->get();
        $submoduleProgressById = TrainingSubmoduleProgress::query()
            ->where('enrollment_application_id', $application->id)
            ->whereIn('training_submodule_id', $moduleSubmodules->pluck('id'))
            ->get()
            ->keyBy('training_submodule_id');
        $submoduleAssessmentSummaries = $moduleSubmodules->mapWithKeys(
            fn (TrainingSubmodule $submodule): array => [
                $submodule->id => $assessments->summary($module, $application, $submodule),
            ]
        );
        $submoduleAccess = $moduleSubmodules->mapWithKeys(
            fn (TrainingSubmodule $submodule): array => [
                $submodule->id => $submodules->accessForSubmodule(
                    $submodule,
                    $moduleSubmodules,
                    $submoduleProgressById,
                ),
            ]
        );

        $assessmentSummary = $assessments->summary($module, $application);
        $quizzes = $assessmentSummary['quizzes'];
        $quizAttempts = QuizAttempt::query()
            ->where('enrollment_application_id', $application->id)
            ->whereIn('quiz_id', $quizzes->pluck('id'))
            ->latest()
            ->get()
            ->groupBy('quiz_id');

        AdminActivityLog::record($request->user(), 'trainee.module.viewer.opened', $module, [
            'trainee_email' => $application->email,
            'progress_status' => $progress->status,
        ]);

        return view('trainee.modules.show', [
            'application' => $application,
            'module' => $module,
            'progress' => $progress,
            'quizzes' => $quizzes,
            'quizAttempts' => $quizAttempts,
            'assessmentSummary' => $assessmentSummary,
            'submodules' => $moduleSubmodules,
            'submoduleProgressById' => $submoduleProgressById,
            'submoduleAssessmentSummaries' => $submoduleAssessmentSummaries,
            'submoduleAccess' => $submoduleAccess,
            'classroomComments' => $comments->visibleFor($request->user(), $module),
            'privateCommentRecipients' => $comments->privateRecipients($request->user(), $module),
        ]);
    }

    public function supplementaryDownload(
        Request $request,
        TrainingModule $module,
        int $index,
        LearningPdfWatermark $watermark,
    ): BinaryFileResponse|StreamedResponse {
        $application = $this->approvedApplicationFor($request);
        $this->authorizeModule($application, $module);
        $list = $module->supplementaryList();
        abort_unless(isset($list[$index]), 404);

        $attachment = $list[$index];
        $path = $attachment['file_path'] ?? null;
        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);

        $this->touchModuleProgress($application, $module);

        AdminActivityLog::record($request->user(), 'trainee.module.supplementary.downloaded', $module, [
            'trainee_email' => $application->email,
            'filename' => $attachment['original_name'] ?? 'supplementary',
        ]);

        $filename = basename($attachment['original_name'] ?? 'attachment');

        return $watermark->respond(
            $path,
            $filename,
            $attachment['mime_type'] ?? null,
            HeaderUtils::DISPOSITION_ATTACHMENT,
        );
    }

    public function moduleContent(
        Request $request,
        TrainingModule $module,
        LearningPdfWatermark $watermark,
    ): BinaryFileResponse|StreamedResponse {
        $application = $this->approvedApplicationFor($request);
        $this->authorizeModule($application, $module);
        abort_unless(Storage::disk('local')->exists($module->file_path), 404);

        // The protected content URL can be opened directly by the browser, so
        // it must create the same progress baseline as the viewer page.
        $this->touchModuleProgress($application, $module);

        AdminActivityLog::record($request->user(), 'trainee.module.content.viewed', $module, [
            'trainee_email' => $application->email,
            'mime_type' => $module->mime_type,
            'range_request' => $request->hasHeader('Range'),
        ]);

        return $this->moduleFileResponse($module, HeaderUtils::DISPOSITION_INLINE, $watermark);
    }

    public function moduleDownload(
        Request $request,
        TrainingModule $module,
        LearningPdfWatermark $watermark,
    ): BinaryFileResponse|StreamedResponse {
        $application = $this->approvedApplicationFor($request);
        $this->authorizeModule($application, $module);
        abort_unless(Storage::disk('local')->exists($module->file_path), 404);
        $this->touchModuleProgress($application, $module);

        AdminActivityLog::record($request->user(), 'trainee.module.content.downloaded', $module, [
            'trainee_email' => $application->email,
            'mime_type' => $module->mime_type,
        ]);

        return $this->moduleFileResponse($module, HeaderUtils::DISPOSITION_ATTACHMENT, $watermark);
    }

    public function updateProgress(
        Request $request,
        TrainingModule $module,
        ModuleAssessmentService $assessments,
    ): RedirectResponse {
        $application = $this->approvedApplicationFor($request);
        $this->authorizeModule($application, $module);
        $validated = $request->validate(['action' => ['required', 'in:submit,complete,reopen']]);
        $submitting = in_array($validated['action'], ['submit', 'complete'], true);
        $progress = ModuleProgress::query()->where([
            'enrollment_application_id' => $application->id,
            'training_module_id' => $module->id,
        ])->firstOrFail();

        if ($progress->isTrainerValidated()) {
            throw ValidationException::withMessages([
                'action' => 'A trainer-validated module cannot be returned to in progress.',
            ]);
        }

        if ($submitting) {
            if (! $module->requiresEvaluation()) {
                throw ValidationException::withMessages([
                    'action' => 'This is a learning-material-only module. It has no Mark as Done or trainer evaluation requirement.',
                ]);
            }

            $summary = $assessments->summary($module, $application);

            if ($summary['required_count'] > 0 && ! $summary['all_passed']) {
                $message = $summary['ready_for_remediation_evaluation']
                    ? 'All attempts are used and one or more assessments were not passed. Your trainer can now record a remediation evaluation; Mark as Done is unavailable.'
                    : 'Pass every published quiz or activity before submitting this module for trainer evaluation.';

                throw ValidationException::withMessages(['action' => $message]);
            }
        }

        $progress->forceFill([
            'status' => $submitting
                ? ModuleProgress::STATUS_AWAITING_EVALUATION
                : ModuleProgress::STATUS_IN_PROGRESS,
            'progress_percent' => $submitting ? max(95, (int) $progress->progress_percent) : 10,
            'first_opened_at' => $progress->first_opened_at ?: now(),
            'last_viewed_at' => now(),
            'submitted_at' => $submitting ? now() : null,
            'completed_at' => null,
        ])->save();

        if ($submitting) {
            $requiredSubmoduleIds = $module->submodules()->where('is_required', true)->pluck('id');
            if ($requiredSubmoduleIds->isNotEmpty()) {
                TrainingSubmoduleProgress::query()
                    ->where('enrollment_application_id', $application->id)
                    ->whereIn('training_submodule_id', $requiredSubmoduleIds)
                    ->whereNotIn('status', [
                        TrainingSubmoduleProgress::STATUS_COMPLETED,
                        TrainingSubmoduleProgress::STATUS_NEEDS_REMEDIATION,
                    ])
                    ->update([
                        'status' => TrainingSubmoduleProgress::STATUS_AWAITING_EVALUATION,
                        'submitted_at' => now(),
                        'progress_percent' => 95,
                    ]);
            }
        }

        AdminActivityLog::record($request->user(), 'trainee.module.progress.updated', $progress, [
            'module_id' => $module->id,
            'status' => $progress->status,
        ]);

        $redirect = $submitting
            ? redirect()->route('trainee.modules.index')
            : redirect()->route('trainee.modules.show', $module);

        return $redirect->with('saved', $submitting
            ? 'Module submitted. Your trainer must validate the competency before the next module unlocks.'
            : 'Module returned to in progress.');
    }

    public function updateSubmoduleProgress(
        Request $request,
        TrainingModule $module,
        TrainingSubmodule $submodule,
    ): RedirectResponse {
        $application = $this->approvedApplicationFor($request);
        $this->authorizeModule($application, $module);
        abort_unless((int) $submodule->training_module_id === (int) $module->id, 404);

        throw ValidationException::withMessages([
            'action' => 'Submodules are completed in face-to-face class. Your trainer records the grade. You cannot mark this submodule as done.',
        ]);
    }

    public function securityEvent(Request $request, TrainingModule $module): Response
    {
        $application = $this->approvedApplicationFor($request);
        $this->authorizeModule($application, $module);
        $validated = $request->validate([
            'event' => ['required', 'in:context_menu,print_shortcut,save_shortcut,before_print'],
        ]);

        // This records browser-side deterrence events. It cannot observe actions below the browser layer.
        AdminActivityLog::record($request->user(), 'trainee.module.restricted-action', $module, [
            'trainee_email' => $application->email,
            'event' => $validated['event'],
        ]);

        return response()->noContent();
    }

    private function authorizeModule(?EnrollmentApplication $application, TrainingModule $module, bool $allowEvaluated = true): void
    {
        abort_unless($application, 403);
        abort_if(
            $application->is_historical_record
            || $application->learning_status === EnrollmentApplication::LEARNING_GRADUATED,
            403
        );
        abort_unless($module->is_published, 404);
        abort_if($module->available_at?->isFuture(), 404);
        $progress = ModuleProgress::query()
            ->where('enrollment_application_id', $application->id)
            ->where('training_module_id', $module->id)
            ->first();

        abort_unless($progress, 404);

        if (! $this->classworkSequence()->canAccess($application, $module)) {
            abort(403, $this->classworkSequence()->lockMessage($application, $module));
        }

        if (! $progress->isAccessible()) {
            $this->classworkSequence()->syncLocks($application);
            $progress->refresh();
        }

        abort_unless($progress->isAccessible(), 404);

        if (! $allowEvaluated) {
            abort_if(
                $progress->isTrainerValidated()
                    || $progress->status === ModuleProgress::STATUS_AWAITING_EVALUATION,
                403,
                'Learning files and downloads are closed while a module is submitted or completed.'
            );
        }
    }

    private function approvedApplicationFor(Request $request): ?EnrollmentApplication
    {
        return EnrollmentApplication::query()
            ->where('user_id', $request->user()->id)
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->latest()
            ->first();
    }

    private function assignedModulesFor(EnrollmentApplication $application)
    {
        return TrainingModule::query()
            ->with(['trainer', 'batch'])
            ->assignedTo($application);
    }

    private function availableModulesFor(EnrollmentApplication $application)
    {
        return TrainingModule::query()
            ->with(['trainer', 'batch'])
            ->availableTo($application)
            ->orderBy('position')
            ->latest('published_at');
    }

    private function classworkSequence(): TraineeClassworkSequence
    {
        return app(TraineeClassworkSequence::class);
    }

    private function evaluatedGradesFor(EnrollmentApplication $application)
    {
        return ModuleProgress::query()
            ->with(['module.trainer', 'evaluator'])
            ->where('enrollment_application_id', $application->id)
            ->whereNotNull('evaluated_at')
            ->whereNotNull('evaluated_by_id')
            ->whereHas('module')
            ->when($application->is_historical_record, fn ($query) => $query->whereRaw('1 = 0'))
            ->latest('evaluated_at');
    }

    private function touchModuleProgress(
        EnrollmentApplication $application,
        TrainingModule $module,
    ): ModuleProgress {
        $progress = ModuleProgress::query()->where([
            'enrollment_application_id' => $application->id,
            'training_module_id' => $module->id,
        ])->firstOrFail();

        if ($progress->wasRecentlyCreated || $progress->status === ModuleProgress::STATUS_NOT_STARTED) {
            $progress->forceFill([
                'status' => ModuleProgress::STATUS_IN_PROGRESS,
                'progress_percent' => $progress->hasRecordedClassworkProgress()
                    ? (int) $progress->progress_percent
                    : 0,
                'first_opened_at' => $progress->first_opened_at ?: now(),
            ]);
        }

        $progress->forceFill(['last_viewed_at' => now()])->save();

        return $progress;
    }

    private function moduleFileResponse(
        TrainingModule $module,
        string $disposition,
        LearningPdfWatermark $watermark,
    ): BinaryFileResponse|StreamedResponse {
        return $watermark->respond(
            $module->file_path,
            basename($module->original_file_name),
            $module->mime_type,
            $disposition,
        );
    }

    private function visibleAnnouncementsFor(EnrollmentApplication $application)
    {
        return TrainerAnnouncement::query()
            ->where('is_published', true)
            ->whereIn('audience', ['all', 'trainees'])
            ->where(fn ($query) => $query
                ->whereNull('posted_at')
                ->orWhere('posted_at', '<=', now()))
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now()))
            ->where(function ($query) use ($application) {
                $query->whereNull('training_batch_id')
                    ->orWhere('training_batch_id', $application->training_batch_id);
            });
    }

    private function availableQuizzesFor(EnrollmentApplication $application)
    {
        return Quiz::query()
            ->with(['trainer', 'batch'])
            ->released()
            ->where(function ($query) use ($application) {
                $query->where('target_enrollment_application_id', $application->id)
                    ->orWhere(function ($batchQuery) use ($application) {
                        $batchQuery->whereNull('target_enrollment_application_id')
                            ->where(function ($scopeQuery) use ($application) {
                                $scopeQuery->whereNull('training_batch_id')
                                    ->orWhere('training_batch_id', $application->training_batch_id);
                            });
                    });
            });
    }

    private function uniqueOnsiteTicketNumber(): string
    {
        do {
            $ticketNumber = 'MCARE-OT-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (
            PaymentTransaction::query()->where('ticket_number', $ticketNumber)->exists()
            || PaymentTransaction::query()->where('reference_number', $ticketNumber)->exists()
            || EnrollmentApplication::query()->where('payment_reference', $ticketNumber)->exists()
        );

        return $ticketNumber;
    }
}

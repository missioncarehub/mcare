<?php

namespace App\Http\Controllers\Trainer;

use App\Http\Controllers\Controller;
use App\Models\EnrollmentApplication;
use App\Models\ModuleProgress;
use App\Models\Quiz;
use App\Models\TraineeAttendance;
use App\Models\TraineeCompetencyRecord;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Services\CompetencyCatalogService;
use App\Services\CompletionEligibilityService;
use App\Services\TraineeRosterCsv;
use App\Services\TrainingCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrainerPortalController extends Controller
{
    public function trainings(Request $request): View
    {
        $trainer = $request->user();

        return view('trainer.trainings', [
            'batches' => TrainingBatch::query()
                ->with('trainer')
                ->withCount(['applications', 'modules'])
                ->orderByDesc('is_active')
                ->orderByDesc('year')
                ->get(),
            'assignedBatch' => TrainingBatch::assignedTo($trainer),
        ]);
    }

    public function trainees(Request $request): View
    {
        $trainer = $request->user();
        $assignedBatch = TrainingBatch::assignedTo($trainer);
        $search = trim((string) $request->query('search', ''));
        $batchId = $request->integer('batch_id') ?: null;
        $schedule = in_array($request->query('schedule'), ['AM', 'PM'], true) ? $request->query('schedule') : null;
        $this->assertBatchFilter($assignedBatch, $batchId);
        $batchId ??= $assignedBatch?->id;
        $trainees = $this->traineeRosterQuery($search, $batchId, $schedule, $assignedBatch)
            ->with(['batch', 'user', 'moduleProgress', 'paymentTransactions.recordedByAdmin', 'paymentTransactions.verifier'])
            ->paginate(15)
            ->withQueryString();

        return view('trainer.trainees', [
            'search' => $search,
            'batchId' => $batchId,
            'schedule' => $schedule,
            'batches' => $assignedBatch ? collect([$assignedBatch]) : collect(),
            'trainees' => $trainees,
            'assignedBatch' => $assignedBatch,
        ]);
    }

    public function exportTrainees(Request $request, TraineeRosterCsv $csv): StreamedResponse
    {
        $assignedBatch = TrainingBatch::assignedTo($request->user());
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'batch_id' => ['nullable', 'integer', 'exists:training_batches,id'],
            'schedule' => ['nullable', Rule::in(['AM', 'PM'])],
        ]);
        $requestedBatchId = isset($validated['batch_id']) ? (int) $validated['batch_id'] : null;
        $this->assertBatchFilter($assignedBatch, $requestedBatchId);
        $trainees = $this->traineeRosterQuery(
            trim((string) ($validated['search'] ?? '')),
            $requestedBatchId ?? $assignedBatch?->id,
            $validated['schedule'] ?? null,
            $assignedBatch,
        )->with(['batch', 'moduleProgress', 'paymentTransactions'])->get();

        return $csv->download($trainees, 'mcare-trainer-trainee-summary-'.now()->format('Y-m-d').'.csv');
    }

    public function sessions(Request $request, TrainingCalendarService $scheduleService): View
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $activeBatch = TrainingBatch::assignedTo($request->user());
        $month = isset($validated['month'])
            ? Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth()
            : $scheduleService->suggestedMonth($activeBatch);
        $sessions = $activeBatch ? $scheduleService->month($activeBatch, $month) : collect();

        return view('trainer.sessions', [
            'activeBatch' => $activeBatch,
            'month' => $month,
            'sessions' => $sessions,
            'sessionsByDate' => $sessions->groupBy('date_key'),
            'calendarSelectedDate' => $validated['date'] ?? null,
        ]);
    }

    public function resources(Request $request, CompetencyCatalogService $catalog): View
    {
        $assignedBatch = TrainingBatch::assignedTo($request->user());

        $modules = TrainingModule::query()
            ->with([
                'batch',
                'targetTrainee',
                'progressRecords.application',
                'submodules',
                'quizzes.questions',
                'quizzes.attempts.application',
            ])
            ->where('trainer_id', $request->user()->id)
            ->latest('published_at')
            ->latest('id')
            ->get();

        $quizzes = Quiz::query()
            ->with(['trainingModule', 'batch', 'targetTrainee', 'questions', 'attempts.application'])
            ->where('trainer_id', $request->user()->id)
            ->latest('id')
            ->get();

        return view('trainer.resources', [
            'batches' => $assignedBatch ? collect([$assignedBatch]) : collect(),
            'modules' => $modules,
            'quizzes' => $quizzes,
            'trainees' => $this->approvedTrainees($assignedBatch)->with('batch')->get(),
            'assignedBatch' => $assignedBatch,
            'catalogUnits' => $catalog->caregivingUnits(true),
        ]);
    }

    public function certificates(CompletionEligibilityService $eligibility): View
    {
        $assignedBatch = TrainingBatch::assignedTo(request()->user());
        $trainees = $this->approvedTrainees($assignedBatch)
            ->with(['batch', 'user'])
            ->orderBy('last_name')
            ->get();

        return view('trainer.certificates', [
            'trainees' => $trainees,
            'eligibilityByTrainee' => $trainees->mapWithKeys(
                fn (EnrollmentApplication $trainee): array => [
                    $trainee->id => $eligibility->evaluate($trainee),
                ],
            ),
        ]);
    }

    public function reports(): View
    {
        // Path: app/Http/Controllers/Trainer/TrainerPortalController.php | Label: Detailed trainer reports
        // A trainer's report shows only their assigned batch. Every metric is
        // computed against the same queries used by the rest of the trainer
        // area so counts always agree with the trainees, attendance, and
        // competencies pages the trainer already uses.
        $activeBatch = TrainingBatch::assignedTo(request()->user());
        $trainees = $this->approvedTrainees($activeBatch)
            ->with(['batch:id,name,year', 'moduleProgress'])
            ->get();

        // Trainee mix
        $totalTrainees = $trainees->count();
        $graduatedTrainees = $trainees->where('learning_status', EnrollmentApplication::LEARNING_GRADUATED)->count();
        $activeLearners = $trainees->filter(function ($trainee) {
            $status = $trainee->learning_status ?: EnrollmentApplication::LEARNING_ACTIVE;

            return $status === EnrollmentApplication::LEARNING_ACTIVE;
        })->count();

        // Modules published for this batch
        $moduleQuery = TrainingModule::query()
            ->when($activeBatch, fn ($query) => $query->where('training_batch_id', $activeBatch->id))
            ->when(! $activeBatch, fn ($query) => $query->whereRaw('1 = 0'));

        $publishedModules = (clone $moduleQuery)->where('is_published', true)->count();
        $totalModules = (clone $moduleQuery)->count();

        // Module progress mix across this batch's trainees
        $progressStatuses = [
            ModuleProgress::STATUS_NOT_STARTED,
            ModuleProgress::STATUS_LOCKED,
            ModuleProgress::STATUS_IN_PROGRESS,
            ModuleProgress::STATUS_AWAITING_EVALUATION,
            ModuleProgress::STATUS_NEEDS_REMEDIATION,
            ModuleProgress::STATUS_COMPLETED,
        ];
        $moduleProgress = ModuleProgress::query()
            ->when($activeBatch, fn ($query) => $query->whereHas('application', fn ($n) => $n->where('training_batch_id', $activeBatch->id)))
            ->when(! $activeBatch, fn ($query) => $query->whereRaw('1 = 0'))
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
        foreach ($progressStatuses as $s) {
            $moduleProgress[$s] = (int) ($moduleProgress[$s] ?? 0);
        }
        $moduleProgressTotal = array_sum($moduleProgress);

        // Attendance snapshot (last 30 days) - only this trainer's batch.
        $attendanceStart = Carbon::now()->subDays(30)->startOfDay();
        $attendanceQuery = TraineeAttendance::query()
            ->where('attendance_date', '>=', $attendanceStart->toDateString())
            ->when($activeBatch, fn ($q) => $q->where('training_batch_id', $activeBatch->id))
            ->when(! $activeBatch, fn ($q) => $q->whereRaw('1 = 0'));
        $attendanceCounts = (clone $attendanceQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
        foreach ([
            TraineeAttendance::STATUS_PRESENT,
            TraineeAttendance::STATUS_LATE,
            TraineeAttendance::STATUS_ABSENT,
            TraineeAttendance::STATUS_EXCUSED,
        ] as $s) {
            $attendanceCounts[$s] = (int) ($attendanceCounts[$s] ?? 0);
        }
        $attendanceLogged = array_sum($attendanceCounts);
        $attendanceRate = $attendanceLogged > 0
            ? round((($attendanceCounts[TraineeAttendance::STATUS_PRESENT] + $attendanceCounts[TraineeAttendance::STATUS_LATE]) / $attendanceLogged) * 100, 1)
            : null;

        // Payments summary (this batch only)
        $fullyPaid = $trainees->where('payment_status', EnrollmentApplication::PAYMENT_PAID)->count();
        $partiallyPaid = $trainees->where('payment_status', EnrollmentApplication::PAYMENT_PARTIALLY_PAID)->count();
        $paymentPending = $trainees->whereIn('payment_status', [
            EnrollmentApplication::PAYMENT_ONSITE_PENDING,
            EnrollmentApplication::PAYMENT_ONLINE_PENDING,
        ])->count();

        // Competency evaluations recently updated for this batch's learners.
        $recentEvaluations = TraineeCompetencyRecord::query()
            ->when($activeBatch, fn ($q) => $q->whereHas('application', fn ($n) => $n->where('training_batch_id', $activeBatch->id)))
            ->when(! $activeBatch, fn ($q) => $q->whereRaw('1 = 0'))
            ->where('updated_at', '>=', $attendanceStart)
            ->count();

        // Per-trainee mini-summary
        $traineeRows = $trainees->map(function ($t) use ($totalModules) {
            $completed = $t->moduleProgress->where('status', ModuleProgress::STATUS_COMPLETED)->count();
            $inProgress = $t->moduleProgress->whereIn('status', [
                ModuleProgress::STATUS_IN_PROGRESS,
                ModuleProgress::STATUS_AWAITING_EVALUATION,
                ModuleProgress::STATUS_NEEDS_REMEDIATION,
            ])->count();
            $progressPercent = $totalModules > 0 ? (int) round(($completed / $totalModules) * 100) : 0;
            return [
                'name' => $t->last_name.', '.$t->first_name,
                'schedule' => $t->schedule_preference,
                'payment_status' => $t->paymentStatusLabel(),
                'learning_status' => $t->learning_status ?: EnrollmentApplication::LEARNING_ACTIVE,
                'learning_status_label' => $t->learningStatusLabel(),
                'completed' => $completed,
                'in_progress' => $inProgress,
                'progress_percent' => $progressPercent,
            ];
        })->sortByDesc('completed')->values();

        return view('trainer.reports', [
            'activeBatch' => $activeBatch,
            'stats' => [
                'trainees' => $totalTrainees,
                'am' => $trainees->where('schedule_preference', 'AM')->count(),
                'pm' => $trainees->where('schedule_preference', 'PM')->count(),
                'modules' => $publishedModules,
                'total_modules' => $totalModules,
                'paid' => $fullyPaid,
                'partial_paid' => $partiallyPaid,
                'payment_pending' => $paymentPending,
                'graduated' => $graduatedTrainees,
                'active_learners' => $activeLearners,
                'module_completions' => $moduleProgress[ModuleProgress::STATUS_COMPLETED],
                'awaiting_evaluation' => $moduleProgress[ModuleProgress::STATUS_AWAITING_EVALUATION],
            ],
            'moduleProgress' => $moduleProgress,
            'moduleProgressTotal' => $moduleProgressTotal,
            'attendance' => [
                'counts' => $attendanceCounts,
                'logged' => $attendanceLogged,
                'rate' => $attendanceRate,
                'window_start' => $attendanceStart,
                'recent_competency_evaluations' => $recentEvaluations,
            ],
            'traineeRows' => $traineeRows,
            'assignedBatch' => $activeBatch,
            'generatedAt' => Carbon::now(),
        ]);
    }

    private function approvedTrainees(?TrainingBatch $batch = null)
    {
        return EnrollmentApplication::query()
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->when($batch, fn ($query) => $query->where('training_batch_id', $batch->id))
            ->when(! $batch, fn ($query) => $query->whereRaw('1 = 0'));
    }

    private function traineeRosterQuery(
        string $search,
        ?int $batchId,
        ?string $schedule,
        ?TrainingBatch $assignedBatch,
    ) {
        return $this->approvedTrainees($assignedBatch)
            ->when($batchId, fn ($query) => $query->where('training_batch_id', $batchId))
            ->when($schedule, fn ($query) => $query->where('schedule_preference', $schedule))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name');
    }

    private function assertBatchFilter(?TrainingBatch $assignedBatch, ?int $requestedBatchId): void
    {
        if ($requestedBatchId && (! $assignedBatch || $requestedBatchId !== (int) $assignedBatch->id)) {
            throw ValidationException::withMessages([
                'batch_id' => 'This trainer can only access the currently assigned batch.',
            ]);
        }
    }
}

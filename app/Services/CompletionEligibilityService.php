<?php

namespace App\Services;

use App\Models\CompetencyOutcome;
use App\Models\CompetencyUnit;
use App\Models\EnrollmentApplication;
use App\Models\ModuleProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\TraineeCompetencyRecord;
use App\Models\TraineeOutcomeResult;
use App\Models\TrainingModule;
use App\Support\CaregivingNcIiCatalog;

class CompletionEligibilityService
{
    /**
     * @return array{
     *   eligible: bool,
     *   graduated: bool,
     *   checks: array<string, array{passed: bool, label: string, detail: string}>,
     *   counts: array<string, int>
     * }
     */
    public function evaluate(EnrollmentApplication $application): array
    {
        $requiredUnits = CompetencyUnit::query()
            ->where('program_code', CaregivingNcIiCatalog::PROGRAM_CODE)
            ->where('category', TrainingModule::CATEGORY_CORE)
            ->where('is_required', true);
        $requiredUnitIds = (clone $requiredUnits)->pluck('id');
        $requiredUnitCodes = (clone $requiredUnits)->pluck('code');
        $requiredOutcomeIds = CompetencyOutcome::query()
            ->whereIn('competency_unit_id', $requiredUnitIds)
            ->where('is_required', true)
            ->pluck('id');

        $competentRecords = TraineeCompetencyRecord::query()
            ->where('enrollment_application_id', $application->id)
            ->whereIn('competency_unit_id', $requiredUnitIds)
            ->where('status', TraineeCompetencyRecord::STATUS_COMPETENT)
            ->count();
        $competentOutcomes = TraineeOutcomeResult::query()
            ->whereHas('record', fn ($query) => $query
                ->where('enrollment_application_id', $application->id))
            ->whereIn('competency_outcome_id', $requiredOutcomeIds)
            ->where('status', TraineeCompetencyRecord::STATUS_COMPETENT)
            ->count();

        $assignedCoreProgress = ModuleProgress::query()
            ->with('module:id,module_code')
            ->where('enrollment_application_id', $application->id)
            ->whereHas('module', fn ($query) => $query->whereIn('module_code', $requiredUnitCodes))
            ->get();
        $modules = $assignedCoreProgress->pluck('training_module_id')->unique()->values();
        $completedModuleCodes = $assignedCoreProgress
            ->filter(fn (ModuleProgress $progress): bool => $progress->isTrainerValidated())
            ->pluck('module.module_code')
            ->filter()
            ->unique();

        $quizzes = Quiz::query()
            ->where('is_published', true)
            ->whereIn('training_module_id', $modules)
            ->pluck('id');
        $passedQuizzes = $quizzes->filter(fn ($quizId) => QuizAttempt::query()
            ->where('quiz_id', $quizId)
            ->where('enrollment_application_id', $application->id)
            ->where('status', QuizAttempt::STATUS_GRADED)
            ->where('passed', true)
            ->exists())->count();

        $unitCount = $requiredUnitIds->count();
        $outcomeCount = $requiredOutcomeIds->count();
        $moduleCount = $requiredUnitCodes->count();
        $completedModules = $completedModuleCodes->count();
        $quizCount = $quizzes->count();

        $checks = [
            'approved' => $this->check(
                $application->status === EnrollmentApplication::STATUS_APPROVED,
                'Enrollment approved',
                $application->statusLabel(),
            ),
            'payment' => $this->check(
                $application->payment_status === EnrollmentApplication::PAYMENT_PAID,
                'Payment cleared',
                $application->paymentStatusLabel(),
            ),
            'batch' => $this->check(
                $application->learning_started_at !== null,
                'Rolling training started',
                $application->learning_started_at?->format('M d, Y g:i A') ?? 'No active-module enrollment snapshot',
            ),
            'units' => $this->check(
                $unitCount > 0 && $competentRecords === $unitCount,
                'Competency units completed',
                "{$competentRecords} of {$unitCount} competent",
            ),
            'outcomes' => $this->check(
                $outcomeCount > 0 && $competentOutcomes === $outcomeCount,
                'Achievement outcomes completed',
                "{$competentOutcomes} of {$outcomeCount} competent",
            ),
            'modules' => $this->check(
                $moduleCount > 0 && $completedModules === $moduleCount,
                'Required core modules completed',
                "{$completedModules} of {$moduleCount} complete",
            ),
            'quizzes' => $this->check(
                $passedQuizzes === $quizCount,
                'Published quizzes passed',
                "{$passedQuizzes} of {$quizCount} passed",
            ),
        ];

        return [
            'eligible' => collect($checks)->every(fn ($check) => $check['passed']),
            'graduated' => $application->learning_status === EnrollmentApplication::LEARNING_GRADUATED,
            'checks' => $checks,
            'counts' => [
                'units' => $unitCount,
                'competent_units' => $competentRecords,
                'outcomes' => $outcomeCount,
                'competent_outcomes' => $competentOutcomes,
                'modules' => $moduleCount,
                'completed_modules' => $completedModules,
                'quizzes' => $quizCount,
                'passed_quizzes' => $passedQuizzes,
            ],
        ];
    }

    /**
     * TOR issue / preview / download requires both graduation and a full
     * completion-check pass. Graduating early does not skip unfinished checks.
     */
    public function canIssueTor(EnrollmentApplication $application, ?array $evaluation = null): bool
    {
        $evaluation ??= $this->evaluate($application);

        return $application->learning_status === EnrollmentApplication::LEARNING_GRADUATED
            && (bool) ($evaluation['eligible'] ?? false);
    }

    public function torIssuanceMessage(EnrollmentApplication $application, ?array $evaluation = null): string
    {
        $evaluation ??= $this->evaluate($application);
        $missing = [];

        if ($application->learning_status !== EnrollmentApplication::LEARNING_GRADUATED) {
            $missing[] = 'the trainee is graduated';
        }

        if (! ($evaluation['eligible'] ?? false)) {
            $blocked = collect($evaluation['checks'] ?? [])
                ->where('passed', false)
                ->pluck('label')
                ->filter()
                ->implode(', ');
            $missing[] = $blocked !== ''
                ? 'all completion checks have passed (still waiting: '.$blocked.')'
                : 'all completion checks have passed';
        }

        return 'A Transcript of Records cannot be issued, previewed, or downloaded until '.$this->joinRequirements($missing).'.';
    }

    private function joinRequirements(array $parts): string
    {
        if (count($parts) <= 1) {
            return $parts[0] ?? 'the required validations are complete';
        }

        $last = array_pop($parts);

        return implode(', ', $parts).' and '.$last;
    }

    /** @return array{passed: bool, label: string, detail: string} */
    private function check(bool $passed, string $label, string $detail): array
    {
        return compact('passed', 'label', 'detail');
    }
}

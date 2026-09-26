<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use App\Models\ClassroomComment;
use App\Models\CompetencyUnit;
use App\Models\ModuleProgress;
use App\Models\OfficialDocument;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\TraineeAttendance;
use App\Models\TraineeCompetencyRecord;
use App\Models\TraineeOutcomeResult;
use App\Models\TrainingModule;
use App\Models\TrainingSubmodule;
use App\Models\TrainingSubmoduleProgress;
use App\Models\User;
use App\Notifications\ClassroomCommentPosted;
use App\Notifications\LmsModulePublished;
use App\Notifications\LmsQuizPublished;
use App\Notifications\TrainerModuleAssignedByAdmin;
use App\Support\CaregivingNcIiCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TrainingModuleDeletionService
{
    /** @var list<string> */
    private const ACTIVE_OFFICIAL_STATUSES = [
        OfficialDocument::STATUS_QUEUED,
        OfficialDocument::STATUS_GENERATING,
        OfficialDocument::STATUS_GENERATED,
        OfficialDocument::STATUS_RELEASED,
        OfficialDocument::STATUS_DOWNLOADED,
    ];

    /** @var list<string> */
    private const MODULE_NOTIFICATION_TYPES = [
        LmsModulePublished::class,
        TrainerModuleAssignedByAdmin::class,
    ];

    /** @var list<string> */
    private const QUIZ_NOTIFICATION_TYPES = [
        LmsQuizPublished::class,
    ];

    public function __construct(private readonly TorGradeScale $gradeScale) {}

    /** @return array<string, mixed> */
    public function impact(TrainingModule $module): array
    {
        $snapshot = $this->snapshot($module);
        $blockers = $this->officialRecordBlockers(
            $snapshot['unit'],
            $snapshot['application_ids'],
        );

        return [
            'affected_trainees' => $snapshot['application_ids']->count(),
            'parent_progress_records' => $snapshot['parent_progress']->count(),
            'submodule_progress_records' => $snapshot['submodule_progress']->count(),
            'quizzes' => $snapshot['quizzes']->count(),
            'quiz_questions' => $snapshot['quiz_questions']->count(),
            'quiz_attempts' => $snapshot['quiz_attempts']->count(),
            'quiz_attendance' => $snapshot['attendance']->count(),
            'comments' => $snapshot['comment_rows']->count(),
            'notifications' => $snapshot['notification_rows']->count(),
            'queued_jobs' => $snapshot['job_rows']->count() + $snapshot['failed_job_rows']->count(),
            'competency_results' => $snapshot['owned_results']->count(),
            'stored_files' => $snapshot['file_paths']->count(),
            'official_record_blocked' => $blockers !== [],
            'official_record_reason' => $blockers[0] ?? null,
        ];
    }

    /** @return array<string, mixed> */
    public function delete(TrainingModule $module, User $admin): array
    {
        $paths = [];

        $summary = DB::transaction(function () use ($module, $admin, &$paths): array {
            $lockedModule = TrainingModule::query()
                ->lockForUpdate()
                ->find($module->getKey());

            if (! $lockedModule) {
                throw ValidationException::withMessages([
                    'module' => 'This module has already been deleted or is no longer available.',
                ]);
            }

            $snapshot = $this->snapshot($lockedModule, true);
            $paths = $snapshot['file_paths']->all();

            $this->deleteRows('trainee_attendances', $snapshot['attendance']->pluck('id'));
            $this->deleteRows('quiz_attempts', $snapshot['quiz_attempts']->pluck('id'));
            $this->deleteRows('quiz_questions', $snapshot['quiz_questions']->pluck('id'));
            $this->deleteRows('classroom_comments', $snapshot['comment_rows']->pluck('id'));
            $this->deleteRows('notifications', $snapshot['notification_rows']->pluck('id'));
            $this->deleteRows('jobs', $snapshot['job_rows']->pluck('id'));
            $this->deleteRows('failed_jobs', $snapshot['failed_job_rows']->pluck('id'));

            $ownedResultIds = $snapshot['owned_results']->pluck('id');
            $affectedRecordIds = $snapshot['owned_results']
                ->pluck('trainee_competency_record_id')
                ->unique()
                ->values();

            $this->deleteRows('trainee_outcome_results', $ownedResultIds);
            $this->recalculateCompetencyRecords(
                $affectedRecordIds,
                $snapshot['module_id'],
            );

            $this->deleteRows('training_submodule_progress', $snapshot['submodule_progress']->pluck('id'));
            $this->deleteRows('module_progress', $snapshot['parent_progress']->pluck('id'));
            $this->deleteRows('quizzes', $snapshot['quizzes']->pluck('id'));
            $this->deleteRows('training_submodules', $snapshot['submodules']->pluck('id'));

            $title = (string) $lockedModule->title;
            $moduleId = (int) $lockedModule->id;
            $trainerId = $lockedModule->trainer_id;
            $batchId = $lockedModule->training_batch_id;
            $recordCounts = [
                'affected_trainees' => $snapshot['application_ids']->count(),
                'parent_progress_records' => $snapshot['parent_progress']->count(),
                'submodule_progress_records' => $snapshot['submodule_progress']->count(),
                'quizzes' => $snapshot['quizzes']->count(),
                'quiz_questions' => $snapshot['quiz_questions']->count(),
                'quiz_attempts' => $snapshot['quiz_attempts']->count(),
                'quiz_attendance' => $snapshot['attendance']->count(),
                'comments' => $snapshot['comment_rows']->count(),
                'notifications' => $snapshot['notification_rows']->count(),
                'queued_jobs' => $snapshot['job_rows']->count() + $snapshot['failed_job_rows']->count(),
                'competency_results' => $snapshot['owned_results']->count(),
                'stored_files' => $snapshot['file_paths']->count(),
            ];

            AdminActivityLog::record($admin, 'admin.module.permanently_deleted', $lockedModule, [
                'module_id' => $moduleId,
                'title' => $title,
                'trainer_id' => $trainerId,
                'batch_id' => $batchId,
                'affected_record_counts' => $recordCounts,
                'admin_id' => $admin->id,
                'deleted_at' => now()->toISOString(),
            ]);

            if (! $lockedModule->delete()) {
                throw new \RuntimeException('The module could not be permanently deleted.');
            }

            $this->deleteOrphanedCustomUnit($snapshot['unit']);

            return [
                'module_id' => $moduleId,
                'title' => $title,
                'counts' => $recordCounts,
            ];
        }, 3);

        $this->deleteStoredFiles($paths);

        return $summary;
    }

    /** @return array<string, mixed> */
    private function snapshot(TrainingModule $module, bool $lock = false): array
    {
        $moduleId = (int) $module->getKey();
        $unit = $this->resolveUnit($module, $lock);

        $submoduleQuery = TrainingSubmodule::query()
            ->where('training_module_id', $moduleId);
        if ($lock) {
            $submoduleQuery->lockForUpdate();
        }
        $submodules = $submoduleQuery->get();
        $submoduleIds = $submodules->pluck('id')->values();

        $parentProgressQuery = ModuleProgress::query()
            ->where('training_module_id', $moduleId);
        if ($lock) {
            $parentProgressQuery->lockForUpdate();
        }
        $parentProgress = $parentProgressQuery->get();

        $submoduleProgressQuery = TrainingSubmoduleProgress::query();
        if ($submoduleIds->isEmpty()) {
            $submoduleProgress = collect();
        } else {
            $submoduleProgressQuery->whereIn('training_submodule_id', $submoduleIds);
            if ($lock) {
                $submoduleProgressQuery->lockForUpdate();
            }
            $submoduleProgress = $submoduleProgressQuery->get();
        }

        $quizQuery = $this->moduleQuizQuery($moduleId, $submoduleIds);
        if ($lock) {
            $quizQuery->lockForUpdate();
        }
        $quizzes = $quizQuery->get();
        $quizIds = $quizzes->pluck('id')->values();

        if ($quizIds->isEmpty()) {
            $quizQuestions = collect();
            $quizAttempts = collect();
            $attendance = collect();
        } else {
            $quizQuestionsQuery = QuizQuestion::query()->whereIn('quiz_id', $quizIds);
            $quizAttemptsQuery = QuizAttempt::query()->whereIn('quiz_id', $quizIds);
            $attendanceQuery = TraineeAttendance::query()->whereIn('quiz_id', $quizIds);
            if ($lock) {
                $quizQuestionsQuery->lockForUpdate();
                $quizAttemptsQuery->lockForUpdate();
                $attendanceQuery->lockForUpdate();
            }
            $quizQuestions = $quizQuestionsQuery->get();
            $quizAttempts = $quizAttemptsQuery->get();
            $attendance = $attendanceQuery->get();
        }

        $commentRows = $this->relatedCommentRows($module, $quizIds, $lock);
        $commentIds = $commentRows->pluck('id')->values();
        $notificationRows = $this->relatedNotificationRows($moduleId, $quizIds, $commentIds, $lock);
        $jobRows = $this->relatedQueueRows('jobs', $moduleId, $quizIds, $commentIds, $lock);
        $failedJobRows = $this->relatedQueueRows('failed_jobs', $moduleId, $quizIds, $commentIds, $lock);

        $ownedResultsQuery = TraineeOutcomeResult::query()
            ->where('training_module_id', $moduleId);
        if ($lock) {
            $ownedResultsQuery->lockForUpdate();
        }
        $ownedResults = $ownedResultsQuery->get();

        $applicationIds = collect([
            $module->target_enrollment_application_id,
        ])
            ->merge($parentProgress->pluck('enrollment_application_id'))
            ->merge($submoduleProgress->pluck('enrollment_application_id'))
            ->merge($quizAttempts->pluck('enrollment_application_id'))
            ->merge($quizzes->pluck('target_enrollment_application_id'))
            ->merge($ownedResults->map(function (TraineeOutcomeResult $result): ?int {
                return $result->record?->enrollment_application_id;
            }))
            ->filter(fn ($id): bool => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $filePaths = collect([$module->file_path])
            ->merge(collect($module->supplementaryList())->pluck('file_path'))
            ->merge($this->quizSubmissionPaths($quizAttempts))
            ->filter(fn ($path): bool => is_string($path) && $path !== '')
            ->unique()
            ->values();

        return compact(
            'moduleId',
            'unit',
            'submodules',
            'submoduleIds',
            'parentProgress',
            'submoduleProgress',
            'quizzes',
            'quizIds',
            'quizQuestions',
            'quizAttempts',
            'attendance',
            'commentRows',
            'notificationRows',
            'jobRows',
            'failedJobRows',
            'ownedResults',
            'applicationIds',
            'filePaths',
        ) + [
            // Keep descriptive keys stable for the controller, UI, and tests.
            'module_id' => $moduleId,
            'submodule_progress' => $submoduleProgress,
            'parent_progress' => $parentProgress,
            'quiz_questions' => $quizQuestions,
            'quiz_attempts' => $quizAttempts,
            'comment_rows' => $commentRows,
            'notification_rows' => $notificationRows,
            'job_rows' => $jobRows,
            'failed_job_rows' => $failedJobRows,
            'owned_results' => $ownedResults,
            'application_ids' => $applicationIds,
            'file_paths' => $filePaths,
        ];
    }

    private function moduleQuizQuery(int $moduleId, Collection $submoduleIds): Builder
    {
        return Quiz::query()->where(function (Builder $query) use ($moduleId, $submoduleIds): void {
            $query->where('training_module_id', $moduleId);

            if ($submoduleIds->isNotEmpty()) {
                $query->orWhereIn('training_submodule_id', $submoduleIds);
            }
        });
    }

    private function resolveUnit(TrainingModule $module, bool $lock = false): ?CompetencyUnit
    {
        $query = CompetencyUnit::query();

        if ($module->competency_unit_id) {
            $query->whereKey($module->competency_unit_id);
        } elseif (filled($module->module_code)) {
            $query
                ->where('program_code', CaregivingNcIiCatalog::PROGRAM_CODE)
                ->where('code', $module->module_code);
        } else {
            return null;
        }

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    /** @return list<string> */
    private function officialRecordBlockers(
        ?CompetencyUnit $unit,
        Collection $applicationIds,
        bool $lock = false,
    ): array {
        // Supplemental/material-only modules are intentionally not part of the
        // official TOR source set. An unrelated COTC/TOR must not block them.
        if (! $unit || ! $unit->is_tor_included || $applicationIds->isEmpty()) {
            return [];
        }

        $lockedRecordsQuery = TraineeCompetencyRecord::query()
            ->whereIn('enrollment_application_id', $applicationIds)
            ->where('competency_unit_id', $unit->id)
            ->whereNotNull('locked_at');
        if ($lock) {
            $lockedRecordsQuery->lockForUpdate();
        }
        $lockedRecordCount = $lockedRecordsQuery->count();

        $documentsQuery = OfficialDocument::query()
            ->whereIn('enrollment_application_id', $applicationIds)
            ->whereIn('type', OfficialDocument::supportedTypes())
            ->whereIn('status', self::ACTIVE_OFFICIAL_STATUSES);
        if ($lock) {
            $documentsQuery->lockForUpdate();
        }
        $documents = $documentsQuery->get(['id', 'type', 'status', 'document_number']);

        $blockers = [];
        if ($lockedRecordCount > 0) {
            $blockers[] = "{$lockedRecordCount} locked competency record(s)";
        }
        if ($documents->isNotEmpty()) {
            $labels = $documents
                ->map(fn (OfficialDocument $document): string => strtoupper($document->type).' '.$document->status)
                ->unique()
                ->implode(', ');
            $blockers[] = "active official document(s) ({$labels})";
        }

        return $blockers;
    }

    private function relatedCommentRows(
        TrainingModule $module,
        Collection $quizIds,
        bool $lock = false,
    ): Collection {
        $moduleType = $module->getMorphClass();
        $quizType = (new Quiz)->getMorphClass();
        $moduleId = (int) $module->getKey();

        $query = DB::table('classroom_comments')
            ->where(function ($builder) use ($moduleType, $quizType, $moduleId, $quizIds): void {
                $builder
                    ->where(function ($moduleQuery) use ($moduleType, $moduleId): void {
                        $moduleQuery
                            ->where('commentable_type', $moduleType)
                            ->where('commentable_id', $moduleId);
                    });

                if ($quizIds->isNotEmpty()) {
                    $builder->orWhere(function ($quizQuery) use ($quizType, $quizIds): void {
                        $quizQuery
                            ->where('commentable_type', $quizType)
                            ->whereIn('commentable_id', $quizIds);
                    });
                }
            })
            ->select(['id', 'commentable_type', 'commentable_id']);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get();
    }

    private function relatedNotificationRows(
        int $moduleId,
        Collection $quizIds,
        Collection $commentIds,
        bool $lock = false,
    ): Collection {
        $types = array_merge(
            self::MODULE_NOTIFICATION_TYPES,
            self::QUIZ_NOTIFICATION_TYPES,
            [ClassroomCommentPosted::class],
        );
        $query = DB::table('notifications')
            ->whereIn('type', $types)
            ->select(['id', 'type', 'data']);

        $rows = $query->get()->filter(function (object $row) use ($moduleId, $quizIds, $commentIds): bool {
            $data = json_decode((string) $row->data, true);
            if (! is_array($data)) {
                return false;
            }

            if (in_array($row->type, self::MODULE_NOTIFICATION_TYPES, true)) {
                return $this->matchesId($data['module_id'] ?? null, $moduleId);
            }

            if (in_array($row->type, self::QUIZ_NOTIFICATION_TYPES, true)) {
                return $quizIds->contains(fn ($id): bool => $this->matchesId($data['quiz_id'] ?? null, (int) $id));
            }

            return $commentIds->contains(
                fn ($id): bool => $this->matchesId($data['classroom_comment_id'] ?? null, (int) $id),
            );
        })->values();

        if (! $lock || $rows->isEmpty()) {
            return $rows;
        }

        return DB::table('notifications')
            ->whereIn('id', $rows->pluck('id'))
            ->lockForUpdate()
            ->get(['id', 'type', 'data'])
            ->values();
    }

    private function relatedQueueRows(
        string $table,
        int $moduleId,
        Collection $quizIds,
        Collection $commentIds,
        bool $lock = false,
    ): Collection {
        if (! in_array($table, ['jobs', 'failed_jobs'], true)) {
            return collect();
        }

        $rows = DB::table($table)
            ->select(['id', 'payload'])
            ->get()
            ->filter(fn (object $row): bool => $this->queuePayloadMatches(
                (string) $row->payload,
                $moduleId,
                $quizIds,
                $commentIds,
            ))
            ->values();

        if (! $lock || $rows->isEmpty()) {
            return $rows;
        }

        return DB::table($table)
            ->whereIn('id', $rows->pluck('id'))
            ->lockForUpdate()
            ->get(['id', 'payload'])
            ->values();
    }

    private function queuePayloadMatches(
        string $payload,
        int $moduleId,
        Collection $quizIds,
        Collection $commentIds,
    ): bool {
        $decoded = json_decode($payload, true);
        $command = is_array($decoded) ? data_get($decoded, 'data.command') : null;
        if (! is_string($command) || $command === '') {
            return false;
        }

        if (str_contains($command, LmsModulePublished::class)
            || str_contains($command, TrainerModuleAssignedByAdmin::class)) {
            return $this->serializedModelIdentifierMatches($command, TrainingModule::class, $moduleId);
        }

        if (str_contains($command, LmsQuizPublished::class)) {
            return $quizIds->contains(
                fn ($id): bool => $this->serializedModelIdentifierMatches($command, Quiz::class, (int) $id),
            );
        }

        if (str_contains($command, ClassroomCommentPosted::class)) {
            return $commentIds->contains(
                fn ($id): bool => $this->serializedModelIdentifierMatches($command, ClassroomComment::class, (int) $id),
            );
        }

        return false;
    }

    private function serializedModelIdentifierMatches(string $command, string $class, int $id): bool
    {
        $classPattern = preg_quote($class, '/');
        $idPattern = preg_quote((string) $id, '/');
        $pattern = '/s:5:"class";s:\d+:"'.$classPattern.'";s:2:"id";(?:i:'.$idPattern.';|s:\d+:"'.$idPattern.'";)/';

        return preg_match($pattern, $command) === 1;
    }

    private function matchesId(mixed $value, int $expected): bool
    {
        return (is_int($value) && $value === $expected)
            || (is_string($value) && ctype_digit($value) && (int) $value === $expected);
    }

    /** @return Collection<int, string> */
    private function quizSubmissionPaths(Collection $attempts): Collection
    {
        return $attempts
            ->flatMap(function (QuizAttempt $attempt): Collection {
                $prefix = "activity-submissions/{$attempt->enrollment_application_id}/{$attempt->quiz_id}/";

                return $this->structuredFilePaths($attempt->answers, $prefix);
            })
            ->unique()
            ->values();
    }

    /** @return Collection<int, string> */
    private function structuredFilePaths(mixed $value, string $allowedPrefix): Collection
    {
        $paths = collect();

        if (! is_array($value)) {
            return $paths;
        }

        if (isset($value['file_path'])
            && is_string($value['file_path'])
            && str_starts_with($value['file_path'], $allowedPrefix)) {
            $paths->push($value['file_path']);
        }

        foreach ($value as $child) {
            $paths = $paths->merge($this->structuredFilePaths($child, $allowedPrefix));
        }

        return $paths->unique()->values();
    }

    private function deleteRows(string $table, Collection $ids): int
    {
        $ids = $ids
            // Notifications use UUID primary keys; the other module-owned
            // tables use integers. Keep the original key type for both.
            ->filter(fn ($id): bool => (is_int($id) || is_string($id)) && trim((string) $id) !== '')
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return 0;
        }

        return DB::table($table)->whereIn('id', $ids)->delete();
    }

    private function recalculateCompetencyRecords(Collection $recordIds, int $deletedModuleId): void
    {
        foreach ($recordIds->unique()->values() as $recordId) {
            $record = TraineeCompetencyRecord::query()
                ->with(['unit.outcomes'])
                ->lockForUpdate()
                ->find($recordId);

            if (! $record) {
                continue;
            }

            $remainingResults = $record->outcomeResults()->get();

            // A custom/non-TOR record may be locked because another official
            // document locked the trainee globally. Preserve its locked
            // aggregate while removing only the attributable child result.
            if ($record->locked_at) {
                continue;
            }

            if ($remainingResults->isEmpty()) {
                $record->delete();

                continue;
            }

            $unit = $record->unit;
            if (! $unit) {
                continue;
            }

            $requiredOutcomes = $unit->outcomes->where('is_required', true);
            $requiredResults = $remainingResults->whereIn(
                'competency_outcome_id',
                $requiredOutcomes->pluck('id'),
            );
            $status = $this->aggregateCompetencyStatus($requiredOutcomes, $requiredResults);
            $hasUnknownSource = $remainingResults->contains(
                fn (TraineeOutcomeResult $result): bool => $result->training_module_id === null,
            );
            $latestResult = $remainingResults
                ->sortByDesc(fn (TraineeOutcomeResult $result) => $result->assessed_at?->getTimestamp() ?? 0)
                ->first();

            $attributes = [
                'status' => $status,
                'assessed_by_id' => $latestResult?->assessed_by_id,
                'assessed_at' => $latestResult?->assessed_at,
            ];

            if (! $hasUnknownSource) {
                $remainingProgress = $this->remainingUnitProgress(
                    $record->enrollment_application_id,
                    $unit,
                    $deletedModuleId,
                );
                $scores = $remainingProgress
                    ->pluck('quiz_score')
                    ->filter(fn ($score): bool => $score !== null)
                    ->map(fn ($score): float => (float) $score);
                $averageScore = $scores->isNotEmpty() ? round($scores->avg(), 2) : null;
                $latestProgress = $remainingProgress
                    ->sortByDesc(fn (ModuleProgress $progress) => $progress->evaluated_at?->getTimestamp() ?? 0)
                    ->first();

                $attributes['percentage_score'] = $averageScore;
                $attributes['tor_grade'] = $status === TraineeCompetencyRecord::STATUS_COMPETENT
                    ? $this->gradeScale->fromPercentage($averageScore)
                    : null;
                $attributes['notes'] = $latestProgress?->evaluation_remarks;
                $attributes['assessed_by_id'] = $latestResult?->assessed_by_id
                    ?: $latestProgress?->evaluated_by_id;
                $attributes['assessed_at'] = $latestResult?->assessed_at
                    ?: $latestProgress?->evaluated_at;
            }

            $record->forceFill($attributes)->save();
        }
    }

    private function aggregateCompetencyStatus(Collection $requiredOutcomes, Collection $results): string
    {
        if ($requiredOutcomes->isEmpty()) {
            return TraineeCompetencyRecord::STATUS_NOT_ASSESSED;
        }

        $resultsByOutcome = $results->keyBy('competency_outcome_id');
        $allCompetent = $requiredOutcomes->every(
            fn ($outcome): bool => $resultsByOutcome->get($outcome->id)?->status
                === TraineeCompetencyRecord::STATUS_COMPETENT,
        );
        $hasNyc = $results->contains(
            fn (TraineeOutcomeResult $result): bool => $result->status
                === TraineeCompetencyRecord::STATUS_NOT_YET_COMPETENT,
        );
        $hasProgress = $results->contains(
            fn (TraineeOutcomeResult $result): bool => $result->status
                === TraineeCompetencyRecord::STATUS_IN_PROGRESS,
        );

        return match (true) {
            $allCompetent => TraineeCompetencyRecord::STATUS_COMPETENT,
            $hasNyc => TraineeCompetencyRecord::STATUS_NOT_YET_COMPETENT,
            $hasProgress => TraineeCompetencyRecord::STATUS_IN_PROGRESS,
            default => TraineeCompetencyRecord::STATUS_NOT_ASSESSED,
        };
    }

    private function remainingUnitProgress(
        int $applicationId,
        CompetencyUnit $unit,
        int $deletedModuleId,
    ): Collection {
        return ModuleProgress::query()
            ->with('module')
            ->where('enrollment_application_id', $applicationId)
            ->where('training_module_id', '!=', $deletedModuleId)
            ->whereNotNull('evaluated_at')
            ->whereHas('module', function (Builder $query) use ($unit): void {
                $query->where(function (Builder $moduleQuery) use ($unit): void {
                    $moduleQuery
                        ->where('competency_unit_id', $unit->id)
                        ->orWhere(function (Builder $legacyQuery) use ($unit): void {
                            $legacyQuery
                                ->whereNull('competency_unit_id')
                                ->where('module_code', $unit->code);
                        });
                });
            })
            ->get();
    }

    private function deleteOrphanedCustomUnit(?CompetencyUnit $unit): void
    {
        if (! $unit
            || $unit->category !== TrainingModule::CATEGORY_CUSTOM
            || $unit->is_tor_included) {
            return;
        }

        $stillReferenced = TrainingModule::query()
            ->where('competency_unit_id', $unit->id)
            ->exists();
        $hasRecords = TraineeCompetencyRecord::query()
            ->where('competency_unit_id', $unit->id)
            ->exists();

        if (! $stillReferenced && ! $hasRecords) {
            $unit->delete();
        }
    }

    /** @param list<string> $paths */
    private function deleteStoredFiles(array $paths): void
    {
        $paths = collect($paths)
            ->filter(fn ($path): bool => is_string($path) && $path !== '')
            ->unique()
            ->values();

        if ($paths->isEmpty()) {
            return;
        }

        $referenced = TrainingModule::query()
            ->whereIn('file_path', $paths)
            ->pluck('file_path');

        $referenced = $referenced->merge(
            TrainingModule::query()
                ->get(['supplementary_files'])
                ->flatMap(fn (TrainingModule $module): Collection => collect($module->supplementaryList())->pluck('file_path')),
        );

        $referenced = $referenced->merge(
            QuizAttempt::query()
                ->get(['enrollment_application_id', 'quiz_id', 'answers'])
                ->flatMap(function (QuizAttempt $attempt): Collection {
                    return $this->structuredFilePaths(
                        $attempt->answers,
                        "activity-submissions/{$attempt->enrollment_application_id}/{$attempt->quiz_id}/",
                    );
                }),
        );

        $deletable = $paths->reject(fn (string $path): bool => $referenced->contains($path));

        if ($deletable->isNotEmpty()) {
            Storage::disk('local')->delete($deletable->all());
        }
    }
}

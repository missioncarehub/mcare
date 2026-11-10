<?php

namespace Database\Seeders;

use App\Models\CompetencyUnit;
use App\Models\EnrollmentApplication;
use App\Models\ModuleProgress;
use App\Models\OfficialDocument;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\TraineeCompetencyRecord;
use App\Models\TraineeOutcomeResult;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Models\User;
use App\Services\CompletionEligibilityService;
use App\Services\OfficialDocumentManager;
use App\Services\TorGradeScale;
use App\Support\CaregivingNcIiCatalog;
use App\Support\RolePermissionMatrix;
use App\Support\TraineeUserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SampleGraduateSeeder extends Seeder
{
    public const EMAIL = 'graduate@mcare.com';

    public function run(): void
    {
        RolePermissionMatrix::ensureConfigured();

        $admin = User::query()->where('role', 'admin')->orderBy('id')->first();
        $trainer = User::query()->where('role', 'trainer')->orderBy('id')->first() ?? $admin;

        if (! $admin || ! $trainer) {
            throw new RuntimeException('Seed an admin and a trainer before the sample graduate.');
        }

        $application = DB::transaction(function () use ($admin, $trainer): EnrollmentApplication {
            $user = $this->user();
            $batch = $this->batch($user, $trainer);
            $application = $this->application($user, $batch, $admin);
            $this->completeCompetencies($application, $trainer);
            $this->completeModulesAndQuizzes($application, $trainer);
            $application->update([
                'learning_status' => EnrollmentApplication::LEARNING_GRADUATED,
                'learning_status_changed_at' => now(),
                'learning_status_changed_by_id' => $admin->id,
            ]);
            TraineeUserProfile::sync($user, $application->fresh());
            $user->alumniProfile()->updateOrCreate([], [
                'is_available_for_duty' => true,
                'availability_updated_at' => now(),
            ]);

            return $application->fresh('batch');
        });

        $eligibility = app(CompletionEligibilityService::class);
        $evaluation = $eligibility->evaluate($application);

        if (! $evaluation['eligible'] || ! $eligibility->canIssueTor($application, $evaluation)) {
            $waiting = collect($evaluation['checks'])
                ->where('passed', false)
                ->map(fn (array $check): string => $check['label'].' ('.$check['detail'].')')
                ->implode('; ');

            throw new RuntimeException(
                'Sample graduate is not ready for certificates: '.($waiting !== '' ? $waiting : 'graduation was not recorded')
            );
        }

        $documents = app(OfficialDocumentManager::class);
        $cotc = $documents->queue($application, OfficialDocument::TYPE_COTC, $admin);

        if ($cotc->status === OfficialDocument::STATUS_GENERATED) {
            $documents->releaseCotc($cotc, $admin);
        }

        $documents->queue($application, OfficialDocument::TYPE_TOR, $admin);
    }

    private function user(): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => 'Sample Graduate',
                'role' => 'trainee',
                'applicant_status' => EnrollmentApplication::STATUS_APPROVED,
                'password' => 'password123',
                'email_verified_at' => now(),
            ],
        );
        RolePermissionMatrix::syncUser($user);

        return $user;
    }

    private function batch(User $user, User $trainer): TrainingBatch
    {
        $existingBatchId = EnrollmentApplication::query()
            ->where('user_id', $user->id)
            ->value('training_batch_id');

        if ($existingBatchId && $batch = TrainingBatch::query()->find($existingBatchId)) {
            return $batch;
        }

        $quizBatchId = Quiz::query()
            ->where('is_published', true)
            ->whereNotNull('training_batch_id')
            ->orderBy('id')
            ->value('training_batch_id');

        if ($quizBatchId && $batch = TrainingBatch::query()->find($quizBatchId)) {
            return $batch;
        }

        return TrainingBatch::query()->updateOrCreate(
            ['name' => 'Sample Graduate Batch', 'year' => (int) now()->year],
            [
                'trainer_id' => $trainer->id,
                'is_active' => false,
                'enrollment_starts_at' => now()->subYear()->startOfDay(),
                'enrollment_ends_at' => now()->subMonths(8)->endOfDay(),
                'training_starts_at' => now()->subMonths(7)->startOfDay(),
                'training_ends_at' => now()->subDay()->endOfDay(),
                'am_start_time' => '08:00',
                'am_end_time' => '12:00',
                'am_days' => 'MWF',
                'pm_start_time' => '13:00',
                'pm_end_time' => '17:00',
                'pm_days' => 'TTS',
                'notes' => 'Completed sample batch for certificate checks.',
            ],
        );
    }

    private function application(User $user, TrainingBatch $batch, User $admin): EnrollmentApplication
    {
        return EnrollmentApplication::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'email' => $user->email,
                'program' => 'Caregiving NC II',
                'training_batch_id' => $batch->id,
                'first_name' => 'Sample',
                'middle_name' => null,
                'last_name' => 'Graduate',
                'birth_date' => '2000-05-15',
                'birthplace_city' => 'Pili',
                'birthplace_province' => 'Camarines Sur',
                'gender' => 'Female',
                'civil_status' => 'Single',
                'employment_status' => 'Unemployed',
                'contact_number' => '09170000002',
                'nationality' => 'Filipino',
                'schedule_preference' => 'AM',
                'street' => '1 Sample Street',
                'barangay' => 'San Isidro',
                'city' => 'Pili',
                'province' => 'Camarines Sur',
                'region' => 'Region V',
                'zip_code' => '4418',
                'educational_attainment' => 'College Graduate',
                'school_name' => 'MCARE School',
                'year_graduated' => (int) now()->year - 1,
                'classification' => 'Regular',
                'privacy_consent' => true,
                'signature_name' => 'Sample Graduate',
                'date_accomplished' => now()->subYear()->toDateString(),
                'status' => EnrollmentApplication::STATUS_APPROVED,
                'learning_status' => EnrollmentApplication::LEARNING_ACTIVE,
                'payment_method' => 'onsite',
                'payment_status' => EnrollmentApplication::PAYMENT_PAID,
                'payment_amount' => 15000,
                'total_program_fee' => 15000,
                'total_paid_amount' => 15000,
                'payment_currency' => 'PHP',
                'payment_reference' => 'SAMPLE-GRADUATE-PAID',
                'payment_verified_by_id' => $admin->id,
                'payment_verified_at' => now()->subMonths(6),
                'reviewed_by_id' => $admin->id,
                'reviewed_at' => now()->subMonths(7),
                'learning_started_at' => $batch->training_starts_at ?? now()->subMonths(6),
                'admin_notes' => 'Sample graduate with every quiz and completion check passed.',
            ],
        );
    }

    private function completeCompetencies(EnrollmentApplication $application, User $trainer): void
    {
        $grade = app(TorGradeScale::class)->fromPercentage(95);

        CompetencyUnit::query()
            ->with('outcomes')
            ->where('program_code', CaregivingNcIiCatalog::PROGRAM_CODE)
            ->where('is_required', true)
            ->orderBy('sort_order')
            ->each(function (CompetencyUnit $unit) use ($application, $trainer, $grade): void {
                $record = TraineeCompetencyRecord::query()->updateOrCreate(
                    [
                        'enrollment_application_id' => $application->id,
                        'competency_unit_id' => $unit->id,
                    ],
                    [
                        'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
                        'percentage_score' => 95,
                        'tor_grade' => $grade,
                        'notes' => 'Sample graduate assessment completed.',
                        'assessed_by_id' => $trainer->id,
                        'assessed_at' => now()->subDays(2),
                    ],
                );

                foreach ($unit->outcomes as $outcome) {
                    TraineeOutcomeResult::query()->updateOrCreate(
                        [
                            'trainee_competency_record_id' => $record->id,
                            'competency_outcome_id' => $outcome->id,
                        ],
                        [
                            'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
                            'assessed_by_id' => $trainer->id,
                            'assessed_at' => now()->subDays(2),
                        ],
                    );
                }
            });
    }

    private function completeModulesAndQuizzes(EnrollmentApplication $application, User $trainer): void
    {
        CompetencyUnit::query()
            ->where('program_code', CaregivingNcIiCatalog::PROGRAM_CODE)
            ->where('category', TrainingModule::CATEGORY_CORE)
            ->where('is_required', true)
            ->orderBy('sort_order')
            ->each(function (CompetencyUnit $unit) use ($application, $trainer): void {
                $shared = TrainingModule::query()
                    ->where('training_batch_id', $application->training_batch_id)
                    ->where('module_code', $unit->code)
                    ->where('is_published', true)
                    ->whereNull('target_enrollment_application_id')
                    ->orderBy('id')
                    ->first();
                $module = $shared ?? $this->privateModule($application, $trainer, $unit);

                ModuleProgress::query()->updateOrCreate(
                    [
                        'enrollment_application_id' => $application->id,
                        'training_module_id' => $module->id,
                    ],
                    [
                        'sequence_number' => $unit->sort_order,
                        'status' => ModuleProgress::STATUS_COMPLETED,
                        'progress_percent' => 100,
                        'assigned_at' => now()->subMonths(6),
                        'unlocked_at' => now()->subMonths(6),
                        'submitted_at' => now()->subDays(3),
                        'practical_rating' => ModuleProgress::RATING_COMPETENT,
                        'competency_outcome' => ModuleProgress::OUTCOME_COMPETENT,
                        'evaluated_by_id' => $trainer->id,
                        'evaluated_at' => now()->subDays(3),
                        'first_opened_at' => now()->subMonths(5),
                        'last_viewed_at' => now()->subDays(3),
                        'completed_at' => now()->subDays(3),
                    ],
                );

                if ($module->target_enrollment_application_id !== null) {
                    $this->ensureSampleQuiz($module, $application, $trainer, $unit);
                }
            });

        $this->passVisibleQuizzes($application);
    }

    private function privateModule(
        EnrollmentApplication $application,
        User $trainer,
        CompetencyUnit $unit,
    ): TrainingModule {
        $path = 'training-modules/sample-graduate/'.($unit->code ?: 'unit-'.$unit->id).'.txt';

        if (! Storage::disk('local')->exists($path)) {
            Storage::disk('local')->put($path, "Sample graduate completion record for {$unit->code}: {$unit->title}");
        }

        return TrainingModule::query()->updateOrCreate(
            [
                'training_batch_id' => $application->training_batch_id,
                'target_enrollment_application_id' => $application->id,
                'module_code' => $unit->code,
            ],
            [
                'trainer_id' => $trainer->id,
                'competency_unit_id' => $unit->id,
                'competency_category' => TrainingModule::CATEGORY_CORE,
                'completion_mode' => TrainingModule::COMPLETION_ASSESSED,
                'title' => $unit->title,
                'description' => 'Sample graduate completion for this required core competency.',
                'file_path' => $path,
                'original_file_name' => ($unit->code ?: 'module').'.txt',
                'mime_type' => 'text/plain',
                'file_size' => Storage::disk('local')->size($path),
                'is_published' => true,
                'lock_until_previous' => false,
                'delivery_status' => TrainingModule::DELIVERY_CLOSED,
                'published_at' => now()->subMonths(6),
                'activated_at' => now()->subMonths(6),
                'closed_at' => now()->subMonths(5),
                'position' => $unit->sort_order,
            ],
        );
    }

    private function ensureSampleQuiz(
        TrainingModule $module,
        EnrollmentApplication $application,
        User $trainer,
        CompetencyUnit $unit,
    ): void {
        $quiz = Quiz::query()->updateOrCreate(
            [
                'training_module_id' => $module->id,
                'target_enrollment_application_id' => $application->id,
                'title' => 'Sample: '.$unit->code,
            ],
            [
                'trainer_id' => $trainer->id,
                'training_batch_id' => $application->training_batch_id,
                'instructions' => 'Sample completion quiz for '.$unit->title.'.',
                'is_published' => true,
                'published_at' => now()->subMonths(2),
                'available_at' => now()->subMonths(2),
                'due_at' => now()->subMonth(),
                'attempt_limit' => 1,
                'passing_score_percent' => 75,
            ],
        );

        QuizQuestion::query()->updateOrCreate(
            ['quiz_id' => $quiz->id, 'position' => 1],
            [
                'type' => QuizQuestion::TYPE_MULTIPLE_CHOICE,
                'prompt' => 'This sample trainee completed '.$unit->title.'.',
                'options' => ['Yes', 'No'],
                'correct_option' => 0,
                'points' => 1,
            ],
        );
    }

    private function passVisibleQuizzes(EnrollmentApplication $application): void
    {
        $moduleIds = ModuleProgress::query()
            ->where('enrollment_application_id', $application->id)
            ->pluck('training_module_id');

        Quiz::query()
            ->with('questions')
            ->where('is_published', true)
            ->where(function ($query) use ($application, $moduleIds): void {
                $query->whereIn('training_module_id', $moduleIds)
                    ->orWhere('target_enrollment_application_id', $application->id)
                    ->orWhere(function ($batch) use ($application): void {
                        $batch->whereNull('target_enrollment_application_id')
                            ->where('training_batch_id', $application->training_batch_id);
                    });
            })
            ->each(function (Quiz $quiz) use ($application): void {
                $total = (float) $quiz->questions->sum('points');
                $total = $total > 0 ? $total : 10;
                $answers = [];

                foreach ($quiz->questions as $question) {
                    if ($question->correct_option !== null) {
                        $answers[(string) $question->id] = $question->correct_option;
                    }
                }

                $attempt = QuizAttempt::query()->firstOrNew([
                    'quiz_id' => $quiz->id,
                    'enrollment_application_id' => $application->id,
                    'attempt_number' => 1,
                ]);
                $attempt->fill([
                    'status' => QuizAttempt::STATUS_GRADED,
                    'answers' => $answers,
                    'earned_points' => $total,
                    'total_points' => $total,
                    'score_percent' => 100,
                    'passed' => true,
                    'started_at' => now()->subDays(4),
                    'submitted_at' => now()->subDays(4),
                    'graded_at' => now()->subDays(4),
                ])->save();
            });
    }
}

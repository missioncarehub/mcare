<?php

namespace App\Console\Commands;

use App\Models\CompetencyUnit;
use App\Models\EnrollmentApplication;
use App\Models\ModuleProgress;
use App\Models\OfficialDocument;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\TraineeCompetencyRecord;
use App\Models\TraineeOutcomeResult;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Models\User;
use App\Services\CompletionEligibilityService;
use App\Services\OfficialDocumentManager;
use App\Services\TorGradeScale;
use App\Support\CaregivingNcIiCatalog;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class PrepareDemoGraduate extends Command
{
    protected $signature = 'mcare:prepare-demo-graduate
        {--email=demo.completed.mcare@gmail.com : Gmail account used for the demo trainee}
        {--password= : Optional password; a strong password is generated for a new account}
        {--force : Allow this demo-data command outside local or testing environments}';

    protected $description = 'Prepare an eligible demo trainee and generated COTC/TOR documents';

    public function handle(
        CompletionEligibilityService $eligibility,
        OfficialDocumentManager $documents,
        TorGradeScale $gradeScale,
    ): int {
        if (! app()->environment(['local', 'testing']) && ! $this->option('force')) {
            $this->error('Demo records are disabled outside local/testing. Use --force only in an intentional demo environment.');

            return self::FAILURE;
        }

        $email = strtolower(trim((string) $this->option('email')));
        $requestedPassword = filled($this->option('password'))
            ? (string) $this->option('password')
            : null;
        validator(
            ['email' => $email, 'password' => $requestedPassword],
            [
                'email' => ['required', 'email:rfc', 'max:255', 'regex:/@gmail\.com\z/i'],
                'password' => ['nullable', Password::min(12)->mixedCase()->letters()->numbers()->symbols()],
            ],
        )->validate();

        $admin = User::query()->where('role', 'admin')->orderBy('id')->first();
        $assessor = User::query()->where('role', 'trainer')->orderBy('id')->first() ?? $admin;

        if (! $admin || ! $assessor) {
            $this->error('Create at least one admin account before preparing the demo trainee.');

            return self::FAILURE;
        }

        $result = DB::transaction(function () use ($email, $requestedPassword, $admin, $assessor, $gradeScale): array {
            $user = User::query()->where('email', $email)->lockForUpdate()->first();
            $password = $requestedPassword;

            if ($user && $user->role !== 'trainee') {
                throw new \RuntimeException('The demo email already belongs to a non-trainee account.');
            }

            if (! $user) {
                $password ??= Str::password(16);
                $user = User::create([
                    'name' => 'Demo Graduate',
                    'email' => $email,
                    'role' => 'trainee',
                    'applicant_status' => EnrollmentApplication::STATUS_APPROVED,
                    'password' => $password,
                ]);
            } else {
                $user->update(array_filter([
                    'name' => 'Demo Graduate',
                    'applicant_status' => EnrollmentApplication::STATUS_APPROVED,
                    'password' => $password,
                ], fn ($value) => $value !== null));
            }

            $year = (int) now()->year;
            $batch = TrainingBatch::query()->updateOrCreate(
                ['name' => 'MCARE Adviser Demo Batch', 'year' => $year],
                [
                    'is_active' => false,
                    'enrollment_starts_at' => now()->subYear()->startOfDay(),
                    'enrollment_ends_at' => now()->subMonths(11)->endOfDay(),
                    'training_starts_at' => now()->subMonths(10)->startOfDay(),
                    'training_ends_at' => now()->subDay()->endOfDay(),
                    'am_start_time' => '08:00',
                    'am_end_time' => '12:00',
                    'am_room' => 'MCARE Demo Room 1',
                    'am_days' => 'MWF',
                    'pm_start_time' => '13:00',
                    'pm_end_time' => '17:00',
                    'pm_room' => 'MCARE Demo Room 2',
                    'pm_days' => 'TTS',
                    'notes' => 'Completed local demonstration batch for adviser and consultant review.',
                ],
            );
            $application = EnrollmentApplication::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'email' => $email,
                    'program' => 'Caregiving NC II',
                    'training_batch_id' => $batch->id,
                    'first_name' => 'Demo',
                    'middle_name' => 'Adviser',
                    'last_name' => 'Graduate',
                    'birth_date' => '2000-01-15',
                    'birthplace_city' => 'Pili',
                    'birthplace_province' => 'Camarines Sur',
                    'gender' => 'Female',
                    'civil_status' => 'Single',
                    'employment_status' => 'Unemployed',
                    'contact_number' => '09170000000',
                    'nationality' => 'Filipino',
                    'schedule_preference' => 'AM',
                    'street' => '1 Demo Street',
                    'barangay' => 'San Isidro',
                    'city' => 'Pili',
                    'province' => 'Camarines Sur',
                    'region' => 'Region V',
                    'zip_code' => '4418',
                    'educational_attainment' => 'College Graduate',
                    'school_name' => 'MCARE Demonstration School',
                    'year_graduated' => $year - 1,
                    'classification' => 'Regular',
                    'privacy_consent' => true,
                    'signature_name' => 'Demo Adviser Graduate',
                    'date_accomplished' => now()->subYear()->toDateString(),
                    'status' => EnrollmentApplication::STATUS_APPROVED,
                    'learning_status' => EnrollmentApplication::LEARNING_ACTIVE,
                    'payment_method' => 'onsite',
                    'payment_status' => EnrollmentApplication::PAYMENT_PAID,
                    'payment_amount' => 15000,
                    'payment_currency' => 'PHP',
                    'payment_reference' => 'DEMO-PAID-'.$year,
                    'payment_verified_by_id' => $admin->id,
                    'payment_verified_at' => now()->subMonths(10),
                    'reviewed_by_id' => $admin->id,
                    'reviewed_at' => now()->subMonths(11),
                    'learning_started_at' => now()->subMonths(10),
                    'admin_notes' => 'Demo graduate prepared for capstone workflow presentation.',
                ],
            );

            $this->completeCompetencies($application, $assessor, $gradeScale);
            $this->completeLearningRequirements($application, $assessor);

            $application->forceFill([
                'learning_status' => EnrollmentApplication::LEARNING_GRADUATED,
            ])->save();

            $user->forceFill([
                'trainee_status' => EnrollmentApplication::LEARNING_GRADUATED,
            ])->save();

            return compact('user', 'batch', 'application', 'password');
        });

        $application = $result['application']->fresh('batch');
        $completion = $eligibility->evaluate($application);

        if (! $completion['eligible']) {
            $this->error('The demo trainee was created, but one or more completion checks are still blocked.');
            $this->table(
                ['Check', 'State', 'Detail'],
                collect($completion['checks'])->map(fn ($check) => [
                    $check['label'],
                    $check['passed'] ? 'PASS' : 'WAIT',
                    $check['detail'],
                ])->values()->all(),
            );

            return self::FAILURE;
        }

        $cotc = $this->prepareCotc($application, $admin, $documents);
        $tor = $documents->generateNow($documents->queue($application, OfficialDocument::TYPE_TOR, $admin));

        $this->newLine();
        $this->info('Demo graduate is ready.');
        $this->table(['Item', 'Value'], [
            ['Trainee', trim($application->first_name.' '.$application->middle_name.' '.$application->last_name)],
            ['Gmail', $result['user']->email],
            ['Password', $result['password'] ?? 'Unchanged from the existing demo account'],
            ['Batch', $result['batch']->name.' '.$result['batch']->year],
            ['Eligibility', 'PASS - all completion requirements'],
            ['COTC', $cotc->document_number.' | '.$cotc->status],
            ['TOR', $tor->document_number.' | '.$tor->status],
        ]);
        $this->line('Trainee login: '.url('/login'));
        $this->line('Admin documents: '.url('/admin/learning/certificates?batch_id='.$result['batch']->id.'&eligibility=eligible'));

        return self::SUCCESS;
    }

    private function completeCompetencies(
        EnrollmentApplication $application,
        User $assessor,
        TorGradeScale $gradeScale,
    ): void {
        CompetencyUnit::query()
            ->with('outcomes')
            ->where('program_code', CaregivingNcIiCatalog::PROGRAM_CODE)
            ->where('is_required', true)
            ->orderBy('sort_order')
            ->each(function ($unit) use ($application, $assessor, $gradeScale): void {
                $record = TraineeCompetencyRecord::query()->updateOrCreate(
                    [
                        'enrollment_application_id' => $application->id,
                        'competency_unit_id' => $unit->id,
                    ],
                    [
                        'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
                        'percentage_score' => 95,
                        'tor_grade' => $gradeScale->fromPercentage(95),
                        'notes' => 'Demo practical assessment completed and verified.',
                        'assessed_by_id' => $assessor->id,
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
                            'assessed_by_id' => $assessor->id,
                            'assessed_at' => now()->subDays(2),
                        ],
                    );
                }
            });
    }

    private function completeLearningRequirements(EnrollmentApplication $application, User $assessor): void
    {
        CompetencyUnit::query()
            ->where('program_code', CaregivingNcIiCatalog::PROGRAM_CODE)
            ->where('category', TrainingModule::CATEGORY_CORE)
            ->where('is_required', true)
            ->orderBy('sort_order')
            ->each(function (CompetencyUnit $unit) use ($application, $assessor): void {
                $path = sprintf(
                    'training-modules/demo/%d/%s.txt',
                    $application->id,
                    Str::slug($unit->code),
                );

                if (! Storage::disk('local')->exists($path)) {
                    Storage::disk('local')->put(
                        $path,
                        "MCARE demo completion record for {$unit->code}: {$unit->title}",
                    );
                }

                TrainingModule::query()->updateOrCreate(
                    [
                        'training_batch_id' => $application->training_batch_id,
                        'target_enrollment_application_id' => $application->id,
                        'module_code' => $unit->code,
                    ],
                    [
                        'trainer_id' => $assessor->id,
                        'competency_category' => TrainingModule::CATEGORY_CORE,
                        'title' => $unit->title,
                        'description' => 'Trainer-validated demo completion for this required core competency.',
                        'file_path' => $path,
                        'original_file_name' => $unit->code.'.txt',
                        'mime_type' => 'text/plain',
                        'file_size' => Storage::disk('local')->size($path),
                        'is_published' => true,
                        'delivery_status' => TrainingModule::DELIVERY_CLOSED,
                        'published_at' => now()->subMonths(10),
                        'activated_at' => now()->subMonths(10),
                        'closed_at' => now()->subMonths(9),
                        'position' => $unit->sort_order,
                    ],
                );
            });

        $this->moduleQuery($application)->each(function (TrainingModule $module) use ($application, $assessor): void {
            ModuleProgress::query()->updateOrCreate(
                [
                    'enrollment_application_id' => $application->id,
                    'training_module_id' => $module->id,
                ],
                [
                    'sequence_number' => $module->position ?: $module->id,
                    'status' => ModuleProgress::STATUS_COMPLETED,
                    'progress_percent' => 100,
                    'assigned_at' => now()->subMonths(10),
                    'unlocked_at' => now()->subMonths(10),
                    'submitted_at' => now()->subDays(3),
                    'practical_rating' => ModuleProgress::RATING_COMPETENT,
                    'competency_outcome' => ModuleProgress::OUTCOME_COMPETENT,
                    'evaluated_by_id' => $assessor->id,
                    'evaluated_at' => now()->subDays(3),
                    'first_opened_at' => now()->subMonth(),
                    'last_viewed_at' => now()->subDays(3),
                    'completed_at' => now()->subDays(3),
                ],
            );
        });

        $this->quizQuery($application)->each(function (Quiz $quiz) use ($application): void {
            $attempt = QuizAttempt::query()
                ->where('quiz_id', $quiz->id)
                ->where('enrollment_application_id', $application->id)
                ->orderBy('attempt_number')
                ->first();

            if (! $attempt) {
                $attempt = new QuizAttempt([
                    'quiz_id' => $quiz->id,
                    'enrollment_application_id' => $application->id,
                    'attempt_number' => 1,
                ]);
            }

            $attempt->fill([
                'status' => QuizAttempt::STATUS_GRADED,
                'answers' => [],
                'earned_points' => 10,
                'total_points' => 10,
                'score_percent' => 100,
                'passed' => true,
                'started_at' => now()->subDays(4),
                'submitted_at' => now()->subDays(4),
                'graded_at' => now()->subDays(4),
            ])->save();
        });
    }

    private function moduleQuery(EnrollmentApplication $application): Builder
    {
        return TrainingModule::query()
            ->where('is_published', true)
            ->where(function ($query) use ($application) {
                $query->where('target_enrollment_application_id', $application->id)
                    ->orWhere(function ($batchQuery) use ($application) {
                        $batchQuery->whereNull('target_enrollment_application_id')
                            ->where(fn ($scopeQuery) => $scopeQuery
                                ->whereNull('training_batch_id')
                                ->orWhere('training_batch_id', $application->training_batch_id));
                    });
            });
    }

    private function quizQuery(EnrollmentApplication $application): Builder
    {
        return Quiz::query()
            ->where('is_published', true)
            ->where(function ($query) use ($application) {
                $query->where('target_enrollment_application_id', $application->id)
                    ->orWhere(function ($batchQuery) use ($application) {
                        $batchQuery->whereNull('target_enrollment_application_id')
                            ->where(fn ($scopeQuery) => $scopeQuery
                                ->whereNull('training_batch_id')
                                ->orWhere('training_batch_id', $application->training_batch_id));
                    });
            });
    }

    private function prepareCotc(
        EnrollmentApplication $application,
        User $admin,
        OfficialDocumentManager $documents,
    ): OfficialDocument {
        $current = OfficialDocument::query()
            ->where('enrollment_application_id', $application->id)
            ->where('type', OfficialDocument::TYPE_COTC)
            ->where('status', '!=', OfficialDocument::STATUS_REVOKED)
            ->latest('version')
            ->first();
        $cotc = $current?->status === OfficialDocument::STATUS_DOWNLOADED
            ? $documents->reissue($application, OfficialDocument::TYPE_COTC, $admin, 'Reset for the scheduled capstone demonstration.')
            : $documents->queue($application, OfficialDocument::TYPE_COTC, $admin);
        $cotc = $documents->generateNow($cotc);

        return $cotc->status === OfficialDocument::STATUS_GENERATED
            ? $documents->releaseCotc($cotc, $admin)
            : $cotc;
    }
}

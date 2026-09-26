<?php

namespace Tests\Feature\Lms;

use App\Models\AdminActivityLog;
use App\Models\ClassroomComment;
use App\Models\CompetencyOutcome;
use App\Models\CompetencyUnit;
use App\Models\EnrollmentApplication;
use App\Models\ModuleProgress;
use App\Models\OfficialDocument;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\TraineeAttendance;
use App\Models\TraineeCompetencyRecord;
use App\Models\TraineeOutcomeResult;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Models\TrainingSubmoduleProgress;
use App\Models\User;
use App\Notifications\ClassroomCommentPosted;
use App\Notifications\LmsQuizPublished;
use App\Notifications\TrainerModuleAssignedByAdmin;
use App\Services\CompletionEligibilityService;
use App\Services\ModuleSubmoduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class TrainingModuleDeletionTest extends TestCase
{
    use CreatesLmsTestData, RefreshDatabase;

    public function test_admin_can_permanently_delete_a_module_and_its_exact_learning_dependencies(): void
    {
        Storage::fake('local');
        $admin = $this->lmsUser('admin');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $trainee, 'application' => $application] = $this->lmsTrainee($batch);
        $module = $this->deletionModule($trainer, $batch, [
            'title' => 'Module to permanently remove',
            'file_path' => 'training-modules/delete/primary.pdf',
            'supplementary_files' => [
                ['file_path' => 'training-modules/delete/handout.pdf', 'original_name' => 'handout.pdf'],
            ],
        ]);
        Storage::disk('local')->put($module->file_path, '%PDF primary');
        Storage::disk('local')->put('training-modules/delete/handout.pdf', '%PDF handout');

        $submodule = $this->lmsSubmodule($module);
        $parentProgress = ModuleProgress::create([
            'enrollment_application_id' => $application->id,
            'training_module_id' => $module->id,
            'status' => ModuleProgress::STATUS_COMPLETED,
            'progress_percent' => 100,
            'assigned_at' => now()->subDay(),
            'unlocked_at' => now()->subDay(),
            'completed_at' => now(),
            'quiz_score' => 90,
            'competency_outcome' => ModuleProgress::OUTCOME_COMPETENT,
            'evaluated_by_id' => $trainer->id,
            'evaluated_at' => now(),
        ]);
        $childProgress = TrainingSubmoduleProgress::create([
            'enrollment_application_id' => $application->id,
            'training_submodule_id' => $submodule->id,
            'status' => TrainingSubmoduleProgress::STATUS_COMPLETED,
            'progress_percent' => 100,
            'completed_at' => now(),
            'quiz_score' => 90,
            'competency_outcome' => ModuleProgress::OUTCOME_COMPETENT,
            'evaluated_by_id' => $trainer->id,
            'evaluated_at' => now(),
        ]);
        $quiz = Quiz::create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'training_module_id' => $module->id,
            'training_submodule_id' => $submodule->id,
            'title' => 'Module quiz to remove',
            'is_published' => true,
            'published_at' => now(),
            'attempt_limit' => 1,
            'passing_score_percent' => 75,
        ]);
        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'type' => QuizQuestion::TYPE_TRUE_FALSE,
            'prompt' => 'Question to remove',
            'options' => ['True', 'False'],
            'correct_option' => 0,
            'points' => 10,
            'position' => 0,
        ]);
        $submissionPath = "activity-submissions/{$application->id}/{$quiz->id}/answer.pdf";
        Storage::disk('local')->put($submissionPath, '%PDF answer');
        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'enrollment_application_id' => $application->id,
            'attempt_number' => 1,
            'status' => QuizAttempt::STATUS_GRADED,
            'answers' => [
                (string) $question->id => [
                    'type' => 'file',
                    'file_path' => $submissionPath,
                    'original_name' => 'answer.pdf',
                ],
            ],
            'earned_points' => 10,
            'total_points' => 10,
            'score_percent' => 100,
            'passed' => true,
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now()->subMinutes(5),
            'graded_at' => now(),
        ]);
        $attendance = TraineeAttendance::create([
            'training_batch_id' => $batch->id,
            'enrollment_application_id' => $application->id,
            'attendance_date' => now()->toDateString(),
            'quiz_id' => $quiz->id,
            'status' => TraineeAttendance::STATUS_PRESENT,
            'check_in_type' => TraineeAttendance::TYPE_ACTIVITY_TIME_IN,
            'timed_in_at' => now(),
            'recorded_by_id' => $trainer->id,
        ]);
        $moduleComment = $module->comments()->create([
            'author_id' => $admin->id,
            'training_batch_id' => $batch->id,
            'visibility' => ClassroomComment::VISIBILITY_CLASS,
            'body' => 'Module comment to remove',
        ]);
        $deletedComment = $module->comments()->create([
            'author_id' => $admin->id,
            'training_batch_id' => $batch->id,
            'visibility' => ClassroomComment::VISIBILITY_PRIVATE,
            'body' => 'Soft-deleted module comment to remove',
        ]);
        $deletedComment->delete();
        $quizComment = $quiz->comments()->create([
            'author_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'visibility' => ClassroomComment::VISIBILITY_CLASS,
            'body' => 'Quiz comment to remove',
        ]);
        $moduleNotification = $trainer->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => TrainerModuleAssignedByAdmin::class,
            'data' => ['module_id' => $module->id, 'title' => $module->title],
        ]);
        $quizNotification = $trainee->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => LmsQuizPublished::class,
            'data' => ['quiz_id' => $quiz->id, 'title' => $quiz->title],
        ]);
        $commentNotification = $trainee->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => ClassroomCommentPosted::class,
            'data' => ['classroom_comment_id' => $quizComment->id],
        ]);
        $queuedJobId = $this->insertQueuedNotificationJob(
            $trainer,
            new TrainerModuleAssignedByAdmin($module),
        );
        $failedJobId = $this->insertFailedNotificationJob(
            $trainer,
            new TrainerModuleAssignedByAdmin($module),
        );

        $this->actingAs($admin)
            ->delete(route('admin.learning.modules.destroy', $module), ['confirmation' => 'DELETE'])
            ->assertRedirect(route('admin.learning.modules'))
            ->assertSessionHas('saved');

        $this->assertDatabaseMissing('training_modules', ['id' => $module->id]);
        $this->assertDatabaseMissing('module_progress', ['id' => $parentProgress->id]);
        $this->assertDatabaseMissing('training_submodule_progress', ['id' => $childProgress->id]);
        $this->assertDatabaseMissing('training_submodules', ['id' => $submodule->id]);
        $this->assertDatabaseMissing('quizzes', ['id' => $quiz->id]);
        $this->assertDatabaseMissing('quiz_questions', ['id' => $question->id]);
        $this->assertDatabaseMissing('quiz_attempts', ['id' => $attempt->id]);
        $this->assertDatabaseMissing('trainee_attendances', ['id' => $attendance->id]);
        $this->assertDatabaseMissing('classroom_comments', ['id' => $moduleComment->id]);
        $this->assertDatabaseMissing('classroom_comments', ['id' => $deletedComment->id]);
        $this->assertDatabaseMissing('classroom_comments', ['id' => $quizComment->id]);
        $this->assertDatabaseMissing('notifications', ['id' => $moduleNotification->id]);
        $this->assertDatabaseMissing('notifications', ['id' => $quizNotification->id]);
        $this->assertDatabaseMissing('notifications', ['id' => $commentNotification->id]);
        $this->assertDatabaseMissing('jobs', ['id' => $queuedJobId]);
        $this->assertDatabaseMissing('failed_jobs', ['id' => $failedJobId]);
        Storage::disk('local')->assertMissing($module->file_path);
        Storage::disk('local')->assertMissing('training-modules/delete/handout.pdf');
        Storage::disk('local')->assertMissing($submissionPath);

        $log = AdminActivityLog::query()
            ->where('action', 'admin.module.permanently_deleted')
            ->where('subject_id', $module->id)
            ->firstOrFail();
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame($module->id, $log->meta['module_id']);
        $this->assertSame(1, $log->meta['affected_record_counts']['parent_progress_records']);
        $this->assertArrayNotHasKey('learner_grades', $log->meta);
    }

    public function test_admin_module_page_shows_impact_counts_and_explicit_confirmation(): void
    {
        $admin = $this->lmsUser('admin');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        $module = $this->deletionModule($trainer, $batch, ['title' => 'Impact summary module']);

        $this->actingAs($admin)
            ->get(route('admin.learning.modules'))
            ->assertOk()
            ->assertSee('Permanently delete', false)
            ->assertSee('data-dashboard-dialog-open="delete-module-'.$module->id.'"', false)
            ->assertSee('Affected trainees', false)
            ->assertSee('Parent progress / grades', false)
            ->assertSee('aria-label="Preview module"', false)
            ->assertSee('aria-label="Open module comments"', false)
            ->assertSee('aria-label="Permanently delete module"', false)
            ->assertSee('admin-module-action-tooltip', false)
            ->assertSee('Type DELETE to confirm', false)
            ->assertSee('name="confirmation"', false);
    }

    public function test_permanent_deletion_requires_confirmation_and_uses_a_stable_redirect(): void
    {
        Storage::fake('local');
        $admin = $this->lmsUser('admin');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        $module = $this->deletionModule($trainer, $batch);

        $this->actingAs($admin)
            ->delete(route('admin.learning.modules.destroy', $module))
            ->assertRedirect(route('admin.learning.modules'))
            ->assertSessionHasErrors(['module']);
        $this->assertDatabaseHas('training_modules', ['id' => $module->id]);

        $this->actingAs($admin)
            ->delete(route('admin.learning.modules.destroy', $module), ['confirmation' => 'delete permanently'])
            ->assertRedirect(route('admin.learning.modules'))
            ->assertSessionHasErrors(['module']);
        $this->assertDatabaseHas('training_modules', ['id' => $module->id]);
    }

    public function test_permanent_deletion_is_admin_only_and_does_not_change_trainer_delete_protection(): void
    {
        $admin = $this->lmsUser('admin');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['application' => $application] = $this->lmsTrainee($batch);
        $module = $this->deletionModule($trainer, $batch);
        ModuleProgress::create([
            'enrollment_application_id' => $application->id,
            'training_module_id' => $module->id,
            'status' => ModuleProgress::STATUS_IN_PROGRESS,
            'progress_percent' => 20,
            'assigned_at' => now(),
            'unlocked_at' => now(),
        ]);

        $this->actingAs($trainer)
            ->delete(route('admin.learning.modules.destroy', $module), ['confirmation' => 'DELETE'])
            ->assertForbidden();
        $this->assertDatabaseHas('training_modules', ['id' => $module->id]);

        $this->actingAs($admin)
            ->delete(route('admin.learning.modules.destroy', $module), ['confirmation' => 'DELETE'])
            ->assertRedirect(route('admin.learning.modules'))
            ->assertSessionHas('saved');
        $this->assertDatabaseMissing('training_modules', ['id' => $module->id]);
    }

    public function test_connected_trainees_and_official_records_do_not_block_admin_deletion(): void
    {
        Storage::fake('local');
        $admin = $this->lmsUser('admin');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['application' => $application] = $this->lmsTrainee($batch);
        [$unit, $outcome] = $this->torIncludedUnit('Protected module unit');
        $module = $this->deletionModule($trainer, $batch, [
            'module_code' => $unit->code,
            'competency_category' => TrainingModule::CATEGORY_CORE,
            'competency_unit_id' => $unit->id,
            'file_path' => 'training-modules/protected.pdf',
        ]);
        Storage::disk('local')->put($module->file_path, '%PDF protected');
        $submodule = $this->lmsSubmodule($module);
        $progress = ModuleProgress::create([
            'enrollment_application_id' => $application->id,
            'training_module_id' => $module->id,
            'status' => ModuleProgress::STATUS_COMPLETED,
            'progress_percent' => 100,
            'assigned_at' => now(),
            'unlocked_at' => now(),
            'completed_at' => now(),
        ]);
        $record = TraineeCompetencyRecord::create([
            'enrollment_application_id' => $application->id,
            'competency_unit_id' => $unit->id,
            'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
            'percentage_score' => 95,
            'tor_grade' => 1.30,
            'locked_at' => now(),
        ]);
        $result = TraineeOutcomeResult::create([
            'trainee_competency_record_id' => $record->id,
            'training_module_id' => $module->id,
            'competency_outcome_id' => $outcome->id,
            'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
            'assessed_by_id' => $trainer->id,
            'assessed_at' => now(),
        ]);
        $tor = $this->officialDocument($application, OfficialDocument::TYPE_TOR, OfficialDocument::STATUS_GENERATED, 'TOR');
        $cotc = $this->officialDocument($application, OfficialDocument::TYPE_COTC, OfficialDocument::STATUS_RELEASED, 'COTC');

        $this->actingAs($admin)
            ->delete(route('admin.learning.modules.destroy', $module), ['confirmation' => 'DELETE'])
            ->assertRedirect(route('admin.learning.modules'))
            ->assertSessionHas('saved');

        $this->assertDatabaseMissing('training_modules', ['id' => $module->id]);
        $this->assertDatabaseMissing('module_progress', ['id' => $progress->id]);
        $this->assertDatabaseMissing('trainee_outcome_results', ['id' => $result->id]);
        $this->assertDatabaseHas('trainee_competency_records', ['id' => $record->id]);
        $this->assertDatabaseHas('official_documents', ['id' => $tor->id, 'type' => OfficialDocument::TYPE_TOR]);
        $this->assertDatabaseHas('official_documents', ['id' => $cotc->id, 'type' => OfficialDocument::TYPE_COTC]);
        Storage::disk('local')->assertMissing($module->file_path);
        $this->assertNull($submodule->fresh());
    }

    public function test_a_supplemental_module_is_not_blocked_by_an_unrelated_official_document(): void
    {
        Storage::fake('local');
        $admin = $this->lmsUser('admin');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['application' => $application] = $this->lmsTrainee($batch);
        [$unit, $outcome] = $this->customUnit('Supplemental module unit');
        $module = $this->deletionModule($trainer, $batch, [
            'competency_category' => TrainingModule::CATEGORY_CUSTOM,
            'competency_unit_id' => $unit->id,
            'file_path' => 'training-modules/supplemental.pdf',
        ]);
        Storage::disk('local')->put($module->file_path, '%PDF supplemental');
        $this->lmsSubmodule($module);
        $progress = ModuleProgress::create([
            'enrollment_application_id' => $application->id,
            'training_module_id' => $module->id,
            'status' => ModuleProgress::STATUS_COMPLETED,
            'progress_percent' => 100,
            'assigned_at' => now(),
            'unlocked_at' => now(),
            'completed_at' => now(),
        ]);
        $record = TraineeCompetencyRecord::create([
            'enrollment_application_id' => $application->id,
            'competency_unit_id' => $unit->id,
            'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
            'locked_at' => now(),
        ]);
        $result = TraineeOutcomeResult::create([
            'trainee_competency_record_id' => $record->id,
            'training_module_id' => $module->id,
            'competency_outcome_id' => $outcome->id,
            'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
        ]);
        $official = $this->officialDocument($application, OfficialDocument::TYPE_COTC, OfficialDocument::STATUS_GENERATED, 'SUPPLEMENTAL-COTC');

        $this->actingAs($admin)
            ->delete(route('admin.learning.modules.destroy', $module), ['confirmation' => 'DELETE'])
            ->assertRedirect(route('admin.learning.modules'))
            ->assertSessionHas('saved');

        $this->assertDatabaseMissing('training_modules', ['id' => $module->id]);
        $this->assertDatabaseMissing('module_progress', ['id' => $progress->id]);
        $this->assertDatabaseMissing('trainee_outcome_results', ['id' => $result->id]);
        $this->assertDatabaseHas('trainee_competency_records', ['id' => $record->id, 'locked_at' => $record->locked_at]);
        $this->assertDatabaseHas('official_documents', ['id' => $official->id, 'status' => OfficialDocument::STATUS_GENERATED]);
        Storage::disk('local')->assertMissing($module->file_path);
    }

    public function test_shared_unit_results_are_preserved_and_the_remaining_aggregate_is_recalculated(): void
    {
        Storage::fake('local');
        $admin = $this->lmsUser('admin');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['application' => $application] = $this->lmsTrainee($batch);
        [$unit, $firstOutcome, $secondOutcome] = $this->customUnitWithTwoOutcomes('Shared module unit');
        $first = $this->deletionModule($trainer, $batch, [
            'title' => 'First shared module',
            'competency_category' => TrainingModule::CATEGORY_CUSTOM,
            'competency_unit_id' => $unit->id,
            'file_path' => 'training-modules/shared-first.pdf',
        ]);
        $second = $this->deletionModule($trainer, $batch, [
            'title' => 'Second shared module',
            'competency_category' => TrainingModule::CATEGORY_CUSTOM,
            'competency_unit_id' => $unit->id,
            'file_path' => 'training-modules/shared-second.pdf',
        ]);
        Storage::disk('local')->put($first->file_path, '%PDF first');
        Storage::disk('local')->put($second->file_path, '%PDF second');
        $this->lmsSubmodule($first);
        $this->lmsSubmodule($second);
        $firstProgress = ModuleProgress::create([
            'enrollment_application_id' => $application->id,
            'training_module_id' => $first->id,
            'status' => ModuleProgress::STATUS_COMPLETED,
            'progress_percent' => 100,
            'assigned_at' => now(),
            'unlocked_at' => now(),
            'completed_at' => now(),
            'quiz_score' => 95,
            'evaluated_by_id' => $trainer->id,
            'evaluated_at' => now()->subMinutes(2),
            'evaluation_remarks' => 'First evaluation',
        ]);
        $secondProgress = ModuleProgress::create([
            'enrollment_application_id' => $application->id,
            'training_module_id' => $second->id,
            'status' => ModuleProgress::STATUS_IN_PROGRESS,
            'progress_percent' => 60,
            'assigned_at' => now(),
            'unlocked_at' => now(),
            'quiz_score' => 88,
            'evaluated_by_id' => $trainer->id,
            'evaluated_at' => now(),
            'evaluation_remarks' => 'Remaining evaluation',
        ]);
        $record = TraineeCompetencyRecord::create([
            'enrollment_application_id' => $application->id,
            'competency_unit_id' => $unit->id,
            'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
            'percentage_score' => 95,
            'tor_grade' => 1.30,
            'notes' => 'First evaluation',
        ]);
        $firstResult = TraineeOutcomeResult::create([
            'trainee_competency_record_id' => $record->id,
            'training_module_id' => $first->id,
            'competency_outcome_id' => $firstOutcome->id,
            'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
        ]);
        $secondResult = TraineeOutcomeResult::create([
            'trainee_competency_record_id' => $record->id,
            'training_module_id' => $second->id,
            'competency_outcome_id' => $secondOutcome->id,
            'status' => TraineeCompetencyRecord::STATUS_IN_PROGRESS,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.learning.modules.destroy', $first), ['confirmation' => 'DELETE'])
            ->assertRedirect(route('admin.learning.modules'))
            ->assertSessionHas('saved');

        $remainingRecord = $record->fresh();
        $this->assertNotNull($remainingRecord);
        $this->assertSame(TraineeCompetencyRecord::STATUS_IN_PROGRESS, $remainingRecord->status);
        $this->assertSame('88.00', (string) $remainingRecord->percentage_score);
        $this->assertNull($remainingRecord->tor_grade);
        $this->assertSame('Remaining evaluation', $remainingRecord->notes);
        $this->assertDatabaseMissing('trainee_outcome_results', ['id' => $firstResult->id]);
        $this->assertDatabaseHas('trainee_outcome_results', [
            'id' => $secondResult->id,
            'training_module_id' => $second->id,
        ]);
        $this->assertDatabaseHas('training_modules', ['id' => $second->id]);
        $this->assertDatabaseHas('module_progress', ['id' => $secondProgress->id]);
        $this->assertDatabaseMissing('module_progress', ['id' => $firstProgress->id]);
        Storage::disk('local')->assertMissing($first->file_path);
        Storage::disk('local')->assertExists($second->file_path);
    }

    public function test_unattributed_legacy_outcome_results_are_not_deleted_with_a_module(): void
    {
        Storage::fake('local');
        $admin = $this->lmsUser('admin');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['application' => $application] = $this->lmsTrainee($batch);
        [$unit, $outcome] = $this->customUnit('Legacy shared unit');
        $module = $this->deletionModule($trainer, $batch, [
            'competency_category' => TrainingModule::CATEGORY_CUSTOM,
            'competency_unit_id' => $unit->id,
        ]);
        $record = TraineeCompetencyRecord::create([
            'enrollment_application_id' => $application->id,
            'competency_unit_id' => $unit->id,
            'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
            'percentage_score' => 90,
            'tor_grade' => 1.75,
        ]);
        $legacyResult = TraineeOutcomeResult::create([
            'trainee_competency_record_id' => $record->id,
            'competency_outcome_id' => $outcome->id,
            'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.learning.modules.destroy', $module), ['confirmation' => 'DELETE'])
            ->assertRedirect(route('admin.learning.modules'));

        $this->assertDatabaseMissing('training_modules', ['id' => $module->id]);
        $this->assertDatabaseHas('trainee_competency_records', ['id' => $record->id]);
        $this->assertDatabaseHas('trainee_outcome_results', [
            'id' => $legacyResult->id,
            'training_module_id' => null,
        ]);
    }

    public function test_deleted_module_disappears_from_role_surfaces_and_completion_calculation(): void
    {
        Storage::fake('local');
        $admin = $this->lmsUser('admin');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $trainee, 'application' => $application] = $this->lmsTrainee($batch);
        $application->update(['payment_status' => EnrollmentApplication::PAYMENT_PAID]);
        [$unit, $outcome] = $this->torIncludedUnit('Visible completion module');
        $module = $this->deletionModule($trainer, $batch, [
            'title' => 'Visible module before deletion',
            'module_code' => $unit->code,
            'competency_category' => TrainingModule::CATEGORY_CORE,
            'competency_unit_id' => $unit->id,
            'is_published' => true,
            'published_at' => now(),
            'file_path' => 'training-modules/visible.pdf',
        ]);
        Storage::disk('local')->put($module->file_path, '%PDF visible');
        $submodule = $this->lmsSubmodule($module);
        $progress = ModuleProgress::create([
            'enrollment_application_id' => $application->id,
            'training_module_id' => $module->id,
            'status' => ModuleProgress::STATUS_COMPLETED,
            'progress_percent' => 100,
            'assigned_at' => now(),
            'unlocked_at' => now(),
            'completed_at' => now(),
            'quiz_score' => 90,
            'competency_outcome' => ModuleProgress::OUTCOME_COMPETENT,
            'evaluated_by_id' => $trainer->id,
            'evaluated_at' => now(),
        ]);
        $record = TraineeCompetencyRecord::create([
            'enrollment_application_id' => $application->id,
            'competency_unit_id' => $unit->id,
            'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
            'percentage_score' => 90,
            'tor_grade' => 1.75,
        ]);
        TraineeOutcomeResult::create([
            'trainee_competency_record_id' => $record->id,
            'training_module_id' => $module->id,
            'competency_outcome_id' => $outcome->id,
            'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
        ]);
        $this->lmsPassedAssessment($trainer, $module, $application, 90);

        $eligibilityBeforeDeletion = app(CompletionEligibilityService::class)->evaluate($application->fresh());
        $this->assertSame(1, $eligibilityBeforeDeletion['counts']['completed_modules']);
        $this->assertSame(1, $eligibilityBeforeDeletion['counts']['passed_quizzes']);
        $this->actingAs($trainer)->get(route('trainer.resources'))->assertSee($module->title);
        $this->actingAs($trainee)->get(route('trainee.modules.index'))->assertSee($module->title);
        $this->actingAs($trainee)->get(route('trainee.dashboard'))->assertSee($module->title);

        $this->actingAs($admin)
            ->delete(route('admin.learning.modules.destroy', $module), ['confirmation' => 'DELETE'])
            ->assertRedirect(route('admin.learning.modules'))
            ->assertSessionHas('saved');

        // The success flash includes the deleted title; clear it before asserting
        // that role-scoped module surfaces no longer render the deleted module.
        session()->forget('saved');

        $this->actingAs($trainer)
            ->get(route('trainer.resources'))
            ->assertOk()
            ->assertDontSee($module->title);
        $this->actingAs($trainee)
            ->get(route('trainee.modules.index'))
            ->assertOk()
            ->assertDontSee($module->title);
        $this->actingAs($trainee)
            ->get(route('trainee.dashboard'))
            ->assertOk()
            ->assertDontSee($module->title);

        $application->update(['learning_status' => EnrollmentApplication::LEARNING_GRADUATED]);
        $this->actingAs($trainee->fresh())
            ->get(route('trainee.grades'))
            ->assertOk()
            ->assertSee('No trainer-validated grades yet')
            ->assertDontSee($module->title);
        $eligibilityAfterDeletion = app(CompletionEligibilityService::class)->evaluate($application->fresh());
        $this->assertFalse($eligibilityAfterDeletion['eligible']);
        $this->assertSame(0, $eligibilityAfterDeletion['counts']['completed_modules']);
        $this->assertSame(0, $eligibilityAfterDeletion['counts']['quizzes']);
        $this->assertDatabaseMissing('module_progress', ['id' => $progress->id]);
        $this->assertDatabaseMissing('training_submodule_progress', ['training_submodule_id' => $submodule->id]);
    }

    public function test_deletion_preserves_another_trainees_module_records(): void
    {
        Storage::fake('local');
        $admin = $this->lmsUser('admin');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['application' => $firstApplication] = $this->lmsTrainee($batch);
        ['application' => $secondApplication] = $this->lmsTrainee($batch);
        $deletedModule = $this->deletionModule($trainer, $batch, [
            'title' => 'Only first trainee module',
            'file_path' => 'training-modules/first-only.pdf',
        ]);
        $keptModule = $this->deletionModule($trainer, $batch, [
            'title' => 'Second trainee module to keep',
            'file_path' => 'training-modules/second-keep.pdf',
        ]);
        Storage::disk('local')->put($deletedModule->file_path, '%PDF first');
        Storage::disk('local')->put($keptModule->file_path, '%PDF second');
        $deletedProgress = ModuleProgress::create([
            'enrollment_application_id' => $firstApplication->id,
            'training_module_id' => $deletedModule->id,
            'status' => ModuleProgress::STATUS_IN_PROGRESS,
            'progress_percent' => 25,
            'assigned_at' => now(),
            'unlocked_at' => now(),
        ]);
        $keptProgress = ModuleProgress::create([
            'enrollment_application_id' => $secondApplication->id,
            'training_module_id' => $keptModule->id,
            'status' => ModuleProgress::STATUS_COMPLETED,
            'progress_percent' => 100,
            'assigned_at' => now(),
            'unlocked_at' => now(),
            'completed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.learning.modules.destroy', $deletedModule), ['confirmation' => 'DELETE'])
            ->assertRedirect(route('admin.learning.modules'))
            ->assertSessionHas('saved');

        $this->assertDatabaseMissing('module_progress', ['id' => $deletedProgress->id]);
        $this->assertDatabaseHas('training_modules', ['id' => $keptModule->id]);
        $this->assertDatabaseHas('module_progress', ['id' => $keptProgress->id]);
        Storage::disk('local')->assertMissing($deletedModule->file_path);
        Storage::disk('local')->assertExists($keptModule->file_path);
    }

    public function test_module_evaluation_records_explicit_module_provenance_for_safe_deletion(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['application' => $application] = $this->lmsTrainee($batch);
        [$unit, $outcome] = $this->customUnit('Provenance module unit');
        $module = $this->deletionModule($trainer, $batch, [
            'competency_category' => TrainingModule::CATEGORY_CUSTOM,
            'competency_unit_id' => $unit->id,
        ]);
        $submodule = $this->lmsSubmodule($module);
        $progress = TrainingSubmoduleProgress::create([
            'enrollment_application_id' => $application->id,
            'training_submodule_id' => $submodule->id,
            'status' => TrainingSubmoduleProgress::STATUS_COMPLETED,
            'progress_percent' => 100,
            'competency_outcome' => ModuleProgress::OUTCOME_COMPETENT,
            'evaluated_by_id' => $trainer->id,
            'evaluated_at' => now(),
            'completed_at' => now(),
        ]);

        app(ModuleSubmoduleService::class)->syncCompetencyOutcome(
            $application,
            $module,
            $submodule,
            $progress,
            $trainer,
        );

        $this->assertDatabaseHas('trainee_outcome_results', [
            'training_module_id' => $module->id,
            'competency_outcome_id' => $outcome->id,
        ]);
    }

    public function test_repeated_delete_is_a_not_found_and_never_a_partial_second_delete(): void
    {
        Storage::fake('local');
        $admin = $this->lmsUser('admin');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        $module = $this->deletionModule($trainer, $batch, ['file_path' => 'training-modules/repeat.pdf']);
        Storage::disk('local')->put($module->file_path, '%PDF repeat');

        $this->actingAs($admin)
            ->delete(route('admin.learning.modules.destroy', $module), ['confirmation' => 'DELETE'])
            ->assertRedirect(route('admin.learning.modules'));
        $this->actingAs($admin)
            ->delete(route('admin.learning.modules.destroy', $module), ['confirmation' => 'DELETE'])
            ->assertNotFound();
        $this->assertDatabaseCount('admin_activity_logs', 1);
    }

    private function deletionModule(User $trainer, TrainingBatch $batch, array $overrides = []): TrainingModule
    {
        return TrainingModule::create(array_merge([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'title' => 'Deletion test module',
            'description' => 'Module created for permanent deletion coverage.',
            'file_path' => 'training-modules/delete-test.pdf',
            'original_file_name' => 'delete-test.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'is_published' => false,
            'published_at' => null,
        ], $overrides));
    }

    /** @return array{0: CompetencyUnit, 1: CompetencyOutcome} */
    private function torIncludedUnit(string $title): array
    {
        $unit = CompetencyUnit::create([
            'program_code' => 'caregiving_nc_ii',
            'category' => TrainingModule::CATEGORY_CORE,
            'code' => 'TEST-'.Str::upper(Str::random(8)),
            'title' => $title,
            'sort_order' => 500,
            'is_required' => true,
            'is_tor_included' => true,
        ]);
        $outcome = $unit->outcomes()->create([
            'title' => $title.' outcome',
            'sort_order' => 1,
            'is_required' => true,
        ]);

        return [$unit, $outcome];
    }

    /** @return array{0: CompetencyUnit, 1: CompetencyOutcome} */
    private function customUnit(string $title): array
    {
        $unit = CompetencyUnit::create([
            'program_code' => 'caregiving_nc_ii',
            'category' => TrainingModule::CATEGORY_CUSTOM,
            'code' => 'CUSTOM-'.Str::upper(Str::random(8)),
            'title' => $title,
            'sort_order' => 500,
            'is_required' => false,
            'is_tor_included' => false,
        ]);
        $outcome = $unit->outcomes()->create([
            'title' => $title.' outcome',
            'sort_order' => 1,
            'is_required' => true,
        ]);

        return [$unit, $outcome];
    }

    /** @return array{0: CompetencyUnit, 1: CompetencyOutcome, 2: CompetencyOutcome} */
    private function customUnitWithTwoOutcomes(string $title): array
    {
        [$unit, $first] = $this->customUnit($title);
        $second = $unit->outcomes()->create([
            'title' => $title.' second outcome',
            'sort_order' => 2,
            'is_required' => true,
        ]);

        return [$unit, $first, $second];
    }

    private function officialDocument(
        EnrollmentApplication $application,
        string $type,
        string $status,
        string $suffix,
    ): OfficialDocument {
        return OfficialDocument::create([
            'enrollment_application_id' => $application->id,
            'training_batch_id' => $application->training_batch_id,
            'type' => $type,
            'version' => 1,
            'document_number' => 'MCARE-'.$suffix.'-'.Str::upper(Str::random(8)),
            'status' => $status,
            'storage_disk' => 'local',
        ]);
    }

    private function insertQueuedNotificationJob(User $notifiable, object $notification): int
    {
        $command = serialize(new SendQueuedNotifications($notifiable, $notification));
        $payload = json_encode([
            'uuid' => (string) Str::uuid(),
            'displayName' => SendQueuedNotifications::class,
            'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
            'data' => [
                'commandName' => SendQueuedNotifications::class,
                'command' => $command,
            ],
        ], JSON_THROW_ON_ERROR);

        return (int) DB::table('jobs')->insertGetId([
            'queue' => 'mail',
            'payload' => $payload,
            'attempts' => 0,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ]);
    }

    private function insertFailedNotificationJob(User $notifiable, object $notification): int
    {
        $command = serialize(new SendQueuedNotifications($notifiable, $notification));
        $payload = json_encode([
            'uuid' => (string) Str::uuid(),
            'displayName' => SendQueuedNotifications::class,
            'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
            'data' => [
                'commandName' => SendQueuedNotifications::class,
                'command' => $command,
            ],
        ], JSON_THROW_ON_ERROR);

        return (int) DB::table('failed_jobs')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'mail',
            'payload' => $payload,
            'exception' => 'test failure',
            'failed_at' => now(),
        ]);
    }
}

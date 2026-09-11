<?php

namespace Tests\Feature\Lms;

use App\Models\ModuleProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class RollingEnrollmentModuleReleaseTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    public function test_latest_continuous_batch_accepts_enrollment_without_a_deadline(): void
    {
        $this->lmsBatch([
            'name' => 'Older Continuous Batch',
            'training_starts_at' => now()->subMonth(),
            'is_continuous_enrollment' => true,
            'enrollment_ends_at' => null,
        ]);
        $latest = $this->lmsBatch([
            'name' => 'Latest Continuous Batch',
            'training_starts_at' => now()->subWeek(),
            'is_continuous_enrollment' => true,
            'enrollment_ends_at' => null,
        ]);

        $this->assertTrue($latest->acceptsEnrollment());
        $this->assertSame('continuous', $latest->enrollmentState());
        $this->assertTrue(TrainingBatch::openForEnrollment()?->is($latest));
    }

    public function test_late_enrollee_starts_with_current_module_and_defers_missed_modules(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch([
            'is_continuous_enrollment' => true,
            'enrollment_ends_at' => null,
        ]);
        ['user' => $existingUser, 'application' => $existingApplication] = $this->lmsTrainee($batch);

        $firstModule = $this->lmsModule($trainer, $batch, [
            'module_code' => 'CORE-001',
            'title' => 'Previously Released Core Module',
        ]);
        $firstSubmodule = $this->lmsSubmodule($firstModule);
        $this->lmsPassedAssessment($trainer, $firstModule, $existingApplication);

        $this->actingAs($trainer)
            ->post(route('trainer.modules.evaluate', $firstModule), [
                'training_submodule_id' => $firstSubmodule->id,
                'enrollment_application_id' => $existingApplication->id,
                'competency_outcome' => ModuleProgress::OUTCOME_COMPETENT,
            ])
            ->assertSessionHasNoErrors();

        $currentModule = $this->lmsModule($trainer, $batch, [
            'module_code' => 'CORE-002',
            'title' => 'Current Active Core Module',
        ]);

        $this->assertSame(TrainingModule::DELIVERY_CLOSED, $firstModule->fresh()->delivery_status);
        $this->assertSame(TrainingModule::DELIVERY_ACTIVE, $currentModule->fresh()->delivery_status);

        ['user' => $lateUser, 'application' => $lateApplication] = $this->lmsTrainee($batch);

        $this->assertDatabaseHas('module_progress', [
            'enrollment_application_id' => $lateApplication->id,
            'training_module_id' => $currentModule->id,
            'is_deferred' => 0,
            'status' => ModuleProgress::STATUS_NOT_STARTED,
        ]);
        $this->assertDatabaseHas('module_progress', [
            'enrollment_application_id' => $lateApplication->id,
            'training_module_id' => $firstModule->id,
            'is_deferred' => 1,
            'status' => ModuleProgress::STATUS_LOCKED,
        ]);

        $visibleIds = TrainingModule::query()
            ->availableTo($lateApplication)
            ->pluck('id');

        // Deferred (missed) modules stay out of the "available" list until the
        // trainee finishes their current path.
        $this->assertFalse($visibleIds->contains($firstModule->id));
        $this->assertTrue($visibleIds->contains($currentModule->id));

        $this->actingAs($lateUser)
            ->get(route('trainee.modules.show', $firstModule))
            ->assertRedirect(route('trainee.modules.index'))
            ->assertSessionHas('error');

        $this->actingAs($lateUser)
            ->get(route('trainee.modules.show', $currentModule))
            ->assertOk();

        $this->actingAs($lateUser)
            ->get(route('trainee.modules.index'))
            ->assertOk()
            ->assertSee('Catch-up')
            ->assertSee('Locked — CORE-002');
    }

    public function test_late_enrollee_can_take_missed_module_after_finishing_current_path(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch([
            'is_continuous_enrollment' => true,
            'enrollment_ends_at' => null,
        ]);
        ['user' => $existingUser, 'application' => $existingApplication] = $this->lmsTrainee($batch);

        $missedModule = $this->lmsModule($trainer, $batch, [
            'module_code' => 'CORE-001',
            'title' => 'Missed Module',
        ]);
        $missedSubmodule = $this->lmsSubmodule($missedModule);
        $this->lmsPassedAssessment($trainer, $missedModule, $existingApplication);

        $this->actingAs($trainer)
            ->post(route('trainer.modules.evaluate', $missedModule), [
                'training_submodule_id' => $missedSubmodule->id,
                'enrollment_application_id' => $existingApplication->id,
                'competency_outcome' => ModuleProgress::OUTCOME_COMPETENT,
            ])
            ->assertSessionHasNoErrors();

        $currentModule = $this->lmsModule($trainer, $batch, [
            'module_code' => 'CORE-002',
            'title' => 'Current Cohort Module',
        ]);
        $currentSubmodule = $this->lmsSubmodule($currentModule);

        ['user' => $lateUser, 'application' => $lateApplication] = $this->lmsTrainee($batch);

        // The missed module is deferred and stays locked while the current path is open.
        $missedProgress = $this->progressFor($lateApplication->id, $missedModule->id);
        $currentProgress = $this->progressFor($lateApplication->id, $currentModule->id);
        $this->assertTrue($missedProgress->is_deferred);
        $this->assertSame(ModuleProgress::STATUS_LOCKED, $missedProgress->status);
        $this->assertFalse($currentProgress->is_deferred);
        $this->assertNotNull($currentProgress->unlocked_at);

        $this->lmsPassedAssessment($trainer, $currentModule, $lateApplication);

        $this->actingAs($trainer)
            ->post(route('trainer.modules.evaluate', $currentModule), [
                'training_submodule_id' => $currentSubmodule->id,
                'enrollment_application_id' => $lateApplication->id,
                'competency_outcome' => ModuleProgress::OUTCOME_COMPETENT,
            ])
            ->assertSessionHasNoErrors();

        $missedProgress->refresh();
        $this->assertSame(ModuleProgress::STATUS_NOT_STARTED, $missedProgress->status);
        $this->assertNotNull($missedProgress->unlocked_at);
        $this->assertTrue($missedProgress->is_deferred);

        $this->actingAs($lateUser)
            ->get(route('trainee.modules.show', $missedModule))
            ->assertOk();
    }

    public function test_next_module_stays_locked_until_the_previous_module_is_competent(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch(['is_continuous_enrollment' => true]);
        ['user' => $trainee, 'application' => $application] = $this->lmsTrainee($batch);
        $laterModule = $this->lmsModule($trainer, $batch, [
            'module_code' => 'NCII-102',
            'title' => 'Later Code Published First',
        ]);
        $earlierModule = $this->lmsModule($trainer, $batch, [
            'module_code' => 'NCII-101',
            'title' => 'Earlier Code Published Second',
        ]);
        $earlierSubmodule = $this->lmsSubmodule($earlierModule);
        $this->lmsPassedAssessment($trainer, $earlierModule, $application);

        $this->assertSame(
            ModuleProgress::STATUS_NOT_STARTED,
            $this->progressFor($application->id, $earlierModule->id)->status,
        );
        $this->assertNotNull($this->progressFor($application->id, $earlierModule->id)->unlocked_at);
        $this->assertSame(
            ModuleProgress::STATUS_LOCKED,
            $this->progressFor($application->id, $laterModule->id)->status,
        );
        $this->assertNull($this->progressFor($application->id, $laterModule->id)->unlocked_at);

        $this->actingAs($trainee)
            ->get(route('trainee.modules.index'))
            ->assertOk()
            ->assertSee('Earlier Code Published Second')
            ->assertSee('Later Code Published First')
            ->assertSee('Locked — NCII-101')
            ->assertSee('Open')
            ->assertSee('Locked')
            ->assertSee('aria-valuenow="0"', false)
            ->assertDontSee('width: 10%', false);

        $this->actingAs($trainee)
            ->get(route('trainee.modules.show', $earlierModule))
            ->assertOk();
        $this->actingAs($trainee)
            ->get(route('trainee.modules.show', $laterModule))
            ->assertRedirect(route('trainee.modules.index'))
            ->assertSessionHas('error');
        $this->actingAs($trainee)
            ->get(route('trainee.modules.content', $laterModule))
            ->assertForbidden();

        $this->actingAs($trainer)
            ->post(route('trainer.modules.evaluate', $earlierModule), [
                'training_submodule_id' => $earlierSubmodule->id,
                'enrollment_application_id' => $application->id,
                'competency_outcome' => ModuleProgress::OUTCOME_COMPETENT,
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue($this->progressFor($application->id, $earlierModule->id)->isTrainerValidated());
        $this->assertSame(
            ModuleProgress::STATUS_NOT_STARTED,
            $this->progressFor($application->id, $laterModule->id)->status,
        );
        $this->assertNotNull($this->progressFor($application->id, $laterModule->id)->unlocked_at);

        $this->actingAs($trainee)
            ->get(route('trainee.modules.show', $laterModule))
            ->assertOk()
            ->assertSee('Later Code Published First');
    }

    public function test_new_module_stays_locked_when_previous_module_needs_remediation(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch(['is_continuous_enrollment' => true]);
        ['user' => $trainee, 'application' => $application] = $this->lmsTrainee($batch);
        $firstModule = $this->lmsModule($trainer, $batch, [
            'module_code' => 'CORE-001',
            'title' => 'First Assigned Module',
        ]);
        $firstSubmodule = $this->lmsSubmodule($firstModule);
        $this->lmsPassedAssessment($trainer, $firstModule, $application);
        $nextModule = $this->lmsModule($trainer, $batch, [
            'module_code' => 'CORE-002',
            'title' => 'Locked Next Module',
        ]);

        $nextProgress = ModuleProgress::query()
            ->where('enrollment_application_id', $application->id)
            ->where('training_module_id', $nextModule->id)
            ->firstOrFail();
        $this->assertSame(ModuleProgress::STATUS_LOCKED, $nextProgress->status);
        $this->assertNull($nextProgress->unlocked_at);
        $this->actingAs($trainee)
            ->get(route('trainee.modules.show', $nextModule))
            ->assertRedirect(route('trainee.modules.index'));

        $this->actingAs($trainee)
            ->get(route('trainee.modules.show', $firstModule))
            ->assertOk()
            ->assertSee('Awaiting trainer evaluation.')
            ->assertDontSee('Mark Submodule as Done');

        $this->actingAs($trainer)
            ->post(route('trainer.modules.evaluate', $firstModule), [
                'training_submodule_id' => $firstSubmodule->id,
                'enrollment_application_id' => $application->id,
                'competency_outcome' => ModuleProgress::OUTCOME_NOT_YET_COMPETENT,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            ModuleProgress::STATUS_NEEDS_REMEDIATION,
            $this->progressFor($application->id, $firstModule->id)->status,
        );
        $this->assertNull($this->progressFor($application->id, $firstModule->id)->submitted_at);
        $nextProgress->refresh();
        $this->assertSame(ModuleProgress::STATUS_LOCKED, $nextProgress->status);
        $this->assertNull($nextProgress->unlocked_at);

        $visibleModuleIds = TrainingModule::query()
            ->assignedTo($application)
            ->pluck('id');
        $unlockedModuleIds = TrainingModule::query()
            ->availableTo($application)
            ->pluck('id');

        $this->assertTrue($visibleModuleIds->contains($firstModule->id));
        $this->assertTrue($visibleModuleIds->contains($nextModule->id));
        $this->assertTrue($unlockedModuleIds->contains($firstModule->id));
        $this->assertFalse($unlockedModuleIds->contains($nextModule->id));
        $this->actingAs($trainee)
            ->get(route('trainee.modules.show', $firstModule))
            ->assertOk();
        $this->actingAs($trainee)
            ->get(route('trainee.modules.show', $nextModule))
            ->assertRedirect(route('trainee.modules.index'));
    }

    public function test_trainee_cannot_mark_a_submodule_as_done(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $trainee, 'application' => $application] = $this->lmsTrainee($batch);
        $module = $this->lmsModule($trainer, $batch);
        $submodule = $this->lmsSubmodule($module);
        Quiz::create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $batch->id,
            'training_module_id' => $module->id,
            'training_submodule_id' => $submodule->id,
            'title' => 'Required Module Check',
            'instructions' => 'Optional classwork. The trainer records the face-to-face grade.',
            'is_published' => true,
            'published_at' => now(),
            'attempt_limit' => 3,
            'passing_score_percent' => 75,
        ]);

        $this->actingAs($trainee)
            ->get(route('trainee.modules.show', $module))
            ->assertOk()
            ->assertDontSee('Mark Submodule as Done')
            ->assertSee('Awaiting trainer evaluation.');

        $this->actingAs($trainee)
            ->patch(route('trainee.modules.submodules.progress', [$module, $submodule]), ['action' => 'submit'])
            ->assertSessionHasErrors('action');

        $progress = $this->progressFor($application->id, $module->id);
        $this->assertNotSame(ModuleProgress::STATUS_AWAITING_EVALUATION, $progress->status);
        $this->assertNull($progress->submitted_at);
        $this->assertNull($progress->completed_at);
    }

    private function progressFor(int $applicationId, int $moduleId): ModuleProgress
    {
        return ModuleProgress::query()
            ->where('enrollment_application_id', $applicationId)
            ->where('training_module_id', $moduleId)
            ->firstOrFail();
    }
}

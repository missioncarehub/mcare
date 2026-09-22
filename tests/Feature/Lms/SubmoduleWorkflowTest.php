<?php

namespace Tests\Feature\Lms;

use App\Models\EnrollmentApplication;
use App\Models\ModuleProgress;
use App\Models\TrainingModule;
use App\Models\TrainingSubmoduleProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class SubmoduleWorkflowTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    public function test_custom_module_is_batchwide_supplemental_and_does_not_replace_the_active_module(): void
    {
        Storage::fake('local');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch(['trainer_id' => $trainer->id]);
        $otherBatch = $this->lmsBatch(['name' => 'Caregiving Batch B']);
        ['application' => $existing] = $this->lmsTrainee($batch);
        ['application' => $graduate] = $this->lmsTrainee($batch, ['first_name' => 'Graduate']);
        $graduate->forceFill(['learning_status' => EnrollmentApplication::LEARNING_GRADUATED])->save();
        ['application' => $otherBatchTrainee] = $this->lmsTrainee($otherBatch);

        $activeModule = $this->lmsModule($trainer, $batch, [
            'title' => 'Current Required Competency',
            'module_code' => 'CORE-ACTIVE',
            'competency_category' => TrainingModule::CATEGORY_CORE,
        ]);

        $this->actingAs($trainer)
            ->post(route('trainer.modules.store'), [
                'audience_type' => 'batch',
                'training_batch_id' => $batch->id,
                'module_code' => 'MCARE-CUSTOM-COMMS',
                'competency_category' => TrainingModule::CATEGORY_CUSTOM,
                'completion_mode' => TrainingModule::COMPLETION_ASSESSED,
                'title' => 'Supplemental Client Communication Practice',
                'description' => 'An assessed custom activity for the trainer assigned class.',
                'submodule_titles' => [
                    'Prepare a clear client update',
                    'Deliver and document the client update',
                ],
                'module_file' => UploadedFile::fake()->create('client-communication.pdf', 100, 'application/pdf'),
                'is_published' => 1,
            ])
            ->assertRedirect(route('trainer.resources'))
            ->assertSessionHasNoErrors();

        $custom = TrainingModule::query()
            ->where('title', 'Supplemental Client Communication Practice')
            ->with(['submodules', 'competencyUnit.outcomes'])
            ->firstOrFail();

        $this->assertSame(TrainingModule::RELEASE_SUPPLEMENTAL, $custom->release_mode);
        $this->assertSame(TrainingModule::DELIVERY_AVAILABLE, $custom->delivery_status);
        $this->assertSame(TrainingModule::DELIVERY_ACTIVE, $activeModule->fresh()->delivery_status);
        $this->assertCount(2, $custom->submodules);
        $this->assertFalse($custom->competencyUnit->is_required);
        $this->assertFalse($custom->competencyUnit->is_tor_included);
        $this->assertDatabaseHas('module_progress', [
            'enrollment_application_id' => $existing->id,
            'training_module_id' => $custom->id,
        ]);
        $this->assertDatabaseMissing('module_progress', [
            'enrollment_application_id' => $graduate->id,
            'training_module_id' => $custom->id,
        ]);
        $this->assertDatabaseMissing('module_progress', [
            'enrollment_application_id' => $otherBatchTrainee->id,
            'training_module_id' => $custom->id,
        ]);

        $this->actingAs($trainer)
            ->patch(route('trainer.modules.update', $custom), [
                'audience_type' => 'batch',
                'training_batch_id' => $batch->id,
                'competency_category' => TrainingModule::CATEGORY_CORE,
                'completion_mode' => TrainingModule::COMPLETION_ASSESSED,
                'title' => $custom->title,
                'description' => $custom->description,
                'is_published' => 1,
            ])
            ->assertSessionHasErrors('competency_category');
        $this->assertSame(TrainingModule::RELEASE_SUPPLEMENTAL, $custom->fresh()->release_mode);

        ['application' => $lateTrainee] = $this->lmsTrainee($batch, ['first_name' => 'Late']);
        $this->assertDatabaseHas('module_progress', [
            'enrollment_application_id' => $lateTrainee->id,
            'training_module_id' => $activeModule->id,
        ]);
        $this->assertDatabaseHas('module_progress', [
            'enrollment_application_id' => $lateTrainee->id,
            'training_module_id' => $custom->id,
        ]);

        $this->actingAs($trainer)
            ->get(route('trainer.competencies.index', ['batch_id' => $batch->id]))
            ->assertOk()
            ->assertSee('Supplemental Client Communication Practice')
            ->assertSee('Institutional / Custom competencies');
    }

    public function test_official_outcomes_become_submodules_and_only_children_can_be_completed(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch(['trainer_id' => $trainer->id]);
        ['user' => $trainee, 'application' => $application] = $this->lmsTrainee($batch);
        $module = $this->lmsModule($trainer, $batch, [
            'module_code' => '500311105',
            'competency_category' => TrainingModule::CATEGORY_BASIC,
            'title' => 'Participate in Workplace Communication',
        ])->fresh(['competencyUnit.outcomes', 'submodules']);

        $this->assertNotNull($module->competencyUnit);
        $this->assertGreaterThan(1, $module->competencyUnit->outcomes->count());
        $this->assertCount($module->competencyUnit->outcomes->count(), $module->submodules);
        $this->assertSame(
            $module->competencyUnit->outcomes->pluck('title')->all(),
            $module->submodules->pluck('title')->all(),
        );

        $this->actingAs($trainee)
            ->patch(route('trainee.modules.progress', $module), ['action' => 'submit'])
            ->assertRedirect(route('trainee.modules.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(
            ModuleProgress::STATUS_AWAITING_EVALUATION,
            $this->parentProgress($application->id, $module->id)->status,
        );

        foreach ($module->submodules as $index => $submodule) {
            $this->actingAs($trainer)
                ->post(route('trainer.modules.evaluate', $module), [
                    'training_submodule_id' => $submodule->id,
                    'enrollment_application_id' => $application->id,
                    'practical_rating' => ModuleProgress::RATING_COMPETENT,
                    'competency_outcome' => ModuleProgress::OUTCOME_COMPETENT,
                ])
                ->assertSessionHasNoErrors();

            if ($index < $module->submodules->count() - 1) {
                $this->assertNotSame(
                    ModuleProgress::STATUS_COMPLETED,
                    $this->parentProgress($application->id, $module->id)->status,
                );
            }
        }

        $parent = $this->parentProgress($application->id, $module->id);
        $this->assertSame(ModuleProgress::STATUS_COMPLETED, $parent->status);
        $this->assertSame(ModuleProgress::OUTCOME_COMPETENT, $parent->competency_outcome);
        $this->assertSame(100, $parent->progress_percent);
        $this->assertSame(
            $module->submodules->count(),
            TrainingSubmoduleProgress::query()
                ->where('enrollment_application_id', $application->id)
                ->whereIn('training_submodule_id', $module->submodules->pluck('id'))
                ->where('status', TrainingSubmoduleProgress::STATUS_COMPLETED)
                ->count(),
        );
        $this->assertDatabaseHas('trainee_competency_records', [
            'enrollment_application_id' => $application->id,
            'competency_unit_id' => $module->competency_unit_id,
            'status' => 'competent',
        ]);
    }

    private function parentProgress(int $applicationId, int $moduleId): ModuleProgress
    {
        return ModuleProgress::query()->where([
            'enrollment_application_id' => $applicationId,
            'training_module_id' => $moduleId,
        ])->firstOrFail();
    }
}

<?php

namespace Tests\Feature\Lms;

use App\Models\CompetencyUnit;
use App\Models\ModuleProgress;
use App\Models\Quiz;
use App\Models\TraineeCompetencyRecord;
use App\Models\TrainingModule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class ModuleManagementTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    public function test_owner_can_edit_metadata_and_replace_then_delete_a_private_module_file(): void
    {
        Storage::fake('local');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        $module = $this->lmsModule($trainer, $batch);
        Storage::disk('local')->put($module->file_path, '%PDF old lesson');
        $originalPath = $module->file_path;

        $basePayload = [
            'audience_type' => 'batch',
            'training_batch_id' => $batch->id,
            'title' => 'Updated patient transfer',
            'description' => 'Updated lesson description.',
            'topic' => 'Mobility support',
            'available_at' => now()->subHour()->toDateTimeString(),
            'due_at' => now()->addWeek()->toDateTimeString(),
            'position' => 2,
            'is_published' => '1',
        ];

        $this->actingAs($trainer)
            ->patch(route('trainer.modules.update', $module), $basePayload)
            ->assertRedirect(route('trainer.resources'))
            ->assertSessionHas('saved');

        $module->refresh();
        $this->assertSame($originalPath, $module->file_path);
        Storage::disk('local')->assertExists($originalPath);

        $this->actingAs($trainer)
            ->patch(route('trainer.modules.update', $module), [
                ...$basePayload,
                'module_file' => UploadedFile::fake()
                    ->create('updated-transfer.pdf', 80, 'application/pdf'),
            ])
            ->assertRedirect(route('trainer.resources'));

        $module->refresh();
        $this->assertNotSame($originalPath, $module->file_path);
        $this->assertSame('updated-transfer.pdf', $module->original_file_name);
        Storage::disk('local')->assertMissing($originalPath);
        Storage::disk('local')->assertExists($module->file_path);
        $replacementPath = $module->file_path;

        $this->actingAs($trainer)
            ->delete(route('trainer.modules.destroy', $module))
            ->assertRedirect(route('trainer.resources'));

        $this->assertDatabaseMissing('training_modules', ['id' => $module->id]);
        Storage::disk('local')->assertMissing($replacementPath);
    }

    public function test_trainer_cannot_update_or_delete_another_trainers_module(): void
    {
        Storage::fake('local');
        $owner = $this->lmsUser('trainer');
        $otherTrainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        $module = $this->lmsModule($owner, $batch);
        Storage::disk('local')->put($module->file_path, '%PDF owner lesson');

        $this->actingAs($otherTrainer)
            ->patch(route('trainer.modules.update', $module), [
                'audience_type' => 'batch',
                'training_batch_id' => $batch->id,
                'title' => 'Unauthorized title',
                'description' => 'This must not be saved.',
                'is_published' => '1',
            ])
            ->assertForbidden();

        $this->actingAs($otherTrainer)
            ->delete(route('trainer.modules.destroy', $module))
            ->assertForbidden();

        $this->assertDatabaseHas('training_modules', [
            'id' => $module->id,
            'title' => 'Safe Patient Transfer',
        ]);
        Storage::disk('local')->assertExists($module->file_path);
    }

    public function test_invalid_replacement_does_not_change_or_remove_the_existing_module_file(): void
    {
        Storage::fake('local');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        $module = $this->lmsModule($trainer, $batch);
        Storage::disk('local')->put($module->file_path, '%PDF original lesson');
        $originalPath = $module->file_path;

        $this->actingAs($trainer)
            ->from(route('trainer.resources'))
            ->patch(route('trainer.modules.update', $module), [
                'audience_type' => 'batch',
                'training_batch_id' => $batch->id,
                'title' => 'Rejected replacement',
                'description' => 'The executable upload must be rejected.',
                'module_file' => UploadedFile::fake()
                    ->create('unsafe.exe', 32, 'application/octet-stream'),
                'is_published' => '1',
            ])
            ->assertRedirect(route('trainer.resources'))
            ->assertSessionHasErrors('module_file');

        $module->refresh();
        $this->assertSame('Safe Patient Transfer', $module->title);
        $this->assertSame($originalPath, $module->file_path);
        Storage::disk('local')->assertExists($originalPath);
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    public function test_draft_module_is_not_listed_or_openable_by_a_trainee(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $trainee] = $this->lmsTrainee($batch);
        $module = $this->lmsModule($trainer, $batch, [
            'title' => 'Trainer draft module',
            'is_published' => false,
            'published_at' => null,
        ]);

        $this->actingAs($trainee)
            ->get(route('trainee.modules.index'))
            ->assertOk()
            ->assertDontSee('Trainer draft module');

        $this->actingAs($trainee)
            ->get(route('trainee.modules.show', $module))
            ->assertNotFound();
    }

    public function test_direct_content_access_creates_and_touches_module_progress(): void
    {
        Storage::fake('local');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $trainee, 'application' => $application] = $this->lmsTrainee($batch);
        $module = $this->lmsModule($trainer, $batch);
        Storage::disk('local')->put($module->file_path, '%PDF protected lesson');

        $this->actingAs($trainee)
            ->get(route('trainee.modules.content', $module))
            ->assertOk();

        $progress = ModuleProgress::query()
            ->where('enrollment_application_id', $application->id)
            ->where('training_module_id', $module->id)
            ->firstOrFail();

        $this->assertSame(ModuleProgress::STATUS_IN_PROGRESS, $progress->status);
        $this->assertSame(0, $progress->progress_percent);
        $this->assertNotNull($progress->first_opened_at);
        $this->assertNotNull($progress->last_viewed_at);
    }

    public function test_trainer_can_create_module_with_supplementary_attachments_and_nominal_hours(): void
    {
        Storage::fake('local');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();

        $response = $this->actingAs($trainer)
            ->post(route('trainer.modules.store'), [
                'audience_type' => 'batch',
                'training_batch_id' => $batch->id,
                'module_code' => 'HCS323301',
                'competency_category' => 'core',
                'title' => 'Provide Care and Support to Infants and Toddlers',
                'topic' => 'Comfort infants and toddlers',
                'estimated_hours' => 40,
                'description' => 'Comprehensive infant care module with worksheets.',
                'position' => 1,
                'is_published' => '1',
                'module_file' => UploadedFile::fake()->create('core1-infant-care.pdf', 100, 'application/pdf'),
                'supplementary_files' => [
                    UploadedFile::fake()->create('infant-feeding-rubric.pdf', 50, 'application/pdf'),
                    UploadedFile::fake()->create('bathing-worksheet.png', 30, 'image/png'),
                ],
            ]);

        $response->assertRedirect(route('trainer.resources'));
        $this->assertDatabaseHas('training_modules', [
            'module_code' => 'HCS323301',
            'competency_category' => 'core',
            'estimated_hours' => 40,
            'title' => 'Provide Care and Support to Infants and Toddlers',
        ]);

        $module = TrainingModule::where('module_code', 'HCS323301')->firstOrFail();
        $this->assertCount(2, $module->supplementaryList());
    }

    public function test_trainer_can_create_quiz_inside_module_hub(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        $module = $this->lmsModule($trainer, $batch, ['module_code' => 'HCS323301']);
        $submodule = $this->lmsSubmodule($module);

        $response = $this->actingAs($trainer)
            ->post(route('trainer.modules.quizzes.store', $module), [
                'training_submodule_id' => $submodule->id,
                'title' => 'Infant Care Mastery Assessment',
                'instructions' => 'Complete all 10 questions.',
                'time_limit_minutes' => 25,
                'passing_score_percent' => 75,
                'is_published' => 1,
            ]);

        $quiz = Quiz::where('title', 'Infant Care Mastery Assessment')->firstOrFail();
        $this->assertSame($module->id, $quiz->training_module_id);
        $this->assertSame($submodule->id, $quiz->training_submodule_id);
        $this->assertFalse($quiz->is_published);
        $this->assertNull($quiz->published_at);
        $response->assertRedirect(route('trainer.quizzes.edit', $quiz));
    }

    public function test_trainer_can_evaluate_trainee_competency_inside_module_hub_and_syncs_tor(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $trainee, 'application' => $application] = $this->lmsTrainee($batch);

        // Ensure competency unit exists for TOR sync
        CompetencyUnit::firstOrCreate(
            ['code' => 'HCS323301'],
            ['title' => 'Provide care and support to infants/toddlers', 'category' => 'core', 'nominal_hours' => 40, 'position' => 1]
        );

        $module = $this->lmsModule($trainer, $batch, ['module_code' => 'HCS323301']);
        $this->lmsPassedAssessment($trainer, $module, $application, 92.5);

        foreach ($module->submodules()->get() as $submodule) {
            $response = $this->actingAs($trainer)
                ->post(route('trainer.modules.evaluate', $module), [
                    'training_submodule_id' => $submodule->id,
                    'enrollment_application_id' => $application->id,
                    'practical_rating' => 'competent',
                    'competency_outcome' => 'competent',
                    'evaluation_remarks' => 'Excellent demonstration of sterile diaper changing and infant feeding.',
                ]);
        }

        $response->assertRedirect(route('trainer.modules.show', ['module' => $module, 'tab' => 'evaluations']).'#evaluations');

        $progress = ModuleProgress::where('enrollment_application_id', $application->id)
            ->where('training_module_id', $module->id)
            ->firstOrFail();

        $this->assertEquals(92.5, (float) $progress->quiz_score);
        $this->assertSame('competent', $progress->practical_rating);
        $this->assertSame('competent', $progress->competency_outcome);
        $this->assertSame(100, $progress->progress_percent);
        $this->assertSame(ModuleProgress::STATUS_COMPLETED, $progress->status);

        // Check TOR record auto-synced
        $this->assertDatabaseHas('trainee_competency_records', [
            'enrollment_application_id' => $application->id,
            'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
        ]);
    }

    public function test_competency_board_grade_updates_trainee_classwork_status(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch(['trainer_id' => $trainer->id]);
        ['user' => $trainee, 'application' => $application] = $this->lmsTrainee($batch);
        $module = $this->lmsModule($trainer, $batch, [
            'module_code' => 'HCS323301',
            'title' => 'Provide Care and Support to Infants and Toddlers',
        ])->fresh(['competencyUnit.outcomes', 'submodules']);
        $unit = $module->competencyUnit;

        $this->assertNotNull($unit);
        $this->assertGreaterThan(1, $module->submodules->count());

        $this->actingAs($trainee)
            ->get(route('trainee.modules.show', $module))
            ->assertOk()
            ->assertSee('Awaiting trainer evaluation.')
            ->assertSee('Pending Evaluation')
            ->assertDontSee('Mark Submodule as Done');

        $this->actingAs($trainer)
            ->patch(route('trainer.competencies.update', $application), [
                'records' => [
                    $unit->id => [
                        'unit_id' => $unit->id,
                        'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
                        'percentage_score' => 80,
                        'notes' => 'Board evaluation recorded.',
                        'outcomes' => $unit->outcomes->mapWithKeys(
                            fn ($outcome) => [$outcome->id => TraineeCompetencyRecord::STATUS_COMPETENT]
                        )->all(),
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        $progress = ModuleProgress::query()
            ->where('enrollment_application_id', $application->id)
            ->where('training_module_id', $module->id)
            ->firstOrFail();

        $this->assertTrue($progress->isTrainerValidated());
        $this->assertSame(ModuleProgress::RATING_COMPETENT, $progress->practical_rating);
        $this->assertSame(ModuleProgress::OUTCOME_COMPETENT, $progress->competency_outcome);

        $this->actingAs($trainee)
            ->get(route('trainee.modules.show', $module))
            ->assertOk()
            ->assertDontSee('Pending Evaluation')
            ->assertDontSee('Awaiting trainer evaluation')
            ->assertSee('Competent (C)')
            ->assertSee('Competent (Passed)');
    }

    public function test_competency_board_nyc_locks_next_module_and_later_submodules(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch(['trainer_id' => $trainer->id]);
        ['user' => $trainee, 'application' => $application] = $this->lmsTrainee($batch);
        $first = $this->lmsModule($trainer, $batch, [
            'module_code' => 'HCS323301',
            'title' => 'Provide Care and Support to Infants and Toddlers',
        ])->fresh(['competencyUnit.outcomes', 'submodules']);
        $next = $this->lmsModule($trainer, $batch, [
            'module_code' => 'HCS323304',
            'title' => 'Foster Physical Development of Children',
        ]);
        $unit = $first->competencyUnit;
        $firstOutcome = $unit->outcomes->sortBy('sort_order')->first();
        $firstSubmodule = $first->submodules
            ->firstWhere('competency_outcome_id', $firstOutcome->id);
        $laterSubmodule = $first->submodules
            ->sortBy('position')
            ->first(fn ($submodule) => (int) $submodule->id !== (int) $firstSubmodule->id);

        $this->assertNotNull($firstSubmodule);
        $this->assertNotNull($laterSubmodule);

        $this->actingAs($trainer)
            ->patch(route('trainer.competencies.update', $application), [
                'records' => [
                    $unit->id => [
                        'unit_id' => $unit->id,
                        'status' => TraineeCompetencyRecord::STATUS_NOT_YET_COMPETENT,
                        'notes' => 'Needs remediation on the first outcome.',
                        'outcomes' => $unit->outcomes->mapWithKeys(
                            fn ($outcome) => [
                                $outcome->id => (int) $outcome->id === (int) $firstOutcome->id
                                    ? TraineeCompetencyRecord::STATUS_NOT_YET_COMPETENT
                                    : TraineeCompetencyRecord::STATUS_NOT_ASSESSED,
                            ]
                        )->all(),
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        $firstProgress = ModuleProgress::query()
            ->where('enrollment_application_id', $application->id)
            ->where('training_module_id', $first->id)
            ->firstOrFail();
        $nextProgress = ModuleProgress::query()
            ->where('enrollment_application_id', $application->id)
            ->where('training_module_id', $next->id)
            ->firstOrFail();

        $this->assertTrue($firstProgress->needsRemediation());
        $this->assertFalse($firstProgress->isTrainerValidated());
        $this->assertSame(ModuleProgress::STATUS_LOCKED, $nextProgress->status);
        $this->assertNull($nextProgress->unlocked_at);

        $this->actingAs($trainee)
            ->get(route('trainee.modules.index'))
            ->assertOk()
            ->assertSee('Locked — finish HCS323301')
            ->assertSee('Needs remediation');

        $this->actingAs($trainee)
            ->get(route('trainee.modules.show', $next))
            ->assertRedirect(route('trainee.modules.index'));

        $this->actingAs($trainee)
            ->get(route('trainee.modules.show', $first))
            ->assertOk()
            ->assertSee('Not yet competent')
            ->assertSee($laterSubmodule->title)
            ->assertSee('Locked until '.$firstSubmodule->title.' is Competent');

        $this->actingAs($trainee)
            ->patch(route('trainee.modules.submodules.progress', [$first, $laterSubmodule]), [
                'action' => 'submit',
            ])
            ->assertSessionHasErrors('action');

        $this->actingAs($trainee)
            ->patch(route('trainee.modules.submodules.progress', [$first, $firstSubmodule]), [
                'action' => 'submit',
            ])
            ->assertSessionHasErrors('action');
    }

    public function test_competency_board_nyc_relocks_an_already_unlocked_next_module(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch(['trainer_id' => $trainer->id]);
        ['user' => $trainee, 'application' => $application] = $this->lmsTrainee($batch);
        $first = $this->lmsModule($trainer, $batch, [
            'module_code' => 'HCS323301',
            'title' => 'Provide Care and Support to Infants and Toddlers',
        ])->fresh(['competencyUnit.outcomes', 'submodules']);
        $next = $this->lmsModule($trainer, $batch, [
            'module_code' => 'HCS323304',
            'title' => 'Foster Physical Development of Children',
        ]);
        $unit = $first->competencyUnit;

        $this->actingAs($trainer)
            ->patch(route('trainer.competencies.update', $application), [
                'records' => [
                    $unit->id => [
                        'unit_id' => $unit->id,
                        'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
                        'percentage_score' => 80,
                        'notes' => 'Initially competent.',
                        'outcomes' => $unit->outcomes->mapWithKeys(
                            fn ($outcome) => [$outcome->id => TraineeCompetencyRecord::STATUS_COMPETENT]
                        )->all(),
                    ],
                ],
            ])
            ->assertSessionHas('saved');

        $this->assertTrue(
            ModuleProgress::query()
                ->where('enrollment_application_id', $application->id)
                ->where('training_module_id', $first->id)
                ->firstOrFail()
                ->isTrainerValidated()
        );
        $this->assertNotNull(
            ModuleProgress::query()
                ->where('enrollment_application_id', $application->id)
                ->where('training_module_id', $next->id)
                ->firstOrFail()
                ->unlocked_at
        );

        $this->actingAs($trainee)
            ->get(route('trainee.modules.show', $next))
            ->assertOk();

        $firstOutcome = $unit->outcomes->sortBy('sort_order')->first();
        $this->actingAs($trainer)
            ->patch(route('trainer.competencies.update', $application), [
                'records' => [
                    $unit->id => [
                        'unit_id' => $unit->id,
                        'status' => TraineeCompetencyRecord::STATUS_NOT_YET_COMPETENT,
                        'notes' => 'Reassessed as NYC.',
                        'outcomes' => $unit->outcomes->mapWithKeys(
                            fn ($outcome) => [
                                $outcome->id => (int) $outcome->id === (int) $firstOutcome->id
                                    ? TraineeCompetencyRecord::STATUS_NOT_YET_COMPETENT
                                    : TraineeCompetencyRecord::STATUS_COMPETENT,
                            ]
                        )->all(),
                    ],
                ],
            ])
            ->assertSessionHas('saved');

        $nextProgress = ModuleProgress::query()
            ->where('enrollment_application_id', $application->id)
            ->where('training_module_id', $next->id)
            ->firstOrFail();

        $this->assertTrue(
            ModuleProgress::query()
                ->where('enrollment_application_id', $application->id)
                ->where('training_module_id', $first->id)
                ->firstOrFail()
                ->needsRemediation()
        );
        $this->assertSame(ModuleProgress::STATUS_LOCKED, $nextProgress->status);
        $this->assertNull($nextProgress->unlocked_at);

        $this->actingAs($trainee)
            ->get(route('trainee.modules.show', $next))
            ->assertRedirect(route('trainee.modules.index'));
    }

    public function test_trainee_can_download_supplementary_attachments(): void
    {
        Storage::fake('local');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $trainee] = $this->lmsTrainee($batch);

        $filePath = "training-modules/{$trainer->id}/supplementary/worksheet.pdf";
        Storage::disk('local')->put($filePath, '%PDF supplementary');

        $module = $this->lmsModule($trainer, $batch, [
            'supplementary_files' => [
                [
                    'file_path' => $filePath,
                    'original_name' => 'infant-care-worksheet.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size' => 1024,
                    'human_size' => '1 KB',
                ],
            ],
        ]);

        $this->actingAs($trainee)
            ->get(route('trainee.modules.supplementary.download', [$module, 0]))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=infant-care-worksheet.pdf');
    }

    public function test_trainer_can_remove_a_supplementary_attachment_without_deleting_the_module(): void
    {
        Storage::fake('local');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        $firstPath = "training-modules/{$trainer->id}/supplementary/first.pdf";
        $secondPath = "training-modules/{$trainer->id}/supplementary/second.pdf";
        Storage::disk('local')->put($firstPath, '%PDF first');
        Storage::disk('local')->put($secondPath, '%PDF second');

        $module = $this->lmsModule($trainer, $batch, [
            'supplementary_files' => [
                ['file_path' => $firstPath, 'original_name' => 'first.pdf'],
                ['file_path' => $secondPath, 'original_name' => 'second.pdf'],
            ],
        ]);

        $this->actingAs($trainer)
            ->delete(route('trainer.modules.supplementary.destroy', [$module, 0]))
            ->assertRedirect(route('trainer.modules.show', $module))
            ->assertSessionHas('saved');

        $module->refresh();
        $this->assertSame('second.pdf', $module->supplementaryList()[0]['original_name']);
        Storage::disk('local')->assertMissing($firstPath);
        Storage::disk('local')->assertExists($secondPath);
    }

    public function test_module_rejects_more_than_ten_supplementary_files_before_storing_anything(): void
    {
        Storage::fake('local');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        $files = collect(range(1, 11))
            ->map(fn (int $index) => UploadedFile::fake()->create("handout-{$index}.pdf", 10, 'application/pdf'))
            ->all();

        $this->actingAs($trainer)
            ->post(route('trainer.modules.store'), [
                'audience_type' => 'batch',
                'training_batch_id' => $batch->id,
                'title' => 'Too many handouts',
                'description' => 'This request must fail before storage.',
                'module_file' => UploadedFile::fake()->create('primary.pdf', 10, 'application/pdf'),
                'supplementary_files' => $files,
            ])
            ->assertSessionHasErrors('supplementary_files');

        $this->assertDatabaseMissing('training_modules', ['title' => 'Too many handouts']);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_private_module_cannot_evaluate_another_trainee_and_downgrade_clears_completion(): void
    {
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $assignedUser, 'application' => $assigned] = $this->lmsTrainee($batch);
        ['application' => $other] = $this->lmsTrainee($batch);
        $module = $this->lmsModule($trainer, $batch, [
            'target_enrollment_application_id' => $assigned->id,
        ]);
        $submodule = $this->lmsSubmodule($module);
        $this->lmsPassedAssessment($trainer, $module, $assigned);

        $this->actingAs($trainer)
            ->post(route('trainer.modules.evaluate', $module), [
                'training_submodule_id' => $submodule->id,
                'enrollment_application_id' => $other->id,
                'competency_outcome' => ModuleProgress::OUTCOME_COMPETENT,
            ])
            ->assertSessionHasErrors('enrollment_application_id');

        $this->actingAs($trainer)
            ->post(route('trainer.modules.evaluate', $module), [
                'training_submodule_id' => $submodule->id,
                'enrollment_application_id' => $assigned->id,
                'competency_outcome' => ModuleProgress::OUTCOME_COMPETENT,
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($trainer)
            ->post(route('trainer.modules.evaluate', $module), [
                'training_submodule_id' => $submodule->id,
                'enrollment_application_id' => $assigned->id,
                'competency_outcome' => ModuleProgress::OUTCOME_NOT_YET_COMPETENT,
            ])
            ->assertSessionHasNoErrors();

        $progress = ModuleProgress::query()
            ->where('enrollment_application_id', $assigned->id)
            ->where('training_module_id', $module->id)
            ->firstOrFail();
        $this->assertSame(ModuleProgress::STATUS_NEEDS_REMEDIATION, $progress->status);
        $this->assertSame(99, $progress->progress_percent);
        $this->assertNull($progress->completed_at);
    }
}

<?php

namespace Tests\Feature;

use App\Contracts\OfficialDocumentRenderer;
use App\Jobs\GenerateBatchTorExport;
use App\Jobs\GenerateOfficialDocument;
use App\Models\BatchDocumentExport;
use App\Models\CompetencyUnit;
use App\Models\EnrollmentApplication;
use App\Models\ModuleProgress;
use App\Models\OfficialDocument;
use App\Models\TraineeCompetencyRecord;
use App\Models\TraineeOutcomeResult;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Models\User;
use App\Services\CompletionEligibilityService;
use App\Services\CompetencyWorkbookExporter;
use App\Services\OfficialDocumentManager;
use App\Support\CaregivingNcIiCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;
use ZipArchive;

class TrainingRecordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_competency_catalog_is_available_after_migration(): void
    {
        $this->assertDatabaseCount('competency_units', 24);
        $this->assertSame(11, CompetencyUnit::query()->where('is_tor_included', true)->count());
        $this->assertDatabaseHas('competency_units', [
            'program_code' => CaregivingNcIiCatalog::PROGRAM_CODE,
            'code' => 'HCS323301',
            'title' => 'Provide Care and Support to Infants and Toddlers',
        ]);
    }

    public function test_trainer_can_record_outcomes_and_official_grade(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $application = $this->approvedApplication($trainee);
        $unit = CompetencyUnit::query()->with('outcomes')->orderBy('sort_order')->firstOrFail();
        $this->publishUnitModule($application, $trainer, $unit);

        $payload = [
            'unit_id' => $unit->id,
            'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
            'percentage_score' => 95,
            'notes' => 'Observed and verified during the practical session.',
            'outcomes' => $unit->outcomes->mapWithKeys(
                fn ($outcome) => [$outcome->id => TraineeCompetencyRecord::STATUS_COMPETENT]
            )->all(),
        ];

        $this->actingAs($trainer)
            ->patch(route('trainer.competencies.update', $application), [
                'records' => [$unit->id => $payload],
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        $this->assertDatabaseHas('trainee_competency_records', [
            'enrollment_application_id' => $application->id,
            'competency_unit_id' => $unit->id,
            'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
            'tor_grade' => 1.30,
        ]);
        $this->assertSame($unit->outcomes->count(), TraineeOutcomeResult::query()->count());
    }

    public function test_completion_requires_every_core_unit_module_and_achievement_outcome(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $application = $this->approvedApplication($trainee, completed: true);
        $this->completeCompetencies($application, $trainer);

        $eligibility = app(CompletionEligibilityService::class)->evaluate($application->fresh('batch'));

        $this->assertTrue($eligibility['eligible']);
        $this->assertSame(11, $eligibility['counts']['competent_units']);
        $this->assertGreaterThan(24, $eligibility['counts']['competent_outcomes']);

        TraineeOutcomeResult::query()
            ->whereHas('outcome.unit', fn ($query) => $query
                ->where('category', TrainingModule::CATEGORY_CORE))
            ->firstOrFail()
            ->update([
            'status' => TraineeCompetencyRecord::STATUS_NOT_YET_COMPETENT,
        ]);

        $this->assertFalse(app(CompletionEligibilityService::class)
            ->evaluate($application->fresh('batch'))['eligible']);
    }

    public function test_admin_immediately_generates_only_the_requested_document_type_after_completion(): void
    {
        Storage::fake('local');
        $this->app->bind(OfficialDocumentRenderer::class, fn () => new class implements OfficialDocumentRenderer
        {
            public function render(OfficialDocument $document): string
            {
                return '%PDF-1.4 generated '.$document->type.' '.$document->document_number;
            }
        });
        $admin = User::factory()->create(['role' => 'admin']);
        $trainer = User::factory()->create(['role' => 'trainer']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $application = $this->approvedApplication($trainee, completed: true);
        $this->completeCompetencies($application, $trainer);

        $this->actingAs($admin)
            ->post(route('admin.learning.documents.generate', [$application, 'cotc']))
            ->assertRedirect()
            ->assertSessionHas('saved');

        $this->assertDatabaseHas('official_documents', [
            'enrollment_application_id' => $application->id,
            'type' => OfficialDocument::TYPE_COTC,
            'status' => OfficialDocument::STATUS_GENERATED,
            'version' => 1,
        ]);

        $cotc = OfficialDocument::query()->sole();
        $this->assertSame(OfficialDocument::TYPE_COTC, $cotc->type);
        $this->assertStringContainsString('/cotc/', $cotc->file_path);
        $this->assertStringContainsString('generated cotc', Storage::disk('local')->get($cotc->file_path));
        $this->assertDatabaseMissing('official_documents', [
            'enrollment_application_id' => $application->id,
            'type' => OfficialDocument::TYPE_TOR,
        ]);
    }

    public function test_admin_tor_request_uses_the_tor_type_template_and_download_path(): void
    {
        Storage::fake('local');
        $this->app->bind(OfficialDocumentRenderer::class, fn () => new class implements OfficialDocumentRenderer
        {
            public function render(OfficialDocument $document): string
            {
                return '%PDF-1.4 '.$document->type.' transcript content';
            }
        });
        $admin = User::factory()->create(['role' => 'admin']);
        $trainer = User::factory()->create(['role' => 'trainer']);
        $application = $this->approvedApplication(User::factory()->create(['role' => 'trainee']), completed: true);
        $this->completeCompetencies($application, $trainer);

        $this->actingAs($admin)
            ->get(route('admin.learning.certificates'))
            ->assertOk()
            ->assertSee(route('admin.learning.documents.generate', [$application, OfficialDocument::TYPE_TOR]), false)
            ->assertSee('Generate TOR');

        $this->actingAs($admin)
            ->post(route('admin.learning.documents.generate', [$application, OfficialDocument::TYPE_TOR]))
            ->assertRedirect()
            ->assertSessionHas('saved');

        $tor = OfficialDocument::query()->where('type', OfficialDocument::TYPE_TOR)->sole();
        $this->assertSame(OfficialDocument::TYPE_TOR, $tor->type);
        $this->assertSame(OfficialDocument::STATUS_GENERATED, $tor->status);
        $this->assertStringContainsString('/tor/', $tor->file_path);
        $this->assertStringContainsString('tor transcript content', Storage::disk('local')->get($tor->file_path));

        $preview = $this->actingAs($admin)
            ->get(route('admin.learning.documents.preview', $tor))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename='.$tor->document_number.'.pdf');
        $this->assertSame(Storage::disk('local')->get($tor->file_path), $preview->streamedContent());

        $download = $this->actingAs($admin)
            ->get(route('admin.learning.documents.download', $tor))
            ->assertOk()
            ->assertDownload($tor->document_number.'.pdf');
        $this->assertSame(Storage::disk('local')->get($tor->file_path), $download->streamedContent());
    }

    public function test_cotc_and_tor_records_are_not_reused_for_each_other(): void
    {
        Storage::fake('local');
        $this->app->bind(OfficialDocumentRenderer::class, fn () => new class implements OfficialDocumentRenderer
        {
            public function render(OfficialDocument $document): string
            {
                return '%PDF-1.4 rendered '.$document->type;
            }
        });
        $admin = User::factory()->create(['role' => 'admin']);
        $trainer = User::factory()->create(['role' => 'trainer']);
        $application = $this->approvedApplication(User::factory()->create(['role' => 'trainee']), completed: true);
        $this->completeCompetencies($application, $trainer);

        $this->actingAs($admin)
            ->post(route('admin.learning.documents.generate', [$application, OfficialDocument::TYPE_COTC]))
            ->assertRedirect();
        $cotc = OfficialDocument::query()->where('type', OfficialDocument::TYPE_COTC)->sole();

        $this->actingAs($admin)
            ->post(route('admin.learning.documents.generate', [$application, OfficialDocument::TYPE_TOR]))
            ->assertRedirect();
        $tor = OfficialDocument::query()->where('type', OfficialDocument::TYPE_TOR)->sole();

        $this->actingAs($admin)
            ->post(route('admin.learning.documents.generate', [$application, OfficialDocument::TYPE_COTC]))
            ->assertRedirect();

        $this->assertDatabaseCount('official_documents', 2);
        $this->assertSame($cotc->id, OfficialDocument::query()->where('type', OfficialDocument::TYPE_COTC)->sole()->id);
        $this->assertSame($tor->id, OfficialDocument::query()->where('type', OfficialDocument::TYPE_TOR)->sole()->id);
        $this->assertStringContainsString('/cotc/', $cotc->file_path);
        $this->assertStringContainsString('/tor/', $tor->file_path);
        $this->assertStringContainsString('rendered cotc', Storage::disk('local')->get($cotc->file_path));
        $this->assertStringContainsString('rendered tor', Storage::disk('local')->get($tor->file_path));
    }

    public function test_legacy_queued_tor_recovery_runs_the_tor_generation_path(): void
    {
        Storage::fake('local');
        $this->app->bind(OfficialDocumentRenderer::class, fn () => new class implements OfficialDocumentRenderer
        {
            public function render(OfficialDocument $document): string
            {
                return '%PDF-1.4 queued recovery '.$document->type;
            }
        });
        $admin = User::factory()->create(['role' => 'admin']);
        $trainer = User::factory()->create(['role' => 'trainer']);
        $application = $this->approvedApplication(User::factory()->create(['role' => 'trainee']), completed: true);
        $this->completeCompetencies($application, $trainer);
        $tor = OfficialDocument::create([
            'enrollment_application_id' => $application->id,
            'training_batch_id' => $application->training_batch_id,
            'type' => OfficialDocument::TYPE_TOR,
            'version' => 1,
            'document_number' => 'MCARE-TOR-2026-00001-V1',
            'status' => OfficialDocument::STATUS_QUEUED,
            'storage_disk' => 'local',
            'generated_by_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.learning.certificates'))
            ->assertOk()
            ->assertSee('Generate TOR now');

        $this->actingAs($admin)
            ->post(route('admin.learning.documents.generate', [$application, OfficialDocument::TYPE_TOR]))
            ->assertRedirect()
            ->assertSessionHas('saved');

        $tor->refresh();
        $this->assertSame(OfficialDocument::STATUS_GENERATED, $tor->status);
        $this->assertStringContainsString('/tor/', $tor->file_path);
        $this->assertStringContainsString('queued recovery tor', Storage::disk('local')->get($tor->file_path));
        $this->assertDatabaseCount('official_documents', 1);
    }

    public function test_queued_official_document_job_preserves_the_stored_tor_type(): void
    {
        Storage::fake('local');
        $this->app->bind(OfficialDocumentRenderer::class, fn () => new class implements OfficialDocumentRenderer
        {
            public function render(OfficialDocument $document): string
            {
                return '%PDF-1.4 queued job '.$document->type;
            }
        });
        $admin = User::factory()->create(['role' => 'admin']);
        $application = $this->approvedApplication(User::factory()->create(['role' => 'trainee']), completed: true);
        $tor = OfficialDocument::create([
            'enrollment_application_id' => $application->id,
            'training_batch_id' => $application->training_batch_id,
            'type' => OfficialDocument::TYPE_TOR,
            'version' => 1,
            'document_number' => 'MCARE-TOR-2026-00001-V1',
            'status' => OfficialDocument::STATUS_QUEUED,
            'storage_disk' => 'local',
            'generated_by_id' => $admin->id,
        ]);

        (new GenerateOfficialDocument($tor->id))->handle(app(OfficialDocumentManager::class));

        $tor->refresh();
        $this->assertSame(OfficialDocument::TYPE_TOR, $tor->type);
        $this->assertSame(OfficialDocument::STATUS_GENERATED, $tor->status);
        $this->assertStringContainsString('/tor/', $tor->file_path);
        $this->assertStringContainsString('queued job tor', Storage::disk('local')->get($tor->file_path));
    }

    public function test_invalid_stored_document_type_is_rejected_instead_of_defaulting_to_cotc(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $application = $this->approvedApplication(User::factory()->create(['role' => 'trainee']));
        $document = OfficialDocument::create([
            'enrollment_application_id' => $application->id,
            'training_batch_id' => $application->training_batch_id,
            'type' => 'TOR',
            'version' => 1,
            'document_number' => 'MCARE-TOR-2026-00001-V1',
            'status' => OfficialDocument::STATUS_QUEUED,
            'storage_disk' => 'local',
            'generated_by_id' => $admin->id,
        ]);

        try {
            app(OfficialDocumentManager::class)->generateNow($document);
            $this->fail('An unsupported stored document type should not be generated.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('type', $exception->errors());
        }

        $this->assertSame(OfficialDocument::STATUS_QUEUED, $document->refresh()->status);
        $this->assertNull($document->file_path);
    }

    public function test_official_document_templates_remain_distinct(): void
    {
        $application = $this->approvedApplication(User::factory()->create(['role' => 'trainee']), completed: true);
        $tor = OfficialDocument::make([
            'type' => OfficialDocument::TYPE_TOR,
            'version' => 1,
            'document_number' => 'MCARE-TOR-2026-00001-V1',
        ]);
        $cotc = OfficialDocument::make([
            'type' => OfficialDocument::TYPE_COTC,
            'version' => 1,
            'document_number' => 'MCARE-COTC-2026-00001-V1',
            'generated_at' => now(),
        ]);
        $application->load(['batch', 'competencyRecords.unit']);
        $organization = config('official_documents.organization');

        $torHtml = view('documents.pdf.tor', [
            'document' => $tor,
            'application' => $application,
            'organization' => $organization,
            'logoDataUri' => 'data:image/png;base64,AAAA',
            'cotcTemplateDataUri' => null,
        ])->render();
        $cotcHtml = view('documents.pdf.cotc', [
            'document' => $cotc,
            'application' => $application,
            'organization' => $organization,
            'logoDataUri' => 'data:image/png;base64,AAAA',
            'cotcTemplateDataUri' => 'data:image/png;base64,AAAA',
        ])->render();

        $this->assertSame('documents.pdf.tor', OfficialDocument::templateViewForType(OfficialDocument::TYPE_TOR));
        $this->assertSame('documents.pdf.cotc', OfficialDocument::templateViewForType(OfficialDocument::TYPE_COTC));
        $this->assertStringContainsString('OFFICIAL TRANSCRIPT OF RECORD', $torHtml);
        $this->assertStringNotContainsString('OFFICIAL TRANSCRIPT OF RECORD', $cotcHtml);
        $this->assertStringContainsString('background-image: url(\'data:image/png;base64,AAAA\')', $cotcHtml);
        $this->assertStringNotContainsString('background-image: url(\'data:image/png;base64,AAAA\')', $torHtml);
    }

    public function test_admin_can_generate_an_existing_document_left_in_the_old_queue(): void
    {
        Storage::fake('local');
        $this->app->bind(OfficialDocumentRenderer::class, fn () => new class implements OfficialDocumentRenderer
        {
            public function render(OfficialDocument $document): string
            {
                return '%PDF-1.4 recovered '.$document->type;
            }
        });
        $admin = User::factory()->create(['role' => 'admin']);
        $trainer = User::factory()->create(['role' => 'trainer']);
        $application = $this->approvedApplication(User::factory()->create(['role' => 'trainee']), completed: true);
        $this->completeCompetencies($application, $trainer);
        $document = OfficialDocument::create([
            'enrollment_application_id' => $application->id,
            'training_batch_id' => $application->training_batch_id,
            'type' => OfficialDocument::TYPE_COTC,
            'version' => 1,
            'document_number' => 'MCARE-COTC-2026-00001-V1',
            'status' => OfficialDocument::STATUS_QUEUED,
            'storage_disk' => 'local',
            'generated_by_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.learning.certificates'))
            ->assertOk()
            ->assertSee('Generate COTC now');

        $this->actingAs($admin)
            ->post(route('admin.learning.documents.generate', [$application, 'cotc']))
            ->assertRedirect()
            ->assertSessionHas('saved');

        $this->assertSame(OfficialDocument::STATUS_GENERATED, $document->refresh()->status);
        $this->assertStringContainsString('/cotc/', $document->file_path);
        Storage::disk('local')->assertExists($document->file_path);
        $this->assertDatabaseCount('official_documents', 1);
    }

    public function test_admin_preview_rewrites_a_clipped_portrait_cotc(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $application = $this->approvedApplication(User::factory()->create(['role' => 'trainee']));
        $portrait = new \FPDF('P', 'mm', 'Letter');
        $portrait->AddPage();
        $broken = $portrait->Output('S');
        $this->assertSame(1, preg_match('/\/MediaBox\s*\[\s*0(?:\.00)?\s+0(?:\.00)?\s+([\d.]+)\s+([\d.]+)\s*\]/', $broken, $matches));
        $this->assertLessThan((float) $matches[2], (float) $matches[1]);

        $path = 'official-documents/cotc/'.$application->training_batch_id.'/mcare-cotc-2026-00003-v1.pdf';
        Storage::disk('local')->put($path, $broken);
        $document = OfficialDocument::create([
            'enrollment_application_id' => $application->id,
            'training_batch_id' => $application->training_batch_id,
            'type' => OfficialDocument::TYPE_COTC,
            'version' => 1,
            'document_number' => 'MCARE-COTC-2026-00003-V1',
            'status' => OfficialDocument::STATUS_RELEASED,
            'storage_disk' => 'local',
            'file_path' => $path,
            'generated_by_id' => $admin->id,
            'released_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.learning.documents.preview', $document))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $rewritten = $response->streamedContent();
        $this->assertSame(1, preg_match('/\/MediaBox\s*\[\s*0(?:\.00)?\s+0(?:\.00)?\s+([\d.]+)\s+([\d.]+)\s*\]/', $rewritten, $rewrittenMatches));
        $this->assertGreaterThan((float) $rewrittenMatches[2], (float) $rewrittenMatches[1]);
        $this->assertEqualsWithDelta(792.0, (float) $rewrittenMatches[1], 1.0);
        $this->assertEqualsWithDelta(612.0, (float) $rewrittenMatches[2], 1.0);
        $this->assertSame(OfficialDocument::STATUS_RELEASED, $document->refresh()->status);
        $foundName = str_contains($rewritten, 'RECORD TRAINEE');
        if (! $foundName && preg_match_all('/stream\r?\n(.+?)\r?\nendstream/s', $rewritten, $streams)) {
            foreach ($streams[1] as $stream) {
                $decoded = @gzuncompress($stream);
                if (is_string($decoded) && str_contains($decoded, 'RECORD TRAINEE')) {
                    $foundName = true;
                    break;
                }
            }
        }
        $this->assertTrue($foundName);
    }

    public function test_trainer_can_open_batch_progress_and_achievement_charts(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $application = $this->approvedApplication($trainee);
        $this->completeCompetencies($application, $trainer);

        $this->actingAs($trainer)
            ->get(route('trainer.competencies.chart', [$application->batch, 'progress']))
            ->assertOk()
            ->assertSee('Progress Chart')
            ->assertSee('HCS323301');

        $this->actingAs($trainer)
            ->get(route('trainer.competencies.chart', [$application->batch, 'achievement']))
            ->assertOk()
            ->assertSee('Achievement Chart')
            ->assertSee('Provide Care and Support to Infants and Toddlers');
    }

    public function test_trainer_can_open_the_batch_grading_board(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $application = $this->approvedApplication($trainee);
        $published = CompetencyUnit::query()->where('code', 'HCS323301')->firstOrFail();
        $hidden = CompetencyUnit::query()->where('code', 'HCS323302')->firstOrFail();
        $this->publishUnitModule($application, $trainer, $published);

        $this->actingAs($trainer)
            ->get(route('trainer.competencies.index', ['batch_id' => $application->training_batch_id]))
            ->assertOk()
            ->assertSee('Batch grading board')
            ->assertSee('Bulk update')
            ->assertSee('Core competencies')
            ->assertSee('HCS323301')
            ->assertDontSee('HCS323302')
            ->assertDontSee($hidden->title)
            ->assertSee('Evaluate')
            ->assertSee('Evaluate record')
            ->assertSee('data-competency-cell', false)
            ->assertSee($application->last_name.', '.$application->first_name);
    }

    public function test_trainer_and_admin_can_download_a_real_competency_workbook(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $application = $this->approvedApplication($trainee);
        $this->completeCompetencies($application, $trainer);

        $export = app(CompetencyWorkbookExporter::class)->build($application->batch, 'AM');
        $reader = new Reader();
        $reader->open($export['path']);
        $sheetRows = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            $sheetRows[$sheet->getName()] = collect(iterator_to_array($sheet->getRowIterator()))
                ->take(8)
                ->map(fn ($row) => $row->toArray())
                ->values()
                ->all();
        }

        $reader->close();
        @unlink($export['path']);

        $this->assertSame(['Progress Matrix', 'Achievement Outcomes', 'Legend'], array_keys($sheetRows));
        $this->assertSame('Trainee ID', $sheetRows['Progress Matrix'][5][0]);
        $this->assertStringContainsString('Trainee, Record', $sheetRows['Progress Matrix'][6][1]);
        $this->assertSame('C | 95%', $sheetRows['Progress Matrix'][6][5]);
        $this->assertSame('C', $sheetRows['Achievement Outcomes'][6][5]);

        $this->actingAs($trainer)
            ->get(route('trainer.competencies.export', [
                'trainingBatch' => $application->batch,
                'schedule' => 'AM',
            ]))
            ->assertOk()
            ->assertDownload();

        $this->actingAs($admin)
            ->get(route('admin.learning.competency-workbooks.download', [
                'batch_id' => $application->training_batch_id,
                'schedule' => 'AM',
            ]))
            ->assertOk()
            ->assertDownload();
    }

    public function test_trainer_can_bulk_update_selected_trainees_atomically(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        $firstUser = User::factory()->create(['role' => 'trainee']);
        $secondUser = User::factory()->create(['role' => 'trainee']);
        $firstApplication = $this->approvedApplication($firstUser);
        $secondApplication = $firstApplication->replicate();
        $secondApplication->fill([
            'user_id' => $secondUser->id,
            'email' => $secondUser->email,
            'first_name' => 'Second',
            'last_name' => 'Trainee',
            'enrollment_number' => null,
        ])->save();
        $unit = CompetencyUnit::query()->with('outcomes')->orderBy('sort_order')->firstOrFail();
        $this->publishUnitModule($firstApplication, $trainer, $unit);

        $this->actingAs($trainer)
            ->patch(route('trainer.competencies.bulk-update'), [
                'batch_id' => $firstApplication->training_batch_id,
                'unit_id' => $unit->id,
                'trainee_ids' => [$firstApplication->id, $secondApplication->id],
                'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
                'percentage_score' => 90,
                'notes' => 'Batch practical assessment completed.',
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        $this->assertSame(2, TraineeCompetencyRecord::query()
            ->where('competency_unit_id', $unit->id)
            ->where('status', TraineeCompetencyRecord::STATUS_COMPETENT)
            ->where('tor_grade', 1.75)
            ->count());
        $this->assertSame($unit->outcomes->count() * 2, TraineeOutcomeResult::query()->count());
    }

    public function test_bulk_update_rejects_a_trainee_from_another_batch_without_partial_changes(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        $firstApplication = $this->approvedApplication(User::factory()->create(['role' => 'trainee']));
        $otherApplication = $this->approvedApplication(User::factory()->create(['role' => 'trainee']));
        $unit = CompetencyUnit::query()->orderBy('sort_order')->firstOrFail();
        $this->publishUnitModule($firstApplication, $trainer, $unit);

        $this->actingAs($trainer)
            ->from(route('trainer.competencies.index', ['batch_id' => $firstApplication->training_batch_id]))
            ->patch(route('trainer.competencies.bulk-update'), [
                'batch_id' => $firstApplication->training_batch_id,
                'unit_id' => $unit->id,
                'trainee_ids' => [$firstApplication->id, $otherApplication->id],
                'status' => TraineeCompetencyRecord::STATUS_IN_PROGRESS,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('trainee_ids');

        $this->assertDatabaseCount('trainee_competency_records', 0);
    }

    public function test_trainee_cotc_download_is_atomically_limited_to_one(): void
    {
        Storage::fake('local');
        $trainee = User::factory()->create(['role' => 'trainee']);
        $application = $this->approvedApplication($trainee, completed: true);
        Storage::disk('local')->put('official-documents/cotc/test.pdf', '%PDF-1.4 test');
        $document = OfficialDocument::create([
            'enrollment_application_id' => $application->id,
            'training_batch_id' => $application->training_batch_id,
            'type' => OfficialDocument::TYPE_COTC,
            'version' => 1,
            'document_number' => 'MCARE-COTC-2026-00001-V1',
            'status' => OfficialDocument::STATUS_RELEASED,
            'storage_disk' => 'local',
            'file_path' => 'official-documents/cotc/test.pdf',
            'released_at' => now(),
        ]);

        $this->actingAs($trainee)
            ->get(route('trainee.cotc.download', $document))
            ->assertOk()
            ->assertDownload($document->document_number.'.pdf');

        $this->actingAs($trainee)
            ->get(route('trainee.cotc.download', $document))
            ->assertRedirect(route('trainee.documents'))
            ->assertSessionHas('warning');

        $this->assertDatabaseHas('official_documents', [
            'id' => $document->id,
            'status' => OfficialDocument::STATUS_DOWNLOADED,
            'download_count' => 1,
        ]);
        $this->assertDatabaseCount('official_document_downloads', 1);
    }

    public function test_batch_tor_export_streams_a_unique_archive(): void
    {
        Storage::fake('local');
        $this->app->bind(OfficialDocumentRenderer::class, fn () => new class implements OfficialDocumentRenderer
        {
            public function render(OfficialDocument $document): string
            {
                return '%PDF-1.4 generated '.$document->document_number;
            }
        });
        $admin = User::factory()->create(['role' => 'admin']);
        $trainer = User::factory()->create(['role' => 'trainer']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $application = $this->approvedApplication($trainee, completed: true);
        $this->completeCompetencies($application, $trainer);
        $export = BatchDocumentExport::create([
            'training_batch_id' => $application->training_batch_id,
            'type' => OfficialDocument::TYPE_TOR,
            'status' => BatchDocumentExport::STATUS_QUEUED,
            'storage_disk' => 'local',
            'requested_by_id' => $admin->id,
        ]);

        (new GenerateBatchTorExport($export->id))->handle(app(OfficialDocumentManager::class));

        $export->refresh();
        $this->assertSame(BatchDocumentExport::STATUS_READY, $export->status);
        Storage::disk('local')->assertExists($export->file_path);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('local')->path($export->file_path)));
        $this->assertStringContainsString('-'.$application->id.'-TOR.pdf', $zip->getNameIndex(0));
        $zip->close();
    }

    private function approvedApplication(User $trainee, bool $completed = false): EnrollmentApplication
    {
        $batch = TrainingBatch::create([
            'name' => 'Batch Records '.$trainee->id,
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->subMonths(6),
            'training_starts_at' => now()->subMonths(5),
            'training_ends_at' => $completed ? now()->subDay() : now()->addMonth(),
        ]);

        return EnrollmentApplication::create([
            'user_id' => $trainee->id,
            'training_batch_id' => $batch->id,
            'email' => $trainee->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Record',
            'last_name' => 'Trainee',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => '1 Training Street',
            'barangay' => 'Central',
            'city' => 'Pili',
            'province' => 'Camarines Sur',
            'zip_code' => '4418',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'MCARE School',
            'year_graduated' => 2022,
            'status' => EnrollmentApplication::STATUS_APPROVED,
            'learning_status' => EnrollmentApplication::LEARNING_ACTIVE,
            'payment_status' => EnrollmentApplication::PAYMENT_PAID,
            'reviewed_at' => now(),
            'learning_started_at' => now(),
        ]);
    }

    private function publishUnitModule(
        EnrollmentApplication $application,
        User $trainer,
        CompetencyUnit $unit,
    ): TrainingModule {
        return TrainingModule::create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => $application->training_batch_id,
            'competency_unit_id' => $unit->id,
            'module_code' => $unit->code,
            'competency_category' => $unit->category ?: TrainingModule::CATEGORY_CORE,
            'title' => $unit->title,
            'description' => 'Published classwork for competency records.',
            'file_path' => "training-modules/testing/{$unit->code}.pdf",
            'original_file_name' => "{$unit->code}.pdf",
            'is_published' => true,
            'delivery_status' => TrainingModule::DELIVERY_ACTIVE,
            'published_at' => now(),
            'activated_at' => now(),
        ]);
    }

    private function completeCompetencies(EnrollmentApplication $application, User $trainer): void
    {
        CompetencyUnit::query()->with('outcomes')->each(function ($unit) use ($application, $trainer): void {
            $record = TraineeCompetencyRecord::create([
                'enrollment_application_id' => $application->id,
                'competency_unit_id' => $unit->id,
                'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
                'percentage_score' => 95,
                'tor_grade' => 1.30,
                'assessed_by_id' => $trainer->id,
                'assessed_at' => now(),
            ]);

            foreach ($unit->outcomes as $outcome) {
                TraineeOutcomeResult::create([
                    'trainee_competency_record_id' => $record->id,
                    'competency_outcome_id' => $outcome->id,
                    'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
                    'assessed_by_id' => $trainer->id,
                    'assessed_at' => now(),
                ]);
            }

            if ($unit->category !== TrainingModule::CATEGORY_CORE || ! $unit->is_required) {
                return;
            }

            $module = TrainingModule::create([
                'trainer_id' => $trainer->id,
                'training_batch_id' => $application->training_batch_id,
                'competency_unit_id' => $unit->id,
                'module_code' => $unit->code,
                'competency_category' => TrainingModule::CATEGORY_CORE,
                'title' => $unit->title,
                'description' => 'Completed core competency delivery.',
                'file_path' => "training-modules/testing/{$unit->code}.pdf",
                'original_file_name' => "{$unit->code}.pdf",
                'is_published' => true,
                'delivery_status' => TrainingModule::DELIVERY_CLOSED,
                'published_at' => now()->subDay(),
                'activated_at' => now()->subDay(),
                'closed_at' => now(),
            ]);

            ModuleProgress::create([
                'enrollment_application_id' => $application->id,
                'training_module_id' => $module->id,
                'status' => ModuleProgress::STATUS_COMPLETED,
                'progress_percent' => 100,
                'assigned_at' => now()->subDay(),
                'unlocked_at' => now()->subDay(),
                'submitted_at' => now(),
                'competency_outcome' => ModuleProgress::OUTCOME_COMPETENT,
                'evaluated_by_id' => $trainer->id,
                'evaluated_at' => now(),
                'completed_at' => now(),
            ]);
        });
    }

    public function test_admin_can_graduate_trainee_directly_and_fulfill_competencies_without_blocking(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $application = $this->approvedApplication($trainee, completed: false);

        $this->actingAs($admin)
            ->patch(route('admin.learning.trainees.status', $application), [
                'learning_status' => 'graduated',
                'learning_status_notes' => 'Completed requirements through direct onsite evaluation.',
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        $application->refresh();
        $this->assertSame('graduated', $application->learning_status);

        // Competency records are marked Competent
        $compRecords = TraineeCompetencyRecord::where('enrollment_application_id', $application->id)->get();
        $this->assertNotEmpty($compRecords);
        $this->assertTrue($compRecords->every(fn ($r) => $r->status === 'competent'));

        // Trainee can open grades page with official notice
        $this->actingAs($trainee)
            ->get(route('trainee.grades'))
            ->assertOk()
            ->assertSee('Official Certificate and Transcript of Records (TOR) Notice');
    }
}

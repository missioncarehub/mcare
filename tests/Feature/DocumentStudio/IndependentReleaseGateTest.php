<?php

namespace Tests\Feature\DocumentStudio;

use App\Models\CompetencyUnit;
use App\Models\DocumentAsset;
use App\Models\DocumentTemplate;
use App\Models\EnrollmentApplication;
use App\Models\OfficialDocument;
use App\Models\TraineeCompetencyRecord;
use App\Models\User;
use App\Services\DocumentStudio\AssetSecurityService;
use App\Services\DocumentStudio\DocumentStudioRenderer;
use App\Services\DocumentStudio\DocumentStudioService;
use App\Services\DocumentStudio\PlaceholderRegistry;
use App\Services\OfficialDocumentManager;
use App\Support\RolePermissionMatrix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class IndependentReleaseGateTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $runtimePath = storage_path('framework/testing/document-studio-release-gate');
        if (! is_dir($runtimePath)) {
            @mkdir($runtimePath, 0777, true);
        }
        $this->app->useStoragePath($runtimePath);
        RolePermissionMatrix::ensureConfigured();
        Storage::fake('local');
        Http::preventStrayRequests();
        Mail::fake();
        Queue::fake();
    }

    private function admin(): User
    {
        $admin = $this->lmsUser('admin');
        RolePermissionMatrix::syncUser($admin);

        return $admin;
    }

    private function template(string $engine = 'pdfme', string $kind = 'cotc'): DocumentTemplate
    {
        return app(DocumentStudioService::class)->createTemplate([
            'name' => 'Independent release review '.uniqid(),
            'engine' => $engine,
            'document_kind' => $kind,
            'initial_state' => [
                'engine' => $engine,
                'page' => ['width_mm' => 210, 'height_mm' => 297],
                'elements' => [['id' => 'name', 'type' => 'text', 'text' => '{{trainee_name}}', 'binding' => '{{trainee_name}}', 'x' => 20, 'y' => 20, 'width' => 100, 'height' => 10]],
            ],
        ]);
    }

    public function test_svg_root_event_handlers_are_removed(): void
    {
        $clean = app(AssetSecurityService::class)->sanitizeSvg('<svg xmlns="http://www.w3.org/2000/svg" onload="window.qaRootSvgXss=true"><rect width="10" height="10"/></svg>');
        $this->assertStringNotContainsString('onload', $clean);
    }

    public function test_published_unicode_and_url_content_passes_its_integrity_check(): void
    {
        $template = $this->template();
        $state = $template->draft_state;
        $state['elements'][0]['text'] = 'José / https://example.test/credential';
        $template->update(['draft_state' => $state]);
        $version = app(DocumentStudioService::class)->publishVersion($template);
        $this->assertTrue($version->verifyChecksum(), 'The same published bytes must verify without changing JSON encoding.');
    }

    public function test_word_content_alias_cannot_bypass_html_sanitization(): void
    {
        $template = $this->template('rk_word', 'general');
        $response = $this->actingAs($this->admin())->patchJson(route('admin.document-studio.draft.save', $template), [
            'draft_state' => ['engine' => 'rk_word', 'content' => '<p>Synthetic alias</p><img src="/qa-missing" onerror="window.qaWordAliasXss=true">'],
            'draft_revision' => 1,
        ]);
        if ($response->status() === 422) {
            $response->assertUnprocessable();

            return;
        }
        $response->assertOk();
        $this->assertStringNotContainsString('onerror', $template->fresh()->draft_state['content']);
    }

    public function test_batch_preserves_the_existing_released_document_and_its_pinned_version(): void
    {
        $admin = $this->admin();
        $batch = $this->lmsBatch();
        ['application' => $enrollment] = $this->lmsTrainee($batch, ['learning_status' => EnrollmentApplication::LEARNING_GRADUATED]);
        $template = $this->template();
        $firstVersion = app(DocumentStudioService::class)->publishVersion($template);
        $issued = app(OfficialDocumentManager::class)->findOrCreateQueued($enrollment, 'cotc', $admin);
        $path = sprintf('official-documents/cotc/%d/%s.pdf', $batch->id, strtolower($issued->document_number));
        $oldBytes = '%PDF-1.4 Synthetic previously released credential';
        Storage::disk('local')->put($path, $oldBytes);
        $issued->update(['status' => OfficialDocument::STATUS_RELEASED, 'file_path' => $path, 'sha256' => hash('sha256', $oldBytes), 'released_at' => now()]);
        $secondVersion = app(DocumentStudioService::class)->publishVersion($template);
        $renderer = \Mockery::mock(DocumentStudioRenderer::class);
        $renderer->shouldReceive('renderVersionToPdf')->andReturn('%PDF-1.4 Synthetic replacement bytes');
        $this->app->instance(DocumentStudioRenderer::class, $renderer);
        $this->actingAs($admin)->postJson(route('admin.document-studio.batch-generate', $template), ['training_batch_id' => $batch->id])->assertOk();

        $this->assertSame($oldBytes, Storage::disk('local')->get($path), 'Batch export must reuse previously issued files.');
        $this->assertSame($firstVersion->id, $issued->fresh()->document_template_version_id);
        $this->assertSame(OfficialDocument::STATUS_RELEASED, $issued->fresh()->status);
        $this->assertSame(1, OfficialDocument::where('enrollment_application_id', $enrollment->id)->count());
    }

    public function test_preview_sends_saved_word_contents_to_the_renderer(): void
    {
        $template = $this->template('rk_word', 'general');
        $state = $template->draft_state;
        $state['html'] = '<p>Independent visible preview text</p>';
        $template->update(['draft_state' => $state]);
        $captured = null;
        $renderer = \Mockery::mock(DocumentStudioRenderer::class);
        $renderer->shouldReceive('renderPayloadToPdf')->once()->andReturnUsing(function ($payload) use (&$captured) {
            $captured = $payload;

            return '%PDF-1.4 Synthetic preview';
        });
        $this->app->instance(DocumentStudioRenderer::class, $renderer);
        $this->actingAs($this->admin())->get(route('admin.document-studio.preview', $template))->assertOk();
        $this->assertSame($state['html'], $captured['html'] ?? null);
    }

    public function test_uploaded_pdf_is_persisted_as_a_scoped_asset_id(): void
    {
        $inputPath = tempnam(sys_get_temp_dir(), 'pdf_');
        $pdf = new \FPDF;
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 12);
        $pdf->Text(20, 30, 'Synthetic upload fixture');
        file_put_contents($inputPath, $pdf->Output('S'));
        $upload = new UploadedFile($inputPath, 'synthetic-background.pdf', 'application/pdf', null, true);
        $this->actingAs($this->admin())->post(route('admin.document-studio.store'), ['name' => 'Uploaded base review', 'engine' => 'pdfme', 'document_kind' => 'general', 'base_pdf' => $upload])->assertSessionHasNoErrors()->assertRedirect();
        $template = DocumentTemplate::where('name', 'Uploaded base review')->firstOrFail();
        $asset = DocumentAsset::where('document_template_id', $template->id)->firstOrFail();
        $this->assertSame($asset->id, $template->draft_state['base_pdf_asset_id'] ?? null);
        $this->assertArrayNotHasKey('basePdfPath', $template->draft_state);
        @unlink($inputPath);
    }

    public function test_draft_rejects_a_client_supplied_verified_background_path(): void
    {
        $template = $this->template('fabric');
        $state = $template->draft_state;
        $state['background'] = ['verifiedPath' => 'C:/synthetic-private-folder/signature.png'];
        $this->actingAs($this->admin())->patchJson(route('admin.document-studio.draft.save', $template), ['draft_state' => $state, 'draft_revision' => 1])->assertUnprocessable();
    }

    public function test_referenced_base_pdf_asset_cannot_be_deleted(): void
    {
        $template = $this->template();
        $asset = DocumentAsset::create(['document_template_id' => $template->id, 'name' => 'Synthetic base', 'type' => DocumentAsset::TYPE_PDF_BASE, 'disk' => 'local', 'file_path' => 'document-studio/assets/synthetic.pdf', 'mime_type' => 'application/pdf', 'file_size' => 32, 'sha256' => str_repeat('a', 64)]);
        Storage::disk('local')->put($asset->file_path, '%PDF-1.4 Synthetic base');
        $asset->update(['sha256' => hash('sha256', '%PDF-1.4 Synthetic base')]);
        $state = $template->draft_state;
        $state['base_pdf_asset_id'] = $asset->id;
        $template->update(['draft_state' => $state]);
        app(DocumentStudioService::class)->publishVersion($template);
        $this->actingAs($this->admin())->deleteJson(route('admin.document-studio.assets.destroy', $asset))->assertUnprocessable();
        $this->assertTrue(Storage::disk('local')->exists($asset->file_path));
    }

    public function test_a_queued_credential_is_not_publicly_verified_as_issued(): void
    {
        $batch = $this->lmsBatch();
        ['application' => $enrollment] = $this->lmsTrainee($batch, ['learning_status' => EnrollmentApplication::LEARNING_GRADUATED]);
        $document = app(OfficialDocumentManager::class)->findOrCreateQueued($enrollment, 'cotc', $this->admin());
        $this->assertSame(OfficialDocument::STATUS_QUEUED, $document->status);
        $this->get('/verify/credential/'.$document->document_number)->assertOk()->assertViewHas('isValid', false);
    }

    public function test_tor_hours_use_the_units_saved_record(): void
    {
        $batch = $this->lmsBatch();
        ['application' => $enrollment] = $this->lmsTrainee($batch, ['learning_status' => EnrollmentApplication::LEARNING_GRADUATED]);
        $unit = CompetencyUnit::create(['training_program_id' => $batch->training_program_id, 'program_code' => 'SYNTHETIC', 'code' => 'REVIEW-UNIT', 'title' => 'Synthetic saved-hours unit', 'category' => 'core', 'is_tor_included' => true, 'estimated_hours' => 36, 'sort_order' => 1]);
        TraineeCompetencyRecord::create(['enrollment_application_id' => $enrollment->id, 'competency_unit_id' => $unit->id, 'status' => 'competent', 'tor_grade' => 1.25]);
        $values = PlaceholderRegistry::resolveForEnrollment($enrollment, 'tor');
        $this->assertSame('36', $values['tor_competencies'][0]['hours']);
    }
}

<?php

namespace Tests\Feature\DocumentStudio;

use App\Models\DocumentAsset;
use App\Models\DocumentTemplate;
use App\Models\EnrollmentApplication;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\DocumentStudio\AssetSecurityService;
use App\Services\DocumentStudio\DocumentStudioRenderer;
use App\Services\DocumentStudio\DocumentStudioService;
use App\Services\DocumentStudio\TemplateImportExportService;
use App\Services\FpdfOfficialDocumentRenderer;
use App\Services\OfficialDocumentManager;
use App\Support\RolePermissionMatrix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class DocumentStudioTakeoverSafetyTest extends TestCase
{
    use CreatesLmsTestData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RolePermissionMatrix::ensureConfigured();
        Storage::fake('local');
        Http::preventStrayRequests();
        Mail::fake();
        Queue::fake();
    }

    private function admin(): User
    {
        $user = $this->lmsUser('admin');
        RolePermissionMatrix::syncUser($user);

        return $user;
    }

    private function template(string $kind = 'cotc'): DocumentTemplate
    {
        return app(DocumentStudioService::class)->createTemplate([
            'name' => 'Synthetic safety '.uniqid(), 'engine' => 'pdfme', 'document_kind' => $kind,
            'initial_state' => ['engine' => 'pdfme', 'page' => ['width_mm' => 210, 'height_mm' => 297],
                'elements' => [['id' => 'name', 'type' => 'text', 'text' => '{{trainee_name}}', 'x' => 20, 'y' => 20, 'width' => 100, 'height' => 10]]],
        ]);
    }

    public function test_template_bundle_clones_private_assets_and_rewrites_references(): void
    {
        $template = $this->template('general');
        $asset = app(AssetSecurityService::class)->storeUploadedFile(UploadedFile::fake()->image('logo.png', 20, 20), 'logo', $template->id);
        $state = $template->draft_state;
        $state['elements'][] = ['type' => 'image', 'asset_id' => $asset->id, 'src' => '/admin/document-studio/assets/'.$asset->id];
        $studio = app(DocumentStudioService::class);
        $studio->saveDraft($template, $state, 1);
        $studio->publishVersion($template);
        $bundles = app(TemplateImportExportService::class);
        $imported = $bundles->import($bundles->export($template->fresh()));
        $copy = $imported->assets()->sole();
        $this->assertNotSame($asset->id, $copy->id);
        $this->assertSame($copy->id, $imported->draft_state['elements'][1]['asset_id']);
        $this->assertSame('/admin/document-studio/assets/'.$copy->id, $imported->draft_state['elements'][1]['src']);
        $this->assertSame($asset->sha256, $copy->sha256);
        $this->assertNotSame($asset->file_path, $copy->file_path);
        $this->assertTrue($studio->publishVersion($imported)->verifyChecksum());
    }

    public function test_damaged_template_bundle_rolls_back_template_and_asset_files(): void
    {
        $template = $this->template('general');
        $asset = app(AssetSecurityService::class)->storeUploadedFile(UploadedFile::fake()->image('logo.png', 20, 20), 'logo', $template->id);
        $state = $template->draft_state;
        $state['background_asset_id'] = $asset->id;
        app(DocumentStudioService::class)->saveDraft($template, $state, 1);
        $bundles = app(TemplateImportExportService::class);
        $payload = $bundles->export($template->fresh());
        $payload['assets'][] = ['id' => 999, 'type' => 'logo', 'extension' => 'png', 'contents' => base64_encode('invalid image'), 'sha256' => str_repeat('a', 64)];
        try {
            $bundles->import($payload);
            $this->fail('A damaged asset must fail validation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('file', $exception->errors());
        }
        $this->assertSame(1, DocumentTemplate::count());
        $this->assertSame(1, DocumentAsset::count());
        $this->assertSame([$asset->file_path], Storage::disk('local')->allFiles(AssetSecurityService::STORAGE_PATH));
    }

    public function test_enrollment_preview_cannot_cross_the_templates_program(): void
    {
        $template = $this->template('general');
        $programBatch = $this->lmsBatch(['training_program_id' => TrainingProgram::create(['name' => 'Synthetic A', 'code' => 'SYN-A'])->id]);
        $otherBatch = $this->lmsBatch(['training_program_id' => TrainingProgram::create(['name' => 'Synthetic B', 'code' => 'SYN-B'])->id]);
        $template->update(['training_program_id' => $programBatch->training_program_id]);
        ['application' => $enrollment] = $this->lmsTrainee($otherBatch);
        $this->actingAs($this->admin())->getJson(route('admin.document-studio.preview', $template).'?enrollment_id='.$enrollment->id)
            ->assertUnprocessable()->assertJsonValidationErrors('enrollment_id');
    }

    public function test_batch_reuses_generated_bytes_and_pin_before_release(): void
    {
        $admin = $this->admin();
        $batch = $this->lmsBatch();
        ['application' => $enrollment] = $this->lmsTrainee($batch, ['learning_status' => EnrollmentApplication::LEARNING_GRADUATED]);
        $template = $this->template();
        $studio = app(DocumentStudioService::class);
        $v1 = $studio->publishVersion($template);
        $document = app(OfficialDocumentManager::class)->findOrCreateQueued($enrollment, 'cotc', $admin);
        $path = 'official-documents/synthetic-preserved.pdf';
        $bytes = '%PDF-1.4 previously generated';
        Storage::disk('local')->put($path, $bytes);
        $document->update(['status' => 'generated', 'file_path' => $path, 'sha256' => hash('sha256', $bytes), 'generated_at' => now()]);
        $studio->publishVersion($template);
        $this->actingAs($admin)->postJson(route('admin.document-studio.batch-generate', $template), ['training_batch_id' => $batch->id])
            ->assertOk()->assertJsonPath('result.successful', 1)->assertJsonPath('result.zip_path', null);
        $this->assertSame($bytes, Storage::disk('local')->get($path));
        $this->assertSame($v1->id, $document->fresh()->document_template_version_id);
        $this->assertSame('generated', $document->fresh()->status);
    }

    public function test_batch_does_not_rewrite_a_released_record_with_a_missing_pdf(): void
    {
        $admin = $this->admin();
        $batch = $this->lmsBatch();
        ['application' => $enrollment] = $this->lmsTrainee($batch, ['learning_status' => EnrollmentApplication::LEARNING_GRADUATED]);
        $template = $this->template();
        app(DocumentStudioService::class)->publishVersion($template);
        $document = app(OfficialDocumentManager::class)->findOrCreateQueued($enrollment, 'cotc', $admin);
        $document->update(['status' => 'released', 'file_path' => 'official-documents/missing.pdf', 'released_at' => now()]);
        $this->actingAs($admin)->postJson(route('admin.document-studio.batch-generate', $template), ['training_batch_id' => $batch->id])
            ->assertOk()->assertJsonPath('result.failed', 1)->assertJsonPath('result.successful', 0);
        $this->assertSame('released', $document->fresh()->status);
        $this->assertFalse(Storage::disk('local')->exists($document->file_path));
    }

    public function test_publishing_a_stale_snapshot_cannot_change_the_draft_or_create_a_version(): void
    {
        $template = $this->template();
        $studio = app(DocumentStudioService::class);
        $latest = $template->draft_state;
        $latest['elements'][0]['text'] = 'Latest staff edit';
        $studio->saveDraft($template, $latest, 1);
        $this->actingAs($this->admin())->postJson(route('admin.document-studio.publish', $template), [
            'draft_revision' => 1, 'draft_state' => $template->draft_state, 'make_active' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('revision');
        $this->assertSame('Latest staff edit', $template->fresh()->draft_state['elements'][0]['text']);
        $this->assertSame(2, $template->fresh()->draft_revision);
        $this->assertSame(0, $template->versions()->count());
    }

    public function test_publishing_requires_the_client_revision_and_rolls_back_invalid_state(): void
    {
        $template = $this->template();
        $admin = $this->admin();
        $this->actingAs($admin)->postJson(route('admin.document-studio.publish', $template), [])->assertUnprocessable();
        $bad = $template->draft_state;
        $bad['elements'][0]['type'] = 'unsafe-plugin';
        $this->actingAs($admin)->postJson(route('admin.document-studio.publish', $template), ['draft_revision' => 1, 'draft_state' => $bad])->assertUnprocessable();
        $this->assertSame(1, $template->fresh()->draft_revision);
        $this->assertSame(0, $template->versions()->count());
    }

    private function asset(DocumentTemplate $template): DocumentAsset
    {
        $path = 'document-studio/assets/'.uniqid().'.pdf';
        $bytes = '%PDF-1.4 synthetic base';
        Storage::disk('local')->put($path, $bytes);

        return DocumentAsset::create(['document_template_id' => $template->id, 'name' => 'Synthetic base', 'type' => 'pdf_base', 'disk' => 'local', 'file_path' => $path, 'mime_type' => 'application/pdf', 'file_size' => strlen($bytes), 'sha256' => hash('sha256', $bytes)]);
    }

    public function test_draft_cannot_reference_another_templates_asset(): void
    {
        $owner = $this->template();
        $target = $this->template();
        $asset = $this->asset($owner);
        $state = $target->draft_state;
        $state['base_pdf_asset_id'] = $asset->id;
        $this->actingAs($this->admin())->patchJson(route('admin.document-studio.draft.save', $target), ['draft_revision' => 1, 'draft_state' => $state])->assertUnprocessable();
        $this->assertSame(1, $target->fresh()->draft_revision);
    }

    public function test_rendering_checks_published_state_and_asset_integrity(): void
    {
        $template = $this->template();
        $asset = $this->asset($template);
        $state = $template->draft_state;
        $state['base_pdf_asset_id'] = $asset->id;
        $template->update(['draft_state' => $state]);
        $version = app(DocumentStudioService::class)->publishVersion($template);
        Storage::disk('local')->put($asset->file_path, '%PDF-1.4 changed bytes');
        try {
            app(DocumentStudioRenderer::class)->renderVersionToPdf($version, []);
            $this->fail('Tampered asset was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('draft_state', $exception->errors());
        }
        DB::table('document_template_versions')->where('id', $version->id)->update(['editor_state' => json_encode(['elements' => []])]);
        $this->expectException(\RuntimeException::class);
        app(DocumentStudioRenderer::class)->renderVersionToPdf($version->fresh(), []);
    }

    public function test_pinned_initial_defaults_keep_the_established_renderer(): void
    {
        $studio = app(DocumentStudioService::class);
        $studio->ensureDefaultTemplates();
        $batch = $this->lmsBatch();
        ['application' => $enrollment] = $this->lmsTrainee($batch, ['learning_status' => EnrollmentApplication::LEARNING_GRADUATED]);
        $document = app(OfficialDocumentManager::class)->findOrCreateQueued($enrollment, 'cotc', $this->admin());
        $this->assertNotNull($document->document_template_version_id);
        $fallback = \Mockery::mock(FpdfOfficialDocumentRenderer::class);
        $fallback->shouldReceive('render')->once()->with($document)->andReturn('%PDF-1.4 preserved baseline design');
        $this->assertSame('%PDF-1.4 preserved baseline design', (new DocumentStudioRenderer($fallback))->render($document));
    }

    public function test_templates_with_published_history_cannot_be_deleted(): void
    {
        $template = $this->template();
        app(DocumentStudioService::class)->publishVersion($template);
        $this->expectException(\RuntimeException::class);
        $template->delete();
    }

    public function test_svg_wrong_namespace_and_external_urls_are_not_accepted(): void
    {
        $security = app(AssetSecurityService::class);
        $safe = $security->sanitizeSvg('<svg xmlns="http://www.w3.org/2000/svg"><rect fill="url(https://example.test/secret)"/><image href="data:image/svg+xml;base64,AAAA"/></svg>');
        $this->assertStringNotContainsString('https://', $safe);
        $this->assertStringNotContainsString('data:image', $safe);
        $this->expectException(ValidationException::class);
        $security->sanitizeSvg('<svg xmlns="http://www.w3.org/1999/xhtml"/>');
    }

    public function test_word_drafts_do_not_preserve_external_resource_loads(): void
    {
        $security = app(AssetSecurityService::class);
        $safe = $security->sanitizeHtml('<p style="background-image:url(https://example.test/a)"><strong>Keep formatting</strong><img src="https://example.test/secret" srcset="https://example.test/other" onerror="alert(1)"></p>');
        $this->assertStringNotContainsString('https://',$safe);
        $this->assertStringContainsString('<strong>Keep formatting</strong>',$safe);
    }
}

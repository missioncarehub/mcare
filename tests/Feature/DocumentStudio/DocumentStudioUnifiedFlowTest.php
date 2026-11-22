<?php

namespace Tests\Feature\DocumentStudio;

use App\Models\DocumentAsset;
use App\Models\DocumentTemplate;
use App\Models\User;
use App\Services\DocumentStudio\DocumentStudioService;
use App\Support\RolePermissionMatrix;
use FPDF;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

class DocumentStudioUnifiedFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RolePermissionMatrix::ensureConfigured();
        Storage::fake('local');
        Http::preventStrayRequests();
        Mail::fake();
        Queue::fake();
        config(['official_documents.browsershot.node_binary' => PHP_OS_FAMILY === 'Windows' ? 'C:/Program Files/nodejs/node.exe' : 'node']);
    }

    private function user(string $role = 'admin'): User
    {
        $user = User::factory()->create(['role' => $role]);
        RolePermissionMatrix::syncUser($user);

        return $user;
    }

    private function pdf(): UploadedFile
    {
        $pdf = new FPDF;
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(80, 10, 'Synthetic editable paper');

        return UploadedFile::fake()->createWithContent('paper.pdf', $pdf->Output('S'));
    }

    private function uploadData(UploadedFile $file, array $extra = []): array
    {
        return array_merge([
            'name' => 'Unified upload',
            'document_kind' => 'general',
            'source' => 'upload',
            'source_file' => $file,
            'operation' => (string) Str::uuid(),
        ], $extra);
    }

    public function test_library_has_one_new_document_path_and_the_form_combines_sources(): void
    {
        $this->actingAs($this->user())->get(route('admin.document-studio.index'))
            ->assertOk()->assertSee('New document')->assertDontSee('Create blank')
            ->assertDontSee('Create New Draft from Current MCARE Design')
            ->assertSee('/start-draft', false);
        $this->get(route('admin.document-studio.create'))->assertOk()
            ->assertSee('Current design')->assertSee('Upload file')->assertSee('Blank page')
            ->assertSee('source_file')->assertDontSee('name="engine"', false);
    }

    public function test_current_soa_and_published_edit_open_full_drafts_without_changing_the_source(): void
    {
        $admin = $this->user();
        $studio = app(DocumentStudioService::class);
        $studio->ensureDefaultTemplates();
        $source = DocumentTemplate::where('document_kind', 'soa')->firstOrFail();
        $before = $source->draft_state;
        $version = $source->activeVersion;
        $hash = $version->sha256;

        $this->actingAs($admin)->post(route('admin.document-studio.store'), [
            'name' => 'Complete SOA draft', 'document_kind' => 'soa', 'source' => 'current',
        ])->assertRedirect();
        $draft = DocumentTemplate::where('name', 'Complete SOA draft')->firstOrFail();
        $this->assertGreaterThan(40, count($draft->draft_state['schemas'][0]));
        $this->assertFalse($draft->is_active);
        $this->assertNull($draft->active_version_id);
        $this->post(route('admin.document-studio.start-draft', $source))
            ->assertRedirect(route('admin.document-studio.edit', $draft));
        $this->assertSame($before, $source->fresh()->draft_state);
        $this->assertSame($version->id, $source->fresh()->active_version_id);
        $this->assertSame($hash, $version->fresh()->sha256);
    }

    public function test_blank_creation_chooses_the_layout_without_accepting_a_client_engine(): void
    {
        $this->actingAs($this->user())->post(route('admin.document-studio.store'), [
            'name' => 'Blank COTC', 'document_kind' => 'cotc', 'source' => 'blank', 'engine' => 'rk_word',
        ])->assertRedirect();
        $draft = DocumentTemplate::where('name', 'Blank COTC')->firstOrFail();
        $this->assertSame('fabric', $draft->engine);
        $this->assertFalse($draft->is_active);
        $this->assertNull($draft->active_version_id);
        $this->assertSame([], $draft->draft_state['elements']);
    }

    public function test_pdf_upload_converts_text_and_keeps_the_original_private(): void
    {
        $this->actingAs($this->user())->post(route('admin.document-studio.store'), $this->uploadData($this->pdf(), [
            'engine' => 'fabric', 'pdf_mode' => 'editable', 'regions' => '[]',
        ]))->assertRedirect();
        $draft = DocumentTemplate::where('name', 'Unified upload')->firstOrFail();
        $this->assertSame('pdfme', $draft->engine);
        $this->assertFalse($draft->is_active);
        $this->assertStringContainsString('Synthetic editable paper', json_encode($draft->draft_state));
        $this->assertSame(1, $draft->assets()->where('type', DocumentAsset::TYPE_PDF_SOURCE)->count());
        $this->assertNotNull($draft->draft_state['base_pdf_asset_id']);
    }

    public function test_pdf_background_and_image_uploads_use_the_correct_private_asset_type(): void
    {
        $this->actingAs($this->user())->post(route('admin.document-studio.store'), $this->uploadData($this->pdf(), [
            'name' => 'Fixed PDF', 'document_kind' => 'cotc', 'pdf_mode' => 'background',
        ]))->assertRedirect();
        $pdf = DocumentTemplate::where('name', 'Fixed PDF')->firstOrFail();
        $this->assertSame('pdfme', $pdf->engine);
        $this->assertSame(DocumentAsset::TYPE_PDF_BASE, $pdf->assets()->firstOrFail()->type);

        $this->post(route('admin.document-studio.store'), $this->uploadData(UploadedFile::fake()->image('artwork.png', 320, 200), [
            'name' => 'Image design', 'engine' => 'rk_word',
        ]))->assertRedirect();
        $image = DocumentTemplate::where('name', 'Image design')->firstOrFail();
        $this->assertSame('fabric', $image->engine);
        $this->assertSame(DocumentAsset::TYPE_IMAGE, $image->assets()->firstOrFail()->type);
        $this->assertNotNull($image->draft_state['background_asset_id']);
    }

    public function test_docx_upload_opens_its_text_in_the_document_editor(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'studio-flow-docx-');
        try {
            $zip = new ZipArchive;
            $zip->open($path, ZipArchive::OVERWRITE);
            $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
            $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
            $zip->addFromString('word/document.xml', '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>Synthetic Word design</w:t></w:r></w:p></w:body></w:document>');
            $zip->close();
            $file = new UploadedFile($path, 'document.docx', null, null, true);
            $this->actingAs($this->user())->post(route('admin.document-studio.store'), $this->uploadData($file))
                ->assertRedirect();
            $draft = DocumentTemplate::where('name', 'Unified upload')->firstOrFail();
            $this->assertSame('rk_word', $draft->engine);
            $this->assertStringContainsString('Synthetic Word design', $draft->draft_state['html']);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_invalid_sources_files_and_regions_are_rejected_before_a_draft_is_created(): void
    {
        $this->actingAs($this->user());
        $this->postJson(route('admin.document-studio.store'), [
            'name' => 'Invalid current', 'document_kind' => 'general', 'source' => 'current',
        ])->assertUnprocessable()->assertJsonValidationErrors('document_kind');
        $this->postJson(route('admin.document-studio.store'), $this->uploadData($this->pdf(), [
            'regions' => json_encode([['page' => 1, 'x' => -1, 'y' => 0, 'width' => 10, 'height' => 10, 'color' => '#ffffff', 'content' => 'Value']]),
        ]))->assertUnprocessable()->assertJsonValidationErrors('regions.0.x');
        $this->postJson(route('admin.document-studio.store'), $this->uploadData($this->pdf(), [
            'base_pdf' => $this->pdf(),
        ]))->assertUnprocessable()->assertJsonValidationErrors('source_file');
        $this->postJson(route('admin.document-studio.store'), $this->uploadData($this->pdf(), [
            'source_file' => [$this->pdf()],
        ]))->assertUnprocessable()->assertJsonValidationErrors('source_file');
        $disguised = UploadedFile::fake()->createWithContent('pretend.pdf', '<script>alert(1)</script>');
        $this->postJson(route('admin.document-studio.store'), $this->uploadData(
            new UploadedFile($disguised->getRealPath(), 'pretend.pdf', null, null, true)
        ))->assertUnprocessable()->assertJsonValidationErrors('source_file');
        $this->postJson(route('admin.document-studio.store'), [
            'name' => 'Wrong source', 'document_kind' => 'soa', 'source' => 'blank', 'base_pdf' => $this->pdf(),
        ])->assertUnprocessable()->assertJsonValidationErrors('source');
        $this->assertDatabaseCount('document_templates', 0);
        $this->assertDatabaseCount('document_assets', 0);
    }

    public function test_non_admins_cannot_create_or_start_a_published_draft(): void
    {
        $template = app(DocumentStudioService::class)->createTemplate(['name' => 'Protected design', 'document_kind' => 'soa']);
        foreach (['trainer', 'trainee', 'alumni', 'applicant'] as $role) {
            $this->actingAs($this->user($role))->get(route('admin.document-studio.create'))->assertForbidden();
            $this->post(route('admin.document-studio.store'), [
                'name' => 'Denied', 'document_kind' => 'soa', 'source' => 'current',
            ])->assertForbidden();
            $this->post(route('admin.document-studio.start-draft', $template))->assertForbidden();
        }
        $this->assertDatabaseCount('document_templates', 1);
    }

    public function test_empty_schema_preview_gives_a_recovery_message_and_cannot_be_published(): void
    {
        $admin = $this->user();
        $draft = app(DocumentStudioService::class)->createTemplate([
            'name' => 'Emptied paper', 'document_kind' => 'general', 'engine' => 'pdfme',
            'initial_state' => ['engine' => 'pdfme', 'schemas' => [[]], 'elements' => [
                ['id' => 'stale', 'type' => 'text', 'content' => 'Old element'],
            ]],
        ], $admin);
        $this->actingAs($admin)->getJson(route('admin.document-studio.preview', ['template' => $draft, 'mode' => 'draft']))
            ->assertUnprocessable()->assertJsonValidationErrors('preview')->assertSee('This layout is empty');
        $this->get(route('admin.document-studio.preview', ['template' => $draft, 'mode' => 'draft']), ['Accept' => 'application/pdf'])
            ->assertUnprocessable()->assertHeader('Content-Type', 'application/json')->assertSee('This layout is empty');
        $this->postJson(route('admin.document-studio.publish', $draft), [
            'draft_revision' => $draft->draft_revision, 'make_active' => true,
        ])->assertUnprocessable();
        $this->assertDatabaseCount('document_template_versions', 0);
        $this->assertNull($draft->fresh()->active_version_id);
    }
}

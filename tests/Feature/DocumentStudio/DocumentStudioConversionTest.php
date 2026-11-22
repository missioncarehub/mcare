<?php

namespace Tests\Feature\DocumentStudio;

use App\Models\DocumentAsset;
use App\Models\DocumentTemplate;
use App\Models\User;
use App\Services\DocumentStudio\AssetSecurityService;
use App\Services\DocumentStudio\DocumentStudioService;
use App\Services\DocumentStudio\ExistingDesignService;
use App\Services\DocumentStudio\PdfConversionService;
use App\Services\DocumentStudio\TemplateImportExportService;
use App\Support\RolePermissionMatrix;
use FPDF;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DocumentStudioConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RolePermissionMatrix::ensureConfigured();
        Storage::fake('local');
        config(['official_documents.browsershot.node_binary' => PHP_OS_FAMILY === 'Windows' ? 'C:/Program Files/nodejs/node.exe' : 'node']);
    }

    private function admin(): User
    {
        $user = User::factory()->create(['role' => 'admin']);
        RolePermissionMatrix::syncUser($user);

        return $user;
    }

    private function upload(bool $blank = false): UploadedFile
    {
        $pdf = new FPDF;
        $pdf->AddPage();
        if (! $blank) {
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(80, 10, 'PRIVATE ORIGINAL NAME');
        }

        return UploadedFile::fake()->createWithContent('source.pdf', $pdf->Output('S'));
    }

    public function test_conversion_stores_provenance_separately_and_never_exports_it(): void
    {
        $template = app(PdfConversionService::class)->convert($this->upload(), ['name' => 'Imported design', 'document_kind' => 'general'], [], $this->admin());
        $this->assertFalse($template->is_active);
        $this->assertNull($template->active_version_id);
        $this->assertSame(1, $template->conversion_report['editable_fields']);
        $source = $template->assets()->where('type', DocumentAsset::TYPE_PDF_SOURCE)->firstOrFail();
        $state = $template->draft_state;
        $state['schemas'][0][0]['content'] = '{{trainee_name}}';
        $template = app(DocumentStudioService::class)->saveDraft($template, $state, $template->draft_revision);
        $export = app(TemplateImportExportService::class)->export($template);
        $this->assertCount(1, $export['assets']);
        $this->assertNotSame($source->id, $export['assets'][0]['id']);
        $this->assertStringNotContainsString('PRIVATE ORIGINAL NAME', json_encode($export));
        $state['base_pdf_asset_id'] = $source->id;
        $this->expectException(ValidationException::class);
        app(DocumentStudioService::class)->saveDraft($template, $state, $template->draft_revision);
    }

    public function test_failed_conversion_rolls_back_template_assets_and_temporary_files(): void
    {
        $before = Storage::disk('local')->allFiles();
        try {
            app(PdfConversionService::class)->convert($this->upload(true), ['name' => 'No editable fields', 'document_kind' => 'general'], [], $this->admin());
            $this->fail('An empty conversion must not succeed.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('file', $exception->errors());
        }
        $this->assertDatabaseCount('document_templates', 0);
        $this->assertDatabaseCount('document_assets', 0);
        $this->assertSame($before, Storage::disk('local')->allFiles());
    }

    public function test_non_admin_roles_cannot_reach_upload_convert_or_existing_design(): void
    {
        foreach (['trainer', 'trainee', 'alumni', 'applicant'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            RolePermissionMatrix::syncUser($user);
            $this->actingAs($user)->get(route('admin.document-studio.upload'))->assertForbidden();
            $this->post(route('admin.document-studio.convert-pdf'))->assertForbidden();
            $this->post(route('admin.document-studio.existing-design'), ['kind' => 'soa'])->assertForbidden();
        }
    }

    public function test_admin_can_open_upload_and_invalid_regions_are_rejected_before_conversion(): void
    {
        $this->actingAs($this->admin())->get(route('admin.document-studio.upload'))
            ->assertRedirect(route('admin.document-studio.create', ['source' => 'upload']));
        $this->get(route('admin.document-studio.create', ['source' => 'upload']))->assertOk()->assertSee('Manual replacement region');
        $this->postJson(route('admin.document-studio.convert-pdf'), [
            'operation' => (string) Str::uuid(), 'name' => 'Invalid region', 'document_kind' => 'general', 'file' => $this->upload(),
            'regions' => json_encode([['page' => 1, 'x' => -5, 'y' => 0, 'width' => 10, 'height' => 10, 'color' => '#ffffff', 'content' => 'test']]),
        ])->assertUnprocessable()->assertJsonValidationErrors('regions.0.x');
        $this->assertDatabaseCount('document_templates', 0);
    }

    public function test_copying_existing_design_never_changes_the_active_version(): void
    {
        $admin = $this->admin();
        $studio = app(DocumentStudioService::class);
        $old = $studio->createTemplate([
            'name' => 'Staff template',
            'engine' => 'pdfme',
            'document_kind' => 'soa',
            'initial_state' => [
                'engine' => 'pdfme',
                'elements' => [
                    ['id' => 'name', 'type' => 'text', 'binding' => '{{trainee_name}}'],
                    ['id' => 'ledger', 'type' => 'table', 'binding' => 'soa_ledger'],
                ],
            ],
        ], $admin);
        $version = $studio->publishVersion($old, 'Original', true, $admin);
        $hash = $version->sha256;
        $copy = app(ExistingDesignService::class)->create('soa', null, $admin);
        $this->assertNotSame($old->id, $copy->id);
        $this->assertFalse($copy->is_active);
        $this->assertNull($copy->active_version_id);
        $this->assertSame($version->id, $old->fresh()->active_version_id);
        $this->assertSame($hash, $version->fresh()->sha256);
        $this->assertEqualsWithDelta(215.9, $copy->draft_state['page']['width_mm'], .01);
        $this->assertStringContainsString('{{soa_ledger}}', json_encode($copy->draft_state));
        $this->assertStringNotContainsString('MCARE_TRAINEE', json_encode($copy->draft_state));
    }

    public function test_plain_text_retains_ampersands_without_creating_html(): void
    {
        $state = app(AssetSecurityService::class)->sanitizeEditorState(['type' => 'text', 'content' => 'Fees & payments <img src=x onerror=alert(1)>']);
        $this->assertSame('Fees & payments ', $state['content']);
    }

    public function test_empty_shape_content_survives_request_normalization(): void
    {
        foreach (['text', 'line', 'rectangle', 'ellipse'] as $type) {
            $state = app(AssetSecurityService::class)->sanitizeEditorState(['type' => $type, 'content' => null]);
            $this->assertSame('', $state['content']);
        }
    }

    public function test_cancelling_a_running_conversion_stops_it_and_rolls_back(): void
    {
        $cache = \Mockery::mock(Repository::class);
        $cache->shouldReceive('add')->once()->andReturnTrue();
        $cache->shouldReceive('get')->andReturn(false, true);
        Cache::shouldReceive('store')->with('file')->andReturn($cache);
        try {
            app(PdfConversionService::class)->convert($this->upload(), ['name' => 'Cancelled', 'document_kind' => 'general'], [], $this->admin(), (string) Str::uuid());
            $this->fail('Cancellation must fail the conversion.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('cancelled', $exception->errors()['file'][0]);
        }
        $this->assertDatabaseCount('document_templates', 0);
        $this->assertDatabaseCount('document_assets', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_cancellation_is_scoped_to_the_authenticated_administrator(): void
    {
        $first = $this->admin();
        $second = $this->admin();
        $operation = (string) Str::uuid();
        $this->actingAs($second)->postJson(route('admin.document-studio.cancel-conversion'), ['operation' => $operation])->assertOk();
        $cache = Cache::store('file');
        $this->assertNull($cache->get('studio-conversion-cancel:'.$first->id.':'.$operation));
        $this->assertTrue($cache->get('studio-conversion-cancel:'.$second->id.':'.$operation));
        $cache->forget('studio-conversion-cancel:'.$second->id.':'.$operation);
    }

    public function test_additive_conversion_migration_can_recover_and_repeat_without_changing_history(): void
    {
        $migration = require database_path('migrations/2026_10_08_120000_add_conversion_report_to_document_templates.php');
        $template = DocumentTemplate::create(['name' => 'Preserved', 'engine' => 'pdfme', 'document_kind' => 'general', 'draft_state' => ['engine' => 'pdfme']]);
        $migration->up();
        $this->assertTrue(Schema::hasColumn('document_templates', 'conversion_report'));
        $this->assertSame('Preserved', $template->fresh()->name);
        $migration->down();
        $migration->up();
        $migration->up();
        $this->assertSame(['engine' => 'pdfme'], $template->fresh()->draft_state);
    }
}

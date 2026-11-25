<?php

namespace Tests\Feature\DocumentStudio;

use App\Models\DocumentTemplate;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\DocumentStudio\DocumentStudioService;
use App\Support\RolePermissionMatrix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProgramSpecificPdfmeTemplateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private DocumentStudioService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        RolePermissionMatrix::ensureConfigured();

        $this->admin = User::factory()->create(['role' => 'admin']);
        RolePermissionMatrix::syncUser($this->admin);

        $this->service = app(DocumentStudioService::class);
    }

    public function test_ensure_default_templates_provisions_program_specific_pdfme_templates(): void
    {
        $this->service->ensureDefaultTemplates();

        $nc2 = TrainingProgram::where('code', 'CAREGIVING-NC-II')->first();
        $this->assertNotNull($nc2, 'Caregiving NC II program must exist');

        $nc3 = TrainingProgram::where('code', 'CAREGIVING-NC-III')->first();
        $this->assertNotNull($nc3, 'Caregiving NC III program must exist');

        // Check NC II COTC
        $nc2Cotc = DocumentTemplate::where('document_kind', DocumentTemplate::KIND_COTC)
            ->where('training_program_id', $nc2->id)
            ->first();
        $this->assertNotNull($nc2Cotc, 'Caregiving NC II must have its own COTC template');
        $this->assertSame(DocumentTemplate::ENGINE_PDFME, $nc2Cotc->engine);
        $this->assertNotNull($nc2Cotc->activeVersion);

        // Verify native PDFme schemas on NC II COTC: clean border + dynamic fields + logo, no qr_code
        $state = $nc2Cotc->draft_state;
        $schemas = $state['schemas'][0] ?? [];
        $schemaNames = array_column($schemas, 'name');
        $this->assertContains('release_date', $schemaNames);
        $this->assertContains('certificate_title', $schemaNames);
        $this->assertContains('trainee_name', $schemaNames);
        $this->assertNotContains('qr_code', $schemaNames, 'COTC default must omit QR code');

        // Check NC III COTC
        $nc3Cotc = DocumentTemplate::where('document_kind', DocumentTemplate::KIND_COTC)
            ->where('training_program_id', $nc3->id)
            ->first();
        $this->assertNotNull($nc3Cotc, 'Caregiving NC III must have its own COTC template');
        $this->assertSame(DocumentTemplate::ENGINE_PDFME, $nc3Cotc->engine);
        $this->assertNotSame($nc2Cotc->id, $nc3Cotc->id, 'NC II and NC III must have distinct templates');

        // Check NC II and NC III TOR
        $nc2Tor = DocumentTemplate::where('document_kind', DocumentTemplate::KIND_TOR)
            ->where('training_program_id', $nc2->id)
            ->first();
        $this->assertNotNull($nc2Tor, 'Caregiving NC II must have its own TOR template');
        $this->assertSame(DocumentTemplate::ENGINE_PDFME, $nc2Tor->engine);

        $nc3Tor = DocumentTemplate::where('document_kind', DocumentTemplate::KIND_TOR)
            ->where('training_program_id', $nc3->id)
            ->first();
        $this->assertNotNull($nc3Tor, 'Caregiving NC III must have its own TOR template');
        $this->assertSame(DocumentTemplate::ENGINE_PDFME, $nc3Tor->engine);
    }

    public function test_admin_can_load_document_studio_index_and_open_editor_in_pdfme(): void
    {
        $this->service->ensureDefaultTemplates();
        $nc2 = TrainingProgram::where('code', 'CAREGIVING-NC-II')->first();
        $nc2Cotc = DocumentTemplate::where('document_kind', DocumentTemplate::KIND_COTC)
            ->where('training_program_id', $nc2->id)
            ->first();

        // 1. Index page loads successfully and defaults to program scope
        $response = $this->actingAs($this->admin)->get(route('admin.document-studio.index'));
        $response->assertOk();
        $response->assertSee('Caregiving NC II');
        $response->assertSee('Open PDFme Designer');

        // 2. Editor page loads successfully and provides #studio-editor-viewport
        $editResponse = $this->actingAs($this->admin)->get(route('admin.document-studio.edit', $nc2Cotc));
        $editResponse->assertOk();
        $editResponse->assertSee('studio-editor-viewport');
        $editResponse->assertSee('"engine":"pdfme"', false);
        $editResponse->assertSee('btn-typography-preset');
    }

    public function test_certificate_fonts_are_provisioned_preserved_and_rendered_to_pdf(): void
    {
        $this->service->ensureDefaultTemplates();

        $nc2 = TrainingProgram::where('code', 'CAREGIVING-NC-II')->firstOrFail();
        $nc3 = TrainingProgram::where('code', 'CAREGIVING-NC-III')->firstOrFail();

        $nc2Cotc = DocumentTemplate::where('document_kind', DocumentTemplate::KIND_COTC)
            ->where('training_program_id', $nc2->id)
            ->firstOrFail();
        $nc3Cotc = DocumentTemplate::where('document_kind', DocumentTemplate::KIND_COTC)
            ->where('training_program_id', $nc3->id)
            ->firstOrFail();

        $nc2Fonts = collect($nc2Cotc->draft_state['schemas'][0] ?? [])->pluck('fontName', 'name');
        $nc3Fonts = collect($nc3Cotc->draft_state['schemas'][0] ?? [])->pluck('fontName', 'name');

        $this->assertSame('PlayfairDisplay-Bold', $nc2Fonts->get('trainee_name'));
        $this->assertSame('Cinzel-Bold', $nc2Fonts->get('training_program'));
        $this->assertSame('GreatVibes', $nc3Fonts->get('trainee_name'));

        // Save custom pdfme schemas with certificate fontName and verify persistence
        $customState = [
            'schema_version' => '1.0',
            'engine' => DocumentTemplate::ENGINE_PDFME,
            'page' => ['size' => 'letter', 'orientation' => 'landscape', 'width_mm' => 279.4, 'height_mm' => 215.9],
            'base_pdf_url' => '/assets/cotc-base-template.pdf',
            'schemas' => [[
                [
                    'name' => 'name_text',
                    'type' => 'text',
                    'position' => ['x' => 25, 'y' => 90],
                    'width' => 230,
                    'height' => 18,
                    'fontSize' => 30,
                    'fontName' => 'GreatVibes',
                    'alignment' => 'center',
                    'binding' => '{{trainee_name}}',
                    'content' => '{{trainee_name}}',
                ],
                [
                    'name' => 'program_text',
                    'type' => 'text',
                    'position' => ['x' => 25, 'y' => 118],
                    'width' => 230,
                    'height' => 10,
                    'fontSize' => 16,
                    'fontName' => 'Cinzel-Bold',
                    'alignment' => 'center',
                    'binding' => '{{training_program}}',
                    'content' => '{{training_program}}',
                ],
                [
                    'name' => 'release_date_text',
                    'type' => 'text',
                    'position' => ['x' => 25, 'y' => 145],
                    'width' => 230,
                    'height' => 6,
                    'fontSize' => 8.5,
                    'fontName' => 'Montserrat',
                    'alignment' => 'center',
                    'binding' => '{{release_date}}',
                    'content' => '{{release_date}}',
                ],
            ]],
        ];

        $this->service->saveDraft($nc2Cotc, $customState, $nc2Cotc->draft_revision, $this->admin);
        $nc2Cotc->refresh();
        $this->assertSame('GreatVibes', $nc2Cotc->draft_state['schemas'][0][0]['fontName']);

        $pdfBinary = app(\App\Services\DocumentStudio\DocumentStudioRenderer::class)->renderPayloadToPdf([
            ...$nc2Cotc->draft_state,
            'values' => [
                'trainee_name' => 'Maria Reyes Santos',
                'training_program' => 'CAREGIVING NC II',
                'release_date' => 'November 25, 2026',
            ],
        ]);

        $this->assertStringStartsWith('%PDF-', $pdfBinary);
        $this->assertGreaterThan(10000, strlen($pdfBinary));

        // Verify preview endpoint works cleanly for both program COTC and program TOR
        $cotcPreview = $this->actingAs($this->admin)->get(route('admin.document-studio.preview', ['template' => $nc2Cotc, 'mode' => 'draft']));
        $cotcPreview->assertOk();
        $cotcPreview->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $cotcPreview->getContent());

        $nc2Tor = DocumentTemplate::where('document_kind', DocumentTemplate::KIND_TOR)
            ->where('training_program_id', $nc2->id)
            ->firstOrFail();
        $torPreview = $this->actingAs($this->admin)->get(route('admin.document-studio.preview', ['template' => $nc2Tor, 'mode' => 'draft']));
        $torPreview->assertOk();
        $torPreview->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $torPreview->getContent());
    }

    public function test_legacy_program_templates_upgrade_and_save_fallback_and_preview_fallback_work(): void
    {
        $program = TrainingProgram::firstOrCreate(
            ['code' => 'CAREGIVING-NC-II'],
            [
                'name' => 'Caregiving NC II',
                'description' => 'Caregiving NC II Program',
                'total_program_fee' => 25000,
                'downpayment_amount' => 3000,
                'is_active' => true,
            ]
        );

        // Seed an unedited legacy fabric baseline for this program
        $legacyCotc = DocumentTemplate::create([
            'name' => "Certificate of Training Completion - {$program->name}",
            'slug' => "cotc-program-{$program->id}",
            'document_kind' => DocumentTemplate::KIND_COTC,
            'engine' => DocumentTemplate::ENGINE_FABRIC,
            'training_program_id' => $program->id,
            'is_active' => true,
            'is_system_default' => false,
            'draft_revision' => 1,
            'draft_state' => [
                'engine' => DocumentTemplate::ENGINE_FABRIC,
                'elements' => [['id' => 'old_name', 'type' => 'text', 'content' => '{{trainee_name}}']],
            ],
        ]);
        $this->service->publishVersion($legacyCotc, 'Legacy baseline', true);

        $this->service->ensureDefaultTemplates();
        $legacyCotc->refresh();
        $this->assertSame(DocumentTemplate::ENGINE_PDFME, $legacyCotc->engine);
        $this->assertSame('PlayfairDisplay-Bold', collect($legacyCotc->draft_state['schemas'][0] ?? [])->pluck('fontName', 'name')->get('trainee_name'));

        // Verify POST save fallback works in addition to PATCH
        $this->actingAs($this->admin)
            ->postJson(route('admin.document-studio.draft.save', $legacyCotc), [
                'draft_state' => $legacyCotc->draft_state,
                'draft_revision' => $legacyCotc->draft_revision,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        // Verify preview falls back cleanly if server Node process fails
        $mockRenderer = \Mockery::mock(\App\Services\DocumentStudio\DocumentStudioRenderer::class);
        $mockRenderer->shouldReceive('renderPayloadToPdf')
            ->once()
            ->andThrow(new \RuntimeException('Document Studio rendering process failed: node not found'));
        $this->app->instance(\App\Services\DocumentStudio\DocumentStudioRenderer::class, $mockRenderer);

        $fallbackPreview = $this->actingAs($this->admin)
            ->get(route('admin.document-studio.preview', ['template' => $legacyCotc, 'mode' => 'draft']));
        $fallbackPreview->assertOk();
        $fallbackPreview->assertHeader('content-type', 'application/pdf');
        $fallbackPreview->assertHeader('x-mcare-preview-fallback', 'fpdf');
        $this->assertStringStartsWith('%PDF-', $fallbackPreview->getContent());
    }

    public function test_outside_library_preview_matches_latest_edited_program_draft(): void
    {
        $program = TrainingProgram::firstOrCreate(
            ['code' => 'CAREGIVING-NC-II'],
            [
                'name' => 'Caregiving NC II',
                'description' => 'Caregiving NC II Program',
                'total_program_fee' => 25000,
                'downpayment_amount' => 3000,
                'is_active' => true,
            ]
        );

        $this->service->ensureDefaultTemplates();

        $publishedCotc = DocumentTemplate::query()
            ->where('document_kind', DocumentTemplate::KIND_COTC)
            ->where('training_program_id', $program->id)
            ->where('is_active', true)
            ->firstOrFail();

        // Simulate clicking "Open PDFme Designer" and editing font in draft copy
        $this->actingAs($this->admin)
            ->post(route('admin.document-studio.start-draft', $publishedCotc))
            ->assertRedirect();

        $workingDraft = DocumentTemplate::query()
            ->where('document_kind', DocumentTemplate::KIND_COTC)
            ->where('training_program_id', $program->id)
            ->where('is_active', false)
            ->latest('id')
            ->firstOrFail();

        $editedState = $workingDraft->draft_state;
        $editedState['schemas'][0][0]['fontName'] = 'GreatVibes';

        $this->actingAs($this->admin)
            ->patchJson(route('admin.document-studio.draft.save', $workingDraft), [
                'draft_state' => $editedState,
                'draft_revision' => $workingDraft->draft_revision,
            ])
            ->assertOk();

        // Editor page keeps training_program_id on Back link and includes field labels button
        $this->actingAs($this->admin)
            ->get(route('admin.document-studio.edit', $workingDraft))
            ->assertOk()
            ->assertSee('btn-toggle-field-labels', false)
            ->assertSee('training_program_id='.$program->id, false);

        // Library index must now preview the edited draft (mode=draft) instead of stale published v1
        $expectedDraftPreviewUrl = route('admin.document-studio.preview', ['template' => $workingDraft, 'mode' => 'draft'], false);
        $this->actingAs($this->admin)
            ->get(route('admin.document-studio.index', ['training_program_id' => $program->id]))
            ->assertOk()
            ->assertSee('Previewing Edited Draft')
            ->assertSee($expectedDraftPreviewUrl, false);
    }
}

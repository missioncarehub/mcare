<?php

namespace Tests\Feature\DocumentStudio;

use App\Models\DocumentTemplate;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\DocumentStudio\DocumentStudioService;
use App\Support\RolePermissionMatrix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramSpecificPdfmeTemplateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private DocumentStudioService $service;

    protected function setUp(): void
    {
        parent::setUp();
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

        // Verify elements on NC II COTC: has release_date and does not have qr_code
        $state = $nc2Cotc->draft_state;
        $elements = $state['elements'] ?? [];
        $elementIds = array_column($elements, 'id');
        $this->assertContains('release_date_text', $elementIds);
        $this->assertNotContains('qr_code', $elementIds, 'COTC default must omit QR code');

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
    }
}

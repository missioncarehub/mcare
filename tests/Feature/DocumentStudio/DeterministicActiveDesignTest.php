<?php

namespace Tests\Feature\DocumentStudio;

use App\Models\DocumentTemplate;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\DocumentStudio\DocumentStudioService;
use App\Support\RolePermissionMatrix;
use FPDF;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeterministicActiveDesignTest extends TestCase
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

    public function test_create_template_auto_assigns_engine_without_explicit_user_selection(): void
    {
        // 1. COTC auto-assigns fabric
        $respCotc = $this->actingAs($this->admin)->post(route('admin.document-studio.store'), [
            'name' => 'Automated COTC Design',
            'document_kind' => DocumentTemplate::KIND_COTC,
        ]);
        $respCotc->assertRedirect();
        $this->assertDatabaseHas('document_templates', [
            'name' => 'Automated COTC Design',
            'document_kind' => DocumentTemplate::KIND_COTC,
            'engine' => DocumentTemplate::ENGINE_FABRIC,
        ]);

        // 2. TOR auto-assigns pdfme
        $respTor = $this->actingAs($this->admin)->post(route('admin.document-studio.store'), [
            'name' => 'Automated TOR Design',
            'document_kind' => DocumentTemplate::KIND_TOR,
        ]);
        $respTor->assertRedirect();
        $this->assertDatabaseHas('document_templates', [
            'name' => 'Automated TOR Design',
            'document_kind' => DocumentTemplate::KIND_TOR,
            'engine' => DocumentTemplate::ENGINE_PDFME,
        ]);

        // 3. SOA auto-assigns pdfme
        $respSoa = $this->actingAs($this->admin)->post(route('admin.document-studio.store'), [
            'name' => 'Automated SOA Design',
            'document_kind' => DocumentTemplate::KIND_SOA,
        ]);
        $respSoa->assertRedirect();
        $this->assertDatabaseHas('document_templates', [
            'name' => 'Automated SOA Design',
            'document_kind' => DocumentTemplate::KIND_SOA,
            'engine' => DocumentTemplate::ENGINE_PDFME,
        ]);
    }

    public function test_publishing_deactivates_competing_active_templates_in_same_scope(): void
    {
        // Create Template A and publish V1
        $templateA = DocumentTemplate::create([
            'name' => 'Template A (Global COTC)',
            'slug' => 'template-a-global-cotc',
            'document_kind' => DocumentTemplate::KIND_COTC,
            'engine' => DocumentTemplate::ENGINE_FABRIC,
            'training_program_id' => null,
            'is_active' => false,
            'draft_revision' => 1,
            'draft_state' => ['elements' => [['id' => 'elem1', 'type' => 'text', 'binding' => '{{trainee_name}}']]],
        ]);
        $vA = $this->service->publishVersion($templateA, 'Initial release A', true, $this->admin);
        $this->assertTrue((bool) $templateA->fresh()->is_active);
        $this->assertSame($vA->id, $templateA->fresh()->active_version_id);

        // Create Template B in the exact same scope (Global COTC)
        $templateB = DocumentTemplate::create([
            'name' => 'Template B (Global COTC Replacement)',
            'slug' => 'template-b-global-cotc-replacement',
            'document_kind' => DocumentTemplate::KIND_COTC,
            'engine' => DocumentTemplate::ENGINE_FABRIC,
            'training_program_id' => null,
            'is_active' => false,
            'draft_revision' => 1,
            'draft_state' => ['elements' => [['id' => 'elem2', 'type' => 'text', 'binding' => '{{trainee_name}}']]],
        ]);

        // Publish version on Template B
        $vB = $this->service->publishVersion($templateB, 'Replacement release B', true, $this->admin);

        // Template B must now be active, Template A must be deactivated
        $this->assertTrue((bool) $templateB->fresh()->is_active);
        $this->assertSame($vB->id, $templateB->fresh()->active_version_id);
        $this->assertFalse((bool) $templateA->fresh()->is_active);

        // Ensure exactly one template is active for this scope
        $activeCount = DocumentTemplate::query()
            ->where('document_kind', DocumentTemplate::KIND_COTC)
            ->whereNull('training_program_id')
            ->where('is_active', true)
            ->count();
        $this->assertSame(1, $activeCount);

        // Resolution resolves Template B
        $resolved = $this->service->resolveActiveVersion(DocumentTemplate::KIND_COTC);
        $this->assertNotNull($resolved);
        $this->assertSame($vB->id, $resolved->id);
    }

    public function test_program_specific_scope_takes_precedence_over_global_scope(): void
    {
        $programCaregiving = TrainingProgram::create([
            'name' => 'Caregiving NC II',
            'code' => 'CG-NCII',
            'tuition_fee' => 25000,
        ]);
        $programNursing = TrainingProgram::create([
            'name' => 'Healthcare Services NC II',
            'code' => 'HCS-NCII',
            'tuition_fee' => 20000,
        ]);

        // Global COTC Template
        $globalTemplate = DocumentTemplate::create([
            'name' => 'Global COTC Baseline',
            'slug' => 'global-cotc-baseline',
            'document_kind' => DocumentTemplate::KIND_COTC,
            'engine' => DocumentTemplate::ENGINE_FABRIC,
            'training_program_id' => null,
            'draft_revision' => 1,
            'draft_state' => ['elements' => [['id' => 'name', 'type' => 'text', 'binding' => '{{trainee_name}}']]],
        ]);
        $vGlobal = $this->service->publishVersion($globalTemplate, 'Global release', true, $this->admin);

        // Caregiving Program-Specific COTC Template
        $programTemplate = DocumentTemplate::create([
            'name' => 'Caregiving Specialized COTC',
            'slug' => 'caregiving-specialized-cotc',
            'document_kind' => DocumentTemplate::KIND_COTC,
            'engine' => DocumentTemplate::ENGINE_FABRIC,
            'training_program_id' => $programCaregiving->id,
            'draft_revision' => 1,
            'draft_state' => ['elements' => [['id' => 'name', 'type' => 'text', 'binding' => '{{trainee_name}}']]],
        ]);
        $vProgram = $this->service->publishVersion($programTemplate, 'Caregiving release', true, $this->admin);

        // 1. Caregiving program gets its program-specific version
        $resolvedCaregiving = $this->service->resolveActiveVersion(DocumentTemplate::KIND_COTC, $programCaregiving->id);
        $this->assertNotNull($resolvedCaregiving);
        $this->assertSame($vProgram->id, $resolvedCaregiving->id);

        // 2. Nursing program has no override -> falls back to Global version
        $resolvedNursing = $this->service->resolveActiveVersion(DocumentTemplate::KIND_COTC, $programNursing->id);
        $this->assertNotNull($resolvedNursing);
        $this->assertSame($vGlobal->id, $resolvedNursing->id);

        // 3. Null programId gets Global version
        $resolvedGlobal = $this->service->resolveActiveVersion(DocumentTemplate::KIND_COTC, null);
        $this->assertNotNull($resolvedGlobal);
        $this->assertSame($vGlobal->id, $resolvedGlobal->id);
    }

    public function test_draft_edits_do_not_mutate_the_active_published_version(): void
    {
        $template = DocumentTemplate::create([
            'name' => 'TOR Template',
            'slug' => 'tor-template',
            'document_kind' => DocumentTemplate::KIND_TOR,
            'engine' => DocumentTemplate::ENGINE_PDFME,
            'draft_revision' => 1,
            'draft_state' => [
                'elements' => [
                    ['id' => '1', 'type' => 'text', 'binding' => '{{trainee_name}}', 'content' => 'Original Published Value'],
                    ['id' => '2', 'type' => 'text', 'binding' => '{{training_program}}'],
                    ['id' => '3', 'type' => 'table', 'binding' => 'tor_competencies'],
                ],
            ],
        ]);

        $v1 = $this->service->publishVersion($template, 'V1 Publication', true, $this->admin);
        $this->assertSame('Original Published Value', $v1->editor_state['elements'][0]['content']);

        // Admin modifies working draft state
        $this->service->saveDraft($template, [
            'elements' => [
                ['id' => '1', 'type' => 'text', 'binding' => '{{trainee_name}}', 'content' => 'Modified Draft Value (Unpublished)'],
                ['id' => '2', 'type' => 'text', 'binding' => '{{training_program}}'],
                ['id' => '3', 'type' => 'table', 'binding' => 'tor_competencies'],
            ],
        ], $template->draft_revision);

        // The template's working draft is updated
        $this->assertSame(
            'Modified Draft Value (Unpublished)',
            $template->fresh()->draft_state['elements'][0]['content']
        );

        // But the active published version remains completely unchanged
        $v1->refresh();
        $this->assertSame('Original Published Value', $v1->editor_state['elements'][0]['content']);

        // Active version resolution returns the published V1 with original content
        $resolved = $this->service->resolveActiveVersion(DocumentTemplate::KIND_TOR);
        $this->assertSame($v1->id, $resolved->id);
        $this->assertSame('Original Published Value', $resolved->editor_state['elements'][0]['content']);
    }

    public function test_published_designs_summary_reports_active_designs_and_program_scopes(): void
    {
        $summary = $this->service->getPublishedDesignsSummary();

        // Must include official credential document kinds (COTC and TOR), omitting unissued SOA for safety
        $this->assertArrayHasKey(DocumentTemplate::KIND_COTC, $summary);
        $this->assertArrayHasKey(DocumentTemplate::KIND_TOR, $summary);
        $this->assertArrayNotHasKey(DocumentTemplate::KIND_SOA, $summary);

        // Publish a global TOR design
        $tor = DocumentTemplate::create([
            'name' => 'Official TOR Design',
            'slug' => 'official-tor-design',
            'document_kind' => DocumentTemplate::KIND_TOR,
            'engine' => DocumentTemplate::ENGINE_PDFME,
            'draft_revision' => 1,
            'draft_state' => [
                'elements' => [
                    ['id' => 'name', 'type' => 'text', 'binding' => '{{trainee_name}}'],
                    ['id' => 'prog', 'type' => 'text', 'binding' => '{{training_program}}'],
                    ['id' => 'comp', 'type' => 'table', 'binding' => 'tor_competencies'],
                ],
            ],
        ]);
        $this->service->publishVersion($tor, 'V1.0', true, $this->admin);

        $summaryAfter = $this->service->getPublishedDesignsSummary();
        $this->assertNotNull($summaryAfter[DocumentTemplate::KIND_TOR]['global']);
        $this->assertSame('Official TOR Design', $summaryAfter[DocumentTemplate::KIND_TOR]['global']->name);
        $this->assertSame(1, $summaryAfter[DocumentTemplate::KIND_TOR]['global']->activeVersion->version_number);
    }

    public function test_uploaded_cotc_pdf_selects_pdfme_and_preserves_source_asset(): void
    {
        Storage::fake('local');
        $pdf = new FPDF('L', 'mm', 'Letter');
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 18);
        $pdf->Text(20, 30, 'SYNTHETIC UPLOADED PDF SOURCE');
        $source = $pdf->Output('S');

        $response = $this->actingAs($this->admin)->post(route('admin.document-studio.store'), [
            'name' => 'Uploaded COTC Standard PDF',
            'document_kind' => DocumentTemplate::KIND_COTC,
            'format' => 'standard',
            'base_pdf' => UploadedFile::fake()->createWithContent('synthetic-cotc.pdf', $source),
        ]);

        $response->assertRedirect();
        $template = DocumentTemplate::where('name', 'Uploaded COTC Standard PDF')->firstOrFail();

        $this->assertSame(DocumentTemplate::ENGINE_PDFME, $template->engine);
        $this->assertNotNull($template->draft_state['base_pdf_asset_id'] ?? null);
    }

    public function test_readiness_audit_recognizes_authoritative_tor_tokens_and_tables(): void
    {
        $template = $this->service->createTemplate([
            'name' => 'Bound TOR Template',
            'document_kind' => DocumentTemplate::KIND_TOR,
            'initial_state' => [
                'engine' => 'pdfme',
                'page' => ['width_mm' => 210, 'height_mm' => 297],
                'elements' => [
                    ['id' => 'name', 'type' => 'text', 'binding' => '{{trainee_name}}'],
                    ['id' => 'program', 'type' => 'text', 'binding' => '{{training_program}}'],
                    ['id' => 'grades', 'type' => 'table', 'binding' => 'tor_competencies'],
                ],
            ],
        ]);

        $audit = $this->service->auditTemplate($template);

        $this->assertContains('trainee_name', array_column($audit['recognized_tokens'], 'token'));
        $this->assertContains('training_program', array_column($audit['recognized_tokens'], 'token'));
        $this->assertTrue($audit['table_slots']['has_competencies_table']);
        $this->assertTrue($audit['is_ready_for_issuance']);
    }
}

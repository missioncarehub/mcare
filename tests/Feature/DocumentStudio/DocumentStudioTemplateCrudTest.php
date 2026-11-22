<?php

namespace Tests\Feature\DocumentStudio;

use App\Models\DocumentTemplate;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Support\RolePermissionMatrix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DocumentStudioTemplateCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RolePermissionMatrix::ensureConfigured();
    }

    public function test_admin_can_access_document_studio_index(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        RolePermissionMatrix::syncUser($admin);

        $response = $this->actingAs($admin)->get(route('admin.document-studio.index'));

        $response->assertOk();
        $response->assertSee('Document Studio');
        $response->assertSee('COTC');
    }

    public function test_trainer_and_trainee_cannot_access_document_studio(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        RolePermissionMatrix::syncUser($trainer);

        $this->actingAs($trainer)->get(route('admin.document-studio.index'))->assertForbidden();

        $trainee = User::factory()->create(['role' => 'trainee']);
        RolePermissionMatrix::syncUser($trainee);

        $this->actingAs($trainee)->get(route('admin.document-studio.index'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.document-studio.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_create_new_template(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        RolePermissionMatrix::syncUser($admin);

        $program = TrainingProgram::create([
            'name' => 'Caregiving NC II',
            'code' => 'CG-NCII',
            'tuition_fee' => 25000,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.document-studio.store'), [
            'name' => 'Custom Caregiving Certificate',
            'document_kind' => DocumentTemplate::KIND_COTC,
            'engine' => DocumentTemplate::ENGINE_FABRIC,
            'training_program_id' => $program->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('document_templates', [
            'name' => 'Custom Caregiving Certificate',
            'document_kind' => 'cotc',
            'engine' => 'fabric',
            'training_program_id' => $program->id,
        ]);
    }

    public function test_admin_can_save_draft_with_optimistic_concurrency(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        RolePermissionMatrix::syncUser($admin);

        $template = DocumentTemplate::create([
            'name' => 'Draft Template',
            'slug' => 'draft-template',
            'document_kind' => DocumentTemplate::KIND_GENERAL,
            'engine' => DocumentTemplate::ENGINE_FABRIC,
            'draft_revision' => 1,
            'draft_state' => ['elements' => []],
        ]);

        // Successful save with matching revision 1
        $response = $this->actingAs($admin)->patchJson(route('admin.document-studio.draft.save', $template), [
            'draft_state' => [
                'elements' => [
                    ['id' => '1', 'type' => 'text', 'text' => 'Updated text'],
                ],
            ],
            'draft_revision' => 1,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'draft_revision' => 2,
        ]);

        $template->refresh();
        $this->assertSame(2, $template->draft_revision);
        $this->assertSame('Updated text', $template->draft_state['elements'][0]['text']);

        // Stale save with old revision 1 should trigger concurrency validation conflict
        $conflictResponse = $this->actingAs($admin)->patchJson(route('admin.document-studio.draft.save', $template), [
            'draft_state' => ['elements' => []],
            'draft_revision' => 1,
        ]);

        $conflictResponse->assertStatus(422);
        $conflictResponse->assertJsonValidationErrors(['revision']);
    }

    public function test_admin_can_publish_and_activate_new_version(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        RolePermissionMatrix::syncUser($admin);

        $template = DocumentTemplate::create([
            'name' => 'Publishable Template',
            'slug' => 'publishable-template',
            'document_kind' => DocumentTemplate::KIND_TOR,
            'engine' => DocumentTemplate::ENGINE_PDFME,
            'draft_revision' => 1,
            'draft_state' => [
                'schemas' => [[
                    ['name' => 'trainee_name', 'type' => 'text'],
                    ['name' => 'training_program', 'type' => 'text'],
                    ['name' => 'tor_competencies', 'type' => 'table'],
                ]],
            ],
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.document-studio.publish', $template), [
            'changelog' => 'First official publication',
            'make_active' => true,
            'draft_revision' => 1,
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'version' => ['id', 'version_number', 'sha256'],
        ]);

        $template->refresh();
        $this->assertNotNull($template->active_version_id);
        $version = $template->activeVersion;
        $this->assertSame(1, $version->version_number);
        $this->assertSame('First official publication', $version->changelog);
    }

    public function test_admin_can_export_and_import_template_json(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        RolePermissionMatrix::syncUser($admin);

        $template = DocumentTemplate::create([
            'name' => 'Portable Template',
            'slug' => 'portable-template',
            'document_kind' => DocumentTemplate::KIND_COTC,
            'engine' => DocumentTemplate::ENGINE_PDFME,
            'draft_revision' => 1,
            'draft_state' => [
                'elements' => [['id' => 'name', 'type' => 'text', 'binding' => '{{trainee_name}}']],
            ],
        ]);

        // Export
        $exportResponse = $this->actingAs($admin)->get(route('admin.document-studio.export', $template));
        $exportResponse->assertOk();
        $exportedData = $exportResponse->json();
        $this->assertSame('Portable Template', $exportedData['name']);

        // Import
        $file = UploadedFile::fake()->createWithContent(
            'imported_template.json',
            json_encode($exportedData)
        );

        $importResponse = $this->actingAs($admin)->post(route('admin.document-studio.import'), [
            'template_file' => $file,
        ]);

        $importResponse->assertRedirect();
        $this->assertDatabaseHas('document_templates', [
            'name' => 'Portable Template (Imported)',
            'document_kind' => 'cotc',
        ]);
    }
}

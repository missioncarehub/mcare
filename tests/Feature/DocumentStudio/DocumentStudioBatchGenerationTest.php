<?php

namespace Tests\Feature\DocumentStudio;

use App\Models\DocumentTemplate;
use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\DocumentStudio\BatchGenerationCoordinator;
use App\Services\DocumentStudio\DocumentStudioService;
use App\Support\RolePermissionMatrix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class DocumentStudioBatchGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RolePermissionMatrix::ensureConfigured();
        Storage::fake('local');
    }

    public function test_batch_generation_coordinator_generates_documents_and_zip(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        RolePermissionMatrix::syncUser($admin);

        $program = TrainingProgram::create(['name' => 'Caregiving NC II', 'code' => 'CG-NCII', 'tuition_fee' => 25000]);
        $batch = TrainingBatch::create([
            'training_program_id' => $program->id,
            'name' => 'Batch 2026',
            'year' => 2026,
            'is_active' => true,
        ]);

        $app1 = EnrollmentApplication::create([
            'user_id' => User::factory()->create(['role' => 'trainee'])->id,
            'training_program_id' => $program->id,
            'training_batch_id' => $batch->id,
            'email' => 'student1@example.com',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'birth_date' => '2000-01-01',
            'gender' => 'Male',
            'contact_number' => '09170000010',
            'schedule_preference' => 'AM',
            'street' => '1 Main St',
            'barangay' => 'Central',
            'city' => 'Pili',
            'province' => 'Camarines Sur',
            'zip_code' => '4418',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'MCARE School',
            'year_graduated' => 2022,
            'status' => EnrollmentApplication::STATUS_APPROVED,
            'learning_status' => EnrollmentApplication::LEARNING_GRADUATED,
        ]);

        $app2 = EnrollmentApplication::create([
            'user_id' => User::factory()->create(['role' => 'trainee'])->id,
            'training_program_id' => $program->id,
            'training_batch_id' => $batch->id,
            'email' => 'student2@example.com',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000011',
            'schedule_preference' => 'AM',
            'street' => '2 Main St',
            'barangay' => 'Central',
            'city' => 'Pili',
            'province' => 'Camarines Sur',
            'zip_code' => '4418',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'MCARE School',
            'year_graduated' => 2022,
            'status' => EnrollmentApplication::STATUS_APPROVED,
            'learning_status' => EnrollmentApplication::LEARNING_GRADUATED,
        ]);

        $studio = app(DocumentStudioService::class);
        $template = $studio->createTemplate([
            'name' => 'Batch Test Certificate',
            'document_kind' => DocumentTemplate::KIND_COTC,
            'engine' => DocumentTemplate::ENGINE_PDFME,
            'training_program_id' => $program->id,
            'initial_state' => [
                'schema_version' => '1.0',
                'engine' => 'pdfme',
                'page' => ['width_mm' => 279.4, 'height_mm' => 215.9],
                'elements' => [
                    ['id' => 'name', 'type' => 'text', 'x' => 20, 'y' => 20, 'width' => 100, 'height' => 10, 'binding' => '{{trainee_name}}'],
                ],
            ],
        ], $admin);

        $version = $studio->publishVersion($template, 'Batch ready v1', true, $admin);

        $coordinator = app(BatchGenerationCoordinator::class);
        $result = $coordinator->generateBatch($version, collect([$app1, $app2]), $admin, true);

        $this->assertSame(2, $result['total']);
        $this->assertSame(2, $result['successful']);
        $this->assertSame(0, $result['failed']);
        $this->assertNotNull($result['zip_path']);
        $this->assertFileExists($result['zip_path']);

        // Inspect zip contents
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($result['zip_path']));
        $this->assertSame(2, $zip->numFiles);
        $zip->close();

        // Cleanup
        @unlink($result['zip_path']);
    }

    public function test_admin_can_call_batch_generate_endpoint(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        RolePermissionMatrix::syncUser($admin);

        $program = TrainingProgram::create(['name' => 'Caregiving NC II', 'code' => 'CG-NCII', 'tuition_fee' => 25000]);
        $batch = TrainingBatch::create([
            'training_program_id' => $program->id,
            'name' => 'Batch 2026',
            'year' => 2026,
            'is_active' => true,
        ]);

        EnrollmentApplication::create([
            'user_id' => User::factory()->create(['role' => 'trainee'])->id,
            'training_program_id' => $program->id,
            'training_batch_id' => $batch->id,
            'email' => 'student3@example.com',
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000012',
            'schedule_preference' => 'AM',
            'street' => '3 Main St',
            'barangay' => 'Central',
            'city' => 'Pili',
            'province' => 'Camarines Sur',
            'zip_code' => '4418',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'MCARE School',
            'year_graduated' => 2022,
            'status' => EnrollmentApplication::STATUS_APPROVED,
            'learning_status' => EnrollmentApplication::LEARNING_GRADUATED,
        ]);

        $studio = app(DocumentStudioService::class);
        $template = $studio->createTemplate([
            'name' => 'Endpoint Batch Certificate',
            'document_kind' => DocumentTemplate::KIND_COTC,
            'engine' => DocumentTemplate::ENGINE_PDFME,
            'training_program_id' => $program->id,
            'initial_state' => [
                'schema_version' => '1.0',
                'engine' => 'pdfme',
                'page' => ['width_mm' => 279.4, 'height_mm' => 215.9],
                'elements' => [
                    ['id' => 'name', 'type' => 'text', 'x' => 20, 'y' => 20, 'width' => 100, 'height' => 10, 'binding' => '{{trainee_name}}'],
                ],
            ],
        ], $admin);

        $studio->publishVersion($template, 'Batch v1', true, $admin);

        $response = $this->actingAs($admin)->post(route('admin.document-studio.batch-generate', $template), [
            'training_batch_id' => $batch->id,
            'schedule' => 'AM',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('application/zip', (string) $response->headers->get('Content-Type'));
    }
}

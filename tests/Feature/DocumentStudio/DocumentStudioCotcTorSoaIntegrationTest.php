<?php

namespace Tests\Feature\DocumentStudio;

use App\Models\CompetencyUnit;
use App\Models\DocumentTemplate;
use App\Models\EnrollmentApplication;
use App\Models\OfficialDocument;
use App\Models\PaymentTransaction;
use App\Models\TraineeCompetencyRecord;
use App\Models\TrainingBatch;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\DocumentStudio\DocumentStudioService;
use App\Services\OfficialDocumentManager;
use App\Services\StatementOfAccountPdfService;
use App\Support\RolePermissionMatrix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentStudioCotcTorSoaIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RolePermissionMatrix::ensureConfigured();
        Storage::fake('local');
    }

    public function test_cotc_generation_preserves_eligibility_and_locks_competencies(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        RolePermissionMatrix::syncUser($admin);

        $program = TrainingProgram::create(['name' => 'Caregiving NC II', 'code' => 'CG-NCII', 'tuition_fee' => 25000]);
        $batch = TrainingBatch::create([
            'training_program_id' => $program->id,
            'name' => 'Batch 2026',
            'year' => 2026,
            'is_active' => true,
            'training_starts_at' => now()->subMonths(3),
            'training_ends_at' => now()->subDay(),
        ]);

        $traineeUser = User::factory()->create(['role' => 'trainee']);
        $application = EnrollmentApplication::create([
            'user_id' => $traineeUser->id,
            'training_program_id' => $program->id,
            'training_batch_id' => $batch->id,
            'email' => 'graduate@example.com',
            'first_name' => 'Luz',
            'middle_name' => 'Cruz',
            'last_name' => 'Dela Torre',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000002',
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
            'payment_status' => EnrollmentApplication::PAYMENT_PAID,
            'learning_started_at' => now()->subMonths(3),
            'graduated_at' => now()->subDay(),
        ]);

        $unit = CompetencyUnit::create([
            'training_program_id' => $program->id,
            'program_code' => 'CG-NCII',
            'code' => 'HCS323301',
            'title' => 'Provide Care and Support',
            'category' => 'core',
            'is_tor_included' => true,
            'sort_order' => 1,
        ]);

        $competencyRecord = TraineeCompetencyRecord::create([
            'enrollment_application_id' => $application->id,
            'competency_unit_id' => $unit->id,
            'status' => 'competent',
            'tor_grade' => 1.25,
            'locked_at' => null,
        ]);

        $docManager = app(OfficialDocumentManager::class);

        // Generate COTC
        $generated = $docManager->generate($application, OfficialDocument::TYPE_COTC, $admin);

        $this->assertSame(OfficialDocument::STATUS_GENERATED, $generated->status);
        $this->assertNotNull($generated->file_path);
        Storage::disk('local')->assertExists($generated->file_path);

        // Verify competency was locked
        $competencyRecord->refresh();
        $this->assertNotNull($competencyRecord->locked_at);

        // Release COTC
        $released = $docManager->releaseCotc($generated, $admin);
        $this->assertSame(OfficialDocument::STATUS_RELEASED, $released->status);
        $this->assertSame($admin->id, $released->released_by_id);
    }

    public function test_custom_activated_studio_template_is_pinned_on_new_official_document(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        RolePermissionMatrix::syncUser($admin);

        $program = TrainingProgram::create(['name' => 'Caregiving NC II', 'code' => 'CG-NCII', 'tuition_fee' => 25000]);
        $batch = TrainingBatch::create([
            'training_program_id' => $program->id,
            'name' => 'Batch 2026',
            'year' => 2026,
            'is_active' => true,
            'training_starts_at' => now()->subMonths(3),
            'training_ends_at' => now()->subDay(),
        ]);

        $application = EnrollmentApplication::create([
            'user_id' => User::factory()->create(['role' => 'trainee'])->id,
            'training_program_id' => $program->id,
            'training_batch_id' => $batch->id,
            'email' => 'custom.studio@example.com',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000003',
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
            'payment_status' => EnrollmentApplication::PAYMENT_PAID,
            'learning_started_at' => now()->subMonths(3),
            'graduated_at' => now()->subDay(),
        ]);

        // Create and activate custom studio template for COTC
        $studio = app(DocumentStudioService::class);
        $template = $studio->createTemplate([
            'name' => 'Special Custom COTC Design',
            'document_kind' => DocumentTemplate::KIND_COTC,
            'engine' => DocumentTemplate::ENGINE_PDFME,
            'training_program_id' => $program->id,
            'initial_state' => [
                'schema_version' => '1.0',
                'engine' => 'pdfme',
                'page' => ['width_mm' => 279.4, 'height_mm' => 215.9],
                'elements' => [
                    ['id' => 'name', 'type' => 'text', 'x' => 30, 'y' => 50, 'width' => 150, 'height' => 12, 'binding' => '{{trainee_name}}'],
                ],
            ],
        ], $admin);

        $version = $studio->publishVersion($template, 'Custom active design', true, $admin);

        // Generate document
        $docManager = app(OfficialDocumentManager::class);
        $document = $docManager->generate($application, OfficialDocument::TYPE_COTC, $admin);

        $this->assertSame(OfficialDocument::STATUS_GENERATED, $document->status);
        $this->assertSame($version->id, $document->document_template_version_id);
    }

    public function test_statement_of_account_service_generates_valid_pdf_with_authoritative_ledger(): void
    {
        $program = TrainingProgram::create(['name' => 'Caregiving NC II', 'code' => 'CG-NCII', 'tuition_fee' => 25000]);
        $batch = TrainingBatch::create(['training_program_id' => $program->id, 'name' => 'Batch 2026', 'year' => 2026, 'is_active' => true]);

        $user = User::factory()->create(['name' => 'Pedro Reyes', 'role' => 'trainee']);
        $application = EnrollmentApplication::create([
            'user_id' => $user->id,
            'training_program_id' => $program->id,
            'training_batch_id' => $batch->id,
            'email' => 'pedro@example.com',
            'first_name' => 'Pedro',
            'last_name' => 'Reyes',
            'birth_date' => '2000-01-01',
            'gender' => 'Male',
            'contact_number' => '09170000004',
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
            'payment_status' => EnrollmentApplication::PAYMENT_PARTIALLY_PAID,
            'owwa_deduction' => 5000,
        ]);

        PaymentTransaction::create([
            'user_id' => $user->id,
            'enrollment_application_id' => $application->id,
            'amount' => 7500,
            'status' => PaymentTransaction::STATUS_VERIFIED,
            'or_number' => 'OR-2026-0044',
            'verified_at' => now(),
        ]);

        $soaService = app(StatementOfAccountPdfService::class);
        $pdfBinary = $soaService->generate($application);

        $this->assertNotEmpty($pdfBinary);
        $this->assertStringStartsWith('%PDF-', $pdfBinary);
    }
}

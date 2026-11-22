<?php

namespace Tests\Feature\DocumentStudio;

use App\Models\CompetencyUnit;
use App\Models\EnrollmentApplication;
use App\Models\PaymentTransaction;
use App\Models\TraineeCompetencyRecord;
use App\Models\TrainingBatch;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\DocumentStudio\DocumentStudioRenderer;
use App\Services\DocumentStudio\PlaceholderRegistry;
use App\Support\RolePermissionMatrix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentStudioRenderingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RolePermissionMatrix::ensureConfigured();
    }

    public function test_placeholder_registry_provides_allowlisted_definitions(): void
    {
        $defs = PlaceholderRegistry::definitions();

        $this->assertArrayHasKey('trainee_name', $defs);
        $this->assertArrayHasKey('training_program', $defs);
        $this->assertArrayHasKey('batch_name', $defs);
        $this->assertArrayHasKey('completion_date', $defs);
        $this->assertArrayHasKey('certificate_number', $defs);
        $this->assertArrayHasKey('trainer_name', $defs);
        $this->assertArrayHasKey('qr_code', $defs);
        $this->assertArrayHasKey('total_program_fee', $defs);
        $this->assertArrayHasKey('outstanding_balance', $defs);
    }

    public function test_placeholder_registry_resolves_authoritative_values_for_enrollment(): void
    {
        $program = TrainingProgram::create([
            'name' => 'Caregiving NC II',
            'code' => 'CG-NCII',
            'total_program_fee' => 25000,
        ]);

        $batch = TrainingBatch::create([
            'training_program_id' => $program->id,
            'name' => 'Batch 5',
            'year' => 2026,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['name' => 'Ana Santos', 'role' => 'trainee']);

        $application = EnrollmentApplication::create([
            'user_id' => $user->id,
            'training_program_id' => $program->id,
            'training_batch_id' => $batch->id,
            'email' => 'ana.santos@example.com',
            'first_name' => 'Ana',
            'middle_name' => 'Cruz',
            'last_name' => 'Santos',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000001',
            'schedule_preference' => 'AM',
            'street' => '123 Main St',
            'barangay' => 'San Isidro',
            'city' => 'Pili',
            'province' => 'Camarines Sur',
            'zip_code' => '4418',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'MCARE School',
            'year_graduated' => 2022,
            'status' => EnrollmentApplication::STATUS_APPROVED,
            'learning_status' => EnrollmentApplication::LEARNING_GRADUATED,
            'graduated_at' => now(),
            'owwa_deduction' => 5000,
            'total_program_fee' => 25000,
        ]);
        // Later catalog changes must not change this enrollment's saved assessment.
        $program->update(['tuition_fee' => 30000]);

        // Add verified payment transaction
        PaymentTransaction::create([
            'user_id' => $user->id,
            'enrollment_application_id' => $application->id,
            'amount' => 10000,
            'status' => PaymentTransaction::STATUS_VERIFIED,
            'payment_method' => 'onsite_cash',
            'or_number' => 'OR-2026-999',
            'verified_at' => now(),
        ]);

        // Add core competency unit with tor grade
        $unit = CompetencyUnit::create([
            'training_program_id' => $program->id,
            'program_code' => 'CG-NCII',
            'code' => 'HCS323301',
            'title' => 'Provide Care and Support to Infants',
            'category' => 'core',
            'is_tor_included' => true,
            'sort_order' => 1,
        ]);

        TraineeCompetencyRecord::create([
            'enrollment_application_id' => $application->id,
            'competency_unit_id' => $unit->id,
            'status' => 'competent',
            'tor_grade' => 1.25,
        ]);

        // 1. Resolve for COTC
        $cotcValues = PlaceholderRegistry::resolveForEnrollment($application, 'cotc');
        $this->assertSame('ANA CRUZ SANTOS', $cotcValues['trainee_name']);
        $this->assertSame('Caregiving NC II', $cotcValues['training_program']);
        $this->assertStringContainsString('Batch 5', $cotcValues['batch_name']);

        // 2. Resolve for TOR
        $torValues = PlaceholderRegistry::resolveForEnrollment($application, 'tor');
        $this->assertNotEmpty($torValues['tor_competencies']);
        $this->assertSame('HCS323301', $torValues['tor_competencies'][0]['code']);
        $this->assertSame('1.25', $torValues['tor_competencies'][0]['grade']);

        // 3. Resolve for SOA
        $soaValues = PlaceholderRegistry::resolveForEnrollment($application, 'soa');
        $this->assertSame('PHP 20,000.00', $soaValues['total_program_fee']); // 25000 - 5000 OWWA
        $this->assertSame('PHP 10,000.00', $soaValues['total_paid']);
        $this->assertSame('PHP 10,000.00', $soaValues['outstanding_balance']);
        $this->assertCount(1, $soaValues['soa_payments']);
        $this->assertSame('OR-2026-999', $soaValues['soa_payments'][0]['or_number']);
    }

    public function test_document_studio_renderer_generates_valid_pdf_via_node(): void
    {
        $renderer = app(DocumentStudioRenderer::class);

        $payload = [
            'engine' => 'pdfme',
            'page' => ['width_mm' => 210, 'height_mm' => 297],
            'elements' => [
                [
                    'id' => 'name_field',
                    'type' => 'text',
                    'x' => 20,
                    'y' => 30,
                    'width' => 120,
                    'height' => 10,
                    'fontSize' => 16,
                    'binding' => '{{trainee_name}}',
                ],
            ],
            'values' => [
                'trainee_name' => 'JUAN DELA CRUZ',
            ],
        ];

        $pdfBinary = $renderer->renderPayloadToPdf($payload);

        $this->assertNotEmpty($pdfBinary);
        $this->assertStringStartsWith('%PDF-', $pdfBinary);
        $this->assertGreaterThan(500, strlen($pdfBinary));
    }
}

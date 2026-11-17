<?php

namespace Tests\Feature;

use App\Models\CertificateTemplate;
use App\Models\EnrollmentApplication;
use App\Models\OfficialDocument;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\TrainingBatch;
use App\Models\TrainingModule;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\FpdfOfficialDocumentRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProgramSpecificCertificateAndAdminQuizViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_module_quizzes_view_shows_summary_from_trainer_without_create_quiz_button(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainer = User::factory()->create(['role' => 'trainer']);
        $batch = TrainingBatch::create([
            'name' => 'Batch 2026-A',
            'year' => 2026,
            'training_starts_at' => '2026-02-01',
            'training_ends_at' => '2026-08-01',
        ]);

        $module = TrainingModule::create([
            'training_batch_id' => $batch->id,
            'trainer_id' => $trainer->id,
            'title' => 'Infection Prevention and Control',
            'description' => 'Comprehensive infection prevention protocols.',
            'file_path' => 'training-modules/infection-control.pdf',
            'original_file_name' => 'infection-control.pdf',
            'mime_type' => 'application/pdf',
            'order' => 1,
            'requires_evaluation' => true,
        ]);

        $quiz = Quiz::create([
            'training_module_id' => $module->id,
            'training_batch_id' => $batch->id,
            'trainer_id' => $trainer->id,
            'title' => 'Hand Hygiene Standard Assessment',
            'passing_score_percent' => 80,
            'time_limit_minutes' => 30,
            'is_published' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.learning.modules.quizzes', $module));

        $response->assertOk();
        // Assert creation form and button are removed
        $response->assertDontSee('Create quiz');
        $response->assertDontSee('New quiz');
        // Assert trainer summary and results action are displayed
        $response->assertSee('Hand Hygiene Standard Assessment');
        $response->assertSee('View Results');
        $response->assertSee(route('admin.learning.quizzes.results', $quiz));
    }

    public function test_program_specific_certificate_templates_can_be_saved_activated_and_resolved(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $programA = TrainingProgram::create([
            'name' => 'Caregiving NC II',
            'code' => 'CG-NC2',
            'is_active' => true,
        ]);
        $programB = TrainingProgram::create([
            'name' => 'Healthcare Services NC II',
            'code' => 'HCS-NC2',
            'is_active' => true,
        ]);

        // Default template (no program) - Version 1
        $defaultTemplate = CertificateTemplate::create([
            'type' => OfficialDocument::TYPE_COTC,
            'training_program_id' => null,
            'version' => 1,
            'is_active' => true,
            'title' => 'Default MCARE Completion Certificate',
            'body' => 'Default Body',
            'paper_size' => 'letter',
            'orientation' => 'landscape',
            'use_official_artwork' => true,
        ]);

        // Program A template - Version 2
        $templateA = CertificateTemplate::create([
            'type' => OfficialDocument::TYPE_COTC,
            'training_program_id' => $programA->id,
            'version' => 2,
            'is_active' => true,
            'title' => 'Caregiving NC II Specialized Certificate',
            'body' => 'Awarded for completing Caregiving NC II',
            'paper_size' => 'letter',
            'orientation' => 'landscape',
            'use_official_artwork' => false,
        ]);

        // Program B template - Version 3
        $templateB = CertificateTemplate::create([
            'type' => OfficialDocument::TYPE_COTC,
            'training_program_id' => $programB->id,
            'version' => 3,
            'is_active' => true,
            'title' => 'Healthcare Services Elite Certificate',
            'body' => 'Awarded for completing Healthcare Services NC II',
            'paper_size' => 'a4',
            'orientation' => 'landscape',
            'use_official_artwork' => false,
        ]);

        // Verify activeFor resolves program-specific template
        $resolvedA = CertificateTemplate::activeFor(OfficialDocument::TYPE_COTC, $programA->id);
        $this->assertNotNull($resolvedA);
        $this->assertSame($templateA->id, $resolvedA->id);
        $this->assertSame('Caregiving NC II Specialized Certificate', $resolvedA->title);

        $resolvedB = CertificateTemplate::activeFor(OfficialDocument::TYPE_COTC, $programB->id);
        $this->assertNotNull($resolvedB);
        $this->assertSame($templateB->id, $resolvedB->id);
        $this->assertSame('Healthcare Services Elite Certificate', $resolvedB->title);

        // Verify unknown program falls back to default template
        $resolvedFallback = CertificateTemplate::activeFor(OfficialDocument::TYPE_COTC, 9999);
        $this->assertNotNull($resolvedFallback);
        $this->assertSame($defaultTemplate->id, $resolvedFallback->id);
    }

    public function test_admin_can_save_program_specific_certificate_template_via_controller(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = TrainingProgram::create([
            'name' => 'Caregiving NC II',
            'code' => 'CAREGIVING-NC2',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.learning.certificate-templates.store'), [
                'type' => OfficialDocument::TYPE_COTC,
                'training_program_id' => $program->id,
                'title' => 'Caregiving NC II Dedicated Certificate',
                'body' => 'Cleared for Caregiving on {{completion_date}}.',
                'paper_size' => 'letter',
                'orientation' => 'landscape',
                'use_official_artwork' => '1',
                'signatory_name' => 'Registrar Test',
                'signatory_role' => 'Registrar',
                'trainee_name_y' => 45.5,
                'trainee_name_size' => 26,
                'trainee_name_align' => 'C',
            ]);

        $response->assertRedirect(route('admin.learning.certificate-templates', ['program_id' => $program->id]));
        $response->assertSessionHas('saved');

        $saved = CertificateTemplate::activeFor(OfficialDocument::TYPE_COTC, $program->id);
        $this->assertNotNull($saved);
        $this->assertSame($program->id, $saved->training_program_id);
        $this->assertSame('Caregiving NC II Dedicated Certificate', $saved->title);
        $this->assertTrue($saved->is_active);
    }

    public function test_admin_tor_template_edit_view_shows_transcript_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = TrainingProgram::create([
            'name' => 'Caregiving NC II',
            'code' => 'CG-NC2',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.learning.certificate-templates.edit', [
                'type' => OfficialDocument::TYPE_TOR,
                'program_id' => $program->id,
            ]));

        $response->assertOk();
        $response->assertSee('Manage Caregiving NC II — Transcript of Records');
        $response->assertSee('Document Heading / Title');
        $response->assertSee('OFFICIAL TRANSCRIPT OF RECORDS');
        $response->assertSee('Official Transcript Remarks');
        $response->assertSee('Certified by (Registrar)');
        $response->assertSee('Prepared by (Trainer)');
        $response->assertSee('A4 (210 × 297 mm)');
        $response->assertSee('Included Competency Units (TESDA Matrix)');
    }

    public function test_admin_can_save_program_specific_tor_template_and_it_renders_correctly(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = TrainingProgram::create([
            'name' => 'Caregiving Elderly Services NC II',
            'code' => 'CES-NC2',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.learning.certificate-templates.store'), [
                'type' => OfficialDocument::TYPE_TOR,
                'training_program_id' => $program->id,
                'title' => 'ACADEMIC TRANSCRIPT OF COMPETENCY RECORDS',
                'body' => 'Graduated with high competency honors.',
                'paper_size' => 'a4',
                'orientation' => 'portrait',
                'signatory_name' => 'Amina Dela Cruz',
                'signatory_role' => 'Registrar Officer',
                'second_signatory_name' => 'Paolo Reyes',
                'second_signatory_role' => 'Master Trainer',
                'activate_immediately' => '1',
            ]);

        $response->assertRedirect(route('admin.learning.certificate-templates', ['program_id' => $program->id]));
        $response->assertSessionHas('saved');

        $savedTor = CertificateTemplate::activeFor(OfficialDocument::TYPE_TOR, $program->id);
        $this->assertNotNull($savedTor);
        $this->assertSame($program->id, $savedTor->training_program_id);
        $this->assertSame('ACADEMIC TRANSCRIPT OF COMPETENCY RECORDS', $savedTor->title);
        $this->assertSame('Graduated with high competency honors.', $savedTor->body);
        $this->assertSame('Amina Dela Cruz', $savedTor->signatory_name);
        $this->assertSame('Paolo Reyes', $savedTor->second_signatory_name);

        // Verify that rendering an official TOR document uses the program-specific template and program name
        $batch = new TrainingBatch([
            'name' => 'Batch 2026-CES',
            'training_program_id' => $program->id,
            'year' => 2026,
            'training_starts_at' => '2026-02-01',
            'training_ends_at' => '2026-08-01',
        ]);
        $application = new EnrollmentApplication([
            'user_id' => $admin->id,
            'enrollment_number' => 'MCE-2026-CES-001',
            'email' => 'juana@example.com',
            'training_program_id' => $program->id,
            'first_name' => 'Juana',
            'last_name' => 'Batumbakal',
            'status' => EnrollmentApplication::STATUS_APPROVED,
            'learning_status' => EnrollmentApplication::LEARNING_GRADUATED,
        ]);
        $application->setRelation('batch', $batch);
        $application->setRelation('trainingProgram', $program);
        $application->setRelation('competencyRecords', collect());

        $doc = new OfficialDocument([
            'type' => OfficialDocument::TYPE_TOR,
            'version' => 1,
            'document_number' => 'MCARE-TOR-CES-001',
            'generated_at' => now(),
        ]);
        $doc->setRelation('application', $application);

        $pdfContent = app(FpdfOfficialDocumentRenderer::class)->render($doc);
        $this->assertStringStartsWith('%PDF-', $pdfContent);
        $this->assertTrue($this->pdfContains($pdfContent, 'ACADEMIC TRANSCRIPT OF COMPETENCY RECORDS'));
        $this->assertTrue($this->pdfContains($pdfContent, 'Graduated with high competency honors.'));
        $this->assertTrue($this->pdfContains($pdfContent, 'AMINA DELA CRUZ'));
        $this->assertTrue($this->pdfContains($pdfContent, 'CAREGIVING ELDERLY SERVICES NC II'));
    }

    public function test_admin_can_upload_custom_certificate_artwork_and_render_program_specific_cotc(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $program = TrainingProgram::create([
            'name' => 'Caregiving NC III Specialist',
            'code' => 'CG-NC3',
            'is_active' => true,
        ]);

        $png = tempnam(sys_get_temp_dir(), 'mcare-prog-cotc');
        $image = imagecreatetruecolor(200, 100);
        imagefilledrectangle($image, 0, 0, 200, 100, imagecolorallocate($image, 255, 230, 240));
        imagepng($image, $png);
        imagedestroy($image);

        $response = $this->actingAs($admin)
            ->post(route('admin.learning.certificate-templates.store'), [
                'type' => OfficialDocument::TYPE_COTC,
                'training_program_id' => $program->id,
                'title' => 'Caregiving NC III Diploma of Excellence',
                'body' => 'Conferred upon {{trainee_name}} on {{completion_date}}.',
                'paper_size' => 'letter',
                'orientation' => 'landscape',
                'background' => new UploadedFile($png, 'nc3-custom-artwork.png', 'image/png', null, true),
                'trainee_name_y' => 52.5,
                'trainee_name_size' => 28,
                'trainee_name_align' => 'C',
                'include_cert_no' => '1',
                'include_date' => '1',
                'activate_immediately' => '1',
            ]);

        $response->assertRedirect(route('admin.learning.certificate-templates', ['program_id' => $program->id]));
        $response->assertSessionHas('saved');

        $activeTemplate = CertificateTemplate::activeFor(OfficialDocument::TYPE_COTC, $program->id);
        $this->assertNotNull($activeTemplate);
        $this->assertSame($program->id, $activeTemplate->training_program_id);
        $this->assertTrue($activeTemplate->is_active);
        $this->assertFalse($activeTemplate->use_official_artwork);
        $this->assertNotNull($activeTemplate->background_path);

        $layout = $activeTemplate->resolvedFieldLayout();
        $this->assertEquals(52.5, $layout['trainee_name']['y']);
        $this->assertEquals(28, $layout['trainee_name']['size']);

        // Test preview endpoint also returns the uploaded template as a valid PDF
        $previewResponse = $this->actingAs($admin)
            ->get(route('admin.learning.certificate-templates.preview', [
                'type' => OfficialDocument::TYPE_COTC,
                'program_id' => $program->id,
            ]));
        $previewResponse->assertOk();
        $previewResponse->assertHeader('content-type', 'application/pdf');

        // Now test generating a real COTC document for an NC III graduate
        $batch = new TrainingBatch([
            'name' => 'Batch NC3-2026',
            'training_program_id' => $program->id,
            'year' => 2026,
            'training_starts_at' => '2026-03-01',
            'training_ends_at' => '2026-09-01',
        ]);
        $application = new EnrollmentApplication([
            'user_id' => $admin->id,
            'enrollment_number' => 'MCE-2026-NC3-001',
            'email' => 'student.nc3@example.com',
            'training_program_id' => $program->id,
            'first_name' => 'Katalina',
            'last_name' => 'Dimagiba',
            'status' => EnrollmentApplication::STATUS_APPROVED,
            'learning_status' => EnrollmentApplication::LEARNING_GRADUATED,
        ]);
        $application->setRelation('batch', $batch);
        $application->setRelation('trainingProgram', $program);
        $application->setRelation('competencyRecords', collect());

        $doc = new OfficialDocument([
            'type' => OfficialDocument::TYPE_COTC,
            'version' => 1,
            'document_number' => 'MCARE-COTC-NC3-001',
            'generated_at' => now(),
        ]);
        $doc->setRelation('application', $application);

        $pdf = app(FpdfOfficialDocumentRenderer::class)->render($doc);
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertTrue($this->pdfContains($pdf, 'KATALINA DIMAGIBA'));
        $this->assertTrue($this->pdfContains($pdf, 'Enrollment ID: MCE-2026-NC3-001'));
        $this->assertTrue($this->pdfContains($pdf, 'MCARE-COTC-NC3-001'));

        @unlink($png);
    }

    private function pdfContains(string $pdf, string $needle): bool
    {
        if (str_contains($pdf, $needle)) {
            return true;
        }

        if (preg_match_all('/stream\r?\n(.+?)\r?\nendstream/s', $pdf, $matches)) {
            foreach ($matches[1] as $stream) {
                $decoded = @gzuncompress($stream);
                if (is_string($decoded) && str_contains($decoded, $needle)) {
                    return true;
                }
            }
        }

        return false;
    }
}

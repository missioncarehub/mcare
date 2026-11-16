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
}

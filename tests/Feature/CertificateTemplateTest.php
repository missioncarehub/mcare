<?php

namespace Tests\Feature;

use App\Models\CertificateTemplate;
use App\Models\CompetencyUnit;
use App\Models\EnrollmentApplication;
use App\Models\OfficialDocument;
use App\Models\TraineeCompetencyRecord;
use App\Models\TrainingBatch;
use App\Models\User;
use App\Services\FpdfOfficialDocumentRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_a_template_version_and_restore_an_older_one(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.learning.certificates'))
            ->assertOk()
            ->assertSee('Certificate templates');

        $this->actingAs($admin)
            ->get(route('admin.learning.certificate-templates'))
            ->assertOk()
            ->assertSee('Certificate templates')
            ->assertSee('Live preview')
            ->assertSee('{{trainee_name}}', false);

        $this->actingAs($admin)
            ->post(route('admin.learning.certificate-templates.store'), [
                'type' => OfficialDocument::TYPE_COTC,
                'title' => 'Board copy of completion',
                'body' => 'Cleared for {{program}} on {{completion_date}}.',
                'paper_size' => 'letter',
                'orientation' => 'landscape',
                'use_official_artwork' => '1',
                'signatory_name' => 'Amina Dela Cruz',
                'signatory_role' => 'Registrar',
                'second_signatory_name' => 'Paolo Reyes',
                'second_signatory_role' => 'Trainer',
            ])
            ->assertRedirect(route('admin.learning.certificate-templates'))
            ->assertSessionHas('saved');

        $first = CertificateTemplate::query()->sole();
        $this->assertSame(1, $first->version);
        $this->assertTrue($first->is_active);

        $this->actingAs($admin)
            ->post(route('admin.learning.certificate-templates.store'), [
                'type' => OfficialDocument::TYPE_COTC,
                'title' => 'Second certificate heading',
                'body' => 'Updated statement for {{trainee_name}}.',
                'paper_size' => 'letter',
                'orientation' => 'landscape',
                'use_official_artwork' => '1',
                'signatory_name' => 'Second Registrar',
                'signatory_role' => 'Registrar',
            ])
            ->assertRedirect();

        $this->assertSame(2, CertificateTemplate::activeFor(OfficialDocument::TYPE_COTC)?->version);

        $this->actingAs($admin)
            ->patch(route('admin.learning.certificate-templates.activate', $first))
            ->assertRedirect()
            ->assertSessionHas('saved');

        $this->assertSame(1, CertificateTemplate::activeFor(OfficialDocument::TYPE_COTC)?->version);
        $this->assertTrue($first->fresh()->is_active);
        $this->assertFalse((bool) CertificateTemplate::query()->where('version', 2)->value('is_active'));
    }

    public function test_the_next_cotc_and_tor_use_the_saved_title_and_signatory(): void
    {
        CertificateTemplate::create([
            'type' => OfficialDocument::TYPE_COTC,
            'version' => 1,
            'is_active' => true,
            'title' => 'Board copy of completion',
            'body' => 'Cleared for {{program}} on {{completion_date}}.',
            'paper_size' => 'letter',
            'orientation' => 'landscape',
            'use_official_artwork' => true,
            'signatory_name' => 'Amina Dela Cruz',
            'signatory_role' => 'Registrar',
            'second_signatory_name' => 'Paolo Reyes',
            'second_signatory_role' => 'Trainer',
        ]);
        CertificateTemplate::create([
            'type' => OfficialDocument::TYPE_TOR,
            'version' => 1,
            'is_active' => true,
            'title' => 'Registrar copy of the transcript',
            'body' => 'Issued for {{trainee_name}}.',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'use_official_artwork' => false,
            'signatory_name' => 'Amina Dela Cruz',
            'signatory_role' => 'Registrar',
            'second_signatory_name' => 'Paolo Reyes',
            'second_signatory_role' => 'Lead Trainer',
        ]);

        $renderer = app(FpdfOfficialDocumentRenderer::class);
        $cotc = $renderer->render($this->document(OfficialDocument::TYPE_COTC));
        $tor = $renderer->render($this->document(OfficialDocument::TYPE_TOR));

        $this->assertTrue($this->pdfContains($cotc, 'Board copy of completion'));
        $this->assertTrue($this->pdfContains($cotc, 'Cleared for Caregiving NC II'));
        $this->assertTrue($this->pdfContains($cotc, 'Amina Dela Cruz'));
        $this->assertTrue($this->pdfContains($cotc, 'MARIA REYES SANTOS'));
        $this->assertTrue($this->pdfContains($cotc, 'Enrollment ID: MCE-2026-SAMPLE'));
        $this->assertTrue($this->pdfContains($cotc, 'MCARE OFFICIAL'));

        $this->assertTrue($this->pdfContains($tor, 'Registrar copy of the transcript'));
        $this->assertTrue($this->pdfContains($tor, 'Issued for Maria Reyes Santos'));
        $this->assertTrue($this->pdfContains($tor, 'AMINA DELA CRUZ'));
        $this->assertTrue($this->pdfContains($tor, 'HCS323301'));
        $this->assertTrue($this->pdfContains($tor, 'COMPETENT'));
        $this->assertTrue($this->pdfContains($tor, 'Enrollment ID: MCE-2026-SAMPLE'));
        $this->assertTrue($this->pdfContains($tor, 'MCARE OFFICIAL'));
    }

    public function test_a_managed_cotc_can_change_paper_size_without_removing_the_verification_line(): void
    {
        CertificateTemplate::create([
            'type' => OfficialDocument::TYPE_COTC,
            'version' => 1,
            'is_active' => true,
            'title' => 'Short course certificate',
            'body' => 'Completed {{program}}.',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'use_official_artwork' => false,
            'signatory_name' => 'Amina Dela Cruz',
            'signatory_role' => 'Registrar',
        ]);

        $pdf = app(FpdfOfficialDocumentRenderer::class)->render($this->document(OfficialDocument::TYPE_COTC));

        $this->assertSame(1, preg_match('/\/MediaBox\s*\[\s*0(?:\.00)?\s+0(?:\.00)?\s+([\d.]+)\s+([\d.]+)\s*\]/', $pdf, $matches));
        $this->assertGreaterThan((float) $matches[1], (float) $matches[2]);
        $this->assertTrue($this->pdfContains($pdf, 'Short course certificate'));
        $this->assertTrue($this->pdfContains($pdf, 'MCARE-COTC-2026-00002-V1'));
    }

    public function test_preview_returns_the_active_template_as_a_pdf(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        CertificateTemplate::create([
            'type' => OfficialDocument::TYPE_TOR,
            'version' => 3,
            'is_active' => true,
            'title' => 'Preview transcript heading',
            'body' => '',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'use_official_artwork' => false,
            'signatory_name' => 'Preview Registrar',
            'signatory_role' => 'Registrar',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.learning.certificate-templates.preview', OfficialDocument::TYPE_TOR));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertTrue($this->pdfContains($response->getContent(), 'Preview transcript heading'));
        $this->assertTrue($this->pdfContains($response->getContent(), 'PREVIEW REGISTRAR'));
    }

    public function test_a_trainer_cannot_open_the_template_manager(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);

        $this->actingAs($trainer)
            ->get(route('admin.learning.certificate-templates'))
            ->assertForbidden();
    }

    private function document(string $type): OfficialDocument
    {
        $batch = new TrainingBatch([
            'name' => 'Batch 1',
            'year' => 2026,
            'training_starts_at' => '2026-01-15',
            'training_ends_at' => '2026-07-18',
        ]);
        $application = new EnrollmentApplication([
            'enrollment_number' => 'MCE-2026-SAMPLE',
            'program' => 'Caregiving NC II',
            'first_name' => 'Maria',
            'middle_name' => 'Reyes',
            'last_name' => 'Santos',
            'street' => '1 Training Street',
            'barangay' => 'Central',
            'city' => 'Pili',
            'province' => 'Camarines Sur',
            'zip_code' => '4418',
            'school_name' => 'MCARE School',
            'classification' => 'Student',
        ]);
        $unit = new CompetencyUnit([
            'code' => 'HCS323301',
            'title' => 'Provide Care and Support to Infants and Toddlers',
            'is_tor_included' => true,
            'sort_order' => 1,
        ]);
        $record = new TraineeCompetencyRecord([
            'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
            'tor_grade' => 1.30,
        ]);
        $record->setRelation('unit', $unit);
        $application->setRelation('batch', $batch);
        $application->setRelation('competencyRecords', collect([$record]));

        $document = new OfficialDocument([
            'type' => $type,
            'version' => 1,
            'document_number' => $type === OfficialDocument::TYPE_COTC
                ? 'MCARE-COTC-2026-00002-V1'
                : 'MCARE-TOR-2026-00002-V1',
            'generated_at' => '2026-07-18 09:00:00',
        ]);
        $document->setRelation('application', $application);

        return $document;
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

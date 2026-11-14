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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            ->assertSee('Default official template')
            ->assertSee('Manage template')
            ->assertSee(route('admin.learning.certificate-templates.edit', 'cotc'), false)
            ->assertSee('REGISTRAR')
            ->assertSee('NOTHING FOLLOWS')
            ->assertSee('style="aspect-ratio: 210 / 297"', false);

        $this->actingAs($admin)
            ->get(route('admin.learning.certificate-templates.edit', 'cotc'))
            ->assertOk()
            ->assertSee('Manage Certificate of Training Completion')
            ->assertSee('Import a pre-made template')
            ->assertSee('Imported template')
            ->assertSee('Import a finished design to preview it here.')
            ->assertDontSee('Live preview')
            ->assertDontSee('Drag a label')
            ->assertSee('style="aspect-ratio: 11 / 8.5"', false)
            ->assertDontSee('Use the official certificate artwork');

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
        $this->assertFalse($this->pdfContains($cotc, 'MCARE OFFICIAL'));

        $this->assertTrue($this->pdfContains($tor, 'Registrar copy of the transcript'));
        $this->assertTrue($this->pdfContains($tor, 'Issued for Maria Reyes Santos'));
        $this->assertTrue($this->pdfContains($tor, 'AMINA DELA CRUZ'));
        $this->assertTrue($this->pdfContains($tor, 'HCS323301'));
        $this->assertTrue($this->pdfContains($tor, 'COMPETENT'));
        $this->assertTrue($this->pdfContains($tor, 'Document no. MCARE-TOR-2026-00002-V1 | Version 1'));
        $this->assertTrue($this->pdfContains($tor, 'For Records and Reference Purposes'));
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

    public function test_an_imported_template_is_filled_from_the_current_record(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $png = tempnam(sys_get_temp_dir(), 'mcare-template');
        $image = imagecreatetruecolor(120, 70);
        imagefilledrectangle($image, 0, 0, 120, 70, imagecolorallocate($image, 244, 214, 255));
        imagepng($image, $png);
        imagedestroy($image);

        $this->actingAs($admin)
            ->get(route('admin.learning.certificate-templates.edit', OfficialDocument::TYPE_COTC))
            ->assertOk()
            ->assertSee('Import a pre-made template')
            ->assertSee('Imported template')
            ->assertSee('Import a finished design to preview it here.')
            ->assertDontSee('Live preview')
            ->assertDontSee('Drag a label');

        $this->actingAs($admin)
            ->post(route('admin.learning.certificate-templates.store'), [
                'type' => OfficialDocument::TYPE_COTC,
                'title' => 'Imported board certificate',
                'body' => 'Awarded to {{trainee_name}} for {{program}}.',
                'paper_size' => 'letter',
                'orientation' => 'landscape',
                'use_official_artwork' => '1',
                'signatory_name' => 'Amina Dela Cruz',
                'signatory_role' => 'Registrar',
                'second_signatory_name' => 'Paolo Reyes',
                'second_signatory_role' => 'Trainer',
                'background' => new UploadedFile($png, 'premades-certificate.png', 'image/png', null, true),
                'field_layout' => json_encode(CertificateTemplate::defaultFieldLayout(OfficialDocument::TYPE_COTC, 'landscape')),
            ])
            ->assertRedirect(route('admin.learning.certificate-templates.edit', OfficialDocument::TYPE_COTC))
            ->assertSessionHas('saved');

        $saved = CertificateTemplate::query()->where('type', OfficialDocument::TYPE_COTC)->sole();
        $this->assertFalse($saved->is_active);
        $this->assertFalse($saved->use_official_artwork);
        $this->assertNotNull($saved->background_path);

        $this->actingAs($admin)
            ->get(route('admin.learning.certificate-templates.edit', OfficialDocument::TYPE_COTC))
            ->assertOk()
            ->assertSee('Make active template');

        $this->actingAs($admin)
            ->patch(route('admin.learning.certificate-templates.activate', $saved))
            ->assertRedirect();

        $saved = CertificateTemplate::activeFor(OfficialDocument::TYPE_COTC);

        $pdf = app(FpdfOfficialDocumentRenderer::class)->render($this->document(OfficialDocument::TYPE_COTC));
        $this->assertTrue($this->pdfContains($pdf, 'MARIA REYES SANTOS'));
        $this->assertTrue($this->pdfContains($pdf, 'Awarded to Maria Reyes Santos for Caregiving NC II'));
        $this->assertTrue($this->pdfContains($pdf, 'Enrollment ID: MCE-2026-SAMPLE'));
        $this->assertFalse($this->pdfContains($pdf, 'MCARE OFFICIAL'));
        $this->assertFalse($this->pdfContains($pdf, 'This certifies that'));

        @unlink($png);
    }

    public function test_active_imported_tor_template_is_used_for_generation(): void
    {
        Storage::fake('local');
        $png = tempnam(sys_get_temp_dir(), 'mcare-tor-template');
        $image = imagecreatetruecolor(120, 180);
        imagefilledrectangle($image, 0, 0, 120, 180, imagecolorallocate($image, 220, 240, 255));
        imagepng($image, $png);
        imagedestroy($image);

        $backgroundPath = 'certificate-templates/imported-tor.png';
        Storage::disk('local')->put($backgroundPath, file_get_contents($png));
        CertificateTemplate::create([
            'type' => OfficialDocument::TYPE_TOR,
            'version' => 1,
            'is_active' => true,
            'title' => 'Imported Transcript Layout',
            'body' => 'Prepared for {{trainee_name}}.',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'use_official_artwork' => false,
            'background_path' => $backgroundPath,
            'field_layout' => CertificateTemplate::defaultFieldLayout(OfficialDocument::TYPE_TOR, 'portrait'),
            'signatory_name' => 'Amina Dela Cruz',
            'signatory_role' => 'Registrar',
        ]);

        $pdf = app(FpdfOfficialDocumentRenderer::class)->render($this->document(OfficialDocument::TYPE_TOR));

        $this->assertTrue($this->pdfContains($pdf, 'Imported Transcript Layout'));
        $this->assertTrue($this->pdfContains($pdf, 'Prepared for Maria Reyes Santos.'));
        $this->assertTrue($this->pdfContains($pdf, 'Provide Care and Support to Infants and Toddlers'));
        $this->assertFalse($this->pdfContains($pdf, 'OFFICIAL TRANSCRIPT OF RECORD'));

        @unlink($png);
    }

    public function test_a_trainer_cannot_open_the_template_manager(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);

        $this->actingAs($trainer)
            ->get(route('admin.learning.certificate-templates'))
            ->assertForbidden();

        $this->actingAs($trainer)
            ->get(route('admin.learning.certificate-templates.edit', 'cotc'))
            ->assertForbidden();
    }

    public function test_deleting_a_saved_template_restores_the_previous_or_official_layout(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $older = CertificateTemplate::create([
            'type' => OfficialDocument::TYPE_TOR,
            'version' => 1,
            'is_active' => false,
            'title' => 'Earlier transcript title',
            'body' => '',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'use_official_artwork' => false,
        ]);
        $current = CertificateTemplate::create([
            'type' => OfficialDocument::TYPE_TOR,
            'version' => 2,
            'is_active' => true,
            'title' => 'OFFICIAL TRANSCRIPT OF RECORD',
            'body' => '',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'use_official_artwork' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.learning.certificate-templates'))
            ->assertOk()
            ->assertSee('Delete saved template')
            ->assertSee('REGISTRAR')
            ->assertSee('NOTHING FOLLOWS');

        $this->actingAs($admin)
            ->delete(route('admin.learning.certificate-templates.destroy', $current))
            ->assertRedirect();

        $this->assertNull(CertificateTemplate::query()->find($current->id));
        $this->assertTrue((bool) $older->fresh()->is_active);

        $this->actingAs($admin)
            ->delete(route('admin.learning.certificate-templates.destroy', $older))
            ->assertRedirect();

        $this->assertNull(CertificateTemplate::activeFor(OfficialDocument::TYPE_TOR));
        $this->actingAs($admin)
            ->get(route('admin.learning.certificate-templates'))
            ->assertOk()
            ->assertSee('Default official template')
            ->assertSee('REGISTRAR')
            ->assertDontSee('Delete saved template');
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

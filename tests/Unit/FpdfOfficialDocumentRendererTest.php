<?php

namespace Tests\Unit;

use App\Contracts\OfficialDocumentRenderer;
use App\Models\CompetencyUnit;
use App\Models\EnrollmentApplication;
use App\Models\OfficialDocument;
use App\Models\TraineeCompetencyRecord;
use App\Models\TrainingBatch;
use App\Models\User;
use App\Services\BrowsershotOfficialDocumentRenderer;
use App\Services\FpdfOfficialDocumentRenderer;
use App\Services\OfficialDocumentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FpdfOfficialDocumentRendererTest extends TestCase
{
    use RefreshDatabase;

    public function test_fpdf_engine_is_bound_by_default_in_tests(): void
    {
        $this->assertInstanceOf(FpdfOfficialDocumentRenderer::class, app(OfficialDocumentRenderer::class));
    }

    public function test_auto_engine_uses_fpdf_when_browsershot_binaries_are_missing(): void
    {
        config([
            'official_documents.pdf_engine' => 'auto',
            'official_documents.browsershot.node_binary' => __DIR__.'/missing-node.exe',
            'official_documents.browsershot.npm_binary' => __DIR__.'/missing-npm.cmd',
            'official_documents.browsershot.chrome_path' => __DIR__.'/missing-chrome.exe',
        ]);
        $this->app->forgetInstance(OfficialDocumentRenderer::class);

        $this->assertFalse(BrowsershotOfficialDocumentRenderer::environmentIsReady());
        $this->assertInstanceOf(FpdfOfficialDocumentRenderer::class, app(OfficialDocumentRenderer::class));
    }

    public function test_browsershot_engine_can_be_selected_explicitly(): void
    {
        config(['official_documents.pdf_engine' => 'browsershot']);
        $this->app->forgetInstance(OfficialDocumentRenderer::class);

        $this->assertInstanceOf(BrowsershotOfficialDocumentRenderer::class, app(OfficialDocumentRenderer::class));
    }

    public function test_cotc_pdf_contains_the_trainee_name_and_template_image(): void
    {
        $pdf = app(FpdfOfficialDocumentRenderer::class)->render($this->document(OfficialDocument::TYPE_COTC));

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertTrue($this->pdfContains($pdf, 'MARIA REYES SANTOS'));
        $this->assertTrue($this->pdfContains($pdf, 'MCARE-COTC-2026-00002-V1'));
        $this->assertGreaterThan(0, preg_match_all('/\/Subtype\s*\/Image/', $pdf) ?: 0);
    }

    public function test_tor_pdf_contains_the_transcript_heading_and_competency_row(): void
    {
        $pdf = app(FpdfOfficialDocumentRenderer::class)->render($this->document(OfficialDocument::TYPE_TOR));

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertTrue($this->pdfContains($pdf, 'OFFICIAL TRANSCRIPT OF RECORD'));
        $this->assertTrue($this->pdfContains($pdf, 'HCS323301'));
        $this->assertTrue($this->pdfContains($pdf, 'COMPETENT'));
        $this->assertTrue($this->pdfContains($pdf, 'MCARE-TOR-2026-00002-V1'));
    }

    public function test_manager_can_generate_a_cotc_without_node(): void
    {
        Storage::fake('local');
        config(['official_documents.pdf_engine' => 'fpdf']);
        $this->app->forgetInstance(OfficialDocumentRenderer::class);

        $admin = User::factory()->create(['role' => 'admin']);
        $batch = TrainingBatch::create([
            'name' => 'PDF Engine Batch',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->subMonth(),
            'training_starts_at' => now()->subMonths(5),
            'training_ends_at' => now()->subDay(),
        ]);
        $application = EnrollmentApplication::create([
            'user_id' => User::factory()->create(['role' => 'trainee'])->id,
            'training_batch_id' => $batch->id,
            'email' => 'pdf.engine@gmail.com',
            'program' => 'Caregiving NC II',
            'first_name' => 'Luz',
            'middle_name' => 'Cruz',
            'last_name' => 'Dela Torre',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => '1 Training Street',
            'barangay' => 'Central',
            'city' => 'Pili',
            'province' => 'Camarines Sur',
            'zip_code' => '4418',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'MCARE School',
            'year_graduated' => 2022,
            'status' => EnrollmentApplication::STATUS_APPROVED,
            'learning_status' => EnrollmentApplication::LEARNING_ACTIVE,
            'payment_status' => EnrollmentApplication::PAYMENT_PAID,
            'reviewed_at' => now(),
            'learning_started_at' => now(),
        ]);
        $document = OfficialDocument::create([
            'enrollment_application_id' => $application->id,
            'training_batch_id' => $batch->id,
            'type' => OfficialDocument::TYPE_COTC,
            'version' => 1,
            'document_number' => 'MCARE-COTC-2026-00999-V1',
            'status' => OfficialDocument::STATUS_QUEUED,
            'storage_disk' => 'local',
            'generated_by_id' => $admin->id,
        ]);

        $generated = app(OfficialDocumentManager::class)->generateNow($document);

        $this->assertSame(OfficialDocument::STATUS_GENERATED, $generated->status);
        Storage::disk('local')->assertExists($generated->file_path);
        $pdf = Storage::disk('local')->get($generated->file_path);
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertTrue($this->pdfContains($pdf, 'LUZ CRUZ DELA TORRE'));
    }

    private function document(string $type): OfficialDocument
    {
        $batch = new TrainingBatch([
            'training_starts_at' => '2026-01-15',
            'training_ends_at' => '2026-07-18',
        ]);
        $application = new EnrollmentApplication([
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

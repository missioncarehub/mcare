<?php

namespace Tests\Feature\Lms;

use App\Models\TrainingModule;
use App\Services\LearningPdfWatermark;
use App\Support\WatermarkedFpdi;
use FPDF;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class LearningPdfWatermarkTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    public function test_uploaded_pdfs_are_stamped_and_trainees_receive_a_personalized_copy(): void
    {
        Storage::fake('local');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $trainee, 'application' => $application] = $this->lmsTrainee($batch, [
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'maria.santos@gmail.com',
        ]);

        $upload = $this->pdfUpload('caregiving-lesson.pdf');

        $this->actingAs($trainer)
            ->post(route('trainer.modules.store'), [
                'audience_type' => 'batch',
                'training_batch_id' => $batch->id,
                'title' => 'Provide Care And Support To Infants',
                'description' => 'Protected caregiving lesson.',
                'completion_mode' => TrainingModule::COMPLETION_MATERIAL_ONLY,
                'module_file' => $upload,
                'is_published' => '1',
            ])
            ->assertRedirect();

        $module = TrainingModule::query()->where('title', 'Provide Care And Support To Infants')->firstOrFail();
        $stored = Storage::disk('local')->get($module->file_path);

        $this->assertFileExists(WatermarkedFpdi::imagePath());
        $this->assertNotNull(WatermarkedFpdi::ensureTransparentImage());
        $this->assertStringContainsString('%PDF', $stored);
        $this->assertFalse($this->pdfContains($stored, 'MCARE Mission Care Training Center'));

        $view = $this->actingAs($trainee)->get(route('trainee.modules.content', $module));
        $view->assertOk();
        $viewed = $this->responseBody($view);

        $this->assertStringContainsString('%PDF', $viewed);
        $this->assertTrue($this->pdfContains($viewed, '/Subtype /Image'));
        $this->assertFalse($this->pdfContains($viewed, 'maria.santos@gmail.com'));

        $download = $this->actingAs($trainee)->get(route('trainee.modules.download', $module));
        $download->assertOk();
        $downloaded = $this->responseBody($download);

        $this->assertTrue($this->pdfContains($downloaded, '/Subtype /Image'));
        $this->assertFalse($this->pdfContains($downloaded, 'maria.santos@gmail.com'));
        $this->assertFalse($this->pdfContains($downloaded, 'MCARE Mission Care Training Center'));

        $this->actingAs($trainee)
            ->get(route('trainee.modules.show', $module))
            ->assertOk()
            ->assertSee('pdf-page-watermark', false);
    }

    public function test_large_lesson_pdfs_are_served_without_live_stamping(): void
    {
        Storage::fake('local');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        $path = "training-modules/{$trainer->id}/chapter.pdf";
        Storage::disk('local')->put(
            $path,
            $this->samplePdf().str_repeat(' ', LearningPdfWatermark::MAX_LIVE_STAMP_BYTES + 1)
        );
        $module = $this->lmsModule($trainer, $batch, [
            'title' => 'Large chapter PDF',
            'file_path' => $path,
            'original_file_name' => 'chapter.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => LearningPdfWatermark::MAX_LIVE_STAMP_BYTES + 1,
        ]);
        ['user' => $trainee] = $this->lmsTrainee($batch);

        $view = $this->actingAs($trainee)->get(route('trainee.modules.content', $module));
        $view->assertOk();
        $viewed = $this->responseBody($view);

        $this->assertStringContainsString('%PDF', $viewed);
        $this->assertFalse($this->pdfContains($viewed, '/Subtype /Image'));
    }

    public function test_non_pdf_uploads_are_not_rewritten_by_the_watermarker(): void
    {
        Storage::fake('local');
        $service = app(LearningPdfWatermark::class);
        Storage::disk('local')->put('training-modules/photo.png', 'png-bytes');

        $size = $service->stampStoredFile('training-modules/photo.png', 'photo.png', 'image/png');

        $this->assertSame(9, $size);
        $this->assertSame('png-bytes', Storage::disk('local')->get('training-modules/photo.png'));
    }

    private function pdfUpload(string $filename): UploadedFile
    {
        $path = Storage::disk('local')->path('incoming-'.$filename);
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, $this->samplePdf());

        return new UploadedFile($path, $filename, 'application/pdf', null, true);
    }

    private function pdfContains(string $pdf, string $needle): bool
    {
        if (str_contains($pdf, $needle)) {
            return true;
        }

        preg_match_all('/stream\r?\n(.*?)endstream/s', $pdf, $matches);

        foreach ($matches[1] as $stream) {
            $decoded = @gzuncompress($stream);
            if (is_string($decoded) && str_contains($decoded, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function samplePdf(): string
    {
        $pdf = new FPDF('P', 'pt', 'A4');
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 14);
        $pdf->Cell(200, 20, 'Caregiving lesson body');

        return $pdf->Output('S');
    }

    private function responseBody($response): string
    {
        $base = $response->baseResponse;
        if (method_exists($base, 'getFile')) {
            return (string) file_get_contents($base->getFile()->getPathname());
        }

        return $response->streamedContent();
    }
}

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
        $this->assertTrue($this->pdfContains($viewed, (string) $application->enrollment_number));
        $this->assertTrue($this->pdfContains($viewed, 'Maria Santos'));
        $this->assertFalse($this->pdfContains($viewed, 'maria.santos@gmail.com'));

        $this->actingAs($trainee)
            ->get(route('trainee.modules.show', $module))
            ->assertOk()
            ->assertDontSee('pdf-page-watermark', false);

        $download = $this->actingAs($trainee)->get(route('trainee.modules.download', $module));
        $download->assertOk();
        $downloaded = $this->responseBody($download);

        $this->assertTrue($this->pdfContains($downloaded, (string) $application->enrollment_number));
        $this->assertTrue($this->pdfContains($downloaded, 'Maria Santos'));
        $this->assertFalse($this->pdfContains($downloaded, 'maria.santos@gmail.com'));
        $this->assertFalse($this->pdfContains($downloaded, 'MCARE Mission Care Training Center'));
    }

    public function test_primary_lesson_preview_embeds_the_watermark_with_fpdi(): void
    {
        $trainer = $this->lmsUser('trainer');
        $admin = $this->lmsUser('admin');

        foreach ([
            route('trainer.modules.preview-watermark') => $trainer,
            route('admin.learning.modules.preview-watermark') => $admin,
        ] as $url => $user) {
            $preview = $this->actingAs($user)->post($url, [
                'module_file' => $this->pdfUpload('primary-lesson.pdf'),
            ]);

            $preview->assertOk();
            $body = $this->responseBody($preview);
            $this->assertStringContainsString('%PDF', $body);
            $this->assertTrue($this->pdfContains($body, '/Subtype /Image'));
            $this->assertTrue($this->pdfContains($body, 'Enrollment number'));
        }
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

    public function test_trainee_image_views_receive_an_embedded_watermark(): void
    {
        Storage::fake('local');
        $trainer = $this->lmsUser('trainer');
        $batch = $this->lmsBatch();
        ['user' => $trainee] = $this->lmsTrainee($batch);
        $path = "training-modules/{$trainer->id}/lesson-photo.png";
        $original = $this->samplePng();
        Storage::disk('local')->put($path, $original);
        $module = $this->lmsModule($trainer, $batch, [
            'title' => 'Infant care photo',
            'file_path' => $path,
            'original_file_name' => 'lesson-photo.png',
            'mime_type' => 'image/png',
            'file_size' => strlen($original),
        ]);

        $this->assertSame($original, Storage::disk('local')->get($path));

        $view = $this->actingAs($trainee)->get(route('trainee.modules.content', $module));
        $view->assertOk();
        $viewed = $this->responseBody($view);

        $this->assertNotSame($original, $viewed);
        $this->assertSame($original, Storage::disk('local')->get($path));
        $this->assertSame('image/png', $view->headers->get('content-type'));

        $pixels = @imagecreatefromstring($viewed);
        $this->assertNotFalse($pixels);
        $this->assertTrue($this->imageHasNonBackgroundPixels($pixels, 248, 250, 252));
        imagedestroy($pixels);

        $download = $this->actingAs($trainee)->get(route('trainee.modules.download', $module));
        $download->assertOk();
        $this->assertNotSame($original, $this->responseBody($download));
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

    private function imageHasNonBackgroundPixels(\GdImage $image, int $red, int $green, int $blue): bool
    {
        $width = imagesx($image);
        $height = imagesy($image);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $color = imagecolorat($image, $x, $y);
                $pixelRed = ($color >> 16) & 0xFF;
                $pixelGreen = ($color >> 8) & 0xFF;
                $pixelBlue = $color & 0xFF;

                if ($pixelRed !== $red || $pixelGreen !== $green || $pixelBlue !== $blue) {
                    return true;
                }
            }
        }

        return false;
    }

    private function samplePng(): string
    {
        $image = imagecreatetruecolor(240, 160);
        $background = imagecolorallocate($image, 248, 250, 252);
        imagefilledrectangle($image, 0, 0, 239, 159, $background);
        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return $bytes;
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

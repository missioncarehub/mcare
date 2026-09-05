<?php

namespace App\Services;

use App\Support\WatermarkedFpdi;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class LearningPdfWatermark
{
    /**
     * Hostinger shared PHP often has 128–256 MB. Live FPDI stamping keeps the
     * whole document in memory, so larger lesson PDFs must be served as-is.
     */
    public const MAX_LIVE_STAMP_BYTES = 4194304;

    public function isPdf(?string $originalName = null, ?string $mime = null, ?string $storagePath = null): bool
    {
        $extension = strtolower(pathinfo((string) ($originalName ?: $storagePath), PATHINFO_EXTENSION));
        $mime = strtolower((string) $mime);

        return $extension === 'pdf'
            || in_array($mime, [
                'application/pdf',
                'application/x-pdf',
                'application/acrobat',
                'application/vnd.adobe.pdf',
                'application/vnd.pdf',
                'text/pdf',
            ], true);
    }

    public function stampStoredFile(string $storagePath, ?string $originalName = null, ?string $mime = null): int
    {
        return (int) Storage::disk('local')->size($storagePath);
    }

    public function respond(
        string $storagePath,
        string $filename,
        ?string $mime,
        string $disposition,
    ): BinaryFileResponse|StreamedResponse {
        $fallbackFilename = str($filename)->ascii()->replaceMatches('/[^A-Za-z0-9._-]/', '-')->toString();
        $headers = [
            'Content-Type' => $mime ?: 'application/octet-stream',
            'Content-Disposition' => HeaderUtils::makeDisposition($disposition, $filename, $fallbackFilename),
            'X-Content-Type-Options' => 'nosniff',
        ];

        $absolutePath = Storage::disk('local')->path($storagePath);
        $size = is_file($absolutePath) ? (int) filesize($absolutePath) : 0;
        $isPdf = $this->isPdf($filename, $mime, $storagePath);

        if ($isPdf) {
            $headers['Content-Type'] = 'application/pdf';
        }

        $headers['Accept-Ranges'] = 'bytes';

        if ($isPdf && $size > 0 && $size <= self::MAX_LIVE_STAMP_BYTES) {
            $stampedPath = $this->stampToTemporaryFile($absolutePath);

            if (is_string($stampedPath) && is_file($stampedPath)) {
                return response()->file($stampedPath, $headers)->deleteFileAfterSend(true);
            }
        }

        return response()->file($absolutePath, $headers);
    }

    private function stampToTemporaryFile(string $absolutePath): ?string
    {
        if (! is_file($absolutePath)) {
            return null;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'mcare-wm-');
        if ($tempPath === false) {
            return null;
        }

        try {
            $pdf = new WatermarkedFpdi('P', 'pt');
            $pdf->SetAutoPageBreak(false);
            $pdf->SetMargins(0, 0, 0);
            $pageCount = $pdf->setSourceFile($absolutePath);

            for ($page = 1; $page <= $pageCount; $page++) {
                $template = $pdf->importPage($page);
                $size = $pdf->getTemplateSize($template);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($template);
                $pdf->paintWatermark();
            }

            $pdf->Output('F', $tempPath);

            if (! is_file($tempPath) || filesize($tempPath) < 8) {
                @unlink($tempPath);

                return null;
            }

            return $tempPath;
        } catch (Throwable $exception) {
            @unlink($tempPath);

            Log::warning('Learning PDF watermark could not be applied.', [
                'path_basename' => basename($absolutePath),
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}

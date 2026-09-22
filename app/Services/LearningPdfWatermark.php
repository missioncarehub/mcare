<?php

namespace App\Services;

use App\Support\WatermarkedFpdi;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Throwable;

class LearningPdfWatermark
{
    /**
     * Hostinger shared PHP often has 128–256 MB. Live FPDI stamping keeps the
     * whole document in memory, so larger lesson PDFs must be served as-is.
     */
    public const MAX_LIVE_STAMP_BYTES = 4194304;

    public const MAX_LIVE_IMAGE_STAMP_BYTES = 8388608;

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

    public function isImage(?string $originalName = null, ?string $mime = null, ?string $storagePath = null): bool
    {
        $extension = strtolower(pathinfo((string) ($originalName ?: $storagePath), PATHINFO_EXTENSION));
        $mime = strtolower((string) $mime);

        return str_starts_with($mime, 'image/')
            || in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
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
        $isImage = $this->isImage($filename, $mime, $storagePath);

        if ($isPdf) {
            $headers['Content-Type'] = 'application/pdf';
        }

        $headers['Accept-Ranges'] = 'bytes';

        $stampedPath = null;
        if ($isPdf && $size > 0 && $size <= self::MAX_LIVE_STAMP_BYTES) {
            $stampedPath = $this->stampPdfToTemporaryFile($absolutePath);
        } elseif ($isImage && $size > 0 && $size <= self::MAX_LIVE_IMAGE_STAMP_BYTES) {
            $stampedPath = $this->stampImageToTemporaryFile($absolutePath, $mime ?: $filename);
        }

        if (is_string($stampedPath) && is_file($stampedPath)) {
            return response()->file($stampedPath, $headers)->deleteFileAfterSend(true);
        }

        return response()->file($absolutePath, $headers);
    }

    private function stampPdfToTemporaryFile(string $absolutePath): ?string
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

    private function stampImageToTemporaryFile(string $absolutePath, string $mimeOrName): ?string
    {
        if (! is_file($absolutePath) || ! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $watermarkPath = WatermarkedFpdi::ensureTransparentImage()
            ?? (is_file(WatermarkedFpdi::imagePath()) ? WatermarkedFpdi::imagePath() : null);
        if ($watermarkPath === null) {
            return null;
        }

        $source = $this->createImageResource($absolutePath, $mimeOrName);
        $mark = @imagecreatefrompng($watermarkPath);
        if ($source === false || $mark === false) {
            if (is_resource($source) || $source instanceof \GdImage) {
                imagedestroy($source);
            }
            if (is_resource($mark) || $mark instanceof \GdImage) {
                imagedestroy($mark);
            }

            return null;
        }

        imagealphablending($source, true);
        imagesavealpha($source, true);

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $markWidth = imagesx($mark);
        $markHeight = imagesy($mark);
        $ratio = $markWidth / max(1, $markHeight);
        $box = min($sourceWidth, $sourceHeight) * 0.72;
        $destWidth = (int) max(1, round($ratio >= 1 ? $box : $box * $ratio));
        $destHeight = (int) max(1, round($ratio >= 1 ? $box / $ratio : $box));
        $x = (int) (($sourceWidth - $destWidth) / 2);
        $y = (int) (($sourceHeight - $destHeight) / 2);

        $scaled = imagecreatetruecolor($destWidth, $destHeight);
        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);
        $clear = imagecolorallocatealpha($scaled, 0, 0, 0, 127);
        imagefilledrectangle($scaled, 0, 0, $destWidth, $destHeight, $clear);
        imagecopyresampled($scaled, $mark, 0, 0, 0, 0, $destWidth, $destHeight, $markWidth, $markHeight);
        $this->applyGlobalAlpha($scaled, 0.42);
        imagealphablending($source, true);
        imagecopy($source, $scaled, $x, $y, 0, 0, $destWidth, $destHeight);

        $tempPath = tempnam(sys_get_temp_dir(), 'mcare-wm-img-');
        if ($tempPath === false || ! $this->writeImageResource($source, $tempPath, $mimeOrName)) {
            imagedestroy($source);
            imagedestroy($mark);
            imagedestroy($scaled);
            if (is_string($tempPath)) {
                @unlink($tempPath);
            }

            return null;
        }

        imagedestroy($source);
        imagedestroy($mark);
        imagedestroy($scaled);

        return $tempPath;
    }

    private function createImageResource(string $path, string $mimeOrName): \GdImage|false
    {
        $kind = $this->imageKind($mimeOrName, $path);

        return match ($kind) {
            'jpeg' => @imagecreatefromjpeg($path),
            'png' => @imagecreatefrompng($path),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            'gif' => @imagecreatefromgif($path),
            default => false,
        };
    }

    private function writeImageResource(\GdImage $image, string $path, string $mimeOrName): bool
    {
        $kind = $this->imageKind($mimeOrName, $path);

        return match ($kind) {
            'jpeg' => (bool) imagejpeg($image, $path, 88),
            'png' => (bool) imagepng($image, $path, 6),
            'webp' => function_exists('imagewebp') && imagewebp($image, $path, 82),
            'gif' => (bool) imagegif($image, $path),
            default => false,
        };
    }

    private function applyGlobalAlpha(\GdImage $image, float $opacity): void
    {
        $opacity = max(0.0, min(1.0, $opacity));
        $width = imagesx($image);
        $height = imagesy($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba & 0x7F000000) >> 24;
                $red = ($rgba >> 16) & 0xFF;
                $green = ($rgba >> 8) & 0xFF;
                $blue = $rgba & 0xFF;
                $newAlpha = (int) min(127, round($alpha + ((127 - $alpha) * (1 - $opacity))));
                imagesetpixel($image, $x, $y, imagecolorallocatealpha($image, $red, $green, $blue, $newAlpha));
            }
        }
    }

    private function imageKind(string $mimeOrName, ?string $path = null): string
    {
        $value = strtolower($mimeOrName.' '.($path ?? ''));

        return match (true) {
            str_contains($value, 'jpeg') || str_contains($value, '.jpg') => 'jpeg',
            str_contains($value, 'webp') => 'webp',
            str_contains($value, 'gif') => 'gif',
            default => 'png',
        };
    }
}

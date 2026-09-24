<?php

namespace App\Services;

use App\Support\WatermarkedFpdi;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
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

    /** @param list<string> $lines */
    public function previewUpload(UploadedFile $file, array $lines): BinaryFileResponse
    {
        $name = $file->getClientOriginalName();
        $mime = $file->getMimeType();
        $source = $file->getRealPath();
        $size = (int) $file->getSize();
        $stampedPath = null;

        if (is_string($source) && $this->isPdf($name, $mime, $source) && $size > 0 && $size <= self::MAX_LIVE_STAMP_BYTES) {
            $stampedPath = $this->stampPdfToTemporaryFile($source, $lines);
            $mime = 'application/pdf';
        } elseif (is_string($source) && $this->isImage($name, $mime, $source) && $size > 0 && $size <= self::MAX_LIVE_IMAGE_STAMP_BYTES) {
            $stampedPath = $this->stampImageToTemporaryFile($source, $mime ?: $name, $lines);
        }

        abort_unless(is_string($stampedPath) && is_file($stampedPath), 422, 'The watermark could not be embedded in this file.');

        return response()->file($stampedPath, [
            'Content-Type' => $mime ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="watermarked-preview"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ])->deleteFileAfterSend(true);
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
        array $lines = [],
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
            $stampedPath = $this->stampPdfToTemporaryFile($absolutePath, $lines);
        } elseif ($isImage && $size > 0 && $size <= self::MAX_LIVE_IMAGE_STAMP_BYTES) {
            $stampedPath = $this->stampImageToTemporaryFile($absolutePath, $mime ?: $filename, $lines);
        }

        if (is_string($stampedPath) && is_file($stampedPath)) {
            return response()->file($stampedPath, $headers)->deleteFileAfterSend(true);
        }

        return response()->file($absolutePath, $headers);
    }

    /** @param list<string> $lines */
    private function stampPdfToTemporaryFile(string $absolutePath, array $lines): ?string
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
                $pdf->paintWatermark();
                $pdf->paintIdentity($lines);
                $pdf->useTemplateAboveIdentity($template);
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

    /** @param list<string> $lines */
    private function stampImageToTemporaryFile(string $absolutePath, string $mimeOrName, array $lines = []): ?string
    {
        if (! is_file($absolutePath) || ! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $source = $this->createImageResource($absolutePath, $mimeOrName);
        if ($source === false) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $canvas = imagecreatetruecolor($width, $height);
        $paper = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $paper);
        $this->paintImageLogo($canvas);
        $this->paintImageIdentity($canvas, $lines);
        imagealphablending($canvas, true);
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $color = imagecolorat($source, $x, $y);
                $red = ($color >> 16) & 0xFF;
                $green = ($color >> 8) & 0xFF;
                $blue = $color & 0xFF;
                if ($red > 245 && $green > 245 && $blue > 245) {
                    continue;
                }
                imagesetpixel($canvas, $x, $y, imagecolorallocate($canvas, $red, $green, $blue));
            }
        }
        imagedestroy($source);
        $source = $canvas;

        $tempPath = tempnam(sys_get_temp_dir(), 'mcare-wm-img-');
        if ($tempPath === false || ! $this->writeImageResource($source, $tempPath, $mimeOrName)) {
            imagedestroy($source);
            if (is_string($tempPath)) {
                @unlink($tempPath);
            }

            return null;
        }

        imagedestroy($source);

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

    private function paintImageLogo(\GdImage $image): void
    {
        $path = WatermarkedFpdi::ensureTransparentImage()
            ?? (is_file(WatermarkedFpdi::imagePath()) ? WatermarkedFpdi::imagePath() : null);
        $mark = is_string($path) ? @imagecreatefrompng($path) : false;
        if ($mark === false) {
            return;
        }

        $sourceWidth = imagesx($image);
        $sourceHeight = imagesy($image);
        $markWidth = imagesx($mark);
        $markHeight = imagesy($mark);
        $ratio = $markWidth / max(1, $markHeight);
        $box = min($sourceWidth, $sourceHeight) * 0.72;
        $destWidth = (int) max(1, round($ratio >= 1 ? $box : $box * $ratio));
        $destHeight = (int) max(1, round($ratio >= 1 ? $box / $ratio : $box));
        $originX = (int) (($sourceWidth - $destWidth) / 2);
        $originY = (int) (($sourceHeight - $destHeight) / 2);

        $scaled = imagecreatetruecolor($destWidth, $destHeight);
        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);
        $clear = imagecolorallocatealpha($scaled, 0, 0, 0, 127);
        imagefilledrectangle($scaled, 0, 0, $destWidth, $destHeight, $clear);
        imagecopyresampled($scaled, $mark, 0, 0, 0, 0, $destWidth, $destHeight, $markWidth, $markHeight);
        imagedestroy($mark);

        $opacity = 0.42;
        for ($py = 0; $py < $destHeight; $py++) {
            for ($px = 0; $px < $destWidth; $px++) {
                $rgba = imagecolorat($scaled, $px, $py);
                $markAlpha = ($rgba & 0x7F000000) >> 24;
                if ($markAlpha >= 120) {
                    continue;
                }

                $coverage = (1 - ($markAlpha / 127)) * $opacity;
                $dx = $originX + $px;
                $dy = $originY + $py;
                if ($dx < 0 || $dy < 0 || $dx >= $sourceWidth || $dy >= $sourceHeight) {
                    continue;
                }

                $base = imagecolorat($image, $dx, $dy);
                $blend = function (int $shift) use ($rgba, $base, $coverage): int {
                    $markChannel = ($rgba >> $shift) & 0xFF;
                    $baseChannel = ($base >> $shift) & 0xFF;

                    return (int) round(($markChannel * $coverage) + ($baseChannel * (1 - $coverage)));
                };
                imagesetpixel($image, $dx, $dy, imagecolorallocate(
                    $image,
                    $blend(16),
                    $blend(8),
                    $blend(0),
                ));
            }
        }

        imagedestroy($scaled);
    }

    /** @param list<string> $lines */
    private function paintImageIdentity(\GdImage $image, array $lines): void
    {
        $text = trim(implode(' | ', array_map(fn ($line) => trim((string) $line), $lines)));
        if ($text === '') {
            return;
        }

        $color = imagecolorallocate($image, 90, 90, 90);
        $width = imagesx($image);
        $height = imagesy($image);
        $fontFile = 'C:\\Windows\\Fonts\\arialbd.ttf';

        if (function_exists('imagettftext') && is_file($fontFile)) {
            $size = max(28, (int) round(min($width, $height) * 0.08));
            imagettftext($image, $size, 90, max(8, $width - (int) round($size * 1.3)), $height - 16, $color, $fontFile, $text);

            return;
        }

        $font = 5;
        $x = max(4, $width - imagefontwidth($font) - 8);
        $y = 8;
        foreach (str_split($text) as $character) {
            imagestring($image, $font, $x, $y, $character, $color);
            $y += imagefontheight($font) + 2;
            if ($y > $height - imagefontheight($font)) {
                break;
            }
        }
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

<?php

namespace App\Support;

use setasign\Fpdi\Fpdi;

class WatermarkedFpdi extends Fpdi
{
    public const IMAGE_RELATIVE_PATH = 'assets/images/water-mark/water.png';

    public const TRANSPARENT_IMAGE_RELATIVE_PATH = 'assets/images/water-mark/water-alpha.png';

    private const LOGO_PAGE_RATIO = 0.72;

    /** @var array<int, array{n?: int, parms: array{ca: float, CA: float, BM: string}}> */
    private array $extGStates = [];

    public static function imagePath(): string
    {
        return public_path(self::IMAGE_RELATIVE_PATH);
    }

    public static function transparentImagePath(): string
    {
        return public_path(self::TRANSPARENT_IMAGE_RELATIVE_PATH);
    }

    public static function publicImageUrl(): string
    {
        $path = self::ensureTransparentImage();

        return $path !== null
            ? asset(self::TRANSPARENT_IMAGE_RELATIVE_PATH)
            : asset(self::IMAGE_RELATIVE_PATH);
    }

    public function paintWatermark(): void
    {
        $path = $this->preparedImagePath();
        if ($path === null) {
            return;
        }

        $this->SetAutoPageBreak(false);
        $this->SetMargins(0, 0, 0);

        $info = @getimagesize($path);
        $pixelWidth = is_array($info) ? max(1, (int) $info[0]) : 1;
        $pixelHeight = is_array($info) ? max(1, (int) $info[1]) : 1;
        $ratio = $pixelWidth / $pixelHeight;

        $box = min($this->w, $this->h) * self::LOGO_PAGE_RATIO;
        $width = $ratio >= 1 ? $box : $box * $ratio;
        $height = $ratio >= 1 ? $box / $ratio : $box;
        $x = ($this->w - $width) / 2;
        $y = ($this->h - $height) / 2;

        $this->setAlpha(0.42);
        $this->Image($path, $x, $y, $width, $height, 'png');
        $this->setAlpha(1);
    }

    /** @param list<string> $lines */
    public function paintIdentity(array $lines): void
    {
        $lines = array_values(array_filter(array_map(
            fn ($line) => $this->pdfText((string) $line),
            $lines,
        ), fn (string $line) => $line !== ''));

        if ($lines === []) {
            return;
        }

        $this->SetAutoPageBreak(false);
        $this->SetMargins(0, 0, 0);
        $this->SetFont('Helvetica', 'B', 22);
        $this->SetTextColor(80, 80, 80);
        $this->setAlpha(0.55);

        $text = implode('   |   ', $lines);
        $x = max(28, $this->w - 28);
        $y = min($this->h - 36, max(36, $this->h * 0.78));
        $this->rotate(90, $x, $y);
        $this->Text($x, $y, $text);
        $this->rotate(0);
        $this->setAlpha(1);
        $this->SetTextColor(0, 0, 0);
    }

    public function useTemplateAboveIdentity(int|string $template): void
    {
        // Darken keeps the lesson ink on top of the side text. White page
        // fills no longer cover the watermark that was painted first.
        $this->setAlpha(1, 'Darken');
        $this->useTemplate($template);
        $this->setAlpha(1, 'Normal');
    }

    private float $rotationAngle = 0;

    private function rotate(float $angle, float $x = 0, float $y = 0): void
    {
        if ($this->rotationAngle !== 0.0) {
            $this->_out('Q');
        }

        $this->rotationAngle = $angle;
        if ($angle === 0.0) {
            return;
        }

        $angle *= M_PI / 180;
        $c = cos($angle);
        $s = sin($angle);
        $cx = $x * $this->k;
        $cy = ($this->h - $y) * $this->k;
        $this->_out(sprintf(
            'q %.5F %.5F %.5F %.5F %.5F %.5F cm 1 0 0 1 %.5F %.5F cm',
            $c,
            $s,
            -$s,
            $c,
            $cx,
            $cy,
            -$cx,
            -$cy,
        ));
    }

    private function pdfText(string $value): string
    {
        $value = trim(str_replace(["\r", "\n", '(', ')', '\\'], ' ', $value));
        $converted = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $value);

        return is_string($converted) ? trim($converted) : $value;
    }

    protected function _enddoc(): void
    {
        if ($this->extGStates !== [] && $this->PDFVersion < '1.4') {
            $this->PDFVersion = '1.4';
        }

        parent::_enddoc();
    }

    protected function _putresources(): void
    {
        $this->putExtGStates();
        parent::_putresources();
    }

    protected function _putresourcedict(): void
    {
        parent::_putresourcedict();

        if ($this->extGStates === []) {
            return;
        }

        $this->_put('/ExtGState <<');
        foreach ($this->extGStates as $id => $state) {
            $this->_put('/GS'.$id.' '.$state['n'].' 0 R');
        }
        $this->_put('>>');
    }

    private function setAlpha(float $alpha, string $blendMode = 'Normal'): void
    {
        $alpha = max(0.0, min(1.0, $alpha));
        $id = $this->addExtGState($alpha, '/'.$blendMode);
        $this->_out(sprintf('/GS%d gs', $id));
    }

    private function addExtGState(float $alpha, string $blendMode = '/Normal'): int
    {
        foreach ($this->extGStates as $id => $state) {
            if (abs($state['parms']['ca'] - $alpha) < 0.0001 && $state['parms']['BM'] === $blendMode) {
                return $id;
            }
        }

        $id = count($this->extGStates) + 1;
        $this->extGStates[$id] = [
            'parms' => [
                'ca' => $alpha,
                'CA' => $alpha,
                'BM' => $blendMode,
            ],
        ];

        return $id;
    }

    private function preparedImagePath(): ?string
    {
        return self::ensureTransparentImage() ?? (is_file(self::imagePath()) ? self::imagePath() : null);
    }

    public static function ensureTransparentImage(): ?string
    {
        $source = self::imagePath();
        $destination = self::transparentImagePath();

        if (! is_file($source)) {
            return is_file($destination) ? $destination : null;
        }

        if (is_file($destination) && filemtime($destination) >= filemtime($source)) {
            return $destination;
        }

        return self::writeTransparentImage($source, $destination) ? $destination : null;
    }

    private static function writeTransparentImage(string $source, string $destination): bool
    {
        $image = @imagecreatefrompng($source);
        if ($image === false) {
            return false;
        }

        imagepalettetotruecolor($image);

        $sourceWidth = imagesx($image);
        $sourceHeight = imagesy($image);
        $width = $sourceWidth;
        $height = $sourceHeight;
        $maxWidth = 900;

        if ($width > $maxWidth) {
            $height = (int) max(1, round($height * ($maxWidth / $width)));
            $width = $maxWidth;
            $resized = imagecreatetruecolor($width, $height);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $clear = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefilledrectangle($resized, 0, 0, $width, $height, $clear);
            imagealphablending($resized, true);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);
            imagedestroy($image);
            $image = $resized;
        }

        $out = imagecreatetruecolor($width, $height);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        $transparent = imagecolorallocatealpha($out, 0, 0, 0, 127);
        imagefilledrectangle($out, 0, 0, $width, $height, $transparent);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba & 0x7F000000) >> 24;
                $red = ($rgba >> 16) & 0xFF;
                $green = ($rgba >> 8) & 0xFF;
                $blue = $rgba & 0xFF;
                $luma = (0.2126 * $red) + (0.7152 * $green) + (0.0722 * $blue);

                if ($luma < 22) {
                    continue;
                }

                imagesetpixel($out, $x, $y, imagecolorallocatealpha($out, $red, $green, $blue, $alpha));
            }
        }

        $directory = dirname($destination);
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            imagedestroy($image);
            imagedestroy($out);

            return false;
        }

        $ok = imagepng($out, $destination, 6);
        imagedestroy($image);
        imagedestroy($out);

        return $ok;
    }

    private function putExtGStates(): void
    {
        foreach ($this->extGStates as $id => $state) {
            $this->_newobj();
            $this->extGStates[$id]['n'] = $this->n;
            $this->_put('<</Type /ExtGState');
            $this->_put(sprintf('/ca %.3F', $state['parms']['ca']));
            $this->_put(sprintf('/CA %.3F', $state['parms']['CA']));
            $this->_put('/BM '.$state['parms']['BM']);
            $this->_put('>>');
            $this->_put('endobj');
        }
    }
}

<?php

namespace App\Services;

use App\Models\CertificateTemplate;
use App\Models\OfficialDocument;
use FPDF;
use RuntimeException;

class ImportedCertificateRenderer
{
    /** @var list<string> */
    private array $temporaryImages = [];

    /**
     * @param  array<string, string>  $values
     * @param  array<string, mixed>  $context
     */
    public function render(CertificateTemplate $template, array $values, array $context = []): string
    {
        $background = $template->absoluteAsset($template->background_path);
        if ($background === null) {
            throw new RuntimeException('The imported certificate template is missing.');
        }

        $this->temporaryImages = [];
        [$orientation, $size, $width, $height] = $this->pageSize($template);

        try {
            $pdf = $this->open($background, $orientation, $size, $width, $height);
            $this->paintFields($pdf, $template, $values, $context, $width, $height);

            return $pdf->Output('S');
        } finally {
            foreach ($this->temporaryImages as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }
    }

    private function open(string $background, string $orientation, string $size, float $width, float $height): OfficialPdf|ImportedTemplatePdf
    {
        if (strtolower(pathinfo($background, PATHINFO_EXTENSION)) === 'pdf') {
            $pdf = new ImportedTemplatePdf($orientation, 'mm', $size);
            $pdf->SetAutoPageBreak(false);
            $pdf->setSourceFile($background);
            $page = $pdf->importPage(1);
            $pdf->AddPage();
            $pdf->useTemplate($page, 0, 0, $width, $height);

            return $pdf;
        }

        $pdf = new OfficialPdf($orientation, 'mm', $size);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();
        $prepared = $this->preparedJpeg($background, $width, $height);
        if ($prepared === null) {
            throw new RuntimeException('The imported certificate template could not be read.');
        }
        $pdf->Image($prepared, 0, 0, $width, $height, 'JPG');

        return $pdf;
    }

    /**
     * @param  array<string, string>  $values
     * @param  array<string, mixed>  $context
     */
    private function paintFields(OfficialPdf|ImportedTemplatePdf $pdf, CertificateTemplate $template, array $values, array $context, float $width, float $height): void
    {
        foreach ($template->resolvedFieldLayout() as $key => $box) {
            $text = $this->fieldText($key, $template, $values, $context);
            if (trim($text) === '') {
                continue;
            }

            $x = $width * (((float) $box['x']) / 100);
            $y = $height * (((float) $box['y']) / 100);
            $boxWidth = $width * (((float) $box['w']) / 100);
            $boxHeight = $height * (((float) $box['h']) / 100);
            $pdf->SetFillColor(255, 252, 255);
            $pdf->Rect($x, $y, $boxWidth, $boxHeight, 'F');
            $signature = match ($key) {
                'signatory' => $context['signatory_signature'] ?? null,
                'second_signatory' => $context['second_signatory_signature'] ?? null,
                default => null,
            };
            if (is_string($signature)) {
                $signatureHeight = min(14.0, $boxHeight * 0.45);
                $prepared = $this->preparedJpeg($signature, $boxWidth, $signatureHeight);
                if ($prepared !== null) {
                    $pdf->Image($prepared, $x, $y, min($boxWidth, 42), $signatureHeight, 'JPG');
                    $y += $signatureHeight;
                    $boxHeight = max(4.0, $boxHeight - $signatureHeight);
                }
            }

            $pdf->SetFont('Times', in_array($key, ['title', 'trainee_name', 'signatory', 'second_signatory'], true) ? 'B' : '', (float) $box['size']);
            $pdf->SetXY($x, $y);
            $pdf->MultiCell($boxWidth, min(5.0, max(3.2, $boxHeight / 3)), $this->latin($text), 0, (string) $box['align']);
        }
    }

    /**
     * @param  array<string, string>  $values
     * @param  array<string, mixed>  $context
     */
    private function fieldText(string $key, CertificateTemplate $template, array $values, array $context): string
    {
        return match ($key) {
            'title' => trim(CertificateTemplate::replacePlaceholders((string) $template->title, $values)),
            'body' => trim(CertificateTemplate::replacePlaceholders((string) $template->body, $values)),
            'signatory' => trim(((string) ($context['signatory_name'] ?? ''))."\n".((string) ($context['signatory_role'] ?? ''))),
            'second_signatory' => trim(((string) ($context['second_signatory_name'] ?? ''))."\n".((string) ($context['second_signatory_role'] ?? ''))),
            'records' => trim((string) ($context['records'] ?? '')),
            'trainee_name' => $this->displayName($template, (string) ($values['trainee_name'] ?? '')),
            'certificate_no' => filled($values['certificate_no'] ?? null) ? 'No. '.$values['certificate_no'] : '',
            'enrollment_id' => filled($values['enrollment_id'] ?? null) ? 'Enrollment ID: '.$values['enrollment_id'] : '',
            default => trim((string) ($values[$key] ?? '')),
        };
    }

    private function displayName(CertificateTemplate $template, string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        if ($template->type === OfficialDocument::TYPE_TOR) {
            return '"'.mb_strtoupper($name).'"';
        }

        if ($template->type === OfficialDocument::TYPE_COTC) {
            return mb_strtoupper($name);
        }

        return $name;
    }

    /** @return array{0: string, 1: string, 2: float, 3: float} */
    private function pageSize(CertificateTemplate $template): array
    {
        $orientation = $template->orientation === 'landscape' ? 'L' : 'P';
        $size = match ($template->paper_size) {
            'letter' => 'Letter',
            'legal' => 'Legal',
            default => 'A4',
        };
        $dimensions = [
            'Letter' => [215.9, 279.4],
            'Legal' => [215.9, 355.6],
            'A4' => [210.0, 297.0],
        ][$size];
        $width = $orientation === 'L' ? $dimensions[1] : $dimensions[0];
        $height = $orientation === 'L' ? $dimensions[0] : $dimensions[1];

        return [$orientation, $size, $width, $height];
    }

    private function preparedJpeg(string $sourcePath, float $widthMm, float $heightMm): ?string
    {
        if (! is_file($sourcePath) || ! function_exists('imagecreatefromstring')) {
            return null;
        }

        $binary = file_get_contents($sourcePath);
        $source = is_string($binary) ? @imagecreatefromstring($binary) : false;
        if ($source === false) {
            return null;
        }

        $dpi = 150;
        $targetWidth = max(1, (int) round($widthMm / 25.4 * $dpi));
        $targetHeight = max(1, (int) round($heightMm / 25.4 * $dpi));
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($canvas === false) {
            imagedestroy($source);

            return null;
        }

        imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, imagesx($source), imagesy($source));
        imagedestroy($source);

        $base = tempnam(sys_get_temp_dir(), 'mcare-import-');
        if ($base === false) {
            imagedestroy($canvas);

            return null;
        }

        $output = $base.'.jpg';
        @unlink($base);
        $written = imagejpeg($canvas, $output, 85);
        imagedestroy($canvas);
        if (! $written) {
            return null;
        }

        $this->temporaryImages[] = $output;

        return $output;
    }

    private function latin(string $value): string
    {
        $converted = iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $value);

        return $converted === false ? (preg_replace('/[^\x20-\x7E\n]/', '', $value) ?? '') : $converted;
    }
}

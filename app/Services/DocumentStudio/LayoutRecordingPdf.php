<?php

namespace App\Services\DocumentStudio;

use FPDF;

/** Captures trusted baseline drawing commands, not pixels or an issued PDF. */
class LayoutRecordingPdf extends FPDF
{
    public array $fields = [];

    public array $capturedImages = [];

    private function color(string $command): string
    {
        $numbers = array_map('floatval', explode(' ', trim($command)));
        if (count($numbers) < 4) {
            $numbers = array_fill(0, 3, $numbers[0]);
        }

        return sprintf('#%02x%02x%02x', ...array_map(fn ($value) => (int) round($value * 255), array_slice($numbers, 0, 3)));
    }

    private function field(array $field): void
    {
        $field['name'] = 'source_'.count($this->fields);
        $this->fields[] = $field;
    }

    public function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '')
    {
        $width = $w ?: $this->w - $this->rMargin - $this->x;
        if ($fill || $border) {
            $this->field(['type' => 'rectangle', 'position' => ['x' => $this->x, 'y' => $this->y],
                'width' => $width, 'height' => $h, 'color' => $fill ? $this->color($this->FillColor) : '',
                'borderWidth' => $border ? $this->LineWidth : 0,
                'borderColor' => $this->color($this->DrawColor), 'backgroundColor' => $fill ? $this->color($this->FillColor) : '', 'content' => '']);
        }
        if ($txt !== '') {
            $this->field(['type' => 'text', 'position' => ['x' => $this->x + $this->cMargin, 'y' => $this->y],
                'width' => max(1, $width - 2 * $this->cMargin), 'height' => max($h, $this->FontSize * 1.2),
                'content' => iconv('windows-1252', 'UTF-8', $txt), 'fontSize' => $this->FontSizePt,
                'fontColor' => $this->color($this->TextColor), 'alignment' => match ($align) {
                    'C' => 'center', 'R' => 'right', default => 'left'
                },
                'verticalAlignment' => 'middle', 'lineHeight' => 1,
                'source_font' => $this->FontFamily, 'source_style' => $this->FontStyle]);
        }

        return parent::Cell($w, $h, $txt, $border, $ln, $align, $fill, $link);
    }

    public function Rect($x, $y, $w, $h, $style = '')
    {
        $fill = str_contains($style, 'F');
        $stroke = $style !== 'F';
        $this->field(['type' => 'rectangle', 'position' => ['x' => $x, 'y' => $y], 'width' => $w, 'height' => $h,
            'borderWidth' => $stroke ? $this->LineWidth : 0, 'borderColor' => $this->color($this->DrawColor),
            'color' => $fill ? $this->color($this->FillColor) : '', 'backgroundColor' => $fill ? $this->color($this->FillColor) : '', 'content' => '']);

        return parent::Rect($x, $y, $w, $h, $style);
    }

    public function Line($x1, $y1, $x2, $y2)
    {
        $this->field(['type' => 'line', 'position' => ['x' => min($x1, $x2), 'y' => min($y1, $y2)],
            'width' => max(.01, abs($x2 - $x1)), 'height' => max(.01, abs($y2 - $y1)),
            'color' => $this->color($this->DrawColor), 'content' => '']);

        return parent::Line($x1, $y1, $x2, $y2);
    }

    public function Image($file, $x = null, $y = null, $w = 0, $h = 0, $type = '', $link = '')
    {
        parent::Image($file, $x, $y, $w, $h, $type, $link);
        $info = getimagesize($file);
        if (! $h) {
            $h = $w * $info[1] / $info[0];
        }
        $key = count($this->capturedImages);
        $this->capturedImages[$key] = ['bytes' => file_get_contents($file), 'mime' => $info['mime']];
        $this->field(['type' => 'image', 'position' => ['x' => $x, 'y' => $y], 'width' => $w, 'height' => $h,
            'source_image' => $key, 'content' => '']);
    }
}

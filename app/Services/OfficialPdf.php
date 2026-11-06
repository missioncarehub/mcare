<?php

namespace App\Services;

use FPDF;

class OfficialPdf extends FPDF
{
    public function paintWatermark(string $label): void
    {
        $this->SetTextColor(214, 196, 232);
        $this->SetFont('Helvetica', 'B', 22);
        $this->rotatedText($this->w * 0.16, $this->h * 0.62, $label, 32);
        $this->rotatedText($this->w * 0.42, $this->h * 0.38, $label, 32);
        $this->SetTextColor(0, 0, 0);
    }

    public function rotatedText(float $x, float $y, string $text, float $angle): void
    {
        $angle *= M_PI / 180;
        $cosine = cos($angle);
        $sine = sin($angle);
        $centerX = $x * $this->k;
        $centerY = ($this->h - $y) * $this->k;
        $this->_out(sprintf(
            'q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',
            $cosine,
            $sine,
            -$sine,
            $cosine,
            $centerX,
            $centerY,
            -$centerX,
            -$centerY,
        ));
        $this->Text($x, $y, $text);
        $this->_out('Q');
    }

    public function presentOnPaper(string $orientation, string $size): void
    {
        if ($this->page < 1) {
            return;
        }

        $orientation = strtoupper(substr($orientation, 0, 1)) === 'L' ? 'L' : 'P';
        $dimensions = $this->_getpagesize($size);
        $width = $orientation === 'P' ? $dimensions[0] : $dimensions[1];
        $height = $orientation === 'P' ? $dimensions[1] : $dimensions[0];
        $scale = min($width / $this->w, $height / $this->h);

        if (abs($width - $this->w) < 0.2 && abs($height - $this->h) < 0.2) {
            return;
        }

        $this->PageInfo[$this->page]['size'] = [$width * $this->k, $height * $this->k];
        if (abs($scale - 1) >= 0.001) {
            $this->pages[$this->page] = sprintf("%.5F 0 0 %.5F 0 0 cm\n", $scale, $scale).$this->pages[$this->page];
        }
    }
}

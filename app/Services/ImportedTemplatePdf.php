<?php

namespace App\Services;

use setasign\Fpdi\Fpdi;

class ImportedTemplatePdf extends Fpdi
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
}

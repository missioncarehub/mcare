<?php

namespace App\Services;

final class LandscapeLetterPdf extends OfficialPdf
{
    public const WIDTH_MM = 279.4;

    public const HEIGHT_MM = 215.9;

    public function __construct()
    {
        parent::__construct('L', 'mm', 'Letter');
        $this->forceLandscapeLetter();
    }

    public function AddPage($orientation = '', $size = '', $rotation = 0)
    {
        parent::AddPage('L', 'Letter', $rotation);
        $this->forceLandscapeLetter();
    }

    private function forceLandscapeLetter(): void
    {
        $this->DefOrientation = 'L';
        $this->CurOrientation = 'L';
        $this->DefPageSize = [self::HEIGHT_MM, self::WIDTH_MM];
        $this->CurPageSize = [self::HEIGHT_MM, self::WIDTH_MM];
        $this->w = self::WIDTH_MM;
        $this->h = self::HEIGHT_MM;
        $this->wPt = $this->w * $this->k;
        $this->hPt = $this->h * $this->k;
        $this->PageBreakTrigger = $this->h - $this->bMargin;

        if ($this->page > 0) {
            $this->PageInfo[$this->page]['size'] = [$this->wPt, $this->hPt];
        }
    }
}

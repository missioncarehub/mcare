<?php

namespace App\Services;

use App\Contracts\OfficialDocumentRenderer;
use App\Models\OfficialDocument;
use FPDF;
use RuntimeException;

class FpdfOfficialDocumentRenderer implements OfficialDocumentRenderer
{
    /** @var list<string> */
    private array $temporaryImages = [];

    public function render(OfficialDocument $document): string
    {
        $document->loadMissing([
            'application.batch',
            'application.competencyRecords.unit',
        ]);

        $this->temporaryImages = [];

        try {
            $pdf = match ($document->type) {
                OfficialDocument::TYPE_COTC => $this->renderCotc($document),
                OfficialDocument::TYPE_TOR => $this->renderTor($document),
                default => throw new RuntimeException('Unsupported official document type: '.$document->type),
            };

            return $pdf->Output('S');
        } finally {
            $this->forgetTemporaryImages();
        }
    }

    private function renderCotc(OfficialDocument $document): FPDF
    {
        $pageWidth = 279.4;
        $pageHeight = 215.9;
        $application = $document->application;
        $organization = config('official_documents.organization', []);
        $fullName = $this->latin(mb_strtoupper($this->fullName($application)));
        $completionDate = $application->batch?->training_ends_at ?? $document->generated_at ?? now();
        $dateLine = $this->latin(sprintf(
            'Given this %s of %s at %s',
            $completionDate->format('jS'),
            $completionDate->format('F, Y'),
            (string) ($organization['name'] ?? 'Mission Care Training Center'),
        ));
        $documentLine = $this->latin($document->document_number.' | Version '.$document->version);

        $pdf = new FPDF('P', 'mm', [$pageWidth, $pageHeight]);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->AddPage();
        $pdf->Image(
            $this->preparedJpeg(public_path('assets/cotc-official-template.png'), $pageWidth, $pageHeight),
            0,
            0,
            $pageWidth,
            $pageHeight,
            'JPG',
        );

        $nameWidth = 178.5;
        $nameHeight = 15.5;
        $nameX = 14.0;
        $nameY = 91.0;
        $pdf->SetFillColor(255, 252, 255);
        $pdf->Rect($nameX, $nameY, $nameWidth, $nameHeight, 'F');
        $pdf->SetDrawColor(207, 98, 234);
        $pdf->SetLineWidth(0.35);
        $pdf->Line($nameX, $nameY + $nameHeight, $nameX + $nameWidth, $nameY + $nameHeight);
        $this->writeFitted(
            $pdf,
            $nameX + 3,
            $nameY,
            $nameWidth - 6,
            $nameHeight - 2.2,
            $fullName,
            'Times',
            'B',
            mb_strlen($fullName) > 30 ? 20 : 25,
            'C',
            'B',
            11,
        );

        $dateWidth = 163.0;
        $dateHeight = 11.5;
        $dateX = 28.0;
        $dateY = 137.0;
        $pdf->SetFillColor(255, 252, 255);
        $pdf->Rect($dateX, $dateY, $dateWidth, $dateHeight, 'F');
        $this->writeFitted($pdf, $dateX, $dateY, $dateWidth, $dateHeight, $dateLine, 'Times', '', 11, 'C', 'M', 8);

        $pdf->SetFillColor(255, 252, 255);
        $pdf->Rect($pageWidth - 70, $pageHeight - 10.5, 62, 5, 'F');
        $pdf->SetTextColor(71, 85, 105);
        $this->writeFitted(
            $pdf,
            $pageWidth - 70,
            $pageHeight - 10.5,
            62,
            5,
            $documentLine,
            'Helvetica',
            '',
            5.5,
            'R',
            'M',
            5,
        );
        $pdf->SetTextColor(0, 0, 0);

        return $pdf;
    }

    private function renderTor(OfficialDocument $document): FPDF
    {
        $application = $document->application;
        $organization = config('official_documents.organization', []);
        $fullName = $this->latin(mb_strtoupper($this->fullName($application)));
        $address = $this->latin(collect([
            $application->street,
            $application->barangay,
            $application->city,
            $application->province,
            $application->zip_code,
        ])->filter()->implode(', ') ?: '-');
        $records = $application->competencyRecords
            ->filter(fn ($record) => $record->unit?->is_tor_included)
            ->sortBy(fn ($record) => $record->unit?->sort_order)
            ->values();

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->AddPage();

        $pageWidth = 210.0;
        $marginX = 8.0;
        $contentWidth = $pageWidth - ($marginX * 2);
        $y = 7.0;

        $logoSize = 20.0;
        $logoX = (($pageWidth - 150) / 2);
        $pdf->Image(
            $this->preparedJpeg(public_path('assets/official-logo.png'), $logoSize, $logoSize),
            $logoX,
            $y,
            $logoSize,
            $logoSize,
            'JPG',
        );
        $this->writeFitted(
            $pdf,
            $logoX + $logoSize + 5,
            $y,
            125,
            8,
            $this->latin((string) ($organization['name'] ?? 'Mission Care Training Center')),
            'Helvetica',
            '',
            20,
            'C',
            'T',
            11,
        );
        $this->writeFitted(
            $pdf,
            $logoX + $logoSize + 5,
            $y + 9,
            125,
            8,
            $this->latin(trim(($organization['address'] ?? '')."\n".($organization['phone'] ?? ''))),
            'Helvetica',
            '',
            7,
            'C',
            'T',
            6,
        );
        $this->writeFitted(
            $pdf,
            $logoX + $logoSize + 5,
            $y + 17.5,
            125,
            6,
            'REGISTRAR',
            'Times',
            'B',
            13,
            'C',
            'M',
            10,
        );

        $y = 36.5;
        $pdf->SetFillColor(156, 122, 195);
        $pdf->Rect($marginX + 4, $y, $contentWidth - 8, 1.2, 'F');

        $y = 41.0;
        $this->writeFitted($pdf, $marginX, $y, $contentWidth, 8, 'OFFICIAL TRANSCRIPT OF RECORD', 'Times', 'B', 13.5, 'C', 'M', 11);
        $this->writeFitted($pdf, $marginX, $y + 9, $contentWidth, 9, '"'.$fullName.'"', 'Times', 'B', 16, 'C', 'M', 10);
        $this->writeFitted($pdf, $marginX, $y + 19, $contentWidth, 7, 'CAREGIVING NC II', 'Times', 'B', 12.5, 'C', 'M', 10);
        $hours = (int) ($organization['course_hours'] ?? 786);
        $this->writeFitted($pdf, $marginX, $y + 26, $contentWidth, 8, $this->latin("Course\n({$hours} Hours)"), 'Times', 'I', 10, 'C', 'T', 8);

        $gridY = 76.0;
        $leftWidth = 52.0;
        $rightWidth = $contentWidth - $leftWidth;
        $leftX = $marginX;
        $rightX = $marginX + $leftWidth;
        $rowCount = max(1, $records->count());
        $gradeRowHeight = min(8.25, max(6.2, 90 / ($rowCount + 1)));
        $rightHeight = 10 + ($gradeRowHeight * $rowCount) + 23;
        $leftHeight = 17 + 17 + 16 + 19 + 65 + 18;
        $gridHeight = max($leftHeight, $rightHeight);

        $pdf->SetDrawColor(17, 17, 17);
        $pdf->SetLineWidth(0.35);
        $pdf->SetFillColor(201, 201, 248);
        $pdf->Rect($leftX, $gridY, $leftWidth, $gridHeight, 'DF');
        $pdf->SetFillColor(250, 203, 250);
        $pdf->Rect($rightX, $gridY, $rightWidth, $gridHeight, 'DF');

        $blockY = $gridY;
        $this->torField($pdf, $leftX, $blockY, $leftWidth, 17, 'Entrance Date:', $application->batch?->training_starts_at?->format('F j, Y') ?? '-');
        $blockY += 17;
        $this->torField($pdf, $leftX, $blockY, $leftWidth, 17, 'Address:', $address, 6.5);
        $blockY += 17;
        $this->torField(
            $pdf,
            $leftX,
            $blockY,
            $leftWidth,
            16,
            'Last School Attended:',
            $this->latin((string) ($application->school_name ?: '-'))."\nCategory:\n".$this->latin((string) ($application->classification ?: '-')),
            6.5,
        );
        $blockY += 16;
        $this->torField(
            $pdf,
            $leftX,
            $blockY,
            $leftWidth,
            19,
            'TITLE/COURSE:',
            "CAREGIVING NC II\nDate Completed/Graduated:\n".$this->latin($application->batch?->training_ends_at?->format('F j, Y') ?? '-'),
            6.5,
        );
        $blockY += 19;
        $this->torField($pdf, $leftX, $blockY, $leftWidth, 65, 'Official Marks:', $this->officialMarksText(), 6.2);
        $blockY += 65;
        $this->torField($pdf, $leftX, $blockY, $leftWidth, 18, 'Remarks:', "\nFor Records and Reference Purposes", 7);

        $headerY = $gridY;
        $codeW = 22.0;
        $gradeW = 22.0;
        $remarksW = 28.0;
        $titleW = $rightWidth - $codeW - $gradeW - $remarksW;
        $pdf->SetFillColor(250, 203, 250);
        $this->torCell($pdf, $rightX, $headerY, $codeW, 10, 'Course Code', 'B', 8, 'C');
        $this->torCell($pdf, $rightX + $codeW, $headerY, $titleW, 10, 'Course Title', 'B', 8, 'C');
        $this->torCell($pdf, $rightX + $codeW + $titleW, $headerY, $gradeW, 10, 'Final Grade', 'B', 8, 'C');
        $this->torCell($pdf, $rightX + $codeW + $titleW + $gradeW, $headerY, $remarksW, 10, 'Remarks', 'B', 8, 'C');

        $rowY = $headerY + 10;
        foreach ($records as $record) {
            $grade = $record->tor_grade ? number_format((float) $record->tor_grade, 2) : '-';
            $remark = $record->status === 'competent' ? 'COMPETENT' : 'NOT YET COMPETENT';
            $this->torCell($pdf, $rightX, $rowY, $codeW, $gradeRowHeight, $this->latin((string) $record->unit?->code), '', 7.5, 'C');
            $this->torCell($pdf, $rightX + $codeW, $rowY, $titleW, $gradeRowHeight, $this->latin((string) $record->unit?->title), '', 7.2, 'L');
            $this->torCell($pdf, $rightX + $codeW + $titleW, $rowY, $gradeW, $gradeRowHeight, $grade, 'B', 7.5, 'C');
            $this->torCell($pdf, $rightX + $codeW + $titleW + $gradeW, $rowY, $remarksW, $gradeRowHeight, $remark, 'B', 6.8, 'C');
            $rowY += $gradeRowHeight;
        }

        $nothingHeight = $gridY + $gridHeight - $rowY;
        $this->torCell($pdf, $rightX, $rowY, $rightWidth, $nothingHeight, '* * * * * NOTHING FOLLOWS * * * * *', '', 8, 'C', 'T');

        $approvalY = $gridY + $gridHeight;
        $approvalHeight = 32.0;
        $midWidth = ($contentWidth - $leftWidth) / 2;
        $pdf->SetFillColor(201, 201, 248);
        $pdf->Rect($leftX, $approvalY, $leftWidth, $approvalHeight, 'DF');
        $pdf->SetFillColor(250, 203, 250);
        $pdf->Rect($rightX, $approvalY, $midWidth, $approvalHeight, 'DF');
        $pdf->Rect($rightX + $midWidth, $approvalY, $midWidth, $approvalHeight, 'DF');

        $this->approvalBlock($pdf, $leftX, $approvalY, $leftWidth, $approvalHeight, 'Prepared by:', (string) ($organization['trainer_name'] ?? ''), 'Lead Trainer');
        $this->approvalBlock($pdf, $rightX, $approvalY, $midWidth, $approvalHeight, 'Certified by:', (string) ($organization['registrar_name'] ?? ''), 'Registrar');
        $this->approvalBlock($pdf, $rightX + $midWidth, $approvalY, $midWidth, $approvalHeight, 'Seal', '', 'Not valid without official seal');

        $this->writeFitted(
            $pdf,
            $marginX,
            $approvalY + $approvalHeight + 2,
            $contentWidth,
            5,
            $this->latin('Document no. '.$document->document_number.' | Version '.$document->version),
            'Helvetica',
            '',
            5.5,
            'R',
            'M',
            5,
        );

        return $pdf;
    }

    private function torField(
        FPDF $pdf,
        float $x,
        float $y,
        float $width,
        float $height,
        string $label,
        string $value,
        float $size = 7,
    ): void {
        $pdf->SetDrawColor(17, 17, 17);
        $pdf->Line($x, $y + $height, $x + $width, $y + $height);
        $this->writeFitted($pdf, $x + 1.2, $y + 1, $width - 2.4, 4, $this->latin($label), 'Helvetica', 'B', 7.2, 'L', 'T', 6);
        $this->writeFitted($pdf, $x + 1.2, $y + 5, $width - 2.4, $height - 6, $this->latin($value), 'Helvetica', '', $size, 'L', 'T', 5.5);
    }

    private function torCell(
        FPDF $pdf,
        float $x,
        float $y,
        float $width,
        float $height,
        string $text,
        string $style,
        float $size,
        string $align,
        string $valign = 'M',
    ): void {
        $pdf->SetDrawColor(17, 17, 17);
        $pdf->Rect($x, $y, $width, $height);
        $padding = $align === 'L' ? 1.2 : 0.6;
        $this->writeFitted(
            $pdf,
            $x + $padding,
            $y,
            $width - ($padding * 2),
            $height,
            $this->latin($text),
            'Helvetica',
            $style,
            $size,
            $align,
            $valign,
            5.5,
        );
    }

    private function approvalBlock(
        FPDF $pdf,
        float $x,
        float $y,
        float $width,
        float $height,
        string $label,
        string $name,
        string $role,
    ): void {
        $this->writeFitted($pdf, $x + 2, $y + 2, $width - 4, 5, $this->latin($label), 'Helvetica', '', 6.5, 'C', 'T', 6);
        if ($name !== '') {
            $this->writeFitted($pdf, $x + 2, $y + $height - 12, $width - 4, 6, $this->latin(mb_strtoupper($name)), 'Helvetica', 'B', 7.2, 'C', 'M', 6);
        }
        $this->writeFitted($pdf, $x + 2, $y + $height - 6, $width - 4, 5, $this->latin($role), 'Helvetica', '', 6.3, 'C', 'M', 5.5);
    }

    private function officialMarksText(): string
    {
        $left = [
            '1.00 - 99%', '1.10 - 98%', '1.20 - 97%', '1.25 - 96%', '1.30 - 95%',
            '1.40 - 94%', '1.50 - 93%', '1.60 - 92%', '1.70 - 91%', '1.75 - 90%',
            '1.80 - 89%', '1.90 - 88%',
        ];
        $right = [
            '2.00 - 87%', '2.10 - 86%', '2.20 - 85%', '2.25 - 84%', '2.30 - 83%',
            '2.40 - 82%', '2.50 - 81%', '2.60 - 80%', '2.70 - 79%', '2.75 - 78%',
            '2.80 - 77%', '2.90 - 76%', '3.00 - 75%',
        ];

        $lines = [];
        for ($i = 0; $i < max(count($left), count($right)); $i++) {
            $lines[] = sprintf('%-12s %s', $left[$i] ?? '', $right[$i] ?? '');
        }

        return implode("\n", $lines)."\n\n1.00-3.00 Competent\n4.00-5.00 Not Yet Competent";
    }

    private function writeFitted(
        FPDF $pdf,
        float $x,
        float $y,
        float $width,
        float $height,
        string $text,
        string $font,
        string $style,
        float $size,
        string $align,
        string $valign,
        float $minimumSize,
    ): void {
        $text = trim($text);
        if ($text === '') {
            return;
        }

        $lines = preg_split("/\n+/", $text) ?: [$text];
        while ($size > $minimumSize) {
            $pdf->SetFont($font, $style, $size);
            $tooWide = false;
            foreach ($lines as $line) {
                if ($pdf->GetStringWidth($line) > $width) {
                    $tooWide = true;
                    break;
                }
            }
            if (! $tooWide && ((count($lines) * ($size * 0.42)) <= $height)) {
                break;
            }
            $size -= 0.4;
        }

        $pdf->SetFont($font, $style, $size);
        $lineHeight = min($height / max(1, count($lines)), $size * 0.45);
        $blockHeight = $lineHeight * count($lines);
        $textY = match ($valign) {
            'T' => $y,
            'B' => $y + $height - $blockHeight,
            default => $y + (($height - $blockHeight) / 2),
        };

        foreach ($lines as $line) {
            if ($pdf->GetStringWidth($line) > $width) {
                while (strlen($line) > 1 && $pdf->GetStringWidth($line.'...') > $width) {
                    $line = rtrim(substr($line, 0, -1));
                }
                $line .= '...';
            }
            $pdf->SetXY($x, $textY);
            $pdf->Cell($width, $lineHeight, $line, 0, 0, $align);
            $textY += $lineHeight;
        }
    }

    private function fullName(object $application): string
    {
        return trim(collect([
            $application->first_name,
            $application->middle_name,
            $application->last_name,
            $application->extension_name,
        ])->filter()->implode(' '));
    }

    private function preparedJpeg(string $sourcePath, float $widthMm, float $heightMm): string
    {
        if (! is_file($sourcePath)) {
            throw new RuntimeException('Official document asset is missing: '.basename($sourcePath));
        }

        if (! function_exists('imagecreatefromstring')) {
            throw new RuntimeException('PHP GD is required to generate official documents on this server.');
        }

        $binary = file_get_contents($sourcePath);
        if (! is_string($binary) || $binary === '') {
            throw new RuntimeException('Official document asset could not be read: '.basename($sourcePath));
        }

        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            throw new RuntimeException('Official document asset is not a readable image: '.basename($sourcePath));
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $dpi = 180;
        $targetWidth = max(1, (int) round($widthMm / 25.4 * $dpi));
        $targetHeight = max(1, (int) round($heightMm / 25.4 * $dpi));
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($canvas === false) {
            imagedestroy($source);
            throw new RuntimeException('Official document image could not be prepared.');
        }

        imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
        imagedestroy($source);

        $base = tempnam(sys_get_temp_dir(), 'mcare-official-');
        if ($base === false) {
            imagedestroy($canvas);
            throw new RuntimeException('Official document image could not be stored.');
        }

        $outputPath = $base.'.jpg';
        @unlink($base);
        $written = imagejpeg($canvas, $outputPath, 88);
        imagedestroy($canvas);

        if (! $written || ! is_file($outputPath)) {
            throw new RuntimeException('Official document image could not be encoded.');
        }

        $this->temporaryImages[] = $outputPath;

        return $outputPath;
    }

    private function forgetTemporaryImages(): void
    {
        foreach ($this->temporaryImages as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $this->temporaryImages = [];
    }

    private function latin(string $value): string
    {
        $converted = iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $value);

        return $converted === false ? (preg_replace('/[^\x20-\x7E\n]/', '', $value) ?? '') : $converted;
    }
}

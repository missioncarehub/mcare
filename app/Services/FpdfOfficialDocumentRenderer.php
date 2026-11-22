<?php

namespace App\Services;

use App\Contracts\OfficialDocumentRenderer;
use App\Models\CertificateTemplate;
use App\Models\OfficialDocument;
use App\Models\PublicSiteSetting;
use FPDF;
use RuntimeException;

class FpdfOfficialDocumentRenderer implements OfficialDocumentRenderer
{
    /** @var list<string> */
    private array $temporaryImages = [];

    public function render(OfficialDocument $document): string
    {
        return $this->renderOnCanvas($document);
    }

    public function renderOnCanvas(OfficialDocument $document, ?FPDF $studioCanvas = null): string
    {
        $document->loadMissing([
            'application.batch',
            'application.trainingProgram',
            'application.competencyRecords.unit',
        ]);

        $this->temporaryImages = [];

        try {
            if ($studioCanvas === null) {
                $imported = $this->renderImported($document);
                if ($imported !== null) {
                    return $imported;
                }
            }

            $pdf = match ($document->type) {
                OfficialDocument::TYPE_COTC => $this->renderCotc($document, $studioCanvas),
                OfficialDocument::TYPE_TOR => $this->renderTor($document, $studioCanvas),
                default => throw new RuntimeException('Unsupported official document type: '.$document->type),
            };

            return $pdf->Output('S');
        } finally {
            $this->forgetTemporaryImages();
        }
    }

    private function renderImported(OfficialDocument $document): ?string
    {
        $template = CertificateTemplate::activeFor($document->type, $document->application?->training_program_id);
        if (! $template?->usesImportedBackground()) {
            return null;
        }

        $records = collect($document->application?->competencyRecords)
            ->filter(fn ($record) => $record->unit?->is_tor_included)
            ->sortBy(fn ($record) => $record->unit?->sort_order)
            ->map(function ($record): string {
                $remark = $record->status === 'competent' ? 'COMPETENT' : 'NOT YET COMPETENT';

                return trim(((string) $record->unit?->code).' '.((string) $record->unit?->title).' '.$remark);
            })
            ->implode("\n");
        $organization = config('official_documents.organization', []);

        return app(ImportedCertificateRenderer::class)->render(
            $template,
            CertificateTemplate::placeholderValues($document),
            [
                'signatory_name' => $this->signatoryName($template->signatory_name, PublicSiteSetting::current()->registrarName() ?: (string) ($organization['registrar_name'] ?? '')),
                'signatory_role' => $this->signatoryName($template->signatory_role, 'Registrar'),
                'signatory_signature' => $template->absoluteAsset($template->signature_path),
                'second_signatory_name' => $this->signatoryName($template->second_signatory_name, (string) ($organization['trainer_name'] ?? '')),
                'second_signatory_role' => $this->signatoryName($template->second_signatory_role, 'Trainer'),
                'second_signatory_signature' => $template->absoluteAsset($template->second_signature_path),
                'records' => $records,
            ],
        );
    }

    private function renderCotc(OfficialDocument $document, ?FPDF $studioCanvas = null): FPDF
    {
        $application = $document->application;
        $organization = config('official_documents.organization', []);
        $template = CertificateTemplate::activeFor(OfficialDocument::TYPE_COTC, $application?->training_program_id);
        $values = CertificateTemplate::placeholderValues($document);
        if ($template && ! $template->usesOfficialArtwork()) {
            return $this->renderManagedCotc($document, $template, $values);
        }

        $fullName = $this->latin(mb_strtoupper($this->fullName($application)));
        $completionDate = $application->batch?->training_ends_at ?? $document->generated_at ?? now();
        $dateLine = $template
            ? $this->latin(CertificateTemplate::replacePlaceholders((string) $template->body, $values))
            : $this->latin(sprintf(
                'Given this %s of %s at %s',
                $completionDate->format('jS'),
                $completionDate->format('F, Y'),
                (string) ($organization['name'] ?? 'Mission Care Training Center'),
            ));
        $documentLine = $this->verificationLine($document);

        $pdf = $studioCanvas ?? new LandscapeLetterPdf;
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->AddPage();

        $pageWidth = $pdf->GetPageWidth();
        $pageHeight = $pdf->GetPageHeight();
        $scale = min($pageWidth / LandscapeLetterPdf::WIDTH_MM, $pageHeight / LandscapeLetterPdf::HEIGHT_MM);
        $drawWidth = LandscapeLetterPdf::WIDTH_MM * $scale;
        $drawHeight = LandscapeLetterPdf::HEIGHT_MM * $scale;
        $originX = ($pageWidth - $drawWidth) / 2;
        $originY = ($pageHeight - $drawHeight) / 2;

        $pdf->Image(
            $this->preparedJpeg(public_path('assets/cotc-official-template.png'), $drawWidth, $drawHeight),
            $originX,
            $originY,
            $drawWidth,
            $drawHeight,
            'JPG',
        );

        $name = $this->cotcBox($originX, $originY, $scale, 14.0, 91.0, 178.5, 15.5);
        $pdf->SetFillColor(255, 252, 255);
        $pdf->Rect($name['x'], $name['y'], $name['w'], $name['h'], 'F');
        $pdf->SetDrawColor(207, 98, 234);
        $pdf->SetLineWidth(0.35 * $scale);
        $pdf->Line($name['x'], $name['y'] + $name['h'], $name['x'] + $name['w'], $name['y'] + $name['h']);
        $this->writeFitted(
            $pdf,
            $name['x'] + (3 * $scale),
            $name['y'],
            $name['w'] - (6 * $scale),
            $name['h'] - (2.2 * $scale),
            $fullName,
            'Times',
            'B',
            (mb_strlen($fullName) > 30 ? 20 : 25) * $scale,
            'C',
            'B',
            11 * $scale,
        );

        $date = $this->cotcBox($originX, $originY, $scale, 28.0, 137.0, 163.0, 11.5);
        $pdf->SetFillColor(255, 252, 255);
        $pdf->Rect($date['x'], $date['y'], $date['w'], $date['h'], 'F');
        $this->writeFitted($pdf, $date['x'], $date['y'], $date['w'], $date['h'], $dateLine, 'Times', '', 11 * $scale, 'C', 'M', 8 * $scale);

        $number = $this->cotcBox($originX, $originY, $scale, LandscapeLetterPdf::WIDTH_MM - 70, LandscapeLetterPdf::HEIGHT_MM - 10.5, 62, 5);
        $pdf->SetFillColor(255, 252, 255);
        $pdf->Rect($number['x'], $number['y'], $number['w'], $number['h'], 'F');
        $pdf->SetTextColor(71, 85, 105);
        $this->writeFitted(
            $pdf,
            $number['x'],
            $number['y'],
            $number['w'],
            $number['h'],
            $documentLine,
            'Helvetica',
            '',
            5.5 * $scale,
            'R',
            'M',
            5 * $scale,
        );
        $pdf->SetTextColor(0, 0, 0);

        if ($template) {
            $this->overlayCotcTemplate($pdf, $template, $values, $originX, $originY, $scale);
        }

        return $pdf;
    }

    private function renderTor(OfficialDocument $document, ?FPDF $studioCanvas = null): FPDF
    {
        $application = $document->application;
        $organization = config('official_documents.organization', []);
        $fullName = $this->latin(mb_strtoupper($this->fullName($application)));
        $address = $this->wrapPlain($this->latin(collect([
            $application->street,
            $application->barangay,
            $application->city,
            $application->province,
            $application->zip_code,
        ])->filter()->implode(', ') ?: '-'), 42);
        $records = $application->competencyRecords
            ->filter(fn ($record) => $record->unit?->is_tor_included)
            ->sortBy(fn ($record) => $record->unit?->sort_order)
            ->values();

        $template = CertificateTemplate::activeFor(OfficialDocument::TYPE_TOR, $application?->training_program_id);
        $values = CertificateTemplate::placeholderValues($document);
        $title = $template
            ? CertificateTemplate::replacePlaceholders((string) $template->title, $values)
            : PublicSiteSetting::torTitle();
        $body = $template ? trim(CertificateTemplate::replacePlaceholders((string) $template->body, $values)) : '';
        $trainerName = $this->signatoryName($template?->second_signatory_name, (string) ($organization['trainer_name'] ?? ''));
        $trainerRole = $this->signatoryName($template?->second_signatory_role, 'Lead Trainer');
        $registrarName = $this->signatoryName(
            $template?->signatory_name,
            PublicSiteSetting::current()->registrarName() ?: (string) ($organization['registrar_name'] ?? ''),
        );
        $registrarRole = $this->signatoryName($template?->signatory_role, 'Registrar');
        $logoPath = $template?->absoluteAsset($template->logo_path) ?? public_path('assets/official-logo.png');

        $pdf = $studioCanvas ?? new OfficialPdf('P', 'mm', 'A4');
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
            $this->preparedJpeg($logoPath, $logoSize, $logoSize),
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

        $programTitle = mb_strtoupper((string) ($application->trainingProgram?->name ?? 'CAREGIVING NC II'));

        $y = 41.0;
        $this->writeFitted($pdf, $marginX, $y, $contentWidth, 8, $this->latin($title), 'Times', 'B', 13.5, 'C', 'M', 11);
        $this->writeFitted($pdf, $marginX, $y + 9, $contentWidth, 9, '"'.$fullName.'"', 'Times', 'B', 16, 'C', 'M', 10);
        $this->writeFitted($pdf, $marginX, $y + 19, $contentWidth, 7, $this->latin($programTitle), 'Times', 'B', 12.5, 'C', 'M', 10);
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
            $this->latin($programTitle)."\nDate Completed/Graduated:\n".$this->latin($application->batch?->training_ends_at?->format('F j, Y') ?? '-'),
            6.5,
        );
        $blockY += 19;
        $this->torField($pdf, $leftX, $blockY, $leftWidth, 65, 'Official Marks:', $this->officialMarksText(), 6.2);
        $blockY += 65;
        $remarks = 'For Records and Reference Purposes';
        if ($body !== '') {
            $remarks .= "\n".$body;
        }
        $this->torField($pdf, $leftX, $blockY, $leftWidth, 18, 'Remarks:', $remarks, 7, 'I');

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

        $this->approvalBlock($pdf, $leftX, $approvalY, $leftWidth, $approvalHeight, 'Prepared by:', $trainerName, $trainerRole, $template?->absoluteAsset($template->second_signature_path));
        $this->approvalBlock($pdf, $rightX, $approvalY, $midWidth, $approvalHeight, 'Certified by:', $registrarName, $registrarRole, $template?->absoluteAsset($template->signature_path));
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

        if ($template && ($template->paper_size !== 'a4' || $template->orientation !== 'portrait')) {
            $pdf->presentOnPaper($template->orientation === 'landscape' ? 'L' : 'P', match ($template->paper_size) {
                'letter' => 'Letter',
                'legal' => 'Legal',
                default => 'A4',
            });
        }

        return $pdf;
    }

    /**
     * @param  array<string, string>  $values
     */
    private function renderManagedCotc(OfficialDocument $document, CertificateTemplate $template, array $values): FPDF
    {
        [$orientation, $size, $pageWidth, $pageHeight] = $this->paperSpec($template);
        $pdf = new OfficialPdf($orientation, 'mm', $size);
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);
        $pdf->AddPage();

        $margin = 14.0;
        $contentWidth = $pageWidth - ($margin * 2);
        $logoSize = 22.0;
        $logoPath = $template->absoluteAsset($template->logo_path) ?? public_path('assets/official-logo.png');
        $pdf->Image($this->preparedJpeg($logoPath, $logoSize, $logoSize), $margin, 12, $logoSize, $logoSize, 'JPG');
        $this->writeFitted(
            $pdf,
            $margin + $logoSize + 4,
            14,
            $contentWidth - $logoSize - 4,
            10,
            $this->latin($values['organization']),
            'Times',
            'B',
            16,
            'L',
            'M',
            10,
        );

        $this->writeFitted($pdf, $margin, 42, $contentWidth, 16, $this->latin(CertificateTemplate::replacePlaceholders((string) $template->title, $values)), 'Times', 'B', 22, 'C', 'M', 12);
        $this->writeFitted($pdf, $margin, 60, $contentWidth, 8, 'This certifies that', 'Times', 'I', 13, 'C', 'M', 10);
        $this->writeFitted($pdf, $margin, 70, $contentWidth, 14, $this->latin(mb_strtoupper($values['trainee_name'])), 'Times', 'B', 20, 'C', 'M', 11);
        $pdf->SetDrawColor(207, 98, 234);
        $pdf->SetLineWidth(0.4);
        $pdf->Line($margin + 18, 86, $pageWidth - $margin - 18, 86);
        $this->writeFitted($pdf, $margin + 8, 92, $contentWidth - 16, 28, $this->latin(CertificateTemplate::replacePlaceholders((string) $template->body, $values)), 'Times', '', 12, 'C', 'T', 8);

        $blockWidth = ($contentWidth - 12) / 2;
        $blockY = $pageHeight - 48;
        $this->signatoryBlock(
            $pdf,
            $margin,
            $blockY,
            $blockWidth,
            $this->signatoryName($template->second_signatory_name, (string) config('official_documents.organization.trainer_name', '')),
            $this->signatoryName($template->second_signatory_role, 'TVI Trainer'),
            $template->absoluteAsset($template->second_signature_path),
        );
        $this->signatoryBlock(
            $pdf,
            $margin + $blockWidth + 12,
            $blockY,
            $blockWidth,
            $this->signatoryName($template->signatory_name, (string) config('official_documents.organization.registrar_name', '')),
            $this->signatoryName($template->signatory_role, 'TVI Registrar'),
            $template->absoluteAsset($template->signature_path),
        );
        $this->writeFitted($pdf, $margin, $pageHeight - 12, $contentWidth, 5, $this->verificationLine($document), 'Helvetica', '', 8, 'R', 'M', 6);

        return $pdf;
    }

    /**
     * @param  array<string, string>  $values
     */
    private function overlayCotcTemplate(
        OfficialPdf $pdf,
        CertificateTemplate $template,
        array $values,
        float $originX,
        float $originY,
        float $scale,
    ): void {
        $title = trim(CertificateTemplate::replacePlaceholders((string) $template->title, $values));
        if (strcasecmp($title, 'Certificate of Training Completion') !== 0 && $title !== '') {
            $titleBox = $this->cotcBox($originX, $originY, $scale, 16, 18, 176, 58);
            $pdf->SetFillColor(255, 252, 255);
            $pdf->Rect($titleBox['x'], $titleBox['y'], $titleBox['w'], $titleBox['h'], 'F');
            $this->writeFitted($pdf, $titleBox['x'] + (4 * $scale), $titleBox['y'], $titleBox['w'] - (8 * $scale), $titleBox['h'], $this->latin($title), 'Times', 'B', 22 * $scale, 'C', 'M', 11 * $scale);
        }

        $this->overlayCotcSignatory(
            $pdf,
            $originX,
            $originY,
            $scale,
            28,
            158,
            $this->signatoryName($template->second_signatory_name, (string) config('official_documents.organization.trainer_name', '')),
            $this->signatoryName($template->second_signatory_role, 'TVI Trainer'),
            $template->absoluteAsset($template->second_signature_path),
        );
        $this->overlayCotcSignatory(
            $pdf,
            $originX,
            $originY,
            $scale,
            112,
            158,
            $this->signatoryName($template->signatory_name, (string) config('official_documents.organization.registrar_name', '')),
            $this->signatoryName($template->signatory_role, 'TVI Registrar'),
            $template->absoluteAsset($template->signature_path),
        );
    }

    private function overlayCotcSignatory(
        OfficialPdf $pdf,
        float $originX,
        float $originY,
        float $scale,
        float $x,
        float $y,
        string $name,
        string $role,
        ?string $signaturePath,
    ): void {
        $box = $this->cotcBox($originX, $originY, $scale, $x, $y, 74, 32);
        $pdf->SetFillColor(255, 252, 255);
        $pdf->Rect($box['x'], $box['y'], $box['w'], $box['h'], 'F');
        if ($signaturePath) {
            $pdf->Image(
                $this->preparedJpeg($signaturePath, 36 * $scale, 10 * $scale),
                $box['x'] + (19 * $scale),
                $box['y'] + (1 * $scale),
                36 * $scale,
                10 * $scale,
                'JPG',
            );
        }
        $this->writeFitted($pdf, $box['x'], $box['y'] + (12 * $scale), $box['w'], 8 * $scale, $this->latin($name), 'Times', 'B', 11 * $scale, 'C', 'M', 7 * $scale);
        $this->writeFitted($pdf, $box['x'], $box['y'] + (21 * $scale), $box['w'], 7 * $scale, $this->latin($role), 'Times', '', 9 * $scale, 'C', 'M', 6 * $scale);
    }

    private function signatoryBlock(
        OfficialPdf $pdf,
        float $x,
        float $y,
        float $width,
        string $name,
        string $role,
        ?string $signaturePath,
    ): void {
        if ($signaturePath) {
            $pdf->Image($this->preparedJpeg($signaturePath, min(46, $width - 8), 14), $x + (($width - min(46, $width - 8)) / 2), $y, min(46, $width - 8), 14, 'JPG');
        }
        $pdf->SetDrawColor(15, 23, 42);
        $pdf->Line($x + 8, $y + 16, $x + $width - 8, $y + 16);
        $this->writeFitted($pdf, $x, $y + 17, $width, 6, $this->latin($name), 'Times', 'B', 11, 'C', 'M', 7);
        $this->writeFitted($pdf, $x, $y + 23, $width, 5, $this->latin($role), 'Times', '', 9, 'C', 'M', 7);
    }

    /** @return array{0: string, 1: string, 2: float, 3: float} */
    private function paperSpec(CertificateTemplate $template): array
    {
        $orientation = $template->orientation === 'landscape' ? 'L' : 'P';
        $size = match ($template->paper_size) {
            'letter' => 'Letter',
            'legal' => 'Legal',
            default => 'A4',
        };
        $dimensions = match ($size) {
            'Letter' => [215.9, 279.4],
            'Legal' => [215.9, 355.6],
            default => [210.0, 297.0],
        };
        $width = $orientation === 'L' ? $dimensions[1] : $dimensions[0];
        $height = $orientation === 'L' ? $dimensions[0] : $dimensions[1];

        return [$orientation, $size, $width, $height];
    }

    private function signatoryName(?string $value, string $fallback): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : $fallback;
    }

    private function verificationLine(OfficialDocument $document): string
    {
        $enrollment = trim((string) ($document->application?->enrollment_number ?? ''));
        if ($enrollment === '' && $document->application?->id) {
            $enrollment = 'ENR-'.$document->application->id;
        }

        $line = $document->document_number.' | Version '.$document->version;
        if ($enrollment !== '') {
            $line = 'Enrollment ID: '.$enrollment.' | '.$line;
        }

        return $this->latin($line);
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
        string $valueStyle = '',
    ): void {
        $pdf->SetDrawColor(17, 17, 17);
        $pdf->Line($x, $y + $height, $x + $width, $y + $height);
        $this->writeFitted($pdf, $x + 1.2, $y + 1, $width - 2.4, 4, $this->latin($label), 'Helvetica', 'B', 7.2, 'L', 'T', 6);
        $this->writeFitted($pdf, $x + 1.2, $y + 5, $width - 2.4, $height - 6, $this->latin($value), 'Helvetica', $valueStyle, $size, 'L', 'T', 5.5);
    }

    private function wrapPlain(string $text, int $width): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);
        if ($text === '') {
            return '-';
        }

        $lines = [];
        $line = '';
        foreach (explode(' ', $text) as $word) {
            $next = $line === '' ? $word : $line.' '.$word;
            if (strlen($next) > $width && $line !== '') {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $next;
            }
        }
        if ($line !== '') {
            $lines[] = $line;
        }

        return implode("\n", $lines);
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
        ?string $signaturePath = null,
    ): void {
        $this->writeFitted($pdf, $x + 2, $y + 2, $width - 4, 5, $this->latin($label), 'Helvetica', '', 6.5, 'C', 'T', 6);
        if ($signaturePath) {
            $imageWidth = min(36.0, $width - 8);
            $pdf->Image(
                $this->preparedJpeg($signaturePath, $imageWidth, 10),
                $x + (($width - $imageWidth) / 2),
                $y + 7,
                $imageWidth,
                10,
                'JPG',
            );
        }
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

    /**
     * @return array{x: float, y: float, w: float, h: float}
     */
    private function cotcBox(float $originX, float $originY, float $scale, float $x, float $y, float $width, float $height): array
    {
        return [
            'x' => $originX + ($x * $scale),
            'y' => $originY + ($y * $scale),
            'w' => $width * $scale,
            'h' => $height * $scale,
        ];
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

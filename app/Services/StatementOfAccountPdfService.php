<?php

namespace App\Services;

use App\Models\EnrollmentApplication;
use App\Models\PaymentTransaction;
use App\Services\DocumentStudio\DocumentStudioRenderer;
use App\Services\DocumentStudio\DocumentStudioService;
use App\Services\DocumentStudio\PlaceholderRegistry;
use FPDF;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class StatementOfAccountPdfService
{
    public function generate(EnrollmentApplication $application): string
    {
        return $this->generateOnCanvas($application);
    }

    public function generateBaseline(EnrollmentApplication $application): string
    {
        return $this->generateOnCanvas($application, new FPDF('P', 'mm', 'Letter'));
    }

    public function generateOnCanvas(EnrollmentApplication $application, ?FPDF $studioCanvas = null): string
    {
        $application->loadMissing([
            'batch.program',
            'trainingProgram',
            'user',
            'paymentTransactions',
        ]);

        $programId = $application->training_program_id ?? $application->batch?->training_program_id;
        $activeStudioVersion = $studioCanvas ? null : app(DocumentStudioService::class)
            ->resolveActiveVersion('soa', $programId);

        if ($activeStudioVersion && ! app(DocumentStudioService::class)->isBaselineVersion($activeStudioVersion)) {
            try {
                $values = PlaceholderRegistry::resolveForEnrollment($application, 'soa');

                return app(DocumentStudioRenderer::class)->renderVersionToPdf($activeStudioVersion, $values);
            } catch (\Throwable $e) {
                Log::warning('Document Studio used the institutional statement fallback.', [
                    'template_version_id' => $activeStudioVersion->id,
                    'reason' => 'studio_render_failed',
                ]);
                // Fall back to baseline FPDF below
            }
        }

        $pdf = $studioCanvas ?? new FPDF('P', 'mm', 'Letter');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(16, 12, 16);
        $pdf->AddPage();

        $pageWidth = $pdf->GetPageWidth(); // 215.9 mm
        $contentWidth = $pageWidth - 32;    // 183.9 mm
        $startX = 16.0;

        // ----------------------------------------------------
        // 1. Header with Prominent Official Logos & Centered Text
        // ----------------------------------------------------
        $headerTopY = 12.0;
        $mcareLogoPath = public_path('assets/mcare-soa-logo.png');
        if (! file_exists($mcareLogoPath)) {
            $mcareLogoPath = public_path('assets/images/logoicon.png');
        }
        $tesdaLogoPath = public_path('assets/tesda-logo.png');

        // MCARE Logo on Left: 27.5mm wide, ~26.5mm high
        $mcareLogoW = 27.5;
        if (file_exists($mcareLogoPath)) {
            $pdf->Image($mcareLogoPath, $startX, $headerTopY, $mcareLogoW, 0);
        }

        // TESDA Logo on Right: 26.5mm x 26.5mm
        $tesdaLogoW = 26.5;
        if (file_exists($tesdaLogoPath)) {
            $pdf->Image($tesdaLogoPath, $startX + $contentWidth - $tesdaLogoW, $headerTopY, $tesdaLogoW, $tesdaLogoW);
        }

        // Center Header Title: "MISSION CARE " in purple, "TRAINING CENTER" in bright magenta
        $midX = $startX + ($contentWidth / 2);
        $pdf->SetFont('Arial', 'B', 15.0);
        $title1 = 'MISSION CARE ';
        $title2 = 'TRAINING CENTER';
        $w1 = $pdf->GetStringWidth($title1);
        $w2 = $pdf->GetStringWidth($title2);
        $startTitleX = $midX - (($w1 + $w2) / 2);

        $pdf->SetXY($startTitleX, $headerTopY + 5.0);
        $pdf->SetTextColor(75, 27, 95); // Deep Purple
        $pdf->Cell($w1, 7.5, $title1, 0, 0, 'L');
        $pdf->SetTextColor(216, 27, 96); // Bright Magenta / Pink
        $pdf->Cell($w2, 7.5, $title2, 0, 1, 'L');

        // Address Line
        $pdf->SetFont('Arial', '', 10.0);
        $pdf->SetTextColor(55, 55, 55);
        $centerSpan = $contentWidth - $mcareLogoW - $tesdaLogoW;
        $pdf->SetXY($startX + $mcareLogoW, $headerTopY + 14.5);
        $pdf->Cell($centerSpan, 5.0, 'Zone 3, San Isidro Pob, Pili, Camarines Sur', 0, 1, 'C');

        // ----------------------------------------------------
        // 2. Document Title: STATEMENT OF ACCOUNT
        // ----------------------------------------------------
        $docTitleY = $headerTopY + 30.5;
        $pdf->SetXY($startX, $docTitleY);
        $pdf->SetFont('Arial', 'B', 12.0);
        $pdf->SetTextColor(40, 40, 40);
        $pdf->Cell($contentWidth, 6.5, 'S T A T E M E N T   O F   A C C O U N T', 0, 1, 'C');

        // ----------------------------------------------------
        // 3. Personal & Enrollment Information Table (Structured Grid)
        // ----------------------------------------------------
        $traineeFullName = trim(($application->first_name ?? '').' '.($application->middle_name ? $application->middle_name.' ' : '').($application->last_name ?? ''));
        if (blank($traineeFullName)) {
            $traineeFullName = $application->user?->name ?? 'Name of Trainee';
        }

        $qualification = $application->trainingProgram?->name
            ?: $application->batch?->program?->name
            ?: ($application->program ?: 'Caregiving NC II');

        $batchNumber = $application->batch?->name ?: 'Batch 2';
        $schedule = strtoupper((string) ($application->schedule_preference ?: 'AM'));
        $batchLabel = $batchNumber.' ('.$schedule.' CLASS)';

        $infoTopY = $docTitleY + 8.5;
        $pdf->SetXY($startX, $infoTopY);
        $pdf->SetFont('Arial', 'B', 9.0);
        $pdf->SetTextColor(75, 27, 95);
        $pdf->Cell($contentWidth, 5.0, 'TRAINEE & ENROLLMENT INFORMATION', 0, 1, 'L');

        // Table layout: 3 rows, 4 columns
        $lblW1 = 34.0;
        $valW1 = 66.0;
        $lblW2 = 36.0;
        $valW2 = $contentWidth - ($lblW1 + $valW1 + $lblW2); // 47.9 mm
        $infoRowH = 7.5;

        $pdf->SetDrawColor(190, 195, 202);
        $pdf->SetLineWidth(0.25);

        // Row 1: Full Name & Statement Date
        $pdf->SetFont('Arial', 'B', 8.5);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->SetFillColor(246, 248, 250);
        $pdf->Cell($lblW1, $infoRowH, ' Full Name:', 1, 0, 'L', true);

        $pdf->SetFont('Arial', 'B', 9.5);
        $pdf->SetTextColor(20, 20, 20);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Cell($valW1, $infoRowH, ' '.$this->latin($traineeFullName), 1, 0, 'L', true);

        $pdf->SetFont('Arial', 'B', 8.5);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->SetFillColor(246, 248, 250);
        $pdf->Cell($lblW2, $infoRowH, ' Statement Date:', 1, 0, 'L', true);

        $pdf->SetFont('Arial', '', 9.0);
        $pdf->SetTextColor(20, 20, 20);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Cell($valW2, $infoRowH, ' '.now()->format('F j, Y'), 1, 1, 'L', true);

        // Row 2: Trainee ID No. & Batch & Schedule
        $pdf->SetFont('Arial', 'B', 8.5);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->SetFillColor(246, 248, 250);
        $pdf->Cell($lblW1, $infoRowH, ' Trainee ID No.:', 1, 0, 'L', true);

        $pdf->SetFont('Arial', 'B', 9.5);
        $pdf->SetTextColor(75, 27, 95);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Cell($valW1, $infoRowH, ' '.($application->trainee_id_number ?: 'Pending assignment'), 1, 0, 'L', true);

        $pdf->SetFont('Arial', 'B', 8.5);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->SetFillColor(246, 248, 250);
        $pdf->Cell($lblW2, $infoRowH, ' Batch & Schedule:', 1, 0, 'L', true);

        $pdf->SetFont('Arial', 'B', 9.0);
        $pdf->SetTextColor(20, 20, 20);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Cell($valW2, $infoRowH, ' '.$this->latin($batchLabel), 1, 1, 'L', true);

        // Row 3: Qualification
        $pdf->SetFont('Arial', 'B', 8.5);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->SetFillColor(246, 248, 250);
        $pdf->Cell($lblW1, $infoRowH, ' Qualification:', 1, 0, 'L', true);

        $pdf->SetFont('Arial', 'B', 9.0);
        $pdf->SetTextColor(20, 20, 20);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Cell($valW1 + $lblW2 + $valW2, $infoRowH, ' '.$this->latin($qualification), 1, 1, 'L', true);
        $pdf->Ln(3.5);

        // ----------------------------------------------------
        // 4. Particulars & Tuition Assessment Table (Full Width)
        // ----------------------------------------------------
        $totalFee = (float) ($application->total_program_fee ?? 22150.00);
        $owwaDeduction = (float) ($application->owwa_deduction ?? 0.00);
        $totalDue = $application->netAmountDue();

        $pdf->SetFont('Arial', 'B', 9.0);
        $pdf->SetTextColor(75, 27, 95);
        $pdf->Cell($contentWidth, 5.0, 'PARTICULARS & ASSESSMENT BREAKDOWN', 0, 1, 'L');

        $partCol1 = $contentWidth - 55.0; // 128.9 mm
        $partCol2 = 55.0;                 // 55.0 mm
        $partRowH = 7.5;

        // Table Header
        $pdf->SetFont('Arial', 'B', 9.0);
        $pdf->SetTextColor(60, 60, 60);
        $pdf->SetFillColor(238, 242, 247);
        $pdf->Cell($partCol1, $partRowH, ' Particulars / Description', 1, 0, 'L', true);
        $pdf->Cell($partCol2, $partRowH, 'Amount (PHP) ', 1, 1, 'R', true);

        // Tuition Fee Row
        $pdf->SetFont('Arial', '', 9.0);
        $pdf->SetTextColor(20, 20, 20);
        $pdf->Cell($partCol1, $partRowH, ' Tuition & Training Assessment Fee', 1, 0, 'L');
        $pdf->Cell($partCol2, $partRowH, number_format($totalFee, 2).' ', 1, 1, 'R');

        // OWWA Deduction Row (if any)
        if ($owwaDeduction > 0) {
            $pdf->SetFont('Arial', '', 9.0);
            $pdf->SetTextColor(16, 120, 60); // Green
            $pdf->Cell($partCol1, $partRowH, ' Less: OWWA Scholarship / Subsidy Deduction', 1, 0, 'L');
            $pdf->Cell($partCol2, $partRowH, '-'.number_format($owwaDeduction, 2).' ', 1, 1, 'R');
        }

        // Total Due Row
        $pdf->SetFont('Arial', 'B', 9.5);
        $pdf->SetTextColor(20, 20, 20);
        $pdf->SetFillColor(246, 248, 251);
        $pdf->Cell($partCol1, $partRowH, ' Total Amount Due', 1, 0, 'L', true);
        $pdf->Cell($partCol2, $partRowH, number_format($totalDue, 2).' ', 1, 1, 'R', true);
        $pdf->Ln(3.5);

        // ----------------------------------------------------
        // 5. Payment Record & Transaction Ledger Table (Large & Prominent)
        // ----------------------------------------------------
        $pdf->SetFont('Arial', 'B', 9.0);
        $pdf->SetTextColor(75, 27, 95);
        $pdf->Cell($contentWidth, 5.0, 'OFFICIAL PAYMENT RECORD & TRANSACTION LEDGER', 0, 1, 'L');

        $colDate = 32.0;
        $colOr = 52.0;
        $colPaid = 50.0;
        $colBal = $contentWidth - ($colDate + $colOr + $colPaid); // 49.9 mm
        $ledgerRowH = 7.8;

        // Table Header
        $pdf->SetFont('Arial', 'B', 9.0);
        $pdf->SetTextColor(60, 60, 60);
        $pdf->SetFillColor(238, 242, 247);
        $pdf->Cell($colDate, $ledgerRowH, 'Date Paid', 1, 0, 'C', true);
        $pdf->Cell($colOr, $ledgerRowH, 'OR / Invoice No.', 1, 0, 'C', true);
        $pdf->Cell($colPaid, $ledgerRowH, 'Amount Paid (PHP)', 1, 0, 'C', true);
        $pdf->Cell($colBal, $ledgerRowH, 'Balance (PHP)', 1, 1, 'C', true);

        // Fetch verified transactions chronologically
        $transactions = $application->paymentTransactions
            ->where('status', PaymentTransaction::STATUS_VERIFIED)
            ->sortBy(fn (PaymentTransaction $tx): int => $tx->paid_at?->getTimestamp() ?? $tx->id)
            ->values();

        $runningBalance = $totalDue;
        $totalPaidSum = 0.0;
        $rowsRendered = 0;

        $pdf->SetFont('Arial', '', 9.0);
        $pdf->SetTextColor(20, 20, 20);

        foreach ($transactions as $tx) {
            $txAmount = (float) $tx->amount;
            $runningBalance = max(0.0, round($runningBalance - $txAmount, 2));
            $totalPaidSum += $txAmount;

            $dateString = $tx->paid_at ? $tx->paid_at->format('M d, Y') : ($tx->created_at ? $tx->created_at->format('M d, Y') : '');
            $refParts = array_filter([
                $tx->or_number ? 'OR# '.$tx->or_number : null,
                $tx->physical_invoice_number ? 'Inv# '.$tx->physical_invoice_number : null,
            ]);
            $orNumber = ! empty($refParts)
                ? implode(' / ', $refParts)
                : ($tx->reference_number ?: 'Verified');

            $pdf->Cell($colDate, $ledgerRowH, $dateString, 1, 0, 'C');
            $pdf->SetFont('Arial', '', strlen($orNumber) > 16 ? 7.8 : 9.0);
            $pdf->Cell($colOr, $ledgerRowH, $orNumber, 1, 0, 'C');
            $pdf->SetFont('Arial', '', 9.0);
            $pdf->Cell($colPaid, $ledgerRowH, number_format($txAmount, 2).' ', 1, 0, 'R');
            $pdf->Cell($colBal, $ledgerRowH, number_format($runningBalance, 2).' ', 1, 1, 'R');

            $rowsRendered++;
        }

        // Fill out empty grid rows to reach at least 8 rows (giving large prominent ledger layout)
        $minRows = 8;
        while ($rowsRendered < $minRows) {
            $pdf->Cell($colDate, $ledgerRowH, '', 1, 0, 'C');
            $pdf->Cell($colOr, $ledgerRowH, '', 1, 0, 'C');
            $pdf->Cell($colPaid, $ledgerRowH, '', 1, 0, 'R');
            $pdf->Cell($colBal, $ledgerRowH, '', 1, 1, 'R');
            $rowsRendered++;
        }

        // Total Row
        $pdf->SetFont('Arial', 'B', 9.0);
        $pdf->SetFillColor(246, 248, 251);
        $pdf->Cell($colDate, $ledgerRowH, 'Total', 1, 0, 'C', true);
        $pdf->Cell($colOr, $ledgerRowH, '', 1, 0, 'C', true);
        $pdf->SetTextColor(16, 120, 60);
        $pdf->Cell($colPaid, $ledgerRowH, ($totalPaidSum > 0 ? number_format($totalPaidSum, 2) : '0.00').' ', 1, 0, 'R', true);
        $pdf->SetTextColor(20, 20, 20);
        $pdf->Cell($colBal, $ledgerRowH, '', 1, 1, 'R', true);
        $pdf->Ln(3.5);

        // ----------------------------------------------------
        // 6. Summary of Account Box
        // ----------------------------------------------------
        $finalBalance = $application->remainingBalance();
        $bulletChar = chr(149);

        $pdf->SetFont('Arial', 'B', 9.0);
        $pdf->SetTextColor(75, 27, 95);
        $pdf->Cell($contentWidth, 5.0, 'SUMMARY OF ACCOUNT', 0, 1, 'L');

        $summaryBoxY = $pdf->GetY();
        $pdf->SetFillColor(252, 253, 255);
        $pdf->SetDrawColor(210, 215, 222);
        $pdf->Rect($startX, $summaryBoxY, $contentWidth, 20.0, 'DF');

        $pdf->SetXY($startX + 4.0, $summaryBoxY + 2.5);
        $pdf->SetFont('Arial', '', 9.0);
        $pdf->SetTextColor(50, 50, 50);
        $pdf->Cell($contentWidth - 8.0, 5.0, '  '.$bulletChar.'   Total Program Fees: PHP '.number_format($totalFee, 2), 0, 1, 'L');

        $pdf->SetX($startX + 4.0);
        $pdf->Cell($contentWidth - 8.0, 5.0, '  '.$bulletChar.'   Total Payments Made / Credited: PHP '.number_format($totalPaidSum, 2), 0, 1, 'L');

        $pdf->SetX($startX + 4.0);
        $pdf->SetFont('Arial', 'B', 9.0);
        $pdf->SetTextColor($finalBalance <= 0 ? 16 : 180, $finalBalance <= 0 ? 120 : 60, 20);
        $pdf->Cell($contentWidth - 8.0, 5.0, '  '.$bulletChar.'   Outstanding Balance: PHP '.number_format($finalBalance, 2).' (as of '.now()->format('F j, Y').')', 0, 1, 'L');

        // ----------------------------------------------------
        // 7. Signatories Section
        // ----------------------------------------------------
        $signY = $summaryBoxY + 25.0;
        $colSignWidth = ($contentWidth / 2) - 10.0;

        // Left: Prepared by
        $pdf->SetXY($startX, $signY);
        $pdf->SetFont('Arial', '', 9.0);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->Cell($colSignWidth, 4.5, 'Prepared and Certified Correct by:', 0, 1, 'L');

        $lineY = $signY + 18.0;
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->Line($startX, $lineY, $startX + $colSignWidth, $lineY);

        $pdf->SetXY($startX, $lineY + 2.0);
        $pdf->SetFont('Arial', 'B', 10.0);
        $pdf->SetTextColor(20, 20, 20);
        $pdf->Cell($colSignWidth, 4.5, 'SALVACION A. COLLAO', 0, 1, 'L');

        $pdf->SetX($startX);
        $pdf->SetFont('Arial', '', 8.5);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell($colSignWidth, 4.0, 'TVI Accountant', 0, 1, 'L');

        // Right: Noted by
        $rightSignX = $startX + $colSignWidth + 20.0;
        $pdf->SetXY($rightSignX, $signY);
        $pdf->SetFont('Arial', '', 9.0);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->Cell($colSignWidth, 4.5, 'Noted by:', 0, 1, 'L');

        $pdf->Line($rightSignX, $lineY, $rightSignX + $colSignWidth, $lineY);

        $pdf->SetXY($rightSignX, $lineY + 2.0);
        $pdf->SetFont('Arial', 'B', 10.0);
        $pdf->SetTextColor(20, 20, 20);
        $pdf->Cell($colSignWidth, 4.5, 'MARICRIS N. COLLAO', 0, 1, 'L');

        $pdf->SetX($rightSignX);
        $pdf->SetFont('Arial', '', 8.5);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell($colSignWidth, 4.0, 'TVI Administrator', 0, 1, 'L');

        return $pdf->Output('S');
    }

    public function downloadResponse(EnrollmentApplication $application): Response
    {
        $pdfBinary = $this->generate($application);

        $traineeSlug = str($application->first_name.' '.$application->last_name)
            ->slug()
            ->toString() ?: 'trainee-'.$application->id;

        $filename = 'MCARE-SOA-'.$application->enrollment_number.'-'.$traineeSlug.'.pdf';

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function inlineResponse(EnrollmentApplication $application): Response
    {
        $pdfBinary = $this->generate($application);

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="MCARE-SOA-'.$application->enrollment_number.'.pdf"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    private function latin(string $value): string
    {
        $converted = iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $value);

        return $converted === false ? (preg_replace('/[^\x20-\x7E\n]/', '', $value) ?? '') : $converted;
    }
}

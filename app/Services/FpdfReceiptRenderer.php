<?php

namespace App\Services;

use App\Models\EnrollmentApplication;
use App\Models\PaymentTransaction;
use FPDF;

class FpdfReceiptRenderer
{
    public function render(EnrollmentApplication $application): string
    {
        $application->loadMissing(['batch', 'paymentTransactions.recordedByAdmin']);

        $verifiedPayment = $application->paymentTransactions
            ->where('status', PaymentTransaction::STATUS_VERIFIED)
            ->sortByDesc('paid_at')
            ->first();
        $pendingTicket = $application->paymentTransactions
            ->where('status', PaymentTransaction::STATUS_PENDING)
            ->sortByDesc('created_at')
            ->first();
        $displayPayment = $verifiedPayment ?: $pendingTicket;
        $paymentCleared = $application->hasEnrollmentPaymentClearance();
        $paidAmount = (float) ($verifiedPayment?->amount ?? $application->total_paid_amount ?? $application->payment_amount ?? 0);
        $amount = $paymentCleared ? $paidAmount : (float) $application->payment_amount;
        $reference = $application->payment_reference
            ?: $displayPayment?->ticket_number
            ?: $application->latestPaymentReference()
            ?: '-';
        $orNumber = $displayPayment?->or_number ?: $application->payment_receipt_number;
        $name = trim(collect([
            $application->first_name,
            $application->middle_name,
            $application->last_name,
            $application->extension_name,
        ])->filter()->implode(' '));
        $organization = config('official_documents.organization', []);

        $pdf = new FPDF('P', 'mm', 'Letter');
        $pdf->SetMargins(18, 16, 18);
        $pdf->SetAutoPageBreak(true, 16);
        $pdf->AddPage();

        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 8, $this->latin((string) ($organization['name'] ?? 'Mission Care Training Center')), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 5, $this->latin((string) ($organization['address'] ?? '')), 0, 1, 'C');
        $pdf->Cell(0, 5, $this->latin((string) ($organization['phone'] ?? '')), 0, 1, 'C');
        $pdf->Ln(7);

        $pdf->SetFont('Arial', 'B', 13);
        $pdf->Cell(0, 8, $paymentCleared ? 'OFFICIAL PAYMENT RECEIPT' : 'PAY-ON-SITE RECEIPT', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 5, $this->latin($application->paymentStatusLabel()), 0, 1, 'C');
        $pdf->Ln(7);

        $this->section($pdf, 'Applicant details');
        $this->row($pdf, 'Name', $name);
        $this->row($pdf, 'Email', $application->email);
        $this->row($pdf, 'Contact number', $application->contact_number);
        $this->row($pdf, 'Program', $application->program);
        if (filled($application->enrollment_number)) {
            $this->row($pdf, 'Enrollment number', $application->enrollment_number);
        }

        $this->section($pdf, 'Payment details');
        $this->row($pdf, 'Amount', 'PHP '.number_format($amount, 2));
        $this->row($pdf, 'Reference number', $reference);
        if (filled($orNumber)) {
            $this->row($pdf, 'OR number', $orNumber);
        }
        $this->row(
            $pdf,
            'Date',
            $verifiedPayment?->paid_at?->format('M d, Y g:i A')
                ?? $application->payment_selected_at?->format('M d, Y g:i A')
                ?? now()->format('M d, Y g:i A'),
        );
        if ($verifiedPayment) {
            $this->row($pdf, 'Received by', $verifiedPayment->recordedByAdmin?->name ?: 'MCARE Cashier');
        }

        $pdf->Ln(8);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'Total: PHP '.number_format($amount, 2), 0, 1, 'R');
        $pdf->Ln(8);
        $pdf->SetFont('Arial', '', 9);
        $notice = $paymentCleared
            ? 'This is your MCARE payment receipt. Keep a copy for your records.'
            : 'This slip is a payment order, not the official BIR receipt. Bring it to MCARE for cashier verification.';
        $pdf->MultiCell(0, 5, $this->latin($notice), 0, 'C');

        return $pdf->Output('S');
    }

    private function section(FPDF $pdf, string $title): void
    {
        $pdf->Ln(4);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 7, $title, 0, 1);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->Line($pdf->GetX(), $pdf->GetY(), 198, $pdf->GetY());
    }

    private function row(FPDF $pdf, string $label, mixed $value): void
    {
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(45, 7, $this->latin($label), 0, 0);
        $pdf->SetFont('Arial', '', 9);
        $pdf->MultiCell(0, 7, $this->latin((string) ($value ?: '-')), 0, 'L');
    }

    private function latin(string $value): string
    {
        return iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value) ?: '';
    }
}

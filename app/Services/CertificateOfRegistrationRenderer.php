<?php

namespace App\Services;

use App\Models\EnrollmentApplication;
use App\Models\PublicSiteSetting;
use FPDF;

class CertificateOfRegistrationRenderer
{
    public static function number(EnrollmentApplication $application): string
    {
        $source = filled($application->enrollment_number)
            ? (string) $application->enrollment_number
            : 'ENR-'.$application->id;

        return 'COR-'.$source;
    }

    public static function filename(EnrollmentApplication $application): string
    {
        $safe = preg_replace('/[^A-Za-z0-9._-]/', '-', self::number($application)) ?: 'certificate-of-registration';

        return $safe.'.pdf';
    }

    public function render(EnrollmentApplication $application): string
    {
        $application->loadMissing('batch');
        $settings = PublicSiteSetting::current();
        $organization = config('official_documents.organization', []);
        $fullName = trim($application->first_name.' '.$application->middle_name.' '.$application->last_name.' '.$application->extension_name);
        $batch = $application->batch
            ? trim($application->batch->name.' '.$application->batch->year)
            : 'Batch to be assigned';
        $schedule = $application->batch?->scheduleLabelFor($application->schedule_preference)
            ?? ($application->schedule_preference ?: 'Schedule to be confirmed');

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->SetMargins(18, 18, 18);
        $pdf->AddPage();

        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(0, 6, $this->latin((string) ($organization['name'] ?? 'MCARE')), 0, 1, 'C');
        $pdf->SetFont('Times', '', 10);
        $pdf->MultiCell(0, 5, $this->latin(trim((string) ($organization['address'] ?? ''))."\n".trim((string) ($organization['phone'] ?? ''))), 0, 'C');
        $pdf->Ln(4);
        $pdf->SetFont('Times', 'B', 16);
        $pdf->Cell(0, 10, $this->latin('CERTIFICATE OF REGISTRATION'), 0, 1, 'C');
        $pdf->SetFont('Times', '', 11);
        $pdf->MultiCell(0, 6, $this->latin(PublicSiteSetting::corStatement()), 0, 'C');
        $pdf->Ln(6);

        $rows = [
            'Certificate no.' => self::number($application),
            'Date' => now()->format('F j, Y'),
            'Trainee' => $fullName !== '' ? $fullName : 'Trainee',
            'Program' => (string) ($application->program ?: 'Caregiving NC II'),
            'Batch' => $batch,
            'Schedule' => (string) $schedule,
            'Payment status' => $application->paymentStatusLabel(),
            'Registrar' => $settings->registrarNameForForm(),
        ];

        foreach ($rows as $label => $value) {
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell(42, 8, $this->latin($label), 0, 0);
            $pdf->SetFont('Times', '', 11);
            $pdf->Cell(0, 8, $this->latin($value), 0, 1);
        }

        $pdf->Ln(16);
        $pdf->Cell(0, 6, $this->latin('______________________________'), 0, 1, 'R');
        $pdf->SetFont('Times', 'B', 11);
        $pdf->Cell(0, 6, $this->latin($settings->registrarNameForForm()), 0, 1, 'R');
        $pdf->SetFont('Times', '', 10);
        $pdf->Cell(0, 5, $this->latin('Registrar'), 0, 1, 'R');

        return $pdf->Output('S');
    }

    private function latin(string $value): string
    {
        $converted = iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $value);

        return $converted === false ? (preg_replace('/[^\x20-\x7E\n]/', '', $value) ?? '') : $converted;
    }
}

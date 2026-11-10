<?php

namespace App\Services;

use App\Models\CertificateTemplate;
use App\Models\EnrollmentApplication;
use App\Models\PublicSiteSetting;

class CertificateOfRegistrationRenderer
{
    /** @var list<string> */
    private array $temporaryImages = [];

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
        $template = CertificateTemplate::activeFor(CertificateTemplate::TYPE_COR);
        $number = self::number($application);
        $values = CertificateTemplate::valuesForEnrollment($application, $number);
        $settings = PublicSiteSetting::current();
        $organization = config('official_documents.organization', []);
        $title = trim($template
            ? CertificateTemplate::replacePlaceholders((string) $template->title, $values)
            : 'CERTIFICATE OF REGISTRATION');
        $body = trim($template
            ? CertificateTemplate::replacePlaceholders((string) $template->body, $values)
            : PublicSiteSetting::corStatement());
        $signatory = trim((string) ($template?->signatory_name ?: $settings->registrarNameForForm()));
        $role = trim((string) ($template?->signatory_role ?: 'Registrar'));
        if ($template?->usesImportedBackground()) {
            return app(ImportedCertificateRenderer::class)->render($template, $values, [
                'signatory_name' => $signatory,
                'signatory_role' => $role,
                'signatory_signature' => $template->absoluteAsset($template->signature_path),
            ]);
        }
        $orientation = $template?->orientation === 'landscape' ? 'L' : 'P';
        $paper = match ($template?->paper_size) {
            'letter' => 'Letter',
            'legal' => 'Legal',
            default => 'A4',
        };
        $logo = $template?->absoluteAsset($template->logo_path) ?: public_path('assets/official-logo.png');
        $signature = $template?->absoluteAsset($template->signature_path);

        $pdf = new OfficialPdf($orientation, 'mm', $paper);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->SetMargins(18, 16, 18);
        $pdf->AddPage();
        $pdf->paintWatermark('MCARE OFFICIAL');

        $this->temporaryImages = [];

        try {
            $logoJpeg = $this->preparedJpeg($logo);
            if ($logoJpeg !== null) {
                $pdf->Image($logoJpeg, ($pdf->GetPageWidth() - 18) / 2, 12, 18, 18, 'JPG');
                $pdf->Ln(16);
            }

            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, 6, $this->latin((string) ($organization['name'] ?? 'MCARE')), 0, 1, 'C');
            $pdf->SetFont('Times', '', 10);
            $pdf->MultiCell(0, 5, $this->latin(trim((string) ($organization['address'] ?? ''))."\n".trim((string) ($organization['phone'] ?? ''))), 0, 'C');
            $pdf->Ln(4);
            $pdf->SetFont('Times', 'B', 16);
            $pdf->MultiCell(0, 8, $this->latin($title !== '' ? $title : 'CERTIFICATE OF REGISTRATION'), 0, 'C');
            if ($body !== '') {
                $pdf->Ln(2);
                $pdf->SetFont('Times', '', 11);
                $pdf->MultiCell(0, 6, $this->latin($body), 0, 'C');
            }
            $pdf->Ln(6);

            $rows = [
                'Certificate no.' => $number,
                'Date' => $values['generation_date'],
                'Trainee' => $values['trainee_name'],
                'Program' => $values['program'],
                'Batch' => $values['batch'],
                'Schedule' => $values['schedule'],
                'Payment status' => $values['payment_status'],
            ];

            foreach ($rows as $label => $value) {
                $pdf->SetFont('Times', 'B', 11);
                $pdf->Cell(42, 8, $this->latin($label), 0, 0);
                $pdf->SetFont('Times', '', 11);
                $pdf->MultiCell(0, 8, $this->latin($value), 0, 'L');
            }

            $pdf->Ln(14);
            $signatureJpeg = $this->preparedJpeg($signature);
            if ($signatureJpeg !== null) {
                $pdf->Image($signatureJpeg, $pdf->GetPageWidth() - 78, $pdf->GetY(), 42, 14, 'JPG');
                $pdf->Ln(16);
            }
            $pdf->Cell(0, 6, $this->latin('______________________________'), 0, 1, 'R');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell(0, 6, $this->latin($signatory !== '' ? $signatory : 'Registrar'), 0, 1, 'R');
            $pdf->SetFont('Times', '', 10);
            $pdf->Cell(0, 5, $this->latin($role !== '' ? $role : 'Registrar'), 0, 1, 'R');

            return $pdf->Output('S');
        } finally {
            foreach ($this->temporaryImages as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }
    }

    private function preparedJpeg(?string $sourcePath): ?string
    {
        if ($sourcePath === null || ! is_file($sourcePath) || ! function_exists('imagecreatefromstring')) {
            return null;
        }

        $binary = file_get_contents($sourcePath);
        $source = is_string($binary) ? @imagecreatefromstring($binary) : false;
        if ($source === false) {
            return null;
        }

        $base = tempnam(sys_get_temp_dir(), 'mcare-cor-');
        if ($base === false) {
            imagedestroy($source);

            return null;
        }

        $output = $base.'.jpg';
        @unlink($base);
        $written = imagejpeg($source, $output, 88);
        imagedestroy($source);
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

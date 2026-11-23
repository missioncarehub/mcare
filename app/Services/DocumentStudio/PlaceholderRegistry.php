<?php

namespace App\Services\DocumentStudio;

use App\Models\EnrollmentApplication;
use App\Models\OfficialDocument;
use App\Models\PaymentTransaction;
use Carbon\Carbon;

class PlaceholderRegistry
{
    /**
     * Complete list of allowlisted placeholder definitions.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function definitions(): array
    {
        return [
            'batch_schedule' => ['token' => '{{batch_schedule}}', 'label' => 'Batch and schedule', 'type' => 'text', 'applicable_kinds' => ['cotc', 'tor', 'soa', 'general'], 'sample_value' => 'Sample batch (AM CLASS)', 'description' => 'Saved record value'],
            'trainee_address' => ['token' => '{{trainee_address}}', 'label' => 'Trainee address', 'type' => 'text', 'applicable_kinds' => ['cotc', 'tor', 'soa', 'general'], 'sample_value' => 'Sample address', 'description' => 'Saved record value'],
            'last_school' => ['token' => '{{last_school}}', 'label' => 'Last school attended', 'type' => 'text', 'applicable_kinds' => ['cotc', 'tor', 'soa', 'general'], 'sample_value' => 'Sample school', 'description' => 'Saved record value'],
            'education_category' => ['token' => '{{education_category}}', 'label' => 'Education category', 'type' => 'text', 'applicable_kinds' => ['cotc', 'tor', 'soa', 'general'], 'sample_value' => 'College', 'description' => 'Saved record value'],
            'document_version' => ['token' => '{{document_version}}', 'label' => 'Document version', 'type' => 'text', 'applicable_kinds' => ['cotc', 'tor', 'soa', 'general'], 'sample_value' => '1', 'description' => 'Saved record value'],
            'entrance_date' => ['token' => '{{entrance_date}}', 'label' => 'Entrance date', 'type' => 'text', 'applicable_kinds' => ['cotc', 'tor', 'soa', 'general'], 'sample_value' => 'June 1, 2026', 'description' => 'Saved record value'],
            'completion_line' => ['token' => '{{completion_line}}', 'label' => 'Completion sentence', 'type' => 'text', 'applicable_kinds' => ['cotc', 'tor', 'soa', 'general'], 'sample_value' => 'Given this 28th of August, 2026 at Mission Care Training Center', 'description' => 'Saved record value'],
            'soa_ledger' => ['token' => '{{soa_ledger}}', 'label' => 'Payment ledger with balance', 'type' => 'table', 'applicable_kinds' => ['soa'], 'sample_value' => [], 'description' => 'Verified payments and running balance'],
            'tor_competencies' => ['token' => '{{tor_competencies}}', 'label' => 'Competencies and grades', 'type' => 'table', 'applicable_kinds' => ['tor'], 'sample_value' => [], 'description' => 'Saved competency records'],
            'soa_assessments' => ['token' => '{{soa_assessments}}', 'label' => 'Assessments', 'type' => 'table', 'applicable_kinds' => ['soa'], 'sample_value' => [], 'description' => 'Saved enrollment assessments'],
            'soa_payments' => ['token' => '{{soa_payments}}', 'label' => 'Verified payments', 'type' => 'table', 'applicable_kinds' => ['soa'], 'sample_value' => [], 'description' => 'Verified enrollment payments'],
            'gross_program_fee' => ['token' => '{{gross_program_fee}}', 'label' => 'Gross program fee', 'type' => 'currency', 'applicable_kinds' => ['soa'], 'sample_value' => 'PHP 25,000.00', 'description' => 'Saved assessment before deductions'],
            'net_amount_due' => ['token' => '{{net_amount_due}}', 'label' => 'Net assessment', 'type' => 'currency', 'applicable_kinds' => ['soa'], 'sample_value' => 'PHP 20,000.00', 'description' => 'Assessment after deductions'],
            'total_deductions' => ['token' => '{{total_deductions}}', 'label' => 'Deductions', 'type' => 'currency', 'applicable_kinds' => ['soa'], 'sample_value' => 'PHP 5,000.00', 'description' => 'Saved enrollment deductions'],
            'trainee_name' => [
                'token' => '{{trainee_name}}',
                'label' => 'Trainee Full Name',
                'description' => 'Official full name in uppercase (e.g. JUAN DELA CRUZ)',
                'type' => 'text',
                'applicable_kinds' => ['cotc', 'tor', 'soa', 'general'],
                'required' => true,
                'sample_value' => 'MARIA REYES SANTOS',
            ],
            'training_program' => [
                'token' => '{{training_program}}',
                'label' => 'Training Program',
                'description' => 'Full qualification name (e.g. Caregiving NC II)',
                'type' => 'text',
                'applicable_kinds' => ['cotc', 'tor', 'soa', 'general'],
                'required' => true,
                'sample_value' => 'Caregiving NC II',
            ],
            'batch_name' => [
                'token' => '{{batch_name}}',
                'label' => 'Batch Name',
                'description' => 'Cohort or training batch title',
                'type' => 'text',
                'applicable_kinds' => ['cotc', 'tor', 'soa', 'general'],
                'required' => false,
                'sample_value' => 'Batch 2 (2026)',
            ],
            'completion_date' => [
                'token' => '{{completion_date}}',
                'label' => 'Completion Date',
                'description' => 'Graduation or completion date (e.g. October 15, 2026)',
                'type' => 'date',
                'applicable_kinds' => ['cotc', 'tor', 'soa', 'general'],
                'required' => false,
                'sample_value' => 'August 28, 2026',
            ],
            'release_date' => [
                'token' => '{{release_date}}',
                'label' => 'Date of Release / Issuance',
                'description' => 'Official certificate release or issuance date',
                'type' => 'date',
                'applicable_kinds' => ['cotc', 'tor', 'soa', 'general'],
                'required' => false,
                'sample_value' => now()->format('F j, Y'),
            ],
            'issued_date' => [
                'token' => '{{issued_date}}',
                'label' => 'Issued Date',
                'description' => 'Date the certificate was released or requested',
                'type' => 'date',
                'applicable_kinds' => ['cotc', 'tor', 'soa', 'general'],
                'required' => false,
                'sample_value' => now()->format('F j, Y'),
            ],
            'certificate_number' => [
                'token' => '{{certificate_number}}',
                'label' => 'Certificate / Serial Number',
                'description' => 'Unique authoritative serial code issued by MCARE',
                'type' => 'text',
                'applicable_kinds' => ['cotc', 'tor', 'general'],
                'required' => false,
                'sample_value' => 'MCARE-COTC-2026-00042-V1',
            ],
            'trainer_name' => [
                'token' => '{{trainer_name}}',
                'label' => 'Trainer / Signatory Name',
                'description' => 'Accredited trainer signatory',
                'type' => 'text',
                'applicable_kinds' => ['cotc', 'tor', 'soa', 'general'],
                'required' => false,
                'sample_value' => (string) config('official_documents.organization.trainer_name', 'Maricris N. Collao'),
            ],
            'registrar_name' => [
                'token' => '{{registrar_name}}',
                'label' => 'Registrar Signatory Name',
                'description' => 'Institution registrar signatory',
                'type' => 'text',
                'applicable_kinds' => ['cotc', 'tor', 'soa', 'general'],
                'required' => false,
                'sample_value' => (string) config('official_documents.organization.registrar_name', 'Salvacion A. Collao'),
            ],
            'registrar_role' => [
                'token' => '{{registrar_role}}',
                'label' => 'Registrar Signatory Role',
                'description' => 'Institution registrar role title',
                'type' => 'text',
                'applicable_kinds' => ['cotc', 'tor', 'soa', 'general'],
                'required' => false,
                'sample_value' => (string) config('official_documents.organization.registrar_role', 'Authorized Registrar'),
            ],
            'trainer_role' => [
                'token' => '{{trainer_role}}',
                'label' => 'Trainer Signatory Role',
                'description' => 'Institution trainer role title',
                'type' => 'text',
                'applicable_kinds' => ['cotc', 'tor', 'soa', 'general'],
                'required' => false,
                'sample_value' => (string) config('official_documents.organization.trainer_role', 'Lead Trainer'),
            ],
            'organization_name' => [
                'token' => '{{organization_name}}',
                'label' => 'Organization Name',
                'description' => 'Official training center name',
                'type' => 'text',
                'applicable_kinds' => ['cotc', 'tor', 'soa', 'general'],
                'required' => false,
                'sample_value' => (string) config('official_documents.organization.name', 'Mission Care Training Center'),
            ],
            'qr_code' => [
                'token' => '{{qr_code}}',
                'label' => 'Verification QR Code',
                'description' => 'Server-controlled QR code URL or payload for public certificate authenticity verification',
                'type' => 'qr',
                'applicable_kinds' => ['cotc', 'tor', 'general'],
                'required' => false,
                'sample_value' => 'https://mcarehub.com/verify/doc/sample-token',
            ],
            'enrollment_number' => [
                'token' => '{{enrollment_number}}',
                'label' => 'Enrollment Number',
                'description' => 'Authoritative enrollment code',
                'type' => 'text',
                'applicable_kinds' => ['cotc', 'tor', 'soa', 'general'],
                'required' => false,
                'sample_value' => 'ENR-2026-00042',
            ],
            'trainee_id_number' => [
                'token' => '{{trainee_id_number}}',
                'label' => 'Trainee ID Number',
                'description' => 'Institutional ID assigned to trainee',
                'type' => 'text',
                'applicable_kinds' => ['soa', 'tor', 'general'],
                'required' => false,
                'sample_value' => 'MC-2026-081',
            ],
            'statement_date' => [
                'token' => '{{statement_date}}',
                'label' => 'Statement Date',
                'description' => 'Date of SOA generation',
                'type' => 'date',
                'applicable_kinds' => ['soa'],
                'required' => false,
                'sample_value' => now()->format('F j, Y'),
            ],
            'total_program_fee' => [
                'token' => '{{total_program_fee}}',
                'label' => 'Total Program Fees',
                'description' => 'Authoritative assessed tuition and fees',
                'type' => 'currency',
                'applicable_kinds' => ['soa'],
                'required' => false,
                'sample_value' => 'PHP 25,000.00',
            ],
            'total_paid' => [
                'token' => '{{total_paid}}',
                'label' => 'Total Payments Credited',
                'description' => 'Sum of verified payments and discounts',
                'type' => 'currency',
                'applicable_kinds' => ['soa'],
                'required' => false,
                'sample_value' => 'PHP 15,000.00',
            ],
            'outstanding_balance' => [
                'token' => '{{outstanding_balance}}',
                'label' => 'Outstanding Balance',
                'description' => 'Remaining unpaid balance',
                'type' => 'currency',
                'applicable_kinds' => ['soa'],
                'required' => false,
                'sample_value' => 'PHP 5,000.00',
            ],
            'payment_status' => [
                'token' => '{{payment_status}}',
                'label' => 'Payment Status',
                'description' => 'PAID, PARTIAL, or PENDING',
                'type' => 'text',
                'applicable_kinds' => ['soa'],
                'required' => false,
                'sample_value' => 'PARTIAL',
            ],
        ];
    }

    /**
     * Get allowlisted definitions for a specific document kind.
     */
    public static function forKind(string $documentKind): array
    {
        return array_filter(self::definitions(), function ($def) use ($documentKind) {
            return in_array($documentKind, $def['applicable_kinds'], true);
        });
    }

    /**
     * Return synthetic sample data for designer previews without accessing trainee records.
     */
    public static function samplePayload(string $documentKind): array
    {
        $payload = [];
        foreach (self::forKind($documentKind) as $key => $def) {
            $payload[$key] = $def['sample_value'];
        }

        // Add table repeaters
        if ($documentKind === 'tor') {
            $payload['tor_competencies'] = [
                [
                    'code' => 'HCS323301',
                    'title' => 'Provide Care and Support to Infants and Toddlers',
                    'hours' => '120',
                    'grade' => '1.30',
                    'status' => 'COMPETENT',
                ],
                [
                    'code' => 'HCS323302',
                    'title' => 'Provide Care and Support to Children',
                    'hours' => '120',
                    'grade' => '1.50',
                    'status' => 'COMPETENT',
                ],
                [
                    'code' => 'HCS323303',
                    'title' => 'Foster Physical, Social and Intellectual Development of Children',
                    'hours' => '100',
                    'grade' => '1.20',
                    'status' => 'COMPETENT',
                ],
                [
                    'code' => 'HCS323304',
                    'title' => 'Provide Care and Support to Elderly',
                    'hours' => '140',
                    'grade' => '1.40',
                    'status' => 'COMPETENT',
                ],
                [
                    'code' => 'HCS323305',
                    'title' => 'Provide Care and Support to People with Special Needs',
                    'hours' => '130',
                    'grade' => '1.60',
                    'status' => 'COMPETENT',
                ],
            ];
        }

        if ($documentKind === 'soa') {
            $payload['soa_assessments'] = [
                ['item' => 'Tuition & Training Fee', 'amount' => 'PHP 25,000.00'],
                ['item' => 'Assessment & Processing Fee', 'amount' => 'PHP 2,500.00'],
                ['item' => 'OWWA Scholarship Deduction', 'amount' => '-PHP 5,000.00'],
            ];

            $payload['soa_payments'] = [
                [
                    'date' => '2026-06-10',
                    'particulars' => 'Initial Downpayment',
                    'or_number' => 'OR-2026-0129',
                    'amount' => 'PHP 7,500.00',
                    'status' => 'VERIFIED',
                ],
                [
                    'date' => '2026-07-15',
                    'particulars' => 'Midterm Installment',
                    'or_number' => 'OR-2026-0482',
                    'amount' => 'PHP 7,500.00',
                    'status' => 'VERIFIED',
                ],
            ];
        }

        if ($documentKind === 'soa') {
            $payload['soa_ledger'] = array_map(fn ($row, $index) => $row + ['balance' => 'PHP '.number_format(20000 - 7500 * ($index + 1), 2)], $payload['soa_payments'], array_keys($payload['soa_payments']));
        }

        return $payload;
    }

    /**
     * Authoritatively resolve real binding data from authorized database records.
     */
    public static function resolveForEnrollment(
        EnrollmentApplication $application,
        string $documentKind,
        ?OfficialDocument $officialDocument = null,
    ): array {
        $application->loadMissing([
            'batch.program',
            'trainingProgram',
            'user',
            'competencyRecords.unit',
            'paymentTransactions',
        ]);

        $organization = config('official_documents.organization', []);
        $trainerName = (string) ($organization['trainer_name'] ?? 'Maricris N. Collao');
        $registrarName = (string) ($organization['registrar_name'] ?? 'Salvacion A. Collao');
        $orgName = (string) ($organization['name'] ?? 'Mission Care Training Center');

        $traineeFullName = trim(mb_strtoupper(
            trim(($application->first_name ?? '').' '.($application->middle_name ? $application->middle_name.' ' : '').($application->last_name ?? ''))
        ));
        if (blank($traineeFullName)) {
            $traineeFullName = mb_strtoupper($application->user?->name ?? 'TRAINEE');
        }

        $programName = $application->trainingProgram?->name
            ?: $application->batch?->program?->name
            ?: ($application->program ?: 'Caregiving NC II');

        $batchName = $application->batch?->name
            ? $application->batch->name.($application->batch->year ? ' ('.$application->batch->year.')' : '')
            : 'Unassigned Batch';

        $completionDate = $application->graduated_at
            ?? $application->learning_status_changed_at
            ?? $application->batch?->training_ends_at
            ?? $officialDocument?->generated_at
            ?? now();
        $formattedDate = $completionDate instanceof Carbon ? $completionDate->format('F j, Y') : (string) $completionDate;

        $docNumber = $officialDocument?->document_number
            ?? sprintf('MCARE-%s-%s-%05d-V1', strtoupper($documentKind), now()->year, $application->id);

        $payload = [
            'batch_schedule' => ($application->batch?->name ?: 'Unassigned Batch').' ('.strtoupper($application->schedule_preference ?: 'AM').' CLASS)',
            'trainee_address' => collect([$application->street, $application->barangay, $application->city, $application->province, $application->zip_code])->filter()->implode(', '),
            'last_school' => $application->school_name ?: '-',
            'education_category' => $application->classification ?: '-',
            'document_version' => (string) ($officialDocument?->version ?? 1),
            'entrance_date' => $application->batch?->training_starts_at?->format('F j, Y') ?? '-',
            'completion_line' => 'Given this '.($completionDate instanceof Carbon ? $completionDate->format('jS \o\f F, Y') : $formattedDate).' at '.$orgName,
            'trainee_name' => $traineeFullName,
            'training_program' => $programName,
            'batch_name' => $batchName,
            'completion_date' => $formattedDate,
            'release_date' => ($officialDocument?->generated_at ?? now())->format('F j, Y'),
            'issued_date' => ($officialDocument?->generated_at ?? now())->format('F j, Y'),
            'certificate_number' => $docNumber,
            'trainer_name' => $trainerName,
            'trainer_role' => (string) ($organization['trainer_role'] ?? 'Lead Trainer'),
            'registrar_name' => $registrarName,
            'registrar_role' => (string) ($organization['registrar_role'] ?? 'Authorized Registrar'),
            'organization_name' => $orgName,
            'enrollment_number' => $application->enrollment_number ?: ('ENR-'.$application->id),
            'trainee_id_number' => $application->trainee_id_number ?: 'N/A',
            'qr_code' => url('/verify/credential/'.$docNumber),
            'statement_date' => now()->format('F j, Y'),
            'payment_status' => strtoupper((string) ($application->payment_status ?: 'UNPAID')),
        ];

        // Specific calculations for Statement of Account
        if ($documentKind === 'soa') {
            $programFee = (float) ($application->total_program_fee
                ?? 22000.00);
            $owwaDeduction = (float) ($application->owwa_deduction ?? 0.0);
            $totalDeductions = $owwaDeduction;
            $netFee = $application->netAmountDue();

            $verifiedTransactions = $application->paymentTransactions()
                ->where('status', PaymentTransaction::STATUS_VERIFIED)
                ->orderBy('id')
                ->get()->sortBy(fn ($tx) => ($tx->paid_at ?? $tx->created_at)?->getTimestamp() ?? 0)->values();
            $totalPaid = (float) $verifiedTransactions->sum('amount');
            $balance = max(0.0, round($netFee - $totalPaid, 2));

            $payload['total_program_fee'] = 'PHP '.number_format($netFee, 2);
            $payload['gross_program_fee'] = 'PHP '.number_format($programFee, 2);
            $payload['net_amount_due'] = 'PHP '.number_format($netFee, 2);
            $payload['total_deductions'] = 'PHP '.number_format($totalDeductions, 2);
            $payload['total_paid'] = 'PHP '.number_format($totalPaid, 2);
            $payload['outstanding_balance'] = 'PHP '.number_format($balance, 2);
            $payload['payment_status'] = $balance <= 0 ? 'PAID' : ($totalPaid > 0 ? 'PARTIAL' : 'PENDING');

            $assessments = [
                ['item' => 'Program Tuition Fee', 'amount' => 'PHP '.number_format($programFee, 2)],
            ];
            if ($owwaDeduction > 0) {
                $assessments[] = ['item' => 'OWWA / Scholarship Grant', 'amount' => '-PHP '.number_format($owwaDeduction, 2)];
            }
            $payload['soa_assessments'] = $assessments;

            $paymentsList = [];
            $runningBalance = $netFee;
            foreach ($verifiedTransactions as $tx) {
                $runningBalance = max(0.0, round($runningBalance - (float) $tx->amount, 2));
                $paymentsList[] = [
                    'date' => $tx->paid_at?->format('M d, Y') ?? $tx->created_at?->format('M d, Y') ?? '',
                    'balance' => 'PHP '.number_format($runningBalance, 2),
                    'particulars' => $tx->payment_method ? ucfirst(str_replace('_', ' ', $tx->payment_method)) : 'Payment',
                    'or_number' => $tx->or_number ?: ($tx->reference_number ?: 'N/A'),
                    'amount' => 'PHP '.number_format((float) $tx->amount, 2),
                    'status' => 'VERIFIED',
                ];
            }
            $payload['soa_payments'] = $paymentsList;
            $payload['soa_ledger'] = $paymentsList;
        }

        // Specific data for TOR
        if ($documentKind === 'tor') {
            $records = $application->competencyRecords
                ->filter(fn ($r) => $r->unit && $r->unit->is_tor_included && $r->unit->category === 'core' && $r->tor_grade !== null)
                ->sortBy(fn ($r) => $r->unit->sort_order ?? $r->unit->id);

            $torRows = [];
            foreach ($records as $r) {
                $torRows[] = [
                    'code' => $r->unit->code,
                    'title' => $r->unit->title,
                    'hours' => $r->unit->estimated_hours === null ? '' : (string) $r->unit->estimated_hours,
                    'grade' => number_format((float) $r->tor_grade, 2),
                    'status' => strtoupper((string) ($r->status ?: 'COMPETENT')),
                ];
            }
            $payload['tor_competencies'] = $torRows;
        }

        return $payload;
    }
}

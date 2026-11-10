<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CertificateTemplate extends Model
{
    public const TYPE_COR = 'cor';

    public const PLACEHOLDERS = [
        'trainee_name',
        'program',
        'batch',
        'schedule',
        'payment_status',
        'completion_date',
        'generation_date',
        'certificate_no',
        'enrollment_id',
        'organization',
    ];

    protected $fillable = [
        'type',
        'version',
        'is_active',
        'title',
        'body',
        'paper_size',
        'orientation',
        'use_official_artwork',
        'background_path',
        'field_layout',
        'logo_path',
        'signatory_name',
        'signatory_role',
        'signature_path',
        'second_signatory_name',
        'second_signatory_role',
        'second_signature_path',
        'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'is_active' => 'boolean',
            'use_official_artwork' => 'boolean',
            'field_layout' => 'array',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /** @return list<string> */
    public static function managedTypes(): array
    {
        return [OfficialDocument::TYPE_COTC, OfficialDocument::TYPE_TOR, self::TYPE_COR];
    }

    public static function label(string $type): string
    {
        return match ($type) {
            OfficialDocument::TYPE_COTC => 'COTC',
            self::TYPE_COR => 'COR',
            default => 'TOR',
        };
    }

    public static function activeFor(string $type): ?self
    {
        if (! in_array($type, self::managedTypes(), true)) {
            return null;
        }

        return static::query()
            ->where('type', $type)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();
    }

    /** @return array<string, mixed> */
    public static function formDefaults(string $type): array
    {
        $organization = config('official_documents.organization', []);

        if ($type === OfficialDocument::TYPE_COTC) {
            return [
                'title' => 'Certificate of Training Completion',
                'body' => 'Given this {{completion_date}} at {{organization}}',
                'paper_size' => 'letter',
                'orientation' => 'landscape',
                'use_official_artwork' => true,
                'signatory_name' => (string) ($organization['registrar_name'] ?? ''),
                'signatory_role' => 'TVI Registrar',
                'second_signatory_name' => (string) ($organization['trainer_name'] ?? ''),
                'second_signatory_role' => 'TVI Trainer',
            ];
        }

        if ($type === self::TYPE_COR) {
            return [
                'title' => 'CERTIFICATE OF REGISTRATION',
                'body' => PublicSiteSetting::corStatement(),
                'paper_size' => 'a4',
                'orientation' => 'portrait',
                'use_official_artwork' => false,
                'signatory_name' => PublicSiteSetting::current()->registrarNameForForm(),
                'signatory_role' => 'Registrar',
                'second_signatory_name' => '',
                'second_signatory_role' => '',
            ];
        }

        return [
            'title' => PublicSiteSetting::torTitle(),
            'body' => '',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'use_official_artwork' => false,
            'signatory_name' => PublicSiteSetting::current()->registrarNameForForm(),
            'signatory_role' => 'Registrar',
            'second_signatory_name' => (string) ($organization['trainer_name'] ?? ''),
            'second_signatory_role' => 'Lead Trainer',
        ];
    }

    public function usesOfficialArtwork(): bool
    {
        return $this->type === OfficialDocument::TYPE_COTC
            && $this->use_official_artwork
            && $this->paper_size === 'letter'
            && $this->orientation === 'landscape';
    }

    public function usesImportedBackground(): bool
    {
        return ! $this->usesOfficialArtwork() && $this->absoluteAsset($this->background_path) !== null;
    }

    /** @return array<string, string> */
    public static function importFields(string $type): array
    {
        $fields = [
            'title' => 'Title',
            'trainee_name' => 'Trainee name',
            'body' => 'Statement',
            'program' => 'Program',
            'batch' => 'Batch',
            'certificate_no' => 'Certificate number',
            'enrollment_id' => 'Enrollment ID',
            'signatory' => 'Registrar',
        ];

        if ($type === self::TYPE_COR) {
            $fields['schedule'] = 'Schedule';
            $fields['payment_status'] = 'Payment';
            $fields['generation_date'] = 'Date';
        } else {
            $fields['completion_date'] = 'Completion date';
            $fields['second_signatory'] = $type === OfficialDocument::TYPE_TOR ? 'Prepared by' : 'Trainer';
        }

        if ($type === OfficialDocument::TYPE_TOR) {
            $fields['records'] = 'Competency records';
        }

        return $fields;
    }

    /**
     * Positions, as percentages of the page, where an imported design is filled
     * from the trainee record. These match the official certificate layouts.
     *
     * @return array<string, array{x: float, y: float, w: float, h: float, size: float, align: string}>
     */
    public static function automaticFieldLayout(string $type, string $orientation): array
    {
        $landscape = $orientation === 'landscape';
        $boxes = match ($type) {
            OfficialDocument::TYPE_COTC, self::TYPE_COR => $landscape
                ? [
                    'trainee_name' => [5, 42, 64, 8, 22, 'C'],
                    'body' => [10, 63, 58, 6, 11, 'C'],
                    'program' => [10, 55, 38, 5, 11, 'L'],
                    'batch' => [52, 55, 38, 5, 11, 'L'],
                    'schedule' => [10, 70, 38, 5, 11, 'L'],
                    'payment_status' => [52, 70, 38, 5, 11, 'L'],
                    'generation_date' => [52, 63, 38, 5, 11, 'L'],
                    'completion_date' => [10, 63, 38, 5, 11, 'L'],
                    'certificate_no' => [55, 94, 40, 4, 8, 'R'],
                    'enrollment_id' => [5, 94, 48, 4, 8, 'L'],
                ]
                : [
                    'trainee_name' => [12, 36, 76, 5, 14, 'L'],
                    'body' => [12, 28, 76, 6, 11, 'C'],
                    'program' => [12, 42, 76, 4, 11, 'L'],
                    'batch' => [12, 47, 76, 4, 11, 'L'],
                    'schedule' => [12, 52, 76, 4, 11, 'L'],
                    'payment_status' => [12, 57, 76, 4, 11, 'L'],
                    'generation_date' => [12, 31, 76, 4, 11, 'L'],
                    'completion_date' => [12, 62, 76, 4, 11, 'L'],
                    'certificate_no' => [12, 26, 76, 4, 11, 'L'],
                    'enrollment_id' => [12, 67, 76, 4, 9, 'L'],
                ],
            default => $landscape
                ? [
                    'trainee_name' => [5, 42, 64, 8, 22, 'C'],
                    'completion_date' => [10, 63, 40, 5, 11, 'C'],
                    'certificate_no' => [55, 94, 40, 4, 8, 'R'],
                    'enrollment_id' => [5, 94, 48, 4, 8, 'L'],
                ]
                : [
                    'trainee_name' => [4, 16.5, 92, 4, 16, 'C'],
                    'completion_date' => [4, 46, 24, 4, 9, 'L'],
                    'certificate_no' => [55, 96, 40, 3, 8, 'R'],
                    'enrollment_id' => [4, 96, 48, 3, 8, 'L'],
                ],
        };

        $keys = match ($type) {
            OfficialDocument::TYPE_COTC => ['trainee_name', 'body', 'certificate_no', 'enrollment_id'],
            self::TYPE_COR => ['trainee_name', 'program', 'batch', 'schedule', 'payment_status', 'generation_date', 'certificate_no'],
            default => ['trainee_name', 'completion_date', 'certificate_no', 'enrollment_id'],
        };

        $layout = [];
        foreach ($keys as $key) {
            [$x, $y, $w, $h, $size, $align] = $boxes[$key];
            $layout[$key] = [
                'x' => (float) $x,
                'y' => (float) $y,
                'w' => (float) $w,
                'h' => (float) $h,
                'size' => (float) $size,
                'align' => $align,
            ];
        }

        return $layout;
    }

    /**
     * Details that change for each trainee and are placed on an imported design.
     *
     * @return array<string, string>
     */
    public static function variableImportFields(string $type): array
    {
        $fields = [
            'trainee_name' => 'Trainee name',
            'program' => 'Program',
            'batch' => 'Batch',
            'certificate_no' => 'Certificate number',
            'enrollment_id' => 'Enrollment ID',
        ];

        if ($type === self::TYPE_COR) {
            $fields['schedule'] = 'Schedule';
            $fields['payment_status'] = 'Payment';
            $fields['generation_date'] = 'Date';
        } else {
            $fields['completion_date'] = 'Completion date';
        }

        if ($type === OfficialDocument::TYPE_TOR) {
            $fields['records'] = 'Competency records';
        }

        return $fields;
    }

    /** @return array<string, array{x: float, y: float, w: float, h: float, size: float, align: string}> */
    public static function defaultFieldLayout(string $type, string $orientation): array
    {
        $boxes = $orientation === 'landscape'
            ? [
                'title' => [8, 5, 84, 9, 18, 'C'],
                'trainee_name' => [12, 30, 76, 9, 20, 'C'],
                'body' => [10, 41, 80, 12, 11, 'C'],
                'program' => [8, 55, 40, 6, 10, 'L'],
                'batch' => [52, 55, 40, 6, 10, 'L'],
                'completion_date' => [8, 63, 40, 6, 10, 'L'],
                'schedule' => [8, 63, 40, 6, 10, 'L'],
                'payment_status' => [52, 63, 40, 6, 10, 'L'],
                'generation_date' => [52, 63, 40, 6, 10, 'L'],
                'records' => [8, 55, 84, 14, 8, 'L'],
                'second_signatory' => [8, 74, 36, 12, 11, 'C'],
                'signatory' => [56, 74, 36, 12, 11, 'C'],
                'enrollment_id' => [8, 90, 40, 5, 8, 'L'],
                'certificate_no' => [50, 90, 42, 5, 8, 'R'],
            ]
            : [
                'title' => [8, 4, 84, 6, 16, 'C'],
                'trainee_name' => [10, 12, 80, 6, 16, 'C'],
                'body' => [10, 19, 80, 8, 10, 'C'],
                'program' => [8, 29, 40, 5, 9, 'L'],
                'batch' => [52, 29, 40, 5, 9, 'L'],
                'completion_date' => [8, 35, 40, 5, 9, 'L'],
                'schedule' => [8, 42, 40, 5, 9, 'L'],
                'payment_status' => [52, 42, 40, 5, 9, 'L'],
                'generation_date' => [8, 48, 40, 5, 9, 'L'],
                'records' => [8, 42, 84, 28, 8, 'L'],
                'second_signatory' => [8, 74, 40, 12, 10, 'C'],
                'signatory' => [52, 74, 40, 12, 10, 'C'],
                'enrollment_id' => [8, 91, 40, 4, 8, 'L'],
                'certificate_no' => [50, 91, 42, 4, 8, 'R'],
            ];

        $layout = [];
        foreach (self::importFields($type) as $key => $label) {
            [$x, $y, $w, $h, $size, $align] = $boxes[$key];
            $layout[$key] = [
                'x' => (float) $x,
                'y' => (float) $y,
                'w' => (float) $w,
                'h' => (float) $h,
                'size' => (float) $size,
                'align' => $align,
            ];
        }

        return $layout;
    }

    /** @return array<string, array{x: float, y: float, w: float, h: float, size: float, align: string}> */
    public function resolvedFieldLayout(): array
    {
        $layout = self::defaultFieldLayout($this->type, $this->orientation);
        $saved = is_array($this->field_layout) ? $this->field_layout : [];

        foreach ($layout as $key => $box) {
            $custom = is_array($saved[$key] ?? null) ? $saved[$key] : [];
            $x = max(0, min(92, (float) ($custom['x'] ?? $box['x'])));
            $y = max(0, min(92, (float) ($custom['y'] ?? $box['y'])));
            $layout[$key] = [
                'x' => $x,
                'y' => $y,
                'w' => max(8, min(100 - $x, (float) ($custom['w'] ?? $box['w']))),
                'h' => max(4, min(40, (float) ($custom['h'] ?? $box['h']))),
                'size' => max(7, min(28, (float) ($custom['size'] ?? $box['size']))),
                'align' => in_array($custom['align'] ?? $box['align'], ['L', 'C', 'R'], true) ? (string) ($custom['align'] ?? $box['align']) : $box['align'],
            ];
        }

        return $layout;
    }

    public function absoluteAsset(?string $path): ?string
    {
        $path = str_replace('\\', '/', trim((string) $path));
        if ($path === '' || str_contains($path, '..') || ! str_starts_with($path, 'certificate-templates/')) {
            return null;
        }

        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        $absolute = Storage::disk('local')->path($path);

        return is_file($absolute) ? $absolute : null;
    }

    /** @return array<string, string> */
    public static function placeholderValues(OfficialDocument $document): array
    {
        $values = self::valuesForEnrollment($document->application, (string) $document->document_number);
        $completion = $document->application?->batch?->training_ends_at ?? $document->generated_at ?? now();
        $values['completion_date'] = $completion->format('F j, Y');
        $values['certificate_no'] = (string) $document->document_number;

        return $values;
    }

    /** @return array<string, string> */
    public static function valuesForEnrollment(?EnrollmentApplication $application, string $certificateNo = ''): array
    {
        $batch = $application?->batch;
        $enrollment = trim((string) ($application?->enrollment_number ?? ''));
        if ($enrollment === '' && $application?->id) {
            $enrollment = 'ENR-'.$application->id;
        }

        $name = trim(collect([
            $application?->first_name,
            $application?->middle_name,
            $application?->last_name,
            $application?->extension_name,
        ])->filter()->implode(' '));
        $batchLabel = trim(collect([$batch?->name, $batch?->year])->filter()->implode(' '));
        $schedule = $batch?->scheduleLabelFor($application?->schedule_preference)
            ?? ($application?->schedule_preference ?: 'Schedule to be confirmed');

        return [
            'trainee_name' => $name !== '' ? $name : 'Trainee',
            'program' => trim((string) ($application?->trainingProgram?->name ?? $application?->program ?? '')) ?: 'Caregiving NC II',
            'batch' => $batchLabel !== '' ? $batchLabel : 'Unassigned batch',
            'schedule' => (string) $schedule,
            'payment_status' => $application?->paymentStatusLabel() ?: 'Unpaid',
            'completion_date' => ($batch?->training_ends_at ?? now())->format('F j, Y'),
            'generation_date' => now()->format('F j, Y'),
            'certificate_no' => $certificateNo,
            'enrollment_id' => $enrollment,
            'organization' => (string) config('official_documents.organization.name', 'Mission Care Training Center'),
        ];
    }

    /** @param  array<string, string>  $values */
    public static function replacePlaceholders(string $template, array $values): string
    {
        $replace = [];
        foreach (self::PLACEHOLDERS as $token) {
            $replace['{{'.$token.'}}'] = (string) ($values[$token] ?? '');
        }

        return strtr($template, $replace);
    }

    public static function headingFor(string $type, ?OfficialDocument $document = null): string
    {
        $active = static::activeFor($type);
        $title = trim((string) ($active?->title ?? ''));

        if ($title === '') {
            return match ($type) {
                OfficialDocument::TYPE_TOR => PublicSiteSetting::torTitle(),
                self::TYPE_COR => 'CERTIFICATE OF REGISTRATION',
                default => 'Certificate of Training Completion',
            };
        }

        if ($document === null) {
            return $title;
        }

        return trim(static::replacePlaceholders($title, static::placeholderValues($document))) ?: $title;
    }
}

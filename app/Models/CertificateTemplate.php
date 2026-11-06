<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CertificateTemplate extends Model
{
    public const PLACEHOLDERS = [
        'trainee_name',
        'program',
        'batch',
        'completion_date',
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
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public static function activeFor(string $type): ?self
    {
        if (! in_array($type, OfficialDocument::supportedTypes(), true)) {
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
        $application = $document->application;
        $batch = $application?->batch;
        $completion = $batch?->training_ends_at ?? $document->generated_at ?? now();
        $enrollment = trim((string) ($application->enrollment_number ?? ''));
        if ($enrollment === '' && $application?->id) {
            $enrollment = 'ENR-'.$application->id;
        }

        $name = trim(collect([
            $application->first_name ?? null,
            $application->middle_name ?? null,
            $application->last_name ?? null,
            $application->extension_name ?? null,
        ])->filter()->implode(' '));

        $batchLabel = trim(collect([$batch?->name, $batch?->year])->filter()->implode(' '));

        return [
            'trainee_name' => $name !== '' ? $name : 'Trainee',
            'program' => trim((string) ($application->trainingProgram?->name ?? $application->program ?? '')) ?: 'Caregiving NC II',
            'batch' => $batchLabel !== '' ? $batchLabel : 'Unassigned batch',
            'completion_date' => $completion->format('F j, Y'),
            'certificate_no' => (string) $document->document_number,
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
            return $type === OfficialDocument::TYPE_TOR
                ? PublicSiteSetting::torTitle()
                : 'Certificate of Training Completion';
        }

        if ($document === null) {
            return $title;
        }

        return trim(static::replacePlaceholders($title, static::placeholderValues($document))) ?: $title;
    }
}

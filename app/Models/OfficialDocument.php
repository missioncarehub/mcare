<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficialDocument extends Model
{
    use HasFactory;

    public const TYPE_COTC = 'cotc';

    public const TYPE_TOR = 'tor';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_GENERATING = 'generating';

    public const STATUS_GENERATED = 'generated';

    public const STATUS_RELEASED = 'released';

    public const STATUS_DOWNLOADED = 'downloaded';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REVOKED = 'revoked';

    public static function supportedTypes(): array
    {
        return [self::TYPE_COTC, self::TYPE_TOR];
    }

    public static function templateViewForType(string $type): string
    {
        return match ($type) {
            self::TYPE_COTC => 'documents.pdf.cotc',
            self::TYPE_TOR => 'documents.pdf.tor',
            default => throw new \InvalidArgumentException("Unsupported official document type: {$type}"),
        };
    }

    protected $fillable = [
        'enrollment_application_id',
        'training_batch_id',
        'type',
        'version',
        'document_number',
        'status',
        'storage_disk',
        'file_path',
        'sha256',
        'template_version',
        'document_template_version_id',
        'generated_by_id',
        'released_by_id',
        'generated_at',
        'released_at',
        'downloaded_at',
        'download_count',
        'revoked_at',
        'revocation_reason',
        'generation_error',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'released_at' => 'datetime',
            'downloaded_at' => 'datetime',
            'revoked_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(EnrollmentApplication::class, 'enrollment_application_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(TrainingBatch::class, 'training_batch_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_id');
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by_id');
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(OfficialDocumentDownload::class);
    }

    public function documentTemplateVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplateVersion::class, 'document_template_version_id');
    }

    public function isDownloadableByTrainee(): bool
    {
        return $this->type === self::TYPE_COTC
            && $this->status === self::STATUS_RELEASED
            && $this->released_at !== null
            && $this->downloaded_at === null
            && filled($this->file_path);
    }
}

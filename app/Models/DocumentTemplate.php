<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DocumentTemplate extends Model
{
    use HasFactory;

    public const KIND_COTC = 'cotc';

    public const KIND_TOR = 'tor';

    public const KIND_SOA = 'soa';

    public const KIND_GENERAL = 'general';

    public const ENGINE_PDFME = 'pdfme';

    public const ENGINE_FABRIC = 'fabric';

    public const ENGINE_RK_WORD = 'rk_word';

    public static function supportedKinds(): array
    {
        return [
            self::KIND_COTC,
            self::KIND_TOR,
            self::KIND_SOA,
            self::KIND_GENERAL,
        ];
    }

    public static function supportedEngines(): array
    {
        return [
            self::ENGINE_PDFME,
            self::ENGINE_FABRIC,
            self::ENGINE_RK_WORD,
        ];
    }

    protected $fillable = [
        'name',
        'slug',
        'document_kind',
        'engine',
        'training_program_id',
        'active_version_id',
        'is_active',
        'is_system_default',
        'draft_revision',
        'draft_state',
        'draft_updated_at',
        'created_by_id',
        'updated_by_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_system_default' => 'boolean',
            'draft_revision' => 'integer',
            'draft_state' => 'array',
            'conversion_report' => 'array',
            'draft_updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (DocumentTemplate $template): void {
            if ($template->versions()->exists()) {
                throw new \RuntimeException('Templates with published versions must be retained. Deactivate the template instead.');
            }
        });
        static::creating(function (DocumentTemplate $template): void {
            if (blank($template->slug)) {
                $baseSlug = Str::slug($template->name) ?: 'template';
                $slug = $baseSlug;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.$counter++;
                }
                $template->slug = $slug;
            }
        });
    }

    public function activeVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplateVersion::class, 'active_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentTemplateVersion::class, 'document_template_id')->orderByDesc('version_number');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(DocumentAsset::class, 'document_template_id');
    }

    public function trainingProgram(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForKind($query, string $kind)
    {
        return $query->where('document_kind', $kind);
    }

    public function scopeForProgram($query, ?int $programId)
    {
        return $query->where(function ($q) use ($programId) {
            $q->whereNull('training_program_id')
                ->when($programId, fn ($sub) => $sub->orWhere('training_program_id', $programId));
        });
    }

    public function isKind(string $kind): bool
    {
        return $this->document_kind === $kind;
    }

    public function isEngine(string $engine): bool
    {
        return $this->engine === $engine;
    }

    public function kindLabel(): string
    {
        return match ($this->document_kind) {
            self::KIND_COTC => 'Certificate of Completion (COTC)',
            self::KIND_TOR => 'Transcript of Records (TOR)',
            self::KIND_SOA => 'Statement of Account (SOA)',
            default => 'General Document',
        };
    }

    public function engineLabel(): string
    {
        return match ($this->engine) {
            self::ENGINE_PDFME => 'PDF Overlay (pdfme)',
            self::ENGINE_FABRIC => 'Free-Layout Canvas (Fabric)',
            self::ENGINE_RK_WORD => 'Flowing Word Document',
            default => ucfirst($this->engine),
        };
    }
}

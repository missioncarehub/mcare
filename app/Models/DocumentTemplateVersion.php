<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

class DocumentTemplateVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_template_id',
        'version_number',
        'schema_version',
        'engine',
        'engine_version',
        'page_settings',
        'editor_state',
        'placeholder_contract',
        'sha256',
        'changelog',
        'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'page_settings' => 'array',
            'editor_state' => 'array',
            'placeholder_contract' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (DocumentTemplateVersion $version): void {
            throw new RuntimeException('DocumentTemplateVersion records are immutable once published.');
        });

        static::deleting(function (DocumentTemplateVersion $version): void {
            throw new RuntimeException('Published template versions cannot be deleted.');
        });
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'document_template_id');
    }

    public function officialDocuments(): HasMany
    {
        return $this->hasMany(OfficialDocument::class, 'document_template_version_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function isCurrentActive(): bool
    {
        return (int) $this->template?->active_version_id === (int) $this->id;
    }

    public function verifyChecksum(): bool
    {
        $computed = hash('sha256', json_encode($this->editor_state, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return hash_equals($this->sha256, $computed);
    }
}

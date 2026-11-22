<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DocumentAsset extends Model
{
    use HasFactory;

    public const TYPE_IMAGE = 'image';

    public const TYPE_PDF_BASE = 'pdf_base';

    // Private import provenance; never an insertable/exportable template asset.
    public const TYPE_PDF_SOURCE = 'pdf_source';

    public const TYPE_FONT = 'font';

    public const TYPE_SIGNATURE = 'signature';

    public const TYPE_LOGO = 'logo';

    public static function supportedTypes(): array
    {
        return [
            self::TYPE_IMAGE,
            self::TYPE_PDF_BASE,
            self::TYPE_FONT,
            self::TYPE_SIGNATURE,
            self::TYPE_LOGO,
        ];
    }

    protected $fillable = [
        'document_template_id',
        'name',
        'type',
        'disk',
        'file_path',
        'mime_type',
        'file_size',
        'dimensions',
        'sha256',
        'is_system',
        'uploaded_by_id',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'dimensions' => 'array',
            'is_system' => 'boolean',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'document_template_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    public function existsOnDisk(): bool
    {
        return filled($this->file_path) && Storage::disk($this->disk)->exists($this->file_path);
    }

    public function fileContents(): ?string
    {
        if (! $this->existsOnDisk()) {
            return null;
        }

        return Storage::disk($this->disk)->get($this->file_path);
    }
}

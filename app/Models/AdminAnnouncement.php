<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminAnnouncement extends Model
{
    use HasFactory;

    public const TARGET_ALL = 'all';

    public const TARGET_BATCH = 'batch';

    public const TARGET_USER = 'user';

    public const KIND_ANNOUNCEMENT = 'announcement';

    public const KIND_REMINDER = 'reminder';

    public const KIND_NEWS = 'news';

    protected $fillable = [
        'author_id',
        'target_type',
        'training_batch_id',
        'target_user_id',
        'title',
        'message',
        'kind',
        'due_date',
        'send_email',
        'send_sms',
        'sms_delivery',
        'is_published',
        'posted_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'send_email' => 'boolean',
            'send_sms' => 'boolean',
            'sms_delivery' => 'array',
            'is_published' => 'boolean',
            'posted_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public static function targetTypes(): array
    {
        return [
            self::TARGET_ALL => 'All Batches',
            self::TARGET_BATCH => 'Specific Batch',
            self::TARGET_USER => 'Specific Enrollee',
        ];
    }

    public static function kinds(): array
    {
        return [
            self::KIND_REMINDER => 'Payment / Due Date Reminder',
            self::KIND_ANNOUNCEMENT => 'General Announcement',
            self::KIND_NEWS => 'News & Updates',
        ];
    }

    public function isVisibleNow(): bool
    {
        return $this->is_published
            && (! $this->posted_at || $this->posted_at->isPast())
            && (! $this->expires_at || $this->expires_at->isFuture());
    }

    public function scopeVisibleTo(Builder $query, User $user, ?int $batchId = null): Builder
    {
        return $query->where('is_published', true)
            ->where(fn (Builder $q) => $q->whereNull('posted_at')->orWhere('posted_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
            ->where(function (Builder $q) use ($user, $batchId) {
                $q->where('target_type', self::TARGET_ALL)
                    ->orWhere(function (Builder $sub) use ($user) {
                        $sub->where('target_type', self::TARGET_USER)
                            ->where('target_user_id', $user->id);
                    });

                if ($batchId) {
                    $q->orWhere(function (Builder $sub) use ($batchId) {
                        $sub->where('target_type', self::TARGET_BATCH)
                            ->where('training_batch_id', $batchId);
                    });
                }
            });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(TrainingBatch::class, 'training_batch_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}

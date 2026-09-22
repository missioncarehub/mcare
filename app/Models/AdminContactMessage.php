<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminContactMessage extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_REVIEWED = 'reviewed';

    public const STATUS_CLOSED = 'closed';

    public const TOPIC_ACCOUNT = 'account';

    public const TOPIC_CLASSWORK = 'classwork';

    public const TOPIC_PAYMENTS = 'payments';

    public const TOPIC_DOCUMENTS = 'documents';

    public const TOPIC_OTHER = 'other';

    protected $fillable = [
        'user_id',
        'topic',
        'subject',
        'message',
        'status',
        'admin_reply',
        'reviewed_by_id',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_REVIEWED => 'Reviewed',
            self::STATUS_CLOSED => 'Closed',
        ];
    }

    /** @return array<string, string> */
    public static function topics(): array
    {
        return [
            self::TOPIC_ACCOUNT => 'Account access',
            self::TOPIC_CLASSWORK => 'Classwork and modules',
            self::TOPIC_PAYMENTS => 'Payments',
            self::TOPIC_DOCUMENTS => 'Documents',
            self::TOPIC_OTHER => 'Other',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? str($this->status)->headline()->toString();
    }

    public function topicLabel(): string
    {
        return self::topics()[$this->topic] ?? str($this->topic)->headline()->toString();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}

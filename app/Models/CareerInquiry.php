<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerInquiry extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_REVIEWED = 'reviewed';

    public const STATUS_CLOSED = 'closed';

    public const CERT_AWAITING_UPLOAD = 'awaiting_upload';

    public const CERT_PENDING_REVIEW = 'pending_review';

    public const CERT_APPROVED = 'approved';

    public const CERT_REJECTED = 'rejected';

    protected $fillable = [
        'career_opportunity_id',
        'user_id',
        'name',
        'email',
        'contact_number',
        'message',
        'credential_paths',
        'placement_certificate',
        'certificate_status',
        'certificate_admin_notes',
        'certificate_reviewed_by_id',
        'certificate_reviewed_at',
        'status',
        'admin_notes',
        'reviewed_by_id',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'certificate_reviewed_at' => 'datetime',
            'credential_paths' => 'array',
            'placement_certificate' => 'array',
        ];
    }

    /** @return array<string, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_REVIEWED => 'Reviewed',
            self::STATUS_CLOSED => 'Closed',
        ];
    }

    /** @return array<string, string> */
    public static function certificateStatuses(): array
    {
        return [
            self::CERT_AWAITING_UPLOAD => 'Certificate required',
            self::CERT_PENDING_REVIEW => 'Certificate under review',
            self::CERT_APPROVED => 'Awarded',
            self::CERT_REJECTED => 'Certificate needs resubmission',
        ];
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPlacementApproved(): bool
    {
        return $this->isApproved();
    }

    public function isCareerAwarded(): bool
    {
        return $this->isApproved() && $this->certificate_status === self::CERT_APPROVED;
    }

    public function needsCertificateUpload(): bool
    {
        return $this->isApproved()
            && in_array($this->certificate_status, [self::CERT_AWAITING_UPLOAD, self::CERT_REJECTED, null], true);
    }

    public function certificateIsPendingReview(): bool
    {
        return $this->isApproved() && $this->certificate_status === self::CERT_PENDING_REVIEW;
    }

    public function certificateStatusLabel(): ?string
    {
        if (! $this->isApproved() || $this->certificate_status === null) {
            return null;
        }

        return self::certificateStatuses()[$this->certificate_status]
            ?? str($this->certificate_status)->headline()->toString();
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(CareerOpportunity::class, 'career_opportunity_id');
    }

    public function graduate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    public function certificateReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'certificate_reviewed_by_id');
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? str($this->status)->headline()->toString();
    }
}

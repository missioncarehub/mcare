<?php

namespace App\Models;

use App\Support\TraineeUserProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class EnrollmentApplication extends Model
{
    use HasFactory;

    public const STATUS_PROFILE_SUBMITTED = 'profile_submitted';

    public const STATUS_PRE_ENLISTMENT = 'pre_enlistment';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DENIED = 'denied';

    public const LEARNING_ACTIVE = 'active';

    public const LEARNING_PAUSED = 'paused';

    public const LEARNING_GRADUATED = 'graduated';

    public const LEARNING_WITHDRAWN = 'withdrawn';

    public const PAYMENT_NOT_SELECTED = 'not_selected';

    public const PAYMENT_ONSITE_PENDING = 'onsite_pending';

    public const PAYMENT_ONLINE_PENDING = 'online_pending';

    public const PAYMENT_PARTIALLY_PAID = 'partially_paid';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_EXPIRED = 'expired';

    public const YEAR_GRADUATED_NOT_APPLICABLE = 0;

    protected $fillable = [
        'user_id',
        'enrollment_number',
        'admission_application_id',
        'email',
        'program',
        'training_program_id',
        'intake_channel',
        'is_historical_record',
        'training_batch_id',
        'first_name',
        'middle_name',
        'last_name',
        'extension_name',
        'birth_date',
        'birthplace_city',
        'birthplace_province',
        'birthplace_region',
        'gender',
        'civil_status',
        'employment_status',
        'employment_type',
        'contact_number',
        'nationality',
        'schedule_preference',
        'street',
        'barangay',
        'city',
        'province',
        'region',
        'zip_code',
        'educational_attainment',
        'school_name',
        'year_graduated',
        'guardian_name',
        'guardian_address',
        'classification',
        'disability_type',
        'disability_cause',
        'scholarship_type',
        'privacy_consent',
        'signature_name',
        'birth_certificate_path',
        'education_document_path',
        'good_moral_certificate_path',
        'id_photo_path',
        'signature_type',
        'signature_path',
        'document_review',
        'documents_reviewed_at',
        'documents_reviewed_by_id',
        'onsite_requirements_verified_at',
        'onsite_requirements_verified_by_id',
        'onsite_requirements_notes',
        'date_accomplished',
        'status',
        'review_released_at',
        'learning_status',
        'learning_status_notes',
        'learning_status_changed_at',
        'learning_status_changed_by_id',
        'payment_method',
        'total_program_fee',
        'downpayment_amount',
        'total_paid_amount',
        'payment_status',
        'payment_amount',
        'payment_currency',
        'payment_reference',
        'payment_receipt_number',
        'payment_receipt_expires_at',
        'payment_selected_at',
        'paymongo_checkout_reference',
        'paymongo_checkout_url',
        'payment_meta',
        'payment_verified_by_id',
        'payment_verified_at',
        'payment_verification_notes',
        'admin_notes',
        'reviewed_at',
        'learning_started_at',
        'reviewed_by_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (EnrollmentApplication $application): void {
            if (blank($application->enrollment_number)) {
                $application->enrollment_number = self::generateNumber();
            }
        });

        static::saved(function (EnrollmentApplication $application): void {
            if (! $application->user_id) {
                return;
            }

            $user = $application->relationLoaded('user')
                ? $application->user
                : $application->user()->first();

            if ($user) {
                TraineeUserProfile::sync($user, $application);
            }
        });
    }

    public static function generateNumber(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $suffix = '';
            for ($i = 0; $i < 6; $i++) {
                $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            $number = 'MCE-'.now()->year.'-'.$suffix;
        } while (self::query()->where('enrollment_number', $number)->exists());

        return $number;
    }

    public static function normalizeNumber(?string $value): string
    {
        $compact = self::compactLookupKey($value);

        if (preg_match('/^MCE(\d{4})([A-Z0-9]{6})$/', $compact, $matches) === 1) {
            return 'MCE-'.$matches[1].'-'.$matches[2];
        }

        return strtoupper(trim((string) $value));
    }

    public static function compactLookupKey(?string $value): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) $value));
    }

    public static function findByNumber(?string $value): ?self
    {
        $number = self::normalizeNumber($value);

        if ($number === '') {
            return null;
        }

        return self::query()->where('enrollment_number', $number)->first();
    }

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'date_accomplished' => 'date',
            'privacy_consent' => 'boolean',
            'is_historical_record' => 'boolean',
            'review_released_at' => 'datetime',
            'total_program_fee' => 'decimal:2',
            'downpayment_amount' => 'decimal:2',
            'total_paid_amount' => 'decimal:2',
            'payment_amount' => 'decimal:2',
            'payment_receipt_expires_at' => 'datetime',
            'payment_selected_at' => 'datetime',
            'payment_meta' => 'array',
            'payment_verified_at' => 'datetime',
            'document_review' => 'array',
            'documents_reviewed_at' => 'datetime',
            'onsite_requirements_verified_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'learning_started_at' => 'datetime',
            'learning_status_changed_at' => 'datetime',
            'year_graduated' => 'integer',
        ];
    }

    public function yearGraduatedLabel(): string
    {
        if (! AdmissionApplication::requiresGraduationYear($this->educational_attainment)) {
            return 'N/A';
        }

        $year = (int) $this->year_graduated;

        return $year >= 1950 ? (string) $year : 'N/A';
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PRE_ENLISTMENT => 'Pre-enlistment',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_DENIED => 'Denied',
        ];
    }

    public static function reviewableStatuses(): array
    {
        return [
            self::STATUS_PRE_ENLISTMENT,
            self::STATUS_APPROVED,
            self::STATUS_DENIED,
        ];
    }

    public function statusLabel(): string
    {
        if ($this->status === self::STATUS_PROFILE_SUBMITTED) {
            return self::statuses()[self::STATUS_PRE_ENLISTMENT];
        }

        return self::statuses()[$this->status] ?? str($this->status)->headline()->toString();
    }

    public static function learningStatuses(): array
    {
        return [
            self::LEARNING_ACTIVE => 'Active',
            self::LEARNING_PAUSED => 'Paused',
            self::LEARNING_GRADUATED => 'Graduated',
            self::LEARNING_WITHDRAWN => 'Withdrawn',
        ];
    }

    public function learningStatusLabel(): string
    {
        return self::learningStatuses()[$this->learning_status]
            ?? str($this->learning_status)->headline()->toString();
    }

    public function accountDeletionTitle(): string
    {
        return $this->is_historical_record
            ? 'Delete verified alumni record?'
            : 'Delete this trainee?';
    }

    public function accountDeletionMessage(): string
    {
        $name = trim($this->first_name.' '.$this->last_name) ?: 'this trainee';

        if ($this->is_historical_record) {
            return $name.' was added through a verified historical alumni claim, not a regular enrollment.';
        }

        return "Permanently delete {$name}? Their account, enrollment, payment records, uploaded documents, and learning history will be removed. This cannot be undone.";
    }

    public function accountDeletionDetail(): ?string
    {
        if (! $this->is_historical_record) {
            return null;
        }

        return 'Deleting permanently removes their alumni account, verified training record, approved alumni claim, uploaded certificate or TOR evidence, and Career Hub access. This cannot be undone.';
    }

    public function accountDeletionAction(): string
    {
        return $this->is_historical_record ? 'Delete alumni record' : 'Delete trainee';
    }

    public static function paymentStatuses(): array
    {
        return [
            self::PAYMENT_NOT_SELECTED => 'Not selected',
            self::PAYMENT_ONSITE_PENDING => 'Pay on site',
            self::PAYMENT_ONLINE_PENDING => 'Online pending',
            self::PAYMENT_PARTIALLY_PAID => 'Partially paid',
            self::PAYMENT_PAID => 'Fully paid',
            self::PAYMENT_EXPIRED => 'Expired',
        ];
    }

    public function getFullNameAttribute(): string
    {
        $name = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        return filled($name) ? $name : ($this->user?->name ?? 'Trainee #'.$this->id);
    }

    public function paymentStatusLabel(): string
    {
        return self::paymentStatuses()[$this->payment_status] ?? str($this->payment_status)->headline()->toString();
    }

    public function remainingBalance(): float
    {
        $fee = (float) ($this->total_program_fee ?? 22000.00);
        $paid = (float) ($this->total_paid_amount ?? 0.00);

        return max(0.0, round($fee - $paid, 2));
    }

    public function isDownpaymentSatisfied(): bool
    {
        if ($this->payment_status === self::PAYMENT_PAID) {
            return true;
        }

        $downpayment = (float) ($this->downpayment_amount ?? 2000.00);
        $paid = (float) ($this->total_paid_amount ?? 0.00);

        return $paid >= $downpayment;
    }

    public function hasEnrollmentPaymentClearance(): bool
    {
        return $this->isDownpaymentSatisfied() && $this->payment_verified_at !== null;
    }

    public function isReleasedForReview(): bool
    {
        return $this->review_released_at !== null;
    }

    public function scopeReleasedForReview(Builder $query): Builder
    {
        return $query->whereNotNull('review_released_at');
    }

    public function recalculatePaymentStatus(): void
    {
        $totalPaid = (float) $this->paymentTransactions()
            ->where('status', PaymentTransaction::STATUS_VERIFIED)
            ->sum('amount');

        // Include legacy payment amount if verified paid previously without transaction row
        if ($totalPaid <= 0 && $this->payment_status === self::PAYMENT_PAID && (float) $this->payment_amount > 0) {
            $totalPaid = (float) $this->payment_amount;
        }

        $totalFee = (float) ($this->total_program_fee ?? 22000.00);
        $downpayment = (float) ($this->downpayment_amount ?? 2000.00);

        $this->total_paid_amount = $totalPaid;

        if ($totalPaid >= $totalFee) {
            $this->payment_status = self::PAYMENT_PAID;
        } elseif ($totalPaid >= $downpayment || $totalPaid > 0) {
            $this->payment_status = self::PAYMENT_PARTIALLY_PAID;
        }

        $this->save();
    }

    public function hasActiveOnsiteReceipt(): bool
    {
        if ($this->payment_method !== 'onsite') {
            return false;
        }

        if (blank($this->payment_reference) && blank($this->payment_receipt_number)) {
            return false;
        }

        return ! $this->payment_receipt_expires_at || $this->payment_receipt_expires_at->isFuture();
    }

    public function canViewPaymentSlip(): bool
    {
        if ($this->payment_method === 'onsite') {
            return filled($this->payment_reference) || filled($this->payment_receipt_number);
        }

        return $this->hasEnrollmentPaymentClearance();
    }

    public function paymongoPaymentId(): ?string
    {
        $fromMeta = data_get($this->payment_meta, 'paymongo_payment_id');
        if (filled($fromMeta)) {
            return (string) $fromMeta;
        }

        if (! $this->relationLoaded('paymentTransactions')) {
            return null;
        }

        return $this->paymentTransactions->first(
            fn (PaymentTransaction $transaction): bool => $transaction->payment_channel === PaymentTransaction::CHANNEL_ONLINE
                && filled($transaction->reference_number)
        )?->reference_number;
    }

    public function latestPaymentReference(): ?string
    {
        if ($this->payment_method === 'online') {
            return $this->paymongoPaymentId()
                ?: $this->payment_reference
                ?: $this->paymongo_checkout_reference;
        }

        $pendingTicket = $this->relationLoaded('paymentTransactions')
            ? $this->paymentTransactions->first(fn (PaymentTransaction $transaction): bool => $transaction->isOnsiteTicket())
            : null;

        return $this->payment_reference
            ?: $pendingTicket?->reference_number
            ?: $pendingTicket?->ticket_number
            ?: $this->payment_receipt_number;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admissionApplication(): BelongsTo
    {
        return $this->belongsTo(AdmissionApplication::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    public function paymentVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_verified_by_id');
    }

    public function documentReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'documents_reviewed_by_id');
    }

    public function onsiteRequirementsVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'onsite_requirements_verified_by_id');
    }

    public function learningStatusChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'learning_status_changed_by_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(TrainingBatch::class, 'training_batch_id');
    }

    public function trainingProgram(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class, 'training_program_id');
    }

    public function moduleProgress(): HasMany
    {
        return $this->hasMany(ModuleProgress::class, 'enrollment_application_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(TraineeAttendance::class, 'enrollment_application_id');
    }

    public function competencyRecords(): HasMany
    {
        return $this->hasMany(TraineeCompetencyRecord::class, 'enrollment_application_id');
    }

    public function officialDocuments(): HasMany
    {
        return $this->hasMany(OfficialDocument::class, 'enrollment_application_id');
    }

    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class)->latest('paid_at');
    }

    public function targetedQuizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'target_enrollment_application_id');
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'enrollment_application_id');
    }

    public function effectivePaymentDeadline(): ?Carbon
    {
        $receiptDeadline = $this->payment_receipt_expires_at;
        $batchDeadline = $this->batch?->enrollment_ends_at;

        if ($receiptDeadline && $batchDeadline) {
            return $receiptDeadline->lte($batchDeadline) ? $receiptDeadline : $batchDeadline;
        }

        return $receiptDeadline ?: $batchDeadline;
    }
}

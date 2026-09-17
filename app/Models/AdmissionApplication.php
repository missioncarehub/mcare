<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AdmissionApplication extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DENIED = 'denied';

    public const EMAIL_IN_USE_MESSAGE = 'This Gmail has already been used for a pending or approved MCARE application.';

    protected $fillable = [
        'application_number',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'contact_number',
        'schedule_preference',
        'educational_attainment',
        'notes',
        'training_program_id',
        'program',
        'status',
        'privacy_consent_at',
        'admin_notes',
        'reviewed_at',
        'reviewed_by_id',
    ];

    protected function casts(): array
    {
        return [
            'privacy_consent_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_DENIED => 'Denied',
        ];
    }

    public static function educationalAttainmentOptions(): array
    {
        return [
            'No Grade Completed',
            'Elementary Undergraduate',
            'Elementary Graduate',
            'High School Undergraduate',
            'High School Graduate',
            'Junior High (K-12)',
            'Senior High (K-12)',
            'Post-Secondary/Technical Vocational Undergraduate',
            'Post-Secondary/Technical Vocational Graduate',
            'College Undergraduate',
            'College Graduate',
            'Masteral',
            'Doctorate',
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? str($this->status)->headline()->toString();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isDenied(): bool
    {
        return $this->status === self::STATUS_DENIED;
    }

    public function fullName(): string
    {
        return trim(collect([$this->first_name, $this->middle_name, $this->last_name])->filter()->implode(' '));
    }

    public static function generateNumber(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $suffix = '';
            for ($i = 0; $i < 6; $i++) {
                $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            $number = 'MCA-'.now()->year.'-'.$suffix;
        } while (self::query()->where('application_number', $number)->exists());

        return $number;
    }

    public static function normalizeNumber(?string $value): string
    {
        $compact = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) $value) ?? '');

        if (preg_match('/^MCA(\d{4})([A-Z0-9]{6})$/', $compact, $matches) === 1) {
            return 'MCA-'.$matches[1].'-'.$matches[2];
        }

        return strtoupper(trim((string) $value));
    }

    public static function findByNumber(?string $value): ?self
    {
        $number = self::normalizeNumber($value);

        if ($number === '') {
            return null;
        }

        return self::query()->where('application_number', $number)->first();
    }

    public function trainingProgram(): BelongsTo
    {
        return $this->belongsTo(TrainingProgram::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    public function enrollment(): HasOne
    {
        return $this->hasOne(EnrollmentApplication::class);
    }

    public function enrollmentUrl(): string
    {
        return route('enrollment.create', ['application_number' => $this->application_number]);
    }

    // Path: app/Models/AdmissionApplication.php | Label: Applicant lifecycle stage progression
    // Produces the ordered set of stages the applicant walks through (application → enrollment →
    // documents → payment → admin review). Used by the public status lookup page and the admin
    // review screens so both stay in sync.
    /**
     * @return array<int, array{key: string, label: string, description: string, state: string}>
     */
    public function progressStages(): array
    {
        $enrollment = $this->relationLoaded('enrollment')
            ? $this->enrollment
            : $this->enrollment()->first();

        $applicationState = match ($this->status) {
            self::STATUS_APPROVED => 'complete',
            self::STATUS_DENIED => 'blocked',
            default => 'in_progress',
        };

        // Stage 2: filling the enrollment form (only unlocks after admission approved).
        if ($this->status === self::STATUS_APPROVED) {
            $enrollmentFormState = $enrollment ? 'complete' : 'in_progress';
        } elseif ($this->status === self::STATUS_DENIED) {
            $enrollmentFormState = 'blocked';
        } else {
            $enrollmentFormState = 'pending';
        }

        // Stage 3: documents review.
        $docState = 'pending';
        if ($enrollment) {
            $documentsUploaded = filled($enrollment->birth_certificate_path)
                || filled($enrollment->education_document_path)
                || filled($enrollment->good_moral_certificate_path)
                || filled($enrollment->id_photo_path);
            $reviewedAt = $enrollment->documents_reviewed_at;
            if ($reviewedAt) {
                $docState = 'complete';
            } elseif ($documentsUploaded) {
                $docState = 'in_progress';
            }
        }

        // Stage 4: payment.
        $paymentState = 'pending';
        if ($enrollment) {
            $paymentStatus = $enrollment->payment_status;
            if ($paymentStatus === \App\Models\EnrollmentApplication::PAYMENT_PAID
                && $enrollment->payment_verified_at) {
                $paymentState = 'complete';
            } elseif (in_array($paymentStatus, [
                \App\Models\EnrollmentApplication::PAYMENT_PARTIALLY_PAID,
                \App\Models\EnrollmentApplication::PAYMENT_ONLINE_PENDING,
                \App\Models\EnrollmentApplication::PAYMENT_ONSITE_PENDING,
                \App\Models\EnrollmentApplication::PAYMENT_PAID,
            ], true)) {
                $paymentState = 'in_progress';
            } elseif ($paymentStatus === \App\Models\EnrollmentApplication::PAYMENT_EXPIRED) {
                $paymentState = 'blocked';
            }
        }

        // Stage 5: final admin review of the enrollment.
        $reviewState = 'pending';
        if ($enrollment) {
            if ($enrollment->status === \App\Models\EnrollmentApplication::STATUS_APPROVED) {
                $reviewState = 'complete';
            } elseif ($enrollment->status === \App\Models\EnrollmentApplication::STATUS_DENIED) {
                $reviewState = 'blocked';
            } elseif ($enrollment->review_released_at) {
                $reviewState = 'in_progress';
            }
        }

        return [
            [
                'key' => 'application',
                'label' => 'Application submitted',
                'description' => 'MCARE received your admission form and reviews your basic details.',
                'state' => $applicationState,
            ],
            [
                'key' => 'enrollment_form',
                'label' => 'Enrollment form',
                'description' => 'Fill in the TESDA-style enrollment form using your application number.',
                'state' => $enrollmentFormState,
            ],
            [
                'key' => 'documents',
                'label' => 'Documents',
                'description' => 'Upload birth certificate, education document, good-moral, and ID photo.',
                'state' => $docState,
            ],
            [
                'key' => 'payment',
                'label' => 'Payment',
                'description' => 'Pay the required downpayment (online or on-site) and wait for verification.',
                'state' => $paymentState,
            ],
            [
                'key' => 'admin_review',
                'label' => 'Admin review',
                'description' => 'MCARE reviews the completed enrollment and unlocks the LMS.',
                'state' => $reviewState,
            ],
        ];
    }

    // Returns the currently active stage (first non-complete stage) or the last one if all done.
    /**
     * @return array{key: string, label: string, description: string, state: string}
     */
    public function currentStage(): array
    {
        $stages = $this->progressStages();
        foreach ($stages as $stage) {
            if ($stage['state'] !== 'complete') {
                return $stage;
            }
        }

        return end($stages) ?: [
            'key' => 'application',
            'label' => 'Application submitted',
            'description' => 'MCARE received your admission form.',
            'state' => 'in_progress',
        ];
    }
}

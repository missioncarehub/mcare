<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CareerOpportunity extends Model
{
    use HasFactory;

    public const GENDER_FEMALE = 'female';

    public const GENDER_MALE = 'male';

    public const GENDER_NOT_SPECIFIED = 'not-specified';

    public const MOBILITY_AMBULATORY = 'ambulatory';

    public const MOBILITY_BEDRIDDEN = 'bedridden';

    public const SMS_NONE = 'none';

    public const SMS_IMMEDIATE = 'immediate';

    public const SMS_SCHEDULED = 'scheduled';

    protected $fillable = [
        'created_by_id',
        'estimated_start_date',
        'patient_gender',
        'mobility_status',
        'patient_age',
        'specific_contraptions',
        'condition_summary',
        // These neutral values keep records compatible with the original schema.
        'title',
        'estimated_salary',
        'employer',
        'description',
        'is_published',
        'published_at',
        'sms_mode',
        'sms_scheduled_at',
        'sms_sent_at',
        'sms_sent_count',
        'sms_skipped_count',
        'sms_last_error',
    ];

    protected function casts(): array
    {
        return [
            'estimated_start_date' => 'date',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'sms_scheduled_at' => 'datetime',
            'sms_sent_at' => 'datetime',
            'sms_sent_count' => 'integer',
            'sms_skipped_count' => 'integer',
        ];
    }

    /** @return array<string, string> */
    public static function patientGenders(): array
    {
        return [
            self::GENDER_FEMALE => 'Female',
            self::GENDER_MALE => 'Male',
            self::GENDER_NOT_SPECIFIED => 'Not specified',
        ];
    }

    /** @return array<string, string> */
    public static function mobilityStatuses(): array
    {
        return [
            self::MOBILITY_AMBULATORY => 'Ambulatory',
            self::MOBILITY_BEDRIDDEN => 'Bedridden',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(CareerInquiry::class);
    }

    public function scopeVisibleToAlumni(Builder $query): Builder
    {
        // Legacy broad job records stay private until an admin supplies the approved fields.
        return $query
            ->where('is_published', true)
            ->whereNotNull('estimated_start_date')
            ->whereNotNull('patient_gender')
            ->whereNotNull('mobility_status')
            ->whereDate('estimated_start_date', '>=', today());
    }

    public function isVisibleToAlumni(): bool
    {
        return $this->is_published
            && $this->estimated_start_date !== null
            && filled($this->patient_gender)
            && filled($this->mobility_status)
            && $this->estimated_start_date->gte(today());
    }

    public function patientGenderLabel(): string
    {
        return self::patientGenders()[$this->patient_gender]
            ?? str($this->patient_gender ?: 'Not specified')->headline()->toString();
    }

    public function mobilityStatusLabel(): string
    {
        return self::mobilityStatuses()[$this->mobility_status]
            ?? str($this->mobility_status ?: 'Not specified')->headline()->toString();
    }

    public function listingTitle(): string
    {
        $title = trim((string) $this->title);

        return $title !== '' ? $title : 'Career opportunity';
    }

    public function listingEmployer(): string
    {
        $employer = trim((string) $this->employer);

        return $employer !== '' ? $employer : 'MCARE-Coordinated Placement';
    }

    public function postingSummary(): ?string
    {
        $summary = trim((string) $this->condition_summary);

        return $summary !== '' ? $summary : null;
    }

    public function graduateSmsMessage(): string
    {
        $parts = [
            'Dear Alumni, we have a job offer for you: '.$this->listingTitle(),
            'Salary: '.($this->estimated_salary ?: 'see Career Hub'),
            'Start: '.($this->estimated_start_date?->format('M d, Y') ?? 'TBA'),
        ];

        $employer = trim((string) $this->employer);
        if ($employer !== '' && $employer !== 'MCARE-Coordinated Placement') {
            $parts[] = 'Employer: '.$employer;
        }

        $care = array_values(array_filter([
            filled($this->patient_gender) ? $this->patientGenderLabel() : null,
            filled($this->mobility_status) ? $this->mobilityStatusLabel() : null,
            $this->patient_age !== null ? 'age '.$this->patient_age : null,
        ]));

        if ($care !== []) {
            $parts[] = 'Patient: '.implode(', ', $care);
        }

        $parts[] = 'Please open the MCARE Career Hub for details: https://mcarehub.com';

        return implode('. ', $parts);
    }

    public function smsStatusLabel(): string
    {
        if ($this->sms_sent_at) {
            return $this->sms_sent_count > 0
                ? 'SMS sent to '.$this->sms_sent_count.' '.str('graduate')->plural($this->sms_sent_count)
                : 'SMS skipped, no graduate numbers';
        }

        if (filled($this->sms_last_error)) {
            return 'SMS failed: '.$this->sms_last_error;
        }

        if ($this->sms_mode === self::SMS_SCHEDULED && $this->sms_scheduled_at) {
            return 'SMS scheduled '.$this->sms_scheduled_at->timezone(config('app.timezone'))->format('M d, Y g:i A');
        }

        if ($this->sms_mode === self::SMS_IMMEDIATE) {
            return 'SMS pending';
        }

        return 'No SMS';
    }
}

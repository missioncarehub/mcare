<?php

namespace App\Models;

use App\Notifications\QueuedVerifyEmail;
use App\Support\RolePermissionMatrix;
use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, MustVerifyEmail, Notifiable;

    protected static function booted(): void
    {
        static::saved(function (User $user): void {
            if (! $user->wasRecentlyCreated && ! $user->wasChanged('role')) {
                return;
            }

            $tables = config('permission.table_names');

            // Fresh installs may create users before package tables are ready.
            if (! Schema::hasTable($tables['roles'] ?? 'roles')
                || ! Schema::hasTable($tables['model_has_roles'] ?? 'model_has_roles')) {
                return;
            }

            RolePermissionMatrix::syncUser($user);
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'middle_name',
        'last_name',
        'extension_name',
        'email',
        'contact_email',
        'contact_number',
        'birth_date',
        'birthplace_city',
        'birthplace_province',
        'birthplace_region',
        'gender',
        'civil_status',
        'employment_status',
        'employment_type',
        'nationality',
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
        'google_id',
        'avatar_url',
        'profile_photo_path',
        'role',
        'applicant_status',
        'trainee_status',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'date',
            'year_graduated' => 'integer',
            'password' => 'hashed',
        ];
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notifyNow(new QueuedVerifyEmail);
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->profilePhotoUrl(),
            set: fn (?string $value): array => [
                'profile_photo_path' => self::storedPhotoFromInput($value),
            ],
        );
    }

    public function profilePhotoUrl(): ?string
    {
        $stored = trim((string) $this->profile_photo_path);
        if ($stored === '' || str_contains($stored, '..')) {
            return null;
        }

        if (str_starts_with($stored, 'avatars/'.$this->id.'/')) {
            return '/storage/'.$stored;
        }

        if (str_starts_with($stored, 'https://')) {
            return $stored;
        }

        if (str_starts_with($stored, '/') && ! str_starts_with($stored, '//')) {
            return $stored;
        }

        return null;
    }

    public static function storedPhotoFromInput(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $path = parse_url($value, PHP_URL_PATH) ?: $value;
        $stored = str_starts_with((string) $path, '/storage/avatars/')
            ? ltrim(substr((string) $path, strlen('/storage/')), '/')
            : $value;

        return str_contains($stored, '..') ? null : $stored;
    }

    public function trainingModules(): HasMany
    {
        return $this->hasMany(TrainingModule::class, 'trainer_id');
    }

    public function trainingBatches(): HasMany
    {
        return $this->hasMany(TrainingBatch::class, 'trainer_id');
    }

    public function trainerAnnouncements(): HasMany
    {
        return $this->hasMany(TrainerAnnouncement::class, 'trainer_id');
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'trainer_id');
    }

    public function alumniProfile(): HasOne
    {
        return $this->hasOne(AlumniProfile::class);
    }

    public function historicalAlumniClaim(): HasOne
    {
        return $this->hasOne(HistoricalAlumniClaim::class);
    }

    public function enrollmentApplication(): HasOne
    {
        return $this->hasOne(EnrollmentApplication::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function adminAnnouncements(): HasMany
    {
        return $this->hasMany(AdminAnnouncement::class, 'author_id');
    }

    public function classroomComments(): HasMany
    {
        return $this->hasMany(ClassroomComment::class, 'author_id');
    }

    public function privateClassroomComments(): HasMany
    {
        return $this->hasMany(ClassroomComment::class, 'recipient_user_id');
    }

    /** @return array<string, string> */
    public static function traineeStatuses(): array
    {
        return [
            EnrollmentApplication::LEARNING_ACTIVE => 'Active student',
            EnrollmentApplication::LEARNING_PAUSED => 'Paused',
            EnrollmentApplication::LEARNING_GRADUATED => 'Graduate',
            EnrollmentApplication::LEARNING_WITHDRAWN => 'Withdrawn',
        ];
    }

    public function traineeStatusLabel(): string
    {
        return self::traineeStatuses()[$this->trainee_status]
            ?? ($this->role === 'trainee' ? 'Active student' : 'Not a trainee');
    }

    public function isActiveStudent(): bool
    {
        return $this->trainee_status === EnrollmentApplication::LEARNING_ACTIVE
            || ($this->trainee_status === null && $this->role === 'trainee' && ! $this->isGraduate());
    }

    public function isGraduate(): bool
    {
        if ($this->trainee_status === EnrollmentApplication::LEARNING_GRADUATED) {
            return true;
        }

        $application = $this->relationLoaded('enrollmentApplication')
            ? $this->enrollmentApplication
            : $this->enrollmentApplication()->first();

        return $application?->status === EnrollmentApplication::STATUS_APPROVED
            && $application->learning_status === EnrollmentApplication::LEARNING_GRADUATED;
    }

    // Path: app/Models/User.php | Label: Check whether the trainee has an approved enrollment
    // Used by the trainee shell/middleware to decide whether LMS modules and classwork are unlocked.
    public function hasApprovedEnrollment(): bool
    {
        $application = $this->relationLoaded('enrollmentApplication')
            ? $this->enrollmentApplication
            : $this->enrollmentApplication()->first();

        return $application?->status === EnrollmentApplication::STATUS_APPROVED;
    }
}

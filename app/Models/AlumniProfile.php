<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlumniProfile extends Model
{
    use HasFactory;

    public const RANK_JUNIOR = 'junior';

    public const RANK_SENIOR = 'senior';

    protected $fillable = [
        'user_id',
        'rank',
        'rank_promoted_at',
        'rank_promoted_by_id',
        'is_available_for_duty',
        'availability_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'is_available_for_duty' => 'boolean',
            'availability_updated_at' => 'datetime',
            'rank_promoted_at' => 'datetime',
        ];
    }

    /** @return array<string, string> */
    public static function ranks(): array
    {
        return [
            self::RANK_JUNIOR => 'Junior alumni',
            self::RANK_SENIOR => 'Senior alumni',
        ];
    }

    public function rankLabel(): string
    {
        return self::ranks()[$this->rank] ?? self::ranks()[self::RANK_JUNIOR];
    }

    public function isSenior(): bool
    {
        return $this->rank === self::RANK_SENIOR;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

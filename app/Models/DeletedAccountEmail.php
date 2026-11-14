<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DeletedAccountEmail extends Model
{
    protected $table = 'deleted_account_email_blocks';

    protected $fillable = [
        'email_hash',
        'deleted_role',
    ];

    public static function hashFor(string $email): string
    {
        return hash('sha256', Str::lower(trim($email)));
    }

    public static function isBlocked(?string $email): bool
    {
        return filled($email)
            && static::query()->where('email_hash', static::hashFor($email))->exists();
    }

    public static function record(string $email, string $role): void
    {
        static::query()->updateOrCreate(
            ['email_hash' => static::hashFor($email)],
            ['deleted_role' => $role],
        );
    }
}
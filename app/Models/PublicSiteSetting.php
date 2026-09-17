<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicSiteSetting extends Model
{
    public const UNUSED_APPROVED_EXPIRY_OFF = 'off';

    public const UNUSED_APPROVED_EXPIRY_DAYS = 'days';

    public const UNUSED_APPROVED_EXPIRY_MONTHS = 'months';

    public const UNUSED_APPROVED_EXPIRY_DATE = 'date';

    protected $fillable = [
        'facebook_url',
        'instagram_url',
        'youtube_url',
        'registrar_name',
        'registrar_signature_type',
        'registrar_signature_path',
        'unused_approved_expiry_mode',
        'unused_approved_expiry_amount',
        'unused_approved_expiry_date',
    ];

    public static function current(): self
    {
        return static::query()->first() ?? new static(self::defaultAttributes());
    }

    public static function instance(): self
    {
        $existing = static::query()->first();

        if ($existing) {
            return $existing;
        }

        return static::query()->create(self::defaultAttributes());
    }

    protected function casts(): array
    {
        return [
            'unused_approved_expiry_amount' => 'integer',
            'unused_approved_expiry_date' => 'date',
        ];
    }

    /** @return array<string, mixed> */
    public static function defaultAttributes(): array
    {
        return [
            'facebook_url' => null,
            'instagram_url' => null,
            'youtube_url' => null,
            'registrar_name' => null,
            'registrar_signature_type' => null,
            'registrar_signature_path' => null,
            'unused_approved_expiry_mode' => self::UNUSED_APPROVED_EXPIRY_OFF,
            'unused_approved_expiry_amount' => null,
            'unused_approved_expiry_date' => null,
        ];
    }

    public static function unusedApprovedExpiryModes(): array
    {
        return [
            self::UNUSED_APPROVED_EXPIRY_OFF => 'Keep approved applications',
            self::UNUSED_APPROVED_EXPIRY_DAYS => 'After a number of days',
            self::UNUSED_APPROVED_EXPIRY_MONTHS => 'After a number of months',
            self::UNUSED_APPROVED_EXPIRY_DATE => 'On a specific date',
        ];
    }

    public function unusedApprovedExpiryMode(): string
    {
        $mode = (string) ($this->unused_approved_expiry_mode ?: self::UNUSED_APPROVED_EXPIRY_OFF);

        return array_key_exists($mode, self::unusedApprovedExpiryModes())
            ? $mode
            : self::UNUSED_APPROVED_EXPIRY_OFF;
    }

    public function unusedApprovedExpiryIsEnabled(): bool
    {
        return $this->unusedApprovedExpiryMode() !== self::UNUSED_APPROVED_EXPIRY_OFF;
    }

    public function unusedApprovedExpirySummary(): ?string
    {
        return match ($this->unusedApprovedExpiryMode()) {
            self::UNUSED_APPROVED_EXPIRY_DAYS => $this->expiryAmountSummary('day'),
            self::UNUSED_APPROVED_EXPIRY_MONTHS => $this->expiryAmountSummary('month'),
            self::UNUSED_APPROVED_EXPIRY_DATE => $this->unused_approved_expiry_date
                ? 'Approved applications that are never enrolled are deleted starting '.$this->unused_approved_expiry_date->format('M j, Y').'. Later approvals stay until this date is updated.'
                : null,
            default => null,
        };
    }

    private function expiryAmountSummary(string $unit): ?string
    {
        $amount = (int) $this->unused_approved_expiry_amount;
        if ($amount < 1) {
            return null;
        }

        return 'Approved applications that are never enrolled are deleted after '.$amount.' '
            .Str::plural($unit, $amount).' from approval.';
    }

    public function registrarName(): string
    {
        return trim((string) $this->registrar_name);
    }

    public function registrarNameForForm(): string
    {
        return $this->registrarName()
            ?: trim((string) config('official_documents.organization.registrar_name'));
    }

    public function hasRegistrarSignature(): bool
    {
        $path = trim((string) $this->registrar_signature_path);

        return $path !== '' && Storage::disk('local')->exists($path);
    }

    /** @return array{facebook: ?string, instagram: ?string, youtube: ?string} */
    public function socialLinks(): array
    {
        return [
            'facebook' => $this->facebook_url,
            'instagram' => $this->instagram_url,
            'youtube' => $this->youtube_url,
        ];
    }

    public static function normalizeHttpsUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        if (! preg_match('/\Ahttps?:\/\//i', $url)) {
            $url = 'https://'.$url;
        }

        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '/');
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        if ($host === '') {
            return '';
        }

        return 'https://'.$host.$path.$query;
    }

    /** @return list<string> */
    public static function allowedHosts(string $network): array
    {
        return match ($network) {
            'facebook' => [
                'facebook.com',
                'www.facebook.com',
                'm.facebook.com',
                'web.facebook.com',
                'fb.com',
                'www.fb.com',
            ],
            'instagram' => [
                'instagram.com',
                'www.instagram.com',
            ],
            'youtube' => [
                'youtube.com',
                'www.youtube.com',
                'm.youtube.com',
                'youtu.be',
                'www.youtu.be',
            ],
            default => [],
        };
    }

    public static function isAllowedSocialUrl(string $url, string $network): bool
    {
        $normalized = self::normalizeHttpsUrl($url);
        $host = strtolower((string) (parse_url($normalized, PHP_URL_HOST) ?: ''));

        return $normalized !== '' && in_array($host, self::allowedHosts($network), true);
    }
}

<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SemaphoreSmsService
{
    public const ENDPOINT = 'https://api.semaphore.co/api/v4/messages';

    public const PRIORITY_ENDPOINT = 'https://api.semaphore.co/api/v4/priority';

    public function configured(): bool
    {
        return filled(config('services.semaphore.key'));
    }

    /**
     * @param  list<string>  $numbers
     * @return array{sent: bool, status: ?int, error: ?string}
     */
    public function send(array $numbers, string $message, ?string $scheduledAt = null, bool $priority = false): array
    {
        $recipients = array_values(array_unique(array_filter($numbers)));

        if ($recipients === []) {
            return ['sent' => false, 'status' => null, 'error' => 'No valid graduate contact numbers were found.'];
        }

        if (! $this->configured()) {
            return ['sent' => false, 'status' => null, 'error' => 'Semaphore API key is not configured.'];
        }

        $payload = [
            'apikey' => (string) config('services.semaphore.key'),
            'number' => implode(',', $recipients),
            'message' => $message,
        ];

        $sender = trim((string) config('services.semaphore.sender'));
        if ($sender !== '') {
            $payload['sendername'] = $sender;
        }

        if (filled($scheduledAt)) {
            $payload['scheduled'] = $scheduledAt;
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(20)
                ->post($priority && ! filled($scheduledAt) ? self::PRIORITY_ENDPOINT : self::ENDPOINT, $payload);
        } catch (ConnectionException $exception) {
            Log::warning('Semaphore SMS could not be reached.', [
                'error' => $exception->getMessage(),
                'recipients' => count($recipients),
            ]);

            return ['sent' => false, 'status' => null, 'error' => 'Semaphore could not be reached.'];
        } catch (Throwable $exception) {
            report($exception);

            return ['sent' => false, 'status' => null, 'error' => 'Semaphore SMS failed unexpectedly.'];
        }

        if (! $this->accepted($response)) {
            $error = $this->errorFrom($response);
            Log::warning('Semaphore SMS was rejected.', [
                'status' => $response->status(),
                'recipients' => count($recipients),
                'error' => $error,
            ]);

            return ['sent' => false, 'status' => $response->status(), 'error' => $error];
        }

        return ['sent' => true, 'status' => $response->status(), 'error' => null];
    }

    public function normalizePhilippineNumber(?string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $raw) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '63') && strlen($digits) === 12) {
            return $digits;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 11) {
            return '63'.substr($digits, 1);
        }

        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            return '63'.$digits;
        }

        return null;
    }

    private function accepted(Response $response): bool
    {
        if ($response->failed()) {
            return false;
        }

        $body = $response->json();

        if (! is_array($body) || $body === [] || ! array_is_list($body)) {
            return false;
        }

        foreach ($body as $item) {
            if (! is_array($item) || empty($item['message_id'])) {
                return false;
            }

            $status = strtolower((string) ($item['status'] ?? ''));
            if (in_array($status, ['failed', 'refunded'], true)) {
                return false;
            }
        }

        return true;
    }

    private function errorFrom(Response $response): string
    {
        $body = $response->json();

        if (is_array($body)) {
            $message = collect($body)->flatten()->first(fn ($value) => is_string($value) && trim($value) !== '');

            if (is_string($message)) {
                return $message;
            }
        }

        return 'Semaphore rejected the SMS request.';
    }
}

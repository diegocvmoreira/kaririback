<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class TurnstileService
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function verify(?string $token, ?string $remoteIp = null): bool
    {
        $secret = (string) config('services.turnstile.secret');
        if ($secret === '' || trim((string) $token) === '') {
            return false;
        }

        try {
            $payload = [
                'secret' => $secret,
                'response' => trim((string) $token),
            ];
            if ($remoteIp !== null && $remoteIp !== '') {
                $payload['remoteip'] = $remoteIp;
            }

            $response = Http::asForm()
                ->connectTimeout(5)
                ->timeout(10)
                ->post((string) config('services.turnstile.siteverify_url', self::VERIFY_URL), $payload);

            $valid = $response->successful() && $response->json('success') === true;
            if (!$valid) {
                Log::notice('Turnstile validation rejected', [
                    'errors' => $response->json('error-codes', []),
                ]);
            }
            return $valid;
        } catch (\Throwable $exception) {
            Log::warning('Turnstile validation failed', ['exception' => $exception->getMessage()]);
            return false;
        }
    }
}

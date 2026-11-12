<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\TurnstileService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class VerifyTurnstile
{
    public function __construct(private readonly TurnstileService $turnstile)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->input('turnstile_token')
            ?? $request->header('X-Turnstile-Token');

        if (!$this->turnstile->verify(is_string($token) ? $token : null, $request->ip())) {
            return response()->json([
                'message' => 'Verificação antispam inválida ou expirada. Atualize o desafio e tente novamente.',
                'errors' => ['turnstile_token' => ['A verificação antispam é obrigatória.']],
            ], 422);
        }

        return $next($request);
    }
}

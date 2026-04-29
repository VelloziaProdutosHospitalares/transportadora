<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOctalogWebhookSecret
{
    /**
     * Valida o cabeçalho quando OCTALOG_WEBHOOK_SECRET está definido (valor configurado na Octalog).
     *
     * Aceita o seguro em bruto ou com prefixo Bearer.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.octalog.webhook_secret');

        if ($expected === null || $expected === '') {
            return $next($request);
        }

        $expected = (string) $expected;
        $authorization = (string) $request->header('Authorization', '');

        $valid = hash_equals($expected, $authorization)
            || hash_equals('Bearer '.$expected, $authorization);

        if (! $valid) {
            abort(Response::HTTP_UNAUTHORIZED, 'Credenciais do webhook inválidas.');
        }

        return $next($request);
    }
}

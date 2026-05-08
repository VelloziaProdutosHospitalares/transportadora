<?php

namespace App\Services;

use App\Exceptions\OctalogException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Cliente dos endpoints de configuração de webhook de tracking de pedidos (Octalog).
 *
 * @see https://integracao.octalog.com.br — POST/GET /webhook/configurancao
 */
final class OctalogPedidosWebhookService
{
    public function __construct(
        private readonly OctalogService $octalog,
    ) {}

    /**
     * POST /webhook/configurancao
     *
     * @param  array<string, mixed>  $payload
     * @return array{success: bool, data: mixed, errors: mixed, status: int}
     *
     * @throws OctalogException em falha de rede
     */
    public function configureWebhook(array $payload): array
    {
        $response = $this->requestWithTokenRetry(
            fn () => $this->octalog->integrationHttpWithToken()
                ->asJson()
                ->post("{$this->baseUrl()}/webhook/configurancao", $payload),
        );

        return $this->wrapJsonResponse($response);
    }

    /**
     * GET /webhook/configurancao
     *
     * @return array{success: bool, data: mixed, errors: mixed, status: int}
     *
     * @throws OctalogException
     */
    public function getWebhookConfiguration(): array
    {
        $response = $this->requestWithTokenRetry(
            fn () => $this->octalog->integrationHttpWithToken()
                ->get("{$this->baseUrl()}/webhook/configurancao"),
        );

        return $this->wrapJsonResponse($response);
    }

    /**
     * @param  callable(): Response  $callback
     */
    private function requestWithTokenRetry(callable $callback): Response
    {
        try {
            $response = $callback();

            if ($response->status() === 401) {
                Cache::forget(OctalogService::AUTH_CACHE_KEY);
                $response = $callback();
            }

            return $response;
        } catch (ConnectionException $e) {
            Log::error('Octalog webhook pedidos: falha de conexão', [
                'mensagem' => $e->getMessage(),
            ]);

            throw new OctalogException(
                'Falha ao conectar com a API da Octalog (webhook pedidos): '.$e->getMessage(),
                previous: $e,
            );
        }
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.octalog.url'), '/');
    }

    /**
     * @return array{success: bool, data: mixed, errors: mixed, status: int}
     */
    private function wrapJsonResponse(Response $response): array
    {
        $status = $response->status();
        $json = $response->json();

        if ($response->successful()) {
            return [
                'success' => true,
                'data' => $json,
                'errors' => [],
                'status' => $status,
            ];
        }

        return [
            'success' => false,
            'data' => [],
            'errors' => is_array($json) ? $json : ['body' => $response->body()],
            'status' => $status,
        ];
    }
}

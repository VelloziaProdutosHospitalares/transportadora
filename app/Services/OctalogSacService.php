<?php

namespace App\Services;

use App\Exceptions\OctalogException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Cliente dos endpoints SAC da Octalog (tickets e configuração de webhook de tracking).
 *
 * @see https://integracao.octalog.com.br — paths /sac/*
 */
final class OctalogSacService
{
    public function __construct(
        private readonly OctalogService $octalog,
    ) {}

    /**
     * POST /sac/webhook/configurancao
     *
     * @param  array<string, mixed>  $payload  URL, LimiteEnvio, DataInicioEnvio, Header, ContatoTecnico
     * @return array{success: bool, data: mixed, errors: mixed, status: int}
     *
     * @throws OctalogException em falha de rede
     */
    public function configureWebhook(array $payload): array
    {
        $response = $this->requestWithTokenRetry(
            fn () => $this->octalog->integrationHttpWithToken()
                ->asJson()
                ->post("{$this->baseUrl()}/sac/webhook/configurancao", $payload),
        );

        return $this->wrapJsonResponse($response);
    }

    /**
     * GET /sac/webhook/configurancao
     *
     * @return array{success: bool, data: mixed, errors: mixed, status: int}
     *
     * @throws OctalogException
     */
    public function getWebhookConfiguration(): array
    {
        $response = $this->requestWithTokenRetry(
            fn () => $this->octalog->integrationHttpWithToken()
                ->get("{$this->baseUrl()}/sac/webhook/configurancao"),
        );

        return $this->wrapJsonResponse($response);
    }

    /**
     * GET /sac/motivos
     *
     * @return array{success: bool, data: mixed, errors: mixed, status: int}
     *
     * @throws OctalogException
     */
    public function listMotivos(): array
    {
        $response = $this->requestWithTokenRetry(
            fn () => $this->octalog->integrationHttpWithToken()
                ->get("{$this->baseUrl()}/sac/motivos"),
        );

        return $this->wrapJsonResponse($response);
    }

    /**
     * POST /sac/criar-ticket
     *
     * @param  array<string, mixed>  $payload  Pedido, IDMotivo, Titulo, Descricao, ComentarioMotorista
     * @return array{success: bool, data: mixed, errors: mixed, status: int}
     *
     * @throws OctalogException
     */
    public function createTicket(array $payload): array
    {
        $response = $this->requestWithTokenRetry(
            fn () => $this->octalog->integrationHttpWithToken()
                ->asJson()
                ->post("{$this->baseUrl()}/sac/criar-ticket", $payload),
        );

        return $this->wrapJsonResponse($response);
    }

    /**
     * DELETE /sac/cancelar-ticket (corpo JSON)
     *
     * @param  array<string, mixed>  $payload  Pedido, IDMotivo, Descricao
     * @return array{success: bool, data: mixed, errors: mixed, status: int}
     *
     * @throws OctalogException
     */
    public function cancelTicket(array $payload): array
    {
        $response = $this->requestWithTokenRetry(
            fn () => $this->octalog->integrationHttpWithToken()
                ->asJson()
                ->send('DELETE', "{$this->baseUrl()}/sac/cancelar-ticket", [
                    'json' => $payload,
                ]),
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
            Log::error('Octalog SAC: falha de conexão', [
                'mensagem' => $e->getMessage(),
            ]);

            throw new OctalogException(
                'Falha ao conectar com a API da Octalog (SAC): '.$e->getMessage(),
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

<?php

namespace App\Services;

use App\DTOs\OctalogOrderData;
use App\Exceptions\OctalogException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OctalogService
{
    /** Chave de cache do token de autenticação. */
    public const AUTH_CACHE_KEY = 'octalog_auth_token';

    /** Máximo de números de pedido por chamada a `POST /pedido/listar` (contrato Octalog / OpenAPI). */
    public const MAX_LISTAR_PEDIDOS = 100;

    /** Minutos de validade do cache do token (margem de segurança antes da expiração real). */
    private const TOKEN_TTL_MINUTES = 50;

    private string $url;

    private string $authUrl;

    private string $usuario;

    private string $senha;

    public function __construct()
    {
        $this->url = rtrim((string) config('services.octalog.url'), '/');
        $this->authUrl = rtrim((string) config('services.octalog.auth_url'), '/');
        $this->usuario = (string) config('services.octalog.usuario');
        $this->senha = (string) config('services.octalog.senha');
    }

    /**
     * Envio `POST /pedido/salvar` (até **50** itens por chamada, conforme contrato Octalog).
     *
     * @param  OctalogOrderData[]  $fretes
     * @return array{success: bool, data: array, errors: array}
     *
     * @throws OctalogException quando não é possível conectar à API, autenticar, ou lista > 50 pedidos
     */
    public function sendOrders(array $fretes): array
    {
        if (count($fretes) > 50) {
            throw new OctalogException('A Octalog aceita no máximo 50 pedidos por envio (contrato POST /pedido/salvar).');
        }

        $payload = array_map(
            static fn (OctalogOrderData $frete) => $frete->toPayload(),
            $fretes,
        );

        $pedidosIds = array_column($payload, 'Pedido');

        try {
            $response = $this->postPedidos($payload);

            if ($response->status() === 401) {
                Cache::forget(self::AUTH_CACHE_KEY);
                $response = $this->postPedidos($payload);
            }
        } catch (ConnectionException $e) {
            Log::error('Octalog: falha de conexão ao enviar pedidos', [
                'pedidos' => $pedidosIds,
                'mensagem' => $e->getMessage(),
            ]);

            throw new OctalogException(
                'Falha ao conectar com a API da Octalog: '.$e->getMessage(),
                previous: $e,
            );
        }

        if ($response->successful()) {
            return [
                'success' => true,
                'data' => $response->json(),
                'errors' => [],
            ];
        }

        $errors = $response->json() ?? [];

        Log::error('Octalog: resposta de erro ao enviar pedidos', [
            'status' => $response->status(),
            'pedidos' => $pedidosIds,
            'erros' => $errors,
        ]);

        return [
            'success' => false,
            'data' => [],
            'errors' => $errors,
        ];
    }

    /**
     * Consulta até 100 pedidos por número (`POST /pedido/listar`). Corpo JSON: array de strings.
     *
     * @param  string[]  $pedidoNumeros
     * @return array{success: bool, data: array<int|string, mixed>, errors: array<int|string, mixed>}
     *
     * @throws OctalogException quando a lista excede {@see self::MAX_LISTAR_PEDIDOS} ou falha rede/auth
     */
    public function listOrders(array $pedidoNumeros): array
    {
        $payload = array_values(array_map(static fn (mixed $n) => (string) $n, $pedidoNumeros));

        if (count($payload) > self::MAX_LISTAR_PEDIDOS) {
            throw new OctalogException(
                'A Octalog aceita no máximo '.self::MAX_LISTAR_PEDIDOS.' pedidos por consulta (contrato POST /pedido/listar).',
            );
        }

        try {
            $response = $this->postListarPedidos($payload);

            if ($response->status() === 401) {
                Cache::forget(self::AUTH_CACHE_KEY);
                $response = $this->postListarPedidos($payload);
            }
        } catch (ConnectionException $e) {
            Log::error('Octalog: falha de conexão ao listar pedidos', [
                'quantidade' => count($payload),
                'mensagem' => $e->getMessage(),
            ]);

            throw new OctalogException(
                'Falha ao conectar com a API da Octalog: '.$e->getMessage(),
                previous: $e,
            );
        }

        if ($response->successful()) {
            $json = $response->json();

            return [
                'success' => true,
                'data' => is_array($json) ? $json : [],
                'errors' => [],
            ];
        }

        $errors = $response->json() ?? [];

        Log::error('Octalog: resposta de erro ao listar pedidos', [
            'status' => $response->status(),
            'quantidade_solicitada' => count($payload),
            'erros' => $errors,
        ]);

        return [
            'success' => false,
            'data' => [],
            'errors' => is_array($errors) ? $errors : [],
        ];
    }

    /**
     * Cliente HTTP com header `token` da autenticação Octalog (reutilizável em outros serviços, ex.: SAC).
     */
    public function integrationHttpWithToken(): PendingRequest
    {
        return Http::withHeaders([
            'token' => $this->resolveToken(),
            'Accept' => 'application/json',
        ]);
    }

    /** Executa a requisição de envio de pedidos usando o token resolvido. */
    private function postPedidos(array $payload): Response
    {
        return Http::withHeaders(['token' => $this->resolveToken()])
            ->asJson()
            ->post("{$this->url}/pedido/salvar", $payload);
    }

    /**
     * @param  string[]  $pedidoNumeros  Lista já normalizada (`["PED-...", ...]`).
     */
    private function postListarPedidos(array $pedidoNumeros): Response
    {
        return Http::withHeaders([
            'token' => $this->resolveToken(),
            'Accept' => 'application/json',
        ])
            ->withBody(json_encode($pedidoNumeros, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), 'application/json')
            ->post("{$this->url}/pedido/listar");
    }

    /**
     * Retorna o token do cache ou autentica na API para obter um novo.
     *
     * @throws OctalogException
     */
    private function resolveToken(): string
    {
        return Cache::remember(
            self::AUTH_CACHE_KEY,
            now()->addMinutes(self::TOKEN_TTL_MINUTES),
            fn () => $this->fetchToken(),
        );
    }

    /**
     * Autentica na API da Octalog e retorna o token JWT.
     *
     * @throws OctalogException
     */
    private function fetchToken(): string
    {
        try {
            $response = Http::post("{$this->authUrl}/autenticacao/token", [
                'usuario' => $this->usuario,
                'senha' => $this->senha,
            ]);
        } catch (ConnectionException $e) {
            Log::error('Octalog: falha de conexão na autenticação', [
                'mensagem' => $e->getMessage(),
            ]);

            throw new OctalogException(
                'Falha ao conectar com a autenticação da Octalog: '.$e->getMessage(),
                previous: $e,
            );
        }

        if (! $response->successful()) {
            Log::error('Octalog: falha na autenticação', [
                'status' => $response->status(),
            ]);

            throw new OctalogException(
                'Falha ao autenticar na API da Octalog. Status HTTP: '.$response->status(),
            );
        }

        $token = $response->json('token');

        if (blank($token)) {
            throw new OctalogException(
                'Token não encontrado na resposta de autenticação da Octalog.',
            );
        }

        return $token;
    }
}

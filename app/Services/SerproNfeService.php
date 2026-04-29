<?php

namespace App\Services;

use App\Exceptions\SerproException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SERPRO API Gateway — consulta NF-e.
 *
 * Alinhamento ao manual oficial (Área do Cliente):
 *
 * (1) Consumer Key e Consumer Secret identificam o contrato (guardar em `.env`).
 * (2) POST ao endpoint Token — gateway São Paulo: `https://gateway.apiserpro.serpro.gov.br/token`; gateway Brasília
 *     (`https://apigateway.serpro.gov.br/token`) consta deprecated no manual.
 *     Headers: `Authorization: Basic` + base64 de `Consumer Key:Consumer Secret` (sem `\n` no resultado; em PHP usar `base64_encode`, não `echo | base64` sem `-n`).
 *     `Content-Type: application/x-www-form-urlencoded`; corpo `grant_type=client_credentials` (tipo errado no token causa HTTP 415).
 * (3) JSON de retorno usa `access_token` e `expires_in`; este serviço cacheia com margem antes do TTL.
 * (4) GET na URL base contratada + `nfe/{chave}` com `Accept: application/json` e `Authorization: Bearer` apenas uma vez
 *     (alguns curls da doc repetem "Bearer Bearer" por erro; o próprio texto do manual indica só um Bearer).
 */
class SerproNfeService
{
    /** Chave estável do access_token OAuth2 no cache. */
    public const OAUTH_CACHE_KEY = 'serpro_oauth_token';

    private string $consumerKey;

    private string $consumerSecret;

    /** Base64 do Basic (key:secret) ou vazio — vem de SERPRO_BASIC_AUTH_BASE64. */
    private string $basicAuthBase64;

    private string $tokenUrl;

    private string $consultaBaseUrl;

    private int $timeoutSeconds;

    private bool $verifySsl;

    public function __construct()
    {
        $this->consumerKey = trim((string) config('services.serpro.consumer_key'));
        $this->consumerSecret = trim((string) config('services.serpro.consumer_secret'));
        $this->basicAuthBase64 = trim((string) config('services.serpro.basic_auth_base64'));
        $this->tokenUrl = rtrim((string) config('services.serpro.token_url'), '/');
        $this->consultaBaseUrl = rtrim((string) config('services.serpro.consulta_base_url'), '/');
        $this->timeoutSeconds = (int) config('services.serpro.timeout', 30);
        $this->verifySsl = (bool) config('services.serpro.verify_ssl', true);
    }

    public function isConfigured(): bool
    {
        $hasTokenAuth = $this->basicAuthBase64 !== ''
            || ($this->consumerKey !== '' && $this->consumerSecret !== '');

        return $hasTokenAuth
            && $this->tokenUrl !== ''
            && $this->consultaBaseUrl !== '';
    }

    /**
     * Obtém o access_token do cache ou renova via client_credentials.
     *
     * @throws SerproException
     */
    public function resolveAccessToken(): string
    {
        $cached = Cache::get(self::OAUTH_CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            return $this->normalizeBearerToken($cached);
        }

        return $this->fetchToken();
    }

    /**
     * Passo 2 e 3 (manual SERPRO): POST client_credentials; lê `access_token` e cacheia com TTL por `expires_in`.
     * Basic: `SERPRO_CONSUMER_KEY` + `SERPRO_CONSUMER_SECRET` (= base64 igual a concatenar key:secret e codificar) ou override `SERPRO_BASIC_AUTH_BASE64`.
     *
     * @throws SerproException
     */
    public function fetchToken(): string
    {
        try {
            $authorizationBasic = $this->authorizationBasicForToken();

            $response = $this->serproHttp()
                ->withHeaders([
                    'Authorization' => $authorizationBasic,
                ])
                ->withBody('grant_type=client_credentials', 'application/x-www-form-urlencoded')
                ->post($this->tokenUrl);

        } catch (ConnectionException $e) {
            Log::error('SERPRO: falha de conexão ao obter token OAuth2', [
                'mensagem' => $e->getMessage(),
            ]);

            throw new SerproException(
                'Não foi possível conectar ao serviço de autenticação da SERPRO.',
                previous: $e,
            );
        }

        if (! $response->successful()) {
            Log::error('SERPRO: falha HTTP ao obter token OAuth2', [
                'status' => $response->status(),
                'body_resumo' => self::truncateForLog($response->body()),
            ]);

            throw new SerproException(
                'Falha ao autenticar na SERPRO (token). Status HTTP: '.$response->status(),
            );
        }

        /** @var array<string, mixed>|null $json */
        $json = $response->json();
        if (! is_array($json)) {
            throw new SerproException('Resposta de token da SERPRO não é JSON válido.');
        }

        $accessToken = $json['access_token'] ?? null;
        if (! is_string($accessToken) || $accessToken === '') {
            throw new SerproException('Resposta de token da SERPRO sem access_token.');
        }

        $accessToken = $this->normalizeBearerToken($accessToken);

        $expiresIn = (int) ($json['expires_in'] ?? 3600);
        $ttlSeconds = max(60, $expiresIn - 120);
        Cache::put(self::OAUTH_CACHE_KEY, $accessToken, now()->addSeconds($ttlSeconds));

        return $accessToken;
    }

    /**
     * Passo 4 (manual SERPRO): GET `{base}/nfe/{chave44}` com Accept JSON e Bearer único (documentação oficial).
     * Em 401, repete-se o passo 2 (`fetchToken`), conforme recomendação de renovação quando o token expira.
     *
     * @return array<string, mixed>
     *
     * @throws SerproException
     */
    public function consultarNfePorChave(string $chave): array
    {
        if (! preg_match('/^\d{44}$/', $chave)) {
            throw new SerproException('Chave de acesso da NF-e inválida: deve conter exatamente 44 dígitos.');
        }

        $response = $this->getNfe($chave, $this->resolveAccessToken());

        if ($response->status() === 401) {
            Cache::forget(self::OAUTH_CACHE_KEY);
            $response = $this->getNfe($chave, $this->fetchToken());
        }

        if ($response->status() === 401) {
            Log::warning('SERPRO: 401 persistente na consulta NF-e após renovar token', [
                'chave_prefixo' => substr($chave, 0, 6).'…',
            ]);

            throw new SerproException('Credenciais ou token da SERPRO rejeitados ao consultar a NF-e.');
        }

        if (! $response->successful()) {
            $jsonErr = $response->json();
            $gatewayCode = is_array($jsonErr) ? ($jsonErr['code'] ?? null) : null;

            Log::error('SERPRO: erro na consulta NF-e', [
                'status' => $response->status(),
                'gateway_code' => is_scalar($gatewayCode) ? (string) $gatewayCode : null,
                'body_resumo' => self::truncateForLog($response->body()),
                'chave_prefixo' => substr($chave, 0, 6).'…',
            ]);

            $friendly = self::friendlyMessageFromGatewayBody($response->status(), $jsonErr);
            throw new SerproException($friendly);
        }

        $json = $response->json();
        if (! is_array($json)) {
            Log::error('SERPRO: resposta de consulta NF-e com JSON inválido', [
                'body_resumo' => self::truncateForLog($response->body()),
                'chave_prefixo' => substr($chave, 0, 6).'…',
            ]);

            throw new SerproException('Resposta da consulta NF-e não é JSON válido.');
        }

        return $json;
    }

    private function getNfe(string $chave44, string $accessToken): Response
    {
        $url = "{$this->consultaBaseUrl}/nfe/{$chave44}";

        try {
            // Manual SERPRO — passo 4: Accept application/json + Authorization Bearer <access_token>
            return $this->serproHttp()
                ->withToken($accessToken)
                ->acceptJson()
                ->get($url);
        } catch (ConnectionException $e) {
            Log::error('SERPRO: falha de conexão na consulta NF-e', [
                'mensagem' => $e->getMessage(),
                'chave_prefixo' => substr($chave44, 0, 6).'…',
            ]);

            throw new SerproException(
                'Não foi possível conectar à API de consulta NF-e da SERPRO.',
                previous: $e,
            );
        }
    }

    private function serproHttp(): PendingRequest
    {
        $pending = Http::timeout($this->timeoutSeconds);
        if (! $this->verifySsl) {
            $pending = $pending->withOptions(['verify' => false]);
        }

        return $pending;
    }

    /** Header Authorization exato para POST /token: prioriza SERPRO_BASIC_AUTH_BASE64. */
    private function authorizationBasicForToken(): string
    {
        if ($this->basicAuthBase64 !== '') {
            if (str_starts_with(strtolower($this->basicAuthBase64), 'basic ')) {
                return $this->basicAuthBase64;
            }

            return 'Basic '.$this->basicAuthBase64;
        }

        return 'Basic '.base64_encode($this->consumerKey.':'.$this->consumerSecret);
    }

    private function normalizeBearerToken(string $token): string
    {
        $t = trim($token);
        if (str_starts_with(strtolower($t), 'bearer ')) {
            return trim(substr($t, 7));
        }

        return $t;
    }

    private static function truncateForLog(string $body, int $max = 500): string
    {
        $body = preg_replace("/\s+/", ' ', $body) ?? $body;
        if (mb_strlen($body) <= $max) {
            return $body;
        }

        return mb_substr($body, 0, $max).'…';
    }

    /**
     * Mensagem legível a partir do JSON de erro do API Gateway SERPRO (ex.: code 900908).
     *
     * @param  array<string, mixed>|null  $json
     */
    private static function friendlyMessageFromGatewayBody(int $httpStatus, ?array $json): string
    {
        $description = is_array($json) && isset($json['description']) && is_string($json['description'])
            ? trim($json['description'])
            : '';
        $message = is_array($json) && isset($json['message']) && is_string($json['message'])
            ? trim($json['message'])
            : '';
        $code = is_array($json) && array_key_exists('code', $json)
            ? (is_string($json['code']) || is_int($json['code']) ? (string) $json['code'] : '')
            : '';

        $detail = $description !== '' ? $description : $message;

        if ($detail !== '') {
            $prefix = $code !== '' ? "[{$code}] " : '';

            return $prefix.$detail;
        }

        return 'Consulta NF-e na SERPRO falhou. Status HTTP: '.$httpStatus;
    }
}

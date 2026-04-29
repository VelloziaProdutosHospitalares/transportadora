<?php

namespace Tests\Feature;

use App\Exceptions\SerproException;
use App\Services\SerproNfeService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SerproNfeServiceTest extends TestCase
{
    private string $chave44;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(SerproNfeService::OAUTH_CACHE_KEY);

        $this->chave44 = str_repeat('5', 44);

        config([
            'services.serpro.consumer_key' => 'test-key',
            'services.serpro.consumer_secret' => 'test-secret',
            'services.serpro.basic_auth_base64' => '',
            'services.serpro.token_url' => 'https://serpro.test/oauth/token',
            'services.serpro.consulta_base_url' => 'https://serpro.test/consulta/api/v1',
            'services.serpro.timeout' => 10,
            'services.serpro.verify_ssl' => true,
        ]);
    }

    #[Test]
    public function obtem_token_e_consulta_nfe_com_sucesso(): void
    {
        $payload = [
            'NFe' => [
                'infNFe' => [
                    'ide' => ['nNF' => '789', 'serie' => '2'],
                    'total' => ['ICMSTot' => ['vNF' => '150.00']],
                ],
            ],
        ];

        Http::fake([
            'https://serpro.test/oauth/token' => Http::response([
                'access_token' => 'access-token-xyz',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
            'https://serpro.test/consulta/api/v1/nfe/*' => Http::response($payload, 200),
        ]);

        $result = (new SerproNfeService)->consultarNfePorChave($this->chave44);

        $this->assertSame($payload, $result);

        Http::assertSent(function ($request) {
            if ($request->url() !== 'https://serpro.test/oauth/token') {
                return false;
            }

            return $request->hasHeader('Authorization')
                && str_starts_with($request->header('Authorization')[0], 'Basic ')
                && $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded');
        });

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/nfe/')
                && $request->hasHeader('Authorization', 'Bearer access-token-xyz');
        });
    }

    #[Test]
    public function em_401_na_consulta_renovar_token_e_repetir_get_uma_vez(): void
    {
        Http::fake([
            'https://serpro.test/oauth/token' => Http::sequence()
                ->push([
                    'access_token' => 'token-v1',
                    'expires_in' => 3600,
                    'token_type' => 'Bearer',
                ], 200)
                ->push([
                    'access_token' => 'token-v2',
                    'expires_in' => 3600,
                    'token_type' => 'Bearer',
                ], 200),
            'https://serpro.test/consulta/api/v1/nfe/*' => Http::sequence()
                ->push([], 401)
                ->push(['ok' => true], 200),
        ]);

        $result = (new SerproNfeService)->consultarNfePorChave($this->chave44);

        $this->assertSame(['ok' => true], $result);

        Http::assertSentCount(4);
    }

    #[Test]
    public function reutiliza_token_em_cache_sem_chamar_o_endpoint_de_token(): void
    {
        Cache::put(SerproNfeService::OAUTH_CACHE_KEY, 'from-cache', now()->addHour());

        Http::fake([
            'https://serpro.test/consulta/api/v1/nfe/*' => Http::response(['a' => 1], 200),
        ]);

        (new SerproNfeService)->consultarNfePorChave($this->chave44);

        Http::assertSentCount(1);
        Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Bearer from-cache'));
    }

    #[Test]
    public function lanca_excecao_se_chave_nao_tem_44_digitos(): void
    {
        $this->expectException(SerproException::class);

        (new SerproNfeService)->consultarNfePorChave('123');
    }

    #[Test]
    public function token_usa_serpro_basic_auth_base64_quando_definido_em_vez_de_gerar_por_chave_e_segredo(): void
    {
        config([
            'services.serpro.consumer_key' => '',
            'services.serpro.consumer_secret' => '',
            'services.serpro.basic_auth_base64' => 'c3RyaWN0QmFzZTY0Q29kZQ',
        ]);

        Http::fake([
            'https://serpro.test/oauth/token' => Http::response([
                'access_token' => 'tok',
                'expires_in' => 3600,
            ], 200),
            'https://serpro.test/consulta/api/v1/nfe/*' => Http::response(['ok' => 1], 200),
        ]);

        (new SerproNfeService)->consultarNfePorChave($this->chave44);

        Http::assertSent(static function ($request): bool {
            if ($request->url() !== 'https://serpro.test/oauth/token') {
                return false;
            }

            $auth = $request->header('Authorization')[0] ?? '';

            return $auth === 'Basic c3RyaWN0QmFzZTY0Q29kZQ';
        });
    }

    #[Test]
    public function lanca_excecao_em_falha_de_conexao_no_token(): void
    {
        Http::fake([
            'https://serpro.test/oauth/token' => static function () {
                throw new ConnectionException('refused');
            },
        ]);

        $this->expectException(SerproException::class);
        $this->expectExceptionMessage('conectar');

        (new SerproNfeService)->fetchToken();
    }

    #[Test]
    public function segundo_401_apos_retry_lanca_excecao(): void
    {
        Http::fake([
            'https://serpro.test/oauth/token' => Http::response([
                'access_token' => 't',
                'expires_in' => 3600,
            ], 200),
            'https://serpro.test/consulta/api/v1/nfe/*' => Http::response([], 401),
        ]);

        $this->expectException(SerproException::class);

        (new SerproNfeService)->consultarNfePorChave($this->chave44);
    }
}

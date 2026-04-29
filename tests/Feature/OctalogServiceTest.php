<?php

namespace Tests\Feature;

use App\DTOs\OctalogOrderData;
use App\Exceptions\OctalogException;
use App\Services\OctalogService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OctalogServiceTest extends TestCase
{
    private OctalogOrderData $orderFixture;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(OctalogService::AUTH_CACHE_KEY);

        $this->orderFixture = new OctalogOrderData(
            pedido: '954595',
            idPrazoEntrega: 6,
            totalVolumes: 1,
            dataVenda: null,
            remetente: [
                'RazaoSocial' => 'NOME DA LOJA',
                'CNPJ' => '22233368000180',
            ],
            destinatario: [
                'Nome' => 'NOME DO CLIENTE',
                'Endereco' => 'RUA CRISTIANO',
                'Numero' => '0',
                'Bairro' => 'CERQUEIRA CESAR',
                'Cidade' => 'Sao Paulo',
                'CEP' => '05411001',
                'UF' => 'SP',
            ],
        );
    }

    // -------------------------------------------------------------------------
    // Happy path
    // -------------------------------------------------------------------------

    #[Test]
    public function envia_pedidos_e_retorna_sucesso_no_happy_path(): void
    {
        Http::fake([
            '*/autenticacao/token' => Http::response(['token' => 'fake-jwt-token'], 200),
            '*/pedido/salvar' => Http::response([
                [
                    'ID' => 902,
                    'Pedido' => '954595',
                    'IDStatus' => 1,
                    'Status' => 'Integração Recebida',
                    'DataEvento' => '2025-01-24T16:42:24Z',
                ],
            ], 200),
        ]);

        $result = (new OctalogService)->sendOrders([$this->orderFixture]);

        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errors']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals('954595', $result['data'][0]['Pedido']);
        $this->assertEquals(1, $result['data'][0]['IDStatus']);

        Http::assertSent(fn ($req) => str_contains($req->url(), '/autenticacao/token'));
        Http::assertSent(function ($req) {
            return str_contains($req->url(), '/pedido/salvar')
                && $req->hasHeader('token', 'fake-jwt-token')
                && $req->method() === 'POST';
        });
    }

    #[Test]
    public function reutiliza_token_em_cache_sem_chamar_autenticacao_novamente(): void
    {
        Cache::put(OctalogService::AUTH_CACHE_KEY, 'cached-token', now()->addMinutes(30));

        Http::fake([
            '*/pedido/salvar' => Http::response([
                ['ID' => 1, 'Pedido' => '954595', 'IDStatus' => 1, 'Status' => 'OK', 'DataEvento' => '2025-01-24T16:42:24Z'],
            ], 200),
        ]);

        (new OctalogService)->sendOrders([$this->orderFixture]);

        // Apenas uma requisição deve ter sido feita (pedido/salvar), sem chamar o endpoint de auth
        Http::assertSentCount(1);
        Http::assertSent(fn ($req) => str_contains($req->url(), '/pedido/salvar')
            && $req->hasHeader('token', 'cached-token'));
    }

    // -------------------------------------------------------------------------
    // Erros de envio de pedidos
    // -------------------------------------------------------------------------

    #[Test]
    public function trata_resposta_400_da_api_sem_lancar_excecao(): void
    {
        Http::fake([
            '*/autenticacao/token' => Http::response(['token' => 'fake-jwt-token'], 200),
            '*/pedido/salvar' => Http::response([
                ['Pedido' => '954595', 'Erros' => 'CNPJ 22233368000180 não está cadastrado.'],
            ], 400),
        ]);

        $result = (new OctalogService)->sendOrders([$this->orderFixture]);

        $this->assertFalse($result['success']);
        $this->assertEmpty($result['data']);
        $this->assertNotEmpty($result['errors']);
        $this->assertEquals('954595', $result['errors'][0]['Pedido']);
        $this->assertStringContainsString('não está cadastrado', $result['errors'][0]['Erros']);
    }

    #[Test]
    public function trata_resposta_500_da_api_sem_lancar_excecao(): void
    {
        Http::fake([
            '*/autenticacao/token' => Http::response(['token' => 'fake-jwt-token'], 200),
            '*/pedido/salvar' => Http::response([], 500),
        ]);

        $result = (new OctalogService)->sendOrders([$this->orderFixture]);

        $this->assertFalse($result['success']);
        $this->assertEmpty($result['data']);
    }

    #[Test]
    public function lanca_octalog_exception_em_falha_de_conexao_ao_enviar(): void
    {
        Http::fake([
            '*/autenticacao/token' => Http::response(['token' => 'fake-jwt-token'], 200),
            '*/pedido/salvar' => static function () {
                throw new ConnectionException('Connection refused');
            },
        ]);

        $this->expectException(OctalogException::class);
        $this->expectExceptionMessageMatches('/Falha ao conectar/');

        (new OctalogService)->sendOrders([$this->orderFixture]);
    }

    #[Test]
    public function renova_token_automaticamente_ao_receber_401(): void
    {
        Http::fake([
            '*/autenticacao/token' => Http::response(['token' => 'novo-token'], 200),
            '*/pedido/salvar' => Http::sequence()
                ->push([], 401)
                ->push([
                    ['ID' => 10, 'Pedido' => '954595', 'IDStatus' => 1, 'Status' => 'OK', 'DataEvento' => '2025-01-24T16:42:24Z'],
                ], 200),
        ]);

        Cache::put(OctalogService::AUTH_CACHE_KEY, 'token-expirado', now()->addMinutes(1));

        $result = (new OctalogService)->sendOrders([$this->orderFixture]);

        $this->assertTrue($result['success']);

        // Deve ter buscado novo token e re-enviado os pedidos
        Http::assertSentCount(3); // 1x auth + 2x pedido/salvar
    }

    // -------------------------------------------------------------------------
    // Erros de autenticação
    // -------------------------------------------------------------------------

    #[Test]
    public function lanca_octalog_exception_quando_autenticacao_falha_com_401(): void
    {
        Http::fake([
            '*/autenticacao/token' => Http::response([], 401),
        ]);

        $this->expectException(OctalogException::class);
        $this->expectExceptionMessageMatches('/Falha ao autenticar/');

        (new OctalogService)->sendOrders([$this->orderFixture]);
    }

    #[Test]
    public function lanca_octalog_exception_quando_resposta_de_auth_nao_contem_token(): void
    {
        Http::fake([
            '*/autenticacao/token' => Http::response(['outro_campo' => 'valor'], 200),
        ]);

        $this->expectException(OctalogException::class);
        $this->expectExceptionMessageMatches('/Token não encontrado/');

        (new OctalogService)->sendOrders([$this->orderFixture]);
    }

    #[Test]
    public function lanca_octalog_exception_em_falha_de_conexao_na_autenticacao(): void
    {
        Http::fake(static function () {
            throw new ConnectionException('DNS lookup failed');
        });

        $this->expectException(OctalogException::class);
        $this->expectExceptionMessageMatches('/autenticação/');

        (new OctalogService)->sendOrders([$this->orderFixture]);
    }

    // -------------------------------------------------------------------------
    // Serialização do payload
    // -------------------------------------------------------------------------

    #[Test]
    public function nao_inclui_dados_fiscais_e_dimensoes_quando_ausentes(): void
    {
        Http::fake([
            '*/autenticacao/token' => Http::response(['token' => 'fake-jwt-token'], 200),
            '*/pedido/salvar' => Http::response([
                ['ID' => 1, 'Pedido' => '954595', 'IDStatus' => 1, 'Status' => 'OK', 'DataEvento' => '2025-01-24T16:42:24Z'],
            ], 200),
        ]);

        (new OctalogService)->sendOrders([$this->orderFixture]);

        Http::assertSent(function ($request) {
            $pedido = ($request->data())[0] ?? [];

            return ! array_key_exists('DadosFiscais', $pedido)
                && ! array_key_exists('Dimensoes', $pedido);
        });
    }

    #[Test]
    public function inclui_dados_fiscais_e_dimensoes_quando_presentes(): void
    {
        $orderComCte = new OctalogOrderData(
            pedido: '999001',
            idPrazoEntrega: 6,
            totalVolumes: 1,
            dadosFiscais: [
                'ChaveNotaFiscal' => null,
                'NumeroNotaFiscal' => '001',
                'SerieNotaFiscal' => '1',
                'ValorTotalReais' => 424.91,
            ],
            dimensoes: [
                [
                    'PesoKilos' => 2.36,
                    'Altura' => 7.3,
                    'Largura' => 45.4,
                    'Comprimento' => 30.3,
                    'Cubagem' => 1.0,
                    'ValorTotalReais' => 424.91,
                ],
            ],
        );

        Http::fake([
            '*/autenticacao/token' => Http::response(['token' => 'fake-jwt-token'], 200),
            '*/pedido/salvar' => Http::response([
                ['ID' => 2, 'Pedido' => '999001', 'IDStatus' => 1, 'Status' => 'OK', 'DataEvento' => '2025-01-24T16:42:24Z'],
            ], 200),
        ]);

        (new OctalogService)->sendOrders([$orderComCte]);

        Http::assertSent(function ($request) {
            $pedido = ($request->data())[0] ?? [];

            return array_key_exists('DadosFiscais', $pedido)
                && array_key_exists('Dimensoes', $pedido);
        });
    }

    #[Test]
    public function lista_pedidos_posta_json_array_em_pedido_listar(): void
    {
        Http::fake([
            '*/autenticacao/token' => Http::response(['token' => 'fake-jwt-token'], 200),
            '*/pedido/listar' => Http::response([
                [
                    'ID' => 1,
                    'Pedido' => '954595',
                    'IDStatus' => 1,
                    'Status' => 'Integração Recebida',
                    'DataEvento' => '2025-01-24T16:42:24Z',
                ],
            ], 200),
        ]);

        $result = (new OctalogService)->listOrders(['954595', '240610']);

        $this->assertTrue($result['success']);
        $this->assertSame('954595', $result['data'][0]['Pedido']);

        Http::assertSent(function ($request) {
            if (! str_contains((string) $request->url(), '/pedido/listar')) {
                return false;
            }

            $decoded = json_decode((string) $request->body(), true);

            return $request->method() === 'POST'
                && $request->hasHeader('token', 'fake-jwt-token')
                && is_array($decoded)
                && $decoded === ['954595', '240610'];
        });
    }

    #[Test]
    public function listagem_rejeita_lista_com_mais_de_100_pedidos(): void
    {
        $numeros = array_map(static fn (int $i) => 'P-'.$i, range(0, 100));

        $this->expectException(OctalogException::class);
        $this->expectExceptionMessageMatches('/100 pedidos/');

        (new OctalogService)->listOrders($numeros);
    }

    #[Test]
    public function envio_rejeita_lista_com_mais_de_50_pedidos(): void
    {
        $destMin = [
            'Nome' => 'NOME DO CLIENTE',
            'Endereco' => 'RUA CRISTIANO',
            'Numero' => '0',
            'Bairro' => 'CERQUEIRA CESAR',
            'Cidade' => 'Sao Paulo',
            'CEP' => '05411001',
            'UF' => 'SP',
        ];
        $remetenteMin = [
            'RazaoSocial' => 'NOME DA LOJA',
            'CNPJ' => '22233368000180',
        ];

        $lista = [];
        for ($i = 0; $i < 51; $i++) {
            $lista[] = new OctalogOrderData(
                pedido: 'PED-'.$i,
                idPrazoEntrega: 6,
                totalVolumes: 1,
                dataVenda: null,
                dadosFiscais: null,
                dimensoes: null,
                remetente: $remetenteMin,
                destinatario: $destMin,
            );
        }

        $this->expectException(OctalogException::class);
        $this->expectExceptionMessageMatches('/50 pedidos/');

        (new OctalogService)->sendOrders($lista);
    }
}

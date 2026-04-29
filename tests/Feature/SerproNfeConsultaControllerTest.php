<?php

namespace Tests\Feature;

use App\Exceptions\SerproException;
use App\Services\SerproNfeService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SerproNfeConsultaControllerTest extends TestCase
{
    private string $chave44;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(SerproNfeService::OAUTH_CACHE_KEY);

        $this->chave44 = str_repeat('7', 44);
    }

    #[Test]
    public function retorna_json_quando_integracao_nao_esta_configurada(): void
    {
        config([
            'services.serpro.consumer_key' => '',
            'services.serpro.consumer_secret' => '',
            'services.serpro.token_url' => '',
            'services.serpro.consulta_base_url' => '',
        ]);

        $response = $this->postJson(route('pedidos.consulta-nfe-serpro'), [
            'chave_nf' => $this->chave44,
        ]);

        $response->assertOk()
            ->assertJson([
                'ok' => false,
                'message' => 'Integração SERPRO não configurada.',
            ]);
    }

    #[Test]
    public function valida_chave_com_44_digitos(): void
    {
        config([
            'services.serpro.consumer_key' => 'k',
            'services.serpro.consumer_secret' => 's',
            'services.serpro.token_url' => 'https://serpro.test/token',
            'services.serpro.consulta_base_url' => 'https://serpro.test/api',
        ]);

        $response = $this->postJson(route('pedidos.consulta-nfe-serpro'), [
            'chave_nf' => '123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['chave_nf']);
    }

    #[Test]
    public function retorna_campos_para_prefill_quando_consulta_ok(): void
    {
        config([
            'services.serpro.consumer_key' => 'k',
            'services.serpro.consumer_secret' => 's',
            'services.serpro.token_url' => 'https://serpro.test/token',
            'services.serpro.consulta_base_url' => 'https://serpro.test/api',
        ]);

        Http::fake([
            'https://serpro.test/token' => Http::response([
                'access_token' => 'tok',
                'expires_in' => 3600,
            ], 200),
            'https://serpro.test/api/nfe/*' => Http::response([
                'NFe' => [
                    'infNFe' => [
                        'ide' => ['nNF' => '99', 'serie' => '3'],
                        'total' => ['ICMSTot' => ['vNF' => '10.5']],
                        'dest' => [
                            'xNome' => 'Fulano',
                            'enderDest' => [
                                'xLgr' => 'Rua X',
                                'nro' => '1',
                                'xBairro' => 'Centro',
                                'xMun' => 'Curitiba',
                                'CEP' => '80000000',
                                'UF' => 'PR',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->postJson(route('pedidos.consulta-nfe-serpro'), [
            'chave_nf' => $this->chave44,
        ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.numero_nf', '99')
            ->assertJsonPath('data.serie_nf', '3')
            ->assertJsonPath('data.destinatario_nome', 'Fulano')
            ->assertJsonPath('data.destinatario_cidade', 'Curitiba');
    }

    #[Test]
    public function retorna_prefill_quando_api_envia_nfe_proc_como_na_doc_serpro(): void
    {
        config([
            'services.serpro.consumer_key' => 'k',
            'services.serpro.consumer_secret' => 's',
            'services.serpro.token_url' => 'https://serpro.test/token',
            'services.serpro.consulta_base_url' => 'https://serpro.test/api',
        ]);

        Http::fake([
            'https://serpro.test/token' => Http::response([
                'access_token' => 'tok',
                'expires_in' => 3600,
            ], 200),
            'https://serpro.test/api/nfe/*' => Http::response([
                'nfeProc' => [
                    'NFe' => [
                        'infNFe' => [
                            'ide' => ['nNF' => '88', 'serie' => '1'],
                            'total' => ['ICMSTot' => ['vNF' => '20']],
                            'dest' => [
                                'xNome' => 'Beltrana',
                                'enderDest' => [
                                    'xLgr' => 'Av Y',
                                    'nro' => '2',
                                    'xBairro' => 'Batel',
                                    'xMun' => 'Curitiba',
                                    'CEP' => '80420000',
                                    'UF' => 'PR',
                                    'fone' => 4133334444,
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->postJson(route('pedidos.consulta-nfe-serpro'), [
            'chave_nf' => $this->chave44,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.numero_nf', '88')
            ->assertJsonPath('data.destinatario_nome', 'Beltrana')
            ->assertJsonPath('data.destinatario_telefone', '4133334444');
    }

    #[Test]
    public function retorna_422_quando_servico_lanca_serpro_exception(): void
    {
        config([
            'services.serpro.consumer_key' => 'k',
            'services.serpro.consumer_secret' => 's',
            'services.serpro.token_url' => 'https://serpro.test/token',
            'services.serpro.consulta_base_url' => 'https://serpro.test/api',
        ]);

        $this->mock(SerproNfeService::class, function ($mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturn(true);
            $mock->shouldReceive('consultarNfePorChave')->once()->andThrow(
                new SerproException('Falha na consulta (rede ou API).'),
            );
        });

        $response = $this->postJson(route('pedidos.consulta-nfe-serpro'), [
            'chave_nf' => $this->chave44,
        ]);

        $response->assertUnprocessable()
            ->assertJson([
                'ok' => false,
                'message' => 'Falha na consulta (rede ou API).',
            ]);
    }

    #[Test]
    public function retorna_json_500_generico_para_excecao_inesperada(): void
    {
        config([
            'services.serpro.consumer_key' => 'k',
            'services.serpro.consumer_secret' => 's',
            'services.serpro.token_url' => 'https://serpro.test/token',
            'services.serpro.consulta_base_url' => 'https://serpro.test/api',
        ]);

        $this->mock(SerproNfeService::class, function ($mock): void {
            $mock->shouldReceive('isConfigured')->once()->andReturn(true);
            $mock->shouldReceive('consultarNfePorChave')->once()->andThrow(
                new \RuntimeException('detalhe interno'),
            );
        });

        $response = $this->postJson(route('pedidos.consulta-nfe-serpro'), [
            'chave_nf' => $this->chave44,
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'ok' => false,
                'message' => 'Não foi possível concluir a consulta. Tente novamente em instantes.',
            ]);
    }
}

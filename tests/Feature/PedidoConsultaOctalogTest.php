<?php

namespace Tests\Feature;

use App\Services\OctalogService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PedidoConsultaOctalogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget(OctalogService::AUTH_CACHE_KEY);
    }

    #[Test]
    public function exibe_formulario_de_consulta_octalog(): void
    {
        $this->get(route('pedidos.consulta-octalog.create'))
            ->assertOk()
            ->assertSee('Consultar status na Octalog', false);
    }

    #[Test]
    public function valida_lista_vazia_de_pedidos(): void
    {
        $this->post(route('pedidos.consulta-octalog.store'), [
            'lista_pedidos' => "  \n \t  ",
        ])->assertSessionHasErrors();
    }

    #[Test]
    public function valida_limite_de_100_numeros_distintos_na_consulta(): void
    {
        $linhas = [];
        for ($i = 0; $i < 101; $i++) {
            $linhas[] = 'PED-'.$i;
        }

        $this->post(route('pedidos.consulta-octalog.store'), [
            'lista_pedidos' => implode("\n", $linhas),
        ])->assertSessionHasErrors(['numeros']);
    }

    #[Test]
    public function exibe_resultado_quando_api_octalog_responde_ok(): void
    {
        Http::fake([
            '*/autenticacao/token' => Http::response(['token' => 'fake-jwt-token'], 200),
            '*/pedido/listar' => Http::response([
                [
                    'ID' => 902,
                    'Pedido' => '954595',
                    'IDStatus' => 1,
                    'Status' => 'Integração Recebida',
                    'DataEvento' => '2025-01-24T16:42:24Z',
                ],
            ], 200),
        ]);

        $response = $this->post(route('pedidos.consulta-octalog.store'), [
            'lista_pedidos' => "954595\n954595",
        ]);

        $response->assertOk();
        $response->assertSee('954595', false);
        $response->assertSee('Integração Recebida', false);
    }

    #[Test]
    public function exibe_mensagem_quando_api_octalog_retorna_lista_vazia(): void
    {
        Http::fake([
            '*/autenticacao/token' => Http::response(['token' => 'fake-jwt-token'], 200),
            '*/pedido/listar' => Http::response([], 200),
        ]);

        $response = $this->post(route('pedidos.consulta-octalog.store'), [
            'lista_pedidos' => 'PED-001',
        ]);

        $response->assertOk();
        $response->assertSee('Nenhum dado retornado pela Octalog', false);
    }

    #[Test]
    public function redireciona_com_erro_quando_api_octalog_rejeita(): void
    {
        Http::fake([
            '*/autenticacao/token' => Http::response(['token' => 'fake-jwt-token'], 200),
            '*/pedido/listar' => Http::response(['erro' => 'falha'], 400),
        ]);

        $this->post(route('pedidos.consulta-octalog.store'), [
            'lista_pedidos' => 'PED-001',
        ])
            ->assertRedirect(route('pedidos.consulta-octalog.create'))
            ->assertSessionHas('error');
    }
}

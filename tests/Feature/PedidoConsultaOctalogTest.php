<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Pedido;
use App\Services\OctalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PedidoConsultaOctalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget(OctalogService::AUTH_CACHE_KEY);
    }

    private function empresa(): Company
    {
        return Company::query()->create([
            'legal_name' => 'Empresa Teste LTDA',
            'trade_name' => 'Empresa Teste',
            'cnpj' => '11222333000181',
            'phone' => '(11) 99999-9999',
            'email' => 'contato@empresa.com',
            'postal_code' => '01310-100',
            'street' => 'Av. Paulista',
            'number' => '1000',
            'district' => 'Bela Vista',
            'city' => 'São Paulo',
            'state' => 'SP',
        ]);
    }

    #[Test]
    public function exibe_formulario_de_consulta_octalog(): void
    {
        $company = $this->empresa();

        $this->get(route('empresas.consulta_octalog.create', $company))
            ->assertOk()
            ->assertSee('Consultar status na Octalog', false);
    }

    #[Test]
    public function valida_lista_vazia_de_pedidos(): void
    {
        $company = $this->empresa();

        $this->post(route('empresas.consulta_octalog.store', $company), [
            'lista_pedidos' => "  \n \t  ",
        ])->assertSessionHasErrors();
    }

    #[Test]
    public function valida_limite_de_100_numeros_distintos_na_consulta(): void
    {
        $company = $this->empresa();

        $linhas = [];
        for ($i = 0; $i < 101; $i++) {
            Pedido::query()->create([
                'company_id' => $company->id,
                'numero_pedido' => 'PED-'.$i,
                'numero_nf' => '1',
                'serie_nf' => '1',
                'valor_total' => '10.00',
                'total_volumes' => 1,
                'id_prazo_entrega' => 6,
                'status' => 'enviado',
            ]);
            $linhas[] = 'PED-'.$i;
        }

        $this->post(route('empresas.consulta_octalog.store', $company), [
            'lista_pedidos' => implode("\n", $linhas),
        ])->assertSessionHasErrors(['numeros']);
    }

    #[Test]
    public function exibe_resultado_quando_api_octalog_responde_ok(): void
    {
        $company = $this->empresa();

        Pedido::query()->create([
            'company_id' => $company->id,
            'numero_pedido' => '954595',
            'numero_nf' => '1',
            'serie_nf' => '1',
            'valor_total' => '10.00',
            'total_volumes' => 1,
            'id_prazo_entrega' => 6,
            'status' => 'enviado',
        ]);

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

        $response = $this->post(route('empresas.consulta_octalog.store', $company), [
            'lista_pedidos' => "954595\n954595",
        ]);

        $response->assertOk();
        $response->assertSee('954595', false);
        $response->assertSee('Integração Recebida', false);
    }

    #[Test]
    public function exibe_mensagem_quando_api_octalog_retorna_lista_vazia(): void
    {
        $company = $this->empresa();

        Pedido::query()->create([
            'company_id' => $company->id,
            'numero_pedido' => 'PED-001',
            'numero_nf' => '1',
            'serie_nf' => '1',
            'valor_total' => '10.00',
            'total_volumes' => 1,
            'id_prazo_entrega' => 6,
            'status' => 'enviado',
        ]);

        Http::fake([
            '*/autenticacao/token' => Http::response(['token' => 'fake-jwt-token'], 200),
            '*/pedido/listar' => Http::response([], 200),
        ]);

        $response = $this->post(route('empresas.consulta_octalog.store', $company), [
            'lista_pedidos' => 'PED-001',
        ]);

        $response->assertOk();
        $response->assertSee('Nenhum dado retornado pela Octalog', false);
    }

    #[Test]
    public function redireciona_com_erro_quando_api_octalog_rejeita(): void
    {
        $company = $this->empresa();

        Pedido::query()->create([
            'company_id' => $company->id,
            'numero_pedido' => 'PED-001',
            'numero_nf' => '1',
            'serie_nf' => '1',
            'valor_total' => '10.00',
            'total_volumes' => 1,
            'id_prazo_entrega' => 6,
            'status' => 'enviado',
        ]);

        Http::fake([
            '*/autenticacao/token' => Http::response(['token' => 'fake-jwt-token'], 200),
            '*/pedido/listar' => Http::response(['erro' => 'falha'], 400),
        ]);

        $this->post(route('empresas.consulta_octalog.store', $company), [
            'lista_pedidos' => 'PED-001',
        ])
            ->assertRedirect(route('empresas.consulta_octalog.create', $company))
            ->assertSessionHas('error');
    }

    #[Test]
    public function redireciona_quando_numero_nao_pertence_a_empresa(): void
    {
        $company = $this->empresa();

        $this->post(route('empresas.consulta_octalog.store', $company), [
            'lista_pedidos' => 'PED-SEM-CADASTRO',
        ])
            ->assertRedirect(route('empresas.consulta_octalog.create', $company))
            ->assertSessionHas('error');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Pedido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmpresaPedidosScopeTest extends TestCase
{
    use RefreshDatabase;

    private function empresa(string $tradeName): Company
    {
        return Company::query()->create([
            'legal_name' => $tradeName.' LTDA',
            'trade_name' => $tradeName,
            'cnpj' => '11222333000181',
            'phone' => '(11) 99999-9999',
            'email' => strtolower(preg_replace('/\s+/', '', $tradeName)).'@scope.test',
            'postal_code' => '01310-100',
            'street' => 'Av. Paulista',
            'number' => '1000',
            'district' => 'Bela Vista',
            'city' => 'São Paulo',
            'state' => 'SP',
        ]);
    }

    #[Test]
    public function index_lista_apenas_pedidos_da_empresa(): void
    {
        $alpha = $this->empresa('Empresa Alfa');
        $beta = $this->empresa('Empresa Beta');

        Pedido::query()->create([
            'company_id' => $alpha->id,
            'numero_pedido' => 'PED-ALPHA-001',
            'numero_nf' => '1',
            'serie_nf' => '1',
            'valor_total' => '10.00',
            'total_volumes' => 1,
            'id_prazo_entrega' => 6,
            'status' => 'enviado',
        ]);

        Pedido::query()->create([
            'company_id' => $beta->id,
            'numero_pedido' => 'PED-BETA-999',
            'numero_nf' => '2',
            'serie_nf' => '1',
            'valor_total' => '20.00',
            'total_volumes' => 1,
            'id_prazo_entrega' => 6,
            'status' => 'enviado',
        ]);

        $html = $this->get(route('empresas.pedidos.index', $alpha))->assertOk()->getContent();
        $this->assertStringContainsString('PED-ALPHA-001', (string) $html);
        $this->assertStringNotContainsString('PED-BETA-999', (string) $html);
    }

    #[Test]
    public function show_pedido_de_outra_empresa_retorna_404(): void
    {
        $alpha = $this->empresa('Empresa Alfa');
        $beta = $this->empresa('Empresa Beta');

        $pedidoBeta = Pedido::query()->create([
            'company_id' => $beta->id,
            'numero_pedido' => 'PED-ISOLADO',
            'numero_nf' => '1',
            'serie_nf' => '1',
            'valor_total' => '30.00',
            'total_volumes' => 1,
            'id_prazo_entrega' => 6,
            'status' => 'enviado',
        ]);

        $this->get(route('empresas.pedidos.show', [$alpha, $pedidoBeta]))
            ->assertNotFound();
    }
}

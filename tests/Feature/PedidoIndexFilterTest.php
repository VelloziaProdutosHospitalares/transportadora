<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Pedido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PedidoIndexFilterTest extends TestCase
{
    use RefreshDatabase;

    private function empresa(): Company
    {
        return Company::query()->create([
            'legal_name' => 'Filtros Pedido LTDA',
            'trade_name' => 'Filtros Pedido',
            'cnpj' => '11222333000181',
            'phone' => '(11) 99999-9999',
            'email' => 'filtros@pedidos.test',
            'postal_code' => '01310-100',
            'street' => 'Av. Paulista',
            'number' => '1000',
            'district' => 'Bela Vista',
            'city' => 'São Paulo',
            'state' => 'SP',
        ]);
    }

    #[Test]
    public function filtra_pedidos_apenas_do_status_escolhido(): void
    {
        $company = $this->empresa();

        Pedido::query()->create([
            'company_id' => $company->id,
            'numero_pedido' => 'PED-F-ST-ENV',
            'numero_nf' => '10',
            'serie_nf' => '1',
            'valor_total' => '1.00',
            'total_volumes' => 1,
            'id_prazo_entrega' => 6,
            'status' => 'enviado',
        ]);

        Pedido::query()->create([
            'company_id' => $company->id,
            'numero_pedido' => 'PED-F-ST-PEN',
            'numero_nf' => '11',
            'serie_nf' => '1',
            'valor_total' => '2.00',
            'total_volumes' => 1,
            'id_prazo_entrega' => 6,
            'status' => 'pendente',
        ]);

        $response = $this->get(route('empresas.pedidos.index', [
            'company' => $company,
            'status' => 'pendente',
        ]));

        $response->assertOk();
        $html = (string) $response->getContent();
        $this->assertStringContainsString('PED-F-ST-PEN', $html);
        $this->assertStringNotContainsString('PED-F-ST-ENV', $html);
    }

    #[Test]
    public function busca_retorna_pedido_por_numero_nf_ou_pedido(): void
    {
        $company = $this->empresa();

        Pedido::query()->create([
            'company_id' => $company->id,
            'numero_pedido' => 'PED-F-BUSCA-X',
            'numero_nf' => '8844',
            'serie_nf' => '2',
            'valor_total' => '99.90',
            'total_volumes' => 1,
            'id_prazo_entrega' => 6,
            'status' => 'enviado',
        ]);

        Pedido::query()->create([
            'company_id' => $company->id,
            'numero_pedido' => 'PED-F-OUTRO',
            'numero_nf' => '1',
            'serie_nf' => '1',
            'valor_total' => '10.00',
            'total_volumes' => 1,
            'id_prazo_entrega' => 6,
            'status' => 'enviado',
        ]);

        $r1 = $this->get(route('empresas.pedidos.index', [
            'company' => $company,
            'search' => '8844',
        ]));
        $r1->assertOk();
        $h1 = (string) $r1->getContent();
        $this->assertStringContainsString('PED-F-BUSCA-X', $h1);
        $this->assertStringNotContainsString('PED-F-OUTRO', $h1);

        $r2 = $this->get(route('empresas.pedidos.index', [
            'company' => $company,
            'search' => 'BUSCA-X',
        ]));
        $r2->assertOk();
        $this->assertStringContainsString('PED-F-BUSCA-X', (string) $r2->getContent());
    }

    #[Test]
    public function busca_usa_digitos_na_chave_nf(): void
    {
        $company = $this->empresa();

        Pedido::query()->create([
            'company_id' => $company->id,
            'numero_pedido' => 'PED-F-CHAVE',
            'chave_nf' => '35200114306000150550070000009991234567890123',
            'numero_nf' => '999',
            'serie_nf' => '1',
            'valor_total' => '10.00',
            'total_volumes' => 1,
            'id_prazo_entrega' => 6,
            'status' => 'enviado',
        ]);

        $response = $this->get(route('empresas.pedidos.index', [
            'company' => $company,
            'search' => '9991234567890',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('PED-F-CHAVE', (string) $response->getContent());
    }

    #[Test]
    public function bloqueia_data_final_anterior_a_inicial(): void
    {
        $company = $this->empresa();

        $response = $this->get(route('empresas.pedidos.index', [
            'company' => $company,
            'created_from' => '2026-05-15',
            'created_to' => '2026-05-01',
        ]));

        $response->assertSessionHasErrors('created_to');
    }

    #[Test]
    public function formulario_mantém_valores_dos_parametros_get(): void
    {
        $company = $this->empresa();

        $response = $this->get(route('empresas.pedidos.index', [
            'company' => $company,
            'status' => 'erro',
            'search' => 'minha-busca-teste',
            'prazo_entrega' => '15',
            'ordenacao' => 'oldest',
            'per_page' => '25',
            'created_from' => '2026-01-10',
            'created_to' => '2026-06-01',
        ]));

        $response->assertOk();
        $html = (string) $response->getContent();
        $this->assertStringContainsString('name="search"', $html);
        $this->assertStringContainsString('minha-busca-teste', $html);
        $this->assertStringContainsString('<option value="25"', $html);
        $this->assertStringContainsString('value="2026-01-10"', $html);
        $this->assertStringContainsString('value="2026-06-01"', $html);
        $this->assertStringMatchesRegularExpression('/<option[^>]+value=["\']erro["\'][^>]*selected/si', $html);
    }
}

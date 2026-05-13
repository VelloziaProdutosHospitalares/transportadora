<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Pedido;
use App\Services\OctalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PedidoResendOctalogTest extends TestCase
{
    use RefreshDatabase;

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

    /**
     * @return array{Company, Pedido}
     */
    private function pedidoParaReenvio(string $status): array
    {
        $company = $this->empresa();
        $pedido = Pedido::query()->create([
            'company_id' => $company->id,
            'numero_pedido' => 'PED-20260513-00032',
            'chave_nf' => '12345678901234567890123456789012345678901234',
            'numero_nf' => '1',
            'serie_nf' => '1',
            'valor_total' => '100.00',
            'total_volumes' => 1,
            'id_prazo_entrega' => 6,
            'status' => $status,
            'destinatario_snapshot' => [
                'recipient_name' => 'Maria Silva',
                'document' => null,
                'phone' => '(21) 97777-6666',
                'email' => null,
                'postal_code' => '22450000',
                'street' => 'Rua A',
                'number' => '10',
                'complement' => '',
                'district' => 'Centro',
                'city' => 'Rio de Janeiro',
                'state' => 'RJ',
                'volume_of' => 1,
                'notes' => '',
                'label_width_mm' => 100,
                'label_height_mm' => 148,
                'show_qr_code' => false,
                'tracking_code' => null,
            ],
        ]);

        return [$company, $pedido];
    }

    public function test_reenvio_com_sucesso_atualiza_pedido(): void
    {
        [$company, $pedido] = $this->pedidoParaReenvio('erro');

        $this->mock(OctalogService::class, function ($mock) {
            $mock->shouldReceive('sendOrders')
                ->once()
                ->andReturn([
                    'success' => true,
                    'data' => [['UrlEtiqueta' => 'https://exemplo.test/etiq.pdf']],
                ]);
        });

        $response = $this->post(route('empresas.pedidos.resend_octalog', [$company, $pedido]));

        $response->assertRedirect(route('empresas.pedidos.show', [$company, $pedido]));
        $response->assertSessionHas('success');

        $pedido->refresh();
        $this->assertSame('enviado', $pedido->status);
        $this->assertSame('https://exemplo.test/etiq.pdf', $pedido->url_etiqueta);
    }

    public function test_reenvio_sem_snapshot_retorna_erro(): void
    {
        $company = $this->empresa();
        $pedido = Pedido::query()->create([
            'company_id' => $company->id,
            'numero_pedido' => 'PED-SEM-SNAP',
            'numero_nf' => '1',
            'serie_nf' => '1',
            'valor_total' => '50.00',
            'total_volumes' => 1,
            'id_prazo_entrega' => 6,
            'status' => 'erro',
            'destinatario_snapshot' => null,
        ]);

        $response = $this->post(route('empresas.pedidos.resend_octalog', [$company, $pedido]));

        $response->assertRedirect(route('empresas.pedidos.show', [$company, $pedido]));
        $response->assertSessionHas('error');
        $pedido->refresh();
        $this->assertSame('erro', $pedido->status);
    }

    public function test_reenvio_em_status_pendente_nao_e_permitido(): void
    {
        [$company, $pedido] = $this->pedidoParaReenvio('pendente');

        $response = $this->post(route('empresas.pedidos.resend_octalog', [$company, $pedido]));

        $response->assertRedirect(route('empresas.pedidos.show', [$company, $pedido]));
        $response->assertSessionHas('error');
    }
}

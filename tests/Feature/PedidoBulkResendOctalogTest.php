<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Pedido;
use App\Services\OctalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PedidoBulkResendOctalogTest extends TestCase
{
    use RefreshDatabase;

    private function empresa(): Company
    {
        return Company::query()->create([
            'legal_name' => 'Empresa Bulk LTDA',
            'trade_name' => 'Empresa Bulk',
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

    private function snapshotCliente(): array
    {
        return [
            'recipient_name' => 'Cliente Um',
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
        ];
    }

    public function test_reenvio_em_massa_com_sucesso_atualiza_pedidos(): void
    {
        $company = $this->empresa();

        $snap = $this->snapshotCliente();

        $p1 = Pedido::query()->create([
            'company_id' => $company->id,
            'numero_pedido' => 'PED-BULK-001',
            'chave_nf' => null,
            'numero_nf' => '1',
            'serie_nf' => '1',
            'valor_total' => '10.00',
            'total_volumes' => 1,
            'id_prazo_entrega' => 6,
            'status' => 'erro',
            'destinatario_snapshot' => $snap,
        ]);

        $p2 = Pedido::query()->create([
            'company_id' => $company->id,
            'numero_pedido' => 'PED-BULK-002',
            'chave_nf' => null,
            'numero_nf' => '2',
            'serie_nf' => '1',
            'valor_total' => '20.00',
            'total_volumes' => 1,
            'id_prazo_entrega' => 6,
            'status' => 'erro',
            'destinatario_snapshot' => $snap,
        ]);

        $this->mock(OctalogService::class, function ($mock) {
            $mock->shouldReceive('sendOrders')
                ->once()
                ->with(\Mockery::on(static fn ($arr): bool => is_array($arr) && count($arr) === 2))
                ->andReturn([
                    'success' => true,
                    'data' => [
                        ['Pedido' => 'PED-BULK-001', 'UrlEtiqueta' => 'https://exemplo.test/1.pdf'],
                        ['Pedido' => 'PED-BULK-002', 'UrlEtiqueta' => 'https://exemplo.test/2.pdf'],
                    ],
                    'errors' => [],
                ]);
        });

        $response = $this->post(route('empresas.pedidos.bulk_resend_octalog', [$company]), [
            'pedido_ids' => [$p1->id, $p2->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame('enviado', $p1->fresh()->status);
        $this->assertSame('enviado', $p2->fresh()->status);
        $this->assertSame('https://exemplo.test/1.pdf', $p1->fresh()->url_etiqueta);
        $this->assertSame('https://exemplo.test/2.pdf', $p2->fresh()->url_etiqueta);
    }

    public function test_reenvio_em_massa_somente_pendente_retorna_mensagem_de_erro(): void
    {
        $company = $this->empresa();

        $pendente = Pedido::query()->create([
            'company_id' => $company->id,
            'numero_pedido' => 'PED-PEND',
            'numero_nf' => '9',
            'serie_nf' => '1',
            'valor_total' => '10.00',
            'total_volumes' => 1,
            'id_prazo_entrega' => 6,
            'status' => 'pendente',
            'destinatario_snapshot' => $this->snapshotCliente(),
        ]);

        $response = $this->post(route('empresas.pedidos.bulk_resend_octalog', [$company]), [
            'pedido_ids' => [$pendente->id],
        ]);

        $response->assertSessionHas('error');
        $this->assertSame('pendente', $pendente->fresh()->status);
    }
}

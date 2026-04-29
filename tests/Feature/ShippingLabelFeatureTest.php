<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Pedido;
use App\Models\ShippingLabel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShippingLabelFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_listagem_de_etiquetas_e_exibida(): void
    {
        $response = $this->get(route('etiquetas.index'));

        $response->assertOk();
        $response->assertSee('Etiquetas geradas');
    }

    public function test_salva_dados_da_empresa(): void
    {
        $response = $this->post(route('empresa.store'), [
            'legal_name' => 'Linktec Internet Piauí LTDA',
            'trade_name' => 'Linktec',
            'cnpj' => '11222333000181',
            'state_registration' => '1234567',
            'phone' => '(86) 99999-9999',
            'email' => 'contato@linktec.com',
            'postal_code' => '64000-000',
            'street' => 'Av. Exemplo',
            'number' => '100',
            'complement' => 'Sala 2',
            'district' => 'Centro',
            'city' => 'Teresina',
            'state' => 'PI',
            'contract' => '9912623005',
            'administrative_code' => '123456',
        ]);

        $response->assertStatus(302);
        $response->assertRedirectToRoute('empresa.edit');
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('companies', [
            'trade_name' => 'Linktec',
            'city' => 'Teresina',
        ]);
    }

    public function test_validacao_falha_com_cnpj_invalido(): void
    {
        $response = $this->post(route('empresa.store'), [
            'legal_name' => 'Empresa Teste LTDA',
            'trade_name' => 'Empresa Teste',
            'cnpj' => '11111111111111',
            'phone' => '(86) 99999-9999',
            'email' => 'contato@empresa.com',
            'postal_code' => '64000-000',
            'street' => 'Rua A',
            'number' => '10',
            'district' => 'Centro',
            'city' => 'Teresina',
            'state' => 'PI',
            'contract' => '9912623005',
        ]);

        $response->assertSessionHasErrors(['cnpj']);
        $this->assertDatabaseCount('companies', 0);
    }

    public function test_pedido_enviado_exibe_etiqueta_termica_na_tela(): void
    {
        Company::query()->create([
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
            'contract' => 'CONTRATO XPTO',
        ]);

        $pedido = Pedido::query()->create([
            'numero_pedido' => 'PED-20260429-99999',
            'numero_nf' => '1',
            'serie_nf' => '1',
            'valor_total' => '100.00',
            'total_volumes' => 1,
            'id_prazo_entrega' => 6,
            'status' => 'enviado',
            'destinatario_snapshot' => [
                'recipient_name' => 'Cliente Teste',
                'document' => '123.456.789-09',
                'phone' => '(21) 98888-7777',
                'postal_code' => '22450000',
                'street' => 'Rua das Flores',
                'number' => '122',
                'complement' => 'Casa',
                'district' => 'Jardins',
                'city' => 'Rio de Janeiro',
                'state' => 'RJ',
                'weight_grams' => 1000,
                'service' => 'SEDEX',
                'volume_of' => 1,
                'notes' => 'Entregar em horário comercial',
                'label_width_mm' => 100,
                'label_height_mm' => 150,
                'show_qr_code' => false,
                'tracking_code' => null,
            ],
        ]);

        $response = $this->get(route('pedidos.show', $pedido));

        $response->assertOk();
        $response->assertSee('CLIENTE TESTE', false);
        $response->assertSee('2026042999999');
        $response->assertSee('<svg', false);
    }

    public function test_marcar_impressa_atualiza_registro(): void
    {
        $pedido = Pedido::query()->create([
            'numero_pedido' => 'PED-MARK-1',
            'numero_nf' => '1',
            'serie_nf' => '1',
            'valor_total' => '50.00',
            'total_volumes' => 1,
            'id_prazo_entrega' => 6,
            'status' => 'enviado',
        ]);

        $label = ShippingLabel::query()->create([
            'pedido_id' => $pedido->id,
            'source' => ShippingLabel::SOURCE_OCTALOG,
            'external_url' => 'https://exemplo.test/etiq.pdf',
        ]);

        $this->post(route('etiquetas.mark-printed', $label))
            ->assertRedirect();

        $label->refresh();
        $this->assertNotNull($label->printed_at);
    }

    public function test_rota_da_logo_entrega_arquivo_sem_depender_de_symlink(): void
    {
        Storage::fake('public');

        $path = 'company-logos/logo.png';
        Storage::disk('public')->put($path, 'fake-image');

        Company::query()->create([
            'legal_name' => 'Vellozia Produtos Hospitalares LTDA',
            'trade_name' => 'Vellozia Produtos Hospitalares',
            'cnpj' => '11222333000181',
            'phone' => '(11) 99999-9999',
            'email' => 'contato@vellozia.com',
            'postal_code' => '01310100',
            'street' => 'Av. Paulista',
            'number' => '1000',
            'district' => 'Bela Vista',
            'city' => 'São Paulo',
            'state' => 'SP',
            'contract' => '9912623005',
            'logo_path' => $path,
        ]);

        $this->get(route('empresa.logo'))
            ->assertOk();
    }
}

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

class PedidoSacTicketTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget(OctalogService::AUTH_CACHE_KEY);
        config([
            'services.octalog.url' => 'https://integracao.test',
            'services.octalog.auth_url' => 'https://api.test',
            'services.octalog.usuario' => 'u',
            'services.octalog.senha' => 'p',
        ]);
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

    private function pedidoEnviado(?Company $company = null): Pedido
    {
        $company = $company ?? $this->empresa();

        return Pedido::query()->create([
            'company_id' => $company->id,
            'numero_pedido' => 'PED-SAC-WEB-1',
            'numero_nf' => '55',
            'serie_nf' => '1',
            'valor_total' => '200.00',
            'total_volumes' => 1,
            'id_prazo_entrega' => 6,
            'status' => 'enviado',
        ]);
    }

    #[Test]
    public function formulario_abrir_ticket_carrega_motivos(): void
    {
        Http::fake([
            'https://api.test/autenticacao/token' => Http::response(['token' => 't'], 200),
            'https://integracao.test/sac/motivos' => Http::response([
                ['IDMotivo' => 3, 'Descricao' => 'Reclamação'],
            ], 200),
        ]);

        $company = $this->empresa();
        $pedido = $this->pedidoEnviado($company);

        $this->get(route('empresas.pedidos.sac.ticket.create', [$company, $pedido]))
            ->assertOk()
            ->assertSee('Reclamação', false);
    }

    #[Test]
    public function store_abrir_ticket_redireciona_com_sucesso(): void
    {
        Http::fake([
            'https://api.test/autenticacao/token' => Http::response(['token' => 't'], 200),
            'https://integracao.test/sac/criar-ticket' => Http::response([
                'Status' => 'Aberto',
                'IDTicket' => 42,
            ], 200),
        ]);

        $company = $this->empresa();
        $pedido = $this->pedidoEnviado($company);

        $response = $this->post(route('empresas.pedidos.sac.ticket.store', [$company, $pedido]), [
            'id_motivo' => 3,
            'titulo' => 'Problema na entrega',
            'descricao' => 'Detalhes do ocorrido.',
            'comentario_motorista' => '',
        ]);

        $response->assertRedirect(route('empresas.pedidos.show', [$company, $pedido]));
        $response->assertSessionHas('success');

        Http::assertSent(function ($request) use ($pedido) {
            if ($request->url() !== 'https://integracao.test/sac/criar-ticket') {
                return false;
            }
            $body = json_decode($request->body(), true);

            return is_array($body)
                && ($body['Pedido'] ?? '') === $pedido->numeroSomenteDigitosParaOctalog()
                && (int) ($body['IDMotivo'] ?? 0) === 3;
        });
    }

    #[Test]
    public function store_falha_validacao_sem_titulo(): void
    {
        $company = $this->empresa();
        $pedido = $this->pedidoEnviado($company);

        $response = $this->post(route('empresas.pedidos.sac.ticket.store', [$company, $pedido]), [
            'id_motivo' => 3,
            'descricao' => 'Só descrição',
        ]);

        $response->assertSessionHasErrors(['titulo']);
    }

    #[Test]
    public function pendente_nao_acessa_formulario_ticket(): void
    {
        $company = $this->empresa();

        $pedido = Pedido::query()->create([
            'company_id' => $company->id,
            'numero_pedido' => 'PED-PEND',
            'numero_nf' => '1',
            'serie_nf' => '1',
            'valor_total' => '10.00',
            'total_volumes' => 1,
            'id_prazo_entrega' => 6,
            'status' => 'pendente',
        ]);

        $this->get(route('empresas.pedidos.sac.ticket.create', [$company, $pedido]))
            ->assertRedirect(route('empresas.pedidos.show', [$company, $pedido]));
    }
}

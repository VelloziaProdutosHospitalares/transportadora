<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Services\OctalogInboundWebhookProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OctalogWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function exemploPayloadLista(): array
    {
        return [
            [
                'ID' => 902,
                'Pedido' => 'PED-001',
                'IDStatus' => 1,
                'Status' => 'Entrega Realizada',
                'PrazoEntrega' => 'D+1',
                'DataEvento' => '2025-01-24T16:42:24.243',
                'Recebedor' => 'Rosana Barboza',
                'Latitude' => -22.841283,
                'Longitude' => -43.340178,
                'NomeAgente' => 'Jose',
            ],
        ];
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function delega_para_processador_e_retorna_ok(): void
    {
        $mock = Mockery::mock(OctalogInboundWebhookProcessor::class);
        $mock->shouldReceive('process')
            ->once()
            ->withArgs(function (array $items): bool {
                return count($items) === 1 && $items[0]['Pedido'] === 'PED-001';
            })
            ->andReturn(['updated' => 1, 'skipped' => 0]);

        $this->instance(OctalogInboundWebhookProcessor::class, $mock);

        $response = $this->postJson('/api/octalog/webhook', $this->exemploPayloadLista());

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('updated', 1)
            ->assertJsonPath('skipped', 0);
    }

    #[Test]
    public function rejeita_quando_segredo_configurado_e_cabecalho_incorreto(): void
    {
        config(['services.octalog.webhook_secret' => 'segredo-api']);

        $response = $this->postJson('/api/octalog/webhook', $this->exemploPayloadLista(), [
            'Authorization' => 'Bearer errado',
        ]);

        $response->assertUnauthorized();
    }

    #[Test]
    public function aceita_segredo_com_ou_sem_prefixo_bearer(): void
    {
        config(['services.octalog.webhook_secret' => 'abc123']);

        $mock = Mockery::mock(OctalogInboundWebhookProcessor::class);
        $mock->shouldReceive('process')->twice()->andReturn(['updated' => 0, 'skipped' => 2]);
        $this->instance(OctalogInboundWebhookProcessor::class, $mock);

        $this->postJson('/api/octalog/webhook', $this->exemploPayloadLista(), [
            'Authorization' => 'Bearer abc123',
        ])->assertOk();

        $this->postJson('/api/octalog/webhook', $this->exemploPayloadLista(), [
            'Authorization' => 'abc123',
        ])->assertOk();
    }

    #[Test]
    public function aceita_payload_sac_ticket_e_anexa_ao_pedido(): void
    {
        $pedido = Pedido::query()->create([
            'numero_pedido' => 'PED-SAC-001',
            'numero_nf' => '123',
            'serie_nf' => '1',
            'valor_total' => '100.00',
            'total_volumes' => 1,
            'id_prazo_entrega' => 6,
            'status' => 'enviado',
            'octalog_response' => null,
        ]);

        $payload = [
            [
                'IDTicket' => 1,
                'Pedido' => $pedido->numero_pedido,
                'IDMotivo' => 1,
                'DataInclusao' => '2026-03-12T20:05:14Z',
                'Titulo' => 'Título do ticket',
                'Descricao' => 'Descrição do ticket',
                'SACComentario' => [
                    [
                        'DataInclusao' => '2026-03-12T20:05:14Z',
                        'Descricao' => 'Comentário',
                    ],
                ],
                'SACAnexos' => ['https://exemplo.test/a.jpg'],
            ],
        ];

        $response = $this->postJson('/api/octalog/webhook', $payload);

        $response->assertOk()
            ->assertJsonPath('updated', 1)
            ->assertJsonPath('skipped', 0);

        $pedido->refresh();
        $events = $pedido->octalog_webhook_events ?? [];
        $this->assertCount(1, $events);
        $this->assertSame('sac_ticket', $events[0]['type'] ?? null);
        $this->assertSame(1, $events[0]['id_ticket'] ?? null);
    }

    #[Test]
    public function aceita_lote_misto_tracking_e_sac(): void
    {
        Pedido::query()->create([
            'numero_pedido' => 'PED-MIX-A',
            'numero_nf' => '1',
            'serie_nf' => '1',
            'valor_total' => '10.00',
            'total_volumes' => 1,
            'id_prazo_entrega' => 6,
            'status' => 'enviado',
        ]);
        Pedido::query()->create([
            'numero_pedido' => 'PED-MIX-B',
            'numero_nf' => '2',
            'serie_nf' => '1',
            'valor_total' => '10.00',
            'total_volumes' => 1,
            'id_prazo_entrega' => 6,
            'status' => 'enviado',
        ]);

        $payload = [
            [
                'ID' => 1,
                'Pedido' => 'PED-MIX-A',
                'IDStatus' => 2,
                'Status' => 'Em rota',
                'PrazoEntrega' => 'D+1',
                'DataEvento' => '2025-01-24T16:42:24.243',
            ],
            [
                'IDTicket' => 9,
                'Pedido' => 'PED-MIX-B',
                'IDMotivo' => 3,
                'DataInclusao' => '2026-03-12T20:05:14Z',
                'Titulo' => 'T',
                'Descricao' => 'D',
                'SACComentario' => [],
                'SACAnexos' => [],
            ],
        ];

        $response = $this->postJson('/api/octalog/webhook', $payload);

        $response->assertOk()->assertJsonPath('updated', 2)->assertJsonPath('skipped', 0);

        $this->assertSame(
            'Em rota',
            Pedido::query()->where('numero_pedido', 'PED-MIX-A')->first()?->octalogLastTrackingWebhookLabel(),
        );
        $b = Pedido::query()->where('numero_pedido', 'PED-MIX-B')->first();
        $this->assertCount(1, $b->octalogSacTicketWebhookEvents());
    }

    #[Test]
    public function rejeita_corpo_que_nao_e_lista_json(): void
    {
        $response = $this->postJson('/api/octalog/webhook', [
            'ID' => 1,
            'Pedido' => 'X',
        ]);

        $response->assertUnprocessable();
    }
}

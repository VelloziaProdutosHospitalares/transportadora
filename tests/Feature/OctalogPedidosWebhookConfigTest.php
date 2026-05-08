<?php

namespace Tests\Feature;

use App\Services\OctalogService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OctalogPedidosWebhookConfigTest extends TestCase
{
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

    #[Test]
    public function enviar_configuracao_webhook_post_pedidos(): void
    {
        Http::fake([
            'https://api.test/autenticacao/token' => Http::response(['token' => 't'], 200),
            'https://integracao.test/webhook/configurancao' => Http::response(['mensagem' => 'Dados Atualizados'], 200),
        ]);

        $response = $this->post(route('octalog.pedidos.webhook.update'), [
            'url' => 'https://cliente.test/api/octalog/webhook',
            'limite_envio' => 25,
            'ativo_webhook' => '1',
            'data_inicio_envio' => now()->subDay()->format('Y-m-d\TH:i'),
            'headers_raw' => "x-custom: abc\n",
            'id_status' => [4, 10],
            'contato_nome' => 'Suporte',
            'contato_email' => 's@teste.local',
            'contato_celular' => '11999999999',
        ]);

        $response->assertRedirect(route('octalog.pedidos.webhook.index'));
        $response->assertSessionHas('success');

        Http::assertSent(function ($request) {
            if ($request->url() !== 'https://integracao.test/webhook/configurancao') {
                return false;
            }
            if ($request->method() !== 'POST') {
                return false;
            }
            $body = json_decode($request->body(), true);

            return is_array($body)
                && ($body['URL'] ?? '') === 'https://cliente.test/api/octalog/webhook'
                && (int) ($body['LimiteEnvio'] ?? 0) === 25
                && ($body['AtivoWebhook'] ?? null) === true
                && ($body['IDStatus'] ?? null) === [4, 10]
                && is_array($body['ContatoTecnico'] ?? null);
        });
    }

    #[Test]
    public function consultar_configuracao_redireciona_com_dados(): void
    {
        Http::fake([
            'https://api.test/autenticacao/token' => Http::response(['token' => 't'], 200),
            'https://integracao.test/webhook/configurancao' => Http::response([
                'URL' => 'https://exemplo.test/hook',
                'LimiteEnvio' => 20,
                'AtivoWebhook' => true,
                'DataInicioEnvio' => '2026-03-12T03:00:00Z',
                'IDStatus' => [4],
                'Header' => [],
                'ContatoTecnico' => [],
            ], 200),
        ]);

        $response = $this->post(route('octalog.pedidos.webhook.consultar'));

        $response->assertRedirect(route('octalog.pedidos.webhook.index'));
        $response->assertSessionHas('pedidos_webhook_config');
    }
}

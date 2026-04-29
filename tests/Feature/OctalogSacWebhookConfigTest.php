<?php

namespace Tests\Feature;

use App\Services\OctalogService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OctalogSacWebhookConfigTest extends TestCase
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
    public function enviar_configuracao_webhook_post_sac(): void
    {
        Http::fake([
            'https://api.test/autenticacao/token' => Http::response(['token' => 't'], 200),
            'https://integracao.test/sac/webhook/configurancao' => Http::response(['mensagem' => 'Dados Atualizados'], 200),
        ]);

        $response = $this->post(route('octalog.sac.webhook.update'), [
            'url' => 'https://cliente.test/api/octalog/webhook',
            'limite_envio' => 25,
            'data_inicio_envio' => '2026-04-01T08:00',
            'headers_raw' => "x-custom: abc\n",
            'contato_nome' => 'Suporte',
            'contato_email' => 's@teste.local',
            'contato_celular' => '11999999999',
        ]);

        $response->assertRedirect(route('octalog.sac.webhook.index'));
        $response->assertSessionHas('success');

        Http::assertSent(function ($request) {
            if ($request->url() !== 'https://integracao.test/sac/webhook/configurancao') {
                return false;
            }
            if ($request->method() !== 'POST') {
                return false;
            }
            $body = json_decode($request->body(), true);

            return is_array($body)
                && ($body['URL'] ?? '') === 'https://cliente.test/api/octalog/webhook'
                && (int) ($body['LimiteEnvio'] ?? 0) === 25
                && is_array($body['ContatoTecnico'] ?? null);
        });
    }

    #[Test]
    public function consultar_configuracao_redireciona_com_dados(): void
    {
        Http::fake([
            'https://api.test/autenticacao/token' => Http::response(['token' => 't'], 200),
            'https://integracao.test/sac/webhook/configurancao' => Http::response([
                'URL' => 'https://exemplo.test/hook',
                'LimiteEnvio' => 20,
                'AtivoWebhook' => true,
                'DataInicioEnvio' => '2026-03-12T03:00:00Z',
                'Header' => [],
                'ContatoTecnico' => [],
            ], 200),
        ]);

        $response = $this->post(route('octalog.sac.webhook.consultar'));

        $response->assertRedirect(route('octalog.sac.webhook.index'));
        $response->assertSessionHas('sac_webhook_config');
    }
}

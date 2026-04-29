<?php

namespace Tests\Feature;

use App\Services\OctalogSacService;
use App\Services\OctalogService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OctalogSacServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget(OctalogService::AUTH_CACHE_KEY);
        config([
            'services.octalog.url' => 'https://integracao.test',
            'services.octalog.auth_url' => 'https://api.test',
            'services.octalog.usuario' => 'usuario_teste',
            'services.octalog.senha' => 'senha_teste',
        ]);
    }

    #[Test]
    public function list_motivos_retorna_sucesso(): void
    {
        Http::fake([
            'https://api.test/autenticacao/token' => Http::response(['token' => 'tok-1'], 200),
            'https://integracao.test/sac/motivos' => Http::response([
                ['IDMotivo' => 1, 'Descricao' => 'Apenas Comentário'],
            ], 200),
        ]);

        $result = app(OctalogSacService::class)->listMotivos();

        $this->assertTrue($result['success']);
        $this->assertSame(200, $result['status']);
        $this->assertIsArray($result['data']);
        $this->assertSame('Apenas Comentário', $result['data'][0]['Descricao'] ?? null);
    }

    #[Test]
    public function repetir_requisicao_apos_401_renovando_token(): void
    {
        Http::fake([
            'https://api.test/autenticacao/token' => Http::sequence()
                ->push(['token' => 'tok-a'], 200)
                ->push(['token' => 'tok-b'], 200),
            'https://integracao.test/sac/motivos' => Http::sequence()
                ->push([], 401)
                ->push([['IDMotivo' => 2, 'Descricao' => 'Ok']], 200),
        ]);

        $result = app(OctalogSacService::class)->listMotivos();

        $this->assertTrue($result['success']);
        $this->assertSame('Ok', $result['data'][0]['Descricao'] ?? null);
    }

    #[Test]
    public function cancelar_ticket_envia_delete_com_json(): void
    {
        Http::fake([
            'https://api.test/autenticacao/token' => Http::response(['token' => 'tok'], 200),
            'https://integracao.test/sac/cancelar-ticket' => Http::response([
                'Status' => 'Cancelado',
                'IDTicket' => 5,
            ], 200),
        ]);

        $result = app(OctalogSacService::class)->cancelTicket([
            'Pedido' => 'P-1',
            'IDMotivo' => 0,
            'Descricao' => 'Motivo',
        ]);

        $this->assertTrue($result['success']);
        Http::assertSent(function ($request) {
            if ($request->url() !== 'https://integracao.test/sac/cancelar-ticket') {
                return false;
            }
            if ($request->method() !== 'DELETE') {
                return false;
            }

            $body = json_decode($request->body(), true);

            return is_array($body)
                && ($body['Pedido'] ?? null) === 'P-1'
                && (int) ($body['IDMotivo'] ?? -1) === 0;
        });
    }
}

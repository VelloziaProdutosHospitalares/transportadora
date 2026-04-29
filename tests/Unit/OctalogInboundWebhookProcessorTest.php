<?php

namespace Tests\Unit;

use App\Services\OctalogInboundWebhookProcessor;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OctalogInboundWebhookProcessorTest extends TestCase
{
    #[Test]
    public function normaliza_campos_opcionais_nulos_na_linha_do_webhook(): void
    {
        $processor = new OctalogInboundWebhookProcessor;
        $ref = new \ReflectionMethod($processor, 'normalizeTrackingEvent');
        $ref->setAccessible(true);

        $linha = [
            'ID' => 903,
            'Pedido' => '954595',
            'IDStatus' => 1,
            'Status' => 'Integração Recebida',
            'PrazoEntrega' => 'D+1',
            'DataEvento' => '2025-01-24T16:42:24.613',
            'Recebedor' => null,
            'Latitude' => null,
            'Longitude' => null,
            'NomeAgente' => null,
        ];

        /** @var array<string, mixed> $out */
        $out = $ref->invoke($processor, $linha);

        $this->assertSame(903, $out['id_evento_octalog']);
        $this->assertSame(1, $out['id_status']);
        $this->assertSame('Integração Recebida', $out['status_text']);
        $this->assertNull($out['recebedor']);
        $this->assertNull($out['latitude']);
        $this->assertNull($out['longitude']);
        $this->assertNull($out['nome_agente']);
        $this->assertArrayHasKey('received_at', $out);
    }
}

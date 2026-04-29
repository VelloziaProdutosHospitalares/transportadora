<?php

namespace App\Services;

use App\Models\Pedido;

class OctalogInboundWebhookProcessor
{
    private const MAX_STORED_EVENTS = 40;

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{updated: int, skipped: int}
     */
    public function process(array $items): array
    {
        $updated = 0;
        $skipped = 0;

        foreach ($items as $item) {
            if ($this->isSacTicketPayload($item)) {
                $this->appendEventForPedido($item, $this->normalizeSacTicketEvent($item), $updated, $skipped);

                continue;
            }

            if (! $this->isTrackingPayload($item)) {
                $skipped++;

                continue;
            }

            $this->appendEventForPedido($item, $this->normalizeTrackingEvent($item), $updated, $skipped);
        }

        return ['updated' => $updated, 'skipped' => $skipped];
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<string, mixed>  $normalized
     */
    private function appendEventForPedido(array $item, array $normalized, int &$updated, int &$skipped): void
    {
        $numero = isset($item['Pedido']) ? trim((string) $item['Pedido']) : '';
        if ($numero === '') {
            $skipped++;

            return;
        }

        $pedido = Pedido::query()->where('numero_pedido', $numero)->first();
        if ($pedido === null) {
            $skipped++;

            return;
        }

        $events = $pedido->octalog_webhook_events;
        if (! is_array($events)) {
            $events = [];
        }
        $events[] = $normalized;
        if (count($events) > self::MAX_STORED_EVENTS) {
            $events = array_slice($events, -self::MAX_STORED_EVENTS);
        }
        $pedido->octalog_webhook_events = $events;
        $pedido->save();
        $updated++;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function isSacTicketPayload(array $item): bool
    {
        return array_key_exists('IDTicket', $item)
            && array_key_exists('Pedido', $item)
            && array_key_exists('IDMotivo', $item);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function isTrackingPayload(array $item): bool
    {
        return array_key_exists('ID', $item)
            && array_key_exists('Pedido', $item)
            && array_key_exists('IDStatus', $item)
            && array_key_exists('Status', $item)
            && array_key_exists('PrazoEntrega', $item)
            && array_key_exists('DataEvento', $item);
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function normalizeTrackingEvent(array $item): array
    {
        return [
            'type' => 'tracking',
            'id_evento_octalog' => (int) ($item['ID'] ?? 0),
            'id_status' => (int) ($item['IDStatus'] ?? 0),
            'status_text' => (string) ($item['Status'] ?? ''),
            'prazo_entrega' => (string) ($item['PrazoEntrega'] ?? ''),
            'data_evento' => (string) ($item['DataEvento'] ?? ''),
            'recebedor' => array_key_exists('Recebedor', $item) ? $item['Recebedor'] : null,
            'latitude' => array_key_exists('Latitude', $item) ? $item['Latitude'] : null,
            'longitude' => array_key_exists('Longitude', $item) ? $item['Longitude'] : null,
            'nome_agente' => array_key_exists('NomeAgente', $item) ? $item['NomeAgente'] : null,
            'received_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Payload conforme exemplo Octalog SAC (webhook).
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function normalizeSacTicketEvent(array $item): array
    {
        $comentarios = $item['SACComentario'] ?? [];
        if (! is_array($comentarios)) {
            $comentarios = [];
        }

        $anexos = $item['SACAnexos'] ?? [];
        if (! is_array($anexos)) {
            $anexos = [];
        }

        return [
            'type' => 'sac_ticket',
            'id_ticket' => (int) ($item['IDTicket'] ?? 0),
            'id_motivo' => (int) ($item['IDMotivo'] ?? 0),
            'data_inclusao' => (string) ($item['DataInclusao'] ?? ''),
            'titulo' => (string) ($item['Titulo'] ?? ''),
            'descricao' => (string) ($item['Descricao'] ?? ''),
            'sac_comentario' => $comentarios,
            'sac_anexos' => array_values(array_map(static fn (mixed $u) => (string) $u, $anexos)),
            'received_at' => now()->toIso8601String(),
        ];
    }
}

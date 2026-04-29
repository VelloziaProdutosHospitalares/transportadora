<?php

namespace App\Models;

use App\Support\OctalogStatusAtividade;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    protected $fillable = [
        'numero_pedido',
        'chave_nf',
        'numero_nf',
        'serie_nf',
        'valor_total',
        'total_volumes',
        'id_prazo_entrega',
        'status',
        'octalog_response',
        'octalog_webhook_events',
        'destinatario_snapshot',
        'url_etiqueta',
        'erro_mensagem',
    ];

    protected function casts(): array
    {
        return [
            'valor_total' => 'decimal:2',
            'octalog_response' => 'array',
            'octalog_webhook_events' => 'array',
            'destinatario_snapshot' => 'array',
            'status' => 'string',
        ];
    }

    public function getNumeroFormatadoAttribute(): string
    {
        return $this->numero_pedido;
    }

    public function scopeEnviados(Builder $query): Builder
    {
        return $query->where('status', 'enviado');
    }

    public function scopePendentes(Builder $query): Builder
    {
        return $query->where('status', 'pendente');
    }

    public function shippingLabels(): HasMany
    {
        return $this->hasMany(ShippingLabel::class);
    }

    /**
     * Rótulo do status operacional Octalog: resposta inicial em `octalog_response` ou último evento de **tracking** no webhook.
     */
    public function octalogStatusAtividadeLabel(): ?string
    {
        $row = $this->octalogFirstResponseRow();
        if ($row !== null) {
            $fromApi = OctalogStatusAtividade::labelFromResponseRow($row);
            if ($fromApi !== null) {
                return $fromApi;
            }
        }

        return $this->octalogLastTrackingWebhookLabel();
    }

    /**
     * Último status textual vindo do webhook de tracking (ignora eventos SAC).
     */
    public function octalogLastTrackingWebhookLabel(): ?string
    {
        $events = $this->octalog_webhook_events;
        if (! is_array($events) || $events === []) {
            return null;
        }

        for ($i = count($events) - 1; $i >= 0; $i--) {
            $e = $events[$i];
            if (! is_array($e)) {
                continue;
            }
            $type = $e['type'] ?? null;
            if ($type === 'sac_ticket') {
                continue;
            }
            if ($type === 'tracking' || ($type === null && array_key_exists('id_status', $e))) {
                $text = isset($e['status_text']) ? trim((string) $e['status_text']) : '';
                if ($text !== '') {
                    return $text;
                }
                $idStatus = $e['id_status'] ?? null;
                if (is_numeric($idStatus)) {
                    $id = (int) $idStatus;

                    return OctalogStatusAtividade::labelForId($id) ?? 'ID '.$id;
                }
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>> Eventos de ticket SAC recebidos via webhook.
     */
    public function octalogSacTicketWebhookEvents(): array
    {
        $events = $this->octalog_webhook_events;
        if (! is_array($events) || $events === []) {
            return [];
        }

        $out = [];
        foreach ($events as $e) {
            if (is_array($e) && ($e['type'] ?? null) === 'sac_ticket') {
                $out[] = $e;
            }
        }

        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function octalogFirstResponseRow(): ?array
    {
        $r = $this->octalog_response;

        if (! is_array($r) || $r === []) {
            return null;
        }

        $first = array_is_list($r) ? ($r[0] ?? null) : $r;

        return is_array($first) ? $first : null;
    }
}

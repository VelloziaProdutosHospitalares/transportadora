<?php

namespace App\Models;

use App\Support\OctalogStatusAtividade;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    protected $fillable = [
        'company_id',
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
        'octalog_id',
        'octalog_status_id',
        'octalog_status_text',
        'octalog_status_at',
    ];

    protected function casts(): array
    {
        return [
            'valor_total' => 'decimal:2',
            'octalog_response' => 'array',
            'octalog_webhook_events' => 'array',
            'destinatario_snapshot' => 'array',
            'status' => 'string',
            'octalog_status_at' => 'datetime',
        ];
    }

    public function getNumeroFormatadoAttribute(): string
    {
        return $this->numero_pedido;
    }

    /**
     * Somente dígitos enviados no campo Pedido para a Octalog e impressos na etiqueta (código de barras).
     * Ex.: {@code PED-20260513-00032} → {@code 2026051300032}.
     */
    public function numeroSomenteDigitosParaOctalog(): string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->numero_pedido);

        return $digits !== '' ? $digits : $this->numero_pedido;
    }

    /**
     * Resolve o pedido quando a Octalog devolve Pedido apenas com dígitos ou no formato histórico PED-*.
     */
    public static function primeiroPorValorPedidoRespondidoOctalog(?string $valorInformado): ?self
    {
        if ($valorInformado === null) {
            return null;
        }

        $t = trim((string) $valorInformado);
        if ($t === '') {
            return null;
        }

        $byExact = self::query()->where('numero_pedido', $t)->first();
        if ($byExact !== null) {
            return $byExact;
        }

        $norm = preg_replace('/\D+/', '', $t);
        if ($norm === '') {
            return null;
        }

        if (preg_match('/^(\d{8})(\d{5})$/', $norm, $m)) {
            $canonical = 'PED-'.$m[1].'-'.$m[2];
            $match = self::query()->where('numero_pedido', $canonical)->first();
            if ($match !== null) {
                return $match;
            }
        }

        return self::query()->where('numero_pedido', $norm)->first();
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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Rótulo do status operacional Octalog, na ordem de prioridade:
     * 1. Último evento de tracking recebido via webhook (mais recente / automático).
     * 2. Último status gravado pela consulta manual (/pedidos/consulta-octalog).
     * 3. Status da resposta inicial de envio (octalog_response).
     */
    public function octalogStatusAtividadeLabel(): ?string
    {
        $fromWebhook = $this->octalogLastTrackingWebhookLabel();
        if ($fromWebhook !== null) {
            return $fromWebhook;
        }

        if (is_string($this->octalog_status_text) && trim($this->octalog_status_text) !== '') {
            return trim($this->octalog_status_text);
        }

        if (is_numeric($this->octalog_status_id)) {
            $label = OctalogStatusAtividade::labelForId((int) $this->octalog_status_id);
            if ($label !== null) {
                return $label;
            }
        }

        $row = $this->octalogFirstResponseRow();
        if ($row !== null) {
            return OctalogStatusAtividade::labelFromResponseRow($row);
        }

        return null;
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingLabel extends Model
{
    public const SOURCE_OCTALOG = 'octalog';

    public const SOURCE_THERMAL = 'thermal';

    protected $fillable = [
        'pedido_id',
        'source',
        'external_url',
        'payload',
        'printed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'printed_at' => 'datetime',
        ];
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class);
    }

    public function isPrinted(): bool
    {
        return $this->printed_at !== null;
    }

    public function summaryLine(): string
    {
        if ($this->source === self::SOURCE_OCTALOG) {
            return 'Etiqueta Octalog (PDF)';
        }

        $payload = $this->payload;
        if (is_array($payload)) {
            $name = isset($payload['recipient_name']) ? trim((string) $payload['recipient_name']) : '';

            return $name !== '' ? 'Etiqueta térmica — '.$name : 'Etiqueta térmica';
        }

        return 'Etiqueta térmica';
    }
}

<?php

namespace App\Support;

use App\Models\Pedido;

class ShippingLabelFormDefaults
{
    /**
     * @return array<string, mixed>
     */
    public static function base(): array
    {
        return [
            'order_number' => 'PED-'.date('Ymd').'-00001',
            'tracking_code' => 'AA02962084BR',
            'recipient_name' => 'Cliente Teste',
            'document' => '123.456.789-09',
            'phone' => '(21) 98888-7777',
            'postal_code' => '22450-000',
            'street' => 'Rua das Flores',
            'number' => '122',
            'complement' => 'Casa',
            'district' => 'Jardins',
            'city' => 'Rio de Janeiro',
            'state' => 'RJ',
            'volume_of' => 1,
            'notes' => 'Entregar em horário comercial.',
            // Etiqueta térmica comum: 100 × 150 mm (≈ 4" × 6"; também existe 102 × 152 mm conforme o rolo).
            'label_width_mm' => 100,
            'label_height_mm' => 150,
            'show_qr_code' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function forPedido(Pedido $pedido): array
    {
        return array_merge(self::base(), [
            'order_number' => $pedido->numero_pedido,
            'volume_of' => max(1, (int) $pedido->total_volumes),
            'service' => match ((int) $pedido->id_prazo_entrega) {
                6 => 'D+1',
                15 => 'D+2',
                default => 'Envio',
            },
        ]);
    }
}

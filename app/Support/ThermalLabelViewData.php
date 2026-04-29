<?php

namespace App\Support;

use App\Models\Pedido;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Picqer\Barcode\BarcodeGeneratorSVG;

final class ThermalLabelViewData
{
    /**
     * Monta os dados da etiqueta térmica a partir do pedido (snapshot gravado no envio ou retorno Octalog).
     *
     * @return array<string, mixed>|null
     */
    public static function fromPedido(Pedido $pedido): ?array
    {
        if ($pedido->status !== 'enviado') {
            return null;
        }

        $base = ShippingLabelFormDefaults::forPedido($pedido);
        $snap = $pedido->destinatario_snapshot;

        if (is_array($snap) && $snap !== []) {
            $merged = array_merge($base, $snap);

            return self::enrich($merged);
        }

        $fromApi = self::fromOctalogDestinatario(self::octalogFirstRow($pedido));
        if ($fromApi === null || trim((string) ($fromApi['recipient_name'] ?? '')) === '') {
            return null;
        }

        $merged = array_merge($base, $fromApi);

        return self::enrich($merged);
    }

    public static function barcodeSvg(string $barcodePlain): string
    {
        $generator = new BarcodeGeneratorSVG;
        $content = preg_replace('/\s+/', '', trim($barcodePlain)) ?: $barcodePlain;

        return $generator->getBarcode($content, $generator::TYPE_CODE_128, 2, 70);
    }

    public static function qrCodeSvg(string $data): string
    {
        return (new Builder(
            writer: new SvgWriter,
            data: $data,
            size: 120,
            margin: 0,
        ))
            ->build()
            ->getString();
    }

    /**
     * @param  array<string, mixed>  $labelData
     * @return array<string, mixed>
     */
    private static function enrich(array $labelData): array
    {
        $cepDigits = (string) ($labelData['postal_code'] ?? '');
        $barcodePlain = preg_replace('/\D+/', '', (string) ($labelData['order_number'] ?? ''));
        if ($barcodePlain === '') {
            $barcodePlain = preg_replace('/\s+/', '', trim((string) ($labelData['order_number'] ?? '')));
        }
        $labelData['barcode_plain'] = $barcodePlain;
        $labelData['cep_formatted'] = strlen($cepDigits) === 8
            ? substr($cepDigits, 0, 5).'-'.substr($cepDigits, 5)
            : $cepDigits;
        $labelData['volume_of'] = max(1, (int) ($labelData['volume_of'] ?? 1));
        $labelData['show_qr_code'] = filter_var($labelData['show_qr_code'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return $labelData;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function octalogFirstRow(Pedido $pedido): ?array
    {
        $r = $pedido->octalog_response;
        if (! is_array($r) || $r === []) {
            return null;
        }

        $first = array_is_list($r) ? ($r[0] ?? null) : $r;

        return is_array($first) ? $first : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function fromOctalogDestinatario(?array $row): ?array
    {
        if ($row === null) {
            return null;
        }

        $d = $row['Destinatario'] ?? $row['destinatario'] ?? null;
        if (! is_array($d)) {
            return null;
        }

        $cep = isset($d['CEP']) ? preg_replace('/\D+/', '', (string) $d['CEP']) : '';

        return [
            'recipient_name' => isset($d['Nome']) ? trim((string) $d['Nome']) : '',
            'document' => isset($d['Documento']) ? trim((string) $d['Documento']) : null,
            'phone' => isset($d['Telefone']) ? trim((string) $d['Telefone']) : null,
            'postal_code' => $cep,
            'street' => isset($d['Endereco']) ? trim((string) $d['Endereco']) : '',
            'number' => isset($d['Numero']) ? trim((string) $d['Numero']) : '',
            'complement' => isset($d['Complemento']) ? trim((string) $d['Complemento']) : null,
            'district' => isset($d['Bairro']) ? trim((string) $d['Bairro']) : '',
            'city' => isset($d['Cidade']) ? trim((string) $d['Cidade']) : '',
            'state' => isset($d['UF']) ? strtoupper(trim((string) $d['UF'])) : '',
            'weight_grams' => 1000,
            'volume_of' => 1,
            'notes' => '',
            'label_width_mm' => 100,
            'label_height_mm' => 150,
            'show_qr_code' => false,
            'tracking_code' => null,
        ];
    }
}

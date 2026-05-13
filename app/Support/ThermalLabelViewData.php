<?php

namespace App\Support;

use App\Models\Pedido;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Picqer\Barcode\BarcodeGeneratorSVG;

final class ThermalLabelViewData
{
    /** Largura mínima de etiqueta compatível com alimentação L42 (tubete 1″). */
    private const LABEL_WIDTH_MM_MIN = 20;

    /** Largura máxima de impressão L42 (~108 mm; rolos comuns até 111 mm físicos). */
    private const LABEL_PRINTABLE_WIDTH_MM_MAX = 108;

    private const LABEL_HEIGHT_MM_MIN = 20;

    private const LABEL_HEIGHT_MM_MAX = 250;

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
        /** Código de barras apenas com dígitos; o número interno em tela continua PED-…. */
        $orderCompact = preg_replace('/\s+/', '', trim((string) ($labelData['order_number'] ?? '')));
        $digitsOnly = preg_replace('/\D+/', '', $orderCompact);
        $labelData['barcode_plain'] = $digitsOnly !== '' ? $digitsOnly : $orderCompact;
        $labelData['cep_formatted'] = strlen($cepDigits) === 8
            ? substr($cepDigits, 0, 5).'-'.substr($cepDigits, 5)
            : $cepDigits;
        $labelData['volume_of'] = max(1, (int) ($labelData['volume_of'] ?? 1));
        $labelData['show_qr_code'] = filter_var($labelData['show_qr_code'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $labelData['label_width_mm'] = self::clampMmDimension(
            $labelData['label_width_mm'] ?? null,
            100,
            self::LABEL_WIDTH_MM_MIN,
            self::LABEL_PRINTABLE_WIDTH_MM_MAX
        );

        $labelData['label_height_mm'] = self::clampMmDimension(
            $labelData['label_height_mm'] ?? null,
            148,
            self::LABEL_HEIGHT_MM_MIN,
            self::LABEL_HEIGHT_MM_MAX
        );

        return $labelData;
    }

    /**
     * @param  mixed  $value  Snapshot/API (int|string|null).
     */
    private static function clampMmDimension(mixed $value, int $fallback, int $min, int $max): int
    {
        if ($value !== null && $value !== '' && is_numeric($value)) {
            $n = (int) round((float) $value);

            return max($min, min($max, $n));
        }

        return max($min, min($max, $fallback));
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
            'label_height_mm' => 148,
            'show_qr_code' => false,
            'tracking_code' => null,
        ];
    }
}

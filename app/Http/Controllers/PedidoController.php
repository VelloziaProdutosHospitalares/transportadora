<?php

namespace App\Http\Controllers;

use App\DTOs\OctalogOrderData;
use App\Exceptions\OctalogException;
use App\Http\Requests\StorePedidoRequest;
use App\Models\Company;
use App\Models\Pedido;
use App\Models\ShippingLabel;
use App\Services\OctalogService;
use App\Support\ThermalLabelViewData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PedidoController extends Controller
{
    public function __construct(
        private readonly OctalogService $octalogService,
    ) {}

    public function index(): View
    {
        $pedidos = Pedido::query()->latest()->paginate(15)->withQueryString();

        return view('pedidos.index', compact('pedidos'));
    }

    public function create(): View
    {
        return view('pedidos.create');
    }

    public function store(StorePedidoRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $pedido = DB::transaction(function () use ($validated) {
            $record = Pedido::query()->create([
                'numero_pedido' => 'PED-TEMP-'.Str::uuid()->toString(),
                'chave_nf' => $validated['chave_nf'] ?? null,
                'numero_nf' => $validated['numero_nf'],
                'serie_nf' => $validated['serie_nf'],
                'valor_total' => $validated['valor_total'],
                'total_volumes' => $validated['total_volumes'],
                'id_prazo_entrega' => $validated['id_prazo_entrega'],
                'status' => 'pendente',
            ]);

            $record->update([
                'numero_pedido' => 'PED-'.date('Ymd').'-'.str_pad((string) $record->id, 5, '0', STR_PAD_LEFT),
            ]);

            return $record->fresh();
        });

        $dadosFiscais = [
            'ChaveNotaFiscal' => $validated['chave_nf'] ?? null,
            'NumeroNotaFiscal' => $validated['numero_nf'],
            'SerieNotaFiscal' => $validated['serie_nf'],
            'ValorTotalReais' => (float) $validated['valor_total'],
        ];

        $docDest = trim((string) ($validated['destinatario_documento'] ?? ''));
        $telDest = trim((string) ($validated['destinatario_telefone'] ?? ''));
        $emailDest = trim((string) ($validated['destinatario_email'] ?? ''));

        $destinatario = [
            'Nome' => $validated['destinatario_nome'],
            'Documento' => $docDest !== '' ? $docDest : null,
            'InscricaoEstadual' => null,
            'Endereco' => $validated['destinatario_endereco'],
            'Numero' => $validated['destinatario_numero'],
            'Bairro' => $validated['destinatario_bairro'],
            'Cidade' => $validated['destinatario_cidade'],
            'PontoReferencia' => '',
            'Complemento' => '',
            'CEP' => $validated['destinatario_cep'],
            'UF' => strtoupper($validated['destinatario_uf']),
            'Telefone' => $telDest,
            'Email' => $emailDest,
        ];

        $company = Company::query()->first();

        $remetente = $company !== null ? [
            'RazaoSocial' => $company->legal_name,
            'CNPJ' => preg_replace('/\D+/', '', (string) $company->cnpj),
            'InscricaoEstadual' => $company->state_registration,
            'Endereco' => $company->street,
            'Numero' => $company->number,
            'Bairro' => $company->district,
            'Cidade' => $company->city,
            'CEP' => preg_replace('/\D+/', '', (string) $company->postal_code),
            'UF' => strtoupper((string) $company->state),
            'Telefone' => $company->phone,
            'Email' => $company->email,
        ] : null;

        $orderData = new OctalogOrderData(
            pedido: $pedido->numero_pedido,
            idPrazoEntrega: (int) $validated['id_prazo_entrega'],
            totalVolumes: (int) $validated['total_volumes'],
            dataVenda: null,
            dadosFiscais: $dadosFiscais,
            remetente: $remetente,
            destinatario: $destinatario,
        );

        try {
            $result = $this->octalogService->sendOrders([$orderData]);
        } catch (OctalogException $e) {
            $safeMessage = $e->getMessage();
            Log::error('Octalog: exceção ao enviar pedido', [
                'pedido_id' => $pedido->id,
                'mensagem' => $safeMessage,
            ]);

            $pedido->update([
                'status' => 'erro',
                'erro_mensagem' => $safeMessage,
            ]);

            return redirect()
                ->route('pedidos.create')
                ->with('error', 'Não foi possível enviar o pedido à Octalog. '.$safeMessage);
        }

        if ($result['success'] === true) {
            /** @var array<int|string, mixed> $data */
            $data = is_array($result['data']) ? $result['data'] : [];
            $urlEtiqueta = $this->extractLabelUrl($data);

            $pedido->update([
                'status' => 'enviado',
                'octalog_response' => $data,
                'url_etiqueta' => $urlEtiqueta,
                'erro_mensagem' => null,
                'destinatario_snapshot' => [
                    'recipient_name' => $validated['destinatario_nome'],
                    'document' => $docDest !== '' ? $docDest : null,
                    'phone' => $telDest !== '' ? $telDest : null,
                    'postal_code' => preg_replace('/\D+/', '', (string) $validated['destinatario_cep']),
                    'street' => $validated['destinatario_endereco'],
                    'number' => $validated['destinatario_numero'],
                    'complement' => '',
                    'district' => $validated['destinatario_bairro'],
                    'city' => $validated['destinatario_cidade'],
                    'state' => strtoupper($validated['destinatario_uf']),
                    'weight_grams' => 1000,
                    'service' => match ((int) $validated['id_prazo_entrega']) {
                        6 => 'D+1',
                        15 => 'D+2',
                        default => 'Envio',
                    },
                    'volume_of' => max(1, (int) $validated['total_volumes']),
                    'notes' => '',
                    'label_width_mm' => 100,
                    'label_height_mm' => 150,
                    'show_qr_code' => false,
                    'tracking_code' => null,
                ],
            ]);

            if ($urlEtiqueta) {
                ShippingLabel::query()->updateOrCreate(
                    ['pedido_id' => $pedido->id, 'source' => ShippingLabel::SOURCE_OCTALOG],
                    ['external_url' => $urlEtiqueta]
                );
            }

            return redirect()
                ->route('pedidos.show', $pedido)
                ->with('success', 'Pedido enviado com sucesso!');
        }

        $erroDetalhe = $this->formatOctalogErrors($result['errors'] ?? []);

        $pedido->update([
            'status' => 'erro',
            'octalog_response' => is_array($result['errors']) ? $result['errors'] : [],
            'erro_mensagem' => $erroDetalhe,
        ]);

        $flashErro = mb_strlen($erroDetalhe) > 400
            ? mb_substr($erroDetalhe, 0, 400).'…'
            : $erroDetalhe;

        return redirect()
            ->route('pedidos.create')
            ->with('error', 'A Octalog não aceitou o pedido. '.$flashErro);
    }

    public function show(Pedido $pedido): View
    {
        if ($pedido->url_etiqueta) {
            ShippingLabel::query()->updateOrCreate(
                ['pedido_id' => $pedido->id, 'source' => ShippingLabel::SOURCE_OCTALOG],
                ['external_url' => $pedido->url_etiqueta]
            );
        }

        $company = Company::query()->first();
        $octalogShippingLabel = $pedido->shippingLabels()
            ->where('source', ShippingLabel::SOURCE_OCTALOG)
            ->first();

        $labelData = ThermalLabelViewData::fromPedido($pedido);
        $barcodeSvg = null;
        $qrCodeSvg = null;
        if ($labelData !== null) {
            $barcodeSvg = ThermalLabelViewData::barcodeSvg((string) $labelData['barcode_plain']);
            if (($labelData['show_qr_code'] ?? false) === true) {
                $qrPayload = trim((string) ($labelData['tracking_code'] ?? '')) !== ''
                    ? (string) $labelData['tracking_code']
                    : (string) $labelData['barcode_plain'];
                $qrCodeSvg = ThermalLabelViewData::qrCodeSvg($qrPayload);
            }
        }

        return view('pedidos.show', [
            'pedido' => $pedido,
            'company' => $company,
            'labelData' => $labelData,
            'barcodeSvg' => $barcodeSvg,
            'qrCodeSvg' => $qrCodeSvg,
            'octalogShippingLabel' => $octalogShippingLabel,
        ]);
    }

    /**
     * @param  array<int|string, mixed>  $data
     */
    private function extractLabelUrl(array $data): ?string
    {
        if ($data === []) {
            return null;
        }

        $first = array_is_list($data) ? ($data[0] ?? null) : $data;
        if (! is_array($first)) {
            return null;
        }

        $url = $first['UrlEtiqueta'] ?? $first['urlEtiqueta'] ?? null;

        return is_string($url) && $url !== '' ? $url : null;
    }

    /**
     * @param  array<int|string, mixed>  $errors
     */
    private function formatOctalogErrors(array $errors): string
    {
        if ($errors === []) {
            return 'Resposta sem detalhes da API.';
        }

        $lines = [];

        if (array_is_list($errors)) {
            foreach ($errors as $item) {
                $line = $this->stringFromOctalogErrorItem($item);
                if ($line !== null) {
                    $lines[] = $line;
                }
            }
        } else {
            $line = $this->stringFromOctalogErrorItem($errors);
            if ($line !== null) {
                $lines[] = $line;
            }
        }

        $lines = array_values(array_unique(array_filter($lines, static fn (string $s) => $s !== '')));

        if ($lines !== []) {
            $text = implode(' ', $lines);

            return mb_strlen($text) > 2000 ? mb_substr($text, 0, 2000).'…' : $text;
        }

        $encoded = json_encode($errors, JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            return 'Não foi possível interpretar o retorno de erros da API.';
        }

        return mb_strlen($encoded) > 2000
            ? mb_substr($encoded, 0, 2000).'…'
            : $encoded;
    }

    private function stringFromOctalogErrorItem(mixed $item): ?string
    {
        if (is_string($item)) {
            $t = trim($item);

            return $t !== '' ? $t : null;
        }

        if (! is_array($item)) {
            return null;
        }

        foreach (['Erros', 'erros', 'Mensagem', 'mensagem', 'Message', 'message', 'Detalhe', 'detalhe'] as $key) {
            if (! array_key_exists($key, $item)) {
                continue;
            }
            $val = $item[$key];
            if (is_string($val)) {
                $t = trim($val);

                return $t !== '' ? $t : null;
            }
            if (is_array($val)) {
                $nested = $this->stringFromOctalogErrorItem($val);

                return $nested;
            }
        }

        return null;
    }
}

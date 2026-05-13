<?php

namespace App\Http\Controllers;

use App\DTOs\OctalogOrderData;
use App\Exceptions\OctalogException;
use App\Http\Requests\BulkResendPedidosOctalogRequest;
use App\Http\Requests\IndexPedidoRequest;
use App\Http\Requests\StorePedidoRequest;
use App\Models\Company;
use App\Models\Pedido;
use App\Models\ShippingLabel;
use App\Services\OctalogService;
use App\Support\PedidoOctalogOrderAssembler;
use App\Support\ThermalLabelViewData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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

    public function index(Company $company, IndexPedidoRequest $request): View
    {
        $query = Pedido::query()->where('company_id', $company->id);

        $validated = $request->validated();

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['prazo_entrega'])) {
            $query->where('id_prazo_entrega', (int) $validated['prazo_entrega']);
        }

        $search = isset($validated['search']) ? trim((string) $validated['search']) : '';
        if ($search !== '') {
            $digitsOnly = (string) preg_replace('/\D+/', '', $search);
            $query->where(function (Builder $q) use ($search, $digitsOnly) {
                $q->where('numero_pedido', 'like', '%'.$search.'%')
                    ->orWhere('numero_nf', 'like', '%'.$search.'%')
                    ->orWhere('serie_nf', 'like', '%'.$search.'%');
                if ($digitsOnly !== '' && strlen($digitsOnly) >= 4) {
                    $q->orWhere('chave_nf', 'like', '%'.$digitsOnly.'%');
                }
            });
        }

        if (! empty($validated['created_from'])) {
            $query->whereDate('created_at', '>=', $validated['created_from']);
        }
        if (! empty($validated['created_to'])) {
            $query->whereDate('created_at', '<=', $validated['created_to']);
        }

        $ordenacao = $validated['ordenacao'] ?? 'recent';
        if ($ordenacao === 'oldest') {
            $query->oldest();
        } else {
            $query->latest();
        }

        $perPage = $request->pageSize();
        $pedidos = $query->paginate($perPage)->withQueryString();

        return view('pedidos.index', [
            'company' => $company,
            'pedidos' => $pedidos,
            'hasRestrictiveFilters' => $request->hasRestrictiveFilters(),
        ]);
    }

    public function create(Company $company): View
    {
        return view('pedidos.create', compact('company'));
    }

    public function store(Company $company, StorePedidoRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $pedido = DB::transaction(function () use ($validated, $company) {
            $record = Pedido::query()->create([
                'company_id' => $company->id,
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

        $remetente = [
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
        ];

        $orderData = new OctalogOrderData(
            pedido: $pedido->numeroSomenteDigitosParaOctalog(),
            idPrazoEntrega: (int) $validated['id_prazo_entrega'],
            totalVolumes: (int) $validated['total_volumes'],
            dataVenda: null,
            dadosFiscais: $dadosFiscais,
            remetente: $remetente,
            destinatario: $destinatario,
        );

        $pedido->update([
            'destinatario_snapshot' => [
                'recipient_name' => $validated['destinatario_nome'],
                'document' => $docDest !== '' ? $docDest : null,
                'phone' => $telDest !== '' ? $telDest : null,
                'email' => $emailDest !== '' ? $emailDest : null,
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
                'label_height_mm' => 148,
                'show_qr_code' => false,
                'tracking_code' => null,
            ],
        ]);

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
                ->route('empresas.pedidos.create', $company)
                ->with('error', 'Não foi possível enviar o pedido à Octalog. '.$safeMessage);
        }

        if ($result['success'] === true) {
            /** @var array<int|string, mixed> $data */
            $data = is_array($result['data']) ? $result['data'] : [];

            $this->applySuccessfulOctalogResponse($pedido, $data);

            return redirect()
                ->route('empresas.pedidos.show', [$company, $pedido])
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
            ->route('empresas.pedidos.create', $company)
            ->with('error', 'A Octalog não aceitou o pedido. '.$flashErro);
    }

    public function show(Company $company, Pedido $pedido): View
    {
        if ($pedido->url_etiqueta) {
            ShippingLabel::query()->updateOrCreate(
                ['pedido_id' => $pedido->id, 'source' => ShippingLabel::SOURCE_OCTALOG],
                ['external_url' => $pedido->url_etiqueta]
            );
        }

        $shippingCompany = $pedido->company;
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
            'company' => $shippingCompany ?? $company,
            'pedido' => $pedido,
            'labelData' => $labelData,
            'barcodeSvg' => $barcodeSvg,
            'qrCodeSvg' => $qrCodeSvg,
            'octalogShippingLabel' => $octalogShippingLabel,
        ]);
    }

    /**
     * @param  array<int|string, mixed>  $apiData
     */
    private function applySuccessfulOctalogResponse(Pedido $pedido, array $apiData): void
    {
        $urlEtiqueta = $this->extractLabelUrl($apiData);

        $pedido->update([
            'status' => 'enviado',
            'octalog_response' => $apiData,
            'url_etiqueta' => $urlEtiqueta,
            'erro_mensagem' => null,
        ]);

        if ($urlEtiqueta) {
            ShippingLabel::query()->updateOrCreate(
                ['pedido_id' => $pedido->id, 'source' => ShippingLabel::SOURCE_OCTALOG],
                ['external_url' => $urlEtiqueta]
            );
        }
    }

    public function resendToOctalog(Company $company, Pedido $pedido): RedirectResponse
    {
        if (! in_array($pedido->status, ['enviado', 'erro'], true)) {
            return redirect()
                ->route('empresas.pedidos.show', [$company, $pedido])
                ->with('error', 'Só é possível reenviar pedidos em status enviado ou com erro à Octalog.');
        }

        $orderData = PedidoOctalogOrderAssembler::toOctalogOrderData($pedido, $company);
        if ($orderData === null) {
            return redirect()
                ->route('empresas.pedidos.show', [$company, $pedido])
                ->with('error', 'Não há dados de destinatário salvos para este pedido. Use um novo cadastro.');
        }

        try {
            $result = $this->octalogService->sendOrders([$orderData]);
        } catch (OctalogException $e) {
            $safeMessage = $e->getMessage();
            Log::error('Octalog: exceção ao reenviar pedido', [
                'pedido_id' => $pedido->id,
                'mensagem' => $safeMessage,
            ]);

            $pedido->update([
                'status' => 'erro',
                'erro_mensagem' => $safeMessage,
            ]);

            return redirect()
                ->route('empresas.pedidos.show', [$company, $pedido])
                ->with('error', 'Não foi possível reenviar à Octalog. '.$safeMessage);
        }

        if ($result['success'] === true) {
            /** @var array<int|string, mixed> $data */
            $data = is_array($result['data']) ? $result['data'] : [];

            $this->applySuccessfulOctalogResponse($pedido, $data);

            return redirect()
                ->route('empresas.pedidos.show', [$company, $pedido])
                ->with('success', 'Pedido reenviado à Octalog com sucesso.');
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
            ->route('empresas.pedidos.show', [$company, $pedido])
            ->with('error', 'A Octalog não aceitou o reenvio. '.$flashErro);
    }

    public function bulkResendToOctalog(Company $company, BulkResendPedidosOctalogRequest $request): RedirectResponse
    {
        /** @var list<int> $ids */
        $ids = array_values(array_unique(array_map('intval', $request->validated()['pedido_ids'])));

        $pedidosQuery = Pedido::query()
            ->where('company_id', $company->id)
            ->whereIn('id', $ids)
            ->orderBy('id');

        /** @var Collection<int, Pedido> $pedidos */
        $pedidos = $pedidosQuery->get();

        /** @var list<array{pedido: Pedido, dto: OctalogOrderData}> $eligiblePairs */
        $eligiblePairs = [];
        $ignoredCount = 0;

        foreach ($pedidos as $pedido) {
            if (! in_array($pedido->status, ['enviado', 'erro'], true)) {
                $ignoredCount++;

                continue;
            }
            $dto = PedidoOctalogOrderAssembler::toOctalogOrderData($pedido, $company);
            if ($dto === null) {
                $ignoredCount++;

                continue;
            }
            $eligiblePairs[] = ['pedido' => $pedido, 'dto' => $dto];
        }

        if ($eligiblePairs === []) {
            $msg = $ignoredCount > 0
                ? 'Nenhum pedido era elegível para reenvio. Pedidos devem estar com status enviado ou erro e ter dados do destinatário salvos.'
                : 'Selecione pedidos válidos para reenviar.';

            return $this->redirectToPedidosIndex($company)->with('error', $msg);
        }

        $successTotal = 0;
        $failTotal = 0;

        foreach (array_chunk($eligiblePairs, OctalogService::MAX_SALVAR_PEDIDOS) as $chunk) {
            /** @var list<array{pedido: Pedido, dto: OctalogOrderData}> $chunk */
            $dtos = array_map(static fn (array $pair) => $pair['dto'], $chunk);

            /** @var array<string, Pedido> $pedidosPorNumero */
            $pedidosPorNumero = [];
            foreach ($chunk as $pair) {
                $pedidosPorNumero[$pair['pedido']->numeroSomenteDigitosParaOctalog()] = $pair['pedido'];
            }

            try {
                $result = $this->octalogService->sendOrders($dtos);
            } catch (OctalogException $e) {
                $safeMessage = $e->getMessage();
                Log::error('Octalog: exceção no reenvio em massa', [
                    'company_id' => $company->id,
                    'pedidos_chunk' => array_map(static fn (array $pair) => $pair['pedido']->id, $chunk),
                    'mensagem' => $safeMessage,
                ]);
                foreach ($chunk as $pair) {
                    $pair['pedido']->update([
                        'status' => 'erro',
                        'erro_mensagem' => $safeMessage,
                    ]);
                }
                $failTotal += count($chunk);

                continue;
            }

            if ($result['success'] === true) {
                /** @var array<int|string, mixed> $dataRaw */
                $dataRaw = is_array($result['data']) ? $result['data'] : [];

                [$ok, $chunkFail] = $this->applyBulkResendChunkSuccess($chunk, $dataRaw, $pedidosPorNumero);
                $successTotal += $ok;
                $failTotal += $chunkFail;

                continue;
            }

            /** @var array<int|string, mixed> $errorsRaw */
            $errorsRaw = is_array($result['errors']) ? $result['errors'] : [];

            $failTotal += $this->applyBulkResendChunkFailure($chunk, $errorsRaw, $pedidosPorNumero);
        }

        return $this->redirectPedidosIndexWithBulkResendFeedback(
            $company,
            $successTotal,
            $failTotal,
            $ignoredCount,
        );
    }

    /**
     * @param  list<array{pedido: Pedido, dto: OctalogOrderData}>  $chunk
     * @param  array<string, Pedido>  $pedidosPorNumero
     * @param  array<int|string, mixed>  $apiDataRaw
     * @return array{0: int, 1: int} [successCount, failCount]
     */
    private function applyBulkResendChunkSuccess(array $chunk, array $apiDataRaw, array $pedidosPorNumero): array
    {
        $rows = $this->octalogPedidoSalvarResultRows($apiDataRaw);
        $handledNumero = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $numRaw = isset($row['Pedido']) ? trim((string) $row['Pedido']) : '';
            $key = $numRaw !== '' ? (preg_replace('/\D+/', '', $numRaw) ?: $numRaw) : '';
            if ($key === '' || ! isset($pedidosPorNumero[$key])) {
                continue;
            }
            $this->applySuccessfulOctalogResponse($pedidosPorNumero[$key], [$row]);
            $handledNumero[$key] = true;
        }

        $fallbackMsg = 'Resposta da Octalog sem dados deste pedido.';

        foreach ($chunk as $pair) {
            $num = $pair['pedido']->numeroSomenteDigitosParaOctalog();
            if (isset($handledNumero[$num])) {
                continue;
            }
            $pair['pedido']->update([
                'status' => 'erro',
                'erro_mensagem' => $fallbackMsg,
            ]);
        }

        $ok = count($handledNumero);
        $chunkSize = count($chunk);

        return [$ok, max(0, $chunkSize - $ok)];
    }

    /**
     * @param  list<array{pedido: Pedido, dto: OctalogOrderData}>  $chunk
     * @param  array<string, Pedido>  $pedidosPorNumero
     * @param  array<int|string, mixed>  $errorsPayload
     */
    private function applyBulkResendChunkFailure(array $chunk, array $errorsPayload, array $pedidosPorNumero): int
    {
        $updatedNums = [];

        /** @var list<array<string, mixed>> $errorItems */
        $errorItems = [];
        if (array_is_list($errorsPayload)) {
            foreach ($errorsPayload as $item) {
                if (is_array($item)) {
                    /** @var array<string, mixed> $item */
                    $errorItems[] = $item;
                }
            }
        } elseif ($errorsPayload !== []) {
            /** @var array<string, mixed> $errorsPayload */
            $errorItems[] = $errorsPayload;
        }

        foreach ($errorItems as $item) {
            $numRaw = isset($item['Pedido']) ? trim((string) $item['Pedido']) : '';
            $key = $numRaw !== '' ? (preg_replace('/\D+/', '', $numRaw) ?: $numRaw) : '';
            if ($key === '' || ! isset($pedidosPorNumero[$key])) {
                continue;
            }
            $pedidosPorNumero[$key]->update([
                'status' => 'erro',
                'octalog_response' => $item,
                'erro_mensagem' => $this->formatOctalogErrors([$item]),
            ]);
            $updatedNums[$key] = true;
        }

        $genericErroMsg = $this->formatOctalogErrors($errorsPayload);

        foreach ($chunk as $pair) {
            $num = $pair['pedido']->numeroSomenteDigitosParaOctalog();
            if (isset($updatedNums[$num])) {
                continue;
            }
            $pair['pedido']->update([
                'status' => 'erro',
                'octalog_response' => is_array($errorsPayload) ? $errorsPayload : [],
                'erro_mensagem' => $genericErroMsg,
            ]);
        }

        return count($chunk);
    }

    /**
     * @param  array<int|string, mixed>  $data
     * @return list<array<string, mixed>>
     */
    private function octalogPedidoSalvarResultRows(array $data): array
    {
        if ($data === []) {
            return [];
        }

        if (array_is_list($data)) {
            $filtered = [];

            foreach ($data as $row) {
                if (is_array($row)) {
                    /** @var array<string, mixed> $row */
                    $filtered[] = $row;
                }
            }

            return $filtered;
        }

        if (! is_array($data)) {
            return [];
        }

        return array_key_exists('Pedido', $data) ? [$data] : [];
    }

    private function redirectPedidosIndexWithBulkResendFeedback(
        Company $company,
        int $successTotal,
        int $failTotal,
        int $ignoredCount,
    ): RedirectResponse {
        $segments = [];

        if ($successTotal > 0) {
            $segments[] = $successTotal === 1
                ? '1 pedido reenviado com sucesso'
                : "{$successTotal} pedidos reenviados com sucesso";
        }
        if ($failTotal > 0) {
            $segments[] = $failTotal === 1
                ? '1 pedido ficou em erro na Octalog'
                : "{$failTotal} pedidos ficaram em erro na Octalog";
        }
        if ($ignoredCount > 0) {
            $segments[] = $ignoredCount === 1
                ? '1 pedido ignorado (não elegível)'
                : "{$ignoredCount} pedidos ignorados (não elegíveis)";
        }

        $message = implode('. ', $segments).'.';
        $severity = ($successTotal === 0 && $failTotal > 0) ? 'error' : 'success';

        return $this->redirectToPedidosIndex($company)->with($severity, $message);
    }

    /**
     * Lista de pedidos com os mesmos filtros da requisição atual (GET).
     * Observação: RedirectResponse não implementa withQueryString() (isso existe no paginator).
     */
    private function redirectToPedidosIndex(Company $company): RedirectResponse
    {
        $url = route('empresas.pedidos.index', [$company]);
        $qs = request()->getQueryString();
        if (is_string($qs) && $qs !== '') {
            $url .= '?'.$qs;
        }

        return redirect()->to($url);
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

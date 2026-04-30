<?php

namespace App\Http\Controllers;

use App\Exceptions\OctalogException;
use App\Http\Requests\ConsultarPedidosOctalogRequest;
use App\Models\Pedido;
use App\Services\OctalogService;
use App\Support\OctalogStatusAtividade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PedidoConsultaOctalogController extends Controller
{
    public function __construct(
        private readonly OctalogService $octalogService,
    ) {}

    public function create(): View
    {
        return view('pedidos.consulta-octalog');
    }

    public function store(ConsultarPedidosOctalogRequest $request): RedirectResponse|View
    {
        $numeros = $request->validated()['numeros'];

        try {
            $result = $this->octalogService->listOrders($numeros);
        } catch (OctalogException $e) {
            return redirect()
                ->route('pedidos.consulta-octalog.create')
                ->withInput($request->only('lista_pedidos'))
                ->with('error', 'Não foi possível consultar os pedidos na Octalog. '.$e->getMessage());
        }

        if ($result['success'] !== true) {
            $detalhe = $this->formatOctalogErrors($result['errors'] ?? []);

            return redirect()
                ->route('pedidos.consulta-octalog.create')
                ->withInput($request->only('lista_pedidos'))
                ->with('error', 'A consulta não retornou dados. '.$detalhe);
        }

        $data = is_array($result['data']) ? $result['data'] : [];

        $pedidosDb = Pedido::whereIn('numero_pedido', $numeros)->get()->keyBy('numero_pedido');

        $linhas = [];
        $totalAtualizados = 0;

        foreach ($data as $item) {
            if (! is_array($item)) {
                continue;
            }
            $pedidoCampo = $item['Pedido'] ?? '';
            $pedidoStr = is_string($pedidoCampo) || is_numeric($pedidoCampo) ? (string) $pedidoCampo : '';
            $idStatus = isset($item['IDStatus']) && is_numeric($item['IDStatus']) ? (int) $item['IDStatus'] : null;
            $statusApi = isset($item['Status']) && is_string($item['Status']) ? $item['Status'] : '';
            $dataEventoRaw = isset($item['DataEvento']) && is_string($item['DataEvento']) ? $item['DataEvento'] : '';
            $idOctalog = isset($item['ID']) && is_numeric($item['ID']) ? (int) $item['ID'] : null;

            $dataEventoExibicao = '';
            $dataEventoCarbonUtc = null;
            if ($dataEventoRaw !== '') {
                try {
                    $dataEventoCarbonUtc = Carbon::parse($dataEventoRaw)->utc();
                    $dataEventoExibicao = $dataEventoCarbonUtc->timezone((string) config('app.timezone'))->format('d/m/Y H:i');
                } catch (\Throwable) {
                    $dataEventoExibicao = $dataEventoRaw;
                }
            }

            $statusAtualizado = false;

            /** @var Pedido|null $pedido */
            $pedido = $pedidosDb->get($pedidoStr);
            if ($pedido !== null && $idStatus !== null) {
                $statusMudou = $pedido->octalog_status_id !== $idStatus;
                $idOctalogMudou = $idOctalog !== null && $pedido->octalog_id !== $idOctalog;

                if ($statusMudou || $idOctalogMudou) {
                    $pedido->octalog_status_id = $idStatus;
                    $pedido->octalog_status_text = $statusApi !== '' ? $statusApi : null;
                    $pedido->octalog_status_at = $dataEventoCarbonUtc ?? now();
                    if ($idOctalog !== null) {
                        $pedido->octalog_id = $idOctalog;
                    }
                    $pedido->save();
                    $statusAtualizado = true;
                    $totalAtualizados++;
                }
            }

            $linhas[] = [
                'Pedido' => $pedidoStr,
                'ID' => $idOctalog,
                'IDStatus' => $idStatus,
                'Status' => $statusApi,
                'DataEventoExibicao' => $dataEventoExibicao,
                'nome_catalogo' => $idStatus !== null ? OctalogStatusAtividade::labelForId($idStatus) : null,
                'status_atualizado' => $statusAtualizado,
            ];
        }

        $mensagemAtualizacao = match (true) {
            $totalAtualizados === 1 => '1 pedido atualizado no sistema.',
            $totalAtualizados > 1 => "{$totalAtualizados} pedidos atualizados no sistema.",
            default => null,
        };

        return view('pedidos.consulta-octalog', [
            'resultados' => $linhas,
            'listaPedidosConsultados' => $numeros,
            'mensagemAtualizacao' => $mensagemAtualizacao,
        ]);
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

            return mb_strlen($text) > 400 ? mb_substr($text, 0, 400).'…' : $text;
        }

        $encoded = json_encode($errors, JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            return 'Não foi possível interpretar o retorno de erros da API.';
        }

        return mb_strlen($encoded) > 400
            ? mb_substr($encoded, 0, 400).'…'
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
                return $this->stringFromOctalogErrorItem($val);
            }
        }

        return null;
    }
}

<?php

namespace App\Http\Controllers;

use App\Exceptions\OctalogException;
use App\Http\Requests\CancelPedidoSacTicketRequest;
use App\Http\Requests\StorePedidoSacTicketRequest;
use App\Models\Pedido;
use App\Services\OctalogSacService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PedidoSacTicketController extends Controller
{
    public function __construct(
        private readonly OctalogSacService $sacService,
    ) {}

    public function create(Pedido $pedido): RedirectResponse|View
    {
        if ($pedido->status !== 'enviado') {
            return redirect()
                ->route('pedidos.show', $pedido)
                ->with('error', 'Abra ticket apenas para pedidos já enviados à Octalog.');
        }

        try {
            $result = $this->sacService->listMotivos();
        } catch (OctalogException $e) {
            Log::error('Octalog SAC: falha ao listar motivos', [
                'pedido_id' => $pedido->id,
                'mensagem' => $e->getMessage(),
            ]);

            return redirect()
                ->route('pedidos.show', $pedido)
                ->with('error', 'Não foi possível carregar os motivos na Octalog. '.$e->getMessage());
        }

        if ($result['success'] !== true) {
            $msg = is_array($result['errors']) ? ($result['errors']['Descricao'] ?? $result['errors']['mensagem'] ?? null) : null;

            return redirect()
                ->route('pedidos.show', $pedido)
                ->with('error', is_string($msg) ? $msg : 'A Octalog não retornou a lista de motivos.');
        }

        /** @var list<array<string, mixed>>|mixed $motivos */
        $motivos = $result['data'];
        if (! is_array($motivos)) {
            $motivos = [];
        }

        return view('pedidos.sac.ticket-create', [
            'pedido' => $pedido,
            'motivos' => $motivos,
        ]);
    }

    public function store(StorePedidoSacTicketRequest $request, Pedido $pedido): RedirectResponse
    {
        if ($pedido->status !== 'enviado') {
            return redirect()
                ->route('pedidos.show', $pedido)
                ->with('error', 'Ticket disponível apenas para pedidos enviados.');
        }

        $payload = $request->toOctalogPayload($pedido->numero_pedido);

        try {
            $result = $this->sacService->createTicket($payload);
        } catch (OctalogException $e) {
            Log::error('Octalog SAC: falha ao criar ticket', [
                'pedido_id' => $pedido->id,
                'mensagem' => $e->getMessage(),
            ]);

            return redirect()
                ->route('pedidos.sac.ticket.create', $pedido)
                ->with('error', $e->getMessage())
                ->withInput();
        }

        if ($result['success'] !== true) {
            $err = $result['errors'];
            $msg = is_array($err) ? ($err['Descricao'] ?? $err['mensagem'] ?? null) : null;

            return redirect()
                ->route('pedidos.sac.ticket.create', $pedido)
                ->with('error', is_string($msg) ? $msg : 'A Octalog recusou a abertura do ticket.')
                ->withInput();
        }

        return redirect()
            ->route('pedidos.show', $pedido)
            ->with('success', 'Ticket registrado na Octalog.');
    }

    public function cancelCreate(Pedido $pedido): View|RedirectResponse
    {
        if ($pedido->status !== 'enviado') {
            return redirect()
                ->route('pedidos.show', $pedido)
                ->with('error', 'Cancelamento disponível apenas para pedidos enviados.');
        }

        return view('pedidos.sac.ticket-cancel', ['pedido' => $pedido]);
    }

    public function cancel(CancelPedidoSacTicketRequest $request, Pedido $pedido): RedirectResponse
    {
        if ($pedido->status !== 'enviado') {
            return redirect()
                ->route('pedidos.show', $pedido)
                ->with('error', 'Cancelamento disponível apenas para pedidos enviados.');
        }

        $payload = $request->toOctalogPayload($pedido->numero_pedido);

        try {
            $result = $this->sacService->cancelTicket($payload);
        } catch (OctalogException $e) {
            Log::error('Octalog SAC: falha ao cancelar ticket', [
                'pedido_id' => $pedido->id,
                'mensagem' => $e->getMessage(),
            ]);

            return redirect()
                ->route('pedidos.sac.ticket.cancel.create', $pedido)
                ->with('error', $e->getMessage())
                ->withInput();
        }

        if ($result['success'] !== true) {
            $err = $result['errors'];
            $msg = is_array($err) ? ($err['Descricao'] ?? $err['mensagem'] ?? null) : null;

            return redirect()
                ->route('pedidos.sac.ticket.cancel.create', $pedido)
                ->with('error', is_string($msg) ? $msg : 'A Octalog recusou o cancelamento.')
                ->withInput();
        }

        return redirect()
            ->route('pedidos.show', $pedido)
            ->with('success', 'Solicitação de cancelamento enviada à Octalog.');
    }
}

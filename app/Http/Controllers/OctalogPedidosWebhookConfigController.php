<?php

namespace App\Http\Controllers;

use App\Exceptions\OctalogException;
use App\Http\Requests\ConfigureOctalogPedidosWebhookRequest;
use App\Services\OctalogPedidosWebhookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OctalogPedidosWebhookConfigController extends Controller
{
    public function __construct(
        private readonly OctalogPedidosWebhookService $pedidosWebhookService,
    ) {}

    public function index(): View
    {
        return view('octalog.pedidos.webhook', [
            'configConsulta' => session('pedidos_webhook_config'),
            'statusAtividades' => config('octalog.status_atividades', []),
        ]);
    }

    public function update(ConfigureOctalogPedidosWebhookRequest $request): RedirectResponse
    {
        $payload = $request->toOctalogPayload();

        try {
            $result = $this->pedidosWebhookService->configureWebhook($payload);
        } catch (OctalogException $e) {
            return redirect()
                ->route('octalog.pedidos.webhook.index')
                ->with('error', $e->getMessage())
                ->withInput();
        }

        if ($result['success'] !== true) {
            $msg = is_array($result['errors']) ? ($result['errors']['mensagem'] ?? null) : null;

            return redirect()
                ->route('octalog.pedidos.webhook.index')
                ->with('error', is_string($msg) ? $msg : 'A Octalog recusou a configuração do webhook.')
                ->withInput();
        }

        $data = $result['data'];
        $mensagem = is_array($data) && isset($data['mensagem']) && is_string($data['mensagem'])
            ? $data['mensagem']
            : 'Configuração enviada com sucesso.';

        return redirect()
            ->route('octalog.pedidos.webhook.index')
            ->with('success', $mensagem);
    }

    public function consultar(): RedirectResponse
    {
        try {
            $result = $this->pedidosWebhookService->getWebhookConfiguration();
        } catch (OctalogException $e) {
            return redirect()
                ->route('octalog.pedidos.webhook.index')
                ->with('error', $e->getMessage());
        }

        if ($result['success'] !== true) {
            $msg = is_array($result['errors']) ? ($result['errors']['mensagem'] ?? null) : null;

            return redirect()
                ->route('octalog.pedidos.webhook.index')
                ->with('error', is_string($msg) ? $msg : 'Não foi possível consultar a configuração.');
        }

        /** @var array<string, mixed> $data */
        $data = is_array($result['data']) ? $result['data'] : [];

        return redirect()
            ->route('octalog.pedidos.webhook.index')
            ->with('success', 'Configuração obtida na Octalog.')
            ->with('pedidos_webhook_config', $data);
    }
}

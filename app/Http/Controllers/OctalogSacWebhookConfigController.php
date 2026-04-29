<?php

namespace App\Http\Controllers;

use App\Exceptions\OctalogException;
use App\Http\Requests\ConfigureOctalogSacWebhookRequest;
use App\Services\OctalogSacService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OctalogSacWebhookConfigController extends Controller
{
    public function __construct(
        private readonly OctalogSacService $sacService,
    ) {}

    public function index(): View
    {
        return view('octalog.sac.webhook', [
            'configConsulta' => session('sac_webhook_config'),
        ]);
    }

    public function update(ConfigureOctalogSacWebhookRequest $request): RedirectResponse
    {
        $payload = $request->toOctalogPayload();

        try {
            $result = $this->sacService->configureWebhook($payload);
        } catch (OctalogException $e) {
            return redirect()
                ->route('octalog.sac.webhook.index')
                ->with('error', $e->getMessage())
                ->withInput();
        }

        if ($result['success'] !== true) {
            $msg = is_array($result['errors']) ? ($result['errors']['mensagem'] ?? null) : null;

            return redirect()
                ->route('octalog.sac.webhook.index')
                ->with('error', is_string($msg) ? $msg : 'A Octalog recusou a configuração do webhook.')
                ->withInput();
        }

        $data = $result['data'];
        $mensagem = is_array($data) && isset($data['mensagem']) && is_string($data['mensagem'])
            ? $data['mensagem']
            : 'Configuração enviada com sucesso.';

        return redirect()
            ->route('octalog.sac.webhook.index')
            ->with('success', $mensagem);
    }

    public function consultar(): RedirectResponse
    {
        try {
            $result = $this->sacService->getWebhookConfiguration();
        } catch (OctalogException $e) {
            return redirect()
                ->route('octalog.sac.webhook.index')
                ->with('error', $e->getMessage());
        }

        if ($result['success'] !== true) {
            $msg = is_array($result['errors']) ? ($result['errors']['mensagem'] ?? null) : null;

            return redirect()
                ->route('octalog.sac.webhook.index')
                ->with('error', is_string($msg) ? $msg : 'Não foi possível consultar a configuração.');
        }

        /** @var array<string, mixed> $data */
        $data = is_array($result['data']) ? $result['data'] : [];

        return redirect()
            ->route('octalog.sac.webhook.index')
            ->with('success', 'Configuração obtida na Octalog.')
            ->with('sac_webhook_config', $data);
    }
}

@extends('layouts.app')

@section('title', 'Webhook SAC Octalog — '.config('app.name'))

@section('content')
    <x-page-header title="Configuração do webhook (Octalog SAC)" :back-href="route('pedidos.index')" back-label="Pedidos">
        <x-slot name="description">
            <div class="max-w-3xl space-y-2 text-sm leading-relaxed text-gray-600">
                <p>
                    Registre na Octalog a URL que receberá movimentações (tracking). Use HTTPS e o mesmo segredo configurado em
                    <code class="rounded bg-gray-100 px-1 py-0.5 font-mono text-xs">OCTALOG_WEBHOOK_SECRET</code>
                    no cabeçalho <code class="rounded bg-gray-100 px-1 py-0.5 font-mono text-xs">Authorization</code> durante o cadastro na Octalog.
                </p>
                <p>
                    Endpoint local de recebimento:
                    <code class="break-all rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs">{{ url('/api/octalog/webhook') }}</code>
                </p>
            </div>
        </x-slot>
    </x-page-header>

    @if (is_array($configConsulta) && $configConsulta !== [])
        <x-card class="mb-8" aria-labelledby="consulta-heading">
            <h2 id="consulta-heading" class="mb-3 text-lg font-semibold text-gray-900">Última consulta à Octalog</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex flex-wrap justify-between gap-2">
                    <dt class="text-gray-600">URL</dt>
                    <dd class="break-all font-mono text-gray-900">{{ $configConsulta['URL'] ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-600">Limite de envio</dt>
                    <dd class="font-medium text-gray-900">{{ $configConsulta['LimiteEnvio'] ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-600">Webhook ativo</dt>
                    <dd class="font-medium text-gray-900">
                        @if (! empty($configConsulta['AtivoWebhook']))
                            Sim
                        @else
                            Não
                        @endif
                    </dd>
                </div>
                <div class="flex flex-wrap justify-between gap-2">
                    <dt class="text-gray-600">Data início</dt>
                    <dd class="text-gray-900">{{ $configConsulta['DataInicioEnvio'] ?? '—' }}</dd>
                </div>
            </dl>
        </x-card>
    @endif

    <div class="grid gap-8 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <form method="post" action="{{ route('octalog.sac.webhook.update') }}" class="space-y-6">
                @csrf
                <x-card>
                <h2 class="mb-4 text-lg font-semibold text-gray-900">Enviar configuração</h2>
                <div>
                    <label for="url" class="block text-sm font-medium text-gray-700">URL (HTTPS)</label>
                    <input
                        type="url"
                        name="url"
                        id="url"
                        value="{{ old('url', url('/api/octalog/webhook')) }}"
                        required
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary"
                    />
                    @error('url')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="limite_envio" class="block text-sm font-medium text-gray-700">Limite de envio</label>
                        <input
                            type="number"
                            name="limite_envio"
                            id="limite_envio"
                            value="{{ old('limite_envio', 20) }}"
                            min="1"
                            required
                            class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                        @error('limite_envio')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="data_inicio_envio" class="block text-sm font-medium text-gray-700">Data início do envio</label>
                        <input
                            type="datetime-local"
                            name="data_inicio_envio"
                            id="data_inicio_envio"
                            value="{{ old('data_inicio_envio', now()->format('Y-m-d\TH:i')) }}"
                            required
                            class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                        @error('data_inicio_envio')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div>
                    <label for="headers_raw" class="block text-sm font-medium text-gray-700">Headers (um por linha)</label>
                    <p class="mt-0.5 text-xs text-gray-500">Ex.: <span class="font-mono">Authorization:Bearer seu_token</span></p>
                    <textarea
                        name="headers_raw"
                        id="headers_raw"
                        rows="4"
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 font-mono text-xs shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary"
                        placeholder="Authorization:Bearer {{ config('services.octalog.webhook_secret') ? '***' : 'defina OCTALOG_WEBHOOK_SECRET' }}"
                    >{{ old('headers_raw') }}</textarea>
                    @error('headers_raw')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <fieldset class="space-y-3 rounded-lg border border-gray-100 bg-gray-50/80 p-4">
                    <legend class="px-1 text-sm font-semibold text-gray-900">Contato técnico</legend>
                    <div>
                        <label for="contato_nome" class="block text-sm font-medium text-gray-700">Nome</label>
                        <input type="text" name="contato_nome" id="contato_nome" value="{{ old('contato_nome') }}" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary" />
                        @error('contato_nome')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="contato_email" class="block text-sm font-medium text-gray-700">E-mail</label>
                        <input type="email" name="contato_email" id="contato_email" value="{{ old('contato_email') }}" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary" />
                        @error('contato_email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="contato_celular" class="block text-sm font-medium text-gray-700">Celular</label>
                        <input type="text" name="contato_celular" id="contato_celular" value="{{ old('contato_celular') }}" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary" />
                        @error('contato_celular')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </fieldset>
                <x-button type="submit">
                    Salvar na Octalog
                </x-button>
                </x-card>
            </form>
        </div>
        <div>
            <form method="post" action="{{ route('octalog.sac.webhook.consultar') }}" class="block">
                @csrf
                <x-card variant="muted">
                    <h2 class="text-base font-semibold text-gray-900">Consultar configuração</h2>
                    <p class="mt-2 text-sm text-gray-600">Chama GET <span class="font-mono text-xs">/sac/webhook/configurancao</span> na Octalog com o token atual.</p>
                    <x-button variant="secondary" type="submit" class="mt-4 w-full">
                        Consultar agora
                    </x-button>
                </x-card>
            </form>
        </div>
    </div>
@endsection

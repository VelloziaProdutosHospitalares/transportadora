@extends('layouts.app')

@section('title', 'Pedido '.$pedido->numero_formatado.' — '.config('app.name'))

@section('content')
    <x-page-header title="Pedido {{ $pedido->numero_formatado }}" :back-href="route('pedidos.index')" back-label="Voltar para pedidos">
        <x-slot name="description">
            <p class="flex flex-wrap items-center gap-2">
                <span>Status:</span>
                @if ($pedido->status === 'pendente')
                    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-900">Pendente</span>
                @elseif ($pedido->status === 'enviado')
                    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-900">Enviado</span>
                @else
                    <span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-900">Erro</span>
                @endif
            </p>
        </x-slot>
    </x-page-header>

    @if ($pedido->status === 'erro' && $pedido->erro_mensagem)
        <x-alert variant="error" class="mb-6">
            <strong class="font-semibold">Erro no envio à Octalog</strong>
            <p class="mt-1 whitespace-pre-wrap break-words">{{ $pedido->erro_mensagem }}</p>
            <p class="mt-3 text-xs opacity-90">
                Corrija os dados e crie um novo pedido a partir da tela <a href="{{ route('pedidos.create') }}">Novo pedido</a>.
            </p>
        </x-alert>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <x-card aria-labelledby="resumo-heading">
            <h2 id="resumo-heading" class="mb-4 text-lg font-semibold text-gray-900">Resumo</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-600">Número interno</dt>
                    <dd class="font-medium text-gray-900">{{ $pedido->numero_formatado }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-600">Nota fiscal</dt>
                    <dd class="font-medium text-gray-900">{{ $pedido->numero_nf }} / Série {{ $pedido->serie_nf }}</dd>
                </div>
                @if ($pedido->chave_nf)
                    <div class="flex flex-col gap-1">
                        <dt class="text-gray-600">Chave NF-e</dt>
                        <dd class="break-all font-mono text-xs text-gray-900">{{ $pedido->chave_nf }}</dd>
                    </div>
                @endif
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-600">Status</dt>
                    <dd>
                        @if ($pedido->status === 'pendente')
                            <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-900">Pendente</span>
                        @elseif ($pedido->status === 'enviado')
                            <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-900">Enviado</span>
                        @else
                            <span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-900">Erro</span>
                        @endif
                    </dd>
                </div>
                @if ($pedido->status === 'enviado' && ($rotuloOctalog = $pedido->octalogStatusAtividadeLabel()))
                    <div class="flex justify-between gap-4 border-t border-gray-100 pt-3">
                        <dt class="text-gray-600">Status na Octalog</dt>
                        <dd class="max-w-[60%] text-right font-medium text-gray-900">{{ $rotuloOctalog }}</dd>
                    </div>
                @endif
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-600">Valor total</dt>
                    <dd class="font-medium text-gray-900">R$ {{ number_format((float) $pedido->valor_total, 2, ',', '.') }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-600">Volumes</dt>
                    <dd class="font-medium text-gray-900">{{ $pedido->total_volumes }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-gray-600">Prazo de entrega</dt>
                    <dd class="font-medium text-gray-900">{{ match ($pedido->id_prazo_entrega) {
                        6 => 'D+1',
                        15 => 'D+2',
                        default => $pedido->id_prazo_entrega,
                    } }}</dd>
                </div>
            </dl>
            <p class="mt-6 text-xs leading-relaxed text-gray-500">
                Os dados de entrega enviados à Octalog são guardados para geração da etiqueta térmica local após o envio com sucesso.
            </p>
        </x-card>

        <x-card aria-labelledby="etiqueta-heading">
            <h2 id="etiqueta-heading" class="mb-4 text-lg font-semibold text-gray-900">Etiqueta de envio</h2>
            @if ($pedido->url_etiqueta && $octalogShippingLabel)
                <div class="mb-4 flex flex-wrap items-center gap-3 rounded-lg border border-gray-100 bg-gray-50/80 px-3 py-2 text-sm">
                    @if ($octalogShippingLabel->isPrinted())
                        <span class="inline-flex items-center gap-1.5 text-emerald-800">
                            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-900">Etiqueta Octalog registrada como impressa</span>
                            <span class="text-gray-600">{{ $octalogShippingLabel->printed_at?->format('d/m/Y H:i') }}</span>
                        </span>
                    @else
                        <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-900">Etiqueta Octalog não marcada como impressa</span>
                        <form method="POST" action="{{ route('etiquetas.mark-printed', $octalogShippingLabel) }}" class="inline">
                            @csrf
                            <x-button type="submit" variant="secondary" class="text-xs">Marcar PDF Octalog como impressa</x-button>
                        </form>
                    @endif
                    <a href="{{ route('etiquetas.index') }}" class="ml-auto text-xs font-medium text-primary hover:underline">Ver todas as etiquetas</a>
                </div>
            @endif

            @include('pedidos.partials.shipping-label-thermal', [
                'pedido' => $pedido,
                'company' => $company,
                'labelData' => $labelData,
                'barcodeSvg' => $barcodeSvg,
                'qrCodeSvg' => $qrCodeSvg,
            ])
        </x-card>
    </div>

    @php
        $sacEventos = $pedido->octalogSacTicketWebhookEvents();
    @endphp
    <x-card class="mt-10" aria-labelledby="sac-heading">
        <h2 id="sac-heading" class="mb-4 text-lg font-semibold text-gray-900">SAC Octalog</h2>
        @if ($pedido->status === 'enviado')
            <div class="flex flex-wrap gap-3">
                <x-button href="{{ route('pedidos.sac.ticket.create', $pedido) }}">
                    Abrir ticket
                </x-button>
                <x-button variant="secondary" href="{{ route('pedidos.sac.ticket.cancel.create', $pedido) }}">
                    Cancelar ticket
                </x-button>
            </div>
        @else
            <p class="text-sm text-gray-600">Tickets SAC ficam disponíveis quando o pedido estiver com status <strong class="font-medium text-gray-800">Enviado</strong>.</p>
        @endif

        @if ($sacEventos !== [])
            <h3 class="mt-6 text-sm font-semibold text-gray-800">Eventos de ticket recebidos (webhook)</h3>
            <ul class="mt-3 space-y-4">
                @foreach ($sacEventos as $ev)
                    <li class="rounded-lg border border-gray-100 bg-gray-50/80 px-4 py-3 text-sm">
                        <p class="font-medium text-gray-900">Ticket #{{ $ev['id_ticket'] ?? '—' }} — {{ $ev['titulo'] ?? '' }}</p>
                        <p class="mt-1 text-xs text-gray-500">Recebido em {{ $ev['received_at'] ?? '—' }}</p>
                        @if (! empty($ev['descricao']))
                            <p class="mt-2 whitespace-pre-wrap text-gray-700">{{ $ev['descricao'] }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>
@endsection

@push('scripts')
    @if ($pedido->url_etiqueta)
        <script>
            (function () {
                var btn = document.getElementById('btn-imprimir-etiqueta');
                if (!btn) {
                    return;
                }
                btn.addEventListener('click', function () {
                    var url = btn.getAttribute('data-url');
                    if (url) {
                        window.open(url, '_blank', 'noopener,noreferrer');
                    }
                });
            })();
        </script>
    @endif
@endpush

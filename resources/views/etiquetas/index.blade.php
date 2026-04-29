@extends('layouts.app')

@section('title', 'Etiquetas — '.config('app.name'))

@section('content')
    <x-page-header title="Etiquetas geradas">
        <x-slot name="description">
            <p>Listagem de etiquetas Octalog e térmicas vinculadas aos pedidos, com status de impressão.</p>
        </x-slot>
    </x-page-header>

    <x-card>
        @if ($labels->isEmpty())
            <p class="text-sm text-gray-600">Nenhuma etiqueta registrada ainda. Etiquetas Octalog aparecem após envio com URL retornada; etiquetas térmicas ao visualizar a prévia na página do pedido.</p>
            <p class="mt-4">
                <x-button href="{{ route('pedidos.index') }}">Ir para pedidos</x-button>
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <th class="whitespace-nowrap py-3 pr-4">Data</th>
                            <th class="whitespace-nowrap py-3 pr-4">Pedido</th>
                            <th class="whitespace-nowrap py-3 pr-4">Tipo</th>
                            <th class="py-3 pr-4">Resumo</th>
                            <th class="whitespace-nowrap py-3 pr-4">Impressa</th>
                            <th class="whitespace-nowrap py-3 pl-2 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($labels as $label)
                            <tr class="text-gray-900">
                                <td class="whitespace-nowrap py-3 pr-4 text-gray-600">
                                    {{ $label->created_at?->format('d/m/Y H:i') }}
                                </td>
                                <td class="whitespace-nowrap py-3 pr-4">
                                    <a href="{{ route('pedidos.show', $label->pedido) }}" class="font-medium text-primary hover:underline">
                                        {{ $label->pedido->numero_formatado }}
                                    </a>
                                </td>
                                <td class="whitespace-nowrap py-3 pr-4">
                                    @if ($label->source === \App\Models\ShippingLabel::SOURCE_OCTALOG)
                                        <span class="inline-flex rounded-full bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-900">Octalog</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-violet-100 px-2 py-0.5 text-xs font-medium text-violet-900">Térmica</span>
                                    @endif
                                </td>
                                <td class="max-w-xs truncate py-3 pr-4 text-gray-700" title="{{ $label->summaryLine() }}">
                                    {{ $label->summaryLine() }}
                                </td>
                                <td class="whitespace-nowrap py-3 pr-4">
                                    @if ($label->isPrinted())
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-900">Sim</span>
                                        <span class="mt-0.5 block text-xs text-gray-500">{{ $label->printed_at?->format('d/m/Y H:i') }}</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-900">Não</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap py-3 pl-2 text-right">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        @if ($label->source === \App\Models\ShippingLabel::SOURCE_OCTALOG && $label->external_url)
                                            <x-button variant="secondary" href="{{ $label->external_url }}" target="_blank" rel="noopener noreferrer" class="text-xs">
                                                Abrir
                                            </x-button>
                                        @endif
                                        @if (! $label->isPrinted())
                                            <form method="POST" action="{{ route('etiquetas.mark-printed', $label) }}" class="inline">
                                                @csrf
                                                <x-button type="submit" class="text-xs">Marcar impressa</x-button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $labels->links() }}
            </div>
        @endif
    </x-card>
@endsection

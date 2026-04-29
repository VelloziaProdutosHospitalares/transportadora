@extends('layouts.app')

@section('title', 'Pedidos — '.config('app.name'))

@section('content')
    <x-page-header title="Pedidos">
        <x-slot name="description">
            <p>
                Acompanhe envios à Octalog, status e etiquetas. Para incluir uma nota, use
                <span class="font-medium text-gray-800">Novo pedido</span>.
            </p>
        </x-slot>
        <x-slot name="actions">
            <div class="flex w-full flex-wrap gap-2 sm:w-auto sm:justify-end">
                <x-button variant="secondary" href="{{ route('pedidos.consulta-octalog.create') }}">
                    Consultar status Octalog
                </x-button>
                <x-button href="{{ route('pedidos.create') }}">
                    Novo pedido
                </x-button>
            </div>
        </x-slot>
    </x-page-header>

    <p class="mb-2 text-xs text-gray-500 md:hidden" role="note">
        Dica: na tela pequena, arraste a tabela lateralmente para ver todas as colunas.
    </p>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="-mx-px overflow-x-auto overscroll-x-contain">
            <table class="min-w-[36rem] w-full divide-y divide-gray-200 text-left text-sm md:min-w-0" aria-describedby="pedidos-table-summary">
                <caption id="pedidos-table-summary" class="sr-only">
                    Lista de pedidos com número, nota fiscal, status, valor, data e ação para detalhes.
                </caption>
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-semibold text-gray-700">Número</th>
                        <th scope="col" class="px-4 py-3 font-semibold text-gray-700">NF</th>
                        <th scope="col" class="px-4 py-3 font-semibold text-gray-700">Status</th>
                        <th scope="col" class="px-4 py-3 font-semibold text-gray-700">Valor</th>
                        <th scope="col" class="px-4 py-3 font-semibold text-gray-700">Criado em</th>
                        <th scope="col" class="px-4 py-3 font-semibold text-gray-700"><span class="sr-only">Ações</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($pedidos as $pedido)
                        <tr class="transition-colors hover:bg-gray-50/80">
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900">{{ $pedido->numero_formatado }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-700">{{ $pedido->numero_nf }} / {{ $pedido->serie_nf }}</td>
                            <td class="max-w-[14rem] px-4 py-3">
                                <div class="flex flex-col items-start gap-1">
                                    @if ($pedido->status === 'pendente')
                                        <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-900">Pendente</span>
                                    @elseif ($pedido->status === 'enviado')
                                        <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-900">Enviado</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-900">Erro</span>
                                    @endif
                                    @if ($pedido->status === 'enviado' && ($rotuloOctalog = $pedido->octalogStatusAtividadeLabel()))
                                        <span class="line-clamp-2 text-xs text-gray-600" title="{{ $rotuloOctalog }}">{{ $rotuloOctalog }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-700">R$ {{ number_format((float) $pedido->valor_total, 2, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-600">{{ $pedido->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <a
                                    href="{{ route('pedidos.show', $pedido) }}"
                                    class="rounded-sm font-medium text-primary underline-offset-2 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                                >
                                    Ver / etiqueta
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-14 text-center">
                                <p class="text-sm font-medium text-gray-900">Nenhum pedido ainda</p>
                                <p class="mt-1 text-sm text-gray-600">Crie o primeiro pedido informando a NF-e e o destinatário.</p>
                                <div class="mt-5 flex justify-center">
                                    <x-button href="{{ route('pedidos.create') }}">
                                        Novo pedido
                                    </x-button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($pedidos->hasPages())
        <div class="mt-6">
            {{ $pedidos->links() }}
        </div>
    @endif
@endsection

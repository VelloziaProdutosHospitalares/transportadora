@extends('layouts.app')

@section('title', 'Pedidos — '.config('app.name'))

@section('content')
    <x-page-header title="Pedidos — {{ $company->trade_name }}">
        <x-slot name="description">
            <p>
                Acompanhe envios à Octalog, status e etiquetas desta empresa. Para incluir uma nota, use
                <span class="font-medium text-gray-800">Novo pedido</span>.
            </p>
        </x-slot>
        <x-slot name="actions">
            <div class="flex w-full flex-wrap gap-2 sm:w-auto sm:justify-end">
                <x-button variant="secondary" href="{{ route('empresas.consulta_octalog.create', $company) }}">
                    Consultar status Octalog
                </x-button>
                <x-button variant="ghost" href="{{ route('empresas.index') }}">
                    Voltar às empresas
                </x-button>
                <x-button href="{{ route('empresas.pedidos.create', $company) }}">
                    Novo pedido
                </x-button>
            </div>
        </x-slot>
    </x-page-header>

    <div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
        <h2 id="filtros-pedidos-heading" class="text-sm font-semibold text-gray-900">Filtros e busca</h2>
        <p id="filtros-pedidos-descricao" class="mt-1 text-xs text-gray-600">
            Refine pela situação, prazo ou texto (nº do pedido, NF ou trecho da chave da NF-e). Use período opcional pela data de criação no sistema.
        </p>

        @if ($errors->any())
            <x-alert variant="error" class="mt-4">
                <p class="font-medium">Revise os campos destacados:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $erro)
                        <li>{{ $erro }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <form
            method="GET"
            action="{{ route('empresas.pedidos.index', $company) }}"
            class="mt-4 space-y-4"
            aria-labelledby="filtros-pedidos-heading"
            aria-describedby="filtros-pedidos-descricao"
        >
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
                <div class="sm:col-span-2 lg:col-span-2">
                    <label for="filtro-busca-pedidos" class="mb-1 block text-sm font-medium text-gray-700">Busca</label>
                    <input
                        type="search"
                        name="search"
                        id="filtro-busca-pedidos"
                        value="{{ old('search', request('search')) }}"
                        maxlength="120"
                        placeholder="Pedido, NF ou chave NF-e"
                        autocomplete="off"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-base shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 sm:text-sm"
                    />
                </div>
                <div>
                    <label for="filtro-status-pedidos" class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                    <select
                        name="status"
                        id="filtro-status-pedidos"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    >
                        <option value="">Todos</option>
                        <option value="pendente" @selected(old('status', request('status')) === 'pendente')>Pendente</option>
                        <option value="enviado" @selected(old('status', request('status')) === 'enviado')>Enviado</option>
                        <option value="erro" @selected(old('status', request('status')) === 'erro')>Erro</option>
                    </select>
                </div>
                <div>
                    <label for="filtro-prazo-pedidos" class="mb-1 block text-sm font-medium text-gray-700">Prazo</label>
                    <select
                        name="prazo_entrega"
                        id="filtro-prazo-pedidos"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    >
                        <option value="">Todos</option>
                        <option value="6" @selected(old('prazo_entrega', request('prazo_entrega')) === '6')>D+1</option>
                        <option value="15" @selected(old('prazo_entrega', request('prazo_entrega')) === '15')>D+2</option>
                    </select>
                </div>
                <div>
                    <label for="filtro-created-from-pedidos" class="mb-1 block text-sm font-medium text-gray-700">Criado de</label>
                    <input
                        type="date"
                        name="created_from"
                        id="filtro-created-from-pedidos"
                        value="{{ old('created_from', request('created_from')) }}"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    />
                    @error('created_from')
                        <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="filtro-created-to-pedidos" class="mb-1 block text-sm font-medium text-gray-700">Criado até</label>
                    <input
                        type="date"
                        name="created_to"
                        id="filtro-created-to-pedidos"
                        value="{{ old('created_to', request('created_to')) }}"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    />
                    @error('created_to')
                        <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label for="filtro-ordenacao-pedidos" class="mb-1 block text-sm font-medium text-gray-700">Ordenar por</label>
                    <select
                        name="ordenacao"
                        id="filtro-ordenacao-pedidos"
                        class="w-full min-w-[12rem] rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    >
                        <option value="recent" @selected(old('ordenacao', request('ordenacao', 'recent')) === 'recent')>Mais recentes</option>
                        <option value="oldest" @selected(old('ordenacao', request('ordenacao')) === 'oldest')>Mais antigos</option>
                    </select>
                </div>
                <div>
                    <label for="filtro-per-page-pedidos" class="mb-1 block text-sm font-medium text-gray-700">Por página</label>
                    <select
                        name="per_page"
                        id="filtro-per-page-pedidos"
                        class="w-full min-w-[7rem] rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    >
                        @foreach ([10, 15, 25, 50] as $n)
                            <option value="{{ $n }}" @selected((int) old('per_page', request('per_page', '15')) === $n)>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-wrap gap-2 pt-1">
                    <x-button type="submit">Filtrar</x-button>
                    <x-button variant="ghost" href="{{ route('empresas.pedidos.index', $company) }}">
                        Limpar
                    </x-button>
                </div>
            </div>
        </form>
    </div>

    <p class="mb-2 text-xs text-gray-500 md:hidden" role="note">
        Dica: na tela pequena, arraste a tabela lateralmente para ver todas as colunas.
    </p>

    @if ($pedidos->total() > 0)
        <p class="mb-3 text-sm text-gray-600">
            Exibindo
            <span class="font-medium text-gray-900">{{ $pedidos->firstItem() }}</span>
            –
            <span class="font-medium text-gray-900">{{ $pedidos->lastItem() }}</span>
            de
            <span class="font-medium text-gray-900">{{ $pedidos->total() }}</span>
            pedido(s).
        </p>
    @endif

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
                                    href="{{ route('empresas.pedidos.show', [$company, $pedido]) }}"
                                    class="rounded-sm font-medium text-primary underline-offset-2 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                                >
                                    Ver / etiqueta
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-14 text-center">
                                @if ($hasRestrictiveFilters ?? false)
                                    <p class="text-sm font-medium text-gray-900">Nenhum pedido encontrado</p>
                                    <p class="mt-1 text-sm text-gray-600">Ajuste os filtros ou limpe-os para ver a lista inteira.</p>
                                    <div class="mt-5 flex justify-center">
                                        <x-button variant="secondary" href="{{ route('empresas.pedidos.index', $company) }}">
                                            Limpar filtros
                                        </x-button>
                                    </div>
                                @else
                                    <p class="text-sm font-medium text-gray-900">Nenhum pedido ainda</p>
                                    <p class="mt-1 text-sm text-gray-600">Crie o primeiro pedido informando a NF-e e o destinatário.</p>
                                    <div class="mt-5 flex justify-center">
                                        <x-button href="{{ route('empresas.pedidos.create', $company) }}">
                                            Novo pedido
                                        </x-button>
                                    </div>
                                @endif
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

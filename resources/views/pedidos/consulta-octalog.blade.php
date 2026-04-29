@extends('layouts.app')

@section('title', 'Consulta de pedidos — '.config('app.name'))

@section('content')
    <x-page-header title="Consultar status na Octalog" :back-href="route('pedidos.index')" back-label="Voltar para pedidos">
        <x-slot name="description">
            <p>
                Envie os <strong class="font-medium text-gray-800">números dos pedidos</strong> (como aparecem no sistema, por exemplo
                <code class="rounded bg-gray-100 px-1 py-0.5 text-xs font-mono text-gray-800">PED-20260429-00001</code>).
                Consulta até <strong class="font-medium text-gray-800">100</strong> pedidos por requisição — é o mesmo contrato da API
                <span class="font-mono text-xs text-gray-500">POST /pedido/listar</span>.
            </p>
        </x-slot>
    </x-page-header>

    @if ($errors->any())
        <x-alert variant="error" class="mb-6">
            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $er)
                    <li>{{ $er }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    <form method="POST" action="{{ route('pedidos.consulta-octalog.store') }}" class="mb-10 space-y-4">
        @csrf
        <div>
            <label for="lista_pedidos" class="mb-1 block text-sm font-medium text-gray-800">Lista de números de pedido</label>
            <textarea
                name="lista_pedidos"
                id="lista_pedidos"
                rows="8"
                required
                class="min-h-[11rem] w-full rounded-lg border border-gray-300 px-4 py-3 font-mono text-base leading-relaxed focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 sm:text-sm"
                placeholder="{{ "PED-20260429-00001\nPED-20260429-00002\n954595" }}"
            >{{ old('lista_pedidos') }}</textarea>
            @error('lista_pedidos')
                <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p>
            @enderror
            <p class="mt-2 text-xs text-gray-500">Separe por linha, vírgula ou ponto e vírgula. Duplicados são ignorados.</p>
        </div>
        <x-button type="submit">
            Consultar na Octalog
        </x-button>
    </form>

    @isset($listaPedidosConsultados)
        @if (! empty($resultados))
            <x-card aria-labelledby="result-heading">
                <h2 id="result-heading" class="mb-4 text-lg font-semibold text-gray-900">Resultado</h2>
                <p class="mb-4 text-xs text-gray-500">
                    {{ count($listaPedidosConsultados) }} pedido(s) solicitados · {{ count($resultados) }} registro(s) retornado(s)
                </p>
                <div class="-mx-px overflow-x-auto">
                    <table class="min-w-[44rem] w-full divide-y divide-gray-200 text-left text-sm" aria-describedby="consulta-result-summary">
                        <caption id="consulta-result-summary" class="sr-only">
                            Status atual conforme Octalog por pedido: identificadores, status textual e data do evento.
                        </caption>
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="whitespace-nowrap px-3 py-2 font-semibold text-gray-700">Pedido</th>
                                <th scope="col" class="whitespace-nowrap px-3 py-2 font-semibold text-gray-700">ID Octalog</th>
                                <th scope="col" class="whitespace-nowrap px-3 py-2 font-semibold text-gray-700">ID status</th>
                                <th scope="col" class="min-w-[8rem] px-3 py-2 font-semibold text-gray-700">Status (API)</th>
                                <th scope="col" class="min-w-[8rem] px-3 py-2 font-semibold text-gray-700">Catálogo</th>
                                <th scope="col" class="whitespace-nowrap px-3 py-2 font-semibold text-gray-700">Data evento</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($resultados as $row)
                                <tr class="align-top">
                                    <td class="whitespace-nowrap px-3 py-2 font-mono text-xs text-gray-900">{{ $row['Pedido'] }}</td>
                                    <td class="whitespace-nowrap px-3 py-2 text-gray-700">{{ $row['ID'] ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-3 py-2 text-gray-700">{{ $row['IDStatus'] ?? '—' }}</td>
                                    <td class="px-3 py-2 text-gray-800">{{ $row['Status'] }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ $row['nome_catalogo'] ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-3 py-2 text-gray-700">
                                        {{ ($row['DataEventoExibicao'] ?? '') !== '' ? $row['DataEventoExibicao'] : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        @else
            <div
                class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm leading-relaxed text-gray-800 shadow-sm"
                role="status"
                aria-live="polite"
            >
                <p class="font-medium text-gray-900">Nenhum dado retornado pela Octalog</p>
                <p class="mt-2 text-gray-700">
                    A consulta foi concluída, mas a API não devolveu registros para os
                    <strong class="font-medium text-gray-900">{{ count($listaPedidosConsultados) }}</strong>
                    pedido(s) informados. Confira se os números estão corretos e se esses pedidos existem na integração.
                </p>
            </div>
        @endif
    @endisset
@endsection

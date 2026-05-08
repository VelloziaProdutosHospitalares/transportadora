@extends('layouts.app')

@section('title', 'Empresas — '.config('app.name'))

@section('content')
    <x-page-header title="Empresas">
        <x-slot name="description">
            <p>
                Escolha uma empresa para gerenciar <strong class="font-medium text-gray-800">pedidos e etiquetas</strong> só dela ou cadastre uma nova empresa remetente.
            </p>
        </x-slot>
        <x-slot name="actions">
            <x-button href="{{ route('empresas.create') }}">Nova empresa</x-button>
        </x-slot>
    </x-page-header>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="-mx-px overflow-x-auto">
            <table class="min-w-[28rem] w-full divide-y divide-gray-200 text-left text-sm" aria-describedby="companies-summary">
                <caption id="companies-summary" class="sr-only">
                    Lista de empresas com nome fantasia, CNPJ e acesso a pedidos.
                </caption>
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-semibold text-gray-700">Nome fantasia</th>
                        <th scope="col" class="px-4 py-3 font-semibold text-gray-700">CNPJ</th>
                        <th scope="col" class="px-4 py-3 font-semibold text-gray-700"><span class="sr-only">Ações</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($companies as $companyRow)
                        <tr class="transition-colors hover:bg-gray-50/80">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $companyRow->trade_name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-700">{{ $companyRow->cnpj }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <div class="flex flex-wrap justify-end gap-3">
                                    <a
                                        href="{{ route('empresas.pedidos.index', $companyRow) }}"
                                        class="rounded-sm font-medium text-primary underline-offset-2 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                                    >
                                        Pedidos
                                    </a>
                                    <a
                                        href="{{ route('empresas.edit', $companyRow) }}"
                                        class="rounded-sm font-medium text-primary underline-offset-2 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                                    >
                                        Dados cadastrais
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-14 text-center">
                                <p class="text-sm font-medium text-gray-900">Nenhuma empresa cadastrada</p>
                                <p class="mt-1 text-sm text-gray-600">Cadastre ao menos uma empresa como remetente nas etiquetas e envios Octalog.</p>
                                <div class="mt-5 flex justify-center">
                                    <x-button href="{{ route('empresas.create') }}">Nova empresa</x-button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($companies->hasPages())
        <div class="mt-6">
            {{ $companies->links() }}
        </div>
    @endif
@endsection

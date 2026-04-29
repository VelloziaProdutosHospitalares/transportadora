@extends('layouts.app')

@section('title', 'Cancelar ticket SAC — '.config('app.name'))

@section('content')
    <x-page-header title="Cancelar ticket (Octalog)" :back-href="route('pedidos.show', $pedido)" :back-label="'Pedido '.$pedido->numero_formatado">
        <x-slot name="description">
            <p>
                Apenas chamados abertos pela API e não finalizados. Motivo &quot;Pedido Cancelado&quot; não pode ser cancelado pela API.
            </p>
        </x-slot>
    </x-page-header>

    <form method="post" action="{{ route('pedidos.sac.ticket.cancel', $pedido) }}" class="mx-auto max-w-2xl space-y-6">
        @csrf
        @method('DELETE')
        <x-card>
            <div>
                <label for="id_motivo" class="block text-sm font-medium text-gray-700">IDMotivo</label>
                <input type="number" name="id_motivo" id="id_motivo" value="{{ old('id_motivo', 0) }}" min="0" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-base shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary sm:text-sm" />
                @error('id_motivo')
                    <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="descricao" class="block text-sm font-medium text-gray-700">Descrição</label>
                <textarea name="descricao" id="descricao" rows="4" maxlength="1200" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-base shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary sm:text-sm">{{ old('descricao') }}</textarea>
                @error('descricao')
                    <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex flex-wrap gap-3 pt-2">
                <x-button variant="danger" type="submit">
                    Enviar cancelamento
                </x-button>
                <x-button variant="ghost" href="{{ route('pedidos.show', $pedido) }}">
                    Voltar
                </x-button>
            </div>
        </x-card>
    </form>
@endsection

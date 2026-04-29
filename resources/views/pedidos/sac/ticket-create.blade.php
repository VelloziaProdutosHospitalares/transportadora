@extends('layouts.app')

@section('title', 'Abrir ticket SAC — '.config('app.name'))

@section('content')
    <x-page-header title="Abrir ticket na Octalog" :back-href="route('pedidos.show', $pedido)" :back-label="'Pedido '.$pedido->numero_formatado">
        <x-slot name="description">
            <p>Pedido <strong class="font-medium text-gray-800">{{ $pedido->numero_formatado }}</strong></p>
        </x-slot>
    </x-page-header>

    <form method="post" action="{{ route('pedidos.sac.ticket.store', $pedido) }}" class="mx-auto max-w-2xl space-y-6">
        @csrf
        <x-card>
            <div>
                <label for="id_motivo" class="block text-sm font-medium text-gray-700">Motivo</label>
                <select name="id_motivo" id="id_motivo" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-base shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary sm:text-sm">
                    <option value="">Selecione…</option>
                    @foreach ($motivos as $m)
                        @php
                            $id = $m['IDMotivo'] ?? $m['id_motivo'] ?? null;
                            $desc = $m['Descricao'] ?? $m['descricao'] ?? '';
                        @endphp
                        @if (is_numeric($id))
                            <option value="{{ (int) $id }}" @selected((string) old('id_motivo') === (string) $id)>{{ $desc }} ({{ (int) $id }})</option>
                        @endif
                    @endforeach
                </select>
                @error('id_motivo')
                    <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="titulo" class="block text-sm font-medium text-gray-700">Título (máx. 150)</label>
                <input type="text" name="titulo" id="titulo" value="{{ old('titulo') }}" maxlength="150" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-base shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary sm:text-sm" />
                @error('titulo')
                    <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="descricao" class="block text-sm font-medium text-gray-700">Descrição (máx. 1200)</label>
                <textarea name="descricao" id="descricao" rows="6" maxlength="1200" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-base shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary sm:text-sm">{{ old('descricao') }}</textarea>
                @error('descricao')
                    <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="comentario_motorista" class="block text-sm font-medium text-gray-700">Comentário motorista (opcional, máx. 150)</label>
                <input type="text" name="comentario_motorista" id="comentario_motorista" value="{{ old('comentario_motorista') }}" maxlength="150" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-base shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary sm:text-sm" />
                @error('comentario_motorista')
                    <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex flex-wrap gap-3 pt-2">
                <x-button type="submit">
                    Enviar à Octalog
                </x-button>
                <x-button variant="ghost" href="{{ route('pedidos.show', $pedido) }}">
                    Cancelar
                </x-button>
            </div>
        </x-card>
    </form>
@endsection

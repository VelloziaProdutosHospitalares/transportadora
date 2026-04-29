@extends('layouts.app')

@section('title', 'Empresa — '.config('app.name'))

@section('content')
    <x-page-header title="Cadastro da empresa">
        <x-slot name="description">
            <p>Preencha os dados do remetente usados na geração de etiquetas de envio.</p>
        </x-slot>
    </x-page-header>

    @if ($errors->any())
        <x-alert variant="error" class="mb-6">
            Verifique os campos em destaque para continuar.
        </x-alert>
    @endif

    @php
        $isUpdate = $company !== null;
        $action = $isUpdate ? route('empresa.update') : route('empresa.store');
    @endphp

    <x-card>
        <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @if ($isUpdate)
                @method('PUT')
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="legal_name" class="mb-1 block text-sm font-medium text-gray-700">Razão social</label>
                    <input type="text" name="legal_name" id="legal_name" value="{{ old('legal_name', $company?->legal_name) }}" required class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" />
                    @error('legal_name') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="trade_name" class="mb-1 block text-sm font-medium text-gray-700">Nome fantasia</label>
                    <input type="text" name="trade_name" id="trade_name" value="{{ old('trade_name', $company?->trade_name) }}" required class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" />
                    @error('trade_name') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="cnpj" class="mb-1 block text-sm font-medium text-gray-700">CNPJ</label>
                    <input type="text" name="cnpj" id="cnpj" value="{{ old('cnpj', $company?->cnpj) }}" required placeholder="00.000.000/0000-00" class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" />
                    @error('cnpj') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="state_registration" class="mb-1 block text-sm font-medium text-gray-700">Inscrição estadual (opcional)</label>
                    <input type="text" name="state_registration" id="state_registration" value="{{ old('state_registration', $company?->state_registration) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" />
                </div>

                <div>
                    <label for="phone" class="mb-1 block text-sm font-medium text-gray-700">Telefone</label>
                    <input type="tel" name="phone" id="phone" value="{{ old('phone', $company?->phone) }}" required placeholder="(11) 99999-9999" class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" />
                    @error('phone') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="mb-1 block text-sm font-medium text-gray-700">E-mail</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $company?->email) }}" required class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" />
                    @error('email') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="contract" class="mb-1 block text-sm font-medium text-gray-700">Contrato</label>
                    <input type="text" name="contract" id="contract" value="{{ old('contract', $company?->contract) }}" required class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" />
                    @error('contract') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="administrative_code" class="mb-1 block text-sm font-medium text-gray-700">Código administrativo</label>
                    <input type="text" name="administrative_code" id="administrative_code" value="{{ old('administrative_code', $company?->administrative_code) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" />
                </div>

                <div>
                    <label for="postal_code" class="mb-1 block text-sm font-medium text-gray-700">CEP</label>
                    <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $company?->postal_code) }}" required placeholder="00000-000" class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" />
                    @error('postal_code') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="street" class="mb-1 block text-sm font-medium text-gray-700">Logradouro</label>
                    <input type="text" name="street" id="street" value="{{ old('street', $company?->street) }}" required class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" />
                    @error('street') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="number" class="mb-1 block text-sm font-medium text-gray-700">Número</label>
                    <input type="text" name="number" id="number" value="{{ old('number', $company?->number) }}" required class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" />
                    @error('number') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="complement" class="mb-1 block text-sm font-medium text-gray-700">Complemento (opcional)</label>
                    <input type="text" name="complement" id="complement" value="{{ old('complement', $company?->complement) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" />
                </div>

                <div>
                    <label for="district" class="mb-1 block text-sm font-medium text-gray-700">Bairro</label>
                    <input type="text" name="district" id="district" value="{{ old('district', $company?->district) }}" required class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" />
                    @error('district') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="city" class="mb-1 block text-sm font-medium text-gray-700">Cidade</label>
                    <input type="text" name="city" id="city" value="{{ old('city', $company?->city) }}" required class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" />
                    @error('city') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="state" class="mb-1 block text-sm font-medium text-gray-700">UF</label>
                    <input type="text" name="state" id="state" maxlength="2" value="{{ old('state', $company?->state) }}" required class="w-full rounded-md border border-gray-300 px-3 py-2 uppercase focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" />
                    @error('state') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="logo" class="mb-1 block text-sm font-medium text-gray-700">Logo da empresa (PNG/JPG/WEBP)</label>
                    <input type="file" name="logo" id="logo" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" />
                    @error('logo') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                    @if ($company?->logo_path)
                        <div class="mt-3">
                            <p class="mb-2 text-xs text-gray-500">Logo atual:</p>
                            <img src="{{ route('empresa.logo') }}" alt="Logo da empresa" class="h-16 w-auto rounded border border-gray-300 bg-white p-1" />
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex justify-end">
                <x-button type="submit">Salvar dados da empresa</x-button>
            </div>
        </form>
    </x-card>
@endsection

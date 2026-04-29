@extends('layouts.app')

@section('title', 'Novo pedido — '.config('app.name'))

@section('content')
    <x-page-header title="Novo pedido" :back-href="route('pedidos.index')" back-label="Voltar para pedidos">
        <x-slot name="description">
            <p>
                Fluxo sugerido:
                <span class="font-medium text-gray-800">1)</span> bipar ou colar a chave da NF-e (44 dígitos) para pré-preencher via SERPRO;
                <span class="font-medium text-gray-800">2)</span> conferir valores;
                <span class="font-medium text-gray-800">3)</span> completar dados do destinatário;
                <span class="font-medium text-gray-800">4)</span> enviar à Octalog.
            </p>
        </x-slot>
    </x-page-header>

    <form id="form-novo-pedido" method="POST" action="{{ route('pedidos.store') }}" class="space-y-8" novalidate>
        @csrf

        <x-card variant="highlight" aria-labelledby="secao-nota-heading">
            <h2 id="secao-nota-heading" class="mb-1 text-lg font-semibold text-amber-950">Nota fiscal</h2>
            <p class="mb-4 text-sm text-amber-900/80">Dados fiscais enviados à Octalog junto com o pedido.</p>
            <div class="space-y-4">
                <div>
                    <label for="chave_nf" class="mb-1 block text-sm font-medium text-gray-800">Chave da NF-e (bipe ou digite)</label>
                    <input
                        type="text"
                        name="chave_nf"
                        id="chave_nf"
                        value="{{ old('chave_nf') }}"
                        maxlength="54"
                        inputmode="numeric"
                        pattern="\d*"
                        autocomplete="off"
                        aria-describedby="chave-nf-ajuda serpro-lookup-status"
                        autofocus
                        placeholder="44 dígitos"
                        class="w-full rounded-lg border border-amber-200 bg-white px-4 py-3 text-lg tracking-wide focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:opacity-80"
                    />
                    @error('chave_nf')
                        <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p>
                    @enderror
                    <p id="serpro-lookup-status" class="mt-2 hidden text-sm" role="status" aria-live="polite"></p>
                    <p id="chave-nf-ajuda" class="mt-1 text-xs text-gray-600">
                        A chave de acesso tem <strong class="font-medium text-gray-800">exatamente 44 dígitos</strong>. Com a integração SERPRO ativa, ao completá-los ou colar a chave (espaços são ignorados), os dados podem ser preenchidos automaticamente nos campos vazios.
                    </p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label for="numero_nf" class="mb-1 block text-sm font-medium text-gray-700">Número da NF</label>
                        <input
                            type="text"
                            name="numero_nf"
                            id="numero_nf"
                            value="{{ old('numero_nf') }}"
                            required
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                        />
                        @error('numero_nf')
                            <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="serie_nf" class="mb-1 block text-sm font-medium text-gray-700">Série</label>
                        <input
                            type="text"
                            name="serie_nf"
                            id="serie_nf"
                            value="{{ old('serie_nf') }}"
                            required
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                        />
                        @error('serie_nf')
                            <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="valor_total" class="mb-1 block text-sm font-medium text-gray-700">Valor total (R$)</label>
                        <input
                            type="text"
                            name="valor_total"
                            id="valor_total"
                            value="{{ old('valor_total') }}"
                            required
                            inputmode="decimal"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                        />
                        @error('valor_total')
                            <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="total_volumes" class="mb-1 block text-sm font-medium text-gray-700">Total de volumes</label>
                        <input
                            type="number"
                            name="total_volumes"
                            id="total_volumes"
                            value="{{ old('total_volumes', 1) }}"
                            min="1"
                            required
                            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                        />
                        @error('total_volumes')
                            <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="id_prazo_entrega" class="mb-1 block text-sm font-medium text-gray-700">Prazo de entrega</label>
                        <select
                            name="id_prazo_entrega"
                            id="id_prazo_entrega"
                            required
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                        >
                            <option value="6" @selected((string) old('id_prazo_entrega', '6') === '6')>D+1</option>
                            <option value="15" @selected((string) old('id_prazo_entrega') === '15')>D+2</option>
                        </select>
                        @error('id_prazo_entrega')
                            <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </x-card>

        <x-card aria-labelledby="secao-dest-heading">
            <h2 id="secao-dest-heading" class="mb-1 text-lg font-semibold text-gray-900">Destinatário</h2>
            <p class="mb-4 text-sm text-gray-600">Endereço de entrega conforme a NF-e ou ajustado manualmente.</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="destinatario_nome" class="mb-1 block text-sm font-medium text-gray-700">Nome</label>
                    <input
                        type="text"
                        name="destinatario_nome"
                        id="destinatario_nome"
                        value="{{ old('destinatario_nome') }}"
                        required
                        autocomplete="name"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    />
                    @error('destinatario_nome')
                        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="destinatario_documento" class="mb-1 block text-sm font-medium text-gray-700">Documento</label>
                    <input
                        type="text"
                        name="destinatario_documento"
                        id="destinatario_documento"
                        value="{{ old('destinatario_documento') }}"
                        autocomplete="off"
                        inputmode="numeric"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    />
                    @error('destinatario_documento')
                        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="destinatario_cep" class="mb-1 block text-sm font-medium text-gray-700">CEP</label>
                    <input
                        type="text"
                        name="destinatario_cep"
                        id="destinatario_cep"
                        value="{{ old('destinatario_cep') }}"
                        maxlength="9"
                        required
                        autocomplete="postal-code"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    />
                    @error('destinatario_cep')
                        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="destinatario_endereco" class="mb-1 block text-sm font-medium text-gray-700">Endereço</label>
                    <input
                        type="text"
                        name="destinatario_endereco"
                        id="destinatario_endereco"
                        value="{{ old('destinatario_endereco') }}"
                        required
                        autocomplete="address-line1"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    />
                    @error('destinatario_endereco')
                        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="destinatario_numero" class="mb-1 block text-sm font-medium text-gray-700">Número</label>
                    <input
                        type="text"
                        name="destinatario_numero"
                        id="destinatario_numero"
                        value="{{ old('destinatario_numero') }}"
                        required
                        autocomplete="address-line2"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    />
                    @error('destinatario_numero')
                        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="destinatario_bairro" class="mb-1 block text-sm font-medium text-gray-700">Bairro</label>
                    <input
                        type="text"
                        name="destinatario_bairro"
                        id="destinatario_bairro"
                        value="{{ old('destinatario_bairro') }}"
                        required
                        autocomplete="address-level3"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    />
                    @error('destinatario_bairro')
                        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="destinatario_cidade" class="mb-1 block text-sm font-medium text-gray-700">Cidade</label>
                    <input
                        type="text"
                        name="destinatario_cidade"
                        id="destinatario_cidade"
                        value="{{ old('destinatario_cidade') }}"
                        required
                        autocomplete="address-level2"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    />
                    @error('destinatario_cidade')
                        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="destinatario_uf" class="mb-1 block text-sm font-medium text-gray-700">UF</label>
                    <input
                        type="text"
                        name="destinatario_uf"
                        id="destinatario_uf"
                        value="{{ old('destinatario_uf') }}"
                        maxlength="2"
                        required
                        autocomplete="address-level1"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 uppercase focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    />
                    @error('destinatario_uf')
                        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="destinatario_telefone" class="mb-1 block text-sm font-medium text-gray-700">Telefone</label>
                    <input
                        type="tel"
                        name="destinatario_telefone"
                        id="destinatario_telefone"
                        value="{{ old('destinatario_telefone') }}"
                        autocomplete="tel"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    />
                    @error('destinatario_telefone')
                        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="destinatario_email" class="mb-1 block text-sm font-medium text-gray-700">E-mail</label>
                    <input
                        type="email"
                        name="destinatario_email"
                        id="destinatario_email"
                        value="{{ old('destinatario_email') }}"
                        autocomplete="email"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    />
                    @error('destinatario_email')
                        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </x-card>

        <div class="flex flex-col items-stretch gap-3 sm:flex-row sm:justify-end">
            <p class="text-center text-xs text-gray-500 sm:mr-auto sm:self-center sm:text-left">
                O envio chama a API da Octalog; em caso de recusa, a mensagem retornada aparece no topo da página.
            </p>
            <x-button id="btn-submit-pedido" type="submit" class="px-6">
                Gerar pedido e etiqueta
            </x-button>
        </div>
    </form>

    {{-- Overlay de carregamento na consulta SERPRO (fora do formulário para não ser afetado pelo disabled) --}}
    <div
        id="serpro-loading-overlay"
        class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-gray-900/45 backdrop-blur-[2px]"
        aria-hidden="true"
        role="status"
        aria-live="polite"
    >
        <div class="flex max-w-sm flex-col items-center gap-4 rounded-xl bg-white px-8 py-8 shadow-2xl ring-1 ring-gray-200">
            <svg
                class="h-12 w-12 animate-spin text-primary"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
            </svg>
            <p class="text-center text-sm font-medium text-gray-900">Consultando NF-e na SERPRO…</p>
            <p class="text-center text-xs text-gray-500">Aguarde um instante</p>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const serproLookupUrl = @json(route('pedidos.consulta-nfe-serpro'));

            const form = document.getElementById('form-novo-pedido');
            const chaveInput = document.getElementById('chave_nf');
            const serproStatus = document.getElementById('serpro-lookup-status');
            const submitBtn = document.getElementById('btn-submit-pedido');
            const loadingOverlay = document.getElementById('serpro-loading-overlay');

            function setSerproLookupBusy(busy) {
                if (loadingOverlay) {
                    loadingOverlay.classList.toggle('hidden', !busy);
                    loadingOverlay.setAttribute('aria-hidden', busy ? 'false' : 'true');
                }
                document.body.style.overflow = busy ? 'hidden' : '';

                if (form) {
                    form.setAttribute('aria-busy', busy ? 'true' : 'false');
                }
                if (chaveInput) {
                    chaveInput.disabled = busy;
                }
                if (submitBtn) {
                    submitBtn.disabled = busy;
                }
            }

            function setSerproStatus(text, variant) {
                if (!serproStatus) {
                    return;
                }
                serproStatus.textContent = text || '';
                serproStatus.classList.remove('hidden', 'text-gray-600', 'text-danger', 'text-amber-800');
                if (!text) {
                    serproStatus.classList.add('hidden');
                    return;
                }
                serproStatus.classList.remove('hidden');
                if (variant === 'error') {
                    serproStatus.classList.add('text-danger');
                } else if (variant === 'warn') {
                    serproStatus.classList.add('text-amber-800');
                } else {
                    serproStatus.classList.add('text-gray-600');
                }
            }

            function fillIfEmpty(id, value) {
                if (value === null || value === undefined || value === '') {
                    return;
                }
                const el = document.getElementById(id);
                if (!el || el.value) {
                    return;
                }
                el.value = value;
            }

            /** Mantém apenas 44 dígitos no campo (colar com espaços não pode truncar a chave). */
            function normalizeChaveNfDigits() {
                if (!chaveInput) {
                    return '';
                }
                const digits = (chaveInput.value || '').replace(/\D/g, '').slice(0, 44);
                chaveInput.value = digits;

                return digits;
            }

            let serproDebounceTimer = null;
            let serproLookupInFlight = false;

            function scheduleSerproLookup() {
                if (serproDebounceTimer) {
                    clearTimeout(serproDebounceTimer);
                }
                serproDebounceTimer = setTimeout(function () {
                    serproDebounceTimer = null;
                    void trySerproLookup();
                }, 400);
            }

            async function trySerproLookup() {
                if (!chaveInput) {
                    return;
                }
                const digits = normalizeChaveNfDigits();
                if (digits.length !== 44) {
                    if (digits.length >= 40 && digits.length <= 43) {
                        setSerproStatus(
                            'Chave incompleta: ' + digits.length + '/44 dígitos. Confira o bipe ou a cópia da chave de acesso.',
                            'warn',
                        );
                    } else if (digits.length > 0 && digits.length < 40) {
                        setSerproStatus('', null);
                    } else {
                        setSerproStatus('', null);
                    }

                    return;
                }

                if (serproLookupInFlight) {
                    return;
                }

                var csrfMeta = document.querySelector('meta[name="csrf-token"]');
                var csrf = csrfMeta ? csrfMeta.getAttribute('content') : null;
                if (!csrf) {
                    setSerproStatus('Sessão inválida: recarregue a página (token CSRF ausente).', 'error');

                    return;
                }

                serproLookupInFlight = true;
                setSerproLookupBusy(true);
                setSerproStatus('Consultando dados na SERPRO… Aguarde.', 'muted');

                try {
                    const res = await fetch(serproLookupUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ chave_nf: digits }),
                    });

                    const data = await res.json().catch(function () {
                        return {};
                    });

                    if (res.status === 422) {
                        const v = data.errors && data.errors.chave_nf && data.errors.chave_nf[0];
                        setSerproStatus(v || data.message || 'Não foi possível consultar a NF-e.', 'error');
                        return;
                    }

                    if (res.status >= 500) {
                        setSerproStatus(data.message || 'Serviço temporariamente indisponível. Tente novamente.', 'error');
                        return;
                    }

                    if (!res.ok) {
                        setSerproStatus(data.message || 'Não foi possível consultar a NF-e. Tente novamente.', 'error');
                        return;
                    }

                    if (data.ok === false) {
                        setSerproStatus(data.message || 'Consulta SERPRO indisponível.', 'warn');
                        return;
                    }

                    if (data.ok !== true || !data.data) {
                        setSerproStatus('', null);
                        return;
                    }

                    const d = data.data;
                    fillIfEmpty('numero_nf', d.numero_nf);
                    fillIfEmpty('serie_nf', d.serie_nf);
                    fillIfEmpty('valor_total', d.valor_total);
                    fillIfEmpty('destinatario_nome', d.destinatario_nome);
                    fillIfEmpty('destinatario_documento', d.destinatario_documento);
                    fillIfEmpty('destinatario_endereco', d.destinatario_endereco);
                    fillIfEmpty('destinatario_numero', d.destinatario_numero);
                    fillIfEmpty('destinatario_bairro', d.destinatario_bairro);
                    fillIfEmpty('destinatario_cidade', d.destinatario_cidade);
                    fillIfEmpty('destinatario_cep', d.destinatario_cep);
                    fillIfEmpty('destinatario_uf', d.destinatario_uf);
                    fillIfEmpty('destinatario_telefone', d.destinatario_telefone);
                    fillIfEmpty('destinatario_email', d.destinatario_email);

                    setSerproStatus('Dados da NF-e carregados.', 'muted');
                } catch (e) {
                    setSerproStatus('Falha na consulta SERPRO. Verifique a conexão.', 'error');
                } finally {
                    serproLookupInFlight = false;
                    setSerproLookupBusy(false);
                }
            }

            if (chaveInput && !chaveInput.value) {
                chaveInput.focus();
            }

            if (chaveInput) {
                chaveInput.addEventListener('input', function () {
                    normalizeChaveNfDigits();
                    scheduleSerproLookup();
                });
                chaveInput.addEventListener('paste', function () {
                    setTimeout(function () {
                        normalizeChaveNfDigits();
                        void trySerproLookup();
                    }, 0);
                });
                chaveInput.addEventListener('keydown', function (ev) {
                    if (ev.key !== 'Enter') {
                        return;
                    }
                    const d = normalizeChaveNfDigits();
                    if (d.length === 44) {
                        ev.preventDefault();
                        void trySerproLookup();
                    }
                });
                chaveInput.addEventListener('blur', function () {
                    normalizeChaveNfDigits();
                    void trySerproLookup();
                });
            }

            const cepInput = document.getElementById('destinatario_cep');
            const logradouro = document.getElementById('destinatario_endereco');
            const bairro = document.getElementById('destinatario_bairro');
            const cidade = document.getElementById('destinatario_cidade');
            const uf = document.getElementById('destinatario_uf');

            if (!cepInput) {
                return;
            }

            cepInput.addEventListener('blur', function () {
                const digits = (cepInput.value || '').replace(/\D/g, '');
                if (digits.length !== 8) {
                    return;
                }

                fetch('https://viacep.com.br/ws/' + digits + '/json/')
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (data) {
                        if (!data || data.erro) {
                            return;
                        }
                        if (logradouro && !logradouro.value) {
                            logradouro.value = data.logradouro || '';
                        }
                        if (bairro && !bairro.value) {
                            bairro.value = data.bairro || '';
                        }
                        if (cidade && !cidade.value) {
                            cidade.value = data.localidade || '';
                        }
                        if (uf && !uf.value) {
                            uf.value = (data.uf || '').toUpperCase();
                        }
                    })
                    .catch(function () {
                        /* silencioso: ViaCEP indisponível */
                    });
            });
        })();
    </script>
@endpush

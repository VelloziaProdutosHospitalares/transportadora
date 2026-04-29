@php
    $viteAssetsAvailable = file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json'));
    $navItems = [
        ['route' => 'pedidos.index', 'match' => 'pedidos.index', 'label' => 'Pedidos'],
        ['route' => 'empresa.edit', 'match' => 'empresa.*', 'label' => 'Empresa'],
        // ['route' => 'etiquetas.index', 'match' => 'etiquetas.*', 'label' => 'Etiquetas'],
        ['route' => 'pedidos.consulta-octalog.create', 'match' => 'pedidos.consulta-octalog.*', 'label' => 'Consulta Octalog'],
        ['route' => 'octalog.sac.webhook.index', 'match' => 'octalog.sac.webhook.*', 'label' => 'Webhook SAC'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>@yield('title', config('app.name', 'Laravel'))</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        @if ($viteAssetsAvailable)
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            {{-- Evita ViteManifestNotFoundException. Para Tailwind completo: npm run dev ou npm run build (Sail: ./vendor/bin/sail npm …). --}}
            <style>
                body {
                    font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
                    margin: 0;
                    min-height: 100vh;
                    background: #f9fafb;
                    color: #111827;
                }
                a {
                    color: #2563eb;
                }
            </style>
        @endif
    </head>
    <body class="flex min-h-screen flex-col bg-page text-gray-900 antialiased">
        @unless ($viteAssetsAvailable)
            <div
                style="border-bottom:1px solid #fcd34d;background:#fffbeb;padding:0.5rem 1rem;text-align:center;font-size:0.875rem;color:#451a03;"
                role="status"
            >
                Assets do Vite não encontrados. Rode
                <code style="background:#fef3c7;padding:0.125rem 0.35rem;border-radius:0.25rem;font-size:0.75rem;">npm run dev</code>
                ou
                <code style="background:#fef3c7;padding:0.125rem 0.35rem;border-radius:0.25rem;font-size:0.75rem;">npm run build</code>
                no projeto (com Sail:
                <code style="background:#fef3c7;padding:0.125rem 0.35rem;border-radius:0.25rem;font-size:0.75rem;">./vendor/bin/sail npm run dev</code>
                ).
            </div>
        @endunless
        <a
            href="#conteudo-principal"
            class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:inline-flex focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-gray-900 focus:shadow-md focus:outline-none focus:ring-2 focus:ring-primary"
        >
            Ir para o conteúdo
        </a>
        <header class="sticky top-0 z-50 border-b border-gray-200/90 bg-white/95 shadow-sm backdrop-blur-md supports-[backdrop-filter]:bg-white/85">
            <nav class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8" aria-label="Principal">
                <a
                    href="{{ url('/') }}"
                    class="truncate text-base font-semibold tracking-tight text-gray-900 transition hover:text-primary focus-visible:rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                >
                    {{ config('app.name', 'Transportadora') }}
                </a>

                {{-- Desktop --}}
                <div class="hidden items-center gap-1 md:flex lg:gap-2">
                    @foreach ($navItems as $item)
                        <x-nav-link
                            href="{{ route($item['route']) }}"
                            :active="request()->routeIs($item['match'])"
                        >
                            {{ $item['label'] }}
                        </x-nav-link>
                    @endforeach
                    <x-nav-link
                        href="{{ route('pedidos.create') }}"
                        :active="request()->routeIs('pedidos.create')"
                        accent
                    >
                        Novo pedido
                    </x-nav-link>
                </div>

                {{-- Mobile: menu colapsável --}}
                <details class="group relative md:hidden">
                    <summary
                        class="flex h-11 min-w-[2.75rem] cursor-pointer list-none items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-700 shadow-sm transition hover:bg-gray-50 [&::-webkit-details-marker]:hidden focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                        aria-label="Abrir ou fechar menu de navegação"
                    >
                        <svg class="h-6 w-6 shrink-0 group-open:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg class="hidden h-6 w-6 shrink-0 group-open:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </summary>
                    <div
                        class="absolute right-0 z-50 mt-2 w-[min(100vw-2rem,18rem)] rounded-xl border border-gray-200 bg-white py-2 shadow-lg ring-1 ring-black/5"
                        role="presentation"
                    >
                        <div class="flex flex-col gap-1 px-2 pb-1">
                            @foreach ($navItems as $item)
                                <x-nav-link
                                    class="w-full justify-start px-4 py-3"
                                    href="{{ route($item['route']) }}"
                                    :active="request()->routeIs($item['match'])"
                                >
                                    {{ $item['label'] }}
                                </x-nav-link>
                            @endforeach
                            <x-nav-link
                                class="w-full justify-center px-4 py-3"
                                href="{{ route('pedidos.create') }}"
                                :active="request()->routeIs('pedidos.create')"
                                accent
                            >
                                Novo pedido
                            </x-nav-link>
                        </div>
                    </div>
                </details>
            </nav>
        </header>

        <main
            id="conteudo-principal"
            class="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6 lg:px-8"
            tabindex="-1"
        >
            @if (session('success'))
                <x-alert variant="success" class="mb-6">
                    {{ session('success') }}
                </x-alert>
            @endif
            @if (session('error'))
                <x-alert variant="error" class="mb-6">
                    {{ session('error') }}
                </x-alert>
            @endif
            @yield('content')
        </main>

        <footer class="border-t border-gray-200 bg-white py-5 text-center">
            <p class="text-xs text-gray-500">
                {{ config('app.name', 'Transportadora') }}
                <span class="text-gray-300" aria-hidden="true">·</span>
                {{ date('Y') }}
            </p>
        </footer>
        @stack('scripts')
    </body>
</html>

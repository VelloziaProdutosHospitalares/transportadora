@props([
    'title',
    'backHref' => null,
    'backLabel' => null,
])

<header {{ $attributes->merge(['class' => 'mb-8 border-b border-gray-200 pb-6']) }}>
    @if ($backHref)
        <nav class="text-sm font-medium text-primary" aria-label="Navegação da página">
            <a
                href="{{ $backHref }}"
                class="inline-flex items-center gap-1 rounded-sm hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
            >
                <span aria-hidden="true">←</span>
                {{ $backLabel ?? 'Voltar' }}
            </a>
        </nav>
    @endif

    <div
        @class([
            'flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between',
            'mt-4' => $backHref,
        ])
    >
        <div class="min-w-0 flex-1 space-y-2">
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900">
                {{ $title }}
            </h1>
            @isset($description)
                <div class="max-w-3xl text-sm leading-relaxed text-gray-600">
                    {{ $description }}
                </div>
            @endisset
        </div>
        @isset($actions)
            <div class="flex shrink-0 flex-wrap items-center gap-2 sm:justify-end">
                {{ $actions }}
            </div>
        @endisset
    </div>
</header>

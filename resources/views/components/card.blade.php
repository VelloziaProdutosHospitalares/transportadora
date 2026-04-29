@props([
    'variant' => 'default',
])

@php
    $variants = [
        'default' => 'rounded-xl border border-gray-200 bg-surface p-6 shadow-sm',
        'highlight' =>
            'rounded-xl border border-amber-200/80 bg-amber-50/60 p-6 shadow-sm',
        'muted' =>
            'rounded-xl border border-dashed border-gray-300 bg-gray-50/80 px-6 py-6 shadow-sm',
    ];

    $cardClass = $variants[$variant] ?? $variants['default'];
@endphp

<div {{ $attributes->merge(['class' => $cardClass]) }}>
    {{ $slot }}
</div>

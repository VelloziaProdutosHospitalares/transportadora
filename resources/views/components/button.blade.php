@props([
    'variant' => 'primary',
    'href' => null,
    'type' => 'button',
])

@php
    $base =
        'inline-flex min-h-[44px] cursor-pointer items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 sm:min-h-0 touch-manipulation';

    $variants = [
        'primary' => 'bg-primary text-white hover:bg-primary-hover focus-visible:ring-primary',
        'secondary' =>
            'border border-gray-300 bg-white text-gray-800 hover:bg-gray-50 focus-visible:ring-primary',
        'ghost' =>
            'border border-transparent bg-transparent text-gray-700 hover:bg-gray-100 focus-visible:ring-primary',
        'danger' => 'bg-danger text-white hover:opacity-95 focus-visible:ring-danger',
    ];

    $variantClass = $variants[$variant] ?? $variants['primary'];
@endphp

@if ($href)
    <a
        href="{{ $href }}"
        {{ $attributes->merge(['class' => trim("$base $variantClass")]) }}
    >
        {{ $slot }}
    </a>
@else
    <button
        type="{{ $type }}"
        {{ $attributes->merge(['class' => trim("$base $variantClass")]) }}
    >
        {{ $slot }}
    </button>
@endif

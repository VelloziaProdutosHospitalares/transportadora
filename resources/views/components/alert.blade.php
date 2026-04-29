@props([
    'variant' => 'success',
])

@php
    $styles = [
        'success' =>
            'border-success-border bg-success-soft text-gray-900 [&_a]:font-medium [&_a]:text-success [&_a]:underline [&_a]:hover:no-underline',
        'error' => 'border-error-border bg-error-soft text-gray-950 [&_a]:font-medium [&_a]:text-danger [&_a]:underline [&_a]:hover:no-underline',
    ];

    $role = $variant === 'error' ? 'alert' : 'status';
    $live = $variant === 'error' ? 'assertive' : 'polite';
@endphp

<div
    {{ $attributes->merge([
        'class' =>
            'rounded-xl border px-4 py-3 text-sm leading-relaxed shadow-sm ' .
                ($styles[$variant] ?? $styles['success']),
    ]) }}
    role="{{ $role }}"
    aria-live="{{ $live }}"
>
    {{ $slot }}
</div>

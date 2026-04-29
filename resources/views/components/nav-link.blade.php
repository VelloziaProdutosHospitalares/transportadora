@props([
    'href',
    'active' => false,
    'accent' => false,
])

<a
    href="{{ $href }}"
    {{ $attributes->merge([
        'class' =>
            'rounded-lg px-3 py-2 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 ' .
                ($accent
                    ? ($active
                        ? 'bg-primary text-white ring-2 ring-primary ring-offset-2'
                        : 'bg-primary text-white hover:bg-primary-hover')
                    : ($active
                        ? 'bg-gray-100 text-gray-900'
                        : 'text-gray-600 hover:bg-gray-50 hover:text-primary')),
    ]) }}
    @if ($active) aria-current="page" @endif
>
    {{ $slot }}
</a>

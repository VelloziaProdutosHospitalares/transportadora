@props([
    'company',
    'labelData',
    'barcodeSvg',
    'qrCodeSvg' => null,
    'widthMm' => 100,
    'heightMm' => 150,
])

@php
    $cepDigits = (string) ($labelData['postal_code'] ?? '');
    $cepFmt = $labelData['cep_formatted'] ?? (strlen($cepDigits) === 8 ? substr($cepDigits, 0, 5).'-'.substr($cepDigits, 5) : $cepDigits);
    $barcodePlain = (string) ($labelData['barcode_plain'] ?? '');
    $volumeOf = (int) ($labelData['volume_of'] ?? 1);
    $routingPrefix = strlen($cepDigits) >= 3 ? substr($cepDigits, 0, 3) : '';
    $cityU = mb_strtoupper((string) ($labelData['city'] ?? ''), 'UTF-8');
    $stateU = mb_strtoupper((string) ($labelData['state'] ?? ''), 'UTF-8');
    $districtU = mb_strtoupper((string) ($labelData['district'] ?? ''), 'UTF-8');
    $streetU = mb_strtoupper((string) ($labelData['street'] ?? ''), 'UTF-8');
    $nameU = mb_strtoupper((string) ($labelData['recipient_name'] ?? ''), 'UTF-8');
    $routingLine = trim($routingPrefix !== '' ? $routingPrefix.' - '.$cityU.' / '.$stateU : $cityU.' / '.$stateU);
@endphp

<article
    class="shipping-label relative box-border flex flex-col border border-black bg-white text-black antialiased"
    style="width: {{ (int) $widthMm }}mm; min-height: {{ (int) $heightMm }}mm; padding: 3mm; box-sizing: border-box;"
>
    {{-- 1) Logo centralizada (remetente não compete visualmente com o destinatário) --}}
    <header class="mb-2 border-b border-black pb-2 text-center">
        @if ($company?->logo_path)
            <img
                src="{{ route('empresa.logo') }}"
                alt=""
                class="mx-auto block max-h-14 max-w-[85%] object-contain"
            />
        @else
            <p class="text-[9px] font-semibold text-gray-600">Logo da empresa</p>
        @endif
    </header>

    {{-- 2) Destinatário em destaque (referência Octalog) --}}
    <section class="mb-2 flex-1">
        <div class="mb-1.5 flex w-full items-stretch gap-0.5">
            <div class="min-w-0 flex-1 bg-black px-2 py-1.5">
                <span class="text-[10px] font-bold uppercase leading-none text-white">Destinatário:</span>
            </div>
            <div class="shrink-0 bg-black px-2 py-1.5 text-right">
                <span class="text-[10px] font-bold leading-none text-white">
                    Volume: {{ $barcodePlain !== '' ? $barcodePlain : $labelData['order_number'] }}/{{ $volumeOf }}
                </span>
            </div>
        </div>

        <p class="text-[15px] font-bold leading-tight tracking-tight text-black">{{ $nameU }}</p>

        <p class="mt-1 text-[11px] font-normal leading-snug">
            {{ $streetU }}{{ ! empty($labelData['number']) ? ', '.mb_strtoupper((string) $labelData['number'], 'UTF-8') : '' }}
        </p>
        @if (! empty($labelData['complement']))
            <p class="text-[10px] leading-snug">{{ mb_strtoupper((string) $labelData['complement'], 'UTF-8') }}</p>
        @endif

        <p class="mt-0.5 text-[11px] leading-snug">
            {{ $districtU }}, {{ $cityU }} / {{ $stateU }}
        </p>
        <p class="mt-0.5 text-[11px] font-medium leading-snug">{{ $cepFmt }}</p>

        @if (! empty($labelData['document']))
            <p class="mt-1 text-[9px] leading-snug text-gray-800">Doc.: {{ $labelData['document'] }}</p>
        @endif
        @if (! empty($labelData['phone']))
            <p class="text-[9px] leading-snug text-gray-800">Tel.: {{ $labelData['phone'] }}</p>
        @endif
    </section>

    {{-- 3) Remetente discreto --}}
    <section class="mb-2 border-t border-gray-400 pt-1.5">
        <p class="text-[9px] font-normal leading-none text-gray-700">Remetente</p>
        <p class="mt-0.5 text-[11px] font-bold leading-tight text-black">
            {{ $company?->trade_name ?? 'Empresa não cadastrada' }}
        </p>
        <p class="mt-0.5 text-[8px] leading-snug text-gray-700">
            {{ $labelData['service'] ?? '' }} · {{ isset($labelData['weight_grams']) ? $labelData['weight_grams'].' g' : '' }}
            @if (! empty($labelData['tracking_code']))
                <span class="block">Rastreio: {{ $labelData['tracking_code'] }}</span>
            @endif
        </p>
    </section>

    {{-- 4) Faixa de roteirização + código de barras --}}
    <section class="mt-auto min-h-0">
        <div class="bg-black py-2 text-center">
            <p class="text-[11px] font-bold uppercase leading-tight text-white">{{ $routingLine }}</p>
        </div>

        <div class="mt-2">
            <div class="label-barcode mx-auto max-w-full">
                {!! $barcodeSvg !!}
            </div>
            <p class="mt-1 text-center text-[13px] font-bold leading-none tracking-wide">
                {{ $barcodePlain !== '' ? $barcodePlain : $labelData['order_number'] }}
            </p>
        </div>

        @if (! empty($qrCodeSvg))
            <div class="label-qrcode mx-auto mt-2 flex max-w-[22mm] justify-center [&_svg]:max-h-full [&_svg]:max-w-full">
                {!! $qrCodeSvg !!}
            </div>
        @endif

        @if (! empty($labelData['notes']))
            <p class="mt-2 border-t border-dashed border-black pt-1 text-[8px] leading-snug text-gray-900">
                Obs.: {{ $labelData['notes'] }}
            </p>
        @endif
    </section>
</article>

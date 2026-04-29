@php
    $labelDataRow = is_array($labelData) ? $labelData : [];
    $hasThermal = $labelDataRow !== [] && ! empty($barcodeSvg);
@endphp

@if ($company === null)
    <x-alert variant="error" class="mb-4">
        Cadastre os dados da empresa para exibir o remetente na etiqueta térmica.
    </x-alert>
@endif

@if ($hasThermal)
    <div class="space-y-4">
        <p class="text-sm text-gray-600">
            Dados de entrega conforme enviados à Octalog. Use <strong class="font-medium text-gray-800">Imprimir</strong> para a impressora térmica
            ({{ (int) ($labelDataRow['label_width_mm'] ?? 100) }}×{{ (int) ($labelDataRow['label_height_mm'] ?? 150) }} mm).
        </p>
        @if ($pedido->url_etiqueta)
            <p class="text-xs text-gray-500">
                <a href="{{ $pedido->url_etiqueta }}" target="_blank" rel="noopener noreferrer" class="font-medium text-primary hover:underline">Abrir etiqueta PDF da Octalog</a>
                (opcional).
            </p>
        @endif
        <div class="flex flex-wrap gap-2 print-hidden">
            <x-button type="button" onclick="window.print()">Imprimir etiqueta</x-button>
        </div>
    </div>

    <section class="thermal-label-print-zone mt-6 space-y-3 print-mt-none">
        <h3 class="print-hidden text-base font-semibold text-gray-900">Pré-visualização</h3>
        <div class="label-print-wrap flex justify-center">
            <x-shipping-label
                :company="$company"
                :label-data="$labelDataRow"
                :barcode-svg="$barcodeSvg"
                :qr-code-svg="$qrCodeSvg"
                :width-mm="(int) ($labelDataRow['label_width_mm'] ?? 100)"
                :height-mm="(int) ($labelDataRow['label_height_mm'] ?? 150)"
            />
        </div>
    </section>
@elseif ($pedido->url_etiqueta)
    <p class="mb-4 text-sm text-gray-600">
        Pré-visualização da Octalog abaixo. Para etiqueta térmica local, os dados do destinatário não estão disponíveis neste pedido.
    </p>
    <div class="space-y-4">
        <iframe
            src="{{ $pedido->url_etiqueta }}"
            title="Pré-visualização da etiqueta de envio"
            class="h-[min(28rem,70vh)] w-full rounded-lg border border-gray-200 bg-gray-50 shadow-inner"
        ></iframe>
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
            <x-button
                type="button"
                id="btn-imprimir-etiqueta"
                data-url="{{ $pedido->url_etiqueta }}"
                class="flex-1 sm:flex-none"
            >
                Imprimir etiqueta
            </x-button>
            <x-button variant="secondary" href="{{ $pedido->url_etiqueta }}" target="_blank" rel="noopener noreferrer" class="flex-1 sm:flex-none">
                Abrir em nova aba
            </x-button>
        </div>
    </div>
@else
    <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50/80 px-4 py-8 text-center">
        <p class="text-sm font-medium text-gray-900">Etiqueta térmica indisponível</p>
        <p class="mt-2 text-sm text-gray-600">
            Não há PDF da Octalog nem dados de destinatário armazenados para este pedido.
        </p>
        @if ($pedido->status !== 'enviado')
            <p class="mt-3 text-xs text-gray-500">Etiquetas locais são geradas após o envio com sucesso à Octalog.</p>
        @endif
    </div>
@endif

@push('scripts')
    @php
        $hasThermalPrint = $hasThermal;
        $pageWmm = $hasThermalPrint ? (int) ($labelDataRow['label_width_mm'] ?? 100) : 100;
        $pageHmm = $hasThermalPrint ? (int) ($labelDataRow['label_height_mm'] ?? 150) : 150;
    @endphp
    <style>
        .label-print-wrap {
            width: 100%;
        }

        .shipping-label {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .shipping-label .label-barcode svg {
            display: block;
            width: 100%;
            height: auto;
            max-height: 26mm;
        }

        .shipping-label .label-qrcode svg {
            display: block;
            width: 100%;
            height: auto;
            max-width: 100%;
            max-height: 100%;
        }

        @if ($hasThermalPrint)
            @media print {
                @page {
                    size: {{ $pageWmm }}mm {{ $pageHmm }}mm;
                    margin: 0;
                }

                html,
                body {
                    width: {{ $pageWmm }}mm;
                    height: {{ $pageHmm }}mm;
                    margin: 0 !important;
                    padding: 0 !important;
                    background: #fff !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }

                body * {
                    visibility: hidden;
                }

                .thermal-label-print-zone,
                .thermal-label-print-zone * {
                    visibility: visible;
                }

                .thermal-label-print-zone {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: {{ $pageWmm }}mm !important;
                    height: {{ $pageHmm }}mm !important;
                    margin: 0 !important;
                    padding: 0 !important;
                }

                .thermal-label-print-zone .label-print-wrap {
                    width: 100% !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    display: block !important;
                }

                .thermal-label-print-zone .shipping-label {
                    width: {{ $pageWmm }}mm !important;
                    height: {{ $pageHmm }}mm !important;
                    min-height: 0 !important;
                    max-height: {{ $pageHmm }}mm !important;
                    margin: 0 !important;
                    padding: 2mm !important;
                    box-sizing: border-box !important;
                    border: 1px solid #000 !important;
                    page-break-after: avoid !important;
                    page-break-inside: avoid !important;
                    overflow: hidden;
                }

                .thermal-label-print-zone .shipping-label .label-barcode svg {
                    max-height: 20mm;
                }

                .thermal-label-print-zone .print-hidden {
                    display: none !important;
                }
            }
        @endif

        @media print {
            .print-hidden,
            body > header,
            body > footer {
                display: none !important;
            }

            body {
                background: #fff !important;
            }

            main {
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .print-mt-none {
                margin-top: 0 !important;
            }
        }
    </style>
@endpush

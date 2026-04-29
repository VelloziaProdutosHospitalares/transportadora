<?php

namespace App\DTOs;

/**
 * Payload `POST /pedido/salvar` (Octalog — até **50** pedidos por requisição).
 *
 * Raiz obrigatória na API: Pedido, IDPrazoEntrega, TotalVolumes. Sem emissão de CT-e,
 * DadosFiscais e Dimensões podem ficar omitidos conforme especificação.
 *
 * @phpstan-type DadosFiscais array{
 *     ChaveNotaFiscal: string|null,
 *     NumeroNotaFiscal: string,
 *     SerieNotaFiscal: string,
 *     ValorTotalReais: float,
 * }
 * @phpstan-type Dimensao array{
 *     PesoKilos: float,
 *     Altura: float,
 *     Largura: float,
 *     Comprimento: float,
 *     Cubagem: float,
 *     ValorTotalReais: float,
 * }
 * @phpstan-type Remetente array{
 *     RazaoSocial: string,
 *     CNPJ: string,
 *     Filial?: string|null,
 *     InscricaoEstadual?: string|null,
 *     Endereco?: string,
 *     Numero?: string,
 *     Bairro?: string,
 *     Cidade?: string,
 *     CEP?: string,
 *     UF?: string,
 *     Telefone?: string,
 *     Email?: string,
 * }
 * @phpstan-type Destinatario array{
 *     Nome: string,
 *     Endereco: string,
 *     Numero: string,
 *     Bairro: string,
 *     Cidade: string,
 *     CEP: string,
 *     UF: string,
 *     Documento?: string|null,
 *     InscricaoEstadual?: string|null,
 *     PontoReferencia?: string,
 *     Complemento?: string,
 *     Telefone?: string,
 *     Email?: string,
 *     Latitude?: float,
 *     Longitude?: float,
 * }
 */
readonly class OctalogOrderData
{
    /**
     * @param  DadosFiscais|null  $dadosFiscais  Obrigatório apenas na emissão de CTE
     * @param  Dimensao[]|null  $dimensoes  Obrigatório apenas na emissão de CTE
     * @param  Remetente|null  $remetente
     * @param  Destinatario|null  $destinatario
     */
    public function __construct(
        public string $pedido,
        public int $idPrazoEntrega,
        public int $totalVolumes,
        public ?string $dataVenda = null,
        public ?array $dadosFiscais = null,
        public ?array $dimensoes = null,
        public ?array $remetente = null,
        public ?array $destinatario = null,
    ) {}

    /** Ordem e normalização estáveis conforme exemplo OpenAPI (pedido/salvar). */
    public function toPayload(): array
    {
        $payload = [
            'Pedido' => $this->pedido,
            'DataVenda' => $this->dataVenda,
            'IDPrazoEntrega' => $this->idPrazoEntrega,
            'TotalVolumes' => $this->totalVolumes,
        ];

        if ($this->dadosFiscais !== null) {
            $payload['DadosFiscais'] = $this->ordenarDadosFiscais($this->dadosFiscais);
        }

        if ($this->dimensoes !== null) {
            $payload['Dimensoes'] = $this->dimensoes;
        }

        if ($this->remetente !== null) {
            $payload['Remetente'] = $this->ordenarRemetente($this->remetente);
        }

        if ($this->destinatario !== null) {
            $payload['Destinatario'] = $this->ordenarDestinatario($this->destinatario);
        }

        return $payload;
    }

    /**
     * Ordem estável conforme exemplo oficial: Chave, Número, Série, Valor.
     *
     * @param  array<string, mixed>  $df
     * @return array<string, mixed>
     */
    private function ordenarDadosFiscais(array $df): array
    {
        return [
            'ChaveNotaFiscal' => $df['ChaveNotaFiscal'] ?? null,
            'NumeroNotaFiscal' => $df['NumeroNotaFiscal'] ?? '',
            'SerieNotaFiscal' => $df['SerieNotaFiscal'] ?? '',
            'ValorTotalReais' => isset($df['ValorTotalReais']) ? (float) $df['ValorTotalReais'] : 0.0,
        ];
    }

    /**
     * @param  array<string, mixed>  $r
     * @return array<string, mixed>
     */
    private function ordenarRemetente(array $r): array
    {
        $out = [];

        foreach (['Filial', 'RazaoSocial', 'CNPJ', 'InscricaoEstadual', 'Endereco', 'Numero', 'Bairro', 'Cidade', 'CEP', 'UF', 'Telefone', 'Email'] as $key) {
            if (! array_key_exists($key, $r)) {
                continue;
            }
            $out[$key] = $r[$key];
        }

        foreach ($r as $k => $v) {
            if (! array_key_exists($k, $out)) {
                $out[$k] = $v;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $d
     * @return array<string, mixed>
     */
    private function ordenarDestinatario(array $d): array
    {
        $ordered = [];

        foreach (['Nome', 'Documento', 'InscricaoEstadual', 'Endereco', 'Numero', 'Bairro', 'Cidade', 'PontoReferencia', 'Complemento', 'CEP', 'UF', 'Telefone', 'Email', 'Latitude', 'Longitude'] as $key) {
            if (! array_key_exists($key, $d)) {
                continue;
            }
            $ordered[$key] = $d[$key];
        }

        foreach ($d as $k => $v) {
            if (! array_key_exists($k, $ordered)) {
                $ordered[$k] = $v;
            }
        }

        return $ordered;
    }
}

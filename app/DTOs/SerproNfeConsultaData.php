<?php

namespace App\DTOs;

/**
 * Trechos úteis da NF-e para pré-preenchimento do pedido (estrutura flexível — vários formatos de retorno da API).
 */
final class SerproNfeConsultaData
{
    /**
     * @param  array<string, mixed>|null  $resolvedInfNFe
     */
    public function __construct(
        public readonly string $chaveAcesso,
        public readonly ?string $numeroNf,
        public readonly ?string $serieNf,
        public readonly ?string $valorTotal,
        public readonly ?string $emitenteNome,
        public readonly ?string $destinatarioNome,
        public readonly ?string $destinatarioDocumento,
        public readonly ?string $destinatarioEndereco,
        public readonly ?string $destinatarioNumero,
        public readonly ?string $destinatarioBairro,
        public readonly ?string $destinatarioCidade,
        public readonly ?string $destinatarioCep,
        public readonly ?string $destinatarioUf,
        public readonly ?string $destinatarioTelefone,
        public readonly ?string $destinatarioEmail,
        public readonly ?array $resolvedInfNFe,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromApiPayload(array $payload, string $chave44): self
    {
        $inf = self::resolveInfNFe($payload);

        $ide = is_array($inf) ? ($inf['ide'] ?? null) : null;
        $emit = is_array($inf) ? ($inf['emit'] ?? null) : null;
        $dest = is_array($inf) ? ($inf['dest'] ?? null) : null;
        $total = is_array($inf) ? ($inf['total'] ?? null) : null;

        $nNf = is_array($ide) ? ($ide['nNF'] ?? null) : null;
        $serie = is_array($ide) ? ($ide['serie'] ?? null) : null;

        $icmsTot = is_array($total) ? ($total['ICMSTot'] ?? null) : null;
        $vNf = is_array($icmsTot) ? ($icmsTot['vNF'] ?? null) : null;

        $emitNome = is_array($emit) ? ($emit['xNome'] ?? $emit['xFant'] ?? null) : null;
        $destNome = is_array($dest) ? ($dest['xNome'] ?? null) : null;

        $doc = null;
        if (is_array($dest)) {
            $doc = $dest['CNPJ'] ?? $dest['CPF'] ?? $dest['cnpj'] ?? $dest['cpf'] ?? null;
        }

        $ender = is_array($dest) ? ($dest['enderDest'] ?? $dest['ender'] ?? null) : null;
        $xLgr = is_array($ender) ? ($ender['xLgr'] ?? null) : null;
        $nro = is_array($ender) ? ($ender['nro'] ?? null) : null;
        $xBairro = is_array($ender) ? ($ender['xBairro'] ?? null) : null;
        $xMun = is_array($ender) ? ($ender['xMun'] ?? null) : null;
        $cep = is_array($ender) ? ($ender['CEP'] ?? $ender['cep'] ?? null) : null;
        $uf = is_array($ender) ? ($ender['UF'] ?? $ender['uf'] ?? null) : null;

        $fone = is_array($dest) ? ($dest['fone'] ?? $dest['telefone'] ?? null) : null;
        if ($fone === null && is_array($ender)) {
            $fone = $ender['fone'] ?? $ender['telefone'] ?? null;
        }
        $email = is_array($dest) ? ($dest['email'] ?? $dest['Email'] ?? null) : null;

        $ufStr = self::stringOrNull($uf);

        return new self(
            chaveAcesso: $chave44,
            numeroNf: self::stringOrNull($nNf),
            serieNf: self::stringOrNull($serie),
            valorTotal: self::stringOrNull($vNf),
            emitenteNome: self::stringOrNull($emitNome),
            destinatarioNome: self::stringOrNull($destNome),
            destinatarioDocumento: self::normalizeDocumento($doc),
            destinatarioEndereco: self::stringOrNull($xLgr),
            destinatarioNumero: self::stringOrNull($nro),
            destinatarioBairro: self::stringOrNull($xBairro),
            destinatarioCidade: self::stringOrNull($xMun),
            destinatarioCep: self::normalizeCep($cep),
            destinatarioUf: $ufStr !== null ? strtoupper($ufStr) : null,
            destinatarioTelefone: self::normalizeFone($fone),
            destinatarioEmail: self::stringOrNull($email),
            resolvedInfNFe: is_array($inf) ? $inf : null,
        );
    }

    /**
     * @return array<string, string|int|float|null>
     */
    public function toFormPrefill(): array
    {
        $out = [
            'chave_nf' => $this->chaveAcesso,
        ];

        if ($this->numeroNf !== null) {
            $out['numero_nf'] = $this->numeroNf;
        }
        if ($this->serieNf !== null) {
            $out['serie_nf'] = $this->serieNf;
        }
        if ($this->valorTotal !== null) {
            $out['valor_total'] = $this->formatValorBr($this->valorTotal);
        }
        if ($this->destinatarioNome !== null) {
            $out['destinatario_nome'] = $this->destinatarioNome;
        }
        if ($this->destinatarioDocumento !== null) {
            $out['destinatario_documento'] = $this->destinatarioDocumento;
        }
        if ($this->destinatarioEndereco !== null) {
            $out['destinatario_endereco'] = $this->destinatarioEndereco;
        }
        if ($this->destinatarioNumero !== null) {
            $out['destinatario_numero'] = $this->destinatarioNumero;
        }
        if ($this->destinatarioBairro !== null) {
            $out['destinatario_bairro'] = $this->destinatarioBairro;
        }
        if ($this->destinatarioCidade !== null) {
            $out['destinatario_cidade'] = $this->destinatarioCidade;
        }
        if ($this->destinatarioCep !== null) {
            $out['destinatario_cep'] = $this->destinatarioCep;
        }
        if ($this->destinatarioUf !== null) {
            $out['destinatario_uf'] = $this->destinatarioUf;
        }
        if ($this->destinatarioTelefone !== null) {
            $out['destinatario_telefone'] = $this->destinatarioTelefone;
        }
        if ($this->destinatarioEmail !== null) {
            $out['destinatario_email'] = $this->destinatarioEmail;
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $root
     * @return array<string, mixed>|null
     */
    private static function resolveInfNFe(array $root): ?array
    {
        $candidates = [
            // Formato típico da API Consulta NF-e (doc SERPRO — nfeProc.NFe.infNFe)
            data_get($root, 'nfeProc.NFe.infNFe'),
            data_get($root, 'nfeProc.nfe.infNFe'),
            data_get($root, 'nfe.NFe.infNFe'),
            data_get($root, 'NFe.infNFe'),
            data_get($root, 'infNFe'),
            data_get($root, 'nfe.infNFe'),
            data_get($root, 'data.NFe.infNFe'),
            data_get($root, 'data.infNFe'),
        ];

        foreach ($candidates as $node) {
            if (is_array($node) && $node !== []) {
                return $node;
            }
        }

        if (isset($root['ide']) || isset($root['dest']) || isset($root['emit'])) {
            return $root;
        }

        return null;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $s = is_string($value) ? $value : (is_scalar($value) ? (string) $value : null);

        if ($s === null || $s === '') {
            return null;
        }

        return $s;
    }

    private static function normalizeDocumento(mixed $doc): ?string
    {
        $s = self::stringOrNull($doc);
        if ($s === null) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $s);

        return $digits !== '' ? $digits : null;
    }

    private static function normalizeCep(mixed $cep): ?string
    {
        $s = self::stringOrNull($cep);
        if ($s === null) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $s);

        return strlen($digits) === 8 ? $digits : null;
    }

    private static function normalizeFone(mixed $fone): ?string
    {
        $s = self::stringOrNull($fone);
        if ($s === null) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $s);

        return $digits !== '' ? $digits : null;
    }

    private function formatValorBr(string $valor): string
    {
        if (is_numeric($valor)) {
            return number_format((float) $valor, 2, ',', '.');
        }

        return $valor;
    }
}

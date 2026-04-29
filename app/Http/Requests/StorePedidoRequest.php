<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $chave = $this->input('chave_nf');
        if (is_string($chave)) {
            $chave = preg_replace('/\D/', '', $chave);
            $this->merge(['chave_nf' => $chave !== '' ? $chave : null]);
        }

        $cep = $this->input('destinatario_cep');
        if (is_string($cep)) {
            $cep = preg_replace('/\D/', '', $cep);
            $this->merge(['destinatario_cep' => $cep]);
        }

        $uf = $this->input('destinatario_uf');
        if (is_string($uf)) {
            $this->merge(['destinatario_uf' => strtoupper($uf)]);
        }

        $valorTotal = $this->input('valor_total');
        if ($valorTotal !== null && $valorTotal !== '') {
            $normalizado = self::normalizarValorMonetarioParaValidacao($valorTotal);
            if ($normalizado !== null) {
                $this->merge(['valor_total' => $normalizado]);
            }
        }
    }

    /**
     * Aceita formato BR na nota/manual (ex.: 1.800,99 ou 1800,99) e inglês quando inequívoco.
     *
     * @return float|numeric-string|null
     */
    private static function normalizarValorMonetarioParaValidacao(mixed $valor): float|int|string|null
    {
        if (is_int($valor) || is_float($valor)) {
            return $valor;
        }

        $sOrig = trim((string) $valor);
        if ($sOrig === '') {
            return null;
        }

        $semEspacos = preg_replace('/\s+/u', '', $sOrig);
        if ($semEspacos !== $sOrig) {
            return self::normalizarValorMonetarioParaValidacao($semEspacos);
        }

        if (preg_match('/^R\$/ui', $sOrig) === 1) {
            return self::normalizarValorMonetarioParaValidacao((string) (preg_replace('/^R\$\s*/ui', '', $sOrig)));
        }

        // Formato brasileiro: decimal "," (ex.: 1.800,99 — resultado típico do pré-preenchimento SERPRO).
        if (str_contains($sOrig, ',')) {
            $ultimaVirgula = strrpos($sOrig, ',');
            $inteiroRaw = substr($sOrig, 0, $ultimaVirgula !== false ? $ultimaVirgula : 0);
            $decimalRaw = $ultimaVirgula !== false ? substr($sOrig, $ultimaVirgula + 1) : '';

            $inteiroSemMilhar = str_replace('.', '', (string) $inteiroRaw);
            $inteiroDigitos = preg_replace('/\D/', '', $inteiroSemMilhar ?? '') ?? '';

            $fracDigitos = preg_replace('/\D/', '', $decimalRaw) ?? '';
            if ($fracDigitos !== '') {
                $fracDigitos = mb_substr($fracDigitos, 0, 10);
            } else {
                $fracDigitos = '0';
            }

            if ($inteiroDigitos === '') {
                $inteiroDigitos = '0';
            }

            return (float) ($inteiroDigitos.'.'.$fracDigitos);
        }

        // Somente dígitos (valor inteiro em reais).
        if (preg_match('/^\d+$/', $sOrig) === 1) {
            return (float) $sOrig;
        }

        // Milhar com pontos repetidos sem vírgula (ex.: 1.234.567) — antes de aceitar decimal "inglês".
        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $sOrig) === 1) {
            return (float) str_replace('.', '', $sOrig);
        }

        // Decimal com ponto (ex.: 1800.50).
        if (preg_match('/^-?\d+\.\d+$/', $sOrig) === 1) {
            return (float) $sOrig;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'chave_nf' => ['nullable', 'string', 'size:44'],
            'numero_nf' => ['required', 'string', 'max:20'],
            'serie_nf' => ['required', 'string', 'max:3'],
            'valor_total' => ['required', 'numeric', 'min:0.01'],
            'total_volumes' => ['required', 'integer', 'min:1'],
            'id_prazo_entrega' => ['required', 'integer', Rule::in([6, 15])],
            'destinatario_nome' => ['required', 'string', 'max:100'],
            'destinatario_documento' => ['nullable', 'string'],
            'destinatario_endereco' => ['required', 'string', 'max:200'],
            'destinatario_numero' => ['required', 'string', 'max:10'],
            'destinatario_bairro' => ['required', 'string', 'max:100'],
            'destinatario_cidade' => ['required', 'string', 'max:100'],
            'destinatario_cep' => ['required', 'string', 'size:8'],
            'destinatario_uf' => ['required', 'string', 'size:2'],
            'destinatario_telefone' => ['nullable', 'string', 'max:20'],
            'destinatario_email' => ['nullable', 'email', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'chave_nf.size' => 'A chave da NF-e deve ter exatamente 44 dígitos.',
            'numero_nf.required' => 'O número da nota fiscal é obrigatório.',
            'numero_nf.max' => 'O número da nota fiscal não pode ter mais de :max caracteres.',
            'serie_nf.required' => 'A série da nota fiscal é obrigatória.',
            'serie_nf.max' => 'A série não pode ter mais de :max caracteres.',
            'valor_total.required' => 'O valor total é obrigatório.',
            'valor_total.numeric' => 'O valor total deve ser numérico.',
            'valor_total.min' => 'O valor total deve ser pelo menos :min.',
            'total_volumes.required' => 'O total de volumes é obrigatório.',
            'total_volumes.integer' => 'O total de volumes deve ser um número inteiro.',
            'total_volumes.min' => 'Deve haver pelo menos :min volume(s).',
            'id_prazo_entrega.required' => 'O prazo de entrega é obrigatório.',
            'id_prazo_entrega.integer' => 'O prazo de entrega deve ser um número inteiro.',
            'id_prazo_entrega.in' => 'Selecione um prazo de entrega válido.',
            'destinatario_nome.required' => 'O nome do destinatário é obrigatório.',
            'destinatario_endereco.required' => 'O endereço do destinatário é obrigatório.',
            'destinatario_numero.required' => 'O número do endereço é obrigatório.',
            'destinatario_bairro.required' => 'O bairro é obrigatório.',
            'destinatario_cidade.required' => 'A cidade é obrigatória.',
            'destinatario_cep.required' => 'O CEP é obrigatório.',
            'destinatario_cep.size' => 'O CEP deve ter 8 dígitos.',
            'destinatario_uf.required' => 'A UF é obrigatória.',
            'destinatario_uf.size' => 'A UF deve ter 2 letras.',
            'destinatario_email.email' => 'Informe um e-mail válido.',
        ];
    }
}

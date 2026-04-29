<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConsultaSerproNfeRequest extends FormRequest
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
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'chave_nf' => ['required', 'string', 'regex:/^\d{44}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'chave_nf.required' => 'Informe a chave da NF-e com 44 dígitos.',
            'chave_nf.regex' => 'A chave da NF-e deve conter exatamente 44 dígitos.',
        ];
    }
}

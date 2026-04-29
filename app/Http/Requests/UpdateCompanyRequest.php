<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'legal_name' => ['required', 'string', 'max:255'],
            'trade_name' => ['required', 'string', 'max:255'],
            'cnpj' => ['required', 'string', 'max:18', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! $this->isValidCnpj((string) $value)) {
                    $fail('O CNPJ informado é inválido.');
                }
            }],
            'state_registration' => ['nullable', 'string', 'max:30'],
            'phone' => ['required', 'string', 'regex:/^\(?\d{2}\)?\s?\d{4,5}\-?\d{4}$/'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'postal_code' => ['required', 'string', 'regex:/^\d{5}\-?\d{3}$/'],
            'street' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', Rule::in($this->brazilianStates())],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'contract' => ['required', 'string', 'max:120'],
            'administrative_code' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'legal_name.required' => 'Informe a razão social.',
            'trade_name.required' => 'Informe o nome fantasia.',
            'phone.required' => 'Informe o telefone.',
            'phone.regex' => 'Informe um telefone válido com DDD.',
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'postal_code.required' => 'Informe o CEP.',
            'postal_code.regex' => 'Informe um CEP válido no formato 00000-000.',
            'street.required' => 'Informe o logradouro.',
            'number.required' => 'Informe o número.',
            'district.required' => 'Informe o bairro.',
            'city.required' => 'Informe a cidade.',
            'state.required' => 'Informe a UF.',
            'state.in' => 'Informe uma UF válida.',
            'logo.image' => 'A logo deve ser uma imagem válida.',
            'logo.mimes' => 'A logo deve estar em PNG, JPG ou WEBP.',
            'contract.required' => 'Informe o contrato.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cnpj' => $this->normalizeDigits((string) $this->input('cnpj')),
            'postal_code' => $this->normalizeDigits((string) $this->input('postal_code')),
            'phone' => preg_replace('/\s+/', ' ', trim((string) $this->input('phone'))),
            'state' => strtoupper(trim((string) $this->input('state'))),
        ]);
    }

    private function normalizeDigits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    private function isValidCnpj(string $cnpj): bool
    {
        $cnpj = $this->normalizeDigits($cnpj);
        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $weightsOne = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weightsTwo = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $digitOne = $this->calculateCnpjDigit($cnpj, $weightsOne);
        $digitTwo = $this->calculateCnpjDigit($cnpj, $weightsTwo);

        return $cnpj[12] === (string) $digitOne && $cnpj[13] === (string) $digitTwo;
    }

    private function calculateCnpjDigit(string $cnpj, array $weights): int
    {
        $sum = 0;
        foreach ($weights as $index => $weight) {
            $sum += (int) $cnpj[$index] * $weight;
        }
        $mod = $sum % 11;

        return $mod < 2 ? 0 : 11 - $mod;
    }

    /**
     * @return array<int, string>
     */
    private function brazilianStates(): array
    {
        return [
            'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT',
            'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO',
            'RR', 'SC', 'SP', 'SE', 'TO',
        ];
    }
}

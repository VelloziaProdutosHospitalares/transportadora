<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConsultarPedidosOctalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $texto = $this->input('lista_pedidos');
        if (! is_string($texto)) {
            $texto = '';
        }

        $tokens = preg_split('/[\s,;\r\n]+/u', $texto) ?: [];
        $lista = [];

        foreach ($tokens as $t) {
            $t = trim((string) $t);
            if ($t !== '') {
                $lista[] = $t;
            }
        }

        $lista = array_values(array_unique($lista, SORT_REGULAR));

        $this->merge([
            'numeros' => $lista,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'lista_pedidos' => ['required', 'string'],
            'numeros' => ['required', 'array', 'min:1', 'max:100'],
            'numeros.*' => ['required', 'string', 'max:80'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lista_pedidos.required' => 'Informe ao menos um número de pedido.',
            'numeros.required' => 'Não foi possível identificar números de pedido na lista.',
            'numeros.min' => 'Informe ao menos um número de pedido.',
            'numeros.max' => 'Informe no máximo :max números de pedido por consulta.',
            'numeros.*.max' => 'Cada número de pedido pode ter no máximo :max caracteres.',
        ];
    }
}

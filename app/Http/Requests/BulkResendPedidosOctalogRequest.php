<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkResendPedidosOctalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Company|null $company */
        $company = $this->route('company');
        $companyId = $company !== null ? (int) $company->id : 0;

        return [
            'pedido_ids' => ['required', 'array', 'min:1', 'max:200'],
            'pedido_ids.*' => [
                'integer',
                Rule::exists('pedidos', 'id')->where(
                    static fn ($query) => $query->where('company_id', $companyId),
                ),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pedido_ids.required' => 'Selecione ao menos um pedido para reenviar.',
            'pedido_ids.array' => 'Seleção de pedidos inválida.',
            'pedido_ids.min' => 'Selecione ao menos um pedido para reenviar.',
            'pedido_ids.max' => 'Selecione no máximo 200 pedidos por vez.',
            'pedido_ids.*.exists' => 'Um ou mais pedidos não pertencem a esta empresa.',
        ];
    }
}

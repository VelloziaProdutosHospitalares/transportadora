<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CancelPedidoSacTicketRequest extends FormRequest
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
            'id_motivo' => ['required', 'integer', 'min:0'],
            'descricao' => ['required', 'string', 'max:1200'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id_motivo.required' => 'Informe o motivo (IDMotivo) da solicitação de cancelamento.',
            'descricao.required' => 'Informe a descrição.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toOctalogPayload(string $numeroPedido): array
    {
        $v = $this->validated();

        return [
            'Pedido' => $numeroPedido,
            'IDMotivo' => (int) $v['id_motivo'],
            'Descricao' => $v['descricao'],
        ];
    }
}

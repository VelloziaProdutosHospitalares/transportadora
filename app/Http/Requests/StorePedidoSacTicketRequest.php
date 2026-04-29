<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePedidoSacTicketRequest extends FormRequest
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
            'id_motivo' => ['required', 'integer', 'min:1'],
            'titulo' => ['required', 'string', 'max:150'],
            'descricao' => ['required', 'string', 'max:1200'],
            'comentario_motorista' => ['nullable', 'string', 'max:150'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'id_motivo.required' => 'Selecione o motivo.',
            'titulo.required' => 'Informe o título.',
            'descricao.required' => 'Informe a descrição.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toOctalogPayload(string $numeroPedido): array
    {
        $v = $this->validated();
        $comentario = trim((string) ($v['comentario_motorista'] ?? ''));

        return [
            'Pedido' => $numeroPedido,
            'IDMotivo' => (int) $v['id_motivo'],
            'Titulo' => $v['titulo'],
            'Descricao' => $v['descricao'],
            'ComentarioMotorista' => $comentario !== '' ? $comentario : null,
        ];
    }
}

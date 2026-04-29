<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class OctalogWebhookIncomingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Corpo: array JSON na raiz. Cada item é **tracking** (movimentação) ou **ticket SAC** (exemplo webhook).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payload' => ['required', 'array', 'min:1'],
            'payload.*' => ['required', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var array<int, mixed>|null $payload */
            $payload = $this->input('payload');
            if (! is_array($payload)) {
                return;
            }

            foreach ($payload as $index => $item) {
                if (! is_array($item)) {
                    $validator->errors()->add(
                        'payload.'.$index,
                        'Cada item do webhook deve ser um objeto.',
                    );

                    continue;
                }

                if ($this->isSacTicketRow($item) || $this->isTrackingRow($item)) {
                    continue;
                }

                $validator->errors()->add(
                    'payload.'.$index,
                    'Item inválido: use o formato de tracking (ID, Pedido, IDStatus, …) ou de ticket SAC (IDTicket, Pedido, IDMotivo, …).',
                );
            }
        });
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isSacTicketRow(array $row): bool
    {
        if (
            ! array_key_exists('IDTicket', $row)
            || ! array_key_exists('Pedido', $row)
            || ! array_key_exists('IDMotivo', $row)
            || ! array_key_exists('DataInclusao', $row)
            || ! array_key_exists('Titulo', $row)
            || ! array_key_exists('Descricao', $row)
        ) {
            return false;
        }

        if (! is_numeric($row['IDTicket']) || ! is_string($row['Pedido']) || ! is_numeric($row['IDMotivo'])) {
            return false;
        }

        $comentarios = $row['SACComentario'] ?? [];
        if ($comentarios !== [] && ! is_array($comentarios)) {
            return false;
        }

        if (is_array($comentarios)) {
            foreach ($comentarios as $c) {
                if (! is_array($c)) {
                    return false;
                }
                if (
                    ! array_key_exists('DataInclusao', $c)
                    || ! array_key_exists('Descricao', $c)
                ) {
                    return false;
                }
            }
        }

        $anexos = $row['SACAnexos'] ?? [];
        if ($anexos !== [] && ! is_array($anexos)) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isTrackingRow(array $row): bool
    {
        foreach (['ID', 'Pedido', 'IDStatus', 'Status', 'PrazoEntrega', 'DataEvento'] as $key) {
            if (! array_key_exists($key, $row)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payload.required' => 'O corpo deve ser um array JSON de eventos.',
            'payload.array' => 'O corpo deve ser um array JSON de eventos.',
            'payload.min' => 'Envie pelo menos um evento.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = json_decode($this->getContent() ?: '[]', true);

        if (! is_array($data) || ! array_is_list($data)) {
            $this->merge(['payload' => null]);

            return;
        }

        $this->merge(['payload' => $data]);
    }
}

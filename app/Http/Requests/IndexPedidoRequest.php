<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $payload = [];

        $perPage = $this->query('per_page');
        if ($perPage !== null && $perPage !== '' && ! in_array((int) $perPage, [10, 15, 25, 50], true)) {
            $payload['per_page'] = 15;
        }

        $ord = $this->query('ordenacao');
        if ($ord !== null && $ord !== '' && ! in_array((string) $ord, ['recent', 'oldest'], true)) {
            $payload['ordenacao'] = 'recent';
        }

        $st = $this->query('status');
        if ($st !== null && $st !== '' && ! in_array((string) $st, ['pendente', 'enviado', 'erro'], true)) {
            $payload['status'] = null;
        }

        $pr = $this->query('prazo_entrega');
        if ($pr !== null && $pr !== '' && ! in_array((string) $pr, ['6', '15'], true)) {
            $payload['prazo_entrega'] = null;
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::in(['pendente', 'enviado', 'erro'])],
            'prazo_entrega' => ['nullable', 'string', Rule::in(['6', '15'])],
            'search' => ['nullable', 'string', 'max:120'],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date'],
            'ordenacao' => ['nullable', 'string', Rule::in(['recent', 'oldest'])],
            'per_page' => ['nullable', 'integer', Rule::in([10, 15, 25, 50])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $from = $this->input('created_from');
            $to = $this->input('created_to');
            if ($from && $to && strtotime((string) $from) > strtotime((string) $to)) {
                $v->errors()->add('created_to', 'A data final deve ser igual ou posterior à inicial.');
            }
        });
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'status.in' => 'Status inválido.',
            'prazo_entrega.in' => 'Prazo de entrega inválido.',
            'search.max' => 'Busca deve ter no máximo 120 caracteres.',
            'ordenacao.in' => 'Ordenação inválida.',
            'per_page.in' => 'Itens por página inválido.',
        ];
    }

    public function pageSize(): int
    {
        $n = (int) ($this->validated('per_page') ?? 15);

        return in_array($n, [10, 15, 25, 50], true) ? $n : 15;
    }

    public function hasRestrictiveFilters(): bool
    {
        foreach (['status', 'prazo_entrega', 'search', 'created_from', 'created_to'] as $key) {
            $v = $this->query($key);
            if ($v !== null && trim((string) $v) !== '') {
                return true;
            }
        }

        return false;
    }
}

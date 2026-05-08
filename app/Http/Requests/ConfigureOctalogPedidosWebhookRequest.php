<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ConfigureOctalogPedidosWebhookRequest extends FormRequest
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
        $minInicio = now()->subDays(5)->startOfMinute();

        return [
            'url' => ['required', 'url', 'max:2048'],
            'limite_envio' => ['required', 'integer', 'min:1', 'max:10000'],
            'ativo_webhook' => ['required', 'boolean'],
            'data_inicio_envio' => ['required', 'date', function (string $attribute, mixed $value, \Closure $fail) use ($minInicio): void {
                if (! is_string($value) && ! is_numeric($value)) {
                    $fail('Informe uma data de início válida.');

                    return;
                }
                try {
                    $parsed = Carbon::parse($value);
                } catch (\Exception) {
                    $fail('Informe uma data de início válida.');

                    return;
                }
                if ($parsed->lt($minInicio)) {
                    $fail('A data de início não pode ser anterior a '.$minInicio->format('d/m/Y H:i').' (máximo de 5 dias retroativos).');
                }
            }],
            'headers_raw' => ['nullable', 'string', 'max:32000', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_string($value) || trim($value) === '') {
                    return;
                }
                $lines = preg_split('/\r\n|\r|\n/', $value) ?: [];
                foreach ($lines as $line) {
                    $t = trim($line);
                    if ($t === '') {
                        continue;
                    }
                    if (! str_contains($t, ':')) {
                        $fail("Os headers devem seguir o padrão \"chave:valor\" (ex.: Authorization:Bearer seu_token). Linha inválida: \"{$t}\".");

                        return;
                    }
                }
            }],
            'id_status' => ['required', 'array', 'min:1'],
            'id_status.*' => ['integer', 'min:1'],
            'contato_nome' => ['required', 'string', 'max:255'],
            'contato_email' => ['required', 'email', 'max:255'],
            'contato_celular' => ['required', 'string', 'max:32'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.required' => 'Informe a URL HTTPS do seu endpoint.',
            'limite_envio.required' => 'Informe o limite de envio.',
            'data_inicio_envio.required' => 'Informe a data de início do envio.',
            'id_status.required' => 'Selecione ao menos um status a receber.',
            'id_status.min' => 'Selecione ao menos um status a receber.',
            'contato_nome.required' => 'Informe o nome do contato técnico.',
            'contato_email.required' => 'Informe o e-mail do contato técnico.',
            'contato_celular.required' => 'Informe o celular do contato técnico.',
        ];
    }

    /**
     * Corpo JSON para POST /webhook/configurancao (chaves conforme Octalog).
     *
     * @return array<string, mixed>
     */
    public function toOctalogPayload(): array
    {
        $validated = $this->validated();
        $lines = preg_split('/\r\n|\r|\n/', (string) ($validated['headers_raw'] ?? '')) ?: [];
        $headers = [];
        foreach ($lines as $line) {
            $t = trim($line);
            if ($t !== '') {
                $headers[] = $t;
            }
        }

        $dataInicio = Carbon::parse((string) $validated['data_inicio_envio'])->format('Y-m-d H:i:s').'.000';

        return [
            'URL' => $validated['url'],
            'LimiteEnvio' => (int) $validated['limite_envio'],
            'AtivoWebhook' => (bool) $validated['ativo_webhook'],
            'DataInicioEnvio' => $dataInicio,
            'Header' => $headers,
            'IDStatus' => array_values(array_unique(array_map(
                static fn (mixed $id): int => (int) $id,
                $validated['id_status'],
            ))),
            'ContatoTecnico' => [
                [
                    'Nome' => $validated['contato_nome'],
                    'Email' => $validated['contato_email'],
                    'Celular' => $validated['contato_celular'],
                ],
            ],
        ];
    }
}

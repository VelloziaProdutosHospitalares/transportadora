<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ConfigureOctalogSacWebhookRequest extends FormRequest
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
            'url' => ['required', 'url', 'max:2048'],
            'limite_envio' => ['required', 'integer', 'min:1', 'max:10000'],
            'data_inicio_envio' => ['required', 'date'],
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
            'contato_nome.required' => 'Informe o nome do contato técnico.',
            'contato_email.required' => 'Informe o e-mail do contato técnico.',
            'contato_celular.required' => 'Informe o celular do contato técnico.',
        ];
    }

    /**
     * Corpo JSON para POST /sac/webhook/configurancao (chaves conforme Octalog).
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

        return [
            'URL' => $validated['url'],
            'LimiteEnvio' => (int) $validated['limite_envio'],
            'DataInicioEnvio' => $validated['data_inicio_envio'],
            'Header' => $headers,
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

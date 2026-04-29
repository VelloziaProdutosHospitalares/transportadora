---
name: blade-tailwind-component
description: Extrai UI repetida para componente Blade (anônimo ou de classe) com Tailwind CSS 4. Use quando pedir componente Blade reutilizável (botão, card, input, modal), tag `x-*`, ou houver duplicação clara em várias views.
---

# Blade e componentes com Tailwind 4

## Uso e gatilhos

- Pedidos do tipo: “criar componente Blade”, “extrair componente”, “UI reutilizável”, `x-botão`, `x-card`.
- Código Blade repetido em mais de um arquivo com as mesmas classes utilitárias.

## Passos

### 1. Definir API do componente

- Dados de entrada → `@props`.
- Conteúdo variável → `{{ $slot }}` ou slots nomeados.
- Variantes (ex. primário / perigo) → prop e `match` em `@php`.

### 2. Escolher tipo

| Situação | Tipo |
| -------- | ---- |
| Só markup + props simples | **Anônimo** — `resources/views/components/nome.blade.php` |
| Lógica PHP no componente | **Classe** — `php artisan make:component Nome` |

### 3. Criar arquivos

Componente **anônimo**: criar o `.blade.php` manualmente (subpastas viram ponto: `form/input` → `<x-form.input>`).

```bash
php artisan make:component MeuComponenteDeClasse
```

### 4. Implementar (exemplo anônimo — botão)

`resources/views/components/button.blade.php`:

```blade
@props([
    'variant' => 'primary',
    'type'    => 'button',
])

@php
$classes = match($variant) {
    'danger'   => 'bg-danger text-white hover:opacity-90',
    'outline'  => 'border border-primary text-primary hover:bg-primary hover:text-white',
    default    => 'bg-primary text-white hover:opacity-90',
};
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => "inline-flex items-center px-4 py-2 rounded font-medium transition-opacity $classes"]) }}
>
    {{ $slot }}
</button>
```

Uso:

```blade
<x-button type="submit">Salvar</x-button>
<x-button type="button" variant="danger">Excluir</x-button>
```

### 5. Substituir duplicações

```bash
rg 'class="border rounded px-3 py-2' resources/views/
```

### 6. Acessibilidade mínima

- Todo `<input>` com `<label>` associado (`for` / `id`).
- Ações destrutivas com texto ou `aria-label` claro.
- `type="button"` quando não for submeter formulário.

## Convenções do projeto

- Estilos com **Tailwind 4** e tokens do `@theme` em `resources/css/app.css` (`primary`, `danger`, …).
- Sem **Livewire** nos exemplos (`wire:*` não se aplica a este stack).

## Mapa de tags (exemplos)

| Tag | Arquivo típico |
| --- | --------------- |
| `<x-button>` | `resources/views/components/button.blade.php` |
| `<x-card>` | `resources/views/components/card.blade.php` |
| `<x-form.input>` | `resources/views/components/form/input.blade.php` |

## Checklist

- [ ] Props e slots definidos; variantes cobertas.
- [ ] `@vite` permanece só no layout (não no componente).
- [ ] Labels e mensagens de erro em pt-BR nas telas que usam o componente.

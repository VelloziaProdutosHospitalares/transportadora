---
name: laravel-blade-feature-slice
description: Implementa feature CRUD Laravel 13 com Blade e Tailwind 4 (migration, model, factory, Form Request, controller resource, rotas, views). Use para “novo CRUD”, “novo modelo”, listagem/cadastro/edição em Blade.
---

# Feature slice — Laravel + Blade

## Uso e gatilhos

- “Criar CRUD”, “nova feature”, “novo recurso”, telas `index` / `create` / `edit` / `show`.
- Deve seguir `projeto.mdc`: sem Inertia/Livewire; stack PHP/Laravel/Vite/Tailwind do repositório.

## Passos

1. **Migration** — `php artisan make:migration create_{recurso}_table` — colunas em inglês, `down()` reversível, FKs com `constrained()` quando couber.
2. **Model** — `php artisan make:model {Recurso}` — `$fillable`, relacionamentos tipados, scopes se necessário.
3. **Factory** — `php artisan make:factory {Recurso}Factory --model={Recurso}`.
4. **Form Requests** — `Store*` / `Update*` com `rules()` e `messages()` em pt-BR.
5. **Policy** — opcional; `php artisan make:policy {Recurso}Policy --model={Recurso}` (descoberta automática no Laravel 13 se seguir o nome).
6. **Controller** — `php artisan make:controller {Recurso}Controller --resource --model={Recurso}` — `index` com `paginate`, `store`/`update` com `validated()`.
7. **Rotas** — `routes/web.php`: `Route::resource(...)` ou `->only([...])`; middleware `auth` quando o app exigir.
8. **Views** — `resources/views/{recurso}/` com `@extends('layouts.app')`; formulários com `@csrf`.
9. **Pint** — `./vendor/bin/pint` nos PHP alterados.

## Exemplo de model (trecho)

```php
class Frete extends Model
{
    protected $fillable = ['origem', 'destino', 'valor', 'status', 'motorista_id'];

    public function motorista(): BelongsTo
    {
        return $this->belongsTo(Motorista::class);
    }

    public function scopeAtivo(Builder $query): Builder
    {
        return $query->where('status', 'ativo');
    }
}
```

## Exemplo de rota

```php
Route::resource('fretes', FreteController::class);
```

## Exemplo de view `index` (trecho)

```blade
@extends('layouts.app')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold">Fretes</h1>
    @forelse ($fretes as $frete)
        {{-- linha --}}
    @empty
        <p class="text-gray-500">Nenhum registro encontrado.</p>
    @endforelse
    {{ $fretes->links() }}
</div>
@endsection
```

## Comandos de referência

```bash
php artisan make:migration create_fretes_table
php artisan make:model Frete
php artisan make:factory FreteFactory --model=Frete
php artisan make:request StoreFreteRequest
php artisan make:request UpdateFreteRequest
php artisan make:policy FretePolicy --model=Frete
php artisan make:controller FreteController --resource --model=Frete
php artisan migrate
./vendor/bin/pint
```

## Checklist

- [ ] Nomes de rota RESTful (`fretes.index`, …).
- [ ] Validação e labels em pt-BR.
- [ ] Mudança mínima: não gerar CRUD duplicado se o recurso já existir no repo.

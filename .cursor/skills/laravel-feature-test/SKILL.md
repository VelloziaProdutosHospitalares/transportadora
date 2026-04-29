---
name: laravel-feature-test
description: Escreve testes de feature HTTP com PHPUnit 12 no Laravel 13 — happy path e erro de validação. Use para cobrir rotas, formulários e controllers resourceful.
---

# Testes de feature (PHPUnit 12)

## Uso e gatilhos

- “Escrever teste”, “teste HTTP”, “cobrir store/update”, “testar rota”.
- Após novo CRUD ou alteração de fluxo que exija regressão.

## Passos

1. Criar classe — `php artisan make:test NomeDoTeste` → em geral `tests/Feature/NomeDoTeste.php`.
2. Usar `RefreshDatabase` quando houver banco.
3. Cobrir **happy path** (ex.: `store` redireciona e persiste).
4. Cobrir **erro** (ex.: validação ou 403, conforme regra de negócio).
5. Rodar — `php artisan test` ou arquivo/método específico.

## Ambiente de teste

O `phpunit.xml` do projeto define variáveis como `DB_DATABASE=testing`, `CACHE_STORE=array`, `SESSION_DRIVER=array`. O SQLite usa o caminho derivado de `config/database.php` para esse nome de banco (não assume `:memory:` só pelo arquivo PHPUnit — ver configuração efetiva ao depurar).

## Estrutura base

```php
<?php

namespace Tests\Feature;

use App\Models\Frete;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FreteTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }
}
```

## Happy path — criação

```php
public function test_usuario_pode_criar_frete(): void
{
    $this->actingAsUser();

    $response = $this->post(route('fretes.store'), [
        'origem'  => 'São Paulo',
        'destino' => 'Rio de Janeiro',
        'valor'   => 1500.00,
        'status'  => 'pendente',
    ]);

    $response->assertRedirect(route('fretes.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('fretes', [
        'origem'  => 'São Paulo',
        'destino' => 'Rio de Janeiro',
    ]);
}
```

## Erro — validação

```php
public function test_store_falha_sem_origem(): void
{
    $this->actingAsUser();

    $response = $this->post(route('fretes.store'), [
        'destino' => 'Rio de Janeiro',
        'valor'   => 1500.00,
    ]);

    $response->assertSessionHasErrors(['origem']);
    $this->assertDatabaseCount('fretes', 0);
}
```

## Outros cenários (trechos)

**Index:**

```php
$this->get(route('fretes.index'))
    ->assertOk()
    ->assertViewIs('fretes.index')
    ->assertViewHas('fretes');
```

**Update:**

```php
$frete = Frete::factory()->create();
$this->put(route('fretes.update', $frete), ['origem' => 'Campinas'] + $frete->toArray())
    ->assertRedirect();
```

**Destroy:**

```php
$frete = Frete::factory()->create();
$this->delete(route('fretes.destroy', $frete))->assertRedirect();
$this->assertModelMissing($frete);
```

**Guest:**

```php
$this->get(route('fretes.index'))->assertRedirect(route('login'));
```

## Comandos

```bash
php artisan test
php artisan test tests/Feature/FreteTest.php
php artisan test --filter=test_usuario_pode_criar_frete
```

## Tabela de asserções úteis

| Objetivo | Método |
| -------- | ------ |
| 200 | `assertOk()` |
| Redirect | `assertRedirect()` / `assertRedirect($url)` |
| Flash | `assertSessionHas('chave')` |
| Validação | `assertSessionHasErrors(['campo'])` |
| Banco | `assertDatabaseHas` / `assertDatabaseCount` |
| Model removido | `assertModelMissing($model)` |
| View | `assertViewIs`, `assertSee` |

## Checklist

- [ ] Teste usa rotas e nomes reais do projeto (`php artisan route:list`).
- [ ] Auth: mesmo padrão que a aplicação (login Breeze, só guest, etc.).

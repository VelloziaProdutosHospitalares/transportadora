---
name: serpro-consulta-nfe
description: Cliente e endpoint interno SERPRO para consulta NF-e (OAuth2, cache, retry 401, DTO para pré-preenchimento). Use para evoluir `SerproNfeService`, consulta por chave no cadastro de pedido ou envs em config/services.
---

# Consulta NF-e SERPRO

## Uso e gatilhos

- “Consulta NF-e SERPRO”, “token SERPRO”, “preencher pedido pela chave”, “OAuth2 client_credentials”.
- Ajuste de URLs trial/produção, timeout, mensagens ou conformidade / logs.

## Arquivos no repositório

| Arquivo | Função |
| ------- | ------ |
| `app/Services/SerproNfeService.php` | Token + GET `.../nfe/{chave}` + cache + retry único em 401 |
| `app/Http/Controllers/SerproNfeConsultaController.php` | `POST` JSON — `ok`, `data` ou `message` |
| `app/Http/Requests/ConsultaSerproNfeRequest.php` | Validação da chave |
| `app/DTOs/SerproNfeConsultaData.php` | `fromApiPayload` / `toFormPrefill` |
| `app/Exceptions/SerproException.php` | Erros de integração |
| `config/services.php` | Chave `serpro` |

## Rota

- **`POST`** `pedidos/consulta-nfe-serpro` — nome `pedidos.consulta-nfe-serpro`.
- Se não configurado: JSON com `ok: false` e mensagem do tipo *Integração SERPRO não configurada.* (comportamento atual do controller).

## Configuração (`.env` / `config/services.php`)

Variáveis típicas (ajustar aos nomes já usados no `config`):

- `SERPRO_CONSUMER_KEY`, `SERPRO_CONSUMER_SECRET` **ou** `SERPRO_BASIC_AUTH_BASE64` (prioridade no token).
- `SERPRO_TOKEN_URL` (default no config: gateway SP).
- `SERPRO_CONSULTA_BASE_URL` (obrigatório para chamadas de NF).
- Opcionais: `SERPRO_TIMEOUT`, `SERPRO_VERIFY_SSL`.

## Passos para evoluir a integração

1. Centralizar URLs e credenciais em `config/services.php`.
2. Manter `resolveAccessToken` / cache com TTL seguro; **um** retry após 401 em consulta.
3. Validar chave **44 dígitos** antes do HTTP (request + service).
4. Respostas internas em JSON claras para o front (`ok`, `message`, `data`).
5. Testes com `Http::fake()` — cenários token + GET OK; GET 401 depois renovação + GET OK.

## Convenções

- Header de consulta: `Authorization: Bearer <token>` uma única vez.
- Token: `application/x-www-form-urlencoded`; `grant_type=client_credentials`.
- Não logar secret nem token integral; ver `.cursor/rules/serpro-nfe.mdc` para LGPD.

## Checklist

- [ ] Base64 Basic sem newline acidental.
- [ ] Integração desligada / incompleta não quebra a tela de pedido (`isConfigured`).
- [ ] Pint nos PHP alterados.

---
name: octalog-pedido-feature
description: Fluxo de pedidos com API Octalog (envio, status, etiqueta, SAC e consultas). Use ao evoluir `PedidoController`, telas de pedidos, integração Octalog ou regras em octalog-api.mdc.
---

# Pedidos e Octalog

## Uso e gatilhos

- “Novo pedido Octalog”, “etiqueta”, “enviar pedido”, “consulta status Octalog”, “SAC / ticket”, “webhook tracking”.
- Manter consistência com `.cursor/rules/octalog-api.mdc` (injecção do service, `success`, privacidade em logs).

## Referência no código atual

| Peça | Local |
| ---- | ----- |
| Envio e persistência | `app/Http/Controllers/PedidoController.php` — `store` chama `OctalogService::sendOrders`, atualiza `status`, `octalog_response`, `url_etiqueta`, `erro_mensagem` |
| Extração da etiqueta | `PedidoController::extractLabelUrl` — chaves `UrlEtiqueta` / `urlEtiqueta` |
| Form Request de criação | `app/Http/Requests/StorePedidoRequest.php` |
| Cliente API | `app/Services/OctalogService.php` — auth com cache, retry em 401, limite de 50 pedidos por envio |
| DTO de payload | `app/DTOs/OctalogOrderData.php` |
| Exceção | `app/Exceptions/OctalogException.php` |
| Consulta por número | `app/Http/Controllers/PedidoConsultaOctalogController.php` — rotas `pedidos.consulta-octalog.*` |
| SAC (ticket / webhook UI) | `PedidoSacTicketController`, `OctalogSacWebhookConfigController`, `OctalogSacService` |
| Webhook inbound | `routes/api.php` → `OctalogWebhookController`, `OctalogInboundWebhookProcessor` |
| Model | `app/Models/Pedido.php` — status, resposta, rótulo de atividade |

## Rotas web relevantes (`routes/web.php`)

- `pedidos` — resource parcial (`index`, `create`, `store`, `show`).
- `pedidos.consulta-octalog.create` / `store` — consulta em lote na API.
- `pedidos.sac.ticket.*` — abertura/cancelamento de ticket.
- `octalog.sac.webhook.*` — configurar/consultar webhook SAC.

## Ao implementar ou alterar o fluxo de criação

1. **Validação** — manter `StorePedidoRequest` alinhado aos campos esperados por `OctalogOrderData::toPayload()` e ao que a view envia.
2. **Transação** — o projeto gera `numero_pedido` estável após o `id` (ver `store`); preservar essa estratégia ou documentar mudança.
3. **Resposta da API** — só tratar como sucesso quando `$result['success'] === true`; caso contrário persistir erro e mensagem formatada (`formatOctalogErrors`).
4. **Exceção de rede/auth** — capturar `OctalogException`, atualizar pedido como `erro` quando fizer sentido, flash amigável.
5. **Etiqueta** — aceitar estrutura lista ou objeto; nunca assumir que `UrlEtiqueta` existe.
6. **UI** — Blade em `resources/views/pedidos/`; mensagens e labels em pt-BR; `@csrf` em POST.

## Checklist

- [ ] Nenhum `new OctalogService` solto em produção (apenas DI).
- [ ] Logs sem PII do destinatário em produção.
- [ ] Testes com `Http::fake()` / mocks onde já for padrão no projeto (`tests/Feature/Octalog*`).

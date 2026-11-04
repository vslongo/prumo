# Messaging (README pra humanos)

> Leia antes o `CLAUDE.md` da raiz. Regras de multi-tenant, testes e DoD de lá valem aqui.
> **Fase: Core.**

## Propósito
Envio e log de WhatsApp pela API oficial.

## Tabelas
messages (templates ficam no enum `MessageTemplate`; tabela própria só se precisar editar template sem deploy)

## API pública (outros módulos só usam isto)
- Actions: QueueWhatsappMessage, HandleDeliveryStatus, ResolveWhatsappSender, FindConsentingRecipient, ExportCustomerMessages
- Rota: POST /api/internal/whatsapp/status (só o worker, HMAC `X-Signature: sha256=...`)
- Fila para o worker: lista Redis `whatsapp:outbound` com `{tenant_id, message_id, template, language, to, params, sender_phone_number_id}`
- Eventos emitidos: nenhum por enquanto
- Eventos ouvidos: QuoteSent, ServiceOrderStatusChanged (ready), CustomerAnonymized (apaga `to` e `params` das mensagens do cliente)

## Estados
queued|sent|delivered|read|failed

## Regras de negócio
Somente Cloud API oficial; templates utility; fallback wa.me; worker Node sem regra de negócio.
- Número único do Prumo agora, número da oficina no futuro (ADR 0003). O remetente sai sempre do `ResolveWhatsappSender`.
- Envio automático só para cliente com celular e `consent_whatsapp_at`. Sem isso, ou sem `WHATSAPP_PHONE_NUMBER_ID`, nada é enfileirado e fica o wa.me.
- Idempotente por `idempotency_key`: um envio por link de orçamento e um aviso de OS pronta por OS.
- Status só avança (queued → sent → delivered → read; failed só de queued/sent). Callback repetido ou fora de ordem é ignorado.

## Estrutura
`backend/app/Modules/Messaging/` → Actions/ Data/ Enums/ Events/ Listeners/ Jobs/ Models/ Policies/ Http/ routes.php
`frontend/src/features/messaging/` (se tiver UI)

## Testes obrigatórios
- Feature test por Action (feliz, regra violada, sem permissão)
- `TenantIsolationTest` (exceto tabelas globais documentadas)
- Toda transição de estado válida/inválida

## Integrações
WhatsApp Cloud API

## Changelog do módulo
- 2026-10-27: criado/atualizado com decisões de core primeiro, técnicos, service_types e upload direto.
- 2026-10-28: módulo criado no backend: tabela messages, QueueWhatsappMessage, job para a fila do worker, callback de status assinado e listeners de QuoteSent e OS pronta. Worker Node e webhook da Meta ficam para o próximo PR.
- 2026-11-04: ExportCustomerMessages (exportação LGPD) e listener ScrubAnonymizedCustomerMessages (CustomerAnonymized → `to` vazio e `params` vazios; linha e status ficam).
- 2026-11-04: worker Node (`worker-whatsapp/`): consome a fila com lista de processamento, envia template pela Cloud API, repete erro temporário, recebe o webhook da Meta (status por `biz_opaque_callback_data` = `tenant_id:message_id`) e devolve tudo pelo callback assinado.

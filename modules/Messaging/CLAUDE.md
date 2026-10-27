# Módulo Messaging

> Leia antes o `CLAUDE.md` da raiz. Regras de multi-tenant, testes e DoD de lá valem aqui.
> **Fase: Core.**

## Propósito
Envio e log de WhatsApp pela API oficial.

## Tabelas
messages, message_templates

## API pública (outros módulos só usam isto)
- Actions: QueueWhatsappMessage, HandleDeliveryStatus
- Eventos emitidos: MessageSent, MessageFailed
- Eventos ouvidos: QuoteSent, ServiceOrderStatusChanged (ready)

## Estados
queued|sent|delivered|read|failed

## Regras de negócio
Somente Cloud API oficial; templates utility; fallback wa.me; worker Node sem regra de negócio.

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

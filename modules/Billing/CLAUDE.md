# Módulo Billing

> Leia antes o `CLAUDE.md` da raiz. Regras de multi-tenant, testes e DoD de lá valem aqui.
> **Fase: Core.**

## Propósito
Assinatura do SaaS via Asaas, planos e trial.

## Tabelas
subscriptions, plans

## API pública (outros módulos só usam isto)
- Actions: StartTrial, Subscribe, HandleAsaasWebhook
- Eventos emitidos: SubscriptionStatusChanged
- Eventos ouvidos: TenantCreated

## Estados
trialing|active|past_due|suspended|canceled

## Regras de negócio
past_due >7 dias → somente leitura; nunca apagar dados; webhooks idempotentes.

## Estrutura
`backend/app/Modules/Billing/` → Actions/ Data/ Enums/ Events/ Listeners/ Jobs/ Models/ Policies/ Http/ routes.php
`frontend/src/features/billing/` (se tiver UI)

## Testes obrigatórios
- Feature test por Action (feliz, regra violada, sem permissão)
- `TenantIsolationTest` (exceto tabelas globais documentadas)
- Toda transição de estado válida/inválida

## Integrações
Asaas

## Changelog do módulo
- 2026-10-27: criado/atualizado com decisões de core primeiro, técnicos, service_types e upload direto.

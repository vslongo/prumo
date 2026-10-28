# Módulo Tenancy

> Leia antes o `CLAUDE.md` da raiz. Regras de multi-tenant, testes e DoD de lá valem aqui.
> **Fase: Core.**

## Propósito
Tenants, contexto atual e global scope (RLS do Postgres é etapa pós-core opcional).

## Tabelas
tenants

## API pública (outros módulos só usam isto)
- Actions: TenantContext::run/set/id, ResolvePublicToken, CreateTenant (nasce `trialing`, `trial_ends_at` = hoje + `config/plans.php` trial_days)
- Eventos emitidos: TenantCreated
- Eventos ouvidos: SubscriptionStatusChanged (Billing)

## Estados
trialing|active|past_due|suspended|canceled

## Regras de negócio
Banco único com tenant_id; único lugar onde withoutGlobalScope é permitido.

## Estrutura
`backend/app/Modules/Tenancy/` → Actions/ Data/ Enums/ Events/ Listeners/ Jobs/ Models/ Policies/ Http/ routes.php
`frontend/src/features/tenancy/` (se tiver UI)

## Testes obrigatórios
- Feature test por Action (feliz, regra violada, sem permissão)
- `TenantIsolationTest` (exceto tabelas globais documentadas)
- Toda transição de estado válida/inválida

## Integrações
nenhuma

## Changelog do módulo
- 2026-10-27: criado/atualizado com decisões de core primeiro, técnicos, service_types e upload direto.
- 2026-10-27: TenantContext (scoped), TenantScope (falha fechada sem tenant), BelongsToTenant e ResolvePublicToken (único `withoutGlobalScope`, para links públicos). Middleware `tenant` roda antes do route model binding.
- 2026-10-28: CreateTenant + evento TenantCreated (após commit). Chamada pelo SignUp do Auth.

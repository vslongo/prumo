# Módulo Billing

> Leia antes o `CLAUDE.md` da raiz. Regras de multi-tenant, testes e DoD de lá valem aqui.
> **Fase: Core.** Decisões em `docs/adr/0004-assinatura-asaas-e-somente-leitura.md`.

## Propósito
Assinatura do SaaS via Asaas, planos (limite de usuários), trial e modo somente leitura.

## Tabelas
- subscriptions (tenant_id único, plan, status, asaas_customer_id, asaas_subscription_id, invoice_url, error, paid_until, past_due_since, canceled_at). Sem CPF/CNPJ.
- billing_webhook_events (id do evento do Asaas, event, processed_at): tabela de sistema, sem tenant_id, só para idempotência.

## API pública (outros módulos só usam isto)
- Actions: ResolveTenantAccess (pode escrever? estado, plano, limite), EnsureSeatAvailable (limite de usuários; chamado pelo Auth), Subscribe, ChangePlan, CancelSubscription, HandleAsaasWebhook, GetBillingOverview. Internas: ApplySubscriptionStatus, ProvisionAsaasSubscription
- Middleware: EnsureTenantCanWrite (no grupo `tenant`): escrita em somente leitura → 402 `read_only`. Liberado: GET, /api/billing*, /api/logout
- Rotas: GET /api/billing/access (todos), GET /api/billing, POST /api/billing/subscribe, PATCH /api/billing/plan, POST /api/billing/cancel (só owner, Gate `manage-billing`), POST /api/webhooks/asaas (header `asaas-access-token`)
- Jobs: ProvisionAsaasSubscriptionJob (criptografado, leva o CPF/CNPJ), SyncAsaasSubscriptionValueJob, CancelAsaasSubscriptionJob
- Eventos emitidos: – (status espelhado em tenants.status via Tenancy ChangeTenantStatus)
- Eventos ouvidos: –

## Estados
Assinatura: pending → active ⇄ past_due → canceled; canceled → pending (assinar de novo).
Acesso (calculado): trial | active | past_due (carência) | canceled (até paid_until) | read_only.

## Regras de negócio
- Pix, boleto ou cartão na fatura do Asaas (billingType UNDEFINED). Primeira cobrança no fim do trial, ou hoje se já acabou.
- Trial vencido sem pagamento → somente leitura na hora. past_due > 7 dias → somente leitura. Cancelada: usa até paid_until.
- Nunca apagar dados. Webhooks idempotentes pelo id do evento; atraso de fatura já paga é ignorado.
- Limite: Solo 1, Oficina 5, Pro ilimitado (usuários ativos + convites pendentes). Trial sem limite. Descer de plano exige caber os ativos.

## Estrutura
`backend/app/Modules/Billing/` → Actions/ Data/ Enums/ Http/ Jobs/ Models/ Support/AsaasClient routes.php
`frontend/src/features/billing/` → api.ts, components/BillingCard (Configurações › Assinatura, só dono), components/BillingBanner (aviso no topo)

## Testes obrigatórios
- Feature test por Action (feliz, regra violada, sem permissão)
- `TenantIsolationTest` (exceto tabelas globais documentadas)
- Toda transição de estado válida/inválida

## Integrações
Asaas (API v3; sandbox por padrão em `ASAAS_BASE_URL`). Nunca chamado na request: só em jobs.

## Changelog do módulo
- 2026-10-27: criado/atualizado com decisões de core primeiro, técnicos, service_types e upload direto.
- 2026-11-04: implementado (ADR 0004). Assinatura Asaas pela fila, webhook idempotente, somente leitura calculado, limite de usuários por plano, aba Assinatura e aviso no app.

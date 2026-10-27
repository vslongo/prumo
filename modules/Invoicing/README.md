# Invoicing (README pra humanos)

> Leia antes o `CLAUDE.md` da raiz. Regras de multi-tenant, testes e DoD de lá valem aqui.
> **Fase: Pós-core.** Não implementar sem pedido explícito.

## Propósito
NFS-e via Focus NFe.

## Tabelas
invoices

## API pública (outros módulos só usam isto)
- Actions: IssueServiceInvoice, CancelServiceInvoice
- Eventos emitidos: InvoiceAuthorized, InvoiceFailed
- Eventos ouvidos: ServiceOrderClosed

## Estados
pending|processing|authorized|error|canceled

## Regras de negócio
Idempotente por ref; XML/PDF no R2.

## Estrutura
`backend/app/Modules/Invoicing/` → Actions/ Data/ Enums/ Events/ Listeners/ Jobs/ Models/ Policies/ Http/ routes.php
`frontend/src/features/invoicing/` (se tiver UI)

## Testes obrigatórios
- Feature test por Action (feliz, regra violada, sem permissão)
- `TenantIsolationTest` (exceto tabelas globais documentadas)
- Toda transição de estado válida/inválida

## Integrações
Focus NFe

## Changelog do módulo
- 2026-10-27: criado/atualizado com decisões de core primeiro, técnicos, service_types e upload direto.

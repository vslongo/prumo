# Payments (README pra humanos)

> Leia antes o `CLAUDE.md` da raiz. Regras de multi-tenant, testes e DoD de lá valem aqui.
> **Fase: Logo após o core.**

## Propósito
Recebimentos da oficina.

## Tabelas
payments

## API pública (outros módulos só usam isto)
- Actions: RegisterPayment, CreatePixCharge
- Eventos emitidos: PaymentReceived
- Eventos ouvidos: Webhook Asaas

## Estados
pending|paid|canceled|refunded

## Regras de negócio
Sem soft delete; estorno = novo status.

## Estrutura
`backend/app/Modules/Payments/` → Actions/ Data/ Enums/ Events/ Listeners/ Jobs/ Models/ Policies/ Http/ routes.php
`frontend/src/features/payments/` (se tiver UI)

## Testes obrigatórios
- Feature test por Action (feliz, regra violada, sem permissão)
- `TenantIsolationTest` (exceto tabelas globais documentadas)
- Toda transição de estado válida/inválida

## Integrações
Asaas

## Changelog do módulo
- 2026-10-27: criado/atualizado com decisões de core primeiro, técnicos, service_types e upload direto.

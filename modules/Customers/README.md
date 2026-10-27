# Customers (README pra humanos)

> Leia antes o `CLAUDE.md` da raiz. Regras de multi-tenant, testes e DoD de lá valem aqui.
> **Fase: Core.**

## Propósito
Clientes da oficina, consentimento e LGPD.

## Tabelas
customers

## API pública (outros módulos só usam isto)
- Actions: CreateCustomer, RecordWhatsappConsent, ExportCustomerData, AnonymizeCustomer
- Eventos emitidos: CustomerCreated, CustomerAnonymized
- Eventos ouvidos: –

## Estados
–

## Regras de negócio
CPF/telefone encrypted + hash; sem consentimento não recebe WhatsApp.

## Estrutura
`backend/app/Modules/Customers/` → Actions/ Data/ Enums/ Events/ Listeners/ Jobs/ Models/ Policies/ Http/ routes.php
`frontend/src/features/customers/` (se tiver UI)

## Testes obrigatórios
- Feature test por Action (feliz, regra violada, sem permissão)
- `TenantIsolationTest` (exceto tabelas globais documentadas)
- Toda transição de estado válida/inválida

## Integrações
nenhuma

## Changelog do módulo
- 2026-10-27: criado/atualizado com decisões de core primeiro, técnicos, service_types e upload direto.

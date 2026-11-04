# Módulo Customers

> Leia antes o `CLAUDE.md` da raiz. Regras de multi-tenant, testes e DoD de lá valem aqui.
> **Fase: Core.**

## Propósito
Clientes da oficina, consentimento e LGPD.

## Tabelas
customers

## API pública (outros módulos só usam isto)
- Actions: CreateCustomer, SearchCustomers, UpdateCustomer (inclui consentimento), ExportCustomerData, AnonymizeCustomer
- Eventos emitidos: CustomerCreated, CustomerAnonymized
- Eventos ouvidos: –

## Estados
–

## Regras de negócio
CPF/telefone encrypted + hash; sem consentimento não recebe WhatsApp.
- Consentimento: marcar grava data e origem (só se ainda não tinha); desmarcar revoga e apaga os dois.
- Exportar e anonimizar: só owner/admin. Anonimizar é irreversível e idempotente (ADR 0005): apaga nome, e-mail, telefone, documento, hashes e consentimento; mantém o cadastro anônimo com veículos, orçamentos, OS e aprovações assinadas. Cliente anonimizado não pode ser editado.

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
- 2026-10-27: CreateCustomer/SearchCustomers implementados. Telefone e CPF/CNPJ só dígitos, cast `encrypted` + `*_hash` (HMAC-SHA256 com APP_KEY) para busca exata. Consentimento de WhatsApp gravado com data e origem na criação.
- 2026-11-04: UpdateCustomer (PATCH, com consentimento e revogação), ExportCustomerData (GET customers/{id}/export, JSON) e AnonymizeCustomer (POST customers/{id}/anonymize, coluna `anonymized_at`, evento CustomerAnonymized). Decisões na ADR 0005.

# Inventory (README pra humanos)

> Leia antes o `CLAUDE.md` da raiz. Regras de multi-tenant, testes e DoD de lá valem aqui.
> **Fase: Pós-core.** Não implementar sem pedido explícito.

## Propósito
Peças e movimentações.

## Tabelas
parts, stock_movements

## API pública (outros módulos só usam isto)
- Actions: CreatePart, RegisterMovement
- Eventos emitidos: StockBelowMinimum
- Eventos ouvidos: ServiceOrderStatusChanged

## Estados
–

## Regras de negócio
Saldo = soma das movimentações.

## Estrutura
`backend/app/Modules/Inventory/` → Actions/ Data/ Enums/ Events/ Listeners/ Jobs/ Models/ Policies/ Http/ routes.php
`frontend/src/features/inventory/` (se tiver UI)

## Testes obrigatórios
- Feature test por Action (feliz, regra violada, sem permissão)
- `TenantIsolationTest` (exceto tabelas globais documentadas)
- Toda transição de estado válida/inválida

## Integrações
nenhuma

## Changelog do módulo
- 2026-10-27: criado/atualizado com decisões de core primeiro, técnicos, service_types e upload direto.

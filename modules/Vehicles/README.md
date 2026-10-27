# Vehicles (README pra humanos)

> Leia antes o `CLAUDE.md` da raiz. Regras de multi-tenant, testes e DoD de lá valem aqui.
> **Fase: Core (registro global no pós-core).**

## Propósito
Veículos da oficina; no pós-core vira registro global com histórico.

## Tabelas
vehicles (core: por tenant). Pós-core: vehicles global, tenant_vehicles, vehicle_ownerships, vehicle_history

## API pública (outros módulos só usam isto)
- Actions: CreateVehicle, FindVehicleByPlate, VehicleBelongsToCustomer
- Eventos emitidos: VehicleCreated
- Eventos ouvidos: –

## Estados
–

## Regras de negócio
Placa normalizada única por tenant + VIN opcional. Histórico no core = lista de OS do veículo. Pós-core: history append-only, sobrevive à troca de dono, outra oficina só vê resumo autorizado.

## Estrutura
`backend/app/Modules/Vehicles/` → Actions/ Data/ Enums/ Events/ Listeners/ Jobs/ Models/ Policies/ Http/ routes.php
`frontend/src/features/vehicles/` (se tiver UI)

## Testes obrigatórios
- Feature test por Action (feliz, regra violada, sem permissão)
- `TenantIsolationTest` (exceto tabelas globais documentadas)
- Toda transição de estado válida/inválida

## Integrações
nenhuma

## Changelog do módulo
- 2026-10-27: criado/atualizado com decisões de core primeiro, técnicos, service_types e upload direto.
- 2026-10-27: CreateVehicle/FindVehicleByPlate implementados. Placa normalizada (antiga ou Mercosul) única por tenant entre não deletados (índice parcial).
- 2026-10-27: ListCustomerVehicles + GET /api/customers/{customerId}/vehicles.

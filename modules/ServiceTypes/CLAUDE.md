# Módulo ServiceTypes

> Leia antes o `CLAUDE.md` da raiz. Regras de multi-tenant, testes e DoD de lá valem aqui.
> **Fase: Core.**

## Propósito
Tipos de serviço com campos específicos definidos por schema JSON.

## Tabelas
service_types (tenant_id null = padrão do sistema)

## API pública (outros módulos só usam isto)
- Actions: CreateServiceType, UpdateServiceType, ValidateItemFields
- Eventos emitidos: ServiceTypeUpdated
- Eventos ouvidos: –

## Estados
–

## Regras de negócio
fields_schema = [{key,label,type: text|number|select|boolean|date, required, options?, unit?}]. Ex.: troca de óleo (oil_brand, viscosity, liters, filter_changed, next_change_km); troca de junta (gasket, side, resurfacing). Valores salvos em fields (jsonb) no item + fields_schema_snapshot. Tipos padrão são somente leitura pras oficinas.

## Estrutura
`backend/app/Modules/ServiceTypes/` → Actions/ Data/ Enums/ Events/ Listeners/ Jobs/ Models/ Policies/ Http/ routes.php
`frontend/src/features/serviceTypes/` (se tiver UI)

## Testes obrigatórios
- Feature test por Action (feliz, regra violada, sem permissão)
- `TenantIsolationTest` (exceto tabelas globais documentadas)
- Toda transição de estado válida/inválida

## Integrações
nenhuma

## Changelog do módulo
- 2026-10-27: criado/atualizado com decisões de core primeiro, técnicos, service_types e upload direto.

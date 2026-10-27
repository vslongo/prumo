# _TEMPLATE (README pra humanos)

> Leia antes o `CLAUDE.md` da raiz. Regras de multi-tenant, testes e DoD de lá valem aqui.
> **Fase: <core | logo após | pós-core>.**

## Propósito
<propósito>

## Tabelas
<tabelas>

## API pública (outros módulos só usam isto)
- Actions: <Actions públicas>
- Eventos emitidos: <eventos emitidos>
- Eventos ouvidos: <eventos ouvidos>

## Estados
<estados>

## Regras de negócio
<regras>

## Estrutura
`backend/app/Modules/_TEMPLATE/` → Actions/ Data/ Enums/ Events/ Listeners/ Jobs/ Models/ Policies/ Http/ routes.php
`frontend/src/features/_TEMPLATE/` (se tiver UI)

## Testes obrigatórios
- Feature test por Action (feliz, regra violada, sem permissão)
- `TenantIsolationTest` (exceto tabelas globais documentadas)
- Toda transição de estado válida/inválida

## Integrações
<integrações ou nenhuma>

## Changelog do módulo
- 2026-10-27: criado/atualizado com decisões de core primeiro, técnicos, service_types e upload direto.

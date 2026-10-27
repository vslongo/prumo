# Audit (README pra humanos)

> Leia antes o `CLAUDE.md` da raiz. Regras de multi-tenant, testes e DoD de lá valem aqui.
> **Fase: Logo após o core.**

## Propósito
Log de auditoria.

## Tabelas
audit_logs

## API pública (outros módulos só usam isto)
- Actions: RecordAudit
- Eventos emitidos: –
- Eventos ouvidos: Eventos dos módulos

## Estados
–

## Regras de negócio
Append-only; sem dado pessoal sensível em claro.

## Estrutura
`backend/app/Modules/Audit/` → Actions/ Data/ Enums/ Events/ Listeners/ Jobs/ Models/ Policies/ Http/ routes.php
`frontend/src/features/audit/` (se tiver UI)

## Testes obrigatórios
- Feature test por Action (feliz, regra violada, sem permissão)
- `TenantIsolationTest` (exceto tabelas globais documentadas)
- Toda transição de estado válida/inválida

## Integrações
nenhuma

## Changelog do módulo
- 2026-10-27: criado/atualizado com decisões de core primeiro, técnicos, service_types e upload direto.

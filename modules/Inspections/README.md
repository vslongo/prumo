# Inspections (README pra humanos)

> Leia antes o `CLAUDE.md` da raiz. Regras de multi-tenant, testes e DoD de lá valem aqui.
> **Fase: Pós-core.** Não implementar sem pedido explícito.

## Propósito
Relatório de entrada opcional: checklist, fotos, panorama, PDF, assinatura.

## Tabelas
inspections, inspection_items

## API pública (outros módulos só usam isto)
- Actions: StartInspection, RecordInspectionItem, CompleteInspection, SignInspection
- Eventos emitidos: InspectionCompleted, InspectionSigned
- Eventos ouvidos: –

## Estados
draft|completed|signed

## Regras de negócio
Imutável após signed; usa Media (upload direto); itens attention/critical viram sugestão de orçamento.

## Estrutura
`backend/app/Modules/Inspections/` → Actions/ Data/ Enums/ Events/ Listeners/ Jobs/ Models/ Policies/ Http/ routes.php
`frontend/src/features/inspections/` (se tiver UI)

## Testes obrigatórios
- Feature test por Action (feliz, regra violada, sem permissão)
- `TenantIsolationTest` (exceto tabelas globais documentadas)
- Toda transição de estado válida/inválida

## Integrações
Media

## Changelog do módulo
- 2026-10-27: criado/atualizado com decisões de core primeiro, técnicos, service_types e upload direto.

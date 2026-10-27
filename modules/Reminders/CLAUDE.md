# Módulo Reminders

> Leia antes o `CLAUDE.md` da raiz. Regras de multi-tenant, testes e DoD de lá valem aqui.
> **Fase: Pós-core.** Não implementar sem pedido explícito.

## Propósito
Lembretes por data/km (ex.: next_change_km da troca de óleo).

## Tabelas
reminders

## API pública (outros módulos só usam isto)
- Actions: ScheduleReminder, CancelReminder
- Eventos emitidos: ReminderDue
- Eventos ouvidos: ServiceOrderClosed

## Estados
scheduled|sent|failed|canceled

## Regras de negócio
Só com consentimento; scheduler diário.

## Estrutura
`backend/app/Modules/Reminders/` → Actions/ Data/ Enums/ Events/ Listeners/ Jobs/ Models/ Policies/ Http/ routes.php
`frontend/src/features/reminders/` (se tiver UI)

## Testes obrigatórios
- Feature test por Action (feliz, regra violada, sem permissão)
- `TenantIsolationTest` (exceto tabelas globais documentadas)
- Toda transição de estado válida/inválida

## Integrações
Messaging

## Changelog do módulo
- 2026-10-27: criado/atualizado com decisões de core primeiro, técnicos, service_types e upload direto.

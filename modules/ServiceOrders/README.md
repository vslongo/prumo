# ServiceOrders (README pra humanos)

> Leia antes o `CLAUDE.md` da raiz. Regras de multi-tenant, testes e DoD de lá valem aqui.
> **Fase: Core.**

## Propósito
OS: centro dos dados (cliente, veículo, itens, valores, técnicos, dados do orçamento).

## Tabelas
service_orders (origin from_quote|direct, quote_id), service_order_items (technician_id, service_type_id, fields jsonb, done_at)

## API pública (outros módulos só usam isto)
- Actions: OpenServiceOrder (direta), ConvertApprovedQuoteToServiceOrder, AddItem, MarkItemDone, TransitionServiceOrder, CloseServiceOrder
- Eventos emitidos: ServiceOrderOpened, ServiceOrderStatusChanged, ServiceOrderClosed
- Eventos ouvidos: QuoteApproved

## Estados
open|in_progress|ready|delivered|closed|canceled

## Regras de negócio
Número sequencial por tenant; todo item tem técnico; valores em centavos; fechar exige itens com preço, técnico, feitos e km de saída.

## Estrutura
`backend/app/Modules/ServiceOrders/` → Actions/ Data/ Enums/ Events/ Listeners/ Jobs/ Models/ Policies/ Http/ routes.php
`frontend/src/features/serviceOrders/` (se tiver UI)

## Testes obrigatórios
- Feature test por Action (feliz, regra violada, sem permissão)
- `TenantIsolationTest` (exceto tabelas globais documentadas)
- Toda transição de estado válida/inválida

## Integrações
Messaging (OS pronta), Media (fotos)

## Changelog do módulo
- 2026-10-27: criado/atualizado com decisões de core primeiro, técnicos, service_types e upload direto.

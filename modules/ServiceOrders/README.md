# ServiceOrders (README pra humanos)

> Leia antes o `CLAUDE.md` da raiz. Regras de multi-tenant, testes e DoD de lá valem aqui.
> **Fase: Core.**

## Propósito
OS: centro dos dados (cliente, veículo, itens, valores, técnicos, dados do orçamento).

## Tabelas
service_orders (origin from_quote|direct, quote_id), service_order_items (technician_id, service_type_id, fields jsonb, done_at)

## API pública (outros módulos só usam isto)
- Actions: OpenServiceOrder (direta), ListServiceOrders, ConvertApprovedQuoteToServiceOrder, AddServiceOrderItem, MarkItemDone, TransitionServiceOrder, CloseServiceOrder, RequestServiceOrderItemPhotoUpload, ConfirmServiceOrderItemPhoto, RemoveServiceOrderItemPhoto, ListServiceOrderPhotos (interna: NextServiceOrderNumber)
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
- 2026-10-27: implementado. Número por tenant com `pg_advisory_xact_lock` + unique (tenant_id, number). Listener em fila `CreateServiceOrderFromApprovedQuote` (QuoteApproved → OS) restaura o tenant; idempotente por `quote_id` unique. Itens só mudam em open|in_progress. `TransitionServiceOrder` não fecha: fechar é `CloseServiceOrder` (delivered, ≥1 item, todos feitos, km saída ≥ km entrada). Histórico do veículo = `GET /api/service-orders?vehicle_id=`. Audit/history da transição: pendente (módulo Audit).
- 2026-10-27: listener `CreateServiceOrderFromApprovedQuote` é `ShouldQueueAfterCommit`.
- 2026-10-27: ListServiceOrders aceita `quote_id` (achar a OS gerada por um orçamento) e devolve resumo de cliente/veículo.
- 2026-11-03: fotos por item (`service_order_item`), mesmas rotas do orçamento sob `service-orders/{serviceOrder}`. Aceita foto até `delivered` (`acceptsPhotos`: não closed/canceled). A lista inclui as fotos do item de orçamento de origem (via `quote_item_id`, `from_quote: true`), que não são removidas pela OS.

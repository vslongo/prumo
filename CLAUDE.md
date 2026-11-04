# CLAUDE.md — SaaS de gestão para oficinas mecânicas

> Leia este arquivo inteiro antes de qualquer mudança. Depois leia `docs/architecture.md` e o `modules/<Modulo>/CLAUDE.md` do módulo que vai tocar.
> Idioma: código, nomes de classe/tabela/coluna em **inglês**; textos de UI, mensagens ao usuário, docs e commits em **português do Brasil**.

## 1. Visão do produto
SaaS B2B multi-tenant para oficinas mecânicas pequenas (2–10 pessoas). Cada oficina = 1 **tenant**.
Proposta: OS e orçamento pelo celular, aprovação do orçamento pelo cliente via link no WhatsApp, histórico do veículo, inspeção inicial opcional com fotos, lembretes, cobrança e NFS-e.

### Core primeiro (decisão do produto)
O MVP é **só o core**:
1. Orçamento criado pelo técnico → enviado ao cliente → cliente aprova e **assina** no link → **OS gerada automaticamente** com os dados do orçamento.
2. Criação de **OS direta**, sem orçamento.
A **OS é o ponto central dos dados**: cliente, veículo, itens (produtos e serviços) com valores, técnico que executou cada item, campos específicos do tipo de serviço e vínculo com o orçamento de origem.
Tudo o resto (estoque, NFS-e, lembretes, inspeção, histórico global/compartilhado do veículo, RLS) é **pós-core**, entra uma feature por vez. Não implemente módulo pós-core sem pedido explícito.
Planos: Solo R$ 49, Oficina R$ 79, Pro R$ 149 (limites em `config/plans.php`, nunca hardcode).
A marca vende sozinha (autoatendimento): todo fluxo precisa funcionar sem call de onboarding.

## 2. Stack
| Camada | Tecnologia |
|---|---|
| API / backend | Laravel 12 (PHP 8.3+), monolito modular |
| Banco | PostgreSQL 16+ (jsonb, pg_trgm; RLS só no pós-core, opcional) |
| Filas/cache | Redis + Laravel Horizon |
| Frontend | React 18 + TypeScript + Vite, PWA, TanStack Query, React Hook Form + Zod, MUI (tema nos tokens da marca; ver `docs/adr/0002-frontend-com-mui.md`) |
| Worker WhatsApp | Node 20+ + TypeScript, consome fila Redis, fala com WhatsApp Cloud API (oficial) |
| Storage | Cloudflare R2 (S3 compatível), bucket privado; navegador comprime (~300 KB) e sobe direto por URL pré-assinada |
| Integrações | Core: Asaas (assinatura do SaaS), WhatsApp Cloud API. Pós-core: Focus NFe (NFS-e) |
| Observabilidade | Sentry (back, front, worker), logs JSON |
| Infra | VPS Hetzner + Laravel Forge; Cloudflare DNS/CDN |
| Testes | Pest (PHP), Vitest + Testing Library (front), Playwright (E2E críticos), Vitest (worker) |

## 3. Estrutura de pastas
```
backend/
  app/
    Modules/
      # core
      Tenancy/ Auth/ Customers/ Vehicles/ ServiceTypes/ Quotes/ ServiceOrders/ Media/ Messaging/ Billing/
      # logo após o core
      Payments/ Audit/
      # pós-core
      Inspections/ Inventory/ Invoicing/ Reminders/
        Actions/  Data/  Enums/  Events/  Listeners/  Jobs/  Models/  Policies/
        Http/{Controllers,Requests,Resources}/  routes.php
  # README.md e CLAUDE.md de cada módulo ficam em modules/<Modulo>/ na raiz do repo
    Shared/ (Traits/BelongsToTenant, ValueObjects/{Money,Plate,Vin}, Support/JsonSchemaValidator)
  database/migrations/  tests/{Feature,Unit,Arch}/
frontend/src/
  features/<feature>/{api.ts,hooks/,components/,pages/,types.ts}
  shared/{ui,lib,hooks}/   app/{router,providers}/
worker-whatsapp/src/{consumer,meta-client,webhook,api-callback}.ts
docs/ (README.md, architecture.md, adr/NNNN-titulo.md)
```

## 4. Padrões de código
### Laravel
- Controller fino: `FormRequest` valida → chama **uma Action** → retorna `JsonResource`. Zero regra de negócio em controller/model.
- **Action** = um caso de uso, classe `final`, método `handle()`, nome verbo+substantivo: `OpenServiceOrder`, `ApproveQuote`.
- DTOs em `Data/` (readonly). Enums PHP backed para todo status.
- Comunicação entre módulos: só via **Action pública** documentada no README do módulo ou **evento de domínio**. Nunca importar Model de outro módulo para escrever nele.
- Efeitos colaterais (WhatsApp, PDF, NFS-e, histórico) sempre por **evento → listener → job**. Jobs idempotentes (`ShouldBeUnique` ou chave de idempotência), `tries`, `backoff`.
- Dinheiro em **centavos (integer)** com VO `Money`; nunca float.
- Datas em UTC no banco, exibidas em America/Sao_Paulo.
- `declare(strict_types=1);`, PHPStan nível 8 (Larastan), Pint.
### React
- Uma pasta por feature; chamadas HTTP só em `features/*/api.ts`; estado de servidor só com TanStack Query.
- Formulários: React Hook Form + Zod; tipos gerados/espelhados dos Resources.
- Mobile-first (técnico usa celular), funciona com conexão ruim: otimista + retry.
- Formulário de item gerado a partir do `fields_schema` do `service_type` (componente `DynamicFields`).
- Upload de foto: `shared/upload` comprime no navegador (~1600 px, ~300 KB, JPEG/WebP), pede URL pré-assinada, sobe direto pro R2 em segundo plano com fila local e retry. **Nunca** bloquear o salvamento da OS/orçamento esperando foto.
### Node (worker)
- TypeScript strict, sem lógica de negócio: recebe `{tenant_id, message_id, template, to, params}`, envia, devolve status via callback assinado para a API.
- Rate limit por número, retry com backoff, nunca loga telefone completo.

## 5. Convenções de nomes
- Tabelas snake_case plural (`service_orders`); FKs `<singular>_id`; pivots em ordem alfabética.
- PKs `uuid` (ordered, `HasUuids`); número humano da OS em `number` sequencial **por tenant**.
- Eventos no passado: `QuoteApproved`, `ServiceOrderClosed`. Jobs: `SendQuoteLinkJob`. Policies: `ServiceOrderPolicy`.
- Rotas REST em inglês, kebab-case: `/api/service-orders/{id}/quotes`.
- Branches: `feat/<modulo>-<desc>`, `fix/...`, `chore/...`.

## 6. Multi-tenant (OBRIGATÓRIO)
Decisão: **banco único**, `tenant_id` em cada linha + **global scope do Laravel**. Não usar banco/schema por empresa (centenas de bancos pra migrar, monitorar e fazer backup; custo cresce por cliente). RLS do Postgres é etapa **pós-core opcional**.
1. Toda tabela de dado de oficina tem `tenant_id uuid not null` + FK + índice composto começando por `tenant_id`.
2. Todo Model de tenant usa `use BelongsToTenant;` (global scope + preenche `tenant_id` no `creating`).
3. Middleware `ResolveTenant` define o tenant atual a partir do usuário logado (`TenantContext`).
4. Jobs carregam `tenant_id` e restauram o contexto (`TenantContext::run($tenantId, fn() => ...)`).
5. Links públicos (aprovação) resolvem o tenant pelo token, nunca por parâmetro da URL.
6. Exceções sem tenant_id: `tenants`, tabelas de sistema e `service_types` com `tenant_id = null` (tipos padrão, somente leitura pras oficinas). No pós-core, `vehicles` global. `users` tem `tenant_id` mas sem global scope (login antes do tenant; ver `docs/adr/0001-users-sem-global-scope-de-tenant.md`). Nova exceção exige ADR.
7. `withoutGlobalScope(TenantScope::class)` é proibido fora de `Modules/Tenancy` e comandos admin revisados.
8. Cada módulo tem `TenantIsolationTest`: tenant A nunca lê/escreve dado do tenant B.
9. (Pós-core, opcional) RLS: `SET app.tenant_id` na conexão + policy `tenant_id = current_setting('app.tenant_id')::uuid`.

## 7. Regras de negócio do domínio
### Papéis e técnicos
- Papéis: `owner`, `admin`, `technician`, `front`. **Técnico** cria orçamento, envia pro cliente, executa OS e marca itens como feitos; não acessa configurações nem financeiro.
- Todo item de orçamento e de OS tem `technician_id` (responsável). Na conversão, o técnico do item do orçamento é copiado.
### Tipos de serviço (campos específicos)
- `service_types`: `name`, `fields_schema` (jsonb), `default_price`, `tenant_id` (null = padrão do sistema).
- `fields_schema` = lista de campos `{key, label, type: text|number|select|boolean|date, required, options?, unit?}`. Ex.: troca de óleo → `oil_brand`, `viscosity` (select), `liters` (number), `filter_changed` (boolean), `next_change_km` (number); troca de junta → `gasket` (select), `side` (select), `resurfacing` (boolean).
- Valores preenchidos ficam em `fields` (jsonb) no `quote_item`/`service_order_item`, validados no backend pelo `JsonSchemaValidator`; o item guarda `fields_schema_snapshot` pra mudança futura no tipo não quebrar item antigo.
### Fotos (Media)
- Fluxo: front comprime → `RequestUploadUrl` cria `media` (`pending`) e devolve URL pré-assinada (expira em minutos) → navegador faz PUT direto no R2 → `ConfirmUpload` marca `uploaded` → job gera miniatura. Upload nunca passa pelo Laravel.
- Só imagens (jpeg/webp/png) e PDF, máx. 2 MB após compressão. `media` `pending` há mais de 24 h é limpa por job.
### Veículos (core)
- No core, `vehicles` é por tenant (`tenant_id`, `customer_id`, `plate_normalized` único por tenant, `vin` opcional). Histórico do carro = lista das OS dele.
- **Pós-core:** registro global por placa/chassi (`vehicles` global + `tenant_vehicles` + `vehicle_ownerships` + `vehicle_history` append-only); sobrevive à troca de dono; outra oficina só vê resumo com autorização do dono.
### Inspeção inicial (pós-core)
- Opcional por OS: km, combustível, panorama geral, itens por área com `ok|attention|critical`, fotos (mesmo upload direto), PDF e assinatura; imutável após assinada; itens de atenção viram sugestão de orçamento.
### Ordem de serviço (centro dos dados, core)
Origem: `from_quote` (gerada por `ConvertApprovedQuoteToServiceOrder`, guarda `quote_id`) ou `direct` (`OpenServiceOrder`, sem orçamento).
Guarda: cliente, veículo, km, itens (serviço/produto) com qty, preço em centavos, `technician_id`, `service_type_id`, `fields`, total, quem abriu.
Máquina de estados:
`open → in_progress → ready → delivered → closed`; `canceled` a partir de qualquer estado antes de `delivered`. A aprovação vive no orçamento; OS gerada de orçamento já nasce `open` com os itens aprovados.
- Transições só via Action (`TransitionServiceOrder`) com checagem de política; registrar em audit e history.
- Fechar OS exige: todos os itens com preço e técnico, itens marcados como feitos e km de saída. (Pagamento entra quando o módulo Payments existir.)
### Orçamento / aprovação (core)
- `quotes` versionado (`version`); editar orçamento enviado cria nova versão e invalida o token anterior.
- Estados: `draft → sent → viewed → approved | rejected | expired`.
- Link público: token aleatório ≥ 32 bytes, expira (padrão 7 dias), rate limit.
- Aprovação exige **assinatura** (desenho → PNG no R2) + nome do assinante; grava em `quote_approvals`: itens aprovados (parcial permitido), `signature_key`, IP, user agent, `approved_at`.
- `QuoteApproved` → listener chama `ConvertApprovedQuoteToServiceOrder` (idempotente: um orçamento gera no máximo uma OS).
### Estoque (pós-core)
- Saldo = soma de `stock_movements` (nunca coluna editável solta). Tipos: `purchase|sale|adjustment|return`. Baixa automática quando OS vai para `in_progress` (configurável) ou `delivered`. Alerta quando abaixo de `min_qty`.
### Pagamentos (logo após o core)
- Assinatura do SaaS (Billing) é **core**. Pagamento da oficina (cliente final): `pending|paid|canceled|refunded`, métodos `cash|pix|card|boleto`. Pix/boleto via Asaas opcional.
- Assinatura do SaaS (módulo **Billing**): Asaas subscription; trial 14 dias; status do tenant `trialing|active|past_due|suspended|canceled`. `past_due` > 7 dias → somente leitura. Nunca apagar dados de tenant suspenso.
- Webhooks Asaas validados pelo token de acesso configurado; processamento idempotente pelo id do evento.
### NFS-e (Focus NFe) (pós-core)
- Emitida por job na fila `fiscal`. Estados: `pending|processing|authorized|error|canceled`. Guardar referência, número, XML e PDF no R2. Nunca reenviar sem checar status na API (idempotência por `ref`).
### Lembretes (pós-core)
- Tipos: `oil_change|revision|inspection_item|custom`, por data e/ou km. Scheduler diário cria jobs; só envia se o cliente tem `consent_whatsapp_at`. Status `scheduled|sent|failed|canceled`.
### WhatsApp
- **Somente API oficial (Cloud API)**. Proibido bibliotecas não oficiais (Baileys, Z-API etc.).
- No core, só envio do link de orçamento e aviso de OS pronta. Mensagens proativas só por **template aprovado**; lembretes e links = categoria utility. Marketing exige opt-in explícito.
- Toda mensagem gera `messages` com status (`queued|sent|delivered|read|failed`) atualizado por webhook. Fallback: botão `wa.me` com texto pronto.

## 8. Testes obrigatórios
- Toda Action: feature test (caminho feliz + regra violada + permissão).
- Todo módulo: `TenantIsolationTest`.
- E2E (Playwright): orçamento → envio → aprovação com assinatura → OS criada; OS direta; foto subindo em segundo plano.
- `fields` validado contra `fields_schema` (obrigatório faltando, tipo errado, opção inválida).
- Máquinas de estado: teste de toda transição válida e inválida.
- Integrações: `Http::fake()`/mocks; nunca chamar API real em teste.
- Testes de arquitetura (Pest arch): controllers não usam Models de outros módulos; nada usa `float` para dinheiro; Models de tenant usam `BelongsToTenant`.
- Cobertura mínima nas pastas `Actions/` e `Enums/`: 90%. CI falha se cair.

## 9. Migrations
- Uma migration por mudança, nunca editar migration já em produção.
- Sempre: `uuid` PK, `tenant_id` + índice (se tabela de tenant; RLS só se a etapa pós-core for aprovada), `timestamps`, `softDeletes` quando aplicável.
- Soft delete: customers, vehicles, service_types, service_orders (pós-core: parts, inspections, tenant_vehicles).
- **Proibido** em quote_approvals, payments, invoices, vehicle_history, audit_logs, messages.
- Mudanças destrutivas em 2 deploys (expand → migrate data → contract).

## 10. Segurança e LGPD
- A oficina é controladora; nós somos operadores. Dados pessoais: nome, telefone, e-mail, CPF/CNPJ, placa.
- CPF/telefone com cast `encrypted` + coluna hash para busca. Nunca logar dado pessoal; Sentry com scrubbing.
- Consentimento de WhatsApp registrado com data e origem. Action `ExportCustomerData` e `AnonymizeCustomer` obrigatórias.
- Auth: Sanctum (cookie SPA), 2FA para owner, Policies em todo endpoint. Rate limit em login e links públicos.
- Uploads só via URL pré-assinada do R2 (direto do navegador), bucket privado, valida mime/tamanho; leitura por URL assinada de curta duração.
- Segredos só em `.env`/Forge; nunca no repositório. Webhooks sempre validados.

## 11. Git, commits e PR
- Conventional Commits em português: `feat(quotes): aprovação parcial por item`.
- PR pequeno (< 400 linhas úteis), descrição: o quê, por quê, como testar, migrations, impacto em tenant/LGPD.
- CI verde obrigatório: Pint, PHPStan, Pest, ESLint, tsc, Vitest. main protegida; merge → staging; tag `vX.Y.Z` → produção.

## 12. Definition of Done
- [ ] Regra de negócio em Action, com testes (inclui isolamento de tenant)
- [ ] Migration com tenant_id e índices
- [ ] Policy/autorização aplicada
- [ ] Eventos/jobs idempotentes
- [ ] UI mobile testada, textos em PT-BR
- [ ] README/CLAUDE.md do módulo atualizado (Actions, eventos, estados)
- [ ] Sem dado pessoal em log; Sentry sem erro novo em staging
- [ ] CI verde e revisado

## 13. NUNCA faça
- Implementar módulo pós-core sem pedido explícito.
- Upload de arquivo passando pelo Laravel ou salvamento esperando foto.
- Item de OS sem técnico responsável.
- Query sem escopo de tenant ou `withoutGlobalScope` fora do lugar permitido.
- Float para dinheiro. Status como string solta (use Enum).
- Lógica de negócio em controller, model, componente React ou no worker Node.
- Chamar integração externa de forma síncrona na request (use fila).
- API não oficial de WhatsApp, mensagem de marketing sem opt-in.
- Editar/deletar `quote_approvals`, `vehicle_history`, `audit_logs`, `payments` ou `invoices` emitidas.
- Editar migration já aplicada em produção. Commitar `.env`/segredos.
- Expor id interno ou dado de outro tenant em link público.
- Inventar regra de negócio não documentada: pergunte ou crie ADR.

## 14. Como atualizar docs
- Mudou comportamento de um módulo → atualize `modules/<Modulo>/README.md` e `CLAUDE.md` no mesmo PR.
- Decisão de arquitetura → `docs/adr/NNNN-titulo.md` (contexto, decisão, consequências).
- Novo módulo → copie `modules/_TEMPLATE/` e registre na tabela de módulos abaixo e no `docs/architecture.md`.

## 15. Módulos
| Módulo | Fase | Responsabilidade |
|---|---|---|
| Tenancy | Core | Tenants, contexto, global scope (RLS pós-core) |
| Auth | Core | Usuários, papéis owner/admin/technician/front, 2FA |
| Customers | Core | Clientes da oficina, consentimento, LGPD |
| Vehicles | Core (global no pós-core) | Veículos por oficina; depois registro global + histórico |
| ServiceTypes | Core | Tipos de serviço com schema de campos JSON |
| Quotes | Core | Orçamento versionado, link, aprovação com assinatura, conversão em OS |
| ServiceOrders | Core | OS (centro dos dados), itens com técnico, estados |
| Media | Core | Upload direto pro R2 por URL pré-assinada |
| Messaging | Core | WhatsApp Cloud API (link do orçamento, OS pronta) |
| Billing | Core | Assinatura do SaaS via Asaas, planos, trial |
| Payments | Logo após | Recebimentos da oficina |
| Audit | Logo após | Log de auditoria |
| Inspections | Pós-core | Relatório de entrada |
| Inventory | Pós-core | Peças e estoque |
| Invoicing | Pós-core | NFS-e via Focus NFe |
| Reminders | Pós-core | Lembretes por data/km |

# Arquitetura

Core primeiro: orçamento → aprovação com assinatura → OS automática, e OS direta. A OS é o centro dos dados.
Multi-tenant: banco único + `tenant_id` + global scope do Laravel (RLS do Postgres é pós-core opcional).
Não usamos banco por empresa: seriam centenas de bancos pra migrar, monitorar e fazer backup.

## Visão geral
```mermaid
flowchart LR
  PWA[React PWA] -->|HTTPS| API[Laravel API]
  PWA -->|upload direto, URL pré-assinada| R2[(Cloudflare R2)]
  PUB[Link público: aprovação + assinatura] --> API
  API --> PG[(PostgreSQL, tenant_id + global scope)]
  API -->|jobs| RD[(Redis)]
  RD --> HZ[Horizon: PDF, miniaturas]
  RD --> WK[Worker Node WhatsApp]
  WK --> META[WhatsApp Cloud API]
  META -. webhook .-> WK
  API --> ASAAS[Asaas - assinatura do SaaS]
  ASAAS -. webhook .-> API
  HZ -.pós-core.-> FOCUS[Focus NFe]
```

## Fluxo do core
```mermaid
flowchart LR
  Q[Orçamento - técnico cria] --> S[Enviado por WhatsApp]
  S --> A{Cliente aprova + assina?}
  A -->|sim| OS[OS gerada automaticamente]
  A -->|não| R[Recusado]
  D[OS direta] --> OS
```

## Upload de fotos
```mermaid
sequenceDiagram
  participant F as Navegador
  participant A as Laravel API
  participant R as Cloudflare R2
  F->>F: comprime (~1600px, ~300 KB)
  F->>A: salva OS/orçamento (não espera foto)
  F->>A: RequestUploadUrl
  A-->>F: URL pré-assinada + media(pending)
  F->>R: PUT da foto (segundo plano, retry)
  F->>A: ConfirmUpload
  A->>A: media = uploaded, job miniatura
```

## Modelo de dados (core)
```mermaid
erDiagram
  TENANTS ||--o{ USERS : has
  TENANTS ||--o{ CUSTOMERS : has
  TENANTS ||--o{ SERVICE_TYPES : custom
  CUSTOMERS ||--o{ VEHICLES : owns
  VEHICLES ||--o{ QUOTES : has
  QUOTES ||--o{ QUOTE_ITEMS : has
  QUOTES ||--o| QUOTE_APPROVALS : signed
  QUOTES ||--o| SERVICE_ORDERS : generates
  VEHICLES ||--o{ SERVICE_ORDERS : has
  SERVICE_ORDERS ||--o{ SERVICE_ORDER_ITEMS : has
  SERVICE_TYPES ||--o{ QUOTE_ITEMS : typed
  SERVICE_TYPES ||--o{ SERVICE_ORDER_ITEMS : typed
  USERS ||--o{ SERVICE_ORDER_ITEMS : technician
  USERS ||--o{ QUOTE_ITEMS : technician
  SERVICE_ORDERS ||--o{ MEDIA : photos
```
Pós-core: inspections/inspection_items, parts/stock_movements, invoices, reminders, payments, audit_logs, vehicles global + tenant_vehicles + vehicle_ownerships + vehicle_history.

Hospedagem: Hetzner CX33 + Laravel Forge + Postgres no mesmo VPS + Cloudflare R2 + Resend + Sentry. Ver Page "Arquitetura técnica: SaaS pra oficinas".

## Erros (Sentry)
Os três apps mandam erro para o Sentry só quando o DSN está configurado (`SENTRY_LARAVEL_DSN`, `VITE_SENTRY_DSN`, `SENTRY_DSN` do worker). Em dev fica vazio e nada sai da máquina.
Regra de LGPD (CLAUDE.md §10): **nenhum dado pessoal vai para o Sentry**.
- **Coleta automática desligada:** backend com `send_default_pii` falso e sem corpo da requisição; front e worker com `dataCollection` todo desligado. Sem replay nem tracing no front.
- **Filtro antes do envio:** backend em `App\Shared\Support\SentryScrubber`, front em `src/shared/lib/sentry.ts`. Os dois filtros:
  - deixam só o método e o caminho da requisição, sem query string (a busca de cliente leva telefone e CPF);
  - trocam o token dos links públicos (`/o/`, `/convite/`, `/public/quotes|invitations/`) por `[token]`;
  - deixam do usuário só o id.
- **Erro de banco:** no backend, a mensagem perde o SQL com os valores e a linha `DETAIL` do Postgres.
- **No front:** breadcrumbs de `console` são descartados.
- **Contexto:** o tenant vai como tag `tenant_id`, definida no `TenantContext`, então vale para requests e jobs.
- **No worker:** só os logs de nível `error` viram evento, e eles já saem com o telefone mascarado.

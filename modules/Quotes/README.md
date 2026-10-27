# Quotes (README pra humanos)

> Leia antes o `CLAUDE.md` da raiz. Regras de multi-tenant, testes e DoD de lá valem aqui.
> **Fase: Core.**

## Propósito
Orçamento versionado, envio, aprovação com assinatura e conversão automática em OS.

## Tabelas
quotes, quote_items (technician_id, service_type_id, fields jsonb), quote_approvals

## API pública (outros módulos só usam isto)
- Actions: CreateQuote, ListQuotes, AddQuoteItem, RemoveQuoteItem, SendQuote, OpenPublicQuote, RequestSignatureUpload, ApproveQuote, RejectQuote (interna: PrepareQuoteForEdit)
- Eventos emitidos: QuoteSent, QuoteApproved, QuoteRejected
- Eventos ouvidos: –

## Estados
draft|sent|viewed|approved|rejected|expired

## Regras de negócio
Token ≥32 bytes, expira em 7 dias; nova versão invalida token; aprovação exige assinatura (PNG no R2) + nome, registra IP/UA/itens aprovados (parcial permitido). QuoteApproved → ConvertApprovedQuoteToServiceOrder (idempotente, máx. 1 OS por orçamento). quote_approvals sem soft delete.

## Estrutura
`backend/app/Modules/Quotes/` → Actions/ Data/ Enums/ Events/ Listeners/ Jobs/ Models/ Policies/ Http/ routes.php
`frontend/src/features/quotes/` (se tiver UI)

## Testes obrigatórios
- Feature test por Action (feliz, regra violada, sem permissão)
- `TenantIsolationTest` (exceto tabelas globais documentadas)
- Toda transição de estado válida/inválida

## Integrações
Messaging (link), Media (assinatura)

## Changelog do módulo
- 2026-10-27: criado/atualizado com decisões de core primeiro, técnicos, service_types e upload direto.
- 2026-10-27: implementado. Versão na mesma linha: editar enviado/visto incrementa `version`, volta a `draft` e anula o token. Token = 32 bytes hex, só o hash (sha256) no banco, 7 dias. Link público identifica itens por `position` (sem ids internos). Assinatura: `RequestSignatureUpload` → PUT direto no R2 → `ApproveQuote` confirma o PNG mais recente enviado após `sent_at`. `QuoteApproved` leva `ApprovedQuoteData` só com os itens aprovados. Reenviar (inclusive expirado) gera token novo.
- 2026-10-27: revisão de segurança: edição/envio relêem o orçamento com `lockForUpdate` (não desfazem aprovação concorrente); aprovação recusa assinatura de envio anterior; link morre após `token_expires_at` em qualquer status; URL de assinatura limitada a 10/h por link.

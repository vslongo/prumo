# Invoicing (README pra humanos)

> Leia antes o `CLAUDE.md` da raiz. Regras de multi-tenant, testes e DoD de lá valem aqui.
> **Fase: logo após o core.** Decisões em `docs/adr/0010-nfse-nacional-direto-na-sefin.md`.

## Propósito
NFS-e emitida direto na API Nacional (Sefin Nacional/ADN), com o certificado A1 da própria oficina. Sem intermediário (Focus NFe etc.).

## Tabelas
- fiscal_profiles (1 por tenant): CNPJ, inscrição municipal, código IBGE do município, regime (`mei|simples_me_epp`), código de tributação nacional padrão, alíquota ISS em pontos-base, série da DPS, `include_products`, ambiente (`restricted|production`), certificado (media + senha `encrypted` + validade).
- invoices: uma por emissão da OS; série/número da DPS, chave de acesso, número da NFS-e, valor, XML/PDF no R2, erro, datas. Sem soft delete; nunca apagar nota emitida.

## API pública (outros módulos só usam isto)
- Actions: UpdateFiscalProfile, RequestCertificateUpload, ConfirmFiscalCertificate, RemoveFiscalCertificate, IssueServiceInvoice; internas: TransmitDps (job `TransmitDpsJob`, fila `fiscal`), GenerateDanfse (listener de InvoiceAuthorized; PDF no R2)
- Eventos emitidos: InvoiceAuthorized, InvoiceFailed
- Eventos ouvidos: nenhum (emissão por botão na OS)

## Estados
`pending → processing → authorized → cancel_requested → canceled`; `processing → error → processing` (nova tentativa com o mesmo número de DPS); `cancel_requested → authorized` se a Sefin recusar o cancelamento. Transição só por `Invoice::transitionTo()`.

## Regras de negócio
- Emite só para OS `delivered|closed`. No máximo uma nota não cancelada por OS (índice parcial). Emitem dono, admin e balcão (Gate `issue-invoice`); técnico só vê.
- Endpoints: `POST/GET /api/service-orders/{id}/invoices`, `GET /api/invoices/{id}`, `GET /api/invoices/{id}/files/{xml|pdf}` (URL assinada de 5 min).
- Leiaute da DPS isolado em `Support/DpsXmlBuilder`; montado sem os XSD oficiais: conferir com `docs/nfse/` e na homologação (pAliq/totTrib do Simples, cNBS, IBSCBS).
- Entram todos os itens da OS; com `include_products` desligado, só os de serviço. Sem item faturável → recusa.
- Tomador precisa de CPF/CNPJ válido.
- Só Simples ME/EPP e MEI, ISS não retido.
- Número da DPS reservado (lock por tenant) antes do envio e reaproveitado; antes de reenviar, consulta a DPS na Sefin.
- Certificado: CNPJ tem que bater com o perfil e a validade tem que ser futura. Só owner/admin. Aberto pelo binário `openssl` com tempo máximo (aceita A1 legado com `-legacy`); guarda o sha256 dos bytes validados, que a emissão confere. Pode ser removido (arquivo e senha apagados). Trocar o CNPJ descarta o certificado.
- XML, documento do tomador, certificado e senha nunca vão para log, Sentry ou resposta da API.

## Estrutura
`backend/app/Modules/Invoicing/` → Actions/ Data/ Enums/ Events/ Listeners/ Jobs/ Models/ Policies/ Support/ Http/ routes.php
`frontend/src/section/invoicing/`

## Testes obrigatórios
- Feature test por Action (feliz, regra violada, sem permissão)
- `TenantIsolationTest`
- Toda transição de estado válida/inválida
- `Http::fake()` para a Sefin; certificado gerado no próprio teste

## Integrações
Sefin Nacional (emissão, consulta, eventos), fila `fiscal`.

## Changelog do módulo
- 2026-10-27: criado/atualizado com decisões de core primeiro, técnicos, service_types e upload direto.
- 2026-11-23: troca Focus NFe pela API Nacional direta (ADR 0010); define perfil fiscal, certificado A1, itens, regimes e estados.
- 2026-11-24: emissão da NFS-e (DPS assinada, mTLS, idempotência pela consulta da DPS), estados com cancelamento em duas etapas.

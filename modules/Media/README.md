# Media (README pra humanos)

> Leia antes o `CLAUDE.md` da raiz. Regras de multi-tenant, testes e DoD de lá valem aqui.
> **Fase: Core.**

## Propósito
Upload de fotos/arquivos direto do navegador pro R2.

## Tabelas
media (owner_type, owner_id, r2_key, status pending|uploaded|failed, size, mime)

## API pública (outros módulos só usam isto)
- Actions: RequestUploadUrl, ConfirmUpload, GetSignedReadUrl
- Eventos emitidos: MediaUploaded
- Eventos ouvidos: –

## Estados
pending|uploaded|failed

## Regras de negócio
Navegador comprime (~1600 px, ~300 KB); URL pré-assinada curta; upload nunca passa pelo Laravel; salvamento de OS/orçamento não espera foto; máx. 2 MB; pending >24 h limpo por job; job gera miniatura.

## Estrutura
`backend/app/Modules/Media/` → Actions/ Data/ Enums/ Events/ Listeners/ Jobs/ Models/ Policies/ Http/ routes.php
`frontend/src/features/media/` (se tiver UI)

## Testes obrigatórios
- Feature test por Action (feliz, regra violada, sem permissão)
- `TenantIsolationTest` (exceto tabelas globais documentadas)
- Toda transição de estado válida/inválida

## Integrações
Cloudflare R2

## Changelog do módulo
- 2026-10-27: criado/atualizado com decisões de core primeiro, técnicos, service_types e upload direto.

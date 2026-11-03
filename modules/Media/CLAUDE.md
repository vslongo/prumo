# Módulo Media

> Leia antes o `CLAUDE.md` da raiz. Regras de multi-tenant, testes e DoD de lá valem aqui.
> **Fase: Core.**

## Propósito
Upload de fotos/arquivos direto do navegador pro R2.

## Tabelas
media (owner_type, owner_id, r2_key, thumb_key, status pending|uploaded|failed, size, mime). owner_type em uso: quote_signature, quote_item, service_order_item

## API pública (outros módulos só usam isto)
- Actions: RequestUploadUrl (PHOTO_MIMES para fotos de item), ConfirmUpload, FindLatestOwnerMedia, FindOwnerMedia, ListOwnerPhotos (devolve PhotoData com URLs assinadas), GetSignedReadUrl (30 min), DeleteMedia. Internas: GenerateThumbnail, PurgeStalePendingMedia
- Endpoints: o Media não tem rotas; quem é dono da foto (Quotes, ServiceOrders) expõe as rotas e decide quando pode
- Eventos emitidos: MediaUploaded
- Eventos ouvidos: MediaUploaded → GenerateThumbnailOnUpload (fila)
- Agendado: PurgeStalePendingMediaJob diário 04:00 (routes/console.php), tenant a tenant via Tenancy ForEachTenant

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
- 2026-10-27: mínimo implementado para a assinatura do orçamento. Disco `r2` (S3, bucket privado). Chave `<owner_type>/<uuid7>.<ext>` sem tenant_id (a URL vai para o cliente final). ConfirmUpload checa existência e tamanho no R2.
- 2026-10-27: ConfirmUpload confere os bytes iniciais (PNG/JPEG/WebP/PDF) além do tamanho; arquivo inválido é apagado e vira `failed`.
- 2026-11-03: fotos por item. Miniatura WebP de 320 px (GD) gerada em fila após o upload; leitura por URL assinada de 30 min; remoção apaga arquivo + miniatura; pending > 24 h limpo todo dia. Front: `shared/upload` comprime (≤1600 px, ~300 KB, WebP/JPEG) e sobe em fila local com retry (em memória; aviso ao sair da página com envio pendente).
- 2026-11-03: foto já no rascunho (novo orçamento / nova OS): fica no navegador e entra na fila logo depois de salvar, ligada ao item pela posição. Salvar não espera a foto.

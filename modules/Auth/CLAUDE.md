# Módulo Auth

> Leia antes o `CLAUDE.md` da raiz. Regras de multi-tenant, testes e DoD de lá valem aqui.
> **Fase: Core.**

## Propósito
Usuários, papéis e 2FA.

## Tabelas
users

## API pública (outros módulos só usam isto)
- Actions: pendentes (InviteUser, ChangeUserRole, DeactivateUser). Rotas: POST /api/login, POST /api/logout, GET /api/me
- Eventos emitidos: UserInvited
- Eventos ouvidos: TenantCreated

## Estados
–

## Regras de negócio
Papéis owner/admin/technician/front. Técnico cria e envia orçamento, executa OS e marca itens feitos; sem acesso a configurações e financeiro. Owner exige 2FA.

## Estrutura
`backend/app/Modules/Auth/` → Actions/ Data/ Enums/ Events/ Listeners/ Jobs/ Models/ Policies/ Http/ routes.php
`frontend/src/features/auth/` (se tiver UI)

## Testes obrigatórios
- Feature test por Action (feliz, regra violada, sem permissão)
- `TenantIsolationTest` (exceto tabelas globais documentadas)
- Toda transição de estado válida/inválida

## Integrações
nenhuma

## Changelog do módulo
- 2026-10-27: criado/atualizado com decisões de core primeiro, técnicos, service_types e upload direto.
- 2026-10-27: login SPA (Sanctum cookie) com rate limit; usuário inativo não entra. `User` sem global scope de tenant (ADR 0001). 2FA do owner pendente.
- 2026-10-27: ListTenantUsers + GET /api/users (filtro de tenant explícito, ADR 0001); /api/me traz o nome da oficina.

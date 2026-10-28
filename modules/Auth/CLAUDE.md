# Módulo Auth

> Leia antes o `CLAUDE.md` da raiz. Regras de multi-tenant, testes e DoD de lá valem aqui.
> **Fase: Core.**

## Propósito
Usuários, papéis e 2FA.

## Tabelas
users, user_invitations

## API pública (outros módulos só usam isto)
- Actions: SignUp (cria oficina via Tenancy CreateTenant + owner). InviteUser, AcceptInvitation, ListPendingInvitations, RevokeInvitation, ChangeUserRole, DeactivateUser, ReactivateUser. Rotas: GET /api/users (inclui desativados com is_active), PATCH /api/users/{user}/role, POST /api/users/{user}/deactivate|reactivate, GET /api/user-invitations, DELETE /api/user-invitations/{id}, POST /api/signup, POST /api/user-invitations, GET /api/public/invitations/{token}, POST /api/public/invitations/{token}/accept, POST /api/login, POST /api/logout, GET /api/me
- Eventos emitidos: UserInvited
- Eventos ouvidos: –

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
- 2026-10-28: cadastro por autoatendimento (SignUp + POST /api/signup, tela /cadastro): cria tenant em trial e o owner na mesma transação e já loga. Sem cartão. Rate limit 5/h por IP. E-mail normalizado em minúsculas no cadastro e no login.
- 2026-10-28: convite de funcionário por link (owner/admin; papel technician|front|admin, nunca owner). Token de 32 bytes (só o hash no banco), 7 dias, uso único com lock. O convite não guarda e-mail nem telefone: o funcionário escolhe o acesso no aceite (hoje e-mail + senha; login por telefone entra no aceite depois). Tela /convite/:token e card Equipe em Configurações com wa.me sem número.
- 2026-10-28: gestão da equipe (owner/admin): trocar papel, desativar (derruba sessões e tokens) e reativar; ninguém mexe no dono nem em si mesmo; usuário de outra oficina = 404 (UserPolicy). Convites pendentes listados e cancelados (cancelar = expirar agora). /api/users passa a trazer desativados para o nome continuar nas OS; o seletor de técnico mostra só ativos.
- 2026-10-28: só o dono gerencia admins (trocar papel, desativar) e só ele promove ou convida admin; admin gerencia técnico e balcão.

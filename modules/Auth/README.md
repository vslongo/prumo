# Auth (README pra humanos)

> Leia antes o `CLAUDE.md` da raiz. Regras de multi-tenant, testes e DoD de lá valem aqui.
> **Fase: Core.**

## Propósito
Usuários, papéis e 2FA.

## Tabelas
users, user_invitations

## API pública (outros módulos só usam isto)
- Actions: SignUp (cria oficina via Tenancy CreateTenant + owner). InviteUser, AcceptInvitation, ListPendingInvitations, RevokeInvitation, ChangeUserRole, DeactivateUser, ReactivateUser, EnableTwoFactor, ConfirmTwoFactor, DisableTwoFactor, VerifyTwoFactorChallenge, ResolveTwoFactorRequirement. Rotas: GET /api/users (inclui desativados com is_active), PATCH /api/users/{user}/role, POST /api/users/{user}/deactivate|reactivate, GET /api/user-invitations, DELETE /api/user-invitations/{id}, POST /api/signup, POST /api/user-invitations, GET /api/public/invitations/{token}, POST /api/public/invitations/{token}/accept, POST /api/login, POST /api/login/two-factor, POST /api/logout, GET /api/me, POST /api/two-factor, POST /api/two-factor/confirm, DELETE /api/two-factor
- Eventos emitidos: UserInvited
- Eventos ouvidos: –

## Estados
–

## Regras de negócio
Papéis owner/admin/technician/front. Técnico cria e envia orçamento, executa OS e marca itens feitos; sem acesso a configurações e financeiro.
- 2FA (ADR 0006): app autenticador (TOTP) + 8 códigos de recuperação de uso único. Obrigatório só para o dono, depois de 7 dias de conta (`ResolveTwoFactorRequirement::GRACE_DAYS`); passado o prazo, o middleware `EnsureTwoFactorEnabled` (grupo `tenant`) responde 403 `two_factor_required` em tudo menos me, logout e two-factor. Os outros papéis ativam se quiserem. Código TOTP não pode ser reusado (`two_factor_last_step`).
- Recuperar senha (ADR 0007): Actions RequestPasswordReset e ResetPassword; rotas `POST /api/password/forgot` e `POST /api/password/reset` (`throttle:password-reset`). Resposta igual com ou sem cadastro; token de uso único por 60 min; e-mail em fila (`Notifications/ResetPasswordNotification`), sem o e-mail na URL; a troca derruba sessões e tokens e não abre sessão (2FA continua valendo); desativado não recupera.
- Aceite dos termos (ADR 0008): `POST /api/signup` exige `accept_terms`; grava `terms_accepted_at` e `terms_version` (`SignUp::TERMS_VERSION`) no dono. Convidado não aceita termos.

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
- 2026-10-27: login SPA (Sanctum cookie) com rate limit; usuário inativo não entra. `User` sem global scope de tenant (ADR 0001). 2FA do owner pendente (feito em 2026-11-04).
- 2026-10-27: ListTenantUsers + GET /api/users (filtro de tenant explícito, ADR 0001); /api/me traz o nome da oficina.
- 2026-10-28: cadastro por autoatendimento (SignUp + POST /api/signup, tela /cadastro): cria tenant em trial e o owner na mesma transação e já loga. Sem cartão. Rate limit 5/h por IP. E-mail normalizado em minúsculas no cadastro e no login.
- 2026-10-28: convite de funcionário por link (owner/admin; papel technician|front|admin, nunca owner). Token de 32 bytes (só o hash no banco), 7 dias, uso único com lock. O convite não guarda e-mail nem telefone: o funcionário escolhe o acesso no aceite (hoje e-mail + senha; login por telefone entra no aceite depois). Tela /convite/:token e card Equipe em Configurações com wa.me sem número.
- 2026-10-28: gestão da equipe (owner/admin): trocar papel, desativar (derruba sessões e tokens) e reativar; ninguém mexe no dono nem em si mesmo; usuário de outra oficina = 404 (UserPolicy). Convites pendentes listados e cancelados (cancelar = expirar agora). /api/users passa a trazer desativados para o nome continuar nas OS; o seletor de técnico mostra só ativos.
- 2026-10-28: só o dono gerencia admins (trocar papel, desativar) e só ele promove ou convida admin; admin gerencia técnico e balcão.
- 2026-10-28: convite guarda também o token cru criptografado (`token`, cast encrypted, oculto no JSON do model) para o dono copiar o link de novo em "Convites pendentes"; a busca do link segue pelo `token_hash`.
- 2026-10-28: convite de admin só aparece e só é cancelado pelo dono (o link dá o papel a quem abrir; ver o link = poder criar admin).
- 2026-11-04: InviteUser, AcceptInvitation e ReactivateUser respeitam o limite de usuários do plano (Billing EnsureSeatAvailable). Convite pendente ocupa lugar; no trial não há limite.
- 2026-11-04: 2FA. Login em duas etapas (senha → `two_factor: true` → POST /login/two-factor com `code` ou `recovery_code`, usuário pendente na sessão por 5 min, limite de 5 tentativas/min por usuário). Segredo e hashes dos códigos de recuperação com cast `encrypted`. Tela Segurança no app para todos os papéis; dono bloqueado vê só a ativação.
- 2026-11-09: recuperação de senha por e-mail (ADR 0007). `User` passa a usar `Notifiable`.
- 2026-11-09: aceite dos termos de uso e da política de privacidade no cadastro (ADR 0008).

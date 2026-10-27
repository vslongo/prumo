# 0001 — `users` sem global scope de tenant

- Status: aceito
- Data: 2026-10-27

## Contexto
O `CLAUDE.md` (§6) exige `BelongsToTenant` (global scope + preenchimento de `tenant_id`) em todo Model de dado de oficina, e diz que nova exceção exige ADR.

O `TenantScope` falha fechado: sem tenant no contexto, a query não devolve nada. O login acontece antes de existir tenant no contexto (é o próprio usuário que define o tenant, no middleware `ResolveTenant`). Com o scope em `User`, o `Auth::attempt` nunca acharia o usuário.

## Decisão
- `users` tem `tenant_id not null` + FK + índice `(tenant_id, role)`, mas o Model `User` **não** usa `BelongsToTenant`.
- `email` é único globalmente, para o login não precisar saber o tenant.
- Toda consulta de usuários fora do login filtra `tenant_id` explicitamente. As validações que referenciam usuário (ex.: `technician_id`) usam `Rule::exists('users')->where('tenant_id', ...)` (ver `app/Shared/Support/ItemRules.php`).
- O teste de arquitetura (`tests/Arch/ArchTest.php`) ignora `User` e `Tenant` na regra de `BelongsToTenant`.

## Consequências
- Uma pessoa não pode ter o mesmo e-mail em duas oficinas. Aceitável para oficinas de 2–10 pessoas; se virar necessidade, criar `memberships` (usuário × tenant) em novo ADR.
- Listagem/gestão de usuários (InviteUser, ChangeUserRole) precisa filtrar por tenant à mão e ter `TenantIsolationTest` próprio quando for implementada.

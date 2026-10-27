# Backend (Laravel 12)

API do SaaS de oficinas. Regras do projeto: `../CLAUDE.md`.

## Rodando local (Docker)
Tudo roda em container (PHP 8.3, Postgres 16, Redis 7), a partir da raiz do repositório:

```bash
docker compose up -d pgsql redis
docker compose run --rm app composer install
docker compose run --rm app php artisan migrate --seed
docker compose up app            # API em http://localhost:8000
```

No Git Bash do Windows, prefixe com `MSYS_NO_PATHCONV=1`.

## Qualidade
```bash
docker compose run --rm app php artisan test
docker compose run --rm --no-deps app vendor/bin/pint --test
docker compose run --rm --no-deps app vendor/bin/phpstan analyse --memory-limit=1G
```

Em dev, `R2_DRIVER=local` grava as assinaturas em `storage/app/r2` com o mesmo fluxo de URL assinada; o worker `queue` precisa estar de pé para a OS nascer da aprovação. Oficina demo: `php artisan db:seed --class=DemoSeeder`.

Testes usam o banco `oficina_test` (criado por `docker/pgsql/init.sql`) e `Storage::fake('r2')`.

## Rotas do core
| Método | Rota | O quê |
|---|---|---|
| POST | /api/login, /api/logout · GET /api/me | Sessão SPA (Sanctum) |
| GET | /api/users | Usuários ativos da oficina (escolher técnico) |
| GET/POST | /api/customers · GET /api/customers/{id} | Clientes (busca `?q=` por nome, telefone ou CPF) |
| GET/POST | /api/vehicles (`?plate=`) · GET /api/vehicles/{id} | Veículos |
| GET | /api/customers/{id}/vehicles | Veículos do cliente |
| GET/POST/PUT | /api/service-types | Tipos de serviço com `fields_schema` |
| GET/POST | /api/quotes · GET /api/quotes/{id} | Orçamentos |
| POST/DELETE | /api/quotes/{id}/items[/{item}] | Itens do orçamento |
| POST | /api/quotes/{id}/send | Gera link público + fallback wa.me |
| GET | /api/public/quotes/{token} | Cliente vê o orçamento |
| POST | /api/public/quotes/{token}/signature-upload-url | URL pré-assinada do PNG da assinatura |
| POST | /api/public/quotes/{token}/approve · /reject | Cliente aprova (parcial, por `position`) ou recusa |
| GET/POST | /api/service-orders (`?vehicle_id=&status=&quote_id=`) · GET /api/service-orders/{id} | OS (direta ou gerada do orçamento) |
| POST | /api/service-orders/{id}/items · /items/{item}/done | Itens e execução |
| POST | /api/service-orders/{id}/transition · /close | Máquina de estados e fechamento |

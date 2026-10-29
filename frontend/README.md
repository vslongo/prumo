# Frontend Prumo (Next.js + React)

Aplicação da oficina e página pública de aprovação do orçamento, com Next.js 16 App Router, TypeScript, MUI e React Query. Os tokens da marca são importados de `../docs/brand/tokens.css`.

## Rodando

Use Node.js 20.9 ou superior. Na raiz do repositório, inicie a API:

```bash
docker compose up -d app queue
```

Dentro de `frontend`:

```bash
npm install
npm run dev
```

Abra http://localhost:5173. O Next encaminha `/api`, `/sanctum` e `/r2-local` para `http://localhost:8000`, mantendo o acesso do navegador na mesma origem para os cookies do Sanctum. Para mudar o destino, defina `API_URL` em `.env.local` e reinicie o servidor.

Acessos demo após `php artisan db:seed --class=DemoSeeder`: `dono@demo.test` e `tecnico@demo.test`, senha `password`.

## Produção

```bash
npm run build
npm start
```

O servidor Next também usa a porta 5173 em produção. É necessário um runtime Node.js; não é uma exportação estática. Defina `API_URL` antes do build para configurar o proxy de produção. O upload de assinatura continua direto para o R2 por URL pré-assinada; configure o CORS do bucket para aceitar `PUT` da origem da aplicação.

O manifest permite instalar o app. O service worker de produção substitui o antigo worker do Vite, usa rede para navegações e mostra uma página de conexão indisponível quando offline. Não armazena respostas da API nem dados privados. No desenvolvimento, o worker é desregistrado para evitar arquivos antigos em cache.

## Estrutura

- `src/app`: rotas Next, layout raiz, providers MUI/React Query, erros e metadados.
- `src/app/(workshop)`: painel, OS, orçamentos, clientes e configurações, com layout autenticado compartilhado.
- `/login`, `/cadastro`, `/convite/[token]` e `/o/[token]`: rotas independentes do layout da oficina.
- `src/features`: telas, formulários e chamadas à API, preservados na migração.
- `src/shared/lib/navigation.tsx`: links e hooks com as APIs de navegação do Next. Não há React Router no runtime; ele é usado somente como histórico em memória nos testes unitários.

A sessão continua sob responsabilidade do Laravel. O layout da oficina consulta `/api/me` e redireciona visitantes sem sessão para `/login`; a API mantém a autorização dos dados.

## Validação

```bash
npm run typecheck
npm run lint
npm test
npm run e2e
```

Os testes unitários usam Vitest. Vite e seu plugin React ficam somente como infraestrutura desses testes. Os testes E2E usam a API real e **recriam o banco de desenvolvimento** com a oficina demo; execute apenas em ambiente descartável. Primeira execução: `npx playwright install chromium`.

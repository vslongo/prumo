# Frontend Prumo (React + Vite)

PWA da oficina e página pública de aprovação do orçamento. Marca: `../docs/brand/BRAND.md` (tokens importados direto de `../docs/brand/tokens.css`, não copie).

## Rodando
A API precisa estar de pé (na raiz do repositório):

```bash
docker compose up -d app queue
```

```bash
npm install
npm run dev
```

Abre em http://localhost:5173. O Vite faz proxy de `/api`, `/sanctum` e `/r2-local` para a API (mesma origem para o cookie do Sanctum). Acessos da oficina demo (`php artisan db:seed --class=DemoSeeder`): `dono@demo.test` e `tecnico@demo.test`, senha `password`.

## Testes
```bash
npm run typecheck
npm test
npm run e2e
```

- `npm test`: Vitest + Testing Library (formatação, DynamicFields, ItemForm, login, badges).
- `npm run e2e`: Playwright em desktop (1366×860) e celular (Pixel 7) contra a API real. **Recria o banco de dev** com a oficina demo antes de rodar. Cobre login, orçamento → link → cliente aprova parcialmente e assina → OS criada pela fila, OS direta até o fechamento, cancelamento, cadastro de cliente e prints das telas em claro e escuro (`test-results/screens`).
- Primeira vez: `npx playwright install chromium`.

## Estrutura
`src/features/<feature>/{api.ts,components/,pages/}` · `src/shared/{ui,lib,hooks}` · `src/app/{App,router,AppLayout}`. Chamadas HTTP só em `features/*/api.ts` (cliente em `shared/lib/http.ts`); estado de servidor só com TanStack Query; formulários com React Hook Form + Zod.

## Produção
O upload da assinatura vai direto para o R2 por URL pré-assinada: o bucket precisa de CORS liberando `PUT` (header `Content-Type`) a partir da origem do app.

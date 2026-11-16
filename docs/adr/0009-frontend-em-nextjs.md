# 0009 — Frontend em Next.js (App Router) no lugar do Vite

- Status: aceito
- Data: 2026-11-16

## Contexto
O frontend era uma SPA em Vite + react-router, servida como arquivos estáticos (`frontend/dist`) pelo Nginx. O PR #25 migrou o app para o Next.js 16 com App Router, mantendo React 19, MUI, TanStack Query, React Hook Form + Zod e os tokens da marca.

## Decisão
- **Next.js 16 (App Router)** substitui Vite + react-router. Rotas ficam em `frontend/src/app/`: `(workshop)/` tem as telas da oficina com o layout autenticado (`AppLayout`). Login, cadastro, convite, senha, termos e o link público `/o/[token]` ficam fora desse layout.
- **As telas continuam client-side** (`'use client'`). O `page.tsx` de cada rota só reexporta a página do módulo. Os dados continuam vindo da API Laravel pelo TanStack Query. Não há regra de negócio nem chamada à API no servidor Next.
- **Módulos do front em `frontend/src/section/<modulo>/`** (antes `features/`), com a mesma divisão: `api.ts`, `components/`, `pages/`, `hooks/`, `types.ts`. Os endpoints da API ficam centralizados em `src/routes/paths.ts`.
- **Navegação por `shared/lib/navigation`**, que expõe `Link`, `NavLink`, `useNavigate`, `useParams` etc. sobre `next/link` e `next/navigation`. O `LinkBehavior` do MUI (ADR 0002) usa esse adaptador. Nos testes, `src/test/setup.ts` liga esse adaptador ao react-router em memória, que fica só como dependência de dev.
- **Mesma origem para os cookies do Sanctum:** o Next reescreve `/api`, `/sanctum` e `/r2-local` para `API_URL`.
- **Variáveis públicas** do navegador usam o prefixo `NEXT_PUBLIC_` (antes `VITE_`).
- **PWA:** manifest e service worker próprios (`app/ServiceWorker.tsx`, no lugar do `vite-plugin-pwa`). O worker não guarda respostas da API nem dados privados.
- **Vitest + Testing Library** seguem nos testes unitários (com Vite só como infraestrutura deles). Playwright segue nos E2E.

## Consequências
- **Produção precisa de um processo Node** (`npm start`, porta 5173), mantido pelo Forge como daemon. O Nginx deixa de servir `frontend/dist` e passa a fazer proxy de `/` para o Next. O passo a passo está em `docs/deploy.md`.
- O `.env.production` do front precisa trocar `VITE_*` por `NEXT_PUBLIC_*` antes do primeiro deploy. Se não trocar, o Sentry e o link de suporte somem.
- As variáveis `NEXT_PUBLIC_*` e `API_URL` são lidas no `npm run build`. Mudou o valor, precisa gerar o build de novo.
- Mais memória no VPS, por causa do processo Node do front. Na CX33 cabe, mas vale acompanhar.
- Toda página nova precisa do arquivo de rota em `src/app/` além da página no módulo. Esquecer o `'use client'` quebra o build.
- SSR/RSC ficam disponíveis, mas só devem ser usados com novo ADR: hoje a regra é tela client-side falando com a API.

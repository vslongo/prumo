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
- `src/section`: módulos da aplicação. Cada módulo reúne suas páginas, formulários, componentes, tipos, chamadas à API e testes.
- `src/shared`: componentes, hooks e utilitários comuns aos módulos.
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

## Organização dos módulos

```text
src/
  app/                 # Rotas e layouts do Next.js
  section/
    auth/              # Login, cadastro, convites e equipe
    cliente/           # Clientes e vínculo com veículos
    dashboard/         # Painel inicial
    items/             # Formulários e listagem de itens usados em OS e orçamentos
    publicQuote/       # Aprovação pública e assinatura de orçamento
    quotes/            # Criação, detalhes e listagem de orçamentos
    serviceOrders/     # Criação, execução, detalhes e listagem de ordens de serviço
    serviceTypes/      # Configurações, tipos de serviço e campos dinâmicos
    vehicles/          # Cadastro e API de veículos
  shared/              # Recursos reutilizáveis da aplicação
```

Dentro de cada módulo, `pages/` contém as telas e `components/` contém os formulários e outros componentes específicos. `api.ts` concentra o acesso à API; arquivos de tipos e testes ficam junto do código correspondente. As rotas em `app/` apontam para as telas em `section/`.

Por exemplo, em `section/serviceOrders`, `pages/NewServiceOrderPage.tsx` cria a OS, `pages/ServiceOrderDetailPage.tsx` reúne os detalhes e a execução, `pages/ServiceOrdersPage.tsx` apresenta a listagem e `components/ServiceOrderList.tsx` renderiza a lista. Recursos usados por mais de um módulo são importados de seu módulo de origem ou de `shared/`.

## Listagens globais

As páginas principais de clientes, ordens de serviço e orçamentos usam `src/components/ListItems.tsx`. O componente busca uma resposta paginada `{ data, meta: { total, current_page, last_page } }` usando o cliente HTTP autenticado e React Query. Ele apresenta tabela, carregamento, erro com nova tentativa, estado vazio e paginação. Em telas pequenas, a tabela permite rolagem horizontal.

Exemplo em um componente cliente, dentro dos providers da aplicação:

```tsx
import { ListItems } from '../../../components/ListItems'
import type { Customer } from '../api'

<ListItems<Customer>
  url={`/api/customers?q=${encodeURIComponent(search)}`}
  queryKey={['customers', 'search']}
  columns={[
    { id: 'name', header: 'Nome', cell: customer => customer.name },
    { id: 'email', header: 'E-mail', cell: customer => customer.email || '—' },
  ]}
  getRowHref={customer => `/clientes/${customer.id}`}
  label="Clientes"
  emptyState={<p>Nenhum cliente encontrado.</p>}
  errorMessage="Não deu para carregar os clientes."
/>
```

`url` inclui os filtros; `ListItems` controla o parâmetro `page`, iniciando em 1 e reiniciando quando a URL muda. `queryKey` deve usar o prefixo do recurso já invalidado pelas mutações (`customers`, `service-orders/list` ou `quotes/list`). As colunas podem retornar texto, valores formatados e componentes, como badges. A primeira coluna oferece o link para os detalhes. Funções de renderização e links devem ser definidos no lado cliente.

As páginas continuam responsáveis pelo cabeçalho, busca e filtros. Listas internas do painel e dos detalhes mantêm seus componentes específicos. Novas páginas de listagem devem utilizar `ListItems`.

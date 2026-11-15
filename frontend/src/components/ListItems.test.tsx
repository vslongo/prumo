import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { act, render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router'
import { ListItems, type ListItemsProps } from './ListItems'

type Item = { id: string; name: string; total: number }
const alice: Item = { id: '1', name: 'Alice', total: 1250 }
const bob: Item = { id: '2', name: 'Bruno', total: 2500 }
const props: ListItemsProps<Item> = {
  url: '/api/customers?q=ana&status=open',
  queryKey: ['customers', 'search'],
  columns: [
    { id: 'name', header: 'Nome', cell: item => item.name },
    { id: 'total', header: 'Total', align: 'right', cell: item => `R$ ${(item.total / 100).toFixed(2)}` },
  ],
  getRowHref: item => `/clientes/${item.id}`,
  emptyState: <p>Nenhum cliente encontrado.</p>,
  errorMessage: 'Falha ao buscar clientes.',
  label: 'Clientes',
}
function response(items: Item[], page = 1, lastPage = 1) {
  return new Response(JSON.stringify({ data: items, meta: { total: lastPage === 1 ? items.length : 40, current_page: page, last_page: lastPage } }), { headers: { 'Content-Type': 'application/json' } })
}
function mount(overrides: Partial<ListItemsProps<Item>> = {}) {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false }, mutations: { retry: false } } })
  function View({ config }: { config: ListItemsProps<Item> }) {
    return <MemoryRouter><QueryClientProvider client={client}><ListItems {...config} /></QueryClientProvider></MemoryRouter>
  }
  const view = render(<View config={{ ...props, ...overrides }} />)
  return { client, update: (config: Partial<ListItemsProps<Item>>) => view.rerender(<View config={{ ...props, ...overrides, ...config }} />) }
}

afterEach(() => vi.unstubAllGlobals())

describe('ListItems', () => {
  it('mostra carregamento, renderiza células formatadas e links acessíveis', async () => {
    let resolve!: (response: Response) => void
    const fetch = vi.fn(() => new Promise<Response>(done => { resolve = done }))
    vi.stubGlobal('fetch', fetch)
    mount()
    expect(screen.getByRole('status', { name: 'Carregando' })).toBeInTheDocument()
    await act(async () => resolve(response([alice])))
    expect(await screen.findByRole('table', { name: 'Clientes' })).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Alice' })).toHaveAttribute('href', '/clientes/1')
    expect(screen.getByRole('cell', { name: 'R$ 12.50' })).toBeInTheDocument()
    expect(screen.getByRole('region', { name: 'Clientes' })).toHaveAttribute('tabindex', '0')
    expect(fetch).toHaveBeenCalledWith('/api/customers?q=ana&status=open&page=1', expect.objectContaining({ credentials: 'include' }))
  })

  it('mostra a mensagem vazia sem uma tabela ou paginação desnecessária', async () => {
    vi.stubGlobal('fetch', vi.fn(async () => response([])))
    mount()
    expect(await screen.findByText('Nenhum cliente encontrado.')).toBeInTheDocument()
    expect(screen.queryByRole('table')).not.toBeInTheDocument()
    expect(screen.queryByRole('navigation')).not.toBeInTheDocument()
  })

  it('permite tentar novamente após uma falha na API', async () => {
    const fetch = vi.fn().mockResolvedValueOnce(new Response('{}', { status: 500 })).mockResolvedValueOnce(response([alice]))
    vi.stubGlobal('fetch', fetch)
    mount()
    expect(await screen.findByRole('alert')).toHaveTextContent('Falha ao buscar clientes.')
    await userEvent.click(screen.getByRole('button', { name: 'Tentar de novo' }))
    expect(await screen.findByRole('link', { name: 'Alice' })).toBeInTheDocument()
    expect(fetch).toHaveBeenCalledTimes(2)
  })

  it('preserva os filtros ao paginar e retorna à primeira página quando a URL muda', async () => {
    const fetch = vi.fn(async (url: string) => {
      const query = new URL(url, 'http://localhost').searchParams
      return response(query.get('page') === '2' ? [bob] : [alice], Number(query.get('page')), 2)
    })
    vi.stubGlobal('fetch', fetch)
    const { update } = mount()
    await screen.findByRole('link', { name: 'Alice' })
    await userEvent.click(screen.getByRole('button', { name: 'Próxima página' }))
    expect(await screen.findByRole('link', { name: 'Bruno' })).toBeInTheDocument()
    expect(fetch).toHaveBeenLastCalledWith('/api/customers?q=ana&status=open&page=2', expect.anything())
    update({ url: '/api/customers?q=maria&status=closed' })
    expect(await screen.findByRole('link', { name: 'Alice' })).toBeInTheDocument()
    expect(screen.queryByText('Bruno')).not.toBeInTheDocument()
    expect(fetch).toHaveBeenLastCalledWith('/api/customers?q=maria&status=closed&page=1', expect.anything())
  })

  it('atualiza os registros quando o prefixo de cache existente é invalidado', async () => {
    const fetch = vi.fn().mockResolvedValueOnce(response([alice])).mockResolvedValueOnce(response([bob]))
    vi.stubGlobal('fetch', fetch)
    const { client } = mount()
    await screen.findByRole('link', { name: 'Alice' })
    await act(async () => { await client.invalidateQueries({ queryKey: ['customers'] }) })
    expect(await screen.findByRole('link', { name: 'Bruno' })).toBeInTheDocument()
    await waitFor(() => expect(screen.queryByText('Alice')).not.toBeInTheDocument())
  })
})

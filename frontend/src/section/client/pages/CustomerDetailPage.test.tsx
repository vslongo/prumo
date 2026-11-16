import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { createMemoryRouter, RouterProvider } from 'react-router'
import { ToastProvider } from '../../../shared/ui/Toast'
import type { Customer } from '../api'
import { CustomerDetailPage } from './CustomerDetailPage'

const customer: Customer = {
  id: 'c1',
  name: 'João da Silva',
  email: null,
  phone: '11988887777',
  document: null,
  consent_whatsapp_at: '2026-11-01T12:00:00Z',
  consent_whatsapp_source: 'balcao',
  anonymized_at: null,
}

function json(status: number, body: unknown) {
  return Promise.resolve(new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } }))
}

/** Servidor de mentira com estado: escrita devolve o cliente novo e as leituras seguintes também. */
function mockApi(role: string, onWrite: (url: string, init: RequestInit) => Customer) {
  let current = customer
  const fetchMock = vi.fn((url: string, init: RequestInit = {}) => {
    if (url === '/sanctum/csrf-cookie') return Promise.resolve(new Response(null, { status: 204 }))
    if (url === '/api/me') return json(200, { data: { id: 'u1', name: 'Ana', email: 'a@x.test', role, is_active: true } })
    if (url === '/api/customers/c1/vehicles') return json(200, { data: [] })
    if ((init.method ?? 'GET') !== 'GET') current = onWrite(url, init)
    return json(200, { data: current })
  })
  vi.stubGlobal('fetch', fetchMock)
  return fetchMock
}

function renderPage() {
  const router = createMemoryRouter([{ path: '/clientes/:id', element: <CustomerDetailPage /> }], { initialEntries: ['/clientes/c1'] })
  render(
    <QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}>
      <ToastProvider>
        <RouterProvider router={router} />
      </ToastProvider>
    </QueryClientProvider>,
  )
}

describe('CustomerDetailPage', () => {
  afterEach(() => vi.unstubAllGlobals())

  it('técnico edita o cliente, revoga o WhatsApp e não vê as ações de LGPD', async () => {
    const fetchMock = mockApi('technician', (_url, init) => ({ ...customer, ...JSON.parse(String(init.body)), consent_whatsapp_at: null }))
    renderPage()
    const user = userEvent.setup()

    await user.click(await screen.findByRole('button', { name: 'Editar' }))
    const name = screen.getByLabelText('Nome')
    await user.clear(name)
    await user.type(name, 'João Souza')
    await user.click(screen.getByLabelText(/aceita receber/))
    await user.click(screen.getByRole('button', { name: 'Salvar' }))

    expect(await screen.findByText('Cliente atualizado.')).toBeInTheDocument()
    const [url, init] = fetchMock.mock.calls.find(([, i]) => i?.method === 'PATCH')!
    expect(url).toBe('/api/customers/c1')
    expect(JSON.parse(String(init!.body))).toMatchObject({ name: 'João Souza', phone: '11988887777', consent_whatsapp: false })
    expect(screen.getByText('Sem autorização')).toBeInTheDocument()
    expect(screen.queryByText('Privacidade (LGPD)')).not.toBeInTheDocument()
  })

  it('valida o CPF/CNPJ antes de enviar', async () => {
    const fetchMock = mockApi('owner', () => customer)
    renderPage()
    const user = userEvent.setup()

    await user.click(await screen.findByRole('button', { name: 'Editar' }))
    await user.type(screen.getByLabelText('CPF ou CNPJ'), '123')
    await user.click(screen.getByRole('button', { name: 'Salvar' }))

    expect(await screen.findByText('CPF (11 dígitos) ou CNPJ (14 dígitos).')).toBeInTheDocument()
    expect(fetchMock.mock.calls.some(([, i]) => i?.method === 'PATCH')).toBe(false)
  })

  it('dono anonimiza o cliente depois de confirmar', async () => {
    const fetchMock = mockApi('owner', () => ({
      ...customer,
      name: 'Cliente anonimizado',
      phone: null,
      consent_whatsapp_at: null,
      anonymized_at: '2026-11-04T12:00:00Z',
    }))
    renderPage()
    const user = userEvent.setup()

    await user.click(await screen.findByRole('button', { name: 'Anonimizar cliente' }))
    expect(screen.getByText(/Não dá para desfazer/)).toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: 'Anonimizar' }))

    expect(await screen.findByText(/Cliente anonimizado em/)).toBeInTheDocument()
    expect(fetchMock.mock.calls.some(([u, i]) => u === '/api/customers/c1/anonymize' && i?.method === 'POST')).toBe(true)
    expect(screen.queryByRole('button', { name: 'Editar' })).not.toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Anonimizar cliente' })).not.toBeInTheDocument()
  })
})

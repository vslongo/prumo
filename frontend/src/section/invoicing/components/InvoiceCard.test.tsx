import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router'
import { ToastProvider } from '../../../shared/ui/Toast'
import type { Invoice } from '../api'
import { InvoiceCard } from './InvoiceCard'

function json(status: number, body: unknown) {
  return Promise.resolve(new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } }))
}

const user = (role: string) => ({ id: 'u1', name: 'Ana', email: 'a@x.test', role, is_active: true, two_factor: { enabled: false } })

const invoice = (over: Partial<Invoice> = {}): Invoice => ({
  id: 'i1', service_order_id: 'o1', status: 'authorized', environment: 'production', dps_series: '1', dps_number: 1,
  access_key: 'K', nfse_number: '42', amount_cents: 25996, include_products: true, error_code: null, error_message: null,
  has_xml: true, has_pdf: true, authorized_at: '2026-11-24T12:00:00Z', canceled_at: null, cancel_reason: null, created_at: '2026-11-24T12:00:00Z', ...over,
})

function renderCard({ role = 'front', list = [] as Invoice[], status = 'delivered' as const, onPost }: { role?: string; list?: Invoice[]; status?: 'delivered' | 'in_progress'; onPost?: () => Promise<Response> }) {
  let current = list
  const fetchMock = vi.fn((url: string, init?: RequestInit) => {
    if (url === '/sanctum/csrf-cookie') return Promise.resolve(new Response(null, { status: 204 }))
    if (url === '/api/me') return json(200, { data: user(role) })
    if (init?.method === 'POST' && url === '/api/service-orders/o1/invoices') {
      if (onPost) return onPost()
      current = [invoice()]
      return json(202, { data: invoice() })
    }
    return json(200, { data: current })
  })
  vi.stubGlobal('fetch', fetchMock)
  render(
    <QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}>
      <MemoryRouter>
        <ToastProvider>
          <InvoiceCard orderId="o1" orderStatus={status} />
        </ToastProvider>
      </MemoryRouter>
    </QueryClientProvider>,
  )
  return fetchMock
}

afterEach(() => vi.unstubAllGlobals())

it('emite a nota da OS entregue e mostra o número', async () => {
  renderCard({})

  await userEvent.click(await screen.findByRole('button', { name: 'Emitir nota fiscal' }))

  expect(await screen.findByText('42')).toBeInTheDocument()
  expect(screen.getByText('Autorizada')).toBeInTheDocument()
  expect(screen.getByRole('button', { name: 'Cancelar nota' })).toBeInTheDocument()
})

it('técnico vê a nota mas não emite nem cancela', async () => {
  renderCard({ role: 'technician' })

  expect(await screen.findByText(/O balcão ou o administrador emite/)).toBeInTheDocument()
  expect(screen.queryByRole('button', { name: 'Emitir nota fiscal' })).not.toBeInTheDocument()
})

it('sem dados fiscais leva para as configurações', async () => {
  renderCard({ onPost: () => json(422, { message: 'x', errors: { fiscal_profile: ['Preencha os dados fiscais em Configurações → Nota fiscal.'] } }) })

  await userEvent.click(await screen.findByRole('button', { name: 'Emitir nota fiscal' }))

  expect(await screen.findByRole('link', { name: 'Abrir configurações' })).toHaveAttribute('href', '/configuracoes?aba=fiscal')
})

it('nota recusada mostra o motivo e deixa tentar de novo', async () => {
  renderCard({ list: [invoice({ status: 'error', error_code: 'E0312', error_message: 'Código de tributação inválido.' })] })

  expect(await screen.findByText(/Código de tributação inválido\. \(E0312\)/)).toBeInTheDocument()
  expect(screen.getByRole('button', { name: 'Tentar de novo' })).toBeInTheDocument()
})

it('OS ainda aberta e sem nota não mostra o card', async () => {
  const fetchMock = renderCard({ status: 'in_progress' })

  await vi.waitFor(() => expect(fetchMock).toHaveBeenCalledWith('/api/service-orders/o1/invoices', expect.anything()))
  expect(screen.queryByText('Nota fiscal')).not.toBeInTheDocument()
})

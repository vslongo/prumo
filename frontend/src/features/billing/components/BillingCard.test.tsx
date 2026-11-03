import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { ToastProvider } from '../../../shared/ui/Toast'
import type { Access, BillingOverview } from '../api'
import { billingNotice } from './BillingBanner'
import { BillingCard } from './BillingCard'

function json(status: number, body: unknown) {
  return Promise.resolve(new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } }))
}

const plans = [
  { key: 'solo', name: 'Solo', price_cents: 4900, max_users: 1 },
  { key: 'oficina', name: 'Oficina', price_cents: 7900, max_users: 5 },
  { key: 'pro', name: 'Pro', price_cents: 14900, max_users: null },
]

const access = (over: Partial<Access> = {}): Access => ({
  state: 'trial', can_write: true, plan: null, subscription_status: null, trial_ends_at: '2026-11-17T12:00:00Z', paid_until: null, read_only_from: '2026-11-17T12:00:00Z', max_users: null, ...over,
})

const overview = (over: Partial<BillingOverview> = {}): BillingOverview => ({ access: access(), subscription: null, plans, active_users: 2, ...over } as BillingOverview)

function renderCard(first: BillingOverview, afterSubscribe?: BillingOverview) {
  const fetchMock = vi.fn((url: string, _init?: RequestInit) => {
    if (url === '/sanctum/csrf-cookie') return Promise.resolve(new Response(null, { status: 204 }))
    if (url === '/api/billing/subscribe') return json(202, { data: afterSubscribe })
    return json(200, { data: first })
  })
  vi.stubGlobal('fetch', fetchMock)
  render(
    <QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}>
      <ToastProvider>
        <BillingCard />
      </ToastProvider>
    </QueryClientProvider>,
  )
  return fetchMock
}

describe('BillingCard', () => {
  afterEach(() => vi.unstubAllGlobals())

  it('no trial: escolhe plano, informa CPF e assina; mostra o link da fatura', async () => {
    const fetchMock = renderCard(overview(), overview({
      subscription: { plan: 'oficina', status: 'pending', invoice_url: 'https://asaas.test/i/abc', error: null, provisioning: false, paid_until: null, past_due_since: null, canceled_at: null },
    }))

    // Solo não comporta os 2 usuários ativos.
    expect(await screen.findByRole('radio', { name: /Solo/ })).toBeDisabled()
    await userEvent.click(screen.getByRole('radio', { name: /Oficina/ }))
    await userEvent.type(screen.getByLabelText('CPF ou CNPJ do responsável'), '529.982.247-25')
    await userEvent.click(screen.getByRole('button', { name: 'Assinar plano Oficina' }))

    expect(await screen.findByRole('link', { name: 'Pagar fatura' })).toHaveAttribute('href', 'https://asaas.test/i/abc')
    const [, init] = fetchMock.mock.calls.find(([url]) => url === '/api/billing/subscribe') ?? []
    expect(JSON.parse(String(init?.body))).toEqual({ plan: 'oficina', cpf_cnpj: '529.982.247-25' })
  })

  it('pede o plano e o documento antes de enviar', async () => {
    const fetchMock = renderCard(overview())

    await userEvent.click(await screen.findByRole('button', { name: 'Assinar' }))
    expect(screen.getByText('Escolha um plano.')).toBeInTheDocument()
    expect(fetchMock.mock.calls.some(([url]) => url === '/api/billing/subscribe')).toBe(false)
  })

  it('assinatura ativa: troca de plano e cancela; sem formulário de assinar', async () => {
    renderCard(overview({
      access: access({ state: 'active', plan: 'oficina', subscription_status: 'active', paid_until: '2026-12-10', read_only_from: null }),
      subscription: { plan: 'oficina', status: 'active', invoice_url: null, error: null, provisioning: false, paid_until: '2026-12-10', past_due_since: null, canceled_at: null },
    }))

    expect(await screen.findByText('Plano Oficina ativo, pago até 10/12/2026.')).toBeInTheDocument()
    expect(screen.queryByLabelText('CPF ou CNPJ do responsável')).not.toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Trocar plano' })).toBeDisabled()
    await userEvent.click(screen.getByRole('radio', { name: /Pro/ }))
    expect(screen.getByRole('button', { name: 'Trocar plano' })).toBeEnabled()
    expect(screen.getByRole('button', { name: 'Cancelar assinatura' })).toBeInTheDocument()
  })

  it('Asaas recusou: mostra o erro e deixa assinar de novo', async () => {
    renderCard(overview({
      subscription: { plan: 'solo', status: 'pending', invoice_url: null, error: 'O CPF/CNPJ informado é inválido.', provisioning: false, paid_until: null, past_due_since: null, canceled_at: null },
    }))

    expect(await screen.findByText('O CPF/CNPJ informado é inválido.')).toBeInTheDocument()
    expect(screen.getByLabelText('CPF ou CNPJ do responsável')).toBeInTheDocument()
  })
})

describe('aviso de assinatura', () => {
  const now = new Date('2026-11-14T12:00:00Z')

  it('trial: só avisa nos últimos dias e some se já escolheu plano', () => {
    expect(billingNotice(access({ read_only_from: '2026-11-28T12:00:00Z' }), now)).toBeNull()
    expect(billingNotice(access(), now)?.text).toBe('Seu teste grátis acaba em 3 dias.')
    expect(billingNotice(access({ subscription_status: 'pending' }), now)).toBeNull()
  })

  it('atraso, cancelada e somente leitura', () => {
    expect(billingNotice(access({ state: 'past_due', read_only_from: '2026-11-20T12:00:00Z' }), now)?.severity).toBe('warning')
    expect(billingNotice(access({ state: 'canceled', paid_until: '2026-12-10' }), now)?.text).toContain('10/12/2026')
    expect(billingNotice(access({ state: 'read_only', can_write: false }), now)).toMatchObject({ severity: 'error', cta: 'Regularizar' })
    expect(billingNotice(access({ state: 'active' }), now)).toBeNull()
  })
})

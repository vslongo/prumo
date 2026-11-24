import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { ToastProvider } from '../../../shared/ui/Toast'
import { percentToBp, type FiscalProfile } from '../api'
import { FiscalCard } from './FiscalCard'

function json(status: number, body: unknown) {
  return Promise.resolve(new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } }))
}

const profile = (over: Partial<FiscalProfile> = {}): FiscalProfile => ({
  cnpj: '11222333000181', municipal_registration: null, city_ibge_code: '4205407', tax_regime: 'simples_me_epp', national_tax_code: '140101',
  municipal_tax_code: null, iss_rate_bp: 300, dps_series: '1', include_products: true, environment: 'restricted', certificate: null, ...over,
})

function renderCard(first: FiscalProfile | null) {
  const fetchMock = vi.fn((url: string, init?: RequestInit) => {
    if (url === '/sanctum/csrf-cookie') return Promise.resolve(new Response(null, { status: 204 }))
    if (init?.method === 'PUT') return json(200, { data: profile(JSON.parse(String(init.body))) })
    return json(200, { data: first })
  })
  vi.stubGlobal('fetch', fetchMock)
  render(
    <QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}>
      <ToastProvider>
        <FiscalCard />
      </ToastProvider>
    </QueryClientProvider>,
  )
  return fetchMock
}

afterEach(() => vi.unstubAllGlobals())

it('converte a alíquota em pontos-base', () => {
  expect(percentToBp('3')).toBe(300)
  expect(percentToBp('2,5')).toBe(250)
  expect(percentToBp('4,75')).toBe(475)
  expect(percentToBp('')).toBeNull()
  expect(percentToBp('abc')).toBeNaN()
})

it('salva o perfil com CNPJ, código IBGE e alíquota normalizados', async () => {
  const fetchMock = renderCard(null)
  const user = userEvent.setup()

  await user.type(await screen.findByLabelText('CNPJ da oficina'), '11.222.333/0001-81')
  await user.type(screen.getByLabelText('Alíquota do ISS (%)'), '2,5')
  await user.type(screen.getByLabelText('Código IBGE do município'), '4205407')
  await user.click(screen.getByRole('button', { name: 'Salvar dados fiscais' }))

  const put = await vi.waitFor(() => {
    const call = fetchMock.mock.calls.find(([, init]) => init?.method === 'PUT')
    if (!call) throw new Error('sem PUT')
    return JSON.parse(String(call[1]?.body))
  })
  expect(put).toMatchObject({ cnpj: '11.222.333/0001-81', iss_rate_bp: 250, city_ibge_code: '4205407', national_tax_code: '140101', include_products: true })
  expect(await screen.findByText('Dados fiscais salvos.')).toBeInTheDocument()
})

it('MEI não pede alíquota de ISS', async () => {
  renderCard(profile({ tax_regime: 'mei', iss_rate_bp: null }))

  await screen.findByLabelText('CNPJ da oficina')
  expect(screen.queryByLabelText('Alíquota do ISS (%)')).not.toBeInTheDocument()
})

it('avisa quando o certificado vence em até 30 dias', async () => {
  const soon = new Date(Date.now() + 10 * 86_400_000).toISOString()
  renderCard(profile({ certificate: { expires_at: soon, valid: true } }))

  expect(await screen.findByText(/Renove e envie o novo/)).toBeInTheDocument()
  expect(screen.getByRole('button', { name: 'Trocar certificado' })).toBeInTheDocument()
})

import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { ToastProvider } from '../../../shared/ui/Toast'
import type { User } from '../api'
import { TwoFactorCard } from './TwoFactorCard'

const owner: User = {
  id: 'u1',
  name: 'Carlos',
  email: 'dono@demo.test',
  role: 'owner',
  is_active: true,
  two_factor: { enabled: false, required_from: '2026-11-11T12:00:00Z', blocked: false },
}

function json(status: number, body: unknown) {
  return Promise.resolve(new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } }))
}

function renderCard(me: User) {
  render(
    <QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}>
      <ToastProvider>
        <TwoFactorCard me={me} />
      </ToastProvider>
    </QueryClientProvider>,
  )
}

describe('TwoFactorCard', () => {
  afterEach(() => vi.unstubAllGlobals())

  it('ativa: mostra a chave, confirma com o código e entrega os códigos de recuperação', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn((url: string) => {
        if (url === '/sanctum/csrf-cookie') return Promise.resolve(new Response(null, { status: 204 }))
        if (url === '/api/two-factor') return json(200, { data: { secret: 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP', otpauth_url: 'otpauth://totp/Prumo:dono' } })
        return json(200, { data: { recovery_codes: ['aaaaa-bbbbb', 'ccccc-ddddd'] } })
      }),
    )
    renderCard(owner)
    const user = userEvent.setup()

    expect(screen.getByText(/Obrigatório para o dono a partir de 11\/11\/2026/)).toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: 'Ativar verificação em duas etapas' }))

    expect(await screen.findByText('JBSW Y3DP EHPK 3PXP JBSW Y3DP EHPK 3PXP')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Abrir no app autenticador' })).toHaveAttribute('href', 'otpauth://totp/Prumo:dono')
    const activate = screen.getByRole('button', { name: 'Ativar' })
    expect(activate).toBeDisabled()

    await user.type(screen.getByLabelText('Código do app'), '12a3456')
    await user.click(activate)

    expect(await screen.findByText('aaaaa-bbbbb')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Já guardei os códigos' })).toBeInTheDocument()
  })

  it('desativa pedindo a senha', async () => {
    // csrf-cookie e DELETE /api/two-factor respondem 204
    const fetchMock = vi.fn((_url: string, _init?: RequestInit) => Promise.resolve(new Response(null, { status: 204 })))
    vi.stubGlobal('fetch', fetchMock)
    renderCard({ ...owner, two_factor: { enabled: true, required_from: null, blocked: false } })
    const user = userEvent.setup()

    await user.click(screen.getByRole('button', { name: 'Desativar' }))
    await user.type(screen.getByLabelText('Sua senha'), 'password')
    await user.click(screen.getByRole('button', { name: 'Desativar' }))

    expect(await screen.findByText('Verificação em duas etapas desativada.')).toBeInTheDocument()
    expect(fetchMock).toHaveBeenCalledWith('/api/two-factor', expect.objectContaining({ method: 'DELETE', body: JSON.stringify({ password: 'password' }) }))
  })
})

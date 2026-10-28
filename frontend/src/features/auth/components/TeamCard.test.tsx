import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { ToastProvider } from '../../../shared/ui/Toast'
import { TeamCard } from './TeamCard'

function json(status: number, body: unknown) {
  return Promise.resolve(new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } }))
}

const owner = { id: 'u1', name: 'Carlos', email: 'c@x.test', role: 'owner', is_active: true }
const tech = { id: 'u2', name: 'Zé', email: 'z@x.test', role: 'technician', is_active: true }
const gone = { id: 'u3', name: 'Beto', email: 'b@x.test', role: 'front', is_active: false }

function renderTeam() {
  const fetchMock = vi.fn((url: string, init?: RequestInit) => {
    if (url === '/api/me') return json(200, { data: owner })
    if (url === '/api/users') return json(200, { data: [owner, tech, gone] })
    if (url === '/api/user-invitations' && (!init?.method || init.method === 'GET')) return json(200, { data: [{ id: 'i1', name: 'Novato', role: 'technician', expires_at: '2099-01-01T00:00:00Z', link: 'http://localhost:5173/convite/abc', whatsapp_url: 'https://wa.me/?text=oi' }] })
    if (url === '/sanctum/csrf-cookie') return Promise.resolve(new Response(null, { status: 204 }))
    return json(200, { data: { ...tech, is_active: false } })
  })
  vi.stubGlobal('fetch', fetchMock)
  render(
    <QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}>
      <ToastProvider>
        <TeamCard />
      </ToastProvider>
    </QueryClientProvider>,
  )
  return fetchMock
}

describe('TeamCard', () => {
  afterEach(() => vi.unstubAllGlobals())

  it('dono não é editável; ativo tem Desativar, desativado tem Reativar; mostra convite pendente', async () => {
    renderTeam()

    const ownerRow = (await screen.findByText('Carlos')).closest('li') as HTMLElement
    expect(within(ownerRow).queryByRole('button')).not.toBeInTheDocument()

    const techRow = screen.getByText('Zé').closest('li') as HTMLElement
    expect(within(techRow).getByRole('button', { name: 'Desativar' })).toBeInTheDocument()

    const goneRow = screen.getByText('Beto').closest('li') as HTMLElement
    expect(within(goneRow).getByText('Balcão · desativado')).toBeInTheDocument()
    expect(within(goneRow).getByRole('button', { name: 'Reativar' })).toBeInTheDocument()

    const inviteRow = (await screen.findByText('Novato')).closest('li') as HTMLElement
    expect(within(inviteRow).getByDisplayValue('http://localhost:5173/convite/abc')).toBeInTheDocument()
    expect(within(inviteRow).getByRole('button', { name: 'Copiar link' })).toBeInTheDocument()
    expect(within(inviteRow).getByRole('link', { name: 'WhatsApp' })).toHaveAttribute('href', 'https://wa.me/?text=oi')
  })

  it('admin não vê controles de outro admin nem a opção de criar admin', async () => {
    const admin = { id: 'u4', name: 'Ana', email: 'a@x.test', role: 'admin', is_active: true }
    const otherAdmin = { id: 'u5', name: 'Bruno', email: 'br@x.test', role: 'admin', is_active: true }
    vi.stubGlobal(
      'fetch',
      vi.fn((url: string) => {
        if (url === '/api/me') return json(200, { data: admin })
        if (url === '/api/users') return json(200, { data: [owner, admin, otherAdmin, tech] })
        return json(200, { data: [] })
      }),
    )
    render(
      <QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}>
        <ToastProvider>
          <TeamCard />
        </ToastProvider>
      </QueryClientProvider>,
    )

    const otherRow = (await screen.findByText('Bruno')).closest('li') as HTMLElement
    expect(within(otherRow).queryByRole('button')).not.toBeInTheDocument()
    const techRow = screen.getByText('Zé').closest('li') as HTMLElement
    expect(within(techRow).getByRole('button', { name: 'Desativar' })).toBeInTheDocument()
    expect(screen.queryByRole('option', { name: 'Administrador' })).not.toBeInTheDocument()
  })

  it('pede confirmação antes de desativar', async () => {
    const fetchMock = renderTeam()
    const user = userEvent.setup()

    const techRow = (await screen.findByText('Zé')).closest('li') as HTMLElement
    await user.click(within(techRow).getByRole('button', { name: 'Desativar' }))
    expect(fetchMock).not.toHaveBeenCalledWith('/api/users/u2/deactivate', expect.anything())

    await user.click(within(await screen.findByRole('dialog')).getByRole('button', { name: 'Desativar' }))
    expect(fetchMock).toHaveBeenCalledWith('/api/users/u2/deactivate', expect.objectContaining({ method: 'POST' }))
  })
})

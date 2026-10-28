import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { createMemoryRouter, RouterProvider } from 'react-router'
import { AcceptInvitationPage } from './AcceptInvitationPage'

const token = 'a'.repeat(64)

function json(status: number, body: unknown) {
  return Promise.resolve(new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } }))
}

function renderAccept(invitation: () => Promise<Response>, accept: () => Promise<Response> = () => json(201, {})) {
  vi.stubGlobal(
    'fetch',
    vi.fn((url: string) => {
      if (url === '/sanctum/csrf-cookie') return Promise.resolve(new Response(null, { status: 204 }))
      if (url.endsWith('/accept')) return accept()
      return invitation()
    }),
  )
  const router = createMemoryRouter(
    [
      { path: '/convite/:token', element: <AcceptInvitationPage /> },
      { path: '/', element: <p>Painel</p> },
    ],
    { initialEntries: [`/convite/${token}`] },
  )
  render(
    <QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}>
      <RouterProvider router={router} />
    </QueryClientProvider>,
  )
}

const valid = () => json(200, { data: { name: 'Zé', role: 'technician', workshop: 'Auto Center Silva', usable: true } })

describe('AcceptInvitationPage', () => {
  afterEach(() => vi.unstubAllGlobals())

  it('mostra a oficina e o papel, cria o acesso e vai para o painel', async () => {
    renderAccept(valid, () => json(201, { data: { id: 'u2', name: 'Zé', email: 'ze@oficina.test', role: 'technician' } }))
    const user = userEvent.setup()

    expect(await screen.findByText('Entrar na Auto Center Silva')).toBeInTheDocument()
    expect(screen.getByText('Você foi convidado como Técnico. Crie seu acesso.')).toBeInTheDocument()
    expect(screen.getByLabelText('Seu nome')).toHaveValue('Zé')

    await user.type(screen.getByLabelText('E-mail'), 'ze@oficina.test')
    await user.type(screen.getByLabelText('Senha'), 'senha-forte-123')
    await user.click(screen.getByRole('button', { name: 'Criar acesso' }))

    expect(await screen.findByText('Painel')).toBeInTheDocument()
  })

  it('avisa quando o convite já foi usado ou expirou', async () => {
    renderAccept(() => json(200, { data: { name: 'Zé', role: 'technician', workshop: 'Oficina', usable: false } }))

    expect(await screen.findByText('Este convite já foi usado ou expirou. Peça um novo ao dono da oficina.')).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: 'Criar acesso' })).not.toBeInTheDocument()
  })

  it('avisa quando o link é inválido', async () => {
    renderAccept(() => json(404, { message: 'Convite inválido.' }))

    expect(await screen.findByText('Convite inválido. Confira o link ou peça um novo ao dono da oficina.')).toBeInTheDocument()
  })
})

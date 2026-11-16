import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { createMemoryRouter, RouterProvider } from 'react-router'
import { LoginPage } from './LoginPage'

function json(status: number, body: unknown) {
  return Promise.resolve(new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } }))
}

function renderLogin() {
  const router = createMemoryRouter(
    [
      { path: '/login', element: <LoginPage /> },
      { path: '/', element: <p>Painel</p> },
    ],
    { initialEntries: ['/login'] },
  )
  render(
    <QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}>
      <RouterProvider router={router} />
    </QueryClientProvider>,
  )
}

describe('LoginPage', () => {
  afterEach(() => vi.unstubAllGlobals())

  it('mostra o erro do servidor quando a senha está errada', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn((url: string) => {
        if (url === '/api/me') return json(401, { message: 'Unauthenticated.' })
        if (url === '/sanctum/csrf-cookie') return Promise.resolve(new Response(null, { status: 204 }))
        return json(422, { message: 'E-mail ou senha inválidos.', errors: { email: ['E-mail ou senha inválidos.'] } })
      }),
    )
    renderLogin()
    const user = userEvent.setup()

    await user.type(await screen.findByLabelText('E-mail'), 'dono@demo.test')
    await user.type(screen.getByLabelText('Senha'), 'errada')
    await user.click(screen.getByRole('button', { name: 'Entrar' }))

    expect(await screen.findByText('E-mail ou senha inválidos.')).toBeInTheDocument()
  })

  it('entra e vai para o painel', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn((url: string) => {
        if (url === '/api/me') return json(401, {})
        if (url === '/sanctum/csrf-cookie') return Promise.resolve(new Response(null, { status: 204 }))
        return json(200, { data: { id: 'u1', name: 'Carlos', email: 'dono@demo.test', role: 'owner' } })
      }),
    )
    renderLogin()
    const user = userEvent.setup()

    await user.type(await screen.findByLabelText('E-mail'), 'dono@demo.test')
    await user.type(screen.getByLabelText('Senha'), 'password')
    await user.click(screen.getByRole('button', { name: 'Entrar' }))

    expect(await screen.findByText('Painel')).toBeInTheDocument()
  })

  it('com 2FA pede o código do app e aceita código de recuperação', async () => {
    const fetchMock = vi.fn((url: string, init: RequestInit = {}) => {
      if (url === '/api/me') return json(401, {})
      if (url === '/sanctum/csrf-cookie') return Promise.resolve(new Response(null, { status: 204 }))
      if (url === '/api/login') return json(200, { two_factor: true })
      const body = JSON.parse(String(init.body))
      if (body.code) return json(422, { message: 'Código inválido.', errors: { code: ['Código inválido.'] } })
      return json(200, { data: { id: 'u1', name: 'Carlos', email: 'dono@demo.test', role: 'owner' } })
    })
    vi.stubGlobal('fetch', fetchMock)
    renderLogin()
    const user = userEvent.setup()

    await user.type(await screen.findByLabelText('E-mail'), 'dono@demo.test')
    await user.type(screen.getByLabelText('Senha'), 'password')
    await user.click(screen.getByRole('button', { name: 'Entrar' }))

    await user.type(await screen.findByLabelText('Código do app'), '123456')
    await user.click(screen.getByRole('button', { name: 'Entrar' }))
    expect(await screen.findByText('Código inválido.')).toBeInTheDocument()

    await user.click(screen.getByRole('button', { name: /usar código de recuperação/ }))
    await user.type(screen.getByLabelText('Código de recuperação'), 'abcde-fghij')
    await user.click(screen.getByRole('button', { name: 'Entrar' }))

    expect(await screen.findByText('Painel')).toBeInTheDocument()
    expect(fetchMock).toHaveBeenLastCalledWith('/api/login/two-factor', expect.objectContaining({ body: JSON.stringify({ recovery_code: 'abcde-fghij' }) }))
  })
})

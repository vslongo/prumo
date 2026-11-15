import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { createMemoryRouter, RouterProvider } from 'react-router'
import { SignUpPage } from './SignUpPage'

function json(status: number, body: unknown) {
  return Promise.resolve(new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } }))
}

function renderSignUp(signupResponse: () => Promise<Response>) {
  const fetchMock = vi.fn((url: string) => {
    if (url === '/api/me') return json(401, {})
    if (url === '/sanctum/csrf-cookie') return Promise.resolve(new Response(null, { status: 204 }))
    return signupResponse()
  })
  vi.stubGlobal('fetch', fetchMock)
  const router = createMemoryRouter(
    [
      { path: '/cadastro', element: <SignUpPage /> },
      { path: '/', element: <p>Painel</p> },
    ],
    { initialEntries: ['/cadastro'] },
  )
  render(
    <QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}>
      <RouterProvider router={router} />
    </QueryClientProvider>,
  )
  return fetchMock
}

async function fillForm(password = 'senha-forte-123') {
  const user = userEvent.setup()
  await user.type(await screen.findByLabelText('Nome da oficina'), 'Auto Center Silva')
  await user.type(screen.getByLabelText('Seu nome'), 'Carlos')
  await user.type(screen.getByLabelText('E-mail'), 'carlos@silva.test')
  await user.type(screen.getByLabelText('Senha'), password)
  await user.click(screen.getByRole('button', { name: 'Criar conta' }))
}

describe('SignUpPage', () => {
  afterEach(() => vi.unstubAllGlobals())

  it('cria a conta e vai para o painel', async () => {
    const fetchMock = renderSignUp(() => json(201, { data: { id: 'u1', name: 'Carlos', email: 'carlos@silva.test', role: 'owner', workshop: 'Auto Center Silva' } }))
    await fillForm()

    expect(await screen.findByText('Painel')).toBeInTheDocument()
    expect(fetchMock).toHaveBeenCalledWith('/api/signup', expect.anything())
  })

  it('valida a senha curta sem chamar a API', async () => {
    const fetchMock = renderSignUp(() => json(201, {}))
    await fillForm('123')

    expect(await screen.findByText('A senha precisa ter pelo menos 8 caracteres.')).toBeInTheDocument()
    expect(fetchMock).not.toHaveBeenCalledWith('/api/signup', expect.anything())
  })

  it('mostra no campo o erro de e-mail já cadastrado', async () => {
    const message = 'Este e-mail já tem cadastro. Entre com ele ou use outro.'
    renderSignUp(() => json(422, { message, errors: { email: [message] } }))
    await fillForm()

    expect(await screen.findByText(message)).toBeInTheDocument()
  })
})

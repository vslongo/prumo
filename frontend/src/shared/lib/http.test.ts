import { ApiError, http } from './http'

const json = (status: number, body: unknown) => new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } })

/** Responde às chamadas da API na ordem dada; o cookie de CSRF sempre dá certo. */
function stubApi(...responses: Response[]) {
  const fetchMock = vi.fn((url: string) => Promise.resolve(url === '/sanctum/csrf-cookie' ? new Response(null, { status: 204 }) : responses.shift()!))
  vi.stubGlobal('fetch', fetchMock)
  return fetchMock
}

const calls = (fetchMock: ReturnType<typeof stubApi>, url: string) => fetchMock.mock.calls.filter(([called]) => called === url).length

describe('http', () => {
  afterEach(() => vi.unstubAllGlobals())

  it('em 419 pega um token novo e repete a chamada uma vez', async () => {
    const fetchMock = stubApi(json(419, { message: 'CSRF token mismatch.' }), json(200, { data: 'ok' }))

    await expect(http.post('/api/login', {})).resolves.toEqual({ data: 'ok' })
    expect(calls(fetchMock, '/api/login')).toBe(2)
  })

  it('não repete mais de uma vez', async () => {
    const fetchMock = stubApi(json(419, { message: 'CSRF token mismatch.' }), json(419, { message: 'CSRF token mismatch.' }))

    await expect(http.post('/api/login', {})).rejects.toBeInstanceOf(ApiError)
    expect(calls(fetchMock, '/api/login')).toBe(2)
  })
})

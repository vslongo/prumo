import type { ErrorEvent } from '@sentry/react'
import { scrubBreadcrumb, scrubEvent, scrubUrl } from './sentry'

describe('sentry scrubbing', () => {
  it('tira query string, hash e token de link público da URL', () => {
    expect(scrubUrl('https://app.prumo.test/clientes?q=11988887777')).toBe('https://app.prumo.test/clientes')
    expect(scrubUrl('https://app.prumo.test/o/abc123secreto')).toBe('https://app.prumo.test/o/[token]')
    expect(scrubUrl('/convite/tok3n#x')).toBe('/convite/[token]')
    expect(scrubUrl('/api/public/quotes/abc/approve')).toBe('/api/public/quotes/[token]/approve')
    expect(scrubUrl('/os/123')).toBe('/os/123')
  })

  it('evento fica só com a URL limpa e o id do usuário', () => {
    const event = scrubEvent({
      type: undefined,
      request: { url: 'https://app.prumo.test/o/segredo?x=1', headers: { Cookie: 'x' }, query_string: 'x=1', data: { nome: 'João' } },
      user: { id: 'u1', email: 'dono@oficina.test', ip_address: '1.2.3.4' },
    } as ErrorEvent)

    expect(event.request).toEqual({ url: 'https://app.prumo.test/o/[token]' })
    expect(event.user).toEqual({ id: 'u1' })
  })

  it('descarta console e limpa URLs de fetch e navegação', () => {
    expect(scrubBreadcrumb({ category: 'console', message: 'cliente João 11988887777' })).toBeNull()
    expect(scrubBreadcrumb({ category: 'fetch', data: { url: '/api/customers?q=123', status_code: 200 } })?.data).toEqual({ url: '/api/customers', status_code: 200 })
    expect(scrubBreadcrumb({ category: 'navigation', data: { from: '/o/abc', to: '/convite/xyz' } })?.data).toEqual({ from: '/o/[token]', to: '/convite/[token]' })
  })
})

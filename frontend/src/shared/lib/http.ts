/**
 * Único cliente HTTP do app. Sessão SPA do Sanctum: cookie de sessão + XSRF-TOKEN.
 * Chamadas HTTP só nos arquivos features/<feature>/api.ts.
 */

export type FieldErrors = Record<string, string[]>

export class ApiError extends Error {
  readonly status: number
  readonly errors: FieldErrors

  constructor(status: number, message: string, errors: FieldErrors = {}) {
    super(message)
    this.status = status
    this.errors = errors
  }

  /** Primeira mensagem de um campo (ex.: "items.0.fields.liters"). */
  field(name: string): string | undefined {
    return this.errors[name]?.[0]
  }
}

function readCookie(name: string): string | undefined {
  const match = document.cookie.split('; ').find((c) => c.startsWith(`${name}=`))
  return match ? decodeURIComponent(match.slice(name.length + 1)) : undefined
}

let csrfReady: Promise<void> | null = null

/** Garante o cookie XSRF-TOKEN antes da primeira escrita. */
export function ensureCsrf(force = false): Promise<void> {
  if (force || !csrfReady) {
    csrfReady = fetch('/sanctum/csrf-cookie', { credentials: 'include' }).then(() => undefined)
  }
  return csrfReady
}

type Method = 'GET' | 'POST' | 'PUT' | 'DELETE'

async function request<T>(method: Method, url: string, body?: unknown): Promise<T> {
  if (method !== 'GET') {
    await ensureCsrf()
  }

  const headers: Record<string, string> = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
  const xsrf = readCookie('XSRF-TOKEN')
  if (xsrf) headers['X-XSRF-TOKEN'] = xsrf
  if (body !== undefined) headers['Content-Type'] = 'application/json'

  let response: Response
  try {
    response = await fetch(url, { method, headers, credentials: 'include', body: body === undefined ? undefined : JSON.stringify(body) })
  } catch {
    throw new ApiError(0, 'Sem conexão. Confira a internet e tente de novo.')
  }

  if (response.status === 204) return undefined as T

  const data = await response.json().catch(() => ({}))

  if (!response.ok) {
    if (response.status === 419) csrfReady = null
    const message = typeof data?.message === 'string' && response.status !== 500 ? data.message : 'Não deu para salvar. Tente de novo.'
    throw new ApiError(response.status, message, data?.errors ?? {})
  }

  return data as T
}

export const http = {
  get: <T>(url: string) => request<T>('GET', url),
  post: <T>(url: string, body?: unknown) => request<T>('POST', url, body ?? {}),
  put: <T>(url: string, body?: unknown) => request<T>('PUT', url, body ?? {}),
  delete: <T>(url: string) => request<T>('DELETE', url),
}

export type Paginated<T> = { data: T[]; meta: { total: number; current_page: number; last_page: number } }
export type Wrapped<T> = { data: T }

import * as Sentry from '@sentry/react'

/** Segmentos que carregam token de acesso (link do orçamento, convite, senha nova) viram `[token]`. */
const TOKEN_PATHS = /(\/(?:o|convite|redefinir-senha|public\/quotes|public\/invitations)\/)[^/?#]+/g

/** Sem query string (a busca de cliente leva telefone/CPF), sem hash e sem token de link público. */
export function scrubUrl(url: string): string {
  return url.split(/[?#]/)[0].replace(TOKEN_PATHS, '$1[token]')
}

/** CLAUDE.md §10: nada de dado pessoal no Sentry. Fica só o caminho da página/requisição e o id do usuário. */
export function scrubEvent<T extends Sentry.ErrorEvent>(event: T): T {
  if (event.request) event.request = { url: event.request.url ? scrubUrl(event.request.url) : undefined }
  if (event.user) event.user = event.user.id ? { id: event.user.id } : undefined
  return event
}

export function scrubBreadcrumb(breadcrumb: Sentry.Breadcrumb): Sentry.Breadcrumb | null {
  // console.log pode levar qualquer coisa (inclusive resposta da API com dado de cliente).
  if (breadcrumb.category === 'console') return null
  const data = breadcrumb.data
  if (data) {
    for (const key of ['url', 'from', 'to'] as const) {
      if (typeof data[key] === 'string') data[key] = scrubUrl(data[key])
    }
  }
  return breadcrumb
}

/** Liga o Sentry só com VITE_SENTRY_DSN (produção/staging). Sem replay e sem tracing. */
export function initSentry() {
  const dsn = import.meta.env.VITE_SENTRY_DSN
  if (!dsn) return
  Sentry.init({
    dsn,
    environment: import.meta.env.VITE_SENTRY_ENVIRONMENT ?? import.meta.env.MODE,
    release: import.meta.env.VITE_SENTRY_RELEASE,
    // Coleta automática toda desligada (o padrão do SDK v11 é coletar); o scrub abaixo é a segunda barreira.
    dataCollection: { userInfo: false, cookies: false, httpHeaders: false, httpBodies: [], urlQueryParams: false },
    beforeSend: scrubEvent,
    beforeBreadcrumb: scrubBreadcrumb,
  })
}

/** Erros de render do React (createRoot) vão para o Sentry; sem DSN é no-op. */
export const reactErrorHandler = Sentry.reactErrorHandler

export const setSentryUser = (id: string | null) => Sentry.setUser(id ? { id } : null)

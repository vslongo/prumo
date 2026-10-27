import { ApiError, http, type Wrapped } from '../../shared/lib/http'
import type { QuoteStatus } from '../../shared/lib/status'

export type PublicQuoteItem = {
  position: number
  kind: 'service' | 'product'
  description: string
  quantity: string
  unit_price_cents: number
  total_cents: number
  details: { label: string; value: unknown; unit: string | null }[]
}

export type PublicQuote = {
  workshop: string
  customer_name: string
  vehicle: { plate: string; brand: string | null; model: string | null; year: number | null }
  status: QuoteStatus
  version: number
  notes: string | null
  total_cents: number
  expires_at: string | null
  items: PublicQuoteItem[]
}

type UploadUrl = { url: string; headers: Record<string, string | string[]>; expires_at: string }

const base = (token: string) => `/api/public/quotes/${token}`

export const publicQuoteApi = {
  get: async (token: string) => (await http.get<Wrapped<PublicQuote>>(base(token))).data,

  /** Assinatura vai direto para o storage pela URL pré-assinada; o backend só confere depois. */
  async uploadSignature(token: string, png: Blob): Promise<void> {
    const { data } = await http.post<Wrapped<UploadUrl>>(`${base(token)}/signature-upload-url`, { size: png.size })
    const headers: Record<string, string> = { 'Content-Type': 'image/png' }
    for (const [k, v] of Object.entries(data.headers ?? {})) {
      if (k.toLowerCase() !== 'host') headers[k] = Array.isArray(v) ? v.join(',') : v
    }
    const res = await fetch(data.url, { method: 'PUT', headers, body: png }).catch(() => null)
    if (!res || !res.ok) throw new ApiError(res?.status ?? 0, 'Não deu para enviar a assinatura. Tente de novo.')
  },

  approve: (token: string, signerName: string, items: number[]) =>
    http.post<{ message: string }>(`${base(token)}/approve`, { signer_name: signerName, items }),

  reject: (token: string, reason: string) => http.post<{ message: string }>(`${base(token)}/reject`, { reason: reason || null }),
}

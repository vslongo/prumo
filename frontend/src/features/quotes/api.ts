import { http, type Paginated, type Wrapped } from '../../shared/lib/http'
import type { QuoteStatus } from '../../shared/lib/status'
import type { Item, ItemInput } from '../items/types'
import type { VehicleSummary } from '../vehicles/api'

export type Quote = {
  id: string
  customer_id: string
  vehicle_id: string
  customer?: { id: string; name: string }
  vehicle?: VehicleSummary
  created_by: string
  status: QuoteStatus
  version: number
  mileage: number | null
  notes: string | null
  total_cents: number
  link_expires_at: string | null
  sent_at: string | null
  viewed_at: string | null
  decided_at: string | null
  rejection_reason: string | null
  items?: Item[]
  approval?: { signer_name: string; approved_item_ids: string[]; approved_at: string } | null
  created_at: string
}

export type QuoteInput = { customer_id: string; vehicle_id: string; mileage?: number | null; notes?: string; items: ItemInput[] }

export type SentQuote = { data: Quote; link: string; whatsapp_url: string | null; expires_at: string }

export const quotesApi = {
  list: (status?: QuoteStatus) => http.get<Paginated<Quote>>(`/api/quotes${status ? `?status=${status}` : ''}`),
  get: async (id: string) => (await http.get<Wrapped<Quote>>(`/api/quotes/${id}`)).data,
  create: async (input: QuoteInput) => (await http.post<Wrapped<Quote>>('/api/quotes', input)).data,
  addItem: async (id: string, item: ItemInput) => (await http.post<Wrapped<Quote>>(`/api/quotes/${id}/items`, item)).data,
  removeItem: async (id: string, itemId: string) => (await http.delete<Wrapped<Quote>>(`/api/quotes/${id}/items/${itemId}`)).data,
  send: (id: string) => http.post<SentQuote>(`/api/quotes/${id}/send`),
}

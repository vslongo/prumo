import { http, type Paginated, type Wrapped } from '../../shared/lib/http'

export type Customer = {
  id: string
  name: string
  email: string | null
  phone: string | null
  document: string | null
  consent_whatsapp_at: string | null
}

export type CustomerInput = {
  name: string
  phone?: string
  email?: string
  document?: string
  consent_whatsapp: boolean
  consent_source?: string
}

export const customersApi = {
  search: (q: string) => http.get<Paginated<Customer>>(`/api/customers?q=${encodeURIComponent(q)}`),
  get: async (id: string) => (await http.get<Wrapped<Customer>>(`/api/customers/${id}`)).data,
  create: async (input: CustomerInput) => (await http.post<Wrapped<Customer>>('/api/customers', input)).data,
}

export function formatPhone(phone: string | null): string {
  if (!phone) return '—'
  const d = phone.replace(/\D/g, '')
  if (d.length === 11) return `(${d.slice(0, 2)}) ${d.slice(2, 7)}-${d.slice(7)}`
  if (d.length === 10) return `(${d.slice(0, 2)}) ${d.slice(2, 6)}-${d.slice(6)}`
  return phone
}

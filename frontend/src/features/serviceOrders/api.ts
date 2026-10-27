import { http, type Paginated, type Wrapped } from '../../shared/lib/http'
import type { ServiceOrderStatus } from '../../shared/lib/status'
import type { Item, ItemInput } from '../items/types'
import type { VehicleSummary } from '../vehicles/api'

export type ServiceOrderItem = Item & { quote_item_id: string | null; done_at: string | null; done_by: string | null }

export type ServiceOrder = {
  id: string
  number: number
  origin: 'from_quote' | 'direct'
  quote_id: string | null
  customer_id: string
  vehicle_id: string
  customer?: { id: string; name: string }
  vehicle?: VehicleSummary
  opened_by: string
  status: ServiceOrderStatus
  mileage_in: number | null
  mileage_out: number | null
  notes: string | null
  total_cents: number
  closed_at: string | null
  canceled_at: string | null
  created_at: string
  items?: ServiceOrderItem[]
}

export type ServiceOrderInput = { customer_id: string; vehicle_id: string; mileage_in?: number | null; notes?: string; items: ItemInput[] }

export type ServiceOrderFilter = { status?: ServiceOrderStatus; vehicleId?: string; quoteId?: string }

export const serviceOrdersApi = {
  list: ({ status, vehicleId, quoteId }: ServiceOrderFilter = {}) => {
    const params = new URLSearchParams()
    if (status) params.set('status', status)
    if (vehicleId) params.set('vehicle_id', vehicleId)
    if (quoteId) params.set('quote_id', quoteId)
    const qs = params.toString()
    return http.get<Paginated<ServiceOrder>>(`/api/service-orders${qs ? `?${qs}` : ''}`)
  },
  get: async (id: string) => (await http.get<Wrapped<ServiceOrder>>(`/api/service-orders/${id}`)).data,
  create: async (input: ServiceOrderInput) => (await http.post<Wrapped<ServiceOrder>>('/api/service-orders', input)).data,
  addItem: async (id: string, item: ItemInput) => (await http.post<Wrapped<ServiceOrder>>(`/api/service-orders/${id}/items`, item)).data,
  markDone: async (id: string, itemId: string) => (await http.post<Wrapped<ServiceOrder>>(`/api/service-orders/${id}/items/${itemId}/done`)).data,
  transition: async (id: string, status: ServiceOrderStatus) => (await http.post<Wrapped<ServiceOrder>>(`/api/service-orders/${id}/transition`, { status })).data,
  close: async (id: string, mileageOut: number) => (await http.post<Wrapped<ServiceOrder>>(`/api/service-orders/${id}/close`, { mileage_out: mileageOut })).data,
}

import { http, type Wrapped } from '../../shared/lib/http'

export type Vehicle = {
  id: string
  customer_id: string
  plate: string
  vin: string | null
  brand: string | null
  model: string | null
  year: number | null
}

/** Resumo que vem junto de OS e orçamento. */
export type VehicleSummary = { id: string; plate: string; brand: string | null; model: string | null }

export type VehicleInput = { customer_id: string; plate: string; brand?: string; model?: string; year?: number }

export const vehiclesApi = {
  byCustomer: async (customerId: string) => (await http.get<Wrapped<Vehicle[]>>(`/api/customers/${customerId}/vehicles`)).data,
  get: async (id: string) => (await http.get<Wrapped<Vehicle>>(`/api/vehicles/${id}`)).data,
  create: async (input: VehicleInput) => (await http.post<Wrapped<Vehicle>>('/api/vehicles', input)).data,
}

export function vehicleName(v: Pick<Vehicle, 'brand' | 'model'> | null | undefined): string {
  return [v?.brand, v?.model].filter(Boolean).join(' ') || 'Veículo'
}

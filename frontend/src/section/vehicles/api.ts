import { ENDPOINTS } from '../../routes/paths'
import { http, type Wrapped } from '../../shared/lib/http'

const endpoints = ENDPOINTS()

export type Vehicle = {
  id: string
  customer_id: string
  plate: string
  vin: string | null
  brand: string | null
  model: string | null
  mileage: number | null
  year: number | null
}

/** Resumo que vem junto de OS e orçamento. */
export type VehicleSummary = { id: string; plate: string; brand: string | null; model: string | null }

export type VehicleInput = { customer_id: string; plate: string; brand?: string | null; model?: string | null; year?: number | null; mileage?: number | null }

export const vehiclesApi = {
  update: async (id: string, input: Omit<VehicleInput, 'customer_id'>) => (await http.patch<Wrapped<Vehicle>>(endpoints.Vehicles.update(id), input)).data,
  byCustomer: async (customerId: string) => (await http.get<Wrapped<Vehicle[]>>(endpoints.Clients.vehicles(customerId))).data,
  get: async (id: string) => (await http.get<Wrapped<Vehicle>>(endpoints.Vehicles.details(id))).data,
  create: async (input: VehicleInput) => (await http.post<Wrapped<Vehicle>>(endpoints.Vehicles.create, input)).data,
}

export function vehicleName(v: Pick<Vehicle, 'brand' | 'model'> | null | undefined): string {
  return [v?.brand, v?.model].filter(Boolean).join(' ') || 'Veículo'
}

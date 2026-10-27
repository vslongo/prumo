import { useQuery } from '@tanstack/react-query'
import { http, type Wrapped } from '../../shared/lib/http'

export type FieldType = 'text' | 'number' | 'select' | 'boolean' | 'date'

export type FieldSchema = {
  key: string
  label: string
  type: FieldType
  required: boolean
  options?: string[]
  unit?: string | null
}

export type ServiceType = {
  id: string
  name: string
  fields_schema: FieldSchema[]
  default_price_cents: number
  is_system_default: boolean
}

export type ServiceTypeInput = Omit<ServiceType, 'id' | 'is_system_default'>

export const serviceTypesApi = {
  list: async () => (await http.get<Wrapped<ServiceType[]>>('/api/service-types')).data,
  create: async (input: ServiceTypeInput) => (await http.post<Wrapped<ServiceType>>('/api/service-types', input)).data,
}

export function useServiceTypes() {
  return useQuery({ queryKey: ['service-types'], queryFn: serviceTypesApi.list, staleTime: 5 * 60_000 })
}

export const fieldTypeLabel: Record<FieldType, string> = {
  text: 'Texto',
  number: 'Número',
  select: 'Lista de opções',
  boolean: 'Sim/não',
  date: 'Data',
}

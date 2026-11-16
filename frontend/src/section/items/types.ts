import type { FieldSchema } from '../serviceTypes/api'

export type ItemKind = 'service' | 'product'

/** Formato de item igual para orçamento e OS (backend: Shared/Support/ItemRules). */
export type ItemInput = {
  kind: ItemKind
  description: string
  service_type_id: string | null
  technician_id: string
  quantity: string
  unit_price_cents: number
  fields: Record<string, unknown>
}

export type Item = ItemInput & {
  id: string
  position: number
  total_cents: number
  fields_schema_snapshot: FieldSchema[]
}

export const itemKindLabel: Record<ItemKind, string> = { service: 'Serviço', product: 'Peça/produto' }

/** Foto de item (orçamento ou OS). URLs assinadas e curtas: a lista é buscada de novo antes de vencer. */
export type ItemPhoto = {
  id: string
  item_id: string
  url: string
  thumb_url: string
  created_at: string
  /** Na OS: veio do item do orçamento de origem (não sai por aqui). */
  from_quote?: boolean
}

import { Package, Wrench } from 'lucide-react'
import type { ReactNode } from 'react'
import { formatMoney, formatQuantity } from '../../../shared/lib/format'
import type { User } from '../../auth/api'
import type { FieldSchema } from '../../serviceTypes/api'
import type { ItemInput } from '../types'

type Displayable = ItemInput & { id?: string; total_cents?: number; fields_schema_snapshot?: FieldSchema[] }

function detailText(item: Displayable, schema: FieldSchema[]): string {
  return schema
    .filter((f) => item.fields[f.key] !== undefined && item.fields[f.key] !== '')
    .map((f) => {
      const v = item.fields[f.key]
      const value = typeof v === 'boolean' ? (v ? 'sim' : 'não') : String(v)
      return `${f.label}: ${value}${f.unit ? ` ${f.unit}` : ''}`
    })
    .join(' · ')
}

type Props<T extends Displayable> = {
  items: T[]
  users: User[]
  schemaFor?: (item: T) => FieldSchema[]
  actions?: (item: T, index: number) => ReactNode
  aside?: (item: T) => ReactNode
}

/** Itens de orçamento/OS: o que é, quem faz, quanto custa. */
export function ItemList<T extends Displayable>({ items, users, schemaFor, actions, aside }: Props<T>) {
  const technician = (id: string) => users.find((u) => u.id === id)?.name ?? 'Técnico'

  return (
    <ul className="divide-y divide-border">
      {items.map((item, index) => {
        const total = item.total_cents ?? Math.round(item.unit_price_cents * Number(item.quantity))
        const details = detailText(item, item.fields_schema_snapshot ?? schemaFor?.(item) ?? [])
        const Icon = item.kind === 'service' ? Wrench : Package
        return (
          <li key={item.id ?? index} className="flex gap-3 py-3 first:pt-0 last:pb-0">
            {aside ? aside(item) : <Icon size={20} className="mt-0.5 shrink-0 text-muted" aria-hidden />}
            <div className="min-w-0 flex-1">
              <div className="flex items-start justify-between gap-3">
                <p className="font-semibold">{item.description}</p>
                <p className="num shrink-0 font-semibold">{formatMoney(total)}</p>
              </div>
              <p className="num text-sm text-muted">
                {formatQuantity(item.quantity)} × {formatMoney(item.unit_price_cents)} · {technician(item.technician_id)}
              </p>
              {details ? <p className="mt-1 text-sm">{details}</p> : null}
              {actions ? <div className="mt-2 flex flex-wrap gap-2">{actions(item, index)}</div> : null}
            </div>
          </li>
        )
      })}
    </ul>
  )
}

export function ItemsTotal({ cents, label = 'Total' }: { cents: number; label?: string }) {
  return (
    <div className="mt-4 flex items-baseline justify-between border-t border-border pt-4">
      <span className="text-muted">{label}</span>
      <span className="num font-display text-2xl font-bold">{formatMoney(cents)}</span>
    </div>
  )
}

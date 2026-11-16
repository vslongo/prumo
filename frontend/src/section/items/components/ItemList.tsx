import Box from '@mui/material/Box'
import Stack from '@mui/material/Stack'
import Typography from '@mui/material/Typography'
import { Package, Wrench } from 'lucide-react'
import type { ReactNode } from 'react'
import { formatMoney, formatQuantity } from '../../../shared/lib/format'
import { muted } from '../../../shared/ui/styles'
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
  /** Abaixo das ações (ex.: fotos do item). */
  extra?: (item: T) => ReactNode
}

/** Itens de orçamento/OS: o que é, quem faz, quanto custa. */
export function ItemList<T extends Displayable>({ items, users, schemaFor, actions, aside, extra }: Props<T>) {
  const technician = (id: string) => users.find((u) => u.id === id)?.name ?? 'Técnico'

  return (
    <Box component="ul" sx={{ m: 0, p: 0, listStyle: 'none', '& > li + li': { borderTop: 1, borderColor: 'divider' }, '& > li': { py: 1.5 }, '& > li:first-of-type': { pt: 0 }, '& > li:last-of-type': { pb: 0 } }}>
      {items.map((item, index) => {
        const total = item.total_cents ?? Math.round(item.unit_price_cents * Number(item.quantity))
        const details = detailText(item, item.fields_schema_snapshot ?? schemaFor?.(item) ?? [])
        const Icon = item.kind === 'service' ? Wrench : Package
        return (
          <Stack component="li" direction="row" key={item.id ?? index} sx={{ gap: 1.5 }}>
            {aside ? aside(item) : <Box component={Icon} size={20} aria-hidden sx={{ mt: 0.25, flexShrink: 0, ...muted }} />}
            <Box sx={{ minWidth: 0, flex: 1 }}>
              <Stack direction="row" sx={{ alignItems: 'flex-start', justifyContent: 'space-between', gap: 1.5 }}>
                <Typography sx={{ fontWeight: 600 }}>{item.description}</Typography>
                <Typography className="num" sx={{ flexShrink: 0, fontWeight: 600 }}>
                  {formatMoney(total)}
                </Typography>
              </Stack>
              <Typography variant="body2" className="num" sx={muted}>
                {formatQuantity(item.quantity)} × {formatMoney(item.unit_price_cents)} · {technician(item.technician_id)}
              </Typography>
              {details ? (
                <Typography variant="body2" sx={{ mt: 0.5 }}>
                  {details}
                </Typography>
              ) : null}
              {actions ? <Stack direction="row" sx={{ mt: 1, flexWrap: 'wrap', gap: 1 }}>{actions(item, index)}</Stack> : null}
              {extra?.(item)}
            </Box>
          </Stack>
        )
      })}
    </Box>
  )
}

export function ItemsTotal({ cents, label = 'Total' }: { cents: number; label?: string }) {
  return (
    <Stack direction="row" sx={{ mt: 2, alignItems: 'baseline', justifyContent: 'space-between', borderTop: 1, borderColor: 'divider', pt: 2 }}>
      <Typography sx={muted}>{label}</Typography>
      <Typography className="num" sx={{ fontFamily: 'var(--font-display)', fontSize: 24, fontWeight: 700 }}>
        {formatMoney(cents)}
      </Typography>
    </Stack>
  )
}

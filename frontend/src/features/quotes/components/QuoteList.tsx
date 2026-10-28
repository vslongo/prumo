import Box from '@mui/material/Box'
import { formatMoney, formatPlate, formatWhen } from '../../../shared/lib/format'
import { RecordList } from '../../../shared/ui/RecordList'
import { QuoteStatusBadge } from '../../../shared/ui/StatusBadge'
import { muted } from '../../../shared/ui/styles'
import { vehicleName } from '../../vehicles/api'
import type { Quote } from '../api'

export function QuoteList({ quotes, compact = false }: { quotes: Quote[]; compact?: boolean }) {
  return (
    <RecordList
      rows={quotes}
      compact={compact}
      href={(q) => `/orcamentos/${q.id}`}
      card={(q) => ({
        title: q.vehicle ? formatPlate(q.vehicle.plate) : 'Orçamento',
        badge: <QuoteStatusBadge status={q.status} />,
        middle: `${vehicleName(q.vehicle)} · ${q.customer?.name ?? ''}`,
        date: formatWhen(q.sent_at ?? q.created_at),
        total: formatMoney(q.total_cents),
      })}
      columns={[
        {
          header: 'Veículo',
          cell: (q) => (
            <>
              <Box component="span" className="num" sx={{ fontWeight: 600 }}>
                {q.vehicle ? formatPlate(q.vehicle.plate) : '—'}
              </Box>
              <Box component="span" sx={{ ml: 1, ...muted }}>
                {vehicleName(q.vehicle)}
              </Box>
            </>
          ),
        },
        { header: 'Cliente', cell: (q) => q.customer?.name },
        { header: 'Status', cell: (q) => <QuoteStatusBadge status={q.status} /> },
        { header: 'Atualizado', nowrap: true, cell: (q) => <Box component="span" sx={muted}>{formatWhen(q.decided_at ?? q.sent_at ?? q.created_at)}</Box> },
        { header: 'Total', align: 'right', cell: (q) => formatMoney(q.total_cents) },
      ]}
    />
  )
}

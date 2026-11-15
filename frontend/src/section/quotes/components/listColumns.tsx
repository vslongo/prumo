import Box from '@mui/material/Box'
import { formatMoney, formatPlate, formatWhen } from '../../../shared/lib/format'
import type { ListItemsColumn } from '../../../components/ListItems'
import { QuoteStatusBadge } from '../../../shared/ui/StatusBadge'
import { muted } from '../../../shared/ui/styles'
import { vehicleName } from '../../vehicles/api'
import type { Quote } from '../api'

export const quoteColumns = [
  {
    id: 'vehicle', header: 'Veículo',
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
  { id: 'customer', header: 'Cliente', cell: (q) => q.customer?.name },
  { id: 'status', header: 'Status', cell: (q) => <QuoteStatusBadge status={q.status} /> },
  { id: 'updatedAt', header: 'Atualizado', nowrap: true, cell: (q) => <Box component="span" sx={muted}>{formatWhen(q.decided_at ?? q.sent_at ?? q.created_at)}</Box> },
  { id: 'total', header: 'Total', align: 'right', cell: (q) => formatMoney(q.total_cents) },
] satisfies ListItemsColumn<Quote>[]

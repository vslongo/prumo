import Box from '@mui/material/Box'
import { formatMoney, formatPlate, formatWhen } from '../../../shared/lib/format'
import type { ListItemsColumn } from '../../../components/ListItems'
import { ServiceOrderStatusBadge } from '../../../shared/ui/StatusBadge'
import { muted } from '../../../shared/ui/styles'
import { vehicleName } from '../../vehicles/api'
import type { ServiceOrder } from '../api'

export const serviceOrderColumns = [
  { id: 'number', header: 'OS', cell: (o) => <Box component="span" className="num" sx={{ fontWeight: 600 }}>{o.number}</Box> },
  {
    id: 'vehicle', header: 'Veículo',
    cell: (o) => (
      <>
        <Box component="span" className="num" sx={{ fontWeight: 600 }}>
          {o.vehicle ? formatPlate(o.vehicle.plate) : ''}
        </Box>
        <Box component="span" sx={{ ml: 1, ...muted }}>
          {vehicleName(o.vehicle)}
        </Box>
      </>
    ),
  },
  { id: 'customer', header: 'Cliente', cell: (o) => o.customer?.name },
  { id: 'status', header: 'Status', cell: (o) => <ServiceOrderStatusBadge status={o.status} /> },
  { id: 'createdAt', header: 'Aberta', nowrap: true, cell: (o) => <Box component="span" sx={muted}>{formatWhen(o.created_at)}</Box> },
  { id: 'total', header: 'Total', align: 'right', cell: (o) => formatMoney(o.total_cents) },
] satisfies ListItemsColumn<ServiceOrder>[]

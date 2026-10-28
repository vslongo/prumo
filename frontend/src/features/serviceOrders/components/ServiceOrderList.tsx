import Box from '@mui/material/Box'
import { formatMoney, formatPlate, formatWhen } from '../../../shared/lib/format'
import { RecordList } from '../../../shared/ui/RecordList'
import { ServiceOrderStatusBadge } from '../../../shared/ui/StatusBadge'
import { muted } from '../../../shared/ui/styles'
import { vehicleName } from '../../vehicles/api'
import type { ServiceOrder } from '../api'

export function ServiceOrderList({ orders, compact = false }: { orders: ServiceOrder[]; compact?: boolean }) {
  return (
    <RecordList
      rows={orders}
      compact={compact}
      href={(o) => `/os/${o.id}`}
      card={(o) => ({
        title: `OS ${o.number} · ${o.vehicle ? formatPlate(o.vehicle.plate) : ''}`,
        badge: <ServiceOrderStatusBadge status={o.status} />,
        middle: `${vehicleName(o.vehicle)} · ${o.customer?.name ?? ''}`,
        date: formatWhen(o.created_at),
        total: formatMoney(o.total_cents),
      })}
      columns={[
        { header: 'OS', cell: (o) => <Box component="span" className="num" sx={{ fontWeight: 600 }}>{o.number}</Box> },
        {
          header: 'Veículo',
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
        { header: 'Cliente', cell: (o) => o.customer?.name },
        { header: 'Status', cell: (o) => <ServiceOrderStatusBadge status={o.status} /> },
        { header: 'Aberta', nowrap: true, cell: (o) => <Box component="span" sx={muted}>{formatWhen(o.created_at)}</Box> },
        { header: 'Total', align: 'right', cell: (o) => formatMoney(o.total_cents) },
      ]}
    />
  )
}

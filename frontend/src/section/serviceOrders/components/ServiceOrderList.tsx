import { formatMoney, formatPlate, formatWhen } from '../../../shared/lib/format'
import { RecordList } from '../../../shared/ui/RecordList'
import { ServiceOrderStatusBadge } from '../../../shared/ui/StatusBadge'
import { vehicleName } from '../../vehicles/api'
import type { ServiceOrder } from '../api'
import { serviceOrderColumns } from './listColumns'

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
      columns={serviceOrderColumns}
    />
  )
}

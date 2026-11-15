import { formatMoney, formatPlate, formatWhen } from '../../../shared/lib/format'
import { RecordList } from '../../../shared/ui/RecordList'
import { QuoteStatusBadge } from '../../../shared/ui/StatusBadge'
import { vehicleName } from '../../vehicles/api'
import type { Quote } from '../api'
import { quoteColumns } from './listColumns'

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
      columns={quoteColumns}
    />
  )
}

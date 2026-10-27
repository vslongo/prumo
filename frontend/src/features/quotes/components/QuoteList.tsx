import { ChevronRight } from 'lucide-react'
import { Link } from 'react-router'
import { formatMoney, formatPlate, formatWhen } from '../../../shared/lib/format'
import { QuoteStatusBadge } from '../../../shared/ui/StatusBadge'
import { vehicleName } from '../../vehicles/api'
import type { Quote } from '../api'

export function QuoteList({ quotes, compact = false }: { quotes: Quote[]; compact?: boolean }) {
  return (
    <>
      <ul className={`flex flex-col gap-2 ${compact ? '' : 'lg:hidden'}`}>
        {quotes.map((q) => (
          <li key={q.id}>
            <Link to={`/orcamentos/${q.id}`} className="flex min-h-[76px] flex-col gap-1 rounded-lg border border-border bg-surface px-4 py-3 active:bg-surface-2">
              <div className="flex items-center justify-between gap-3">
                <span className="num font-semibold">{q.vehicle ? formatPlate(q.vehicle.plate) : 'Orçamento'}</span>
                <QuoteStatusBadge status={q.status} />
              </div>
              <div className="truncate text-sm text-muted">
                {vehicleName(q.vehicle)} · {q.customer?.name}
              </div>
              <div className="flex items-center justify-between text-sm">
                <span className="text-muted">{formatWhen(q.sent_at ?? q.created_at)}</span>
                <span className="num font-semibold">{formatMoney(q.total_cents)}</span>
              </div>
            </Link>
          </li>
        ))}
      </ul>

      <div className={`hidden overflow-hidden rounded-lg border border-border bg-surface ${compact ? '' : 'lg:block'}`}>
        <table className="w-full text-left">
          <thead className="bg-surface-2 text-sm text-muted">
            <tr>
              <th className="px-4 py-2.5 font-medium">Veículo</th>
              <th className="px-4 py-2.5 font-medium">Cliente</th>
              <th className="px-4 py-2.5 font-medium">Status</th>
              <th className="px-4 py-2.5 font-medium">Atualizado</th>
              <th className="px-4 py-2.5 text-right font-medium">Total</th>
              <th className="w-10" />
            </tr>
          </thead>
          <tbody>
            {quotes.map((q) => (
              <tr key={q.id} className="relative border-t border-border hover:bg-surface-2">
                <td className="px-4 py-3">
                  <Link to={`/orcamentos/${q.id}`} className="num font-semibold after:absolute after:inset-0">
                    {q.vehicle ? formatPlate(q.vehicle.plate) : '—'}
                  </Link>
                  <span className="ml-2 text-muted">{vehicleName(q.vehicle)}</span>
                </td>
                <td className="px-4 py-3">{q.customer?.name}</td>
                <td className="px-4 py-3">
                  <QuoteStatusBadge status={q.status} />
                </td>
                <td className="px-4 py-3 text-muted">{formatWhen(q.decided_at ?? q.sent_at ?? q.created_at)}</td>
                <td className="num px-4 py-3 text-right font-semibold">{formatMoney(q.total_cents)}</td>
                <td className="pr-3 text-muted">
                  <ChevronRight size={18} aria-hidden />
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </>
  )
}

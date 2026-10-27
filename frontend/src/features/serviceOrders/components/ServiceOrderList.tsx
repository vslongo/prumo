import { ChevronRight } from 'lucide-react'
import { Link } from 'react-router'
import { formatMoney, formatPlate, formatWhen } from '../../../shared/lib/format'
import { ServiceOrderStatusBadge } from '../../../shared/ui/StatusBadge'
import { vehicleName } from '../../vehicles/api'
import type { ServiceOrder } from '../api'

/** Mobile: linha-card tocável. Desktop: tabela (docs/brand: "Lista de OS"). */
export function ServiceOrderList({ orders, compact = false }: { orders: ServiceOrder[]; compact?: boolean }) {
  return (
    <>
      <ul className={`flex flex-col gap-2 ${compact ? '' : 'lg:hidden'}`}>
        {orders.map((o) => (
          <li key={o.id}>
            <Link to={`/os/${o.id}`} className="flex min-h-[76px] flex-col gap-1 rounded-lg border border-border bg-surface px-4 py-3 active:bg-surface-2">
              <div className="flex items-center justify-between gap-3">
                <span className="num font-semibold">
                  OS {o.number} · {o.vehicle ? formatPlate(o.vehicle.plate) : ''}
                </span>
                <ServiceOrderStatusBadge status={o.status} />
              </div>
              <div className="truncate text-sm text-muted">
                {vehicleName(o.vehicle)} · {o.customer?.name}
              </div>
              <div className="flex items-center justify-between text-sm">
                <span className="text-muted">{formatWhen(o.created_at)}</span>
                <span className="num font-semibold">{formatMoney(o.total_cents)}</span>
              </div>
            </Link>
          </li>
        ))}
      </ul>

      <div className={`hidden overflow-hidden rounded-lg border border-border bg-surface ${compact ? '' : 'lg:block'}`}>
        <table className="w-full text-left">
          <thead className="bg-surface-2 text-sm text-muted">
            <tr>
              <th className="px-4 py-2.5 font-medium">OS</th>
              <th className="px-4 py-2.5 font-medium">Veículo</th>
              <th className="px-4 py-2.5 font-medium">Cliente</th>
              <th className="px-4 py-2.5 font-medium">Status</th>
              <th className="px-4 py-2.5 font-medium">Aberta</th>
              <th className="px-4 py-2.5 text-right font-medium">Total</th>
              <th className="w-10" />
            </tr>
          </thead>
          <tbody>
            {orders.map((o) => (
              <tr key={o.id} className="group relative border-t border-border hover:bg-surface-2">
                <td className="num px-4 py-3 font-semibold">
                  <Link to={`/os/${o.id}`} className="after:absolute after:inset-0">
                    {o.number}
                  </Link>
                </td>
                <td className="px-4 py-3">
                  <span className="num font-semibold">{o.vehicle ? formatPlate(o.vehicle.plate) : ''}</span>
                  <span className="ml-2 text-muted">{vehicleName(o.vehicle)}</span>
                </td>
                <td className="px-4 py-3">{o.customer?.name}</td>
                <td className="px-4 py-3">
                  <ServiceOrderStatusBadge status={o.status} />
                </td>
                <td className="px-4 py-3 text-muted">{formatWhen(o.created_at)}</td>
                <td className="num px-4 py-3 text-right font-semibold">{formatMoney(o.total_cents)}</td>
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

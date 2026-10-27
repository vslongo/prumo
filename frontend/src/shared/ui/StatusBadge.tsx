import { quoteStatusLabel, serviceOrderStatusLabel, type QuoteStatus, type ServiceOrderStatus } from '../lib/status'

function Badge({ family, status, label }: { family: 'os' | 'quote'; status: string; label: string }) {
  return (
    <span
      className="inline-flex items-center gap-1.5 rounded-sm px-2 py-0.5 text-[13px] font-semibold whitespace-nowrap"
      style={{ color: `var(--status-${family}-${status}-fg)`, background: `var(--status-${family}-${status}-bg)` }}
    >
      <span className="size-1.5 rounded-full bg-current" aria-hidden />
      {label}
    </span>
  )
}

export function ServiceOrderStatusBadge({ status }: { status: ServiceOrderStatus }) {
  return <Badge family="os" status={status} label={serviceOrderStatusLabel[status]} />
}

export function QuoteStatusBadge({ status }: { status: QuoteStatus }) {
  return <Badge family="quote" status={status} label={quoteStatusLabel[status]} />
}

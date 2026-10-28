import Box from '@mui/material/Box'
import { quoteStatusLabel, serviceOrderStatusLabel, type QuoteStatus, type ServiceOrderStatus } from '../lib/status'

function Badge({ family, status, label }: { family: 'os' | 'quote'; status: string; label: string }) {
  return (
    <Box
      component="span"
      style={{ color: `var(--status-${family}-${status}-fg)`, background: `var(--status-${family}-${status}-bg)` }} sx={{ display: 'inline-flex', alignItems: 'center', gap: 0.75, borderRadius: 0.5, px: 1, py: 0.25, fontSize: 13, fontWeight: 600, whiteSpace: 'nowrap' }}
    >
      <Box component="span" aria-hidden sx={{ width: 6, height: 6, borderRadius: '50%', bgcolor: 'currentColor' }} />
      {label}
    </Box>
  )
}

export function ServiceOrderStatusBadge({ status }: { status: ServiceOrderStatus }) {
  return <Badge family="os" status={status} label={serviceOrderStatusLabel[status]} />
}

export function QuoteStatusBadge({ status }: { status: QuoteStatus }) {
  return <Badge family="quote" status={status} label={quoteStatusLabel[status]} />
}

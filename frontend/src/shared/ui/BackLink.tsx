import MuiLink from '@mui/material/Link'
import { ChevronLeft } from 'lucide-react'

export function BackLink({ to, label }: { to: string; label: string }) {
  return (
    <MuiLink
      href={to}
      underline="none"
      sx={{ mb: 1, ml: -0.5, display: 'inline-flex', height: 36, alignItems: 'center', gap: 0.5, fontSize: 14, fontWeight: 500, color: 'text.secondary', '&:hover': { color: 'text.primary' } }}
    >
      <ChevronLeft size={18} aria-hidden />
      {label}
    </MuiLink>
  )
}

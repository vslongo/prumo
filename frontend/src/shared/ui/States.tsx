import Alert from '@mui/material/Alert'
import Box from '@mui/material/Box'
import Paper from '@mui/material/Paper'
import Skeleton from '@mui/material/Skeleton'
import Stack from '@mui/material/Stack'
import type { SxProps, Theme } from '@mui/material/styles'
import Typography from '@mui/material/Typography'
import { CircleAlert, type LucideIcon } from 'lucide-react'
import type { ReactNode } from 'react'
import { Button } from './Button'

export function Card({ children, sx }: { children: ReactNode; sx?: SxProps<Theme> }) {
  return (
    <Paper component="section" sx={[{ p: 2 }, ...(Array.isArray(sx) ? sx : [sx])]}>
      {children}
    </Paper>
  )
}

export function CardTitle({ children, action }: { children: ReactNode; action?: ReactNode }) {
  return (
    <Stack direction="row" sx={{ alignItems: 'center', justifyContent: 'space-between', gap: 1.5, mb: 1.5 }}>
      <Typography variant="h2">{children}</Typography>
      {action}
    </Stack>
  )
}

export function EmptyState({ icon: Icon, title, text, action }: { icon: LucideIcon; title: string; text: string; action?: ReactNode }) {
  return (
    <Stack sx={{ alignItems: 'center', gap: 1, border: '1px dashed var(--color-border-strong)', borderRadius: 1.5, px: 3, py: 5, textAlign: 'center' }}>
      <Box component={Icon} size={32} aria-hidden sx={{ color: 'text.secondary' }} />
      <Typography sx={{ fontFamily: 'var(--font-display)', fontSize: 18, fontWeight: 600 }}>{title}</Typography>
      <Typography color="text.secondary" sx={{ maxWidth: 384 }}>
        {text}
      </Typography>
      {action ? <Box sx={{ mt: 1.5 }}>{action}</Box> : null}
    </Stack>
  )
}

export function ErrorBox({ message, onRetry }: { message: string; onRetry?: () => void }) {
  return (
    <Alert
      severity="error"
      icon={<CircleAlert size={20} aria-hidden />}
      sx={{ bgcolor: 'error.light', color: 'error.main', alignItems: { sm: 'center' }, flexWrap: 'wrap', '& .MuiAlert-icon': { color: 'inherit' } }}
      action={
        onRetry ? (
          <Button variant="secondary" onClick={onRetry}>
            Tentar de novo
          </Button>
        ) : undefined
      }
    >
      {message}
    </Alert>
  )
}

export function ListSkeleton({ rows = 4 }: { rows?: number }) {
  return (
    <Stack aria-label="Carregando" role="status" sx={{ gap: 1 }}>
      {Array.from({ length: rows }, (_, i) => (
        <Skeleton key={i} height={76} />
      ))}
    </Stack>
  )
}

export function PageHeader({ title, subtitle, action, back }: { title: string; subtitle?: ReactNode; action?: ReactNode; back?: ReactNode }) {
  return (
    <Stack component="header" direction={{ xs: 'column', sm: 'row' }} sx={{ alignItems: { sm: 'flex-end' }, justifyContent: 'space-between', gap: 1.5, mb: 2.5 }}>
      <Box sx={{ minWidth: 0 }}>
        {back}
        <Typography variant="h1">{title}</Typography>
        {subtitle ? (
          <Typography component="div" color="text.secondary" sx={{ mt: 0.5 }}>
            {subtitle}
          </Typography>
        ) : null}
      </Box>
      {action ? (
        <Stack direction="row" sx={{ gap: 1, flexShrink: 0 }}>
          {action}
        </Stack>
      ) : null}
    </Stack>
  )
}

/** Linha "rótulo: valor" usada nos resumos de OS e orçamento. */
export function Detail({ label, children }: { label: string; children: ReactNode }) {
  return (
    <Stack sx={{ gap: 0.25 }}>
      <Typography component="dt" variant="body2" color="text.secondary">
        {label}
      </Typography>
      <Typography component="dd" sx={{ fontWeight: 500, m: 0 }}>
        {children}
      </Typography>
    </Stack>
  )
}

/** Lista de detalhes em grade (dl). */
export function DetailGrid({ children, columns = 2 }: { children: ReactNode; columns?: 1 | 2 }) {
  return (
    <Box component="dl" sx={{ display: 'grid', gridTemplateColumns: `repeat(${columns}, 1fr)`, gap: 2, m: 0 }}>
      {children}
    </Box>
  )
}

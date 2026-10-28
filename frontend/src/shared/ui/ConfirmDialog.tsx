import Box from '@mui/material/Box'
import Dialog from '@mui/material/Dialog'
import Stack from '@mui/material/Stack'
import Typography from '@mui/material/Typography'
import type { ReactNode } from 'react'
import { Button } from './Button'

type Props = {
  open: boolean
  title: string
  text?: string
  confirmLabel: string
  danger?: boolean
  loading?: boolean
  onConfirm: () => void
  onClose: () => void
  children?: ReactNode
}

export function ConfirmDialog({ open, title, text, confirmLabel, danger, loading, onConfirm, onClose, children }: Props) {
  return (
    <Dialog open={open} onClose={onClose} aria-labelledby="confirm-title">
      <Typography id="confirm-title" variant="h3" component="h2" sx={{ fontWeight: 600 }}>
        {title}
      </Typography>
      {text ? (
        <Typography color="text.secondary" sx={{ mt: 1 }}>
          {text}
        </Typography>
      ) : null}
      {children ? <Box sx={{ mt: 2 }}>{children}</Box> : null}
      <Stack direction={{ xs: 'column-reverse', sm: 'row' }} sx={{ justifyContent: 'flex-end', gap: 1, mt: 3 }}>
        <Button variant="secondary" onClick={onClose}>
          Voltar
        </Button>
        <Button variant={danger ? 'danger' : 'primary'} onClick={onConfirm} loading={loading}>
          {confirmLabel}
        </Button>
      </Stack>
    </Dialog>
  )
}

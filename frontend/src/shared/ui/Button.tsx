import MuiButton, { type ButtonProps as MuiButtonProps } from '@mui/material/Button'
import CircularProgress from '@mui/material/CircularProgress'
import type { ReactNode } from 'react'
import { LinkBehavior } from './LinkBehavior'

type Variant = 'primary' | 'secondary' | 'danger' | 'ghost'

/** Variantes da marca → props do MUI. */
function variantProps(variant: Variant = 'primary'): Pick<MuiButtonProps, 'variant' | 'color' | 'sx'> {
  switch (variant) {
    case 'secondary':
      return { variant: 'outlined', color: 'inherit', sx: { bgcolor: 'background.paper', borderColor: 'var(--color-border-strong)', '&:hover': { bgcolor: 'action.hover' } } }
    case 'danger':
      return { variant: 'contained', color: 'error' }
    case 'ghost':
      return { variant: 'text', color: 'primary' }
    default:
      return { variant: 'contained', color: 'primary' }
  }
}

type ButtonProps = Omit<MuiButtonProps, 'variant' | 'color'> & {
  /** Com `href` externo (ex.: wa.me). */
  target?: string
  rel?: string
  variant?: Variant
  /** Ação destrutiva com cara de link (ex.: "Remover"). */
  tone?: 'danger'
  full?: boolean
  loading?: boolean
  loadingText?: string
  icon?: ReactNode
}

export function Button({ variant, tone, full, loading, loadingText, icon, children, disabled, sx, ...rest }: ButtonProps) {
  const v = variantProps(variant)
  return (
    <MuiButton
      {...v}
      color={tone === 'danger' ? 'error' : v.color}
      fullWidth={full}
      disabled={disabled || loading}
      aria-busy={loading || undefined}
      startIcon={loading ? <CircularProgress size={16} color="inherit" /> : icon}
      sx={[v.sx ?? {}, ...(Array.isArray(sx) ? sx : [sx])]}
      {...rest}
    >
      {loading && loadingText ? loadingText : children}
    </MuiButton>
  )
}

/** Botão de navegação ligado explicitamente ao Next.js. */
export function ButtonLink({ to, ...rest }: Omit<ButtonProps, 'href' | 'loading' | 'loadingText'> & { to: string }) {
  return <Button component={LinkBehavior} href={to} {...rest} />
}

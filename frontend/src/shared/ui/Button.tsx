import { Loader2 } from 'lucide-react'
import type { ButtonHTMLAttributes, ReactNode } from 'react'
import { Link, type LinkProps } from 'react-router'

type Variant = 'primary' | 'secondary' | 'danger' | 'ghost'

const base =
  'inline-flex items-center justify-center gap-2 rounded-md font-semibold transition-colors select-none disabled:opacity-50 disabled:cursor-not-allowed h-12 lg:h-10 px-4 text-[15px] lg:text-base whitespace-nowrap'

const variants: Record<Variant, string> = {
  primary: 'bg-brand text-on-brand hover:bg-brand-strong',
  secondary: 'bg-surface text-text border border-border-strong hover:bg-surface-2',
  danger: 'bg-danger text-white dark:text-bg hover:opacity-90',
  ghost: 'text-brand underline-offset-4 hover:underline px-2',
}

export function buttonClass(variant: Variant = 'primary', full = false, extra = ''): string {
  return [base, variants[variant], full ? 'w-full' : '', extra].join(' ')
}

type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & {
  variant?: Variant
  full?: boolean
  loading?: boolean
  loadingText?: string
  icon?: ReactNode
}

export function Button({ variant = 'primary', full, loading, loadingText, icon, children, className = '', disabled, type = 'button', ...rest }: ButtonProps) {
  return (
    <button type={type} className={buttonClass(variant, full, className)} disabled={disabled || loading} aria-busy={loading || undefined} {...rest}>
      {loading ? <Loader2 size={16} className="animate-spin" aria-hidden /> : icon}
      {loading && loadingText ? loadingText : children}
    </button>
  )
}

export function ButtonLink({ variant = 'primary', full, className = '', icon, children, ...rest }: LinkProps & { variant?: Variant; full?: boolean; icon?: ReactNode }) {
  return (
    <Link className={buttonClass(variant, full, className)} {...rest}>
      {icon}
      {children}
    </Link>
  )
}

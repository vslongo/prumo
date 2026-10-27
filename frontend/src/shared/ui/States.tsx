import { CircleAlert, type LucideIcon } from 'lucide-react'
import type { ReactNode } from 'react'
import { Button } from './Button'

export function Card({ children, className = '' }: { children: ReactNode; className?: string }) {
  return <section className={`rounded-lg border border-border bg-surface p-4 ${className}`}>{children}</section>
}

export function CardTitle({ children, action }: { children: ReactNode; action?: ReactNode }) {
  return (
    <div className="mb-3 flex items-center justify-between gap-3">
      <h2 className="text-lg font-semibold">{children}</h2>
      {action}
    </div>
  )
}

export function EmptyState({ icon: Icon, title, text, action }: { icon: LucideIcon; title: string; text: string; action?: ReactNode }) {
  return (
    <div className="flex flex-col items-center gap-2 rounded-lg border border-dashed border-border-strong px-6 py-10 text-center">
      <Icon size={32} className="text-muted" aria-hidden />
      <p className="font-display text-lg font-semibold">{title}</p>
      <p className="max-w-sm text-muted">{text}</p>
      {action ? <div className="mt-3">{action}</div> : null}
    </div>
  )
}

export function ErrorBox({ message, onRetry }: { message: string; onRetry?: () => void }) {
  return (
    <div role="alert" className="flex flex-col gap-3 rounded-md bg-danger-soft p-3 text-danger sm:flex-row sm:items-center sm:justify-between">
      <p className="flex items-start gap-2">
        <CircleAlert size={20} className="mt-0.5 shrink-0" aria-hidden />
        {message}
      </p>
      {onRetry ? (
        <Button variant="secondary" onClick={onRetry}>
          Tentar de novo
        </Button>
      ) : null}
    </div>
  )
}

export function Skeleton({ className = '' }: { className?: string }) {
  return <div className={`animate-pulse rounded-md bg-surface-2 ${className}`} aria-hidden />
}

export function ListSkeleton({ rows = 4 }: { rows?: number }) {
  return (
    <div className="flex flex-col gap-2" aria-label="Carregando" role="status">
      {Array.from({ length: rows }, (_, i) => (
        <Skeleton key={i} className="h-[76px]" />
      ))}
    </div>
  )
}

export function PageHeader({ title, subtitle, action, back }: { title: string; subtitle?: ReactNode; action?: ReactNode; back?: ReactNode }) {
  return (
    <header className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div className="min-w-0">
        {back}
        <h1 className="text-2xl font-bold leading-tight lg:text-[28px]">{title}</h1>
        {subtitle ? <div className="mt-1 text-muted">{subtitle}</div> : null}
      </div>
      {action ? <div className="flex shrink-0 gap-2">{action}</div> : null}
    </header>
  )
}

/** Linha "rótulo: valor" usada nos resumos de OS e orçamento. */
export function Detail({ label, children }: { label: string; children: ReactNode }) {
  return (
    <div className="flex flex-col gap-0.5">
      <dt className="text-sm text-muted">{label}</dt>
      <dd className="font-medium">{children}</dd>
    </div>
  )
}

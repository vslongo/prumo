import { CircleAlert } from 'lucide-react'
import { useId, type InputHTMLAttributes, type ReactNode, type Ref, type SelectHTMLAttributes, type TextareaHTMLAttributes } from 'react'

const control =
  'w-full rounded-md bg-surface border px-3 text-base text-text placeholder:text-muted outline-none focus:ring-2 focus:ring-accent focus:border-transparent disabled:opacity-60'

function borderFor(error?: string) {
  return error ? 'border-danger' : 'border-border-strong'
}

type FieldProps = { label: string; hint?: string; error?: string; children: (id: string, describedBy: string | undefined) => ReactNode; className?: string }

/** Rótulo sempre visível acima; ajuda e erro abaixo (docs/brand: "Campo"). */
export function Field({ label, hint, error, children, className = '' }: FieldProps) {
  const id = useId()
  const describedBy = error ? `${id}-error` : hint ? `${id}-hint` : undefined

  return (
    <div className={`flex flex-col gap-1.5 ${className}`}>
      <label htmlFor={id} className="text-sm font-medium">
        {label}
      </label>
      {children(id, describedBy)}
      {error ? (
        <p id={`${id}-error`} className="flex items-start gap-1.5 text-sm text-danger" role="alert">
          <CircleAlert size={16} className="mt-0.5 shrink-0" aria-hidden />
          {error}
        </p>
      ) : hint ? (
        <p id={`${id}-hint`} className="text-sm text-muted">
          {hint}
        </p>
      ) : null}
    </div>
  )
}

type Common = { label: string; hint?: string; error?: string; className?: string }

export function TextField({ label, hint, error, className, ref, ...rest }: Common & InputHTMLAttributes<HTMLInputElement> & { ref?: Ref<HTMLInputElement> }) {
  return (
    <Field label={label} hint={hint} error={error} className={className}>
      {(id, describedBy) => (
        <input id={id} ref={ref} aria-invalid={!!error} aria-describedby={describedBy} className={`${control} h-12 ${borderFor(error)}`} {...rest} />
      )}
    </Field>
  )
}

export function SelectField({ label, hint, error, className, children, ref, ...rest }: Common & SelectHTMLAttributes<HTMLSelectElement> & { ref?: Ref<HTMLSelectElement> }) {
  return (
    <Field label={label} hint={hint} error={error} className={className}>
      {(id, describedBy) => (
        <select id={id} ref={ref} aria-invalid={!!error} aria-describedby={describedBy} className={`${control} h-12 ${borderFor(error)}`} {...rest}>
          {children}
        </select>
      )}
    </Field>
  )
}

export function TextAreaField({ label, hint, error, className, ref, ...rest }: Common & TextareaHTMLAttributes<HTMLTextAreaElement> & { ref?: Ref<HTMLTextAreaElement> }) {
  return (
    <Field label={label} hint={hint} error={error} className={className}>
      {(id, describedBy) => (
        <textarea id={id} ref={ref} rows={3} aria-invalid={!!error} aria-describedby={describedBy} className={`${control} py-3 ${borderFor(error)}`} {...rest} />
      )}
    </Field>
  )
}

export function CheckboxField({ label, className = '', ref, ...rest }: { label: ReactNode; className?: string } & InputHTMLAttributes<HTMLInputElement> & { ref?: Ref<HTMLInputElement> }) {
  return (
    <label className={`flex min-h-11 cursor-pointer items-center gap-3 text-base ${className}`}>
      <input type="checkbox" ref={ref} className="size-5 accent-[var(--color-brand)]" {...rest} />
      <span>{label}</span>
    </label>
  )
}

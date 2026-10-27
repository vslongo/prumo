import { useEffect, useRef, type ReactNode } from 'react'
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

/** Confirmação com <dialog> nativo (foco preso e Esc de graça). */
export function ConfirmDialog({ open, title, text, confirmLabel, danger, loading, onConfirm, onClose, children }: Props) {
  const ref = useRef<HTMLDialogElement>(null)

  useEffect(() => {
    const dialog = ref.current
    if (!dialog) return
    if (open && !dialog.open) dialog.showModal?.()
    if (!open && dialog.open) dialog.close?.()
  }, [open])

  return (
    <dialog
      ref={ref}
      onClose={onClose}
      aria-labelledby="confirm-title"
      className="m-auto w-[min(92vw,440px)] rounded-lg border border-border bg-surface p-5 text-text shadow-md backdrop:bg-[rgb(17_20_19/0.5)]"
    >
      <h2 id="confirm-title" className="text-xl font-semibold">
        {title}
      </h2>
      {text ? <p className="mt-2 text-muted">{text}</p> : null}
      {children ? <div className="mt-4">{children}</div> : null}
      <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <Button variant="secondary" onClick={onClose}>
          Voltar
        </Button>
        <Button variant={danger ? 'danger' : 'primary'} onClick={onConfirm} loading={loading}>
          {confirmLabel}
        </Button>
      </div>
    </dialog>
  )
}

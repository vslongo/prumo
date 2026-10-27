import { CircleCheck } from 'lucide-react'
import { createContext, use, useCallback, useState, type ReactNode } from 'react'

type Toast = { id: number; message: string }

const ToastContext = createContext<(message: string) => void>(() => {})

/** Confirmação rápida que some em 4 s (docs/brand: só para sucesso curto, nunca modal). */
export function ToastProvider({ children }: { children: ReactNode }) {
  const [toasts, setToasts] = useState<Toast[]>([])

  const show = useCallback((message: string) => {
    const id = Date.now() + Math.random()
    setToasts((t) => [...t, { id, message }])
    setTimeout(() => setToasts((t) => t.filter((x) => x.id !== id)), 4000)
  }, [])

  return (
    <ToastContext value={show}>
      {children}
      <div
        aria-live="polite"
        className="pointer-events-none fixed inset-x-0 bottom-[calc(76px+env(safe-area-inset-bottom))] z-50 flex flex-col items-center gap-2 px-4 lg:inset-x-auto lg:top-4 lg:right-4 lg:bottom-auto lg:items-end"
      >
        {toasts.map((t) => (
          <div key={t.id} role="status" className="pointer-events-auto flex max-w-md items-center gap-2 rounded-md bg-text px-4 py-3 text-bg shadow-md">
            <CircleCheck size={20} aria-hidden />
            {t.message}
          </div>
        ))}
      </div>
    </ToastContext>
  )
}

export function useToast() {
  return use(ToastContext)
}

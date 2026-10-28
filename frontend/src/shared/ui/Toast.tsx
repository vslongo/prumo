import Snackbar from '@mui/material/Snackbar'
import SnackbarContent from '@mui/material/SnackbarContent'
import useMediaQuery from '@mui/material/useMediaQuery'
import { CircleCheck } from 'lucide-react'
import { createContext, use, useCallback, useState, type ReactNode } from 'react'

const ToastContext = createContext<(message: string) => void>(() => {})

/** Confirmação rápida que some em 4 s (docs/brand: só para sucesso curto, nunca modal). */
export function ToastProvider({ children }: { children: ReactNode }) {
  // ponytail: mostra só a última mensagem; fila de toasts se um dia vierem várias juntas.
  const [toast, setToast] = useState<{ key: number; message: string } | null>(null)

  const desktop = useMediaQuery('(min-width:1024px)')
  const show = useCallback((message: string) => setToast({ key: Date.now(), message }), [])

  return (
    <ToastContext value={show}>
      {children}
      <Snackbar
        key={toast?.key}
        open={!!toast}
        autoHideDuration={4000}
        onClose={(_, reason) => reason !== 'clickaway' && setToast(null)}
        // Mobile: acima da navegação inferior. Desktop: canto superior direito.
        anchorOrigin={desktop ? { vertical: 'top', horizontal: 'right' } : { vertical: 'bottom', horizontal: 'center' }}
        sx={desktop ? undefined : { bottom: 'calc(76px + env(safe-area-inset-bottom)) !important' }}
      >
        <SnackbarContent
          role="status"
          sx={{ bgcolor: 'text.primary', color: 'background.default', fontSize: 16 }}
          message={
            <span style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
              <CircleCheck size={20} aria-hidden />
              {toast?.message}
            </span>
          }
        />
      </Snackbar>
    </ToastContext>
  )
}

export function useToast() {
  return use(ToastContext)
}

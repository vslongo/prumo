'use client'

import { useState } from 'react'
import { AppRouterCacheProvider } from '@mui/material-nextjs/v16-appRouter'
import CssBaseline from '@mui/material/CssBaseline'
import { ThemeProvider } from '@mui/material/styles'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { ApiError } from '../shared/lib/http'
import { ToastProvider } from '../shared/ui/Toast'
import { theme } from './theme'
import { ServiceWorker } from './ServiceWorker'

const createQueryClient = () => new QueryClient({
  defaultOptions: {
    queries: {
      // Conexão ruim na oficina: tenta de novo, mas não insiste em erro de regra (4xx).
      retry: (count, error) => count < 2 && !(error instanceof ApiError && error.status >= 400 && error.status < 500),
      refetchOnWindowFocus: false,
    },
  },
})

export function Providers({ children }: { children: React.ReactNode }) {
  const [queryClient] = useState(createQueryClient)
  return (
    <AppRouterCacheProvider>
    <ThemeProvider theme={theme}>
      <CssBaseline />
      <QueryClientProvider client={queryClient}>
        <ToastProvider>
          <ServiceWorker />
          {children}
        </ToastProvider>
      </QueryClientProvider>
    </ThemeProvider>
    </AppRouterCacheProvider>
  )
}

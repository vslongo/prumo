import type { Metadata, Viewport } from 'next'
import { Suspense } from 'react'
import { Providers } from './providers'
import '../index.css'

export const metadata: Metadata = {
  title: 'Prumo', description: 'OS e orçamentos da sua oficina pelo celular.',
  manifest: '/manifest.webmanifest', icons: { icon: '/favicon.svg' },
  appleWebApp: { capable: true, title: 'Prumo' },
}
export const viewport: Viewport = { width: 'device-width', initialScale: 1, viewportFit: 'cover', themeColor: '#0e5a4f' }
const themeScript = `try { var t = localStorage.getItem('prumo-theme') || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'); document.documentElement.dataset.theme = t; } catch (e) {}`
export default function RootLayout({ children }: { children: React.ReactNode }) {
  return <html lang="pt-BR" suppressHydrationWarning><head>
    <script dangerouslySetInnerHTML={{ __html: themeScript }} />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=IBM+Plex+Sans+Condensed:wght@600;700&display=swap" rel="stylesheet" />
  </head><body><Suspense fallback={<p role="status">Carregando…</p>}><Providers>{children}</Providers></Suspense></body></html>
}

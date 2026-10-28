import react from '@vitejs/plugin-react'
import { VitePWA } from 'vite-plugin-pwa'
import { defineConfig } from 'vitest/config'

const api = process.env.API_URL ?? 'http://localhost:8000'

export default defineConfig({
  plugins: [
    react(),
    VitePWA({
      registerType: 'autoUpdate',
      manifest: {
        name: 'Prumo',
        short_name: 'Prumo',
        description: 'OS e orçamentos da sua oficina pelo celular.',
        lang: 'pt-BR',
        theme_color: '#0e5a4f',
        background_color: '#f4f3ef',
        display: 'standalone',
        icons: [{ src: '/favicon.svg', sizes: 'any', type: 'image/svg+xml', purpose: 'any' }],
      },
      workbox: { navigateFallbackDenylist: [/^\/api/, /^\/sanctum/, /^\/r2-local/] },
    }),
  ],
  server: {
    port: 5173,
    strictPort: true,
    // Mesma origem para o cookie de sessão do Sanctum; /r2-local é o "R2" de dev (URL assinada local).
    // changeOrigin: false mantém o Host do navegador, para as URLs assinadas saírem com a origem do Vite.
    proxy: {
      '/api': { target: api, changeOrigin: false },
      '/sanctum': { target: api, changeOrigin: false },
      '/r2-local': { target: api, changeOrigin: false },
    },
  },
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./src/test/setup.ts'],
    include: ['src/**/*.test.{ts,tsx}'],
  },
})

import { defineConfig, devices } from '@playwright/test'

/**
 * Testes de tela (E2E) contra a API real (docker compose: app + queue + pgsql + redis).
 * O globalSetup recria o banco de dev com a oficina demo antes de cada execução.
 */
export default defineConfig({
  testDir: './e2e',
  globalSetup: './e2e/global-setup.ts',
  fullyParallel: false,
  workers: 1,
  retries: 0,
  reporter: [['list'], ['html', { open: 'never' }]],
  timeout: 60_000,
  expect: { timeout: 20_000 },
  use: {
    baseURL: 'http://localhost:5173',
    locale: 'pt-BR',
    timezoneId: 'America/Sao_Paulo',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  projects: [
    { name: 'setup', testMatch: /auth\.setup\.ts/ },
    {
      name: 'desktop',
      dependencies: ['setup'],
      use: { ...devices['Desktop Chrome'], viewport: { width: 1366, height: 860 }, storageState: 'e2e/.auth/owner.json' },
    },
    {
      name: 'mobile',
      dependencies: ['setup'],
      use: { ...devices['Pixel 7'], storageState: 'e2e/.auth/owner.json' },
    },
  ],
  webServer: {
    command: 'npm run dev',
    url: 'http://localhost:5173/login',
    reuseExistingServer: true,
    timeout: 60_000,
  },
})

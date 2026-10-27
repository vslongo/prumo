import { expect, test } from '@playwright/test'

/** Prints das telas principais (claro e escuro) para revisão visual; ficam em test-results/screens. */
const pages = [
  { name: 'inicio', path: '/', ready: /Bom dia|Boa tarde|Boa noite/ },
  { name: 'os', path: '/os', ready: 'Ordens de serviço' },
  { name: 'orcamentos', path: '/orcamentos', ready: 'Orçamentos' },
  { name: 'novo-orcamento', path: '/orcamentos/novo', ready: 'Novo orçamento' },
  { name: 'clientes', path: '/clientes', ready: 'Clientes' },
  { name: 'configuracoes', path: '/configuracoes', ready: 'Configurações' },
]

for (const theme of ['light', 'dark'] as const) {
  test(`telas no tema ${theme === 'light' ? 'claro' : 'escuro'}`, async ({ page }, info) => {
    await page.addInitScript((t) => localStorage.setItem('prumo-theme', t), theme)
    for (const p of pages) {
      await page.goto(p.path)
      await expect(page.getByRole('heading', { name: p.ready, level: 1 })).toBeVisible()
      await page.waitForLoadState('networkidle')
      await page.screenshot({ path: `test-results/screens/${info.project.name}-${theme}-${p.name}.png`, fullPage: true })
    }
  })
}

test('tela de login', async ({ browser }, info) => {
  const ctx = await browser.newContext({ storageState: { cookies: [], origins: [] }, viewport: info.project.use.viewport ?? undefined })
  const page = await ctx.newPage()
  await page.goto('/login')
  await expect(page.getByRole('heading', { name: 'Entrar' })).toBeVisible()
  await page.screenshot({ path: `test-results/screens/${info.project.name}-login.png`, fullPage: true })
  await ctx.close()
})

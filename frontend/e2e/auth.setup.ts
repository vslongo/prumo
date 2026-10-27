import { expect, test as setup } from '@playwright/test'

setup('entra como dono da oficina demo', async ({ page }) => {
  await page.goto('/login')
  await page.getByLabel('E-mail').fill('dono@demo.test')
  await page.getByLabel('Senha').fill('password')
  await page.getByRole('button', { name: 'Entrar' }).click()
  await expect(page.getByRole('heading', { name: /Bom dia|Boa tarde|Boa noite/ })).toBeVisible({ timeout: 30_000 })
  await page.context().storageState({ path: 'e2e/.auth/owner.json' })
})

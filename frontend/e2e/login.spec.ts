import { expect, test } from '@playwright/test'

test.use({ storageState: { cookies: [], origins: [] } })

test('valida campos e recusa senha errada', async ({ page }) => {
  await page.goto('/login')
  await page.getByRole('button', { name: 'Entrar' }).click()
  await expect(page.getByText('Informe o e-mail.')).toBeVisible()
  await expect(page.getByText('Informe a senha.')).toBeVisible()

  await page.getByLabel('E-mail').fill('tecnico@demo.test')
  await page.getByLabel('Senha').fill('errada')
  await page.getByRole('button', { name: 'Entrar' }).click()
  await expect(page.getByRole('alert').filter({ hasText: 'E-mail ou senha inválidos.' })).toBeVisible()
})

test('técnico entra e não vê Configurações', async ({ page }, info) => {
  await page.goto('/os')
  await expect(page).toHaveURL(/\/login$/)
  await page.getByLabel('E-mail').fill('tecnico@demo.test')
  await page.getByLabel('Senha').fill('password')
  await page.getByRole('button', { name: 'Entrar' }).click()
  await expect(page.getByRole('heading', { name: /Rafael/ })).toBeVisible()
  await expect(page.getByRole('link', { name: 'Configurações' })).toHaveCount(0)
  if (info.project.name === 'mobile') {
    await expect(page.getByRole('navigation', { name: 'Principal' }).getByRole('link', { name: 'Orçamentos' })).toBeVisible()
  }
})

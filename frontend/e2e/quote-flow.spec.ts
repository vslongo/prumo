import { expect, test } from '@playwright/test'
import { addOilChange, addProduct, pickCustomerAndVehicle, sign } from './helpers'

test('orçamento → link → cliente aprova parcialmente e assina → OS criada sozinha', async ({ page, browser }) => {
  // Oficina monta o orçamento
  await page.goto('/orcamentos/novo')
  await pickCustomerAndVehicle(page, 'Ana Paula Ribeiro', /BRA2E19/)
  await page.getByLabel('Km atual').fill('84300')
  await addOilChange(page, 'Rafael Souza')
  await addProduct(page, 'Filtro de ar', '1', '45,90', 'Rafael Souza')
  await expect(page.getByText('R$ 125,90')).toBeVisible() // 80,00 + 45,90 no resumo
  await page.getByRole('button', { name: 'Salvar orçamento' }).click()

  await expect(page).toHaveURL(/\/orcamentos\/[0-9a-f-]{36}$/)
  await expect(page.getByText('Rascunho')).toBeVisible()
  await expect(page.getByText('Viscosidade: 5W30 · Litros: 4 L · Trocou filtro: sim')).toBeVisible()

  await page.getByRole('button', { name: 'Gerar link de aprovação' }).click()
  const link = await page.getByLabel('Link de aprovação').inputValue()
  expect(link).toMatch(/\/o\/[0-9a-f]{64}$/)
  await expect(page.getByRole('link', { name: 'Enviar pelo WhatsApp' })).toHaveAttribute('href', /^https:\/\/wa\.me\/5511987654321\?text=/)

  // Cliente abre o link, sem login
  const client = await browser.newContext({ storageState: { cookies: [], origins: [] }, viewport: { width: 390, height: 844 }, hasTouch: true })
  const customerPage = await client.newPage()
  await customerPage.goto(link)
  await expect(customerPage.getByRole('heading', { name: 'Itens do orçamento' })).toBeVisible()
  await expect(customerPage.getByText('Olá, Ana.')).toBeVisible()

  await customerPage.getByRole('checkbox', { name: /Filtro de ar/ }).uncheck()
  await expect(customerPage.getByText('Total de 1 item')).toBeVisible()
  await expect(customerPage.getByRole('button', { name: 'Aprovar e assinar' })).toBeDisabled() // falta assinar

  await customerPage.getByLabel('Seu nome completo').fill('Ana Paula Ribeiro')
  await sign(customerPage)
  await customerPage.getByRole('button', { name: 'Aprovar e assinar' }).click()
  await expect(customerPage.getByRole('heading', { name: 'Orçamento aprovado.' })).toBeVisible()
  await client.close()

  // Oficina vê a aprovação e a OS gerada pela fila
  await page.reload()
  await expect(page.getByText('Aprovado', { exact: true })).toBeVisible()
  await expect(page.getByText(/Ana Paula Ribeiro aprovou e assinou/)).toBeVisible()
  await page.getByRole('link', { name: /Ver OS \d+/ }).click()

  await expect(page.getByRole('heading', { name: /^OS \d+$/ })).toBeVisible()
  await expect(page.getByText('Aberta', { exact: true }).first()).toBeVisible()
  await expect(page.getByText('Troca de óleo')).toBeVisible()
  await expect(page.getByText('Filtro de ar')).toHaveCount(0) // item recusado não entra na OS
  await expect(page.getByRole('link', { name: 'Orçamento', exact: true })).toBeVisible()
})

test('não salva orçamento sem cliente ou sem itens', async ({ page }) => {
  await page.goto('/orcamentos/novo')
  await page.getByRole('button', { name: 'Salvar orçamento' }).click()
  await expect(page.getByText('Escolha o cliente e o veículo.')).toBeVisible()

  await pickCustomerAndVehicle(page, 'Ana Paula Ribeiro', /QWE-1234/)
  await page.getByRole('button', { name: 'Salvar orçamento' }).click()
  await expect(page.getByText('Adicione ao menos um item.')).toBeVisible()

  // campo obrigatório do tipo de serviço
  const form = page.getByRole('form', { name: 'Item' })
  await form.getByLabel('Tipo de serviço').selectOption({ label: 'Troca de óleo' })
  await form.getByRole('button', { name: 'Adicionar item' }).click()
  await expect(form.getByText('Informe marca do óleo.')).toBeVisible()
  await expect(form.getByText('Escolha viscosidade.')).toBeVisible()
})

test('link inválido mostra mensagem para o cliente', async ({ browser }) => {
  const client = await browser.newContext({ storageState: { cookies: [], origins: [] } })
  const p = await client.newPage()
  await p.goto(`/o/${'a'.repeat(64)}`)
  await expect(p.getByRole('heading', { name: 'Link inválido.' })).toBeVisible()
  await client.close()
})

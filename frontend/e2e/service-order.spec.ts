import { expect, test } from '@playwright/test'
import { addProduct, pickCustomerAndVehicle } from './helpers'

test('OS direta: abre, executa, entrega e fecha', async ({ page }) => {
  await page.goto('/os/nova')
  await page.getByRole('button', { name: 'Abrir OS' }).click()
  await expect(page.getByText('Escolha o cliente e o veículo.')).toBeVisible()

  await pickCustomerAndVehicle(page, 'Ana Paula Ribeiro', /QWE-1234/)
  await page.getByLabel('Km de entrada').fill('50000')
  await addProduct(page, 'Pastilha de freio', '2', '120', 'Rafael Souza')
  await page.getByRole('button', { name: 'Abrir OS' }).click()

  await expect(page.getByRole('heading', { name: /^OS \d+$/ })).toBeVisible()
  await expect(page.getByText(/OS \d+ aberta\./)).toBeVisible()
  await expect(page.getByText('R$ 240,00').first()).toBeVisible()

  await page.getByRole('button', { name: 'Iniciar serviço' }).click()
  await expect(page.getByText('Em execução', { exact: true }).first()).toBeVisible()

  await page.getByRole('button', { name: 'Marcar como feito' }).click()
  await expect(page.getByText(/Feito hoje/)).toBeVisible()
  await expect(page.getByText('1 de 1 feitos')).toBeVisible()

  await page.getByRole('button', { name: 'Marcar como pronta' }).click()
  await expect(page.getByRole('button', { name: 'Registrar entrega' })).toBeVisible()
  await page.getByRole('button', { name: 'Registrar entrega' }).click()

  await page.getByRole('button', { name: 'Fechar OS' }).click()
  const dialog = page.getByRole('dialog')
  await dialog.getByLabel('Km de saída').fill('49000')
  await expect(dialog.getByText(/Não pode ser menor que o km de entrada/)).toBeVisible()
  await dialog.getByLabel('Km de saída').fill('50020')
  await dialog.getByRole('button', { name: 'Fechar OS' }).click()

  await expect(page.getByText('Fechada', { exact: true }).first()).toBeVisible()
  await expect(page.getByText('50.020 km')).toBeVisible()
  await expect(page.getByRole('heading', { name: 'Próximo passo' })).toHaveCount(0)
})

test('cancela OS com confirmação', async ({ page }) => {
  await page.goto('/os/nova')
  await pickCustomerAndVehicle(page, 'Ana Paula Ribeiro', /BRA2E19/)
  await page.getByRole('button', { name: 'Abrir OS' }).click()
  await expect(page.getByRole('heading', { name: /^OS \d+$/ })).toBeVisible()

  await page.getByRole('button', { name: 'Cancelar OS' }).click()
  const dialog = page.getByRole('dialog')
  await expect(dialog.getByText('Essa ação não pode ser desfeita.')).toBeVisible()
  await dialog.getByRole('button', { name: 'Cancelar OS' }).click()
  await expect(page.getByText('Cancelada', { exact: true }).first()).toBeVisible()
})

test('cadastra cliente com veículo e vê o histórico', async ({ page }) => {
  await page.goto('/clientes')
  await page.getByRole('button', { name: 'Cadastrar cliente' }).click()
  const form = page.getByRole('form', { name: 'Novo cliente' })
  await form.getByRole('button', { name: 'Salvar cliente' }).click()
  await expect(form.getByText('Informe o nome do cliente.')).toBeVisible()

  const plate = `TST${Math.floor(Math.random() * 10)}A${String(Math.floor(Math.random() * 100)).padStart(2, '0')}`
  await form.getByLabel('Nome').fill('Marcos Teixeira')
  await form.getByLabel('Celular (WhatsApp)').fill('(21) 99876-5432')
  await form.getByLabel('Placa').fill(plate.toLowerCase())
  await form.getByLabel('Marca').fill('Chevrolet')
  await form.getByLabel('Modelo').fill('Onix')
  await form.getByRole('button', { name: 'Salvar cliente' }).click()

  await expect(page.getByRole('heading', { name: 'Marcos Teixeira' })).toBeVisible()
  await expect(page.getByRole('radio', { name: new RegExp(plate) })).toBeVisible()
  await expect(page.getByText('Nenhuma OS para este veículo.')).toBeVisible()
})

import { expect, type Page } from '@playwright/test'

/** Escolhe cliente pela busca e o veículo pela placa. */
export async function pickCustomerAndVehicle(page: Page, customer: string, plate: RegExp) {
  await page.getByLabel('Buscar cliente').fill(customer.split(' ')[0])
  await page.getByRole('button', { name: new RegExp(customer) }).click()
  await page.getByRole('radio', { name: plate }).click()
  await expect(page.getByRole('radio', { name: plate })).toHaveAttribute('aria-checked', 'true')
}

/** Item do tipo "Troca de óleo" com os campos específicos preenchidos. */
export async function addOilChange(page: Page, technician: string) {
  const form = page.getByRole('form', { name: 'Item' })
  await form.getByLabel('Tipo de serviço').selectOption({ label: 'Troca de óleo' })
  await form.getByLabel('Marca do óleo').fill('Mobil Super')
  await form.getByLabel('Viscosidade').selectOption('5W30')
  await form.getByLabel('Litros (L)').fill('4')
  await form.getByLabel('Trocou filtro').check()
  await form.getByLabel('Técnico responsável').selectOption({ label: technician })
  await form.getByRole('button', { name: 'Adicionar item' }).click()
}

export async function addProduct(page: Page, description: string, quantity: string, price: string, technician: string) {
  const form = page.getByRole('form', { name: 'Item' })
  await form.getByText('Peça/produto', { exact: true }).click()
  await form.getByLabel('Descrição').fill(description)
  await form.getByLabel('Quantidade').fill(quantity)
  await form.getByLabel('Valor unitário (R$)').fill(price)
  await form.getByLabel('Técnico responsável').selectOption({ label: technician })
  await form.getByRole('button', { name: 'Adicionar item' }).click()
}

/** Desenha um "rabisco" de assinatura no canvas com o mouse. */
export async function sign(page: Page) {
  const canvas = page.getByRole('img', { name: /Área de assinatura/ })
  await canvas.scrollIntoViewIfNeeded()
  const box = await canvas.boundingBox()
  if (!box) throw new Error('canvas de assinatura não visível')
  const y = box.y + box.height / 2
  await page.mouse.move(box.x + 30, y)
  await page.mouse.down()
  for (let i = 1; i <= 12; i++) await page.mouse.move(box.x + 30 + i * 18, y + (i % 2 ? -18 : 18), { steps: 3 })
  await page.mouse.up()
}

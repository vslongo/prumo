import { expect, test, type Page } from '@playwright/test'
import { addProduct, pickCustomerAndVehicle } from './helpers'

/** JPEG "de câmera" (2400×1800) gerado no próprio navegador. */
async function cameraJpeg(page: Page): Promise<Buffer> {
  const dataUrl = await page.evaluate(() => {
    const c = document.createElement('canvas')
    c.width = 2400
    c.height = 1800
    const g = c.getContext('2d')!
    for (let i = 0; i < 200; i++) {
      g.fillStyle = `hsl(${i * 11}, 70%, 50%)`
      g.fillRect((i * 97) % 2400, (i * 53) % 1800, 300, 200)
    }
    return c.toDataURL('image/jpeg', 0.95)
  })
  return Buffer.from(dataUrl.split(',')[1], 'base64')
}

test('foto sobe em segundo plano: a OS segue andando enquanto envia', async ({ page }) => {
  await page.goto('/os/nova')
  await pickCustomerAndVehicle(page, 'Ana Paula Ribeiro', /QWE-1234/)
  await addProduct(page, 'Pastilha de freio', '1', '120', 'Rafael Souza')
  await page.getByRole('button', { name: 'Abrir OS' }).click()
  await expect(page.getByRole('heading', { name: /^OS \d+$/ })).toBeVisible()

  // Conexão lenta: segura o PUT no storage até liberarmos.
  let release: () => void = () => undefined
  const held = new Promise<void>((r) => (release = r))
  await page.route('**/r2-local/**', async (route) => {
    if (route.request().method() === 'PUT') await held
    await route.continue()
  })

  await page.getByLabel('Adicionar foto').setInputFiles({ name: 'camera.jpg', mimeType: 'image/jpeg', buffer: await cameraJpeg(page) })
  await expect(page.getByRole('listitem', { name: 'Enviando foto' })).toBeVisible()

  // Nada espera a foto.
  await page.getByRole('button', { name: 'Iniciar serviço' }).click()
  await expect(page.getByText('Em execução', { exact: true }).first()).toBeVisible()
  await expect(page.getByRole('listitem', { name: 'Enviando foto' })).toBeVisible()

  release()
  await expect(page.getByRole('button', { name: 'Ver foto' })).toBeVisible()
  await expect(page.getByRole('listitem', { name: 'Enviando foto' })).toHaveCount(0)

  await page.reload()
  await page.getByRole('button', { name: 'Ver foto' }).click()
  await expect(page.getByRole('img', { name: 'Foto do item' })).toBeVisible()
  await page.getByRole('button', { name: 'Remover foto' }).click()
  await expect(page.getByRole('button', { name: 'Ver foto' })).toHaveCount(0)
})

test('foto que falhou fica na tela para tentar de novo', async ({ page }) => {
  await page.goto('/os/nova')
  await pickCustomerAndVehicle(page, 'Ana Paula Ribeiro', /BRA2E19/)
  await addProduct(page, 'Correia', '1', '90', 'Rafael Souza')
  await page.getByRole('button', { name: 'Abrir OS' }).click()
  await expect(page.getByRole('heading', { name: /^OS \d+$/ })).toBeVisible()

  // Storage recusa (403): não adianta insistir sozinho.
  let reject = true
  await page.route('**/r2-local/**', (route) => (reject && route.request().method() === 'PUT' ? route.fulfill({ status: 403 }) : route.continue()))

  await page.getByLabel('Adicionar foto').setInputFiles({ name: 'camera.jpg', mimeType: 'image/jpeg', buffer: await cameraJpeg(page) })
  await expect(page.getByRole('listitem', { name: /Foto não enviada/ })).toBeVisible()

  reject = false
  await page.getByRole('button', { name: 'Tentar enviar de novo' }).click()
  await expect(page.getByRole('button', { name: 'Ver foto' })).toBeVisible()
})

test('cliente vê a foto do item no link de aprovação', async ({ page, browser }) => {
  await page.goto('/orcamentos/novo')
  await pickCustomerAndVehicle(page, 'Ana Paula Ribeiro', /QWE-1234/)
  await addProduct(page, 'Disco de freio', '2', '180', 'Rafael Souza')
  await page.getByRole('button', { name: 'Salvar orçamento' }).click()
  await expect(page).toHaveURL(/\/orcamentos\/[0-9a-f-]{36}$/)

  await page.getByLabel('Adicionar foto').setInputFiles({ name: 'disco.jpg', mimeType: 'image/jpeg', buffer: await cameraJpeg(page) })
  await expect(page.getByRole('button', { name: 'Ver foto' })).toBeVisible()

  await page.getByRole('button', { name: 'Gerar link de aprovação' }).click()
  const link = await page.getByLabel('Link de aprovação').inputValue()

  const customer = await browser.newPage({ storageState: { cookies: [], origins: [] } })
  await customer.goto(new URL(link).pathname)
  await expect(customer.getByRole('list', { name: 'Fotos de Disco de freio' }).getByRole('button', { name: 'Ver foto' })).toBeVisible()
  await customer.close()
})

test('tira a foto ainda montando o orçamento; sobe depois de salvar, no item certo', async ({ page }) => {
  await page.goto('/orcamentos/novo')
  await pickCustomerAndVehicle(page, 'Ana Paula Ribeiro', /QWE-1234/)
  await addProduct(page, 'Pastilha dianteira', '1', '150', 'Rafael Souza')
  await addProduct(page, 'Fluido de freio', '1', '40', 'Rafael Souza')

  // Foto só no segundo item, antes de existir orçamento.
  await page.getByLabel('Adicionar foto').nth(1).setInputFiles({ name: 'fluido.jpg', mimeType: 'image/jpeg', buffer: await cameraJpeg(page) })
  await expect(page.getByRole('button', { name: 'Ver foto' })).toHaveCount(1)

  await page.getByRole('button', { name: 'Salvar orçamento' }).click()
  await expect(page).toHaveURL(/\/orcamentos\/[0-9a-f-]{36}$/)
  // Já na tela do orçamento salvo (o rascunho fica visível um instante durante a navegação).
  await expect(page.getByRole('button', { name: 'Gerar link de aprovação' })).toBeVisible()

  const photos = page.getByRole('list', { name: 'Fotos do item' })
  await expect(photos.nth(1).getByRole('button', { name: 'Ver foto' })).toBeVisible()
  await expect(photos.nth(0).getByRole('button', { name: 'Ver foto' })).toHaveCount(0)
  await expect(page.getByRole('listitem', { name: 'Enviando foto' })).toHaveCount(0)

  await page.reload()
  await expect(page.getByRole('button', { name: 'Ver foto' })).toHaveCount(1)
})

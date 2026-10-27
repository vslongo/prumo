const money = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })
const integer = new Intl.NumberFormat('pt-BR')
const quantity = new Intl.NumberFormat('pt-BR', { maximumFractionDigits: 3 })
const time = new Intl.DateTimeFormat('pt-BR', { hour: '2-digit', minute: '2-digit' })
const date = new Intl.DateTimeFormat('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' })

/** 125000 → "R$ 1.250,00". Dinheiro sempre trafega em centavos. */
export function formatMoney(cents: number): string {
  return money.format(cents / 100).replace(/ /g, ' ')
}

/** "1.250,50" | "1250.5" | "80" → centavos. Inválido → null. */
export function parseMoneyToCents(input: string): number | null {
  const clean = input.replace(/[R$\s]/g, '')
  if (clean === '') return null
  const normalized = clean.includes(',') ? clean.replace(/\./g, '').replace(',', '.') : clean
  if (!/^\d+(\.\d{1,2})?$/.test(normalized)) return null
  const [reais, centavos = ''] = normalized.split('.')
  return Number(reais) * 100 + Number(centavos.padEnd(2, '0'))
}

/** Centavos → "1250,50" para preencher campo de valor. */
export function centsToInput(cents: number): string {
  return (cents / 100).toFixed(2).replace('.', ',')
}

export function formatKm(km: number | null | undefined): string {
  return km === null || km === undefined ? '—' : `${integer.format(km)} km`
}

/** "Hoje, 14:20" · "Ontem, 09:10" · "12/10/2026". */
export function formatWhen(iso: string | null | undefined, now: Date = new Date()): string {
  if (!iso) return '—'
  const d = new Date(iso)
  const startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime()
  const day = 24 * 60 * 60 * 1000
  if (d.getTime() >= startOfToday + day) return date.format(d) // futuro (ex.: validade do link)
  if (d.getTime() >= startOfToday) return `Hoje, ${time.format(d)}`
  if (d.getTime() >= startOfToday - day) return `Ontem, ${time.format(d)}`
  return date.format(d)
}

/** Quantidade como o backend devolve ("4.500") → "4,5". */
export function formatQuantity(q: string | number): string {
  return quantity.format(Number(q))
}

export function formatPlate(plate: string): string {
  return /^[A-Z]{3}\d{4}$/.test(plate) ? `${plate.slice(0, 3)}-${plate.slice(3)}` : plate
}

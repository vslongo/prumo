import { centsToInput, formatKm, formatMoney, formatPlate, formatQuantity, formatWhen, parseMoneyToCents } from './format'

describe('dinheiro', () => {
  it('formata centavos em reais', () => {
    expect(formatMoney(125000)).toBe('R$ 1.250,00')
    expect(formatMoney(0)).toBe('R$ 0,00')
  })

  it('lê o que o usuário digita em centavos', () => {
    expect(parseMoneyToCents('80')).toBe(8000)
    expect(parseMoneyToCents('45,9')).toBe(4590)
    expect(parseMoneyToCents('1.250,50')).toBe(125050)
    expect(parseMoneyToCents('R$ 12,34')).toBe(1234)
    expect(parseMoneyToCents('12.5')).toBe(1250)
  })

  it('recusa valor inválido', () => {
    expect(parseMoneyToCents('')).toBeNull()
    expect(parseMoneyToCents('abc')).toBeNull()
    expect(parseMoneyToCents('1,234')).toBeNull()
    expect(parseMoneyToCents('-5')).toBeNull()
  })

  it('volta centavos para o campo', () => {
    expect(centsToInput(8000)).toBe('80,00')
  })
})

describe('datas', () => {
  const now = new Date(2026, 9, 27, 15, 0)

  it('usa hoje/ontem para o recente e data cheia para o resto', () => {
    expect(formatWhen(new Date(2026, 9, 27, 14, 20).toISOString(), now)).toBe('Hoje, 14:20')
    expect(formatWhen(new Date(2026, 9, 26, 9, 5).toISOString(), now)).toBe('Ontem, 09:05')
    expect(formatWhen(new Date(2026, 9, 12, 9, 5).toISOString(), now)).toBe('12/10/2026')
    expect(formatWhen(null, now)).toBe('—')
  })

  it('data futura (validade do link) mostra o dia, não "Hoje"', () => {
    expect(formatWhen(new Date(2026, 10, 3, 14, 52).toISOString(), now)).toBe('03/11/2026')
  })
})

describe('outros formatos', () => {
  it('km, quantidade e placa', () => {
    expect(formatKm(84300)).toBe('84.300 km')
    expect(formatKm(null)).toBe('—')
    expect(formatQuantity('4.500')).toBe('4,5')
    expect(formatPlate('ABC1234')).toBe('ABC-1234')
    expect(formatPlate('BRA2E19')).toBe('BRA2E19')
  })
})

import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import type { User } from '../../auth/api'
import type { ServiceType } from '../../serviceTypes/api'
import { ItemForm } from './ItemForm'

const two_factor = { enabled: false, required_from: null, blocked: false }
const users: User[] = [
  { id: 'u1', name: 'Carlos Mendes', email: 'c@x.test', role: 'owner', is_active: true, two_factor },
  { id: 'u2', name: 'Rafael Souza', email: 'r@x.test', role: 'technician', is_active: true, two_factor },
  { id: 'u3', name: 'Saiu da Oficina', email: 's@x.test', role: 'technician', is_active: false, two_factor },
]

const types: ServiceType[] = [
  {
    id: 'st1',
    name: 'Troca de óleo',
    default_price_cents: 8000,
    is_system_default: true,
    fields_schema: [
      { key: 'oil_brand', label: 'Marca do óleo', type: 'text', required: true },
      { key: 'liters', label: 'Litros', type: 'number', required: true, unit: 'L' },
    ],
  },
]

describe('ItemForm', () => {
  it('só oferece técnicos ativos como responsável', () => {
    render(<ItemForm serviceTypes={types} users={users} onSubmit={vi.fn()} />)

    expect(screen.getByRole('option', { name: 'Rafael Souza' })).toBeInTheDocument()
    expect(screen.queryByRole('option', { name: 'Saiu da Oficina' })).not.toBeInTheDocument()
  })

  it('ao escolher o tipo, preenche descrição e preço e exige os campos específicos', async () => {
    const onSubmit = vi.fn()
    render(<ItemForm serviceTypes={types} users={users} defaultTechnicianId="u1" onSubmit={onSubmit} />)
    const user = userEvent.setup()

    await user.selectOptions(screen.getByLabelText('Tipo de serviço'), 'st1')
    expect(screen.getByLabelText('Descrição')).toHaveValue('Troca de óleo')
    expect(screen.getByLabelText('Valor unitário (R$)')).toHaveValue('80,00')

    await user.click(screen.getByRole('button', { name: 'Adicionar item' }))
    expect(await screen.findByText('Informe marca do óleo.')).toBeInTheDocument()
    expect(onSubmit).not.toHaveBeenCalled()

    await user.type(screen.getByLabelText('Marca do óleo'), 'Mobil')
    await user.type(screen.getByLabelText('Litros (L)'), '4')
    await user.selectOptions(screen.getByLabelText('Técnico responsável'), 'u2')
    await user.click(screen.getByRole('button', { name: 'Adicionar item' }))

    expect(onSubmit).toHaveBeenCalledWith({
      kind: 'service',
      description: 'Troca de óleo',
      service_type_id: 'st1',
      technician_id: 'u2',
      quantity: '1',
      unit_price_cents: 8000,
      fields: { oil_brand: 'Mobil', liters: 4 },
    })
  })

  it('peça avulsa: valor em reais vira centavos e mostra o total do item', async () => {
    const onSubmit = vi.fn()
    render(<ItemForm serviceTypes={types} users={users} defaultTechnicianId="u1" onSubmit={onSubmit} />)
    const user = userEvent.setup()

    await user.click(screen.getByText('Peça/produto'))
    expect(screen.queryByLabelText('Tipo de serviço')).not.toBeInTheDocument()
    await user.type(screen.getByLabelText('Descrição'), 'Óleo 5W30')
    await user.clear(screen.getByLabelText('Quantidade'))
    await user.type(screen.getByLabelText('Quantidade'), '4,5')
    await user.type(screen.getByLabelText('Valor unitário (R$)'), '39,99')
    expect(screen.getByText('R$ 179,96')).toBeInTheDocument()

    await user.click(screen.getByRole('button', { name: 'Adicionar item' }))
    expect(onSubmit).toHaveBeenCalledWith(expect.objectContaining({ kind: 'product', service_type_id: null, quantity: '4.5', unit_price_cents: 3999, fields: {} }))
  })

  it('valida descrição, quantidade e valor', async () => {
    render(<ItemForm serviceTypes={types} users={users} onSubmit={vi.fn()} />)
    const user = userEvent.setup()
    await user.clear(screen.getByLabelText('Quantidade'))
    await user.click(screen.getByRole('button', { name: 'Adicionar item' }))

    expect(await screen.findByText('Informe a descrição.')).toBeInTheDocument()
    expect(screen.getByText('Informe a quantidade.')).toBeInTheDocument()
    expect(screen.getByText('Informe o valor (ex.: 80,00).')).toBeInTheDocument()
    expect(screen.getByText('Escolha o técnico responsável.')).toBeInTheDocument()
  })
})

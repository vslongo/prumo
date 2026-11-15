import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import type { Selection } from '../../client/components/CustomerVehiclePicker'
import { DraftEditor } from './DraftEditor'

vi.mock('../../auth/api', () => ({
  useMe: () => ({ data: null }),
  useUsers: () => ({ data: [] }),
}))
vi.mock('../../serviceTypes/api', () => ({ useServiceTypes: () => ({ data: [] }) }))
vi.mock('./ItemForm', () => ({ ItemForm: () => null }))
vi.mock('../../client/components/CustomerVehiclePicker', () => ({
  CustomerVehiclePicker: ({ onChange }: { onChange: (value: Selection | null) => void }) => {
    const choose = (id: string, mileage: number | null) => onChange({
      customer: { id: 'c1', name: 'Ana', email: null, phone: null, document: null, consent_whatsapp_at: null },
      vehicle: { id, customer_id: 'c1', plate: 'ABC1234', vin: null, brand: null, model: null, year: null, mileage },
    })
    return <>
      <button onClick={() => choose('v1', 45000)}>Veículo com KM</button>
      <button onClick={() => choose('v2', 0)}>Veículo zero KM</button>
      <button onClick={() => choose('v3', null)}>Veículo sem KM</button>
      <button onClick={() => onChange(null)}>Trocar cliente</button>
    </>
  },
}))

it.each(['Km de entrada', 'Km atual'])('preenche e permite alterar %s sem sobrescrever a edição', async (mileageLabel) => {
  const user = userEvent.setup()
  const onSubmit = vi.fn().mockResolvedValue(undefined)
  render(<DraftEditor mileageLabel={mileageLabel} submitLabel="Salvar" submittingLabel="Salvando" onSubmit={onSubmit} />)
  await user.click(screen.getByText('Veículo com KM'))
  const input = screen.getByLabelText(mileageLabel)
  expect(input).toHaveValue('45000')
  await user.clear(input)
  await user.type(input, '46000')
  await user.click(screen.getByText('Veículo com KM'))
  expect(input).toHaveValue('46000')
  await user.click(screen.getByRole('button', { name: 'Salvar' }))
  expect(onSubmit).toHaveBeenCalledWith(expect.objectContaining({ vehicleId: 'v1', mileage: 46000 }))
})

it('substitui o KM ao trocar de veículo e limpa ao trocar de cliente', async () => {
  const user = userEvent.setup()
  render(<DraftEditor mileageLabel="KM" submitLabel="Salvar" submittingLabel="Salvando" onSubmit={vi.fn()} />)
  await user.click(screen.getByText('Veículo com KM'))
  await user.click(screen.getByText('Veículo zero KM'))
  expect(screen.getByLabelText('KM')).toHaveValue('0')
  await user.click(screen.getByText('Veículo sem KM'))
  expect(screen.getByLabelText('KM')).toHaveValue('')
  await user.click(screen.getByText('Trocar cliente'))
  expect(screen.queryByLabelText('KM')).not.toBeInTheDocument()
  await user.click(screen.getByText('Veículo com KM'))
  expect(screen.getByLabelText('KM')).toHaveValue('45000')
})

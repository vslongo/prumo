import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { ApiError } from '../../../shared/lib/http'
import { vehiclesApi, type Vehicle } from '../api'
import { VehicleForm } from './VehicleForm'

vi.mock('../api', () => ({ vehiclesApi: { create: vi.fn(), update: vi.fn() } }))
const vehicle: Vehicle = { id: 'v1', customer_id: 'c1', plate: 'ABC1234', brand: 'VW', model: 'Gol', year: 2020, mileage: 45000, vin: null }

it('preenche a edição e salva KM alterado e campos opcionais removidos', async () => {
  const user = userEvent.setup()
  const onSaved = vi.fn()
  vi.mocked(vehiclesApi.update).mockResolvedValue({ ...vehicle, mileage: 46000, model: null })
  render(<VehicleForm customerId="c1" vehicle={vehicle} onCreated={onSaved} />)
  expect(screen.getByLabelText('Placa')).toHaveValue('ABC1234')
  expect(screen.getByLabelText('Ano')).toHaveValue('2020')
  const km = screen.getByLabelText('Quilometragem (KM)')
  expect(km).toHaveValue('45000')
  await user.clear(km)
  await user.type(km, '46000')
  await user.clear(screen.getByLabelText('Modelo'))
  await user.click(screen.getByRole('button', { name: 'Salvar alterações' }))
  expect(vehiclesApi.update).toHaveBeenCalledWith('v1', { plate: 'ABC1234', brand: 'VW', model: null, year: 2020, mileage: 46000 })
  expect(vehiclesApi.create).not.toHaveBeenCalled()
  expect(onSaved).toHaveBeenCalledWith(expect.objectContaining({ mileage: 46000, model: null }))
})

it('mantém o formulário aberto e mostra o erro retornado pelo backend', async () => {
  const user = userEvent.setup()
  const onSaved = vi.fn()
  vi.mocked(vehiclesApi.update).mockRejectedValue(new ApiError(422, 'Dados inválidos', { plate: ['Placa já cadastrada.'] }))
  render(<VehicleForm customerId="c1" vehicle={vehicle} onCreated={onSaved} />)
  await user.click(screen.getByRole('button', { name: 'Salvar alterações' }))
  expect(await screen.findByText('Placa já cadastrada.')).toBeInTheDocument()
  expect(onSaved).not.toHaveBeenCalled()
})

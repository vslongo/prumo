import { zodResolver } from '@hookform/resolvers/zod'
import Box from '@mui/material/Box'
import Stack from '@mui/material/Stack'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { ApiError } from '../../../shared/lib/http'
import { Button } from '../../../shared/ui/Button'
import { TextField } from '../../../shared/ui/Field'
import { ErrorBox } from '../../../shared/ui/States'
import { fieldsetSx, uppercase } from '../../../shared/ui/styles'
import { vehiclesApi, type Vehicle } from '../api'

const schema = z.object({
  plate: z
    .string()
    .trim()
    .transform((v) => v.toUpperCase().replace(/[^A-Z0-9]/g, ''))
    .refine((v) => /^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/.test(v), 'Informe a placa (ABC1234 ou ABC1D23).'),
  brand: z.string().trim().max(60),
  model: z.string().trim().max(80),
  mileage: z.string().trim().refine((v) => v === '' || (/^\d+$/.test(v) && Number(v) <= 2147483647), 'Informe um KM inteiro entre 0 e 2147483647.'),
  year: z
    .string()
    .trim()
    .refine((v) => v === '' || (/^\d{4}$/.test(v) && Number(v) >= 1950 && Number(v) <= 2100), 'Ano inválido.'),
})

export function VehicleForm({ customerId, vehicle, onCreated, onCancel }: { customerId: string; vehicle?: Vehicle; onCreated: (v: Vehicle) => void; onCancel?: () => void }) {
  const [error, setError] = useState<string | null>(null)
  const { register, handleSubmit, formState } = useForm<z.input<typeof schema>, unknown, z.output<typeof schema>>({
    resolver: zodResolver(schema),
    defaultValues: { plate: vehicle?.plate ?? '', brand: vehicle?.brand ?? '', model: vehicle?.model ?? '', year: vehicle?.year == null ? '' : String(vehicle.year), mileage: vehicle?.mileage == null ? '' : String(vehicle.mileage) },
  })

  const submit = handleSubmit(async (v) => {
    setError(null)
    try {
      const input = { plate: v.plate, brand: v.brand || null, model: v.model || null, year: v.year ? Number(v.year) : null, mileage: v.mileage === '' ? null : Number(v.mileage) }
      onCreated(vehicle ? await vehiclesApi.update(vehicle.id, input) : await vehiclesApi.create({ customer_id: customerId, ...input }))
    } catch (e) {
      setError(e instanceof ApiError ? (Object.values(e.errors)[0]?.[0] ?? e.message) : 'Não deu para salvar. Tente de novo.')
    }
  })

  return (
    <Stack component="form" onSubmit={submit} noValidate aria-label={vehicle ? 'Editar veículo' : 'Novo veículo'} sx={fieldsetSx}>
      {error ? <ErrorBox message={error} /> : null}
      <TextField label="Placa" autoCapitalize="characters" sx={uppercase} error={formState.errors.plate?.message} {...register('plate')} />
      <Box sx={{ display: 'grid', gridTemplateColumns: 'repeat(2, minmax(0, 1fr))', gap: 1.5 }}>
        <TextField label="Marca" {...register('brand')} />
        <TextField label="Modelo" {...register('model')} />
      </Box>
      <TextField label="Ano" inputMode="numeric" sx={{ maxWidth: 160 }} error={formState.errors.year?.message} {...register('year')} />
      <TextField label="Quilometragem (KM)" hint="Opcional" inputMode="numeric" error={formState.errors.mileage?.message} {...register('mileage')} />
      <Stack direction={{ xs: 'column-reverse', sm: 'row' }} sx={{ gap: 1, justifyContent: 'flex-end' }}>
        {onCancel ? (
          <Button variant="secondary" onClick={onCancel}>
            Cancelar
          </Button>
        ) : null}
        <Button type="submit" variant="secondary" loading={formState.isSubmitting} loadingText="Salvando…">
          {vehicle ? 'Salvar alterações' : 'Salvar veículo'}
        </Button>
      </Stack>
    </Stack>
  )
}

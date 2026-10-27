import { zodResolver } from '@hookform/resolvers/zod'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { ApiError } from '../../../shared/lib/http'
import { Button } from '../../../shared/ui/Button'
import { TextField } from '../../../shared/ui/Field'
import { ErrorBox } from '../../../shared/ui/States'
import { vehiclesApi, type Vehicle } from '../api'

const schema = z.object({
  plate: z
    .string()
    .trim()
    .transform((v) => v.toUpperCase().replace(/[^A-Z0-9]/g, ''))
    .refine((v) => /^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/.test(v), 'Informe a placa (ABC1234 ou ABC1D23).'),
  brand: z.string().trim().max(60),
  model: z.string().trim().max(80),
  year: z
    .string()
    .trim()
    .refine((v) => v === '' || (/^\d{4}$/.test(v) && Number(v) >= 1950 && Number(v) <= 2100), 'Ano inválido.'),
})

export function VehicleForm({ customerId, onCreated, onCancel }: { customerId: string; onCreated: (v: Vehicle) => void; onCancel?: () => void }) {
  const [error, setError] = useState<string | null>(null)
  const { register, handleSubmit, formState } = useForm<z.input<typeof schema>, unknown, z.output<typeof schema>>({
    resolver: zodResolver(schema),
    defaultValues: { plate: '', brand: '', model: '', year: '' },
  })

  const submit = handleSubmit(async (v) => {
    setError(null)
    try {
      onCreated(await vehiclesApi.create({ customer_id: customerId, plate: v.plate, brand: v.brand || undefined, model: v.model || undefined, year: v.year ? Number(v.year) : undefined }))
    } catch (e) {
      setError(e instanceof ApiError ? (e.field('plate') ?? e.message) : 'Não deu para salvar. Tente de novo.')
    }
  })

  return (
    <form onSubmit={submit} noValidate className="flex flex-col gap-4 rounded-md border border-border bg-bg p-4" aria-label="Novo veículo">
      {error ? <ErrorBox message={error} /> : null}
      <TextField label="Placa" autoCapitalize="characters" className="[&_input]:uppercase" error={formState.errors.plate?.message} {...register('plate')} />
      <div className="grid grid-cols-2 gap-3">
        <TextField label="Marca" {...register('brand')} />
        <TextField label="Modelo" {...register('model')} />
      </div>
      <TextField label="Ano" inputMode="numeric" className="max-w-40" error={formState.errors.year?.message} {...register('year')} />
      <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        {onCancel ? (
          <Button variant="secondary" onClick={onCancel}>
            Cancelar
          </Button>
        ) : null}
        <Button type="submit" variant="secondary" loading={formState.isSubmitting} loadingText="Salvando…">
          Salvar veículo
        </Button>
      </div>
    </form>
  )
}

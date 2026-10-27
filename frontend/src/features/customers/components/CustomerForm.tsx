import { zodResolver } from '@hookform/resolvers/zod'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { ApiError } from '../../../shared/lib/http'
import { Button } from '../../../shared/ui/Button'
import { CheckboxField, TextField } from '../../../shared/ui/Field'
import { ErrorBox } from '../../../shared/ui/States'
import { vehiclesApi, type Vehicle } from '../../vehicles/api'
import { customersApi, type Customer } from '../api'

const plateRegex = /^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/

const schema = z.object({
  name: z.string().trim().min(2, 'Informe o nome do cliente.'),
  phone: z
    .string()
    .trim()
    .refine((v) => v === '' || /^\d{10,11}$/.test(v.replace(/\D/g, '')), 'Telefone com DDD (10 ou 11 dígitos).'),
  consent_whatsapp: z.boolean(),
  plate: z
    .string()
    .trim()
    .transform((v) => v.toUpperCase().replace(/[^A-Z0-9]/g, ''))
    .refine((v) => v === '' || plateRegex.test(v), 'Placa inválida (ABC1234 ou ABC1D23).'),
  brand: z.string().trim().max(60),
  model: z.string().trim().max(80),
  year: z
    .string()
    .trim()
    .refine((v) => v === '' || (/^\d{4}$/.test(v) && Number(v) >= 1950 && Number(v) <= 2100), 'Ano inválido.'),
})

type FormIn = z.input<typeof schema>

/** Cadastro rápido de cliente + primeiro veículo (o caso do balcão). */
export function CustomerForm({ onCreated, onCancel, withVehicle = true }: { onCreated: (c: Customer, v: Vehicle | null) => void; onCancel?: () => void; withVehicle?: boolean }) {
  const [error, setError] = useState<string | null>(null)
  const { register, handleSubmit, formState } = useForm<FormIn, unknown, z.output<typeof schema>>({
    resolver: zodResolver(schema),
    defaultValues: { name: '', phone: '', consent_whatsapp: true, plate: '', brand: '', model: '', year: '' },
  })

  const submit = handleSubmit(async (v) => {
    setError(null)
    try {
      const customer = await customersApi.create({
        name: v.name,
        phone: v.phone || undefined,
        consent_whatsapp: v.consent_whatsapp,
        consent_source: 'balcao',
      })
      const vehicle =
        withVehicle && v.plate
          ? await vehiclesApi.create({ customer_id: customer.id, plate: v.plate, brand: v.brand || undefined, model: v.model || undefined, year: v.year ? Number(v.year) : undefined })
          : null
      onCreated(customer, vehicle)
    } catch (e) {
      setError(e instanceof ApiError ? (Object.values(e.errors)[0]?.[0] ?? e.message) : 'Não deu para salvar. Tente de novo.')
    }
  })

  return (
    <form onSubmit={submit} noValidate className="flex flex-col gap-4" aria-label="Novo cliente">
      {error ? <ErrorBox message={error} /> : null}
      <TextField label="Nome" autoComplete="off" error={formState.errors.name?.message} {...register('name')} />
      <TextField label="Celular (WhatsApp)" type="tel" inputMode="tel" placeholder="(11) 98888-7777" error={formState.errors.phone?.message} {...register('phone')} />
      <CheckboxField label="Cliente aceita receber orçamento e avisos pelo WhatsApp" {...register('consent_whatsapp')} />

      {withVehicle ? (
        <fieldset className="flex flex-col gap-4 rounded-md border border-border bg-bg p-4">
          <legend className="px-1 text-sm font-semibold">Veículo</legend>
          <TextField label="Placa" autoCapitalize="characters" className="[&_input]:uppercase" error={formState.errors.plate?.message} {...register('plate')} />
          <div className="grid grid-cols-2 gap-3">
            <TextField label="Marca" {...register('brand')} />
            <TextField label="Modelo" {...register('model')} />
          </div>
          <TextField label="Ano" inputMode="numeric" className="max-w-40" error={formState.errors.year?.message} {...register('year')} />
        </fieldset>
      ) : null}

      <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        {onCancel ? (
          <Button variant="secondary" onClick={onCancel}>
            Cancelar
          </Button>
        ) : null}
        <Button type="submit" loading={formState.isSubmitting} loadingText="Salvando…">
          Salvar cliente
        </Button>
      </div>
    </form>
  )
}

import { zodResolver } from '@hookform/resolvers/zod'
import Stack from '@mui/material/Stack'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { ApiError } from '../../../shared/lib/http'
import { Button } from '../../../shared/ui/Button'
import { CheckboxField, TextField } from '../../../shared/ui/Field'
import { ErrorBox } from '../../../shared/ui/States'
import { customersApi, type Customer } from '../api'

const digits = (v: string) => v.replace(/\D/g, '')

const schema = z.object({
  name: z.string().trim().min(2, 'Informe o nome do cliente.'),
  phone: z
    .string()
    .trim()
    .refine((v) => v === '' || /^\d{10,11}$/.test(digits(v)), 'Telefone com DDD (10 ou 11 dígitos).'),
  email: z
    .string()
    .trim()
    .refine((v) => v === '' || z.email().safeParse(v).success, 'E-mail inválido.'),
  document: z
    .string()
    .trim()
    .refine((v) => v === '' || [11, 14].includes(digits(v).length), 'CPF (11 dígitos) ou CNPJ (14 dígitos).'),
  consent_whatsapp: z.boolean(),
})

type FormIn = z.input<typeof schema>

/** Edição do cadastro. Desmarcar o WhatsApp revoga a autorização (some a data e a origem). */
export function CustomerEditForm({ customer, onSaved, onCancel }: { customer: Customer; onSaved: (c: Customer) => void; onCancel: () => void }) {
  const [error, setError] = useState<string | null>(null)
  const { register, handleSubmit, formState } = useForm<FormIn, unknown, z.output<typeof schema>>({
    resolver: zodResolver(schema),
    defaultValues: {
      name: customer.name,
      phone: customer.phone ?? '',
      email: customer.email ?? '',
      document: customer.document ?? '',
      consent_whatsapp: customer.consent_whatsapp_at !== null,
    },
  })

  const submit = handleSubmit(async (v) => {
    setError(null)
    try {
      onSaved(
        await customersApi.update(customer.id, {
          name: v.name,
          phone: v.phone || null,
          email: v.email || null,
          document: v.document || null,
          consent_whatsapp: v.consent_whatsapp,
          consent_source: 'balcao',
        }),
      )
    } catch (e) {
      setError(e instanceof ApiError ? (Object.values(e.errors)[0]?.[0] ?? e.message) : 'Não deu para salvar. Tente de novo.')
    }
  })

  return (
    <Stack component="form" onSubmit={submit} noValidate aria-label="Editar cliente" sx={{ gap: 2 }}>
      {error ? <ErrorBox message={error} /> : null}
      <TextField label="Nome" autoComplete="off" error={formState.errors.name?.message} {...register('name')} />
      <TextField label="Celular (WhatsApp)" type="tel" inputMode="tel" placeholder="(11) 98888-7777" error={formState.errors.phone?.message} {...register('phone')} />
      <TextField label="E-mail" type="email" inputMode="email" autoComplete="off" error={formState.errors.email?.message} {...register('email')} />
      <TextField label="CPF ou CNPJ" inputMode="numeric" autoComplete="off" error={formState.errors.document?.message} {...register('document')} />
      <CheckboxField label="Cliente aceita receber orçamento e avisos pelo WhatsApp" defaultChecked={customer.consent_whatsapp_at !== null} {...register('consent_whatsapp')} />
      <Stack direction={{ xs: 'column-reverse', sm: 'row' }} sx={{ gap: 1, justifyContent: 'flex-end' }}>
        <Button variant="secondary" onClick={onCancel}>
          Cancelar
        </Button>
        <Button type="submit" loading={formState.isSubmitting} loadingText="Salvando…">
          Salvar
        </Button>
      </Stack>
    </Stack>
  )
}

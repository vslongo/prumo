import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect, useState } from 'react'
import { useForm, type Resolver } from 'react-hook-form'
import { z } from 'zod'
import { centsToInput, formatMoney, parseMoneyToCents } from '../../../shared/lib/format'
import { ApiError } from '../../../shared/lib/http'
import { Button } from '../../../shared/ui/Button'
import { SelectField, TextField } from '../../../shared/ui/Field'
import { ErrorBox } from '../../../shared/ui/States'
import type { User } from '../../auth/api'
import type { ServiceType } from '../../serviceTypes/api'
import { DynamicFields } from '../../serviceTypes/components/DynamicFields'
import { buildFieldsSchema, cleanFields } from '../../serviceTypes/components/fieldsSchema'
import { itemKindLabel, type ItemInput } from '../types'

type FormValues = {
  kind: 'service' | 'product'
  service_type_id: string
  description: string
  technician_id: string
  quantity: string
  price: string
  fields: Record<string, unknown>
}

function baseSchema(types: ServiceType[], serviceTypeId: string) {
  const type = types.find((t) => t.id === serviceTypeId)
  return z.object({
    kind: z.enum(['service', 'product']),
    service_type_id: z.string(),
    description: z.string().trim().min(1, 'Informe a descrição.').max(255),
    technician_id: z.string().min(1, 'Escolha o técnico responsável.'),
    quantity: z
      .string()
      .trim()
      .refine((v) => /^\d+([.,]\d{1,3})?$/.test(v) && Number(v.replace(',', '.')) > 0, 'Informe a quantidade.'),
    price: z.string().refine((v) => parseMoneyToCents(v) !== null, 'Informe o valor (ex.: 80,00).'),
    fields: type ? buildFieldsSchema(type.fields_schema) : z.object({}).optional(),
  })
}

type Props = {
  serviceTypes: ServiceType[]
  users: User[]
  defaultTechnicianId?: string
  submitLabel?: string
  onSubmit: (item: ItemInput) => Promise<void> | void
  onCancel?: () => void
}

/** Formulário de item (serviço ou peça), igual para orçamento e OS. */
export function ItemForm({ serviceTypes, users, defaultTechnicianId, submitLabel = 'Adicionar item', onSubmit, onCancel }: Props) {
  const [error, setError] = useState<string | null>(null)

  const resolver: Resolver<FormValues> = (values, ctx, opts) =>
    zodResolver(baseSchema(serviceTypes, values.service_type_id))(values, ctx, opts as never) as never

  const { register, handleSubmit, watch, setValue, reset, formState } = useForm<FormValues>({
    resolver,
    defaultValues: { kind: 'service', service_type_id: '', description: '', technician_id: defaultTechnicianId ?? '', quantity: '1', price: '', fields: {} },
  })

  const kind = watch('kind')
  const serviceTypeId = watch('service_type_id')
  const quantity = watch('quantity')
  const price = watch('price')
  const selectedType = serviceTypes.find((t) => t.id === serviceTypeId)

  // Escolher o tipo preenche descrição e preço padrão; limpa os campos do tipo anterior.
  useEffect(() => {
    setValue('fields', {})
    if (selectedType) {
      setValue('description', selectedType.name)
      if (selectedType.default_price_cents > 0) setValue('price', centsToInput(selectedType.default_price_cents))
    }
  }, [selectedType, setValue])

  useEffect(() => {
    if (kind === 'product') setValue('service_type_id', '')
  }, [kind, setValue])

  const qty = Number(quantity?.replace(',', '.'))
  const cents = parseMoneyToCents(price ?? '')
  const preview = cents !== null && qty > 0 ? Math.round(cents * qty) : null

  const submit = handleSubmit(async (v) => {
    setError(null)
    try {
      await onSubmit({
        kind: v.kind,
        description: v.description.trim(),
        service_type_id: v.kind === 'service' && v.service_type_id ? v.service_type_id : null,
        technician_id: v.technician_id,
        quantity: v.quantity.replace(',', '.'),
        unit_price_cents: parseMoneyToCents(v.price) ?? 0,
        fields: v.kind === 'service' && v.service_type_id ? cleanFields(v.fields) : {},
      })
      reset({ kind: v.kind, service_type_id: '', description: '', technician_id: v.technician_id, quantity: '1', price: '', fields: {} })
    } catch (e) {
      setError(e instanceof ApiError ? (Object.values(e.errors)[0]?.[0] ?? e.message) : 'Não deu para adicionar o item. Tente de novo.')
    }
  })

  return (
    <form onSubmit={submit} noValidate className="flex flex-col gap-4" aria-label="Item">
      {error ? <ErrorBox message={error} /> : null}

      <fieldset>
        <legend className="mb-1.5 text-sm font-medium">Tipo de item</legend>
        <div className="grid grid-cols-2 gap-2">
          {(['service', 'product'] as const).map((k) => (
            <label
              key={k}
              className={`flex h-12 cursor-pointer items-center justify-center rounded-md border text-[15px] font-semibold ${
                kind === k ? 'border-brand bg-brand text-on-brand' : 'border-border-strong bg-surface hover:bg-surface-2'
              }`}
            >
              <input type="radio" value={k} className="sr-only" {...register('kind')} />
              {itemKindLabel[k]}
            </label>
          ))}
        </div>
      </fieldset>

      {kind === 'service' ? (
        <SelectField label="Tipo de serviço" hint="Define os campos específicos do serviço." {...register('service_type_id')}>
          <option value="">Outro serviço (sem campos específicos)</option>
          {serviceTypes.map((t) => (
            <option key={t.id} value={t.id}>
              {t.name}
            </option>
          ))}
        </SelectField>
      ) : null}

      <TextField label="Descrição" error={formState.errors.description?.message} {...register('description')} />

      <div className="grid grid-cols-2 gap-3">
        <TextField label="Quantidade" inputMode="decimal" error={formState.errors.quantity?.message} {...register('quantity')} />
        <TextField label="Valor unitário (R$)" inputMode="decimal" placeholder="0,00" error={formState.errors.price?.message} {...register('price')} />
      </div>

      <SelectField label="Técnico responsável" error={formState.errors.technician_id?.message} {...register('technician_id')}>
        <option value="">Escolha</option>
        {users.map((u) => (
          <option key={u.id} value={u.id}>
            {u.name}
          </option>
        ))}
      </SelectField>

      {selectedType && selectedType.fields_schema.length > 0 ? (
        <div className="rounded-md border border-border bg-bg p-4">
          <p className="mb-3 text-sm font-semibold">{selectedType.name}: detalhes</p>
          <DynamicFields schema={selectedType.fields_schema} register={register} errors={formState.errors.fields as never} />
        </div>
      ) : null}

      <div className="flex flex-col-reverse gap-2 border-t border-border pt-4 sm:flex-row sm:items-center sm:justify-between">
        <p className="text-sm text-muted">
          Total do item: <span className="num font-semibold text-text">{preview === null ? '—' : formatMoney(preview)}</span>
        </p>
        <div className="flex flex-col-reverse gap-2 sm:flex-row">
          {onCancel ? (
            <Button variant="secondary" onClick={onCancel}>
              Cancelar
            </Button>
          ) : null}
          <Button type="submit" variant="secondary" loading={formState.isSubmitting} loadingText="Adicionando…">
            {submitLabel}
          </Button>
        </div>
      </div>
    </form>
  )
}

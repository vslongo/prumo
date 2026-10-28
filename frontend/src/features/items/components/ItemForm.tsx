import { zodResolver } from '@hookform/resolvers/zod'
import Box from '@mui/material/Box'
import Stack from '@mui/material/Stack'
import ToggleButton from '@mui/material/ToggleButton'
import ToggleButtonGroup from '@mui/material/ToggleButtonGroup'
import Typography from '@mui/material/Typography'
import { useEffect, useState } from 'react'
import { useForm, type Resolver } from 'react-hook-form'
import { z } from 'zod'
import { centsToInput, formatMoney, parseMoneyToCents } from '../../../shared/lib/format'
import { ApiError } from '../../../shared/lib/http'
import { Button } from '../../../shared/ui/Button'
import { SelectField, TextField } from '../../../shared/ui/Field'
import { ErrorBox } from '../../../shared/ui/States'
import { muted } from '../../../shared/ui/styles'
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
    <Stack component="form" onSubmit={submit} noValidate aria-label="Item" sx={{ gap: 2 }}>
      {error ? <ErrorBox message={error} /> : null}

      <Box component="fieldset" sx={{ m: 0, p: 0, border: 0, minWidth: 0 }}>
        <Box component="legend" sx={{ mb: 0.75, p: 0, fontSize: 14, fontWeight: 500 }}>
          Tipo de item
        </Box>
        <ToggleButtonGroup
          exclusive
          fullWidth
          value={kind}
          onChange={(_, k: FormValues['kind'] | null) => k && setValue('kind', k)}
          sx={{ gap: 1, '& .MuiToggleButtonGroup-grouped': { m: 0, border: '1px solid var(--color-border-strong) !important', borderRadius: '8px !important' } }}
        >
          {(['service', 'product'] as const).map((k) => (
            <ToggleButton
              key={k}
              value={k}
              sx={{
                height: 48,
                textTransform: 'none',
                fontSize: 15,
                fontWeight: 600,
                color: 'text.primary',
                bgcolor: 'background.paper',
                '&.Mui-selected, &.Mui-selected:hover': { bgcolor: 'primary.main', color: 'primary.contrastText', borderColor: 'primary.main !important' },
              }}
            >
              {itemKindLabel[k]}
            </ToggleButton>
          ))}
        </ToggleButtonGroup>
      </Box>

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

      <Box sx={{ display: 'grid', gridTemplateColumns: 'repeat(2, minmax(0, 1fr))', gap: 1.5 }}>
        <TextField label="Quantidade" inputMode="decimal" error={formState.errors.quantity?.message} {...register('quantity')} />
        <TextField label="Valor unitário (R$)" inputMode="decimal" placeholder="0,00" error={formState.errors.price?.message} {...register('price')} />
      </Box>

      <SelectField label="Técnico responsável" error={formState.errors.technician_id?.message} {...register('technician_id')}>
        <option value="">Escolha</option>
        {users.map((u) => (
          <option key={u.id} value={u.id}>
            {u.name}
          </option>
        ))}
      </SelectField>

      {selectedType && selectedType.fields_schema.length > 0 ? (
        <Box sx={{ border: 1, borderColor: 'divider', borderRadius: 1, bgcolor: 'background.default', p: 2 }}>
          <Typography variant="body2" sx={{ mb: 1.5, fontWeight: 600 }}>
            {selectedType.name}: detalhes
          </Typography>
          <DynamicFields schema={selectedType.fields_schema} register={register} errors={formState.errors.fields as never} />
        </Box>
      ) : null}

      <Stack direction={{ xs: 'column-reverse', sm: 'row' }} sx={{ gap: 1, alignItems: { sm: 'center' }, justifyContent: 'space-between', borderTop: 1, borderColor: 'divider', pt: 2 }}>
        <Typography variant="body2" sx={muted}>
          Total do item:{' '}
          <Box component="span" className="num" sx={{ fontWeight: 600, color: 'text.primary' }}>
            {preview === null ? '—' : formatMoney(preview)}
          </Box>
        </Typography>
        <Stack direction={{ xs: 'column-reverse', sm: 'row' }} sx={{ gap: 1 }}>
          {onCancel ? (
            <Button variant="secondary" onClick={onCancel}>
              Cancelar
            </Button>
          ) : null}
          <Button type="submit" variant="secondary" loading={formState.isSubmitting} loadingText="Adicionando…">
            {submitLabel}
          </Button>
        </Stack>
      </Stack>
    </Stack>
  )
}

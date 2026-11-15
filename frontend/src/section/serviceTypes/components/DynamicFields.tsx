import Box from '@mui/material/Box'
import type { FieldErrors, UseFormRegister } from 'react-hook-form'
import { CheckboxField, SelectField, TextField } from '../../../shared/ui/Field'
import type { FieldSchema } from '../api'

type Props = {
  schema: FieldSchema[]
  register: UseFormRegister<any>
  errors?: FieldErrors<Record<string, unknown>>
  prefix?: string
}

/** Formulário de campos específicos gerado do schema (CLAUDE.md §4: componente DynamicFields). */
export function DynamicFields({ schema, register, errors, prefix = 'fields' }: Props) {
  if (schema.length === 0) return null

  return (
    <Box sx={{ display: 'grid', gap: 2, gridTemplateColumns: { xs: 'minmax(0, 1fr)', sm: 'repeat(2, minmax(0, 1fr))' } }}>
      {schema.map((f) => {
        const name = `${prefix}.${f.key}`
        const error = (errors?.[f.key] as { message?: string } | undefined)?.message
        const label = f.unit ? `${f.label} (${f.unit})` : f.label
        const optional = f.required ? '' : ' · opcional'

        switch (f.type) {
          case 'boolean':
            return <CheckboxField key={f.key} label={f.label} sx={{ gridColumn: { sm: 'span 2' } }} {...register(name)} />
          case 'select':
            return (
              <SelectField key={f.key} label={label} hint={f.required ? undefined : 'Opcional'} error={error} defaultValue="" {...register(name)}>
                <option value="">Escolha{optional}</option>
                {(f.options ?? []).map((o) => (
                  <option key={o} value={o}>
                    {o}
                  </option>
                ))}
              </SelectField>
            )
          case 'number':
            return <TextField key={f.key} label={label} hint={f.required ? undefined : 'Opcional'} error={error} type="number" inputMode="decimal" step="any" {...register(name, { valueAsNumber: true })} />
          case 'date':
            return <TextField key={f.key} label={label} hint={f.required ? undefined : 'Opcional'} error={error} type="date" {...register(name)} />
          default:
            return <TextField key={f.key} label={label} hint={f.required ? undefined : 'Opcional'} error={error} {...register(name)} />
        }
      })}
    </Box>
  )
}

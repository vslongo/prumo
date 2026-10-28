import Checkbox from '@mui/material/Checkbox'
import FormControl from '@mui/material/FormControl'
import FormControlLabel from '@mui/material/FormControlLabel'
import FormHelperText from '@mui/material/FormHelperText'
import FormLabel from '@mui/material/FormLabel'
import InputAdornment from '@mui/material/InputAdornment'
import NativeSelect from '@mui/material/NativeSelect'
import OutlinedInput from '@mui/material/OutlinedInput'
import type { SxProps, Theme } from '@mui/material/styles'
import { CircleAlert, Search } from 'lucide-react'
import { useId, type InputHTMLAttributes, type ReactNode, type Ref, type SelectHTMLAttributes, type TextareaHTMLAttributes } from 'react'

type FieldProps = { label: string; hint?: string; error?: string; children: (id: string, describedBy: string | undefined) => ReactNode; sx?: SxProps<Theme> }

/** Rótulo sempre visível acima; ajuda e erro abaixo (docs/brand: "Campo"). */
export function Field({ label, hint, error, children, sx }: FieldProps) {
  const id = useId()
  const describedBy = error ? `${id}-error` : hint ? `${id}-hint` : undefined

  return (
    <FormControl fullWidth error={!!error} sx={sx}>
      <FormLabel htmlFor={id}>{label}</FormLabel>
      {children(id, describedBy)}
      {error ? (
        <FormHelperText id={`${id}-error`} role="alert" sx={{ display: 'flex', alignItems: 'flex-start', gap: 0.75 }}>
          <CircleAlert size={16} style={{ marginTop: 2, flexShrink: 0 }} aria-hidden />
          {error}
        </FormHelperText>
      ) : hint ? (
        <FormHelperText id={`${id}-hint`}>{hint}</FormHelperText>
      ) : null}
    </FormControl>
  )
}

type Common = { label: string; hint?: string; error?: string; sx?: SxProps<Theme>; startAdornment?: ReactNode }

// Os campos ficam não controlados (react-hook-form `register`): o ref vai direto para o elemento nativo.
export function TextField({ label, hint, error, sx, startAdornment, ref, ...rest }: Common & Omit<InputHTMLAttributes<HTMLInputElement>, 'color' | 'size'> & { ref?: Ref<HTMLInputElement> }) {
  return (
    <Field label={label} hint={hint} error={error} sx={sx}>
      {(id, describedBy) => (
        <OutlinedInput id={id} inputRef={ref} error={!!error} startAdornment={startAdornment} inputProps={{ ...rest, 'aria-invalid': !!error, 'aria-describedby': describedBy }} />
      )}
    </Field>
  )
}

export function SelectField({ label, hint, error, sx, children, ref, ...rest }: Common & Omit<SelectHTMLAttributes<HTMLSelectElement>, 'color'> & { ref?: Ref<HTMLSelectElement> }) {
  return (
    <Field label={label} hint={hint} error={error} sx={sx}>
      {(id, describedBy) => (
        <NativeSelect input={<OutlinedInput />} error={!!error} inputRef={ref} inputProps={{ ...rest, id, 'aria-invalid': !!error, 'aria-describedby': describedBy } as never}>
          {children}
        </NativeSelect>
      )}
    </Field>
  )
}

export function TextAreaField({ label, hint, error, sx, ref, ...rest }: Common & Omit<TextareaHTMLAttributes<HTMLTextAreaElement>, 'color'> & { ref?: Ref<HTMLTextAreaElement> }) {
  return (
    <Field label={label} hint={hint} error={error} sx={sx}>
      {(id, describedBy) => (
        <OutlinedInput id={id} multiline minRows={3} inputRef={ref} error={!!error} inputProps={{ ...rest, 'aria-invalid': !!error, 'aria-describedby': describedBy }} />
      )}
    </Field>
  )
}

/**
 * Checkbox do MUI. O ícone segue o estado interno do componente, então valor inicial vindo do
 * react-hook-form precisa chegar também por `defaultChecked`.
 */
export function CheckboxField({ label, sx, ref, checked, defaultChecked, onChange, onBlur, name, disabled }: { label: ReactNode; sx?: SxProps<Theme> } & InputHTMLAttributes<HTMLInputElement> & { ref?: Ref<HTMLInputElement> }) {
  return (
    <FormControlLabel
      sx={[{ minHeight: 44, ml: -1, mr: 0, gap: 0.5 }, ...(Array.isArray(sx) ? sx : [sx])]}
      label={label}
      control={<Checkbox name={name} checked={checked} defaultChecked={defaultChecked} onChange={onChange} disabled={disabled} slotProps={{ input: { ref, onBlur } as never }} />}
    />
  )
}

/** Lupa à esquerda do campo de busca. */
export function SearchAdornment() {
  return (
    <InputAdornment position="start">
      <Search size={18} aria-hidden />
    </InputAdornment>
  )
}

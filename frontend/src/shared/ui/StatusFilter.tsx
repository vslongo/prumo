import Chip from '@mui/material/Chip'
import Stack from '@mui/material/Stack'

type Option<T extends string> = { value: T | undefined; label: string }

/** Chips roláveis de filtro por status (docs/brand: acima da lista). */
export function StatusFilter<T extends string>({ options, value, onChange }: { options: Option<T>[]; value: T | undefined; onChange: (v: T | undefined) => void }) {
  return (
    <Stack role="radiogroup" aria-label="Filtrar por status" direction="row" sx={{ gap: 1, mx: { xs: -2, lg: 0 }, mb: 2, px: { xs: 2, lg: 0 }, pb: 0.5, overflowX: 'auto' }}>
      {options.map((o) => {
        const active = o.value === value
        return (
          <Chip
            key={o.label}
            role="radio"
            aria-checked={active}
            label={o.label}
            clickable
            color={active ? 'primary' : 'default'}
            variant={active ? 'filled' : 'outlined'}
            onClick={() => onChange(o.value)}
            sx={{ height: 40, flexShrink: 0, borderRadius: 0.5, fontSize: 14, fontWeight: 600, px: 0.5, ...(active ? {} : { bgcolor: 'background.paper', borderColor: 'var(--color-border-strong)' }) }}
          />
        )
      })}
    </Stack>
  )
}

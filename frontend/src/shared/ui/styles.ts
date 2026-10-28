import type { SxProps, Theme } from '@mui/material/styles'

/** Bloco recuado dentro de um card (fieldset de veículo, campos do tipo de serviço). */
export const fieldsetSx = { gap: 2, m: 0, minWidth: 0, border: 1, borderColor: 'divider', borderRadius: 1, bgcolor: 'background.default', p: 2 } satisfies SxProps<Theme>

export const legendSx = { px: 0.5, fontSize: 14, fontWeight: 600 } satisfies SxProps<Theme>

export const uppercase = { '& input': { textTransform: 'uppercase' } } satisfies SxProps<Theme>

/** Linha tocável com borda (seleção de veículo, resultado de busca). */
export const optionRowSx = {
  display: 'flex',
  minHeight: 56,
  width: '100%',
  alignItems: 'center',
  justifyContent: 'flex-start',
  gap: 1.5,
  border: 1,
  borderColor: 'var(--color-border-strong)',
  borderRadius: 1,
  bgcolor: 'background.paper',
  px: 1.5,
  textAlign: 'left',
  fontSize: 16,
  '&:hover': { bgcolor: 'action.hover' },
  '&[aria-checked="true"]': { borderColor: 'primary.main', boxShadow: (t) => `0 0 0 1px ${t.palette.primary.main}`, bgcolor: 'background.paper' },
} satisfies SxProps<Theme>

export const muted = { color: 'text.secondary' } as const

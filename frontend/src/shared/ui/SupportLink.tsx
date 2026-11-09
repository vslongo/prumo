import MuiLink from '@mui/material/Link'
import Typography from '@mui/material/Typography'
import type { SxProps, Theme } from '@mui/material/styles'

/** Só dígitos, com DDI (ex.: 5547999990000). Vazio = sem canal configurado, o link some. */
const number = String(import.meta.env.VITE_SUPPORT_WHATSAPP ?? '').replace(/\D/g, '')

/** Canal de suporte: abre o WhatsApp do Prumo com a mensagem já começada. */
export function SupportLink({ sx }: { sx?: SxProps<Theme> }) {
  if (!number) return null

  return (
    <Typography variant="body2" color="text.secondary" sx={sx}>
      Precisa de ajuda?{' '}
      <MuiLink href={`https://wa.me/${number}?text=${encodeURIComponent('Oi! Preciso de ajuda com o Prumo.')}`} target="_blank" rel="noopener noreferrer">
        Fale com o suporte no WhatsApp
      </MuiLink>
    </Typography>
  )
}

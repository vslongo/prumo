import Alert from '@mui/material/Alert'
import MuiLink from '@mui/material/Link'
import { Link } from 'react-router'
import { formatDate } from '../../../shared/lib/format'
import type { User } from '../api'

/** Dono ainda no prazo: lembra de ativar o 2FA antes que vire obrigatório. */
export function TwoFactorBanner({ me }: { me: User }) {
  const { required_from: from, blocked } = me.two_factor
  if (!from || blocked) return null

  return (
    <Alert severity="info" role="status" sx={{ mb: 2.5, alignItems: 'center', bgcolor: 'info.light', color: 'info.main', '& .MuiAlert-icon': { color: 'inherit' } }}>
      Proteja a conta da oficina: a verificação em duas etapas passa a ser obrigatória em {formatDate(from)}.{' '}
      <MuiLink component={Link} to="/seguranca" sx={{ fontWeight: 600, color: 'inherit' }}>
        Ativar agora
      </MuiLink>
    </Alert>
  )
}

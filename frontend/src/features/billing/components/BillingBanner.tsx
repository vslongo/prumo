import Alert from '@mui/material/Alert'
import MuiLink from '@mui/material/Link'
import { Link } from 'react-router'
import { formatDate } from '../../../shared/lib/format'
import { daysUntil, useAccess, type Access } from '../api'

/** Quantos dias antes do fim do trial o aviso aparece. */
const TRIAL_WARNING_DAYS = 5

type Notice = { severity: 'info' | 'warning' | 'error'; text: string; cta: string }

export function billingNotice(a: Access, now: Date = new Date()): Notice | null {
  const days = daysUntil(a.read_only_from, now)
  switch (a.state) {
    case 'trial':
      if (a.subscription_status === 'pending' || days > TRIAL_WARNING_DAYS) return null
      return { severity: 'info', text: days <= 1 ? 'Seu teste grátis acaba hoje.' : `Seu teste grátis acaba em ${days} dias.`, cta: 'Assinar' }
    case 'past_due':
      return { severity: 'warning', text: `Pagamento em atraso. Regularize até ${formatDate(a.read_only_from)} para continuar usando normalmente.`, cta: 'Ver fatura' }
    case 'canceled':
      return { severity: 'info', text: `Assinatura cancelada. A oficina usa normalmente até ${formatDate(a.paid_until)}.`, cta: 'Assinar de novo' }
    case 'read_only':
      return { severity: 'error', text: 'Modo somente leitura: dá para consultar tudo, mas não criar nem editar. Seus dados estão guardados.', cta: 'Regularizar' }
    default:
      return null
  }
}

/** Faixa no topo do app: trial acabando, atraso, cancelada ou somente leitura. Só o dono vê o atalho para a assinatura. */
export function BillingBanner({ isOwner }: { isOwner: boolean }) {
  const access = useAccess()
  const notice = access.data ? billingNotice(access.data) : null
  if (!notice) return null

  return (
    <Alert severity={notice.severity} role={notice.severity === 'error' ? 'alert' : 'status'} sx={{ mb: 2.5, alignItems: 'center', bgcolor: `${notice.severity}.light`, color: `${notice.severity}.main`, '& .MuiAlert-icon': { color: 'inherit' } }}>
      {notice.text}{' '}
      {isOwner ? (
        <MuiLink component={Link} to="/configuracoes?aba=assinatura" sx={{ fontWeight: 600, color: 'inherit' }}>
          {notice.cta}
        </MuiLink>
      ) : (
        'Fale com o dono da oficina.'
      )}
    </Alert>
  )
}

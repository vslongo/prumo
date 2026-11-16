import Box from '@mui/material/Box'
import ButtonBase from '@mui/material/ButtonBase'
import CircularProgress from '@mui/material/CircularProgress'
import Stack from '@mui/material/Stack'
import Typography from '@mui/material/Typography'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ExternalLink } from 'lucide-react'
import { useState } from 'react'
import { formatDate, formatMoney } from '../../../shared/lib/format'
import { ApiError } from '../../../shared/lib/http'
import { Button } from '../../../shared/ui/Button'
import { ConfirmDialog } from '../../../shared/ui/ConfirmDialog'
import { TextField } from '../../../shared/ui/Field'
import { Card, CardTitle, ErrorBox, ListSkeleton } from '../../../shared/ui/States'
import { muted, optionRowSx } from '../../../shared/ui/styles'
import { useToast } from '../../../shared/ui/Toast'
import { billingApi, type BillingOverview, type PlanInfo, type PlanKey } from '../api'

const apiMessage = (e: unknown) => (e instanceof ApiError ? (Object.values(e.errors)[0]?.[0] ?? e.message) : 'Não deu para salvar. Tente de novo.')

const usersLabel = (max: number | null) => (max === null ? 'Usuários ilimitados' : max === 1 ? '1 usuário' : `Até ${max} usuários`)

function statusLine(o: BillingOverview): string {
  const a = o.access
  const s = o.subscription
  const plan = o.plans.find((p) => p.key === s?.plan)?.name
  switch (a.state) {
    case 'trial':
      return s?.status === 'pending' ? `Teste grátis até ${formatDate(a.trial_ends_at)}. Plano ${plan} escolhido: a primeira fatura vence no fim do teste.` : `Teste grátis até ${formatDate(a.trial_ends_at)}, com tudo liberado.`
    case 'active':
      return `Plano ${plan} ativo${a.paid_until ? `, pago até ${formatDate(a.paid_until)}` : ''}.`
    case 'past_due':
      return `Plano ${plan} com pagamento em atraso. Regularize até ${formatDate(a.read_only_from)} para não ficar em somente leitura.`
    case 'canceled':
      return `Assinatura cancelada. A oficina usa normalmente até ${formatDate(a.paid_until)}.`
    default:
      return s?.status === 'pending' ? `Aguardando o pagamento do plano ${plan} para liberar a oficina.` : 'Somente leitura: escolha um plano para voltar a criar e editar. Seus dados estão guardados.'
  }
}

function PlanPicker({ plans, value, onChange, activeUsers }: { plans: PlanInfo[]; value: PlanKey | null; onChange: (p: PlanKey) => void; activeUsers: number }) {
  return (
    <Stack role="radiogroup" aria-label="Plano" sx={{ gap: 1 }}>
      {plans.map((p) => {
        const tooSmall = p.max_users !== null && activeUsers > p.max_users
        return (
          <ButtonBase key={p.key} role="radio" aria-checked={value === p.key} disabled={tooSmall} onClick={() => onChange(p.key)} sx={[optionRowSx, { justifyContent: 'space-between', opacity: tooSmall ? 0.5 : 1 }]}>
            <Box component="span">
              <Box component="span" sx={{ display: 'block', fontWeight: 600 }}>
                {p.name}
              </Box>
              <Box component="span" sx={{ display: 'block', fontSize: 14, ...muted }}>
                {usersLabel(p.max_users)}
                {tooSmall ? ` · a oficina tem ${activeUsers} ativos` : ''}
              </Box>
            </Box>
            <Box component="span" className="num" sx={{ fontWeight: 600, whiteSpace: 'nowrap', flexShrink: 0 }}>
              {formatMoney(p.price_cents)}
              <Box component="span" sx={{ fontWeight: 400, fontSize: 14, ...muted }}>
                /mês
              </Box>
            </Box>
          </ButtonBase>
        )
      })}
    </Stack>
  )
}

/** Assinatura do Prumo (só o dono): escolher plano, pagar a fatura no Asaas, trocar de plano e cancelar. */
export function BillingCard() {
  const queryClient = useQueryClient()
  const toast = useToast()
  const overview = useQuery({
    queryKey: ['billing', 'overview'],
    queryFn: billingApi.overview,
    // Enquanto o Asaas gera a fatura (fila), confere de novo.
    refetchInterval: (q) => (q.state.data?.subscription?.provisioning ? 2000 : false),
  })
  const [plan, setPlan] = useState<PlanKey | null>(null)
  const [document, setDocument] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [confirmCancel, setConfirmCancel] = useState(false)

  const apply = (data: BillingOverview) => {
    queryClient.setQueryData(['billing', 'overview'], data)
    queryClient.setQueryData(['billing', 'access'], data.access)
    setError(null)
  }

  const subscribe = useMutation({ mutationFn: (p: PlanKey) => billingApi.subscribe(p, document), onSuccess: apply, onError: (e) => setError(apiMessage(e)) })
  const change = useMutation({
    mutationFn: (p: PlanKey) => billingApi.changePlan(p),
    onSuccess: (d) => (apply(d), setPlan(null), toast('Plano alterado. O valor novo vale a partir da próxima fatura.')),
    onError: (e) => setError(apiMessage(e)),
  })
  const cancel = useMutation({
    mutationFn: billingApi.cancel,
    onSuccess: (d) => (apply(d), setConfirmCancel(false), toast('Assinatura cancelada.')),
    onError: (e) => (setConfirmCancel(false), setError(apiMessage(e))),
  })

  if (overview.isLoading) return <Card><ListSkeleton rows={3} /></Card>
  if (overview.isError || !overview.data) return <ErrorBox message="Não deu para carregar a assinatura." onRetry={() => overview.refetch()} />

  const o = overview.data
  const s = o.subscription
  // Sem assinatura, cancelada ou pedido recusado pelo Asaas: formulário de assinar.
  const canSubscribe = !s || s.status === 'canceled' || (s.status === 'pending' && !s.provisioning && !s.invoice_url)
  const manageable = s && !canSubscribe && s.status !== 'canceled'
  const selected = plan ?? (manageable ? s.plan : null)

  return (
    <Card>
      <CardTitle>Assinatura</CardTitle>
      <Typography sx={{ mb: 2 }}>{statusLine(o)}</Typography>
      {error ? <Box sx={{ mb: 2 }}><ErrorBox message={error} /></Box> : null}
      {s?.error && canSubscribe ? <Box sx={{ mb: 2 }}><ErrorBox message={s.error} /></Box> : null}

      {s?.provisioning ? (
        <Stack direction="row" role="status" sx={{ gap: 1.5, alignItems: 'center', mb: 2 }}>
          <CircularProgress size={20} aria-hidden />
          <Typography>Gerando sua fatura…</Typography>
        </Stack>
      ) : null}

      {s?.invoice_url ? (
        <Stack sx={{ gap: 1, mb: 2.5 }}>
          <Button href={s.invoice_url} target="_blank" rel="noreferrer" full icon={<ExternalLink size={18} aria-hidden />}>
            Pagar fatura
          </Button>
          <Typography variant="body2" sx={muted}>
            Pix, boleto ou cartão, na página segura do Asaas. A liberação é automática assim que o pagamento cair.
          </Typography>
        </Stack>
      ) : null}

      {canSubscribe ? (
        <Stack sx={{ gap: 2 }}>
          <PlanPicker plans={o.plans} value={selected} onChange={setPlan} activeUsers={o.active_users} />
          <TextField label="CPF ou CNPJ do responsável" hint="Usado só pelo Asaas para emitir a cobrança. Não guardamos aqui." inputMode="text" autoComplete="off" value={document} onChange={(e) => setDocument(e.target.value)} />
          <Button
            onClick={() => (!selected ? setError('Escolha um plano.') : document.replace(/\W/g, '').length < 11 ? setError('Informe o CPF ou CNPJ.') : subscribe.mutate(selected))}
            loading={subscribe.isPending}
            loadingText="Enviando…"
          >
            {selected ? `Assinar plano ${o.plans.find((p) => p.key === selected)?.name}` : 'Assinar'}
          </Button>
          {o.access.state === 'trial' ? (
            <Typography variant="body2" sx={muted}>
              Assinando agora você não perde os dias de teste: a primeira fatura vence em {formatDate(o.access.trial_ends_at)}.
            </Typography>
          ) : null}
        </Stack>
      ) : null}

      {manageable ? (
        <Stack sx={{ gap: 2, borderTop: 1, borderColor: 'divider', pt: 2.5 }}>
          <Typography sx={{ fontWeight: 600 }}>Trocar de plano</Typography>
          <PlanPicker plans={o.plans} value={selected} onChange={setPlan} activeUsers={o.active_users} />
          <Button variant="secondary" disabled={!plan || plan === s.plan} loading={change.isPending} onClick={() => plan && change.mutate(plan)}>
            Trocar plano
          </Button>
          <Button variant="ghost" tone="danger" onClick={() => setConfirmCancel(true)}>
            Cancelar assinatura
          </Button>
        </Stack>
      ) : null}

      <ConfirmDialog
        open={confirmCancel}
        title="Cancelar a assinatura?"
        text={o.access.paid_until ? `As cobranças param. A oficina usa normalmente até ${formatDate(o.access.paid_until)}; depois fica em somente leitura. Nenhum dado é apagado.` : 'As cobranças param e a oficina fica em somente leitura. Nenhum dado é apagado.'}
        confirmLabel="Cancelar assinatura"
        danger
        loading={cancel.isPending}
        onConfirm={() => cancel.mutate()}
        onClose={() => setConfirmCancel(false)}
      />
    </Card>
  )
}

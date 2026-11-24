import Alert from '@mui/material/Alert'
import Chip from '@mui/material/Chip'
import CircularProgress from '@mui/material/CircularProgress'
import MuiLink from '@mui/material/Link'
import Stack from '@mui/material/Stack'
import Typography from '@mui/material/Typography'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { Download } from 'lucide-react'
import { useState } from 'react'
import { formatMoney, formatWhen } from '../../../shared/lib/format'
import { ApiError } from '../../../shared/lib/http'
import { Link } from '../../../shared/lib/navigation'
import { invoiceStatusLabel, type ServiceOrderStatus } from '../../../shared/lib/status'
import { Button } from '../../../shared/ui/Button'
import { ConfirmDialog } from '../../../shared/ui/ConfirmDialog'
import { SelectField, TextAreaField } from '../../../shared/ui/Field'
import { Card, CardTitle, Detail, DetailGrid, ErrorBox, ListSkeleton } from '../../../shared/ui/States'
import { muted } from '../../../shared/ui/styles'
import { useToast } from '../../../shared/ui/Toast'
import { useMe } from '../../auth/api'
import { cancelReasonLabel, invoiceApi, invoiceInFlight, useOrderInvoices, type CancelReason, type Invoice } from '../api'

const firstError = (e: unknown) => (e instanceof ApiError ? (Object.values(e.errors)[0]?.[0] ?? e.message) : 'Não deu para falar com o servidor. Tente de novo.')

/** Erro de configuração fiscal: o texto leva para a aba certa. */
const needsSetup = (e: unknown) => e instanceof ApiError && 'fiscal_profile' in e.errors

function CancelDialog({ invoice, open, onClose }: { invoice: Invoice; open: boolean; onClose: () => void }) {
  const queryClient = useQueryClient()
  const toast = useToast()
  const [reason, setReason] = useState<CancelReason>('1')
  const [text, setText] = useState('')
  const [error, setError] = useState<string | null>(null)
  const cancel = useMutation({
    mutationFn: () => invoiceApi.cancel(invoice.id, reason, text.trim()),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['invoices', invoice.service_order_id] })
      toast('Pedido de cancelamento enviado.')
      onClose()
    },
    onError: (e) => setError(firstError(e)),
  })

  return (
    <ConfirmDialog open={open} title="Cancelar a nota fiscal?" confirmLabel="Cancelar nota" danger loading={cancel.isPending} onClose={onClose}
      onConfirm={() => (text.trim().length < 15 ? setError('Explique o motivo com pelo menos 15 letras.') : cancel.mutate())}>
      <Stack sx={{ gap: 2, mt: 2 }}>
        {error ? <ErrorBox message={error} /> : null}
        <SelectField label="Motivo" value={reason} onChange={(e) => setReason(e.target.value as CancelReason)}>
          {(Object.keys(cancelReasonLabel) as CancelReason[]).map((r) => (
            <option key={r} value={r}>{cancelReasonLabel[r]}</option>
          ))}
        </SelectField>
        <TextAreaField label="Explique o motivo" hint="Vai para a Sefin junto com o cancelamento." rows={3} maxLength={255} value={text} onChange={(e) => setText(e.target.value)} />
      </Stack>
    </ConfirmDialog>
  )
}

/** NFS-e da OS: emitir (OS entregue/fechada), acompanhar, baixar PDF/XML e cancelar. Técnico só vê. */
export function InvoiceCard({ orderId, orderStatus }: { orderId: string; orderStatus: ServiceOrderStatus }) {
  const queryClient = useQueryClient()
  const toast = useToast()
  const { data: me } = useMe()
  const invoices = useOrderInvoices(orderId)
  const [cancelOpen, setCancelOpen] = useState(false)
  const canAct = !!me && me.role !== 'technician'
  const billable = orderStatus === 'delivered' || orderStatus === 'closed'

  const issue = useMutation({
    mutationFn: () => invoiceApi.issue(orderId),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['invoices', orderId] }),
  })

  const download = async (invoice: Invoice, type: 'pdf' | 'xml') => {
    try {
      window.open(await invoiceApi.fileUrl(invoice.id, type), '_blank', 'noopener')
    } catch (e) {
      toast(firstError(e))
    }
  }

  if (invoices.isLoading) return <Card><ListSkeleton rows={2} /></Card>
  if (invoices.isError) return <Card><ErrorBox message="Não deu para carregar a nota fiscal." onRetry={() => invoices.refetch()} /></Card>

  const list = invoices.data ?? []
  const current = list.find((i) => i.status !== 'canceled')
  const canceled = list.filter((i) => i.status === 'canceled')
  if (!current && canceled.length === 0 && !billable) return null

  return (
    <Card>
      <CardTitle action={current ? <Chip size="small" label={invoiceStatusLabel[current.status]} color={current.status === 'authorized' ? 'success' : current.status === 'error' ? 'error' : 'default'} /> : null}>
        Nota fiscal
      </CardTitle>

      {current?.environment === 'restricted' ? (
        <Typography variant="body2" sx={{ mb: 1, ...muted }}>Homologação: nota de teste, sem valor fiscal.</Typography>
      ) : null}

      {current && invoiceInFlight(current.status) ? (
        <Stack direction="row" sx={{ alignItems: 'center', gap: 1 }}>
          <CircularProgress size={18} aria-hidden />
          <Typography>{current.status === 'cancel_requested' ? 'Cancelando na Sefin…' : 'Emitindo na Sefin…'}</Typography>
        </Stack>
      ) : null}

      {current?.status === 'error' ? (
        <Stack sx={{ gap: 1.5 }}>
          <Alert severity="error">{current.error_message ?? 'A Sefin recusou a nota.'}{current.error_code ? ` (${current.error_code})` : ''}</Alert>
          {canAct ? <Button full loading={issue.isPending} onClick={() => issue.mutate()}>Tentar de novo</Button> : null}
        </Stack>
      ) : null}

      {current?.status === 'authorized' ? (
        <Stack sx={{ gap: 1.5 }}>
          {current.error_message ? <Alert severity="warning">{current.error_message}</Alert> : null}
          <DetailGrid>
            <Detail label="Número">{current.nfse_number ?? '—'}</Detail>
            <Detail label="Valor">{formatMoney(current.amount_cents)}</Detail>
            <Detail label="Emitida">{formatWhen(current.authorized_at)}</Detail>
            <Detail label="Peças">{current.include_products ? 'Incluídas' : 'Fora da nota'}</Detail>
          </DetailGrid>
          <Stack direction="row" sx={{ gap: 1 }}>
            <Button variant="secondary" icon={<Download size={16} aria-hidden />} disabled={!current.has_pdf} onClick={() => download(current, 'pdf')}>
              {current.has_pdf ? 'PDF' : 'Gerando PDF…'}
            </Button>
            <Button variant="secondary" icon={<Download size={16} aria-hidden />} onClick={() => download(current, 'xml')}>XML</Button>
          </Stack>
          {canAct ? <Button variant="ghost" tone="danger" full onClick={() => setCancelOpen(true)}>Cancelar nota</Button> : null}
          <CancelDialog invoice={current} open={cancelOpen} onClose={() => setCancelOpen(false)} />
        </Stack>
      ) : null}

      {!current && billable ? (
        <Stack sx={{ gap: 1.5 }}>
          {issue.isError ? (
            <Alert severity="error">
              {firstError(issue.error)}{' '}
              {needsSetup(issue.error) ? <MuiLink component={Link} to="/configuracoes?aba=fiscal">Abrir configurações</MuiLink> : null}
            </Alert>
          ) : null}
          {canAct ? (
            <Button full loading={issue.isPending} loadingText="Enviando…" onClick={() => issue.mutate()}>Emitir nota fiscal</Button>
          ) : (
            <Typography sx={muted}>Sem nota fiscal. O balcão ou o administrador emite.</Typography>
          )}
        </Stack>
      ) : null}

      {canceled.length > 0 ? (
        <Stack sx={{ gap: 0.5, mt: 2, pt: 2, borderTop: 1, borderColor: 'divider' }}>
          {canceled.map((i) => (
            <Stack key={i.id} direction="row" sx={{ alignItems: 'center', justifyContent: 'space-between', gap: 1 }}>
              <Typography variant="body2" sx={muted}>Nº {i.nfse_number ?? '—'} cancelada {formatWhen(i.canceled_at)}</Typography>
              {i.has_pdf ? <Button variant="ghost" size="small" onClick={() => download(i, 'pdf')}>PDF</Button> : null}
            </Stack>
          ))}
        </Stack>
      ) : null}
    </Card>
  )
}

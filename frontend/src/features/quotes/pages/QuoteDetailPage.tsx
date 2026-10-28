import Alert from '@mui/material/Alert'
import Box from '@mui/material/Box'
import Skeleton from '@mui/material/Skeleton'
import Stack from '@mui/material/Stack'
import Typography from '@mui/material/Typography'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ClipboardList, Copy, MessageCircle, Plus, Send, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { useParams } from 'react-router'
import { formatKm, formatPlate, formatWhen } from '../../../shared/lib/format'
import { ApiError } from '../../../shared/lib/http'
import { BackLink } from '../../../shared/ui/BackLink'
import { Button, ButtonLink } from '../../../shared/ui/Button'
import { TextField } from '../../../shared/ui/Field'
import { Card, CardTitle, Detail, DetailGrid, ErrorBox, PageHeader } from '../../../shared/ui/States'
import { muted } from '../../../shared/ui/styles'
import { QuoteStatusBadge } from '../../../shared/ui/StatusBadge'
import { useToast } from '../../../shared/ui/Toast'
import { useUsers } from '../../auth/api'
import { ItemForm } from '../../items/components/ItemForm'
import { ItemList, ItemsTotal } from '../../items/components/ItemList'
import { serviceOrdersApi } from '../../serviceOrders/api'
import { useServiceTypes } from '../../serviceTypes/api'
import { vehicleName } from '../../vehicles/api'
import { quotesApi, type SentQuote } from '../api'

const editable = ['draft', 'sent', 'viewed']
const sendable = ['draft', 'sent', 'viewed', 'expired']

export function QuoteDetailPage() {
  const { id = '' } = useParams()
  const queryClient = useQueryClient()
  const toast = useToast()
  const users = useUsers()
  const types = useServiceTypes()
  const [adding, setAdding] = useState(false)
  const [sent, setSent] = useState<SentQuote | null>(null)
  const [actionError, setActionError] = useState<string | null>(null)

  const quote = useQuery({ queryKey: ['quotes', id], queryFn: () => quotesApi.get(id) })
  const order = useQuery({
    queryKey: ['service-orders', 'by-quote', id],
    queryFn: async () => (await serviceOrdersApi.list({ quoteId: id })).data[0] ?? null,
    enabled: quote.data?.status === 'approved',
    // A OS nasce pela fila logo após a aprovação; confere de novo até aparecer.
    refetchInterval: (q) => (q.state.data ? false : 3000),
  })

  const refresh = (data: Awaited<ReturnType<typeof quotesApi.get>>) => {
    queryClient.setQueryData(['quotes', id], data)
    queryClient.invalidateQueries({ queryKey: ['quotes', 'list'] })
  }

  const fail = (e: unknown) => setActionError(e instanceof ApiError ? (Object.values(e.errors)[0]?.[0] ?? e.message) : 'Não deu para salvar. Tente de novo.')

  const send = useMutation({
    mutationFn: () => quotesApi.send(id),
    onMutate: () => setActionError(null),
    onSuccess: (res) => {
      refresh(res.data)
      setSent(res)
      toast('Link do orçamento gerado.')
    },
    onError: fail,
  })

  const remove = useMutation({
    mutationFn: (itemId: string) => quotesApi.removeItem(id, itemId),
    onMutate: () => setActionError(null),
    onSuccess: (data) => {
      refresh(data)
      setSent(null)
    },
    onError: fail,
  })

  if (quote.isLoading) {
    return (
      <>
        <Skeleton height={40} width={256} sx={{ mb: 3 }} />
        <Skeleton height={288} />
      </>
    )
  }
  if (quote.isError || !quote.data) return <ErrorBox message="Não deu para carregar o orçamento." onRetry={() => quote.refetch()} />

  const q = quote.data
  const isEditable = editable.includes(q.status)
  const wasSent = q.status === 'sent' || q.status === 'viewed'
  const approvedIds = new Set(q.approval?.approved_item_ids ?? [])

  const copy = async (text: string) => {
    await navigator.clipboard?.writeText(text).catch(() => undefined)
    toast('Link copiado.')
  }

  return (
    <>
      <PageHeader
        back={<BackLink to="/orcamentos" label="Orçamentos" />}
        title={q.vehicle ? `${formatPlate(q.vehicle.plate)} · ${vehicleName(q.vehicle)}` : 'Orçamento'}
        subtitle={
          <Box component="span" sx={{ display: 'flex', flexWrap: 'wrap', alignItems: 'center', gap: 1 }}>
            <QuoteStatusBadge status={q.status} />
            <span>{q.customer?.name}</span>
            {q.version > 1 ? <span>· versão {q.version}</span> : null}
          </Box>
        }
      />

      <Box sx={{ display: 'grid', gap: 2, gridTemplateColumns: { xs: 'minmax(0, 1fr)', lg: 'minmax(0, 1fr) 340px' }, alignItems: { lg: 'start' } }}>
        <Stack sx={{ gap: 2 }}>
          {q.status === 'approved' ? (
            <Stack role="status" direction={{ xs: 'column', sm: 'row' }} sx={{ gap: 1.5, alignItems: { sm: 'center' }, justifyContent: 'space-between', borderRadius: 1.5, bgcolor: 'success.light', color: 'success.main', p: 2 }}>
              <Typography>
                <strong>{q.approval?.signer_name}</strong> aprovou e assinou {formatWhen(q.approval?.approved_at).toLowerCase()}.
                {q.items && approvedIds.size < q.items.length ? ` ${approvedIds.size} de ${q.items.length} itens aprovados.` : ''}
              </Typography>
              {order.data ? (
                <ButtonLink to={`/os/${order.data.id}`} variant="secondary" icon={<ClipboardList size={18} aria-hidden />} sx={{ flexShrink: 0 }}>
                  Ver OS {order.data.number}
                </ButtonLink>
              ) : (
                <Typography variant="body2">Gerando a OS…</Typography>
              )}
            </Stack>
          ) : null}
          {q.status === 'rejected' ? (
            <Box role="status" sx={{ borderRadius: 1.5, bgcolor: 'error.light', color: 'error.main', p: 2 }}>
              Cliente recusou {formatWhen(q.decided_at).toLowerCase()}.{q.rejection_reason ? ` Motivo: “${q.rejection_reason}”` : ''}
            </Box>
          ) : null}

          <Card>
            <CardTitle
              action={
                isEditable && !adding ? (
                  <Button variant="ghost" icon={<Plus size={18} aria-hidden />} onClick={() => setAdding(true)}>
                    Adicionar item
                  </Button>
                ) : null
              }
            >
              Itens
            </CardTitle>
            {actionError ? (
              <Box sx={{ mb: 2 }}>
                <ErrorBox message={actionError} />
              </Box>
            ) : null}
            <ItemList
              items={q.items ?? []}
              users={users.data ?? []}
              aside={
                q.status === 'approved'
                  ? (item) => (
                      <Box
                        component="span"
                        title={approvedIds.has(item.id) ? 'Aprovado' : 'Não aprovado'}
                        aria-label={approvedIds.has(item.id) ? 'Aprovado' : 'Não aprovado'}
                        sx={{ mt: 0.75, width: 10, height: 10, flexShrink: 0, borderRadius: '50%', bgcolor: approvedIds.has(item.id) ? 'success.main' : 'var(--color-border-strong)' }}
                      />
                    )
                  : undefined
              }
              actions={
                isEditable && (q.items?.length ?? 0) > 1
                  ? (item) => (
                      <Button variant="ghost" tone="danger" size="small" icon={<Trash2 size={16} aria-hidden />} loading={remove.isPending && remove.variables === item.id} onClick={() => remove.mutate(item.id)}>
                        Remover
                      </Button>
                    )
                  : undefined
              }
            />
            <ItemsTotal cents={q.total_cents} />

            {adding ? (
              <Box sx={{ mt: 2.5, borderTop: 1, borderColor: 'divider', pt: 2.5 }}>
                {wasSent ? (
                  <Alert severity="warning" icon={false} sx={{ mb: 2, bgcolor: 'warning.light', color: 'warning.main', fontSize: 14 }}>
                    O cliente já recebeu este orçamento. Ao mudar, o link antigo deixa de valer e será preciso enviar de novo.
                  </Alert>
                ) : null}
                <ItemForm
                  serviceTypes={types.data ?? []}
                  users={users.data ?? []}
                  onCancel={() => setAdding(false)}
                  onSubmit={async (item) => {
                    refresh(await quotesApi.addItem(id, item))
                    setSent(null)
                    setAdding(false)
                    toast('Item adicionado.')
                  }}
                />
              </Box>
            ) : null}
          </Card>
        </Stack>

        <Stack component="aside" sx={{ gap: 2, position: { lg: 'sticky' }, top: { lg: 32 } }}>
          <Card>
            <DetailGrid>
              <Detail label="Criado">{formatWhen(q.created_at)}</Detail>
              <Detail label="Km">{formatKm(q.mileage)}</Detail>
              <Detail label="Enviado">{formatWhen(q.sent_at)}</Detail>
              <Detail label="Link vale até">{formatWhen(q.link_expires_at)}</Detail>
            </DetailGrid>
            {q.notes ? <Typography variant="body2" sx={{ mt: 2, borderTop: 1, borderColor: 'divider', pt: 2, whiteSpace: 'pre-line' }}>{q.notes}</Typography> : null}
          </Card>

          {sendable.includes(q.status) ? (
            <Card>
              <CardTitle>Enviar ao cliente</CardTitle>
              {sent ? (
                <Stack sx={{ gap: 1.5 }}>
                  <TextField label="Link de aprovação" readOnly value={sent.link} onFocus={(e) => e.target.select()} sx={{ '& .MuiOutlinedInput-root': { bgcolor: 'background.default', fontSize: 14 } }} />
                  {sent.whatsapp_url ? (
                    <Button href={sent.whatsapp_url} target="_blank" rel="noreferrer" full icon={<MessageCircle size={18} aria-hidden />}>
                      Enviar pelo WhatsApp
                    </Button>
                  ) : (
                    <Typography variant="body2" sx={muted}>
                      Cliente sem celular cadastrado. Copie o link e envie como preferir.
                    </Typography>
                  )}
                  <Button variant="secondary" full icon={<Copy size={18} aria-hidden />} onClick={() => copy(sent.link)}>
                    Copiar link
                  </Button>
                </Stack>
              ) : (
                <>
                  <Typography variant="body2" sx={{ mb: 2, ...muted }}>
                    {wasSent ? 'Gerar um link novo invalida o anterior.' : 'O cliente recebe um link para ver, aprovar e assinar. Vale por 7 dias.'}
                  </Typography>
                  <Button full icon={<Send size={18} aria-hidden />} loading={send.isPending} loadingText="Gerando link…" onClick={() => send.mutate()}>
                    {wasSent || q.status === 'expired' ? 'Reenviar link' : 'Gerar link de aprovação'}
                  </Button>
                </>
              )}
            </Card>
          ) : null}
        </Stack>
      </Box>
    </>
  )
}

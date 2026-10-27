import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ClipboardList, Copy, MessageCircle, Plus, Send, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { Link, useParams } from 'react-router'
import { formatKm, formatPlate, formatWhen } from '../../../shared/lib/format'
import { ApiError } from '../../../shared/lib/http'
import { BackLink } from '../../../shared/ui/BackLink'
import { Button, buttonClass } from '../../../shared/ui/Button'
import { Card, CardTitle, Detail, ErrorBox, PageHeader, Skeleton } from '../../../shared/ui/States'
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
        <Skeleton className="mb-6 h-10 w-64" />
        <Skeleton className="h-72" />
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
          <span className="flex flex-wrap items-center gap-2">
            <QuoteStatusBadge status={q.status} />
            <span>{q.customer?.name}</span>
            {q.version > 1 ? <span>· versão {q.version}</span> : null}
          </span>
        }
      />

      <div className="grid gap-4 lg:grid-cols-[1fr_340px] lg:items-start">
        <div className="flex flex-col gap-4">
          {q.status === 'approved' ? (
            <div role="status" className="flex flex-col gap-3 rounded-lg border border-success/30 bg-success-soft p-4 text-success sm:flex-row sm:items-center sm:justify-between">
              <p>
                <strong>{q.approval?.signer_name}</strong> aprovou e assinou {formatWhen(q.approval?.approved_at).toLowerCase()}.
                {q.items && approvedIds.size < q.items.length ? ` ${approvedIds.size} de ${q.items.length} itens aprovados.` : ''}
              </p>
              {order.data ? (
                <Link to={`/os/${order.data.id}`} className={buttonClass('secondary')}>
                  <ClipboardList size={18} aria-hidden /> Ver OS {order.data.number}
                </Link>
              ) : (
                <span className="text-sm">Gerando a OS…</span>
              )}
            </div>
          ) : null}
          {q.status === 'rejected' ? (
            <div role="status" className="rounded-lg bg-danger-soft p-4 text-danger">
              Cliente recusou {formatWhen(q.decided_at).toLowerCase()}.{q.rejection_reason ? ` Motivo: “${q.rejection_reason}”` : ''}
            </div>
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
              <div className="mb-4">
                <ErrorBox message={actionError} />
              </div>
            ) : null}
            <ItemList
              items={q.items ?? []}
              users={users.data ?? []}
              aside={
                q.status === 'approved'
                  ? (item) => (
                      <span className={`mt-1 size-2.5 shrink-0 rounded-full ${approvedIds.has(item.id) ? 'bg-success' : 'bg-border-strong'}`} title={approvedIds.has(item.id) ? 'Aprovado' : 'Não aprovado'} aria-label={approvedIds.has(item.id) ? 'Aprovado' : 'Não aprovado'} />
                    )
                  : undefined
              }
              actions={
                isEditable && (q.items?.length ?? 0) > 1
                  ? (item) => (
                      <Button variant="ghost" className="h-9 text-danger" icon={<Trash2 size={16} aria-hidden />} loading={remove.isPending && remove.variables === item.id} onClick={() => remove.mutate(item.id)}>
                        Remover
                      </Button>
                    )
                  : undefined
              }
            />
            <ItemsTotal cents={q.total_cents} />

            {adding ? (
              <div className="mt-5 border-t border-border pt-5">
                {wasSent ? <p className="mb-4 rounded-md bg-warning-soft p-3 text-sm text-warning">O cliente já recebeu este orçamento. Ao mudar, o link antigo deixa de valer e será preciso enviar de novo.</p> : null}
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
              </div>
            ) : null}
          </Card>
        </div>

        <aside className="flex flex-col gap-4 lg:sticky lg:top-8">
          <Card>
            <dl className="grid grid-cols-2 gap-4">
              <Detail label="Criado">{formatWhen(q.created_at)}</Detail>
              <Detail label="Km">{formatKm(q.mileage)}</Detail>
              <Detail label="Enviado">{formatWhen(q.sent_at)}</Detail>
              <Detail label="Link vale até">{formatWhen(q.link_expires_at)}</Detail>
            </dl>
            {q.notes ? <p className="mt-4 border-t border-border pt-4 text-sm whitespace-pre-line">{q.notes}</p> : null}
          </Card>

          {sendable.includes(q.status) ? (
            <Card>
              <CardTitle>Enviar ao cliente</CardTitle>
              {sent ? (
                <div className="flex flex-col gap-3">
                  <label className="text-sm font-medium" htmlFor="quote-link">
                    Link de aprovação
                  </label>
                  <input id="quote-link" readOnly value={sent.link} onFocus={(e) => e.target.select()} className="h-12 w-full rounded-md border border-border-strong bg-bg px-3 text-sm" />
                  {sent.whatsapp_url ? (
                    <a href={sent.whatsapp_url} target="_blank" rel="noreferrer" className={buttonClass('primary', true)}>
                      <MessageCircle size={18} aria-hidden /> Enviar pelo WhatsApp
                    </a>
                  ) : (
                    <p className="text-sm text-muted">Cliente sem celular cadastrado. Copie o link e envie como preferir.</p>
                  )}
                  <Button variant="secondary" full icon={<Copy size={18} aria-hidden />} onClick={() => copy(sent.link)}>
                    Copiar link
                  </Button>
                </div>
              ) : (
                <>
                  <p className="mb-4 text-sm text-muted">
                    {wasSent ? 'Gerar um link novo invalida o anterior.' : 'O cliente recebe um link para ver, aprovar e assinar. Vale por 7 dias.'}
                  </p>
                  <Button full icon={<Send size={18} aria-hidden />} loading={send.isPending} loadingText="Gerando link…" onClick={() => send.mutate()}>
                    {wasSent || q.status === 'expired' ? 'Reenviar link' : 'Gerar link de aprovação'}
                  </Button>
                </>
              )}
            </Card>
          ) : null}
        </aside>
      </div>
    </>
  )
}

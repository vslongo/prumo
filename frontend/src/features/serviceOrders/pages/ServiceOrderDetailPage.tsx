import Box from '@mui/material/Box'
import MuiLink from '@mui/material/Link'
import Skeleton from '@mui/material/Skeleton'
import Stack from '@mui/material/Stack'
import Typography from '@mui/material/Typography'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Check, Circle, FileText, Plus } from 'lucide-react'
import { useState } from 'react'
import { useParams } from 'react-router'
import { formatKm, formatPlate, formatWhen } from '../../../shared/lib/format'
import { ApiError } from '../../../shared/lib/http'
import { serviceOrderCancelable, serviceOrderNextStep, serviceOrderStatusLabel, type ServiceOrderStatus } from '../../../shared/lib/status'
import { BackLink } from '../../../shared/ui/BackLink'
import { Button } from '../../../shared/ui/Button'
import { ConfirmDialog } from '../../../shared/ui/ConfirmDialog'
import { TextField } from '../../../shared/ui/Field'
import { Card, CardTitle, Detail, DetailGrid, ErrorBox, PageHeader } from '../../../shared/ui/States'
import { muted } from '../../../shared/ui/styles'
import { ServiceOrderStatusBadge } from '../../../shared/ui/StatusBadge'
import { useToast } from '../../../shared/ui/Toast'
import { useUsers } from '../../auth/api'
import { ItemForm } from '../../items/components/ItemForm'
import { ItemList, ItemsTotal } from '../../items/components/ItemList'
import { ItemPhotos } from '../../items/components/ItemPhotos'
import { useItemPhotos } from '../../items/hooks/useItemPhotos'
import { useServiceTypes } from '../../serviceTypes/api'
import { vehicleName } from '../../vehicles/api'
import { serviceOrdersApi, type ServiceOrder } from '../api'

const steps: ServiceOrderStatus[] = ['open', 'in_progress', 'ready', 'delivered', 'closed']

/** Linha do tempo da OS: onde ela está e o que vem depois. */
function Progress({ status }: { status: ServiceOrderStatus }) {
  if (status === 'canceled') return null
  const current = steps.indexOf(status)
  return (
    <Box component="ol" aria-label="Andamento da OS" sx={{ mb: 2, mt: 0, p: 0, listStyle: 'none', display: 'grid', gridTemplateColumns: 'repeat(5, 1fr)', gap: 0.5 }}>
      {steps.map((s, i) => (
        <Stack component="li" key={s} aria-current={i === current ? 'step' : undefined} sx={{ gap: 0.75 }}>
          <Box component="span" sx={{ height: 6, borderRadius: 1.5, bgcolor: i <= current ? 'primary.main' : 'action.hover' }} />
          <Box component="span" sx={{ fontSize: { xs: 12, sm: 13 }, ...(i === current ? { fontWeight: 600 } : muted) }}>
            {serviceOrderStatusLabel[s]}
          </Box>
        </Stack>
      ))}
    </Box>
  )
}

export function ServiceOrderDetailPage() {
  const { id = '' } = useParams()
  const queryClient = useQueryClient()
  const toast = useToast()
  const users = useUsers()
  const types = useServiceTypes()
  const [adding, setAdding] = useState(false)
  const [confirm, setConfirm] = useState<'cancel' | 'close' | null>(null)
  const [mileageOut, setMileageOut] = useState('')
  const [error, setError] = useState<string | null>(null)

  const order = useQuery({ queryKey: ['service-orders', id], queryFn: () => serviceOrdersApi.get(id) })
  const photos = useItemPhotos(['service-orders', id, 'photos'], {
    list: () => serviceOrdersApi.photos(id),
    request: (itemId, mime, size) => serviceOrdersApi.requestPhotoUpload(id, itemId, mime, size),
    confirm: (itemId, mediaId) => serviceOrdersApi.confirmPhoto(id, itemId, mediaId),
    remove: (itemId, mediaId) => serviceOrdersApi.removePhoto(id, itemId, mediaId),
  })

  const apply = (data: ServiceOrder) => {
    queryClient.setQueryData(['service-orders', id], data)
    queryClient.invalidateQueries({ queryKey: ['service-orders', 'list'] })
    queryClient.invalidateQueries({ queryKey: ['service-orders', 'board'] })
  }
  const fail = (e: unknown) => setError(e instanceof ApiError ? (Object.values(e.errors)[0]?.[0] ?? e.message) : 'Não deu para salvar. Tente de novo.')

  const transition = useMutation({
    mutationFn: (to: ServiceOrderStatus) => serviceOrdersApi.transition(id, to),
    onMutate: () => setError(null),
    onSuccess: (data) => {
      apply(data)
      setConfirm(null)
      toast(`OS ${data.number}: ${serviceOrderStatusLabel[data.status].toLowerCase()}.`)
    },
    onError: (e) => (setConfirm(null), fail(e)),
  })

  const markDone = useMutation({
    mutationFn: (itemId: string) => serviceOrdersApi.markDone(id, itemId),
    onMutate: () => setError(null),
    onSuccess: apply,
    onError: fail,
  })

  const close = useMutation({
    mutationFn: (km: number) => serviceOrdersApi.close(id, km),
    onMutate: () => setError(null),
    onSuccess: (data) => {
      apply(data)
      setConfirm(null)
      toast(`OS ${data.number} fechada.`)
    },
    onError: (e) => (setConfirm(null), fail(e)),
  })

  if (order.isLoading) {
    return (
      <>
        <Skeleton height={40} width={256} sx={{ mb: 3 }} />
        <Skeleton height={288} />
      </>
    )
  }
  if (order.isError || !order.data) return <ErrorBox message="Não deu para carregar a OS." onRetry={() => order.refetch()} />

  const o = order.data
  const items = o.items ?? []
  const next = serviceOrderNextStep[o.status]
  const acceptsItems = o.status === 'open' || o.status === 'in_progress'
  const acceptsPhotos = o.status !== 'closed' && o.status !== 'canceled'
  const doneCount = items.filter((i) => i.done_at).length
  const kmOut = Number(mileageOut)
  const kmInvalid = mileageOut === '' || (o.mileage_in !== null && kmOut < o.mileage_in)

  return (
    <>
      <PageHeader
        back={<BackLink to="/os" label="OS" />}
        title={`OS ${o.number}`}
        subtitle={
          <Box component="span" sx={{ display: 'flex', flexWrap: 'wrap', alignItems: 'center', gap: 1 }}>
            <ServiceOrderStatusBadge status={o.status} />
            <Box component="span" className="num" sx={{ fontWeight: 600, color: 'text.primary' }}>{o.vehicle ? formatPlate(o.vehicle.plate) : ''}</Box>
            <span>
              {vehicleName(o.vehicle)} · {o.customer?.name}
            </span>
          </Box>
        }
      />

      <Box sx={{ display: 'grid', gap: 2, gridTemplateColumns: { xs: 'minmax(0, 1fr)', lg: 'minmax(0, 1fr) 340px' }, alignItems: { lg: 'start' } }}>
        <Stack sx={{ gap: 2 }}>
          <Card>
            <Progress status={o.status} />
            <CardTitle
              action={
                acceptsItems && !adding ? (
                  <Button variant="ghost" icon={<Plus size={18} aria-hidden />} onClick={() => setAdding(true)}>
                    Adicionar item
                  </Button>
                ) : null
              }
            >
              Itens
              {items.length ? (
                <Box component="span" className="num" sx={{ ml: 1, fontFamily: 'var(--font-sans)', fontSize: 16, fontWeight: 400, ...muted }}>
                  {doneCount} de {items.length} feitos
                </Box>
              ) : null}
            </CardTitle>

            {error ? (
              <Box sx={{ mb: 2 }}>
                <ErrorBox message={error} />
              </Box>
            ) : null}

            {items.length === 0 && !adding ? <Typography sx={muted}>Nenhum item ainda. Adicione o que vai ser feito.</Typography> : null}

            <ItemList
              items={items}
              users={users.data ?? []}
              aside={(item) =>
                item.done_at ? (
                  <Box component={Check} size={20} aria-label="Feito" sx={{ mt: 0.25, flexShrink: 0, color: 'success.main' }} />
                ) : (
                  <Box component={Circle} size={20} aria-label="A fazer" sx={{ mt: 0.25, flexShrink: 0, color: 'var(--color-border-strong)' }} />
                )
              }
              actions={(item) =>
                item.done_at ? (
                  <Typography variant="body2" sx={{ color: 'success.main' }}>
                    Feito {formatWhen(item.done_at).toLowerCase()}
                    {item.done_by ? ` por ${users.data?.find((u) => u.id === item.done_by)?.name ?? 'técnico'}` : ''}
                  </Typography>
                ) : acceptsItems ? (
                  <Button variant="secondary" size="small" icon={<Check size={16} aria-hidden />} loading={markDone.isPending && markDone.variables === item.id} onClick={() => markDone.mutate(item.id)}>
                    Marcar como feito
                  </Button>
                ) : null
              }
              extra={(item) => {
                const mine = photos.byItem(item.id)
                return (
                  <ItemPhotos
                    uploadGroup={item.id}
                    photos={mine.map((p) => ({ key: p.id, url: p.url, thumb_url: p.thumb_url, removable: acceptsPhotos && !p.from_quote }))}
                    onAdd={acceptsPhotos ? (files) => photos.add(item.id, files) : undefined}
                    onRemove={(key) => {
                      const photo = mine.find((p) => p.id === key)
                      if (photo) photos.remove.mutate(photo, { onError: fail })
                    }}
                  />
                )
              }}
            />
            {items.length ? <ItemsTotal cents={o.total_cents} /> : null}

            {adding ? (
              <Box sx={{ mt: 2.5, borderTop: 1, borderColor: 'divider', pt: 2.5 }}>
                <ItemForm
                  serviceTypes={types.data ?? []}
                  users={users.data ?? []}
                  onCancel={() => setAdding(false)}
                  onSubmit={async (item) => {
                    apply(await serviceOrdersApi.addItem(id, item))
                    setAdding(false)
                    toast('Item adicionado.')
                  }}
                />
              </Box>
            ) : null}
          </Card>
        </Stack>

        <Stack component="aside" sx={{ gap: 2, position: { lg: 'sticky' }, top: { lg: 32 } }}>
          {o.status !== 'closed' && o.status !== 'canceled' ? (
            <Card>
              <CardTitle>Próximo passo</CardTitle>
              {next ? (
                <Button full loading={transition.isPending && transition.variables === next.to} onClick={() => transition.mutate(next.to)}>
                  {next.label}
                </Button>
              ) : null}
              {o.status === 'delivered' ? (
                <Button full onClick={() => setConfirm('close')} disabled={doneCount < items.length || items.length === 0}>
                  Fechar OS
                </Button>
              ) : null}
              {o.status === 'delivered' && (doneCount < items.length || items.length === 0) ? (
                <Typography variant="body2" sx={{ mt: 1, ...muted }}>
                  Para fechar, marque todos os itens como feitos.
                </Typography>
              ) : null}
              {serviceOrderCancelable.includes(o.status) ? (
                <Button variant="ghost" tone="danger" full sx={{ mt: 1 }} onClick={() => setConfirm('cancel')}>
                  Cancelar OS
                </Button>
              ) : null}
            </Card>
          ) : null}

          <Card>
            <DetailGrid>
              <Detail label="Aberta">{formatWhen(o.created_at)}</Detail>
              <Detail label="Origem">
                {o.quote_id ? (
                  <MuiLink href={`/orcamentos/${o.quote_id}`} underline="hover" sx={{ display: 'inline-flex', alignItems: 'center', gap: 0.5 }}>
                    <FileText size={16} aria-hidden /> Orçamento
                  </MuiLink>
                ) : (
                  'Direta'
                )}
              </Detail>
              <Detail label="Km entrada">{formatKm(o.mileage_in)}</Detail>
              <Detail label="Km saída">{formatKm(o.mileage_out)}</Detail>
              {o.closed_at ? <Detail label="Fechada">{formatWhen(o.closed_at)}</Detail> : null}
              {o.canceled_at ? <Detail label="Cancelada">{formatWhen(o.canceled_at)}</Detail> : null}
            </DetailGrid>
            {o.notes ? <Typography variant="body2" sx={{ mt: 2, borderTop: 1, borderColor: 'divider', pt: 2, whiteSpace: 'pre-line' }}>{o.notes}</Typography> : null}
          </Card>
        </Stack>
      </Box>

      <ConfirmDialog
        open={confirm === 'cancel'}
        title={`Cancelar a OS ${o.number}?`}
        text="Essa ação não pode ser desfeita."
        confirmLabel="Cancelar OS"
        danger
        loading={transition.isPending}
        onConfirm={() => transition.mutate('canceled')}
        onClose={() => setConfirm(null)}
      />
      <ConfirmDialog
        open={confirm === 'close'}
        title={`Fechar a OS ${o.number}`}
        text="Informe o km na saída do veículo."
        confirmLabel="Fechar OS"
        loading={close.isPending}
        onConfirm={() => !kmInvalid && close.mutate(kmOut)}
        onClose={() => setConfirm(null)}
      >
        <TextField
          label="Km de saída"
          inputMode="numeric"
          value={mileageOut}
          onChange={(e) => setMileageOut(e.target.value.replace(/\D/g, ''))}
          error={mileageOut !== '' && kmInvalid ? `Não pode ser menor que o km de entrada (${formatKm(o.mileage_in)}).` : undefined}
          hint={o.mileage_in !== null ? `Entrada: ${formatKm(o.mileage_in)}` : undefined}
        />
      </ConfirmDialog>
    </>
  )
}

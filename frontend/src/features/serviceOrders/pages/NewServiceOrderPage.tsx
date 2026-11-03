import { useQueryClient } from '@tanstack/react-query'
import { useNavigate } from 'react-router'
import { BackLink } from '../../../shared/ui/BackLink'
import { PageHeader } from '../../../shared/ui/States'
import { useToast } from '../../../shared/ui/Toast'
import { DraftEditor } from '../../items/components/DraftEditor'
import { enqueueItemPhotos } from '../../items/hooks/useItemPhotos'
import { serviceOrdersApi, serviceOrdersPhotoApi } from '../api'

/** OS direta, sem orçamento. */
export function NewServiceOrderPage() {
  const navigate = useNavigate()
  const toast = useToast()
  const queryClient = useQueryClient()

  return (
    <>
      <PageHeader back={<BackLink to="/os" label="OS" />} title="Nova OS" subtitle="Para serviço que não precisa de orçamento." />
      <DraftEditor
        mileageLabel="Km de entrada"
        submitLabel="Abrir OS"
        submittingLabel="Abrindo…"
        onSubmit={async (d) => {
          const order = await serviceOrdersApi.create({ customer_id: d.customerId, vehicle_id: d.vehicleId, mileage_in: d.mileage, notes: d.notes || undefined, items: d.items })
          queryClient.invalidateQueries({ queryKey: ['service-orders'] })
          // Itens voltam na ordem enviada (posição 1..n): cada um leva as fotos tiradas no rascunho.
          const items = [...(order.items ?? [])].sort((a, b) => a.position - b.position)
          items.forEach((item, i) => enqueueItemPhotos(queryClient, ['service-orders', order.id, 'photos'], serviceOrdersPhotoApi(order.id), item.id, d.photos[i] ?? []))
          toast(`OS ${order.number} aberta.`)
          navigate(`/os/${order.id}`)
        }}
      />
    </>
  )
}

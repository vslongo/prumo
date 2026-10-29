import { useQueryClient } from '@tanstack/react-query'
import { useNavigate } from '../../../shared/lib/navigation'
import { BackLink } from '../../../shared/ui/BackLink'
import { PageHeader } from '../../../shared/ui/States'
import { useToast } from '../../../shared/ui/Toast'
import { DraftEditor } from '../../items/components/DraftEditor'
import { serviceOrdersApi } from '../api'

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
          toast(`OS ${order.number} aberta.`)
          navigate(`/os/${order.id}`)
        }}
      />
    </>
  )
}

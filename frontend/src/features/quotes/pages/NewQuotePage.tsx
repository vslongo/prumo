import { useQueryClient } from '@tanstack/react-query'
import { useNavigate } from 'react-router'
import { BackLink } from '../../../shared/ui/BackLink'
import { PageHeader } from '../../../shared/ui/States'
import { useToast } from '../../../shared/ui/Toast'
import { DraftEditor } from '../../items/components/DraftEditor'
import { quotesApi } from '../api'

export function NewQuotePage() {
  const navigate = useNavigate()
  const toast = useToast()
  const queryClient = useQueryClient()

  return (
    <>
      <PageHeader back={<BackLink to="/orcamentos" label="Orçamentos" />} title="Novo orçamento" subtitle="Monte o orçamento e depois envie pelo WhatsApp." />
      <DraftEditor
        mileageLabel="Km atual"
        submitLabel="Salvar orçamento"
        submittingLabel="Salvando…"
        requireItems
        onSubmit={async (d) => {
          const quote = await quotesApi.create({ customer_id: d.customerId, vehicle_id: d.vehicleId, mileage: d.mileage, notes: d.notes || undefined, items: d.items })
          queryClient.invalidateQueries({ queryKey: ['quotes'] })
          toast('Orçamento salvo.')
          navigate(`/orcamentos/${quote.id}`)
        }}
      />
    </>
  )
}

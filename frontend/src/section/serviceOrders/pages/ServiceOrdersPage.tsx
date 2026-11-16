import { ENDPOINTS } from '../../../routes/paths'
import { ClipboardList, Plus } from 'lucide-react'
import { useSearchParams } from '../../../shared/lib/navigation'
import { serviceOrderStatusLabel, type ServiceOrderStatus } from '../../../shared/lib/status'
import { ButtonLink } from '../../../shared/ui/Button'
import { EmptyState, PageHeader } from '../../../shared/ui/States'
import { StatusFilter } from '../../../shared/ui/StatusFilter'
import type { ServiceOrder } from '../api'
import { ListItems } from '../../../components/ListItems'
import { serviceOrderColumns } from '../components/listColumns'

const endpoints = ENDPOINTS()

const filters: { value: ServiceOrderStatus | undefined; label: string }[] = [
  { value: undefined, label: 'Todas' },
  ...(['open', 'in_progress', 'ready', 'delivered', 'closed', 'canceled'] as const).map((s) => ({ value: s, label: serviceOrderStatusLabel[s] })),
]

export function ServiceOrdersPage() {
  const [params, setParams] = useSearchParams()
  const status = (params.get('status') as ServiceOrderStatus | null) ?? undefined

  return (
    <>
      <PageHeader
        title="Ordens de serviço"
        action={
          <ButtonLink to="/os/nova" icon={<Plus size={18} aria-hidden />} full sx={{ width: { sm: 'auto' } }}>
            Nova OS
          </ButtonLink>
        }
      />
      <StatusFilter options={filters} value={status} onChange={(s) => setParams(s ? { status: s } : {})} />
      <ListItems<ServiceOrder>
        url={`${endpoints.ServiceOrders.list}${status ? `?status=${encodeURIComponent(status)}` : ''}`}
        queryKey={['service-orders', 'list']}
        columns={serviceOrderColumns}
        getRowHref={item => `/os/${item.id}`}
        label="Ordens de serviço"
        errorMessage="Não deu para carregar as OS."
        emptyState={status ? (
          <EmptyState icon={ClipboardList} title={`Nenhuma OS ${serviceOrderStatusLabel[status].toLowerCase()}.`} text="Troque o filtro para ver as outras." />
        ) : (
          <EmptyState icon={ClipboardList} title="Nenhuma OS aberta." text="Abra a primeira OS ou envie um orçamento." action={<ButtonLink to="/os/nova">Nova OS</ButtonLink>} />
        )}
      />
    </>
  )
}

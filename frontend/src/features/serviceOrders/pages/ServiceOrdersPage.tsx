import { useQuery } from '@tanstack/react-query'
import { ClipboardList, Plus } from 'lucide-react'
import { useSearchParams } from 'react-router'
import { serviceOrderStatusLabel, type ServiceOrderStatus } from '../../../shared/lib/status'
import { ButtonLink } from '../../../shared/ui/Button'
import { EmptyState, ErrorBox, ListSkeleton, PageHeader } from '../../../shared/ui/States'
import { StatusFilter } from '../../../shared/ui/StatusFilter'
import { serviceOrdersApi } from '../api'
import { ServiceOrderList } from '../components/ServiceOrderList'

const filters: { value: ServiceOrderStatus | undefined; label: string }[] = [
  { value: undefined, label: 'Todas' },
  ...(['open', 'in_progress', 'ready', 'delivered', 'closed', 'canceled'] as const).map((s) => ({ value: s, label: serviceOrderStatusLabel[s] })),
]

export function ServiceOrdersPage() {
  const [params, setParams] = useSearchParams()
  const status = (params.get('status') as ServiceOrderStatus | null) ?? undefined
  const list = useQuery({ queryKey: ['service-orders', 'list', { status }], queryFn: () => serviceOrdersApi.list({ status }) })

  return (
    <>
      <PageHeader
        title="Ordens de serviço"
        action={
          <ButtonLink to="/os/nova" icon={<Plus size={18} aria-hidden />} full className="sm:w-auto">
            Nova OS
          </ButtonLink>
        }
      />
      <StatusFilter options={filters} value={status} onChange={(s) => setParams(s ? { status: s } : {})} />
      {list.isLoading ? (
        <ListSkeleton />
      ) : list.isError ? (
        <ErrorBox message="Não deu para carregar as OS." onRetry={() => list.refetch()} />
      ) : list.data?.data.length ? (
        <ServiceOrderList orders={list.data.data} />
      ) : status ? (
        <EmptyState icon={ClipboardList} title={`Nenhuma OS ${serviceOrderStatusLabel[status].toLowerCase()}.`} text="Troque o filtro para ver as outras." />
      ) : (
        <EmptyState icon={ClipboardList} title="Nenhuma OS aberta." text="Abra a primeira OS ou envie um orçamento." action={<ButtonLink to="/os/nova">Nova OS</ButtonLink>} />
      )}
    </>
  )
}

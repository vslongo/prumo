import { useQuery } from '@tanstack/react-query'
import { FileText, Plus } from 'lucide-react'
import { useSearchParams } from 'react-router'
import { quoteStatusLabel, type QuoteStatus } from '../../../shared/lib/status'
import { ButtonLink } from '../../../shared/ui/Button'
import { EmptyState, ErrorBox, ListSkeleton, PageHeader } from '../../../shared/ui/States'
import { StatusFilter } from '../../../shared/ui/StatusFilter'
import { quotesApi } from '../api'
import { QuoteList } from '../components/QuoteList'

const filters: { value: QuoteStatus | undefined; label: string }[] = [
  { value: undefined, label: 'Todos' },
  ...(['draft', 'sent', 'viewed', 'approved', 'rejected', 'expired'] as const).map((s) => ({ value: s, label: quoteStatusLabel[s] })),
]

export function QuotesPage() {
  const [params, setParams] = useSearchParams()
  const status = (params.get('status') as QuoteStatus | null) ?? undefined
  const list = useQuery({ queryKey: ['quotes', 'list', status ?? 'all'], queryFn: () => quotesApi.list(status) })

  return (
    <>
      <PageHeader
        title="Orçamentos"
        action={
          <ButtonLink to="/orcamentos/novo" icon={<Plus size={18} aria-hidden />} full sx={{ width: { sm: 'auto' } }}>
            Novo orçamento
          </ButtonLink>
        }
      />
      <StatusFilter options={filters} value={status} onChange={(s) => setParams(s ? { status: s } : {})} />
      {list.isLoading ? (
        <ListSkeleton />
      ) : list.isError ? (
        <ErrorBox message="Não deu para carregar os orçamentos." onRetry={() => list.refetch()} />
      ) : list.data?.data.length ? (
        <QuoteList quotes={list.data.data} />
      ) : status ? (
        <EmptyState icon={FileText} title={`Nenhum orçamento ${quoteStatusLabel[status].toLowerCase()}.`} text="Troque o filtro para ver os outros." />
      ) : (
        <EmptyState icon={FileText} title="Nenhum orçamento ainda." text="Monte o orçamento no celular e mande pelo WhatsApp." action={<ButtonLink to="/orcamentos/novo">Novo orçamento</ButtonLink>} />
      )}
    </>
  )
}

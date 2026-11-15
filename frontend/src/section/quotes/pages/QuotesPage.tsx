import { ENDPOINTS } from '../../../routes/paths'
import { FileText, Plus } from 'lucide-react'
import { useSearchParams } from '../../../shared/lib/navigation'
import { quoteStatusLabel, type QuoteStatus } from '../../../shared/lib/status'
import { ButtonLink } from '../../../shared/ui/Button'
import { EmptyState, PageHeader } from '../../../shared/ui/States'
import { StatusFilter } from '../../../shared/ui/StatusFilter'
import type { Quote } from '../api'
import { ListItems } from '../../../components/ListItems'
import { quoteColumns } from '../components/listColumns'

const endpoints = ENDPOINTS()

const filters: { value: QuoteStatus | undefined; label: string }[] = [
  { value: undefined, label: 'Todos' },
  ...(['draft', 'sent', 'viewed', 'approved', 'rejected', 'expired'] as const).map((s) => ({ value: s, label: quoteStatusLabel[s] })),
]

export function QuotesPage() {
  const [params, setParams] = useSearchParams()
  const status = (params.get('status') as QuoteStatus | null) ?? undefined

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
      <ListItems<Quote>
        url={`${endpoints.Quotes.list}${status ? `?status=${encodeURIComponent(status)}` : ''}`}
        queryKey={['quotes', 'list']}
        columns={quoteColumns}
        getRowHref={item => `/orcamentos/${item.id}`}
        label="Orçamentos"
        errorMessage="Não deu para carregar os orçamentos."
        emptyState={status ? (
          <EmptyState icon={FileText} title={`Nenhum orçamento ${quoteStatusLabel[status].toLowerCase()}.`} text="Troque o filtro para ver os outros." />
        ) : (
          <EmptyState icon={FileText} title="Nenhum orçamento ainda." text="Monte o orçamento no celular e mande pelo WhatsApp." action={<ButtonLink to="/orcamentos/novo">Novo orçamento</ButtonLink>} />
        )}
      />
    </>
  )
}

import { useQuery } from '@tanstack/react-query'
import { ClipboardList, FileText, Plus } from 'lucide-react'
import { Link } from 'react-router'
import { formatMoney } from '../../../shared/lib/format'
import { serviceOrderStatusLabel, type ServiceOrderStatus } from '../../../shared/lib/status'
import { ButtonLink } from '../../../shared/ui/Button'
import { Card, CardTitle, EmptyState, ErrorBox, ListSkeleton, PageHeader, Skeleton } from '../../../shared/ui/States'
import { useMe } from '../../auth/api'
import { quotesApi } from '../../quotes/api'
import { QuoteList } from '../../quotes/components/QuoteList'
import { serviceOrdersApi } from '../../serviceOrders/api'
import { ServiceOrderList } from '../../serviceOrders/components/ServiceOrderList'

const board: ServiceOrderStatus[] = ['open', 'in_progress', 'ready']

function greeting(now = new Date()): string {
  const h = now.getHours()
  return h < 12 ? 'Bom dia' : h < 18 ? 'Boa tarde' : 'Boa noite'
}

export function DashboardPage() {
  const { data: me } = useMe()
  const counts = useQuery({
    queryKey: ['service-orders', 'board'],
    queryFn: async () => Object.fromEntries(await Promise.all(board.map(async (s) => [s, (await serviceOrdersApi.list({ status: s })).meta.total] as const))),
  })
  const recent = useQuery({ queryKey: ['service-orders', 'list', {}], queryFn: () => serviceOrdersApi.list() })
  const waiting = useQuery({ queryKey: ['quotes', 'list', 'sent'], queryFn: () => quotesApi.list('sent') })
  const viewed = useQuery({ queryKey: ['quotes', 'list', 'viewed'], queryFn: () => quotesApi.list('viewed') })

  const awaitingCustomer = [...(viewed.data?.data ?? []), ...(waiting.data?.data ?? [])]
  const awaitingTotal = awaitingCustomer.reduce((sum, q) => sum + q.total_cents, 0)

  return (
    <>
      <PageHeader
        title={`${greeting()}, ${me?.name.split(' ')[0] ?? ''}`}
        subtitle={me?.workshop}
        action={
          <>
            <ButtonLink to="/orcamentos/novo" variant="secondary" icon={<FileText size={18} aria-hidden />} className="flex-1 sm:flex-none">
              Novo orçamento
            </ButtonLink>
            <ButtonLink to="/os/nova" icon={<Plus size={18} aria-hidden />} className="flex-1 sm:flex-none">
              Nova OS
            </ButtonLink>
          </>
        }
      />

      <section aria-label="OS no pátio" className="mb-6 grid grid-cols-3 gap-2 sm:gap-3">
        {board.map((s) => (
          <Link
            key={s}
            to={`/os?status=${s}`}
            className="flex flex-col gap-1 rounded-lg border border-border bg-surface p-3 hover:border-border-strong sm:p-4"
          >
            <span className="flex items-center gap-1.5 text-sm text-muted">
              <span className="size-2 rounded-full" style={{ background: `var(--status-os-${s}-fg)` }} aria-hidden />
              {serviceOrderStatusLabel[s]}
            </span>
            {counts.isLoading ? <Skeleton className="h-9 w-12" /> : <span className="num font-display text-[32px] leading-none font-bold">{counts.data?.[s] ?? 0}</span>}
          </Link>
        ))}
      </section>

      {counts.isError ? <ErrorBox message="Não deu para carregar o resumo." onRetry={() => counts.refetch()} /> : null}

      <div className="grid gap-6 lg:grid-cols-[3fr_2fr]">
        <Card>
          <CardTitle
            action={
              <Link to="/os" className="text-sm font-semibold text-brand hover:underline">
                Ver todas
              </Link>
            }
          >
            Últimas OS
          </CardTitle>
          {recent.isLoading ? (
            <ListSkeleton rows={3} />
          ) : recent.isError ? (
            <ErrorBox message="Não deu para carregar as OS." onRetry={() => recent.refetch()} />
          ) : recent.data?.data.length ? (
            <ServiceOrderList orders={recent.data.data.slice(0, 6)} compact />
          ) : (
            <EmptyState icon={ClipboardList} title="Nenhuma OS aberta." text="Abra a primeira OS ou envie um orçamento." action={<ButtonLink to="/os/nova">Nova OS</ButtonLink>} />
          )}
        </Card>

        <Card>
          <CardTitle>Aguardando o cliente</CardTitle>
          {waiting.isLoading || viewed.isLoading ? (
            <ListSkeleton rows={2} />
          ) : awaitingCustomer.length ? (
            <>
              <p className="mb-3 text-sm text-muted">
                {awaitingCustomer.length} {awaitingCustomer.length === 1 ? 'orçamento' : 'orçamentos'} · <span className="num font-semibold text-text">{formatMoney(awaitingTotal)}</span>
              </p>
              <QuoteList quotes={awaitingCustomer.slice(0, 5)} compact />
            </>
          ) : (
            <p className="text-muted">Nenhum orçamento esperando resposta.</p>
          )}
        </Card>
      </div>
    </>
  )
}

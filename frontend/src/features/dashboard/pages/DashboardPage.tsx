import Box from '@mui/material/Box'
import ButtonBase from '@mui/material/ButtonBase'
import MuiLink from '@mui/material/Link'
import Skeleton from '@mui/material/Skeleton'
import Typography from '@mui/material/Typography'
import { useQuery } from '@tanstack/react-query'
import { ClipboardList, FileText, Plus } from 'lucide-react'
import { formatMoney } from '../../../shared/lib/format'
import { serviceOrderStatusLabel, type ServiceOrderStatus } from '../../../shared/lib/status'
import { ButtonLink } from '../../../shared/ui/Button'
import { Card, CardTitle, EmptyState, ErrorBox, ListSkeleton, PageHeader } from '../../../shared/ui/States'
import { muted } from '../../../shared/ui/styles'
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
            <ButtonLink to="/orcamentos/novo" variant="secondary" icon={<FileText size={18} aria-hidden />} sx={{ flex: { xs: 1, sm: 'none' } }}>
              Novo orçamento
            </ButtonLink>
            <ButtonLink to="/os/nova" icon={<Plus size={18} aria-hidden />} sx={{ flex: { xs: 1, sm: 'none' } }}>
              Nova OS
            </ButtonLink>
          </>
        }
      />

      <Box component="section" aria-label="OS no pátio" sx={{ mb: 3, display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: { xs: 1, sm: 1.5 } }}>
        {board.map((s) => (
          <ButtonBase
            key={s}
            href={`/os?status=${s}`}
            sx={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-start', gap: 0.5, border: 1, borderColor: 'divider', borderRadius: 1.5, bgcolor: 'background.paper', p: { xs: 1.5, sm: 2 }, '&:hover': { borderColor: 'var(--color-border-strong)' } }}
          >
            <Box component="span" sx={{ display: 'flex', alignItems: 'center', gap: 0.75, fontSize: 14, ...muted }}>
              <Box component="span" aria-hidden sx={{ width: 8, height: 8, borderRadius: '50%' }} style={{ background: `var(--status-os-${s}-fg)` }} />
              {serviceOrderStatusLabel[s]}
            </Box>
            {counts.isLoading ? (
              <Skeleton height={36} width={48} />
            ) : (
              <Box component="span" className="num" sx={{ fontFamily: 'var(--font-display)', fontSize: 32, lineHeight: 1, fontWeight: 700 }}>
                {counts.data?.[s] ?? 0}
              </Box>
            )}
          </ButtonBase>
        ))}
      </Box>

      {counts.isError ? <ErrorBox message="Não deu para carregar o resumo." onRetry={() => counts.refetch()} /> : null}

      <Box sx={{ display: 'grid', gap: 3, gridTemplateColumns: { xs: 'minmax(0, 1fr)', lg: 'minmax(0, 3fr) minmax(0, 2fr)' } }}>
        <Card>
          <CardTitle
            action={
              <MuiLink href="/os" underline="hover" sx={{ fontSize: 14, fontWeight: 600 }}>
                Ver todas
              </MuiLink>
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
              <Typography variant="body2" sx={{ mb: 1.5, ...muted }}>
                {awaitingCustomer.length} {awaitingCustomer.length === 1 ? 'orçamento' : 'orçamentos'} ·{' '}
                <Box component="span" className="num" sx={{ fontWeight: 600, color: 'text.primary' }}>
                  {formatMoney(awaitingTotal)}
                </Box>
              </Typography>
              <QuoteList quotes={awaitingCustomer.slice(0, 5)} compact />
            </>
          ) : (
            <Typography sx={muted}>Nenhum orçamento esperando resposta.</Typography>
          )}
        </Card>
      </Box>
    </>
  )
}

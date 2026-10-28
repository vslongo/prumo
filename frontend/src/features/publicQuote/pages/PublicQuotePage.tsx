import Box from '@mui/material/Box'
import Checkbox from '@mui/material/Checkbox'
import Paper from '@mui/material/Paper'
import Skeleton from '@mui/material/Skeleton'
import Stack from '@mui/material/Stack'
import Typography from '@mui/material/Typography'
import { useQuery } from '@tanstack/react-query'
import { CircleCheck, CircleX, Clock, FileX } from 'lucide-react'
import { useCallback, useRef, useState } from 'react'
import { useParams } from 'react-router'
import { formatMoney, formatPlate, formatQuantity, formatWhen } from '../../../shared/lib/format'
import { ApiError } from '../../../shared/lib/http'
import { Button } from '../../../shared/ui/Button'
import { TextAreaField, TextField } from '../../../shared/ui/Field'
import { LogoMark } from '../../../shared/ui/Logo'
import { ErrorBox } from '../../../shared/ui/States'
import { muted } from '../../../shared/ui/styles'
import { publicQuoteApi, type PublicQuote } from '../api'
import { SignaturePad, type SignaturePadHandle } from '../components/SignaturePad'

const column = { mx: 'auto', maxWidth: 672, px: 2 } as const

function Shell({ workshop, children }: { workshop?: string; children: React.ReactNode }) {
  return (
    <Box sx={{ minHeight: '100dvh', bgcolor: 'background.default' }}>
      <Box component="header" sx={{ borderBottom: 1, borderColor: 'divider', bgcolor: 'background.paper' }}>
        <Box sx={{ ...column, display: 'flex', height: 64, alignItems: 'center' }}>
          <Typography sx={{ fontFamily: 'var(--font-display)', fontSize: 20, fontWeight: 700 }}>{workshop ?? 'Orçamento'}</Typography>
        </Box>
      </Box>
      <Box component="main" sx={{ ...column, py: 3 }}>
        {children}
      </Box>
      <Box component="footer" sx={{ ...column, display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 1, pt: 1, pb: 5, fontSize: 14, ...muted }}>
        <LogoMark size={18} /> Enviado com Prumo
      </Box>
    </Box>
  )
}

function Outcome({ icon: Icon, tone, title, text }: { icon: typeof CircleCheck; tone: 'success' | 'danger' | 'muted'; title: string; text: string }) {
  const color = tone === 'success' ? 'success.main' : tone === 'danger' ? 'error.main' : 'text.secondary'
  return (
    <Paper role="status" sx={{ display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 1.5, px: 3, py: 5, textAlign: 'center' }}>
      <Box component={Icon} size={40} aria-hidden sx={{ color }} />
      <Typography variant="h1" sx={{ fontSize: 24 }}>
        {title}
      </Typography>
      <Typography sx={{ maxWidth: 384, ...muted }}>{text}</Typography>
    </Paper>
  )
}

/** Página que o cliente abre pelo WhatsApp: vê, escolhe os itens, assina e aprova. Sem login. */
export function PublicQuotePage() {
  const { token = '' } = useParams()
  const quote = useQuery({ queryKey: ['public-quote', token], queryFn: () => publicQuoteApi.get(token), retry: false })
  const [done, setDone] = useState<'approved' | 'rejected' | null>(null)

  if (quote.isLoading) {
    return (
      <Shell>
        <Skeleton height={96} sx={{ mb: 2 }} />
        <Skeleton height={288} />
      </Shell>
    )
  }

  if (quote.isError || !quote.data) {
    const status = quote.error instanceof ApiError ? quote.error.status : 0
    return (
      <Shell>
        {status === 410 ? (
          <Outcome icon={Clock} tone="muted" title="Este orçamento expirou." text="Peça um novo link para a oficina." />
        ) : status === 404 ? (
          <Outcome icon={FileX} tone="muted" title="Link inválido." text="Este link não existe mais. Se a oficina mudou o orçamento, ela vai mandar um link novo." />
        ) : (
          <ErrorBox message="Não deu para abrir o orçamento. Confira a internet." onRetry={() => quote.refetch()} />
        )}
      </Shell>
    )
  }

  const q = quote.data
  const status = done ?? q.status

  return (
    <Shell workshop={q.workshop}>
      {status === 'approved' ? (
        <Outcome icon={CircleCheck} tone="success" title="Orçamento aprovado." text="A oficina já foi avisada. Obrigado!" />
      ) : status === 'rejected' ? (
        <Outcome icon={CircleX} tone="danger" title="Orçamento recusado." text="A oficina foi avisada. Se mudar de ideia, fale com ela." />
      ) : (
        <Decision quote={q} token={token} onDone={setDone} />
      )}
    </Shell>
  )
}

function Decision({ quote: q, token, onDone }: { quote: PublicQuote; token: string; onDone: (s: 'approved' | 'rejected') => void }) {
  const [selected, setSelected] = useState<Set<number>>(() => new Set(q.items.map((i) => i.position)))
  const [name, setName] = useState('')
  const [signed, setSigned] = useState(false)
  const [rejecting, setRejecting] = useState(false)
  const [reason, setReason] = useState('')
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [nameError, setNameError] = useState<string | undefined>()
  const pad = useRef<SignaturePadHandle>(null)
  const onSignChange = useCallback((empty: boolean) => setSigned(!empty), [])

  const total = q.items.filter((i) => selected.has(i.position)).reduce((s, i) => s + i.total_cents, 0)

  const toggle = (position: number) =>
    setSelected((s) => {
      const next = new Set(s)
      if (next.has(position)) next.delete(position)
      else next.add(position)
      return next
    })

  const approve = async () => {
    setError(null)
    setNameError(undefined)
    if (name.trim().length < 3) return setNameError('Informe seu nome completo.')
    if (selected.size === 0) return setError('Escolha ao menos um item para aprovar.')
    const png = await pad.current?.toPng()
    if (!png || pad.current?.isEmpty()) return setError('Assine no quadro acima para aprovar.')
    setBusy(true)
    try {
      await publicQuoteApi.uploadSignature(token, png)
      await publicQuoteApi.approve(token, name.trim(), [...selected])
      onDone('approved')
    } catch (e) {
      setError(e instanceof ApiError ? (Object.values(e.errors)[0]?.[0] ?? e.message) : 'Não deu para aprovar. Tente de novo.')
      setBusy(false)
    }
  }

  const reject = async () => {
    setBusy(true)
    try {
      await publicQuoteApi.reject(token, reason.trim())
      onDone('rejected')
    } catch (e) {
      setError(e instanceof ApiError ? e.message : 'Não deu para enviar. Tente de novo.')
      setBusy(false)
    }
  }

  const section = { display: 'flex', flexDirection: 'column', gap: 2, p: 2 } as const

  return (
    <Stack sx={{ gap: 2.5 }}>
      <section>
        <Typography sx={muted}>Olá, {q.customer_name.split(' ')[0]}. Este é o orçamento para o seu veículo:</Typography>
        <Paper sx={{ mt: 1.5, display: 'flex', alignItems: 'center', gap: 1.5, p: 2 }}>
          <Box component="span" className="num" sx={{ border: 2, borderColor: 'text.primary', borderRadius: 0.5, px: 1, py: 0.25, fontFamily: 'var(--font-display)', fontSize: 18, fontWeight: 700, letterSpacing: '0.05em' }}>
            {formatPlate(q.vehicle.plate)}
          </Box>
          <Box component="span" sx={{ fontWeight: 500 }}>
            {[q.vehicle.brand, q.vehicle.model].filter(Boolean).join(' ')} {q.vehicle.year ?? ''}
          </Box>
        </Paper>
      </section>

      <Paper component="section">
        <Typography variant="h3" component="h1" sx={{ borderBottom: 1, borderColor: 'divider', px: 2, py: 1.5 }}>
          Itens do orçamento
        </Typography>
        {q.items.length > 1 ? (
          <Typography variant="body2" sx={{ px: 2, pt: 1.5, ...muted }}>
            Desmarque o que não quiser fazer agora.
          </Typography>
        ) : null}
        <Box component="ul" sx={{ m: 0, p: 0, listStyle: 'none', '& > li + li': { borderTop: 1, borderColor: 'divider' } }}>
          {q.items.map((item) => (
            <li key={item.position}>
              <Box component="label" sx={{ display: 'flex', cursor: 'pointer', gap: 1.5, px: 2, py: 2 }}>
                <Checkbox checked={selected.has(item.position)} onChange={() => toggle(item.position)} disabled={q.items.length === 1} sx={{ p: 0, alignSelf: 'flex-start', mt: 0.25 }} />
                <Box component="span" sx={{ minWidth: 0, flex: 1 }}>
                  <Box component="span" sx={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: 1.5 }}>
                    <Box component="span" sx={{ fontWeight: 600 }}>
                      {item.description}
                    </Box>
                    <Box component="span" className="num" sx={{ flexShrink: 0, fontWeight: 600 }}>
                      {formatMoney(item.total_cents)}
                    </Box>
                  </Box>
                  <Box component="span" className="num" sx={{ display: 'block', fontSize: 14, ...muted }}>
                    {formatQuantity(item.quantity)} × {formatMoney(item.unit_price_cents)}
                  </Box>
                  {item.details.length ? (
                    <Box component="span" sx={{ mt: 0.5, display: 'block', fontSize: 14 }}>
                      {item.details.map((d) => `${d.label}: ${typeof d.value === 'boolean' ? (d.value ? 'sim' : 'não') : String(d.value)}${d.unit ? ` ${d.unit}` : ''}`).join(' · ')}
                    </Box>
                  ) : null}
                </Box>
              </Box>
            </li>
          ))}
        </Box>
        <Stack direction="row" sx={{ alignItems: 'baseline', justifyContent: 'space-between', borderTop: 1, borderColor: 'divider', px: 2, py: 2 }}>
          <Typography sx={muted}>{selected.size === q.items.length ? 'Total' : `Total de ${selected.size} ${selected.size === 1 ? 'item' : 'itens'}`}</Typography>
          <Typography className="num" sx={{ fontFamily: 'var(--font-display)', fontSize: 28, fontWeight: 700 }}>
            {formatMoney(total)}
          </Typography>
        </Stack>
      </Paper>

      {q.notes ? <Paper sx={{ p: 2, whiteSpace: 'pre-line' }}>{q.notes}</Paper> : null}
      {q.expires_at ? (
        <Typography variant="body2" sx={muted}>
          Válido até {formatWhen(q.expires_at)}.
        </Typography>
      ) : null}

      {rejecting ? (
        <Paper component="section" sx={section}>
          <Typography variant="h2">Recusar orçamento</Typography>
          <TextAreaField label="Quer contar o motivo?" hint="Opcional" value={reason} onChange={(e) => setReason(e.target.value)} maxLength={500} />
          {error ? <ErrorBox message={error} /> : null}
          <Stack direction={{ xs: 'column-reverse', sm: 'row' }} sx={{ gap: 1, justifyContent: 'flex-end' }}>
            <Button variant="secondary" onClick={() => setRejecting(false)}>
              Voltar
            </Button>
            <Button variant="danger" loading={busy} loadingText="Enviando…" onClick={reject}>
              Recusar orçamento
            </Button>
          </Stack>
        </Paper>
      ) : (
        <Paper component="section" sx={section}>
          <Typography variant="h2">Aprovar e assinar</Typography>
          <TextField label="Seu nome completo" autoComplete="name" value={name} onChange={(e) => setName(e.target.value)} error={nameError} />
          <div>
            <Typography variant="body2" sx={{ mb: 0.75, fontWeight: 500 }}>
              Assinatura
            </Typography>
            <SignaturePad ref={pad} onChange={onSignChange} />
          </div>
          <Typography variant="body2" sx={muted}>
            Ao assinar, você autoriza a oficina a fazer os itens marcados pelo valor de {formatMoney(total)}.
          </Typography>
          {error ? <ErrorBox message={error} /> : null}
          <Button full loading={busy} loadingText="Enviando…" onClick={approve} disabled={!signed || selected.size === 0}>
            Aprovar e assinar
          </Button>
          <Button variant="secondary" full onClick={() => setRejecting(true)}>
            Recusar
          </Button>
        </Paper>
      )}
    </Stack>
  )
}

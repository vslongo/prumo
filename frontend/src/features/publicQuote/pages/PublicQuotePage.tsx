import { useQuery } from '@tanstack/react-query'
import { CircleCheck, CircleX, Clock, FileX } from 'lucide-react'
import { useCallback, useRef, useState } from 'react'
import { useParams } from 'react-router'
import { formatMoney, formatPlate, formatQuantity, formatWhen } from '../../../shared/lib/format'
import { ApiError } from '../../../shared/lib/http'
import { Button } from '../../../shared/ui/Button'
import { TextAreaField, TextField } from '../../../shared/ui/Field'
import { LogoMark } from '../../../shared/ui/Logo'
import { ErrorBox, Skeleton } from '../../../shared/ui/States'
import { publicQuoteApi, type PublicQuote } from '../api'
import { SignaturePad, type SignaturePadHandle } from '../components/SignaturePad'

function Shell({ workshop, children }: { workshop?: string; children: React.ReactNode }) {
  return (
    <div className="min-h-dvh bg-bg">
      <header className="border-b border-border bg-surface">
        <div className="mx-auto flex h-16 max-w-2xl items-center px-4">
          <p className="font-display text-xl font-bold">{workshop ?? 'Orçamento'}</p>
        </div>
      </header>
      <main className="mx-auto max-w-2xl px-4 py-6">{children}</main>
      <footer className="mx-auto flex max-w-2xl items-center justify-center gap-2 px-4 pt-2 pb-10 text-sm text-muted">
        <LogoMark size={18} /> Enviado com Prumo
      </footer>
    </div>
  )
}

function Outcome({ icon: Icon, tone, title, text }: { icon: typeof CircleCheck; tone: 'success' | 'danger' | 'muted'; title: string; text: string }) {
  const color = tone === 'success' ? 'text-success' : tone === 'danger' ? 'text-danger' : 'text-muted'
  return (
    <div role="status" className="flex flex-col items-center gap-3 rounded-lg border border-border bg-surface px-6 py-10 text-center">
      <Icon size={40} className={color} aria-hidden />
      <h1 className="text-2xl font-bold">{title}</h1>
      <p className="max-w-sm text-muted">{text}</p>
    </div>
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
        <Skeleton className="mb-4 h-24" />
        <Skeleton className="h-72" />
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

  return (
    <div className="flex flex-col gap-5">
      <section>
        <p className="text-muted">Olá, {q.customer_name.split(' ')[0]}. Este é o orçamento para o seu veículo:</p>
        <div className="mt-3 flex items-center gap-3 rounded-lg border border-border bg-surface p-4">
          <span className="num rounded-sm border-2 border-text px-2 py-0.5 font-display text-lg font-bold tracking-wider">{formatPlate(q.vehicle.plate)}</span>
          <span className="font-medium">
            {[q.vehicle.brand, q.vehicle.model].filter(Boolean).join(' ')} {q.vehicle.year ?? ''}
          </span>
        </div>
      </section>

      <section className="rounded-lg border border-border bg-surface">
        <h1 className="border-b border-border px-4 py-3 text-xl font-bold">Itens do orçamento</h1>
        {q.items.length > 1 ? <p className="px-4 pt-3 text-sm text-muted">Desmarque o que não quiser fazer agora.</p> : null}
        <ul className="divide-y divide-border">
          {q.items.map((item) => (
            <li key={item.position}>
              <label className="flex cursor-pointer gap-3 px-4 py-4">
                <input type="checkbox" className="mt-1 size-5 shrink-0 accent-[var(--color-brand)]" checked={selected.has(item.position)} onChange={() => toggle(item.position)} disabled={q.items.length === 1} />
                <span className="min-w-0 flex-1">
                  <span className="flex items-start justify-between gap-3">
                    <span className="font-semibold">{item.description}</span>
                    <span className="num shrink-0 font-semibold">{formatMoney(item.total_cents)}</span>
                  </span>
                  <span className="num block text-sm text-muted">
                    {formatQuantity(item.quantity)} × {formatMoney(item.unit_price_cents)}
                  </span>
                  {item.details.length ? (
                    <span className="mt-1 block text-sm">
                      {item.details.map((d) => `${d.label}: ${typeof d.value === 'boolean' ? (d.value ? 'sim' : 'não') : String(d.value)}${d.unit ? ` ${d.unit}` : ''}`).join(' · ')}
                    </span>
                  ) : null}
                </span>
              </label>
            </li>
          ))}
        </ul>
        <div className="flex items-baseline justify-between border-t border-border px-4 py-4">
          <span className="text-muted">{selected.size === q.items.length ? 'Total' : `Total de ${selected.size} ${selected.size === 1 ? 'item' : 'itens'}`}</span>
          <span className="num font-display text-[28px] font-bold">{formatMoney(total)}</span>
        </div>
      </section>

      {q.notes ? <p className="rounded-lg border border-border bg-surface p-4 whitespace-pre-line">{q.notes}</p> : null}
      {q.expires_at ? <p className="text-sm text-muted">Válido até {formatWhen(q.expires_at)}.</p> : null}

      {rejecting ? (
        <section className="flex flex-col gap-4 rounded-lg border border-border bg-surface p-4">
          <h2 className="text-lg font-semibold">Recusar orçamento</h2>
          <TextAreaField label="Quer contar o motivo?" hint="Opcional" value={reason} onChange={(e) => setReason(e.target.value)} maxLength={500} />
          {error ? <ErrorBox message={error} /> : null}
          <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <Button variant="secondary" onClick={() => setRejecting(false)}>
              Voltar
            </Button>
            <Button variant="danger" loading={busy} loadingText="Enviando…" onClick={reject}>
              Recusar orçamento
            </Button>
          </div>
        </section>
      ) : (
        <section className="flex flex-col gap-4 rounded-lg border border-border bg-surface p-4">
          <h2 className="text-lg font-semibold">Aprovar e assinar</h2>
          <TextField label="Seu nome completo" autoComplete="name" value={name} onChange={(e) => setName(e.target.value)} error={nameError} />
          <div>
            <p className="mb-1.5 text-sm font-medium">Assinatura</p>
            <SignaturePad ref={pad} onChange={onSignChange} />
          </div>
          <p className="text-sm text-muted">Ao assinar, você autoriza a oficina a fazer os itens marcados pelo valor de {formatMoney(total)}.</p>
          {error ? <ErrorBox message={error} /> : null}
          <Button full loading={busy} loadingText="Enviando…" onClick={approve} disabled={!signed || selected.size === 0}>
            Aprovar e assinar
          </Button>
          <Button variant="secondary" full onClick={() => setRejecting(true)}>
            Recusar
          </Button>
        </section>
      )}
    </div>
  )
}

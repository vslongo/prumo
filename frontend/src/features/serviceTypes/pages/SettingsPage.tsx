import { useQueryClient } from '@tanstack/react-query'
import { Plus, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { Navigate } from 'react-router'
import { centsToInput, formatMoney, parseMoneyToCents } from '../../../shared/lib/format'
import { ApiError } from '../../../shared/lib/http'
import { Button } from '../../../shared/ui/Button'
import { CheckboxField, SelectField, TextField } from '../../../shared/ui/Field'
import { Card, CardTitle, ErrorBox, ListSkeleton, PageHeader } from '../../../shared/ui/States'
import { useToast } from '../../../shared/ui/Toast'
import { canManageSettings, useMe } from '../../auth/api'
import { fieldTypeLabel, serviceTypesApi, useServiceTypes, type FieldSchema, type FieldType } from '../api'

type DraftField = FieldSchema & { optionsText: string }

const slug = (label: string) =>
  label
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_+|_+$/g, '')
    .replace(/^(\d)/, 'f_$1')
    .slice(0, 50) || 'campo'

/** Tipos de serviço da oficina e seus campos específicos (só dono/administrador). */
export function SettingsPage() {
  const { data: me } = useMe()
  const types = useServiceTypes()
  const queryClient = useQueryClient()
  const toast = useToast()
  const [name, setName] = useState('')
  const [price, setPrice] = useState('')
  const [fields, setFields] = useState<DraftField[]>([])
  const [error, setError] = useState<string | null>(null)
  const [saving, setSaving] = useState(false)

  if (me && !canManageSettings(me)) return <Navigate to="/" replace />

  const update = (i: number, patch: Partial<DraftField>) => setFields((all) => all.map((f, j) => (j === i ? { ...f, ...patch } : f)))

  const save = async () => {
    setError(null)
    if (!name.trim()) return setError('Informe o nome do serviço.')
    const cents = price.trim() === '' ? 0 : parseMoneyToCents(price)
    if (cents === null) return setError('Valor padrão inválido (ex.: 80,00).')
    setSaving(true)
    try {
      await serviceTypesApi.create({
        name: name.trim(),
        default_price_cents: cents,
        fields_schema: fields.map(({ optionsText, ...f }) => ({
          key: slug(f.label),
          label: f.label.trim(),
          type: f.type,
          required: f.required,
          ...(f.type === 'select' ? { options: optionsText.split(',').map((o) => o.trim()).filter(Boolean) } : {}),
          ...(f.unit ? { unit: f.unit } : {}),
        })),
      })
      queryClient.invalidateQueries({ queryKey: ['service-types'] })
      setName('')
      setPrice('')
      setFields([])
      toast('Tipo de serviço salvo.')
    } catch (e) {
      setError(e instanceof ApiError ? (Object.values(e.errors)[0]?.[0] ?? e.message) : 'Não deu para salvar. Tente de novo.')
    } finally {
      setSaving(false)
    }
  }

  return (
    <>
      <PageHeader title="Configurações" subtitle="Tipos de serviço e os campos que o técnico preenche em cada um." />

      <div className="grid gap-4 lg:grid-cols-[1fr_1fr] lg:items-start">
        <Card>
          <CardTitle>Tipos de serviço</CardTitle>
          {types.isLoading ? (
            <ListSkeleton rows={3} />
          ) : types.isError ? (
            <ErrorBox message="Não deu para carregar." onRetry={() => types.refetch()} />
          ) : (
            <ul className="divide-y divide-border">
              {types.data?.map((t) => (
                <li key={t.id} className="py-3 first:pt-0 last:pb-0">
                  <div className="flex items-baseline justify-between gap-3">
                    <p className="font-semibold">{t.name}</p>
                    <p className="num text-sm text-muted">{t.default_price_cents ? formatMoney(t.default_price_cents) : '—'}</p>
                  </div>
                  <p className="text-sm text-muted">
                    {t.fields_schema.length ? t.fields_schema.map((f) => f.label).join(' · ') : 'Sem campos específicos'}
                    {t.is_system_default ? ' · padrão Prumo' : ''}
                  </p>
                </li>
              ))}
            </ul>
          )}
        </Card>

        <Card>
          <CardTitle>Novo tipo de serviço</CardTitle>
          <div className="flex flex-col gap-4">
            {error ? <ErrorBox message={error} /> : null}
            <TextField label="Nome do serviço" placeholder="Ex.: Alinhamento" value={name} onChange={(e) => setName(e.target.value)} />
            <TextField label="Valor padrão (R$)" hint="Opcional. Preenche o valor do item." inputMode="decimal" placeholder="0,00" value={price} onChange={(e) => setPrice(e.target.value)} onBlur={() => { const c = parseMoneyToCents(price); if (c !== null) setPrice(centsToInput(c)) }} />

            {fields.map((f, i) => (
              <fieldset key={i} className="flex flex-col gap-3 rounded-md border border-border bg-bg p-4">
                <legend className="px-1 text-sm font-semibold">Campo {i + 1}</legend>
                <TextField label="Nome do campo" placeholder="Ex.: Lado" value={f.label} onChange={(e) => update(i, { label: e.target.value })} />
                <div className="grid grid-cols-2 gap-3">
                  <SelectField label="Tipo" value={f.type} onChange={(e) => update(i, { type: e.target.value as FieldType })}>
                    {(Object.keys(fieldTypeLabel) as FieldType[]).map((t) => (
                      <option key={t} value={t}>
                        {fieldTypeLabel[t]}
                      </option>
                    ))}
                  </SelectField>
                  <TextField label="Unidade" hint="Opcional" placeholder="km, L…" value={f.unit ?? ''} onChange={(e) => update(i, { unit: e.target.value })} />
                </div>
                {f.type === 'select' ? <TextField label="Opções" hint="Separe por vírgula." placeholder="esquerdo, direito, ambos" value={f.optionsText} onChange={(e) => update(i, { optionsText: e.target.value })} /> : null}
                <div className="flex items-center justify-between">
                  <CheckboxField label="Obrigatório" checked={f.required} onChange={(e) => update(i, { required: e.target.checked })} />
                  <Button variant="ghost" className="h-9 text-danger" icon={<Trash2 size={16} aria-hidden />} onClick={() => setFields((all) => all.filter((_, j) => j !== i))}>
                    Remover
                  </Button>
                </div>
              </fieldset>
            ))}

            <Button variant="secondary" icon={<Plus size={18} aria-hidden />} onClick={() => setFields((all) => [...all, { key: '', label: '', type: 'text', required: false, unit: '', optionsText: '' }])}>
              Adicionar campo
            </Button>
            <Button onClick={save} loading={saving} loadingText="Salvando…">
              Salvar tipo de serviço
            </Button>
          </div>
        </Card>
      </div>
    </>
  )
}

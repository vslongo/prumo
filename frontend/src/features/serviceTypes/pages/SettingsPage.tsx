import Box from '@mui/material/Box'
import Stack from '@mui/material/Stack'
import Tab from '@mui/material/Tab'
import Tabs from '@mui/material/Tabs'
import Typography from '@mui/material/Typography'
import { useQueryClient } from '@tanstack/react-query'
import { Plus, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { Navigate, useSearchParams } from 'react-router'
import { centsToInput, formatMoney, parseMoneyToCents } from '../../../shared/lib/format'
import { ApiError } from '../../../shared/lib/http'
import { Button } from '../../../shared/ui/Button'
import { CheckboxField, SelectField, TextField } from '../../../shared/ui/Field'
import { Card, CardTitle, ErrorBox, ListSkeleton, PageHeader } from '../../../shared/ui/States'
import { fieldsetSx, legendSx, muted } from '../../../shared/ui/styles'
import { useToast } from '../../../shared/ui/Toast'
import { canManageSettings, useMe } from '../../auth/api'
import { TeamCard } from '../../auth/components/TeamCard'
import { fieldTypeLabel, serviceTypesApi, useServiceTypes, type FieldSchema, type FieldType } from '../api'

type DraftField = FieldSchema & { optionsText: string }
type SettingsTab = 'equipe' | 'servicos'

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
  const [searchParams, setSearchParams] = useSearchParams()
  const tab: SettingsTab = searchParams.get('aba') === 'servicos' ? 'servicos' : 'equipe'
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
      <PageHeader title="Configurações" subtitle={tab === 'equipe' ? 'Quem trabalha na oficina e o convite para novos funcionários.' : 'Tipos de serviço e os campos que o técnico preenche em cada um.'} />

      {/* Aba na URL (?aba=servicos): dá para mandar o link direto para a aba. */}
      <Tabs value={tab} onChange={(_, value: SettingsTab) => setSearchParams(value === 'equipe' ? {} : { aba: value }, { replace: true })} variant="scrollable" allowScrollButtonsMobile sx={{ mb: 2, borderBottom: 1, borderColor: 'divider' }}>
        <Tab value="equipe" label="Equipe" />
        <Tab value="servicos" label="Tipos de serviço" />
      </Tabs>

      <Box sx={{ display: 'grid', gap: 2, gridTemplateColumns: { xs: 'minmax(0, 1fr)', lg: 'repeat(2, minmax(0, 1fr))' }, alignItems: { lg: 'start' } }}>
        {tab === 'equipe' ? (
          <TeamCard />
        ) : (
          <>
            <Card>
              <CardTitle>Tipos de serviço</CardTitle>
              {types.isLoading ? (
                <ListSkeleton rows={3} />
              ) : types.isError ? (
                <ErrorBox message="Não deu para carregar." onRetry={() => types.refetch()} />
              ) : (
                <Box component="ul" sx={{ m: 0, p: 0, listStyle: 'none', '& > li': { py: 1.5 }, '& > li + li': { borderTop: 1, borderColor: 'divider' }, '& > li:first-of-type': { pt: 0 }, '& > li:last-of-type': { pb: 0 } }}>
                  {types.data?.map((t) => (
                    <li key={t.id}>
                      <Stack direction="row" sx={{ alignItems: 'baseline', justifyContent: 'space-between', gap: 1.5 }}>
                        <Typography sx={{ fontWeight: 600 }}>{t.name}</Typography>
                        <Typography variant="body2" className="num" sx={muted}>
                          {t.default_price_cents ? formatMoney(t.default_price_cents) : '—'}
                        </Typography>
                      </Stack>
                      <Typography variant="body2" sx={muted}>
                        {t.fields_schema.length ? t.fields_schema.map((f) => f.label).join(' · ') : 'Sem campos específicos'}
                        {t.is_system_default ? ' · padrão Prumo' : ''}
                      </Typography>
                    </li>
                  ))}
                </Box>
              )}
            </Card>

            <Card>
              <CardTitle>Novo tipo de serviço</CardTitle>
              <Stack sx={{ gap: 2 }}>
                {error ? <ErrorBox message={error} /> : null}
                <TextField label="Nome do serviço" placeholder="Ex.: Alinhamento" value={name} onChange={(e) => setName(e.target.value)} />
                <TextField label="Valor padrão (R$)" hint="Opcional. Preenche o valor do item." inputMode="decimal" placeholder="0,00" value={price} onChange={(e) => setPrice(e.target.value)} onBlur={() => { const c = parseMoneyToCents(price); if (c !== null) setPrice(centsToInput(c)) }} />

                {fields.map((f, i) => (
                  <Stack component="fieldset" key={i} sx={[fieldsetSx, { gap: 1.5 }]}>
                    <Box component="legend" sx={legendSx}>
                      Campo {i + 1}
                    </Box>
                    <TextField label="Nome do campo" placeholder="Ex.: Lado" value={f.label} onChange={(e) => update(i, { label: e.target.value })} />
                    <Box sx={{ display: 'grid', gridTemplateColumns: 'repeat(2, minmax(0, 1fr))', gap: 1.5 }}>
                      <SelectField label="Tipo" value={f.type} onChange={(e) => update(i, { type: e.target.value as FieldType })}>
                        {(Object.keys(fieldTypeLabel) as FieldType[]).map((t) => (
                          <option key={t} value={t}>
                            {fieldTypeLabel[t]}
                          </option>
                        ))}
                      </SelectField>
                      <TextField label="Unidade" hint="Opcional" placeholder="km, L…" value={f.unit ?? ''} onChange={(e) => update(i, { unit: e.target.value })} />
                    </Box>
                    {f.type === 'select' ? <TextField label="Opções" hint="Separe por vírgula." placeholder="esquerdo, direito, ambos" value={f.optionsText} onChange={(e) => update(i, { optionsText: e.target.value })} /> : null}
                    <Stack direction="row" sx={{ alignItems: 'center', justifyContent: 'space-between' }}>
                      <CheckboxField label="Obrigatório" checked={f.required} onChange={(e) => update(i, { required: e.target.checked })} />
                      <Button variant="ghost" tone="danger" size="small" icon={<Trash2 size={16} aria-hidden />} onClick={() => setFields((all) => all.filter((_, j) => j !== i))}>
                        Remover
                      </Button>
                    </Stack>
                  </Stack>
                ))}

                <Button variant="secondary" icon={<Plus size={18} aria-hidden />} onClick={() => setFields((all) => [...all, { key: '', label: '', type: 'text', required: false, unit: '', optionsText: '' }])}>
                  Adicionar campo
                </Button>
                <Button onClick={save} loading={saving} loadingText="Salvando…">
                  Salvar tipo de serviço
                </Button>
              </Stack>
            </Card>
          </>
        )}
      </Box>
    </>
  )
}

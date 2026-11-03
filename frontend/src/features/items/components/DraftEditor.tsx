import Box from '@mui/material/Box'
import Skeleton from '@mui/material/Skeleton'
import Stack from '@mui/material/Stack'
import { Trash2 } from 'lucide-react'
import { useEffect, useRef, useState } from 'react'
import { ApiError } from '../../../shared/lib/http'
import { Button } from '../../../shared/ui/Button'
import { TextAreaField, TextField } from '../../../shared/ui/Field'
import { Card, CardTitle, ErrorBox } from '../../../shared/ui/States'
import { muted } from '../../../shared/ui/styles'
import { useMe, useUsers } from '../../auth/api'
import { CustomerVehiclePicker, type Selection } from '../../customers/components/CustomerVehiclePicker'
import { useServiceTypes } from '../../serviceTypes/api'
import type { ItemInput } from '../types'
import { ItemForm } from './ItemForm'
import { ItemList, ItemsTotal } from './ItemList'
import { ItemPhotos } from './ItemPhotos'

/** `photos[i]` são as fotos do `items[i]`: sobem depois de salvar, sem atrasar o salvamento. */
export type Draft = { customerId: string; vehicleId: string; mileage: number | null; notes: string; items: ItemInput[]; photos: File[][] }

type DraftPhoto = { key: string; file: File; url: string }
type DraftItem = ItemInput & { photos: DraftPhoto[] }

type Props = {
  mileageLabel: string
  submitLabel: string
  submittingLabel: string
  requireItems?: boolean
  onSubmit: (draft: Draft) => Promise<void>
}

/** Montagem de orçamento ou OS direta: cliente e veículo → itens → salvar. Tudo numa tela, pensado para o celular. */
export function DraftEditor({ mileageLabel, submitLabel, submittingLabel, requireItems, onSubmit }: Props) {
  const { data: me } = useMe()
  const users = useUsers()
  const types = useServiceTypes()
  const [selection, setSelection] = useState<Selection | null>(null)
  const [mileage, setMileage] = useState('')
  const [notes, setNotes] = useState('')
  const [items, setItems] = useState<DraftItem[]>([])
  const [error, setError] = useState<string | null>(null)
  const [saving, setSaving] = useState(false)

  // Prévias locais (object URL): liberadas ao sair da tela.
  const previews = useRef(new Set<string>())
  useEffect(() => () => previews.current.forEach((u) => URL.revokeObjectURL(u)), [])

  const addPhotos = (index: number, files: File[]) => {
    const added = files.map((file) => {
      const url = URL.createObjectURL(file)
      previews.current.add(url)
      return { key: url, file, url }
    })
    setItems((all) => all.map((it, i) => (i === index ? { ...it, photos: [...it.photos, ...added] } : it)))
  }

  const removePhoto = (index: number, key: string) => {
    URL.revokeObjectURL(key)
    previews.current.delete(key)
    setItems((all) => all.map((it, i) => (i === index ? { ...it, photos: it.photos.filter((p) => p.key !== key) } : it)))
  }

  const total = items.reduce((s, i) => s + Math.round(i.unit_price_cents * Number(i.quantity)), 0)
  const mileageNumber = mileage.trim() === '' ? null : Number(mileage.replace(/\D/g, ''))

  const submit = async () => {
    setError(null)
    if (!selection?.vehicle) return setError('Escolha o cliente e o veículo.')
    if (requireItems && items.length === 0) return setError('Adicione ao menos um item.')
    setSaving(true)
    try {
      await onSubmit({
        customerId: selection.customer.id,
        vehicleId: selection.vehicle.id,
        mileage: mileageNumber,
        notes: notes.trim(),
        items: items.map(({ photos: _photos, ...item }) => item),
        photos: items.map((it) => it.photos.map((p) => p.file)),
      })
    } catch (e) {
      setError(e instanceof ApiError ? (Object.values(e.errors)[0]?.[0] ?? e.message) : 'Não deu para salvar. Tente de novo.')
      setSaving(false)
    }
  }

  return (
    <Box sx={{ display: 'grid', gap: 2, gridTemplateColumns: { xs: 'minmax(0, 1fr)', lg: 'minmax(0, 1fr) 340px' }, alignItems: { lg: 'start' } }}>
      <Stack sx={{ gap: 2 }}>
        <Card>
          <CardTitle>1. Cliente e veículo</CardTitle>
          <CustomerVehiclePicker value={selection} onChange={setSelection} />
          {selection?.vehicle ? (
            <Box sx={{ mt: 2, display: 'grid', gap: 2, gridTemplateColumns: { xs: 'minmax(0, 1fr)', sm: 'repeat(2, minmax(0, 1fr))' } }}>
              <TextField label={mileageLabel} hint="Opcional" inputMode="numeric" value={mileage} onChange={(e) => setMileage(e.target.value.replace(/\D/g, ''))} />
            </Box>
          ) : null}
        </Card>

        <Card>
          <CardTitle>2. Itens</CardTitle>
          {items.length > 0 ? (
            <Box sx={{ mb: 2.5 }}>
              <ItemList
                items={items}
                users={users.data ?? []}
                schemaFor={(i) => types.data?.find((t) => t.id === i.service_type_id)?.fields_schema ?? []}
                actions={(_, index) => (
                  <Button variant="ghost" tone="danger" size="small" icon={<Trash2 size={16} aria-hidden />} onClick={() => setItems((all) => all.filter((_, i) => i !== index))}>
                    Remover
                  </Button>
                )}
                extra={(item) => {
                  const index = items.indexOf(item)
                  return (
                    <ItemPhotos
                      photos={item.photos.map((p) => ({ key: p.key, url: p.url, thumb_url: p.url, removable: true }))}
                      onAdd={(files) => addPhotos(index, files)}
                      onRemove={(key) => removePhoto(index, key)}
                    />
                  )
                }}
              />
            </Box>
          ) : null}
          {users.isLoading || types.isLoading ? (
            <Skeleton height={256} />
          ) : users.isError || types.isError ? (
            <ErrorBox message="Não deu para carregar técnicos e tipos de serviço." onRetry={() => (users.refetch(), types.refetch())} />
          ) : (
            <Box sx={items.length > 0 ? { borderTop: 1, borderColor: 'divider', pt: 2.5 } : undefined}>
              <ItemForm serviceTypes={types.data ?? []} users={users.data ?? []} defaultTechnicianId={me?.id} onSubmit={(item) => setItems((all) => [...all, { ...item, photos: [] }])} />
            </Box>
          )}
        </Card>

        <Card>
          <CardTitle>3. Observações</CardTitle>
          <TextAreaField label="Observações para a oficina e o cliente" hint="Opcional" value={notes} onChange={(e) => setNotes(e.target.value)} maxLength={2000} />
        </Card>
      </Stack>

      <Box component="aside" sx={{ position: { lg: 'sticky' }, top: { lg: 32 } }}>
        <Card>
          <CardTitle>Resumo</CardTitle>
          <Stack component="dl" sx={{ gap: 1, m: 0, fontSize: 14, '& > div': { display: 'flex', justifyContent: 'space-between', gap: 1.5 }, '& dt': muted, '& dd': { m: 0, fontWeight: 500, minWidth: 0, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' } }}>
            <div>
              <dt>Cliente</dt>
              <dd>{selection?.customer.name ?? '—'}</dd>
            </div>
            <div>
              <dt>Veículo</dt>
              <dd className="num">{selection?.vehicle?.plate ?? '—'}</dd>
            </div>
            <div>
              <dt>Itens</dt>
              <dd className="num">{items.length}</dd>
            </div>
          </Stack>
          <ItemsTotal cents={total} />
          {error ? (
            <Box sx={{ mt: 2 }}>
              <ErrorBox message={error} />
            </Box>
          ) : null}
          <Button full sx={{ mt: 2 }} onClick={submit} loading={saving} loadingText={submittingLabel}>
            {submitLabel}
          </Button>
        </Card>
      </Box>
    </Box>
  )
}

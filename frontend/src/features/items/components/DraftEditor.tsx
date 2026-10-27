import { Trash2 } from 'lucide-react'
import { useState } from 'react'
import { ApiError } from '../../../shared/lib/http'
import { Button } from '../../../shared/ui/Button'
import { TextAreaField, TextField } from '../../../shared/ui/Field'
import { Card, CardTitle, ErrorBox, Skeleton } from '../../../shared/ui/States'
import { useMe, useUsers } from '../../auth/api'
import { CustomerVehiclePicker, type Selection } from '../../customers/components/CustomerVehiclePicker'
import { useServiceTypes } from '../../serviceTypes/api'
import type { ItemInput } from '../types'
import { ItemForm } from './ItemForm'
import { ItemList, ItemsTotal } from './ItemList'

export type Draft = { customerId: string; vehicleId: string; mileage: number | null; notes: string; items: ItemInput[] }

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
  const [items, setItems] = useState<ItemInput[]>([])
  const [error, setError] = useState<string | null>(null)
  const [saving, setSaving] = useState(false)

  const total = items.reduce((s, i) => s + Math.round(i.unit_price_cents * Number(i.quantity)), 0)
  const mileageNumber = mileage.trim() === '' ? null : Number(mileage.replace(/\D/g, ''))

  const submit = async () => {
    setError(null)
    if (!selection?.vehicle) return setError('Escolha o cliente e o veículo.')
    if (requireItems && items.length === 0) return setError('Adicione ao menos um item.')
    setSaving(true)
    try {
      await onSubmit({ customerId: selection.customer.id, vehicleId: selection.vehicle.id, mileage: mileageNumber, notes: notes.trim(), items })
    } catch (e) {
      setError(e instanceof ApiError ? (Object.values(e.errors)[0]?.[0] ?? e.message) : 'Não deu para salvar. Tente de novo.')
      setSaving(false)
    }
  }

  return (
    <div className="grid gap-4 lg:grid-cols-[1fr_340px] lg:items-start">
      <div className="flex flex-col gap-4">
        <Card>
          <CardTitle>1. Cliente e veículo</CardTitle>
          <CustomerVehiclePicker value={selection} onChange={setSelection} />
          {selection?.vehicle ? (
            <div className="mt-4 grid gap-4 sm:grid-cols-2">
              <TextField label={mileageLabel} hint="Opcional" inputMode="numeric" value={mileage} onChange={(e) => setMileage(e.target.value.replace(/\D/g, ''))} />
            </div>
          ) : null}
        </Card>

        <Card>
          <CardTitle>2. Itens</CardTitle>
          {items.length > 0 ? (
            <div className="mb-5">
              <ItemList
                items={items}
                users={users.data ?? []}
                schemaFor={(i) => types.data?.find((t) => t.id === i.service_type_id)?.fields_schema ?? []}
                actions={(_, index) => (
                  <Button variant="ghost" className="h-9 text-danger" icon={<Trash2 size={16} aria-hidden />} onClick={() => setItems((all) => all.filter((_, i) => i !== index))}>
                    Remover
                  </Button>
                )}
              />
            </div>
          ) : null}
          {users.isLoading || types.isLoading ? (
            <Skeleton className="h-64" />
          ) : users.isError || types.isError ? (
            <ErrorBox message="Não deu para carregar técnicos e tipos de serviço." onRetry={() => (users.refetch(), types.refetch())} />
          ) : (
            <div className={items.length > 0 ? 'border-t border-border pt-5' : ''}>
              <ItemForm serviceTypes={types.data ?? []} users={users.data ?? []} defaultTechnicianId={me?.id} onSubmit={(item) => setItems((all) => [...all, item])} />
            </div>
          )}
        </Card>

        <Card>
          <CardTitle>3. Observações</CardTitle>
          <TextAreaField label="Observações para a oficina e o cliente" hint="Opcional" value={notes} onChange={(e) => setNotes(e.target.value)} maxLength={2000} />
        </Card>
      </div>

      <aside className="lg:sticky lg:top-8">
        <Card>
          <CardTitle>Resumo</CardTitle>
          <dl className="flex flex-col gap-2 text-sm">
            <div className="flex justify-between gap-3">
              <dt className="text-muted">Cliente</dt>
              <dd className="truncate font-medium">{selection?.customer.name ?? '—'}</dd>
            </div>
            <div className="flex justify-between gap-3">
              <dt className="text-muted">Veículo</dt>
              <dd className="num font-medium">{selection?.vehicle?.plate ?? '—'}</dd>
            </div>
            <div className="flex justify-between gap-3">
              <dt className="text-muted">Itens</dt>
              <dd className="num font-medium">{items.length}</dd>
            </div>
          </dl>
          <ItemsTotal cents={total} />
          {error ? (
            <div className="mt-4">
              <ErrorBox message={error} />
            </div>
          ) : null}
          <Button full className="mt-4" onClick={submit} loading={saving} loadingText={submittingLabel}>
            {submitLabel}
          </Button>
        </Card>
      </aside>
    </div>
  )
}

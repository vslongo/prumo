import { useQuery, useQueryClient } from '@tanstack/react-query'
import { Car, ClipboardList, Plus } from 'lucide-react'
import { useState } from 'react'
import { useParams } from 'react-router'
import { formatPlate } from '../../../shared/lib/format'
import { BackLink } from '../../../shared/ui/BackLink'
import { Button, ButtonLink } from '../../../shared/ui/Button'
import { Card, CardTitle, Detail, EmptyState, ErrorBox, ListSkeleton, PageHeader, Skeleton } from '../../../shared/ui/States'
import { useToast } from '../../../shared/ui/Toast'
import { serviceOrdersApi } from '../../serviceOrders/api'
import { ServiceOrderList } from '../../serviceOrders/components/ServiceOrderList'
import { vehicleName, vehiclesApi } from '../../vehicles/api'
import { VehicleForm } from '../../vehicles/components/VehicleForm'
import { customersApi, formatPhone } from '../api'

export function CustomerDetailPage() {
  const { id = '' } = useParams()
  const [adding, setAdding] = useState(false)
  const [vehicleId, setVehicleId] = useState<string | null>(null)
  const queryClient = useQueryClient()
  const toast = useToast()

  const customer = useQuery({ queryKey: ['customers', id], queryFn: () => customersApi.get(id) })
  const vehicles = useQuery({ queryKey: ['customers', id, 'vehicles'], queryFn: () => vehiclesApi.byCustomer(id) })
  const selected = vehicleId ?? vehicles.data?.[0]?.id ?? null
  const history = useQuery({
    queryKey: ['service-orders', 'list', { vehicleId: selected }],
    queryFn: () => serviceOrdersApi.list({ vehicleId: selected! }),
    enabled: !!selected,
  })

  if (customer.isLoading) return <Skeleton className="h-64" />
  if (customer.isError || !customer.data) return <ErrorBox message="Não deu para carregar o cliente." onRetry={() => customer.refetch()} />

  const c = customer.data

  return (
    <>
      <PageHeader back={<BackLink to="/clientes" label="Clientes" />} title={c.name} subtitle={formatPhone(c.phone)} />

      <div className="grid gap-4 lg:grid-cols-[340px_1fr] lg:items-start">
        <div className="flex flex-col gap-4">
          <Card>
            <dl className="grid gap-4">
              <Detail label="Celular">{formatPhone(c.phone)}</Detail>
              {c.email ? <Detail label="E-mail">{c.email}</Detail> : null}
              <Detail label="WhatsApp">{c.consent_whatsapp_at ? 'Aceita receber' : 'Sem autorização'}</Detail>
            </dl>
          </Card>

          <Card>
            <CardTitle
              action={
                adding ? null : (
                  <Button variant="ghost" icon={<Plus size={18} aria-hidden />} onClick={() => setAdding(true)}>
                    Veículo
                  </Button>
                )
              }
            >
              Veículos
            </CardTitle>
            {adding ? (
              <div className="mb-3">
                <VehicleForm
                  customerId={id}
                  onCancel={() => setAdding(false)}
                  onCreated={(v) => {
                    queryClient.invalidateQueries({ queryKey: ['customers', id, 'vehicles'] })
                    setAdding(false)
                    setVehicleId(v.id)
                    toast('Veículo cadastrado.')
                  }}
                />
              </div>
            ) : null}
            {vehicles.isLoading ? (
              <Skeleton className="h-14" />
            ) : vehicles.data?.length ? (
              <div role="radiogroup" aria-label="Veículos" className="flex flex-col gap-2">
                {vehicles.data.map((v) => (
                  <button
                    key={v.id}
                    type="button"
                    role="radio"
                    aria-checked={selected === v.id}
                    onClick={() => setVehicleId(v.id)}
                    className={`flex min-h-14 items-center gap-3 rounded-md border px-3 text-left ${selected === v.id ? 'border-brand ring-1 ring-brand' : 'border-border hover:bg-surface-2'}`}
                  >
                    <Car size={20} className="text-muted" aria-hidden />
                    <span className="num font-semibold">{formatPlate(v.plate)}</span>
                    <span className="truncate text-muted">
                      {vehicleName(v)} {v.year ?? ''}
                    </span>
                  </button>
                ))}
              </div>
            ) : (
              <p className="text-muted">Nenhum veículo cadastrado.</p>
            )}
          </Card>
        </div>

        <Card>
          <CardTitle>Histórico do veículo</CardTitle>
          {!selected ? (
            <p className="text-muted">Cadastre um veículo para ver o histórico.</p>
          ) : history.isLoading ? (
            <ListSkeleton rows={2} />
          ) : history.data?.data.length ? (
            <ServiceOrderList orders={history.data.data} />
          ) : (
            <EmptyState icon={ClipboardList} title="Nenhuma OS para este veículo." text="As OS aparecem aqui assim que forem abertas." action={<ButtonLink to="/os/nova">Nova OS</ButtonLink>} />
          )}
        </Card>
      </div>
    </>
  )
}

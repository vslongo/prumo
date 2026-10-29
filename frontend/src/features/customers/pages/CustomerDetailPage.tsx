import Box from '@mui/material/Box'
import ButtonBase from '@mui/material/ButtonBase'
import Skeleton from '@mui/material/Skeleton'
import Stack from '@mui/material/Stack'
import Typography from '@mui/material/Typography'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { Car, ClipboardList, Plus } from 'lucide-react'
import { useState } from 'react'
import { useParams } from '../../../shared/lib/navigation'
import { formatPlate } from '../../../shared/lib/format'
import { BackLink } from '../../../shared/ui/BackLink'
import { Button, ButtonLink } from '../../../shared/ui/Button'
import { Card, CardTitle, Detail, DetailGrid, EmptyState, ErrorBox, ListSkeleton, PageHeader } from '../../../shared/ui/States'
import { muted, optionRowSx } from '../../../shared/ui/styles'
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

  if (customer.isLoading) return <Skeleton height={256} />
  if (customer.isError || !customer.data) return <ErrorBox message="Não deu para carregar o cliente." onRetry={() => customer.refetch()} />

  const c = customer.data

  return (
    <>
      <PageHeader back={<BackLink to="/clientes" label="Clientes" />} title={c.name} subtitle={formatPhone(c.phone)} />

      <Box sx={{ display: 'grid', gap: 2, gridTemplateColumns: { xs: 'minmax(0, 1fr)', lg: '340px minmax(0, 1fr)' }, alignItems: { lg: 'start' } }}>
        <Stack sx={{ gap: 2 }}>
          <Card>
            <DetailGrid columns={1}>
              <Detail label="Celular">{formatPhone(c.phone)}</Detail>
              {c.email ? <Detail label="E-mail">{c.email}</Detail> : null}
              <Detail label="WhatsApp">{c.consent_whatsapp_at ? 'Aceita receber' : 'Sem autorização'}</Detail>
            </DetailGrid>
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
              <Box sx={{ mb: 1.5 }}>
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
              </Box>
            ) : null}
            {vehicles.isLoading ? (
              <Skeleton height={56} />
            ) : vehicles.data?.length ? (
              <Stack role="radiogroup" aria-label="Veículos" sx={{ gap: 1 }}>
                {vehicles.data.map((v) => (
                  <ButtonBase key={v.id} role="radio" aria-checked={selected === v.id} onClick={() => setVehicleId(v.id)} sx={[optionRowSx, { borderColor: 'divider' }]}>
                    <Box component={Car} size={20} aria-hidden sx={muted} />
                    <Box component="span" className="num" sx={{ fontWeight: 600 }}>
                      {formatPlate(v.plate)}
                    </Box>
                    <Box component="span" sx={{ ...muted, minWidth: 0, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                      {vehicleName(v)} {v.year ?? ''}
                    </Box>
                  </ButtonBase>
                ))}
              </Stack>
            ) : (
              <Typography sx={muted}>Nenhum veículo cadastrado.</Typography>
            )}
          </Card>
        </Stack>

        <Card>
          <CardTitle>Histórico do veículo</CardTitle>
          {!selected ? (
            <Typography sx={muted}>Cadastre um veículo para ver o histórico.</Typography>
          ) : history.isLoading ? (
            <ListSkeleton rows={2} />
          ) : history.data?.data.length ? (
            <ServiceOrderList orders={history.data.data} />
          ) : (
            <EmptyState icon={ClipboardList} title="Nenhuma OS para este veículo." text="As OS aparecem aqui assim que forem abertas." action={<ButtonLink to="/os/nova">Nova OS</ButtonLink>} />
          )}
        </Card>
      </Box>
    </>
  )
}

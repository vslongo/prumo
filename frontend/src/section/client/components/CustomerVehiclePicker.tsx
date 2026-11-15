import Box from '@mui/material/Box'
import ButtonBase from '@mui/material/ButtonBase'
import Skeleton from '@mui/material/Skeleton'
import Stack from '@mui/material/Stack'
import Typography from '@mui/material/Typography'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { Car, Check, UserPlus } from 'lucide-react'
import { useEffect, useState } from 'react'
import { formatPlate } from '../../../shared/lib/format'
import { Button } from '../../../shared/ui/Button'
import { SearchAdornment, TextField } from '../../../shared/ui/Field'
import { ErrorBox } from '../../../shared/ui/States'
import { muted, optionRowSx } from '../../../shared/ui/styles'
import { vehicleName, vehiclesApi, type Vehicle } from '../../vehicles/api'
import { VehicleForm } from '../../vehicles/components/VehicleForm'
import { customersApi, formatPhone, type Customer } from '../api'
import { CustomerForm } from './CustomerForm'

export type Selection = { customer: Customer; vehicle: Vehicle | null }

function useDebounced<T>(value: T, ms = 300): T {
  const [v, setV] = useState(value)
  useEffect(() => {
    const t = setTimeout(() => setV(value), ms)
    return () => clearTimeout(t)
  }, [value, ms])
  return v
}

/** Escolhe cliente (busca por nome, celular ou CPF) e o veículo dele; cadastra na hora se não existir. */
export function CustomerVehiclePicker({ value, onChange }: { value: Selection | null; onChange: (s: Selection | null) => void }) {
  const [term, setTerm] = useState('')
  const [mode, setMode] = useState<'search' | 'new-customer' | 'new-vehicle'>('search')
  const q = useDebounced(term.trim())
  const queryClient = useQueryClient()

  const results = useQuery({ queryKey: ['customers', 'search', q], queryFn: () => customersApi.search(q), enabled: !value && mode === 'search' })
  const vehicles = useQuery({
    queryKey: ['customers', value?.customer.id, 'vehicles'],
    queryFn: () => vehiclesApi.byCustomer(value!.customer.id),
    enabled: !!value,
  })

  // Cliente com um carro só: já seleciona.
  useEffect(() => {
    if (value && !value.vehicle && vehicles.data?.length === 1) onChange({ ...value, vehicle: vehicles.data[0] })
  }, [value, vehicles.data, onChange])

  if (mode === 'new-customer') {
    return (
      <CustomerForm
        onCancel={() => setMode('search')}
        onCreated={(customer, vehicle) => {
          queryClient.invalidateQueries({ queryKey: ['customers'] })
          setMode('search')
          onChange({ customer, vehicle })
        }}
      />
    )
  }

  if (value) {
    return (
      <Stack sx={{ gap: 2 }}>
        <Stack direction="row" sx={{ alignItems: 'center', justifyContent: 'space-between', gap: 1.5, border: 1, borderColor: 'divider', borderRadius: 1, bgcolor: 'background.default', p: 1.5 }}>
          <Box sx={{ minWidth: 0 }}>
            <Typography noWrap sx={{ fontWeight: 600 }}>
              {value.customer.name}
            </Typography>
            <Typography variant="body2" sx={muted}>
              {formatPhone(value.customer.phone)}
            </Typography>
          </Box>
          <Button variant="ghost" onClick={() => onChange(null)}>
            Trocar
          </Button>
        </Stack>

        <div>
          <Typography variant="body2" sx={{ mb: 1, fontWeight: 500 }}>
            Veículo
          </Typography>
          {vehicles.isLoading ? (
            <Skeleton height={56} />
          ) : vehicles.isError ? (
            <ErrorBox message="Não deu para carregar os veículos." onRetry={() => vehicles.refetch()} />
          ) : mode === 'new-vehicle' ? (
            <VehicleForm
              customerId={value.customer.id}
              onCancel={() => setMode('search')}
              onCreated={(v) => {
                queryClient.invalidateQueries({ queryKey: ['customers', value.customer.id, 'vehicles'] })
                setMode('search')
                onChange({ ...value, vehicle: v })
              }}
            />
          ) : (
            <Stack role="radiogroup" aria-label="Veículo" sx={{ gap: 1 }}>
              {vehicles.data?.map((v) => {
                const selected = value.vehicle?.id === v.id
                return (
                  <ButtonBase key={v.id} role="radio" aria-checked={selected} onClick={() => onChange({ ...value, vehicle: v })} sx={optionRowSx}>
                    <Box component={Car} size={20} aria-hidden sx={{ flexShrink: 0, ...muted }} />
                    <Box component="span" className="num" sx={{ fontWeight: 600 }}>
                      {formatPlate(v.plate)}
                    </Box>
                    <Box component="span" sx={{ ...muted, minWidth: 0, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                      {vehicleName(v)}
                      {v.year ? ` ${v.year}` : ''}
                    </Box>
                    {selected ? <Box component={Check} size={20} aria-hidden sx={{ ml: 'auto', flexShrink: 0, color: 'primary.main' }} /> : null}
                  </ButtonBase>
                )
              })}
              <Button variant="ghost" sx={{ alignSelf: 'flex-start' }} onClick={() => setMode('new-vehicle')}>
                Adicionar outro veículo
              </Button>
            </Stack>
          )}
        </div>
      </Stack>
    )
  }

  return (
    <Stack sx={{ gap: 1.5 }}>
      <TextField label="Buscar cliente" placeholder="Nome, celular ou CPF" value={term} onChange={(e) => setTerm(e.target.value)} autoComplete="off" startAdornment={<SearchAdornment />} />
      {results.isLoading ? (
        <Skeleton height={56} />
      ) : results.isError ? (
        <ErrorBox message="Não deu para buscar clientes." onRetry={() => results.refetch()} />
      ) : (
        <Stack component="ul" sx={{ gap: 0.75, m: 0, p: 0, listStyle: 'none' }}>
          {results.data?.data.slice(0, 6).map((c) => (
            <li key={c.id}>
              <ButtonBase onClick={() => onChange({ customer: c, vehicle: null })} sx={[optionRowSx, { justifyContent: 'space-between', borderColor: 'divider' }]}>
                <Box component="span" sx={{ fontWeight: 600, minWidth: 0, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                  {c.name}
                </Box>
                <Box component="span" className="num" sx={{ flexShrink: 0, fontSize: 14, ...muted }}>
                  {formatPhone(c.phone)}
                </Box>
              </ButtonBase>
            </li>
          ))}
          {q && results.data?.data.length === 0 ? (
            <Box component="li" sx={{ py: 1, ...muted }}>
              Nada encontrado para “{q}”. Confira o nome ou o celular.
            </Box>
          ) : null}
        </Stack>
      )}
      <Button variant="secondary" icon={<UserPlus size={18} aria-hidden />} onClick={() => setMode('new-customer')}>
        Cadastrar cliente
      </Button>
    </Stack>
  )
}

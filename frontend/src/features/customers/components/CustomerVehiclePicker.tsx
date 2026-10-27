import { useQuery, useQueryClient } from '@tanstack/react-query'
import { Car, Check, Search, UserPlus } from 'lucide-react'
import { useEffect, useState } from 'react'
import { formatPlate } from '../../../shared/lib/format'
import { Button } from '../../../shared/ui/Button'
import { TextField } from '../../../shared/ui/Field'
import { ErrorBox, Skeleton } from '../../../shared/ui/States'
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
      <div className="flex flex-col gap-4">
        <div className="flex items-center justify-between gap-3 rounded-md border border-border bg-bg p-3">
          <div className="min-w-0">
            <p className="truncate font-semibold">{value.customer.name}</p>
            <p className="text-sm text-muted">{formatPhone(value.customer.phone)}</p>
          </div>
          <Button variant="ghost" onClick={() => onChange(null)}>
            Trocar
          </Button>
        </div>

        <div>
          <p className="mb-2 text-sm font-medium">Veículo</p>
          {vehicles.isLoading ? (
            <Skeleton className="h-14" />
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
            <div role="radiogroup" aria-label="Veículo" className="flex flex-col gap-2">
              {vehicles.data?.map((v) => {
                const selected = value.vehicle?.id === v.id
                return (
                  <button
                    key={v.id}
                    type="button"
                    role="radio"
                    aria-checked={selected}
                    onClick={() => onChange({ ...value, vehicle: v })}
                    className={`flex min-h-14 items-center gap-3 rounded-md border px-3 text-left ${selected ? 'border-brand bg-surface ring-1 ring-brand' : 'border-border-strong bg-surface hover:bg-surface-2'}`}
                  >
                    <Car size={20} className="shrink-0 text-muted" aria-hidden />
                    <span className="num font-semibold">{formatPlate(v.plate)}</span>
                    <span className="truncate text-muted">
                      {vehicleName(v)}
                      {v.year ? ` ${v.year}` : ''}
                    </span>
                    {selected ? <Check size={20} className="ml-auto shrink-0 text-brand" aria-hidden /> : null}
                  </button>
                )
              })}
              <Button variant="ghost" className="self-start" onClick={() => setMode('new-vehicle')}>
                Adicionar outro veículo
              </Button>
            </div>
          )}
        </div>
      </div>
    )
  }

  return (
    <div className="flex flex-col gap-3">
      <div className="relative">
        <TextField label="Buscar cliente" placeholder="Nome, celular ou CPF" value={term} onChange={(e) => setTerm(e.target.value)} className="[&_input]:pl-10" autoComplete="off" />
        <Search size={18} className="pointer-events-none absolute bottom-[15px] left-3 text-muted" aria-hidden />
      </div>
      {results.isLoading ? (
        <Skeleton className="h-14" />
      ) : results.isError ? (
        <ErrorBox message="Não deu para buscar clientes." onRetry={() => results.refetch()} />
      ) : (
        <ul className="flex flex-col gap-1.5">
          {results.data?.data.slice(0, 6).map((c) => (
            <li key={c.id}>
              <button type="button" onClick={() => onChange({ customer: c, vehicle: null })} className="flex min-h-14 w-full items-center justify-between gap-3 rounded-md border border-border bg-surface px-3 text-left hover:bg-surface-2">
                <span className="truncate font-semibold">{c.name}</span>
                <span className="num shrink-0 text-sm text-muted">{formatPhone(c.phone)}</span>
              </button>
            </li>
          ))}
          {q && results.data?.data.length === 0 ? <li className="py-2 text-muted">Nada encontrado para “{q}”. Confira o nome ou o celular.</li> : null}
        </ul>
      )}
      <Button variant="secondary" icon={<UserPlus size={18} aria-hidden />} onClick={() => setMode('new-customer')}>
        Cadastrar cliente
      </Button>
    </div>
  )
}

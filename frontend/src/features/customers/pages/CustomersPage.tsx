import { useQuery, useQueryClient } from '@tanstack/react-query'
import { ChevronRight, Search, UserPlus, Users } from 'lucide-react'
import { useEffect, useState } from 'react'
import { Link, useNavigate } from 'react-router'
import { Button } from '../../../shared/ui/Button'
import { TextField } from '../../../shared/ui/Field'
import { Card, CardTitle, EmptyState, ErrorBox, ListSkeleton, PageHeader } from '../../../shared/ui/States'
import { useToast } from '../../../shared/ui/Toast'
import { customersApi, formatPhone } from '../api'
import { CustomerForm } from '../components/CustomerForm'

export function CustomersPage() {
  const [term, setTerm] = useState('')
  const [q, setQ] = useState('')
  const [creating, setCreating] = useState(false)
  const navigate = useNavigate()
  const toast = useToast()
  const queryClient = useQueryClient()

  useEffect(() => {
    const t = setTimeout(() => setQ(term.trim()), 300)
    return () => clearTimeout(t)
  }, [term])

  const list = useQuery({ queryKey: ['customers', 'search', q], queryFn: () => customersApi.search(q) })

  return (
    <>
      <PageHeader
        title="Clientes"
        action={
          creating ? null : (
            <Button icon={<UserPlus size={18} aria-hidden />} full className="sm:w-auto" onClick={() => setCreating(true)}>
              Cadastrar cliente
            </Button>
          )
        }
      />

      {creating ? (
        <Card className="mb-5">
          <CardTitle>Novo cliente</CardTitle>
          <CustomerForm
            onCancel={() => setCreating(false)}
            onCreated={(c) => {
              queryClient.invalidateQueries({ queryKey: ['customers'] })
              toast('Cliente cadastrado.')
              navigate(`/clientes/${c.id}`)
            }}
          />
        </Card>
      ) : null}

      <div className="relative mb-4">
        <TextField label="Buscar" placeholder="Nome, celular ou CPF" value={term} onChange={(e) => setTerm(e.target.value)} className="[&_input]:pl-10" />
        <Search size={18} className="pointer-events-none absolute bottom-[15px] left-3 text-muted" aria-hidden />
      </div>

      {list.isLoading ? (
        <ListSkeleton />
      ) : list.isError ? (
        <ErrorBox message="Não deu para carregar os clientes." onRetry={() => list.refetch()} />
      ) : list.data?.data.length ? (
        <ul className="divide-y divide-border overflow-hidden rounded-lg border border-border bg-surface">
          {list.data.data.map((c) => (
            <li key={c.id}>
              <Link to={`/clientes/${c.id}`} className="flex min-h-16 items-center justify-between gap-3 px-4 py-3 hover:bg-surface-2">
                <div className="min-w-0">
                  <p className="truncate font-semibold">{c.name}</p>
                  <p className="num text-sm text-muted">{formatPhone(c.phone)}</p>
                </div>
                <ChevronRight size={18} className="shrink-0 text-muted" aria-hidden />
              </Link>
            </li>
          ))}
        </ul>
      ) : q ? (
        <EmptyState icon={Search} title={`Nada encontrado para “${q}”.`} text="Confira o nome ou o celular." />
      ) : (
        <EmptyState icon={Users} title="Nenhum cliente ainda." text="Cadastre o cliente e o carro no primeiro atendimento." />
      )}
    </>
  )
}

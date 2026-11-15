import { ENDPOINTS } from '../../../routes/paths'
import { useQueryClient } from '@tanstack/react-query'
import { Search, UserPlus, Users } from 'lucide-react'
import { useEffect, useState } from 'react'
import { useNavigate } from '../../../shared/lib/navigation'
import { Button } from '../../../shared/ui/Button'
import { SearchAdornment, TextField } from '../../../shared/ui/Field'
import { Card, CardTitle, EmptyState, PageHeader } from '../../../shared/ui/States'
import { useToast } from '../../../shared/ui/Toast'
import { formatPhone, type Customer } from '../api'
import { CustomerForm } from '../components/CustomerForm'
import { ListItems, type ListItemsColumn } from '../../../components/ListItems'

const endpoints = ENDPOINTS()

const columns: ListItemsColumn<Customer>[] = [
  { id: 'name', header: 'Nome', cell: customer => customer.name },
  { id: 'phone', header: 'Celular', cell: customer => formatPhone(customer.phone), nowrap: true },
  { id: 'email', header: 'E-mail', cell: customer => customer.email || '—' },
  { id: 'document', header: 'CPF / Documento', cell: customer => customer.document || '—', nowrap: true },
]

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


  return (
    <>
      <PageHeader
        title="Clientes"
        action={
          creating ? null : (
            <Button icon={<UserPlus size={18} aria-hidden />} full sx={{ width: { sm: 'auto' } }} onClick={() => setCreating(true)}>
              Cadastrar cliente
            </Button>
          )
        }
      />

      {creating ? (
        <Card sx={{ mb: 2.5 }}>
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

      <TextField label="Buscar" placeholder="Nome, celular ou CPF" value={term} onChange={(e) => setTerm(e.target.value)} startAdornment={<SearchAdornment />} sx={{ mb: 2 }} />

      <ListItems<Customer>
        url={`${endpoints.Clients.list}?q=${encodeURIComponent(q)}`}
        queryKey={['customers', 'search']}
        columns={columns}
        getRowHref={customer => `/clientes/${customer.id}`}
        label="Clientes"
        errorMessage="Não deu para carregar os clientes."
        emptyState={q ? (
          <EmptyState icon={Search} title={`Nada encontrado para “${q}”.`} text="Confira o nome ou o celular." />
        ) : (
          <EmptyState icon={Users} title="Nenhum cliente ainda." text="Cadastre o cliente e o carro no primeiro atendimento." />
        )}
      />
    </>
  )
}

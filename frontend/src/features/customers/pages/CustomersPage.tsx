import Box from '@mui/material/Box'
import ListItemButton from '@mui/material/ListItemButton'
import Paper from '@mui/material/Paper'
import Typography from '@mui/material/Typography'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { ChevronRight, Search, UserPlus, Users } from 'lucide-react'
import { useEffect, useState } from 'react'
import { useNavigate } from '../../../shared/lib/navigation'
import { Button } from '../../../shared/ui/Button'
import { SearchAdornment, TextField } from '../../../shared/ui/Field'
import { Card, CardTitle, EmptyState, ErrorBox, ListSkeleton, PageHeader } from '../../../shared/ui/States'
import { muted } from '../../../shared/ui/styles'
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

      {list.isLoading ? (
        <ListSkeleton />
      ) : list.isError ? (
        <ErrorBox message="Não deu para carregar os clientes." onRetry={() => list.refetch()} />
      ) : list.data?.data.length ? (
        <Paper component="ul" sx={{ m: 0, p: 0, listStyle: 'none', overflow: 'hidden' }}>
          {list.data.data.map((c, i) => (
            <li key={c.id}>
              <ListItemButton href={`/clientes/${c.id}`} divider={i < list.data.data.length - 1} sx={{ minHeight: 64, justifyContent: 'space-between', gap: 1.5, px: 2, py: 1.5 }}>
                <Box sx={{ minWidth: 0 }}>
                  <Typography noWrap sx={{ fontWeight: 600 }}>
                    {c.name}
                  </Typography>
                  <Typography variant="body2" className="num" sx={muted}>
                    {formatPhone(c.phone)}
                  </Typography>
                </Box>
                <Box component={ChevronRight} size={18} aria-hidden sx={{ flexShrink: 0, ...muted }} />
              </ListItemButton>
            </li>
          ))}
        </Paper>
      ) : q ? (
        <EmptyState icon={Search} title={`Nada encontrado para “${q}”.`} text="Confira o nome ou o celular." />
      ) : (
        <EmptyState icon={Users} title="Nenhum cliente ainda." text="Cadastre o cliente e o carro no primeiro atendimento." />
      )}
    </>
  )
}

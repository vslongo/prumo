import Box from '@mui/material/Box'
import ListItemButton from '@mui/material/ListItemButton'
import Paper from '@mui/material/Paper'
import Typography from '@mui/material/Typography'
import { ChevronRight } from 'lucide-react'
import { muted } from '../../shared/ui/styles'
import { formatPhone, type Customer } from './api'

/** Listagem de clientes com acesso aos detalhes. */
export function CustomerList({ customers }: { customers: Customer[] }) {
  return (
        <Paper component="ul" sx={{ m: 0, p: 0, listStyle: 'none', overflow: 'hidden' }}>
          {customers.map((c, i) => (
            <li key={c.id}>
              <ListItemButton href={`/clientes/${c.id}`} divider={i < customers.length - 1} sx={{ minHeight: 64, justifyContent: 'space-between', gap: 1.5, px: 2, py: 1.5 }}>
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
  )
}

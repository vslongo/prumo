import Box from '@mui/material/Box'
import ButtonBase from '@mui/material/ButtonBase'
import Paper from '@mui/material/Paper'
import Stack from '@mui/material/Stack'
import Table from '@mui/material/Table'
import TableBody from '@mui/material/TableBody'
import TableCell from '@mui/material/TableCell'
import TableHead from '@mui/material/TableHead'
import TableRow from '@mui/material/TableRow'
import { ChevronRight } from 'lucide-react'
import type { ReactNode } from 'react'
import { Link } from 'react-router'
import { muted } from './styles'

export type Column<T> = { header: string; cell: (row: T) => ReactNode; align?: 'right'; nowrap?: boolean }

type Props<T> = {
  rows: T[]
  href: (row: T) => string
  /** Linha-card do celular: título + badge, linha do meio, rodapé (data e total). */
  card: (row: T) => { title: ReactNode; badge: ReactNode; middle: ReactNode; date: ReactNode; total: ReactNode }
  columns: Column<T>[]
  compact?: boolean
}

/** Mobile: linha-card tocável. Desktop: tabela (docs/brand: "Lista de OS"). `compact` usa só os cards. */
export function RecordList<T extends { id: string }>({ rows, href, card, columns, compact = false }: Props<T>) {
  return (
    <>
      <Stack component="ul" sx={{ gap: 1, m: 0, p: 0, listStyle: 'none', display: compact ? 'flex' : { xs: 'flex', lg: 'none' } }}>
        {rows.map((row) => {
          const c = card(row)
          return (
            <li key={row.id}>
              <ButtonBase
                href={href(row)}
                sx={{ display: 'flex', width: '100%', minHeight: 76, flexDirection: 'column', alignItems: 'stretch', gap: 0.5, border: 1, borderColor: 'divider', borderRadius: 1.5, bgcolor: 'background.paper', px: 2, py: 1.5, textAlign: 'left', fontSize: 16, '&:active': { bgcolor: 'action.hover' } }}
              >
                <Stack direction="row" sx={{ alignItems: 'center', justifyContent: 'space-between', gap: 1.5 }}>
                  <Box component="span" className="num" sx={{ fontWeight: 600 }}>
                    {c.title}
                  </Box>
                  {c.badge}
                </Stack>
                <Box sx={{ fontSize: 14, ...muted, minWidth: 0, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{c.middle}</Box>
                <Stack direction="row" sx={{ alignItems: 'center', justifyContent: 'space-between', fontSize: 14 }}>
                  <Box component="span" sx={muted}>
                    {c.date}
                  </Box>
                  <Box component="span" className="num" sx={{ fontWeight: 600 }}>
                    {c.total}
                  </Box>
                </Stack>
              </ButtonBase>
            </li>
          )
        })}
      </Stack>

      {compact ? null : (
        <Paper sx={{ display: { xs: 'none', lg: 'block' }, overflow: 'hidden' }}>
          <Table>
            <TableHead>
              <TableRow>
                {columns.map((c) => (
                  <TableCell key={c.header} align={c.align}>
                    {c.header}
                  </TableCell>
                ))}
                <TableCell sx={{ width: 40 }} />
              </TableRow>
            </TableHead>
            <TableBody>
              {rows.map((row) => (
                <TableRow key={row.id} hover sx={{ position: 'relative', '&:last-child td': { borderBottom: 0 } }}>
                  {columns.map((c, i) => (
                    <TableCell key={c.header} align={c.align} className={c.align ? 'num' : undefined} sx={{ ...(c.align ? { fontWeight: 600 } : {}), ...(c.align || c.nowrap ? { whiteSpace: 'nowrap' } : {}) }}>
                      {/* A primeira coluna leva o link, que cobre a linha inteira. */}
                      {i === 0 ? (
                        <Box component={Link} to={href(row)} sx={{ color: 'inherit', textDecoration: 'none', '&::after': { content: '""', position: 'absolute', inset: 0 } }}>
                          {c.cell(row)}
                        </Box>
                      ) : (
                        c.cell(row)
                      )}
                    </TableCell>
                  ))}
                  <TableCell sx={{ pl: 0, pr: 1.5, ...muted }}>
                    <ChevronRight size={18} aria-hidden />
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </Paper>
      )}
    </>
  )
}

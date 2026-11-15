'use client'

import Box from '@mui/material/Box'
import Pagination from '@mui/material/Pagination'
import Paper from '@mui/material/Paper'
import Stack from '@mui/material/Stack'
import Table from '@mui/material/Table'
import TableBody from '@mui/material/TableBody'
import TableCell from '@mui/material/TableCell'
import TableContainer from '@mui/material/TableContainer'
import TableHead from '@mui/material/TableHead'
import TableRow from '@mui/material/TableRow'
import Typography from '@mui/material/Typography'
import { useQuery, type QueryKey } from '@tanstack/react-query'
import { useState, type ReactNode } from 'react'
import { http, type Paginated } from '../shared/lib/http'
import { Link } from '../shared/lib/navigation'
import { ErrorBox, ListSkeleton } from '../shared/ui/States'

export type ListItemsColumn<T> = {
  id: string
  header: string
  cell: (item: T) => ReactNode
  align?: 'left' | 'center' | 'right'
  nowrap?: boolean
}

export type ListItemsProps<T> = {
  /** Endpoint returning { data: T[], meta: { total, current_page, last_page } }. */
  url: string
  /** Existing resource prefix, e.g. ['service-orders', 'list'], for cache invalidation. */
  queryKey: QueryKey
  columns: ListItemsColumn<T>[]
  getRowHref: (item: T) => string
  emptyState: ReactNode
  errorMessage?: string
  label?: string
}

/** Shared API-backed table. Changing the endpoint/filters resets pagination. */
export function ListItems<T extends { id: string }>(props: ListItemsProps<T>) {
  return <PaginatedListItems key={props.url} {...props} />
}

function PaginatedListItems<T extends { id: string }>({
  url, queryKey, columns, getRowHref, emptyState,
  errorMessage = 'Não deu para carregar os registros.', label = 'Listagem de registros',
}: ListItemsProps<T>) {
  const [page, setPage] = useState(1)

  const endpoint = new URL(url, 'http://list-items.local')
 
  endpoint.searchParams.set('page', String(page))
 
  const requestUrl = /^https?:\/\//.test(url) ? endpoint.toString() : `${endpoint.pathname}${endpoint.search}`
 
  const list = useQuery({
    queryKey: [...queryKey, 'list-items', requestUrl],
    queryFn: () => http.get<Paginated<T>>(requestUrl),
  })

  if (list.isPending) return <ListSkeleton />
 
  if (list.isError) return <ErrorBox message={errorMessage} onRetry={() => void list.refetch()} />

  const { data: items, meta } = list.data
 
  return (
    <Stack sx={{ gap: 2 }}>
      {items.length ? (
        <TableContainer component={Paper} role="region" aria-label={label} tabIndex={0} sx={{ overflowX: 'auto' }}>
          <Table aria-label={label} sx={{ minWidth: 600 }}>
            <TableHead>
              <TableRow>
                {columns.map(column => <TableCell key={column.id} scope="col" align={column.align}>{column.header}</TableCell>)}
              </TableRow>
            </TableHead>
            <TableBody>
              {items.map(item => (
                <TableRow key={item.id} hover sx={{ '&:last-child td, &:last-child th': { borderBottom: 0 } }}>
                  {columns.map((column, index) => (
                    <TableCell key={column.id} component={index === 0 ? 'th' : 'td'} scope={index === 0 ? 'row' : undefined} align={column.align} sx={{ whiteSpace: column.nowrap ? 'nowrap' : undefined }}>
                      {index === 0 ? (
                        <Box component={Link} to={getRowHref(item)} sx={{ color: 'primary.main', fontWeight: 600, textUnderlineOffset: 4 }}>
                          {column.cell(item)}
                        </Box>
                      ) : column.cell(item)}
                    </TableCell>
                  ))}
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </TableContainer>
      ) : emptyState}
      {meta.last_page > 1 || page > 1 ? (
        <Stack sx={{ gap: 1, alignItems: 'center' }}>
          <Typography variant="body2" color="text.secondary" role="status">{meta.total} registros · Página {meta.current_page} de {meta.last_page}</Typography>
          <Pagination
            count={Math.max(meta.last_page, page)} page={page} onChange={(_, value) => setPage(value)}
            size="small" siblingCount={0} aria-label="Paginação da listagem"
            getItemAriaLabel={(type, value, selected) => type === 'page' ? `${selected ? 'Página atual,' : 'Ir para'} página ${value}` : type === 'next' ? 'Próxima página' : type === 'previous' ? 'Página anterior' : type === 'first' ? 'Primeira página' : 'Última página'}
          />
        </Stack>
      ) : null}
    </Stack>
  )
}

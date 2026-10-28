import BottomNavigation from '@mui/material/BottomNavigation'
import BottomNavigationAction from '@mui/material/BottomNavigationAction'
import Box from '@mui/material/Box'
import IconButton from '@mui/material/IconButton'
import ListItemButton from '@mui/material/ListItemButton'
import Paper from '@mui/material/Paper'
import Skeleton from '@mui/material/Skeleton'
import Stack from '@mui/material/Stack'
import Typography from '@mui/material/Typography'
import { useQueryClient } from '@tanstack/react-query'
import { ClipboardList, FileText, House, LogOut, Moon, Settings, Sun, Users, type LucideIcon } from 'lucide-react'
import { Navigate, NavLink, Outlet, useLocation, useNavigate } from 'react-router'
import { authApi, canManageSettings, roleLabel, useMe } from '../features/auth/api'
import { useTheme } from '../shared/hooks/useTheme'
import { Logo } from '../shared/ui/Logo'

type NavItem = { to: string; label: string; icon: LucideIcon; end?: boolean }

const nav: NavItem[] = [
  { to: '/', label: 'Início', icon: House, end: true },
  { to: '/os', label: 'OS', icon: ClipboardList },
  { to: '/orcamentos', label: 'Orçamentos', icon: FileText },
  { to: '/clientes', label: 'Clientes', icon: Users },
]

const isActive = (pathname: string, { to, end }: NavItem) => (end ? pathname === to : pathname === to || pathname.startsWith(`${to}/`))

// Fio laranja da marca marcando o item ativo.
const accentBar = { content: '""', position: 'absolute', bgcolor: 'secondary.main' } as const

export function AppLayout() {
  const { data: me, isLoading } = useMe()
  const [theme, toggleTheme] = useTheme()
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const { pathname } = useLocation()

  if (isLoading) {
    return (
      <Box sx={{ maxWidth: 1024, mx: 'auto', p: 3 }}>
        <Skeleton height={36} width={192} sx={{ mb: 3 }} />
        <Skeleton height={160} />
      </Box>
    )
  }
  if (!me) return <Navigate to="/login" replace />

  const logout = async () => {
    await authApi.logout().catch(() => undefined)
    queryClient.clear()
    navigate('/login', { replace: true })
  }

  const current = nav.findIndex((item) => isActive(pathname, item))
  const ThemeIcon = theme === 'dark' ? Sun : Moon

  return (
    <Box sx={{ minHeight: '100dvh', display: { lg: 'grid' }, gridTemplateColumns: { xs: 'minmax(0, 1fr)', lg: '240px minmax(0, 1fr)' } }}>
      {/* Desktop: barra lateral */}
      <Box component="aside" sx={{ position: 'sticky', top: 0, height: '100dvh', display: { xs: 'none', lg: 'flex' }, flexDirection: 'column', borderRight: 1, borderColor: 'divider', bgcolor: 'background.paper' }}>
        <Box sx={{ px: 2.5, pt: 3, pb: 2.5 }}>
          <Logo height={30} />
          <Typography variant="body2" color="text.secondary" noWrap title={me.workshop} sx={{ mt: 1.5 }}>
            {me.workshop}
          </Typography>
        </Box>
        <Stack component="nav" aria-label="Principal" sx={{ gap: 0.25, px: 1.5, flex: 1 }}>
          {nav.map((item) => (
            <SideLink key={item.to} {...item} />
          ))}
          <Box sx={{ flex: 1 }} />
          {canManageSettings(me) ? <SideLink to="/configuracoes" label="Configurações" icon={Settings} /> : null}
        </Stack>
        <Box sx={{ p: 1.5, borderTop: 1, borderColor: 'divider' }}>
          <Box sx={{ px: 1, pb: 1 }}>
            <Typography noWrap sx={{ fontWeight: 600 }}>
              {me.name}
            </Typography>
            <Typography variant="body2" color="text.secondary">
              {roleLabel[me.role]}
            </Typography>
          </Box>
          <Stack direction="row" sx={{ gap: 0.5 }}>
            <ListItemButton onClick={toggleTheme} sx={{ height: 40, flex: 1, justifyContent: 'center', gap: 1, borderRadius: 1, fontSize: 14, color: 'text.secondary', '&:hover': { color: 'text.primary' } }}>
              <ThemeIcon size={18} aria-hidden />
              {theme === 'dark' ? 'Tema claro' : 'Tema escuro'}
            </ListItemButton>
            <IconButton onClick={logout} aria-label="Sair" title="Sair" sx={{ borderRadius: 1, width: 40, color: 'text.secondary' }}>
              <LogOut size={18} aria-hidden />
            </IconButton>
          </Stack>
        </Box>
      </Box>

      <Box sx={{ display: 'flex', minWidth: 0, flexDirection: 'column' }}>
        {/* Mobile: topo enxuto */}
        <Box component="header" sx={{ position: 'sticky', top: 0, zIndex: 30, display: { xs: 'flex', lg: 'none' }, height: 56, alignItems: 'center', justifyContent: 'space-between', borderBottom: 1, borderColor: 'divider', bgcolor: 'background.paper', px: 2 }}>
          <Logo height={26} />
          <Stack direction="row">
            <IconButton onClick={toggleTheme} aria-label={theme === 'dark' ? 'Usar tema claro' : 'Usar tema escuro'} sx={{ width: 44, height: 44, color: 'text.secondary' }}>
              <ThemeIcon size={20} aria-hidden />
            </IconButton>
            {canManageSettings(me) ? (
              <IconButton component={NavLink} to="/configuracoes" aria-label="Configurações" sx={{ width: 44, height: 44, color: 'text.secondary' }}>
                <Settings size={20} aria-hidden />
              </IconButton>
            ) : null}
            <IconButton onClick={logout} aria-label="Sair" sx={{ width: 44, height: 44, color: 'text.secondary' }}>
              <LogOut size={20} aria-hidden />
            </IconButton>
          </Stack>
        </Box>

        <Box component="main" sx={{ mx: 'auto', width: '100%', maxWidth: 1024, flex: 1, px: { xs: 2, lg: 4 }, pt: { xs: 2.5, lg: 4 }, pb: { xs: 14, lg: 6 } }}>
          <Outlet />
        </Box>

        {/* Mobile: navegação inferior */}
        <Paper component="nav" aria-label="Principal" square sx={{ position: 'fixed', insetInline: 0, bottom: 0, zIndex: 40, display: { lg: 'none' }, borderWidth: '1px 0 0', pb: 'env(safe-area-inset-bottom)' }}>
          <BottomNavigation showLabels value={current} sx={{ height: 64, bgcolor: 'transparent' }}>
            {nav.map(({ to, label, icon: Icon, end }) => (
              <BottomNavigationAction
                key={to}
                component={NavLink}
                to={to}
                end={end}
                label={label}
                icon={<Icon size={24} aria-hidden />}
                sx={{
                  position: 'relative',
                  minWidth: 0,
                  gap: 0.5,
                  color: 'text.secondary',
                  '& .MuiBottomNavigationAction-label': { fontSize: 12, fontWeight: 500, '&.Mui-selected': { fontSize: 12 } },
                  '&.Mui-selected': { color: 'primary.main', '&::before': { ...accentBar, top: 0, insetInline: 20, height: 2 } },
                }}
              />
            ))}
          </BottomNavigation>
        </Paper>
      </Box>
    </Box>
  )
}

function SideLink({ to, label, icon: Icon, end }: NavItem) {
  return (
    <ListItemButton
      component={NavLink}
      to={to}
      end={end}
      sx={{
        position: 'relative',
        height: 44,
        flexGrow: 0,
        gap: 1.5,
        borderRadius: 1,
        px: 1.5,
        '&.active': { bgcolor: 'action.selected', fontWeight: 600, color: 'primary.main', '&::before': { ...accentBar, insetBlock: 8, left: 0, width: 3, borderRadius: '0 2px 2px 0' } },
      }}
    >
      <Icon size={20} aria-hidden />
      {label}
    </ListItemButton>
  )
}

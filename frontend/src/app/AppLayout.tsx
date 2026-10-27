import { useQueryClient } from '@tanstack/react-query'
import { ClipboardList, FileText, House, LogOut, Moon, Settings, Sun, Users, type LucideIcon } from 'lucide-react'
import { Navigate, NavLink, Outlet, useNavigate } from 'react-router'
import { authApi, canManageSettings, roleLabel, useMe } from '../features/auth/api'
import { useTheme } from '../shared/hooks/useTheme'
import { Logo } from '../shared/ui/Logo'
import { Skeleton } from '../shared/ui/States'

type NavItem = { to: string; label: string; icon: LucideIcon; end?: boolean }

const nav: NavItem[] = [
  { to: '/', label: 'Início', icon: House, end: true },
  { to: '/os', label: 'OS', icon: ClipboardList },
  { to: '/orcamentos', label: 'Orçamentos', icon: FileText },
  { to: '/clientes', label: 'Clientes', icon: Users },
]

export function AppLayout() {
  const { data: me, isLoading } = useMe()
  const [theme, toggleTheme] = useTheme()
  const queryClient = useQueryClient()
  const navigate = useNavigate()

  if (isLoading) {
    return (
      <div className="mx-auto max-w-5xl p-6">
        <Skeleton className="mb-6 h-9 w-48" />
        <Skeleton className="h-40" />
      </div>
    )
  }
  if (!me) return <Navigate to="/login" replace />

  const logout = async () => {
    await authApi.logout().catch(() => undefined)
    queryClient.clear()
    navigate('/login', { replace: true })
  }

  return (
    <div className="min-h-dvh lg:grid lg:grid-cols-[240px_1fr]">
      {/* Desktop: barra lateral */}
      <aside className="sticky top-0 hidden h-dvh flex-col border-r border-border bg-surface lg:flex">
        <div className="px-5 pt-6 pb-5">
          <Logo height={30} />
          <p className="mt-3 truncate text-sm text-muted" title={me.workshop}>
            {me.workshop}
          </p>
        </div>
        <nav aria-label="Principal" className="flex flex-1 flex-col gap-0.5 px-3">
          {nav.map((item) => (
            <SideLink key={item.to} {...item} />
          ))}
          <div className="flex-1" />
          {canManageSettings(me) ? <SideLink to="/configuracoes" label="Configurações" icon={Settings} /> : null}
        </nav>
        <div className="border-t border-border p-3">
          <div className="px-2 pb-2">
            <p className="truncate font-semibold">{me.name}</p>
            <p className="text-sm text-muted">{roleLabel[me.role]}</p>
          </div>
          <div className="flex gap-1">
            <button type="button" onClick={toggleTheme} className="flex h-10 flex-1 items-center justify-center gap-2 rounded-md text-sm text-muted hover:bg-surface-2 hover:text-text">
              {theme === 'dark' ? <Sun size={18} aria-hidden /> : <Moon size={18} aria-hidden />}
              {theme === 'dark' ? 'Tema claro' : 'Tema escuro'}
            </button>
            <button type="button" onClick={logout} aria-label="Sair" title="Sair" className="flex size-10 items-center justify-center rounded-md text-muted hover:bg-surface-2 hover:text-text">
              <LogOut size={18} aria-hidden />
            </button>
          </div>
        </div>
      </aside>

      <div className="flex min-w-0 flex-col">
        {/* Mobile: topo enxuto */}
        <header className="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-border bg-surface px-4 lg:hidden">
          <Logo height={26} />
          <div className="flex items-center">
            <button type="button" onClick={toggleTheme} aria-label={theme === 'dark' ? 'Usar tema claro' : 'Usar tema escuro'} className="flex size-11 items-center justify-center text-muted">
              {theme === 'dark' ? <Sun size={20} aria-hidden /> : <Moon size={20} aria-hidden />}
            </button>
            {canManageSettings(me) ? (
              <NavLink to="/configuracoes" aria-label="Configurações" className="flex size-11 items-center justify-center text-muted">
                <Settings size={20} aria-hidden />
              </NavLink>
            ) : null}
            <button type="button" onClick={logout} aria-label="Sair" className="flex size-11 items-center justify-center text-muted">
              <LogOut size={20} aria-hidden />
            </button>
          </div>
        </header>

        <main className="mx-auto w-full max-w-5xl flex-1 px-4 pt-5 pb-28 lg:px-8 lg:pt-8 lg:pb-12">
          <Outlet />
        </main>

        {/* Mobile: navegação inferior */}
        <nav aria-label="Principal" className="pb-safe fixed inset-x-0 bottom-0 z-40 grid grid-cols-4 border-t border-border bg-surface lg:hidden">
          {nav.map(({ to, label, icon: Icon, end }) => (
            <NavLink
              key={to}
              to={to}
              end={end}
              className={({ isActive }) =>
                `relative flex h-16 flex-col items-center justify-center gap-1 text-xs font-medium ${isActive ? 'text-brand' : 'text-muted'}`
              }
            >
              {({ isActive }) => (
                <>
                  {isActive ? <span className="absolute inset-x-5 top-0 h-0.5 bg-accent" aria-hidden /> : null}
                  <Icon size={24} aria-hidden />
                  {label}
                </>
              )}
            </NavLink>
          ))}
        </nav>
      </div>
    </div>
  )
}

function SideLink({ to, label, icon: Icon, end }: NavItem) {
  return (
    <NavLink
      to={to}
      end={end}
      className={({ isActive }) =>
        `relative flex h-11 items-center gap-3 rounded-md px-3 ${isActive ? 'bg-surface-2 font-semibold text-brand' : 'text-text hover:bg-surface-2'}`
      }
    >
      {({ isActive }) => (
        <>
          {isActive ? <span className="absolute inset-y-2 left-0 w-[3px] rounded-r bg-accent" aria-hidden /> : null}
          <Icon size={20} aria-hidden />
          {label}
        </>
      )}
    </NavLink>
  )
}

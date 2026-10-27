import { useQuery } from '@tanstack/react-query'
import { ApiError, ensureCsrf, http, type Wrapped } from '../../shared/lib/http'

export type Role = 'owner' | 'admin' | 'technician' | 'front'
export type User = { id: string; name: string; email: string; role: Role; workshop?: string }

export const roleLabel: Record<Role, string> = { owner: 'Dono', admin: 'Administrador', technician: 'Técnico', front: 'Balcão' }

export const authApi = {
  async login(email: string, password: string) {
    await ensureCsrf(true)
    return (await http.post<Wrapped<User>>('/api/login', { email, password })).data
  },
  logout: () => http.post<void>('/api/logout'),
  me: async () => (await http.get<Wrapped<User>>('/api/me')).data,
  users: async () => (await http.get<Wrapped<User[]>>('/api/users')).data,
}

/** Usuário logado; `null` quando não há sessão. */
export function useMe() {
  return useQuery({
    queryKey: ['me'],
    queryFn: async () => {
      try {
        return await authApi.me()
      } catch (e) {
        if (e instanceof ApiError && e.status === 401) return null
        throw e
      }
    },
    staleTime: 5 * 60_000,
  })
}

export function useUsers() {
  return useQuery({ queryKey: ['users'], queryFn: authApi.users, staleTime: 5 * 60_000 })
}

export function canManageSettings(user: User | null | undefined): boolean {
  return user?.role === 'owner' || user?.role === 'admin'
}

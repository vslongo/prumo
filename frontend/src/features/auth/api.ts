import { useQuery } from '@tanstack/react-query'
import { ApiError, ensureCsrf, http, type Wrapped } from '../../shared/lib/http'

export type Role = 'owner' | 'admin' | 'technician' | 'front'
export type User = { id: string; name: string; email: string; role: Role; workshop?: string }

export type SignUpInput = { workshop_name: string; name: string; email: string; password: string }
export type InvitableRole = Exclude<Role, 'owner'>
export type SentInvitation = { link: string; whatsapp_url: string; expires_at: string }
export type PublicInvitation = { name: string; role: Role; workshop: string; usable: boolean }
export type AcceptInvitationInput = { name: string; email: string; password: string }

export const roleLabel: Record<Role, string> = { owner: 'Dono', admin: 'Administrador', technician: 'Técnico', front: 'Balcão' }

export const authApi = {
  async login(email: string, password: string) {
    await ensureCsrf(true)
    return (await http.post<Wrapped<User>>('/api/login', { email, password })).data
  },
  async signUp(input: SignUpInput) {
    await ensureCsrf(true)
    return (await http.post<Wrapped<User>>('/api/signup', input)).data
  },
  logout: () => http.post<void>('/api/logout'),
  me: async () => (await http.get<Wrapped<User>>('/api/me')).data,
  users: async () => (await http.get<Wrapped<User[]>>('/api/users')).data,
  invite: async (name: string, role: InvitableRole) => (await http.post<Wrapped<SentInvitation>>('/api/user-invitations', { name, role })).data,
  invitation: async (token: string) => (await http.get<Wrapped<PublicInvitation>>(`/api/public/invitations/${token}`)).data,
  async acceptInvitation(token: string, input: AcceptInvitationInput) {
    await ensureCsrf(true)
    return (await http.post<Wrapped<User>>(`/api/public/invitations/${token}/accept`, input)).data
  },
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

import { ENDPOINTS } from '../../routes/paths'
import { useQuery } from '@tanstack/react-query'
import { ApiError, ensureCsrf, http, type Wrapped } from '../../shared/lib/http'

const endpoints = ENDPOINTS()

export type Role = 'owner' | 'admin' | 'technician' | 'front'
export type User = { id: string; name: string; email: string; role: Role; is_active: boolean; workshop?: string }

export type SignUpInput = { workshop_name: string; name: string; email: string; password: string }
export type InvitableRole = Exclude<Role, 'owner'>
export type SentInvitation = { link: string; whatsapp_url: string; expires_at: string }
export type PublicInvitation = { name: string; role: Role; workshop: string; usable: boolean }
export type AcceptInvitationInput = { name: string; email: string; password: string }
export type PendingInvitation = { id: string; name: string; role: InvitableRole; expires_at: string; link: string | null; whatsapp_url: string | null }

export const roleLabel: Record<Role, string> = { owner: 'Dono', admin: 'Administrador', technician: 'Técnico', front: 'Balcão' }

export const authApi = {
  async login(email: string, password: string) {
    await ensureCsrf(true)
    return (await http.post<Wrapped<User>>(endpoints.Auth.login, { email, password })).data
  },
  async signUp(input: SignUpInput) {
    await ensureCsrf(true)
    return (await http.post<Wrapped<User>>(endpoints.Auth.signup, input)).data
  },
  logout: () => http.post<void>(endpoints.Auth.logout),
  me: async () => (await http.get<Wrapped<User>>(endpoints.Auth.me)).data,
  users: async () => (await http.get<Wrapped<User[]>>(endpoints.Users.list)).data,
  changeRole: async (id: string, role: InvitableRole) => (await http.patch<Wrapped<User>>(endpoints.Users.changeRole(id), { role })).data,
  deactivate: async (id: string) => (await http.post<Wrapped<User>>(endpoints.Users.deactivate(id))).data,
  reactivate: async (id: string) => (await http.post<Wrapped<User>>(endpoints.Users.reactivate(id))).data,
  pendingInvitations: async () => (await http.get<Wrapped<PendingInvitation[]>>(endpoints.Invitations.list)).data,
  revokeInvitation: (id: string) => http.delete<void>(endpoints.Invitations.delete(id)),
  invite: async (name: string, role: InvitableRole) => (await http.post<Wrapped<SentInvitation>>(endpoints.Invitations.create, { name, role })).data,
  invitation: async (token: string) => (await http.get<Wrapped<PublicInvitation>>(endpoints.PublicInvitations.details(token))).data,
  async acceptInvitation(token: string, input: AcceptInvitationInput) {
    await ensureCsrf(true)
    return (await http.post<Wrapped<User>>(endpoints.PublicInvitations.accept(token), input)).data
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

export function usePendingInvitations() {
  return useQuery({ queryKey: ['user-invitations'], queryFn: authApi.pendingInvitations })
}

export function canManageSettings(user: User | null | undefined): boolean {
  return user?.role === 'owner' || user?.role === 'admin'
}

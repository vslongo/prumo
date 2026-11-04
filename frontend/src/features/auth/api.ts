import { useQuery } from '@tanstack/react-query'
import { ApiError, ensureCsrf, http, type Wrapped } from '../../shared/lib/http'

export type Role = 'owner' | 'admin' | 'technician' | 'front'
/** `required_from`: desde quando o 2FA é obrigatório (só dono sem 2FA); `blocked`: passou do prazo, o app só deixa ativar. */
export type TwoFactorState = { enabled: boolean; required_from: string | null; blocked: boolean }
export type User = { id: string; name: string; email: string; role: Role; is_active: boolean; workshop?: string; two_factor: TwoFactorState }
export type LoginResult = { twoFactor: true } | { twoFactor: false; user: User }
export type TwoFactorSetup = { secret: string; otpauth_url: string }

export type SignUpInput = { workshop_name: string; name: string; email: string; password: string }
export type InvitableRole = Exclude<Role, 'owner'>
export type SentInvitation = { link: string; whatsapp_url: string; expires_at: string }
export type PublicInvitation = { name: string; role: Role; workshop: string; usable: boolean }
export type AcceptInvitationInput = { name: string; email: string; password: string }
export type PendingInvitation = { id: string; name: string; role: InvitableRole; expires_at: string; link: string | null; whatsapp_url: string | null }

export const roleLabel: Record<Role, string> = { owner: 'Dono', admin: 'Administrador', technician: 'Técnico', front: 'Balcão' }

export const authApi = {
  /** Com 2FA ativo a senha certa ainda não entra: devolve `twoFactor: true` e o login termina em `loginTwoFactor`. */
  async login(email: string, password: string): Promise<LoginResult> {
    await ensureCsrf(true)
    const res = await http.post<Wrapped<User> | { two_factor: true }>('/api/login', { email, password })
    return 'two_factor' in res ? { twoFactor: true } : { twoFactor: false, user: res.data }
  },
  loginTwoFactor: async (input: { code: string } | { recovery_code: string }) => (await http.post<Wrapped<User>>('/api/login/two-factor', input)).data,
  startTwoFactor: async () => (await http.post<Wrapped<TwoFactorSetup>>('/api/two-factor')).data,
  confirmTwoFactor: async (code: string) => (await http.post<Wrapped<{ recovery_codes: string[] }>>('/api/two-factor/confirm', { code })).data.recovery_codes,
  disableTwoFactor: (password: string) => http.delete<void>('/api/two-factor', { password }),
  async signUp(input: SignUpInput) {
    await ensureCsrf(true)
    return (await http.post<Wrapped<User>>('/api/signup', input)).data
  },
  logout: () => http.post<void>('/api/logout'),
  me: async () => (await http.get<Wrapped<User>>('/api/me')).data,
  users: async () => (await http.get<Wrapped<User[]>>('/api/users')).data,
  changeRole: async (id: string, role: InvitableRole) => (await http.patch<Wrapped<User>>(`/api/users/${id}/role`, { role })).data,
  deactivate: async (id: string) => (await http.post<Wrapped<User>>(`/api/users/${id}/deactivate`)).data,
  reactivate: async (id: string) => (await http.post<Wrapped<User>>(`/api/users/${id}/reactivate`)).data,
  pendingInvitations: async () => (await http.get<Wrapped<PendingInvitation[]>>('/api/user-invitations')).data,
  revokeInvitation: (id: string) => http.delete<void>(`/api/user-invitations/${id}`),
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

export function usePendingInvitations() {
  return useQuery({ queryKey: ['user-invitations'], queryFn: authApi.pendingInvitations })
}

export function canManageSettings(user: User | null | undefined): boolean {
  return user?.role === 'owner' || user?.role === 'admin'
}

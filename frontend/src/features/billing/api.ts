import { useQuery } from '@tanstack/react-query'
import { http, type Wrapped } from '../../shared/lib/http'

export type PlanKey = 'solo' | 'oficina' | 'pro'
export type AccessState = 'trial' | 'active' | 'past_due' | 'canceled' | 'read_only'
export type SubscriptionStatus = 'pending' | 'active' | 'past_due' | 'canceled'

/** Situação de acesso da oficina (todos os papéis veem). */
export type Access = {
  state: AccessState
  can_write: boolean
  plan: PlanKey | null
  subscription_status: SubscriptionStatus | null
  trial_ends_at: string | null
  paid_until: string | null
  /** Quando vira somente leitura (fim do trial, da carência ou do período pago). */
  read_only_from: string | null
  max_users: number | null
}

export type PlanInfo = { key: PlanKey; name: string; price_cents: number; max_users: number | null }

export type BillingOverview = {
  access: Access
  subscription: {
    plan: PlanKey
    status: SubscriptionStatus
    invoice_url: string | null
    error: string | null
    /** Aguardando o Asaas gerar a fatura. */
    provisioning: boolean
    paid_until: string | null
    past_due_since: string | null
    canceled_at: string | null
  } | null
  plans: PlanInfo[]
  active_users: number
}

export const billingApi = {
  access: async () => (await http.get<Wrapped<Access>>('/api/billing/access')).data,
  overview: async () => (await http.get<Wrapped<BillingOverview>>('/api/billing')).data,
  subscribe: async (plan: PlanKey, cpfCnpj: string) => (await http.post<Wrapped<BillingOverview>>('/api/billing/subscribe', { plan, cpf_cnpj: cpfCnpj })).data,
  changePlan: async (plan: PlanKey) => (await http.patch<Wrapped<BillingOverview>>('/api/billing/plan', { plan })).data,
  cancel: async () => (await http.post<Wrapped<BillingOverview>>('/api/billing/cancel')).data,
}

export function useAccess() {
  return useQuery({ queryKey: ['billing', 'access'], queryFn: billingApi.access, staleTime: 5 * 60_000 })
}

/** Dias inteiros até `iso` (arredonda para cima; 0 se já passou). */
export function daysUntil(iso: string | null, now: Date = new Date()): number {
  if (!iso) return 0
  return Math.max(0, Math.ceil((new Date(iso).getTime() - now.getTime()) / 86_400_000))
}

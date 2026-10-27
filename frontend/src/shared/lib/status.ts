/** Rótulos da marca (docs/brand/BRAND.md, "Mapa de status"). Os nomes batem com os enums do backend. */

export type ServiceOrderStatus = 'open' | 'in_progress' | 'ready' | 'delivered' | 'closed' | 'canceled'
export type QuoteStatus = 'draft' | 'sent' | 'viewed' | 'approved' | 'rejected' | 'expired'

export const serviceOrderStatusLabel: Record<ServiceOrderStatus, string> = {
  open: 'Aberta',
  in_progress: 'Em execução',
  ready: 'Pronta',
  delivered: 'Entregue',
  closed: 'Fechada',
  canceled: 'Cancelada',
}

export const quoteStatusLabel: Record<QuoteStatus, string> = {
  draft: 'Rascunho',
  sent: 'Enviado',
  viewed: 'Visualizado',
  approved: 'Aprovado',
  rejected: 'Recusado',
  expired: 'Expirado',
}

/** Próximo passo da OS (espelha a máquina de estados do backend). Fechar tem fluxo próprio. */
export const serviceOrderNextStep: Partial<Record<ServiceOrderStatus, { to: ServiceOrderStatus; label: string }>> = {
  open: { to: 'in_progress', label: 'Iniciar serviço' },
  in_progress: { to: 'ready', label: 'Marcar como pronta' },
  ready: { to: 'delivered', label: 'Registrar entrega' },
}

export const serviceOrderCancelable: ServiceOrderStatus[] = ['open', 'in_progress', 'ready']

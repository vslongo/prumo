import { useQuery } from '@tanstack/react-query'
import { ENDPOINTS } from '../../routes/paths'
import { http, type Wrapped } from '../../shared/lib/http'
import type { InvoiceStatus } from '../../shared/lib/status'
import { putUpload, type UploadTicket } from '../../shared/upload/queue'

export type TaxRegime = 'mei' | 'simples_me_epp'
export type FiscalEnvironment = 'restricted' | 'production'

export const taxRegimeLabel: Record<TaxRegime, string> = {
  simples_me_epp: 'Simples Nacional (ME/EPP)',
  mei: 'MEI',
}

export type FiscalProfileInput = {
  cnpj: string
  municipal_registration: string | null
  city_ibge_code: string
  tax_regime: TaxRegime
  national_tax_code: string
  municipal_tax_code: string | null
  /** Alíquota do ISS em pontos-base (2,5% = 250). MEI não informa. */
  iss_rate_bp: number | null
  dps_series: string
  /** Próximo número da DPS; null = continua do último emitido pelo Prumo. */
  dps_next_number: number | null
  include_products: boolean
  environment: FiscalEnvironment
}

export type FiscalProfile = FiscalProfileInput & {
  certificate: { expires_at: string | null; valid: boolean } | null
}

/** "3,5" → 350 pontos-base; vazio → null. */
export function percentToBp(v: string): number | null {
  const t = v.trim().replace(',', '.')
  if (t === '') return null
  if (!/^\d{1,2}(\.\d{1,2})?$/.test(t)) return Number.NaN
  const [int, dec = ''] = t.split('.')
  return Number(int) * 100 + Number(dec.padEnd(2, '0'))
}

const E = ENDPOINTS().Invoicing

export const invoicingApi = {
  profile: async () => (await http.get<Wrapped<FiscalProfile | null>>(E.fiscalProfile)).data,
  saveProfile: async (input: FiscalProfileInput) => (await http.put<Wrapped<FiscalProfile>>(E.fiscalProfile, input)).data,
  /** Sobe o .pfx direto no R2 (sem compressão) e pede ao backend para conferir CNPJ e validade. */
  uploadCertificate: async (file: File, password: string) => {
    const ticket = (await http.post<Wrapped<UploadTicket>>(E.certificateUploadUrl, { size: file.size })).data
    await putUpload(ticket, new Blob([file], { type: 'application/x-pkcs12' }))
    return (await http.post<Wrapped<FiscalProfile>>(E.certificate, { media_id: ticket.media_id, password })).data
  },
}

export const removeCertificate = async () => (await http.delete<Wrapped<FiscalProfile | null>>(E.certificate)).data

export type Invoice = {
  id: string
  service_order_id: string
  status: InvoiceStatus
  environment: FiscalEnvironment
  dps_series: string
  dps_number: number
  access_key: string | null
  nfse_number: string | null
  amount_cents: number
  include_products: boolean
  error_code: string | null
  error_message: string | null
  has_xml: boolean
  has_pdf: boolean
  authorized_at: string | null
  canceled_at: string | null
  cancel_reason: string | null
  created_at: string
}

export type CancelReason = '1' | '2' | '9'

export const cancelReasonLabel: Record<CancelReason, string> = {
  '1': 'Erro na emissão',
  '2': 'Serviço não prestado',
  '9': 'Outro motivo',
}

export const invoiceApi = {
  list: async (orderId: string) => (await http.get<Wrapped<Invoice[]>>(E.orderInvoices(orderId))).data,
  issue: async (orderId: string) => (await http.post<Wrapped<Invoice>>(E.orderInvoices(orderId))).data,
  cancel: async (id: string, reason: CancelReason, text: string) => (await http.post<Wrapped<Invoice>>(E.cancel(id), { reason, text })).data,
  fileUrl: async (id: string, type: 'xml' | 'pdf') => (await http.get<Wrapped<{ url: string }>>(E.file(id, type))).data.url,
}

/** Em andamento na fila: a tela confere de novo até terminar. */
export const invoiceInFlight = (s: InvoiceStatus) => s === 'pending' || s === 'processing' || s === 'cancel_requested'

export function useOrderInvoices(orderId: string) {
  return useQuery({
    queryKey: ['invoices', orderId],
    queryFn: () => invoiceApi.list(orderId),
    refetchInterval: (q) => (q.state.data?.some((i) => invoiceInFlight(i.status)) ? 3000 : false),
  })
}

export function useFiscalProfile() {
  return useQuery({ queryKey: ['fiscal-profile'], queryFn: invoicingApi.profile })
}

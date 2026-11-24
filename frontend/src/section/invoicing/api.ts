import { useQuery } from '@tanstack/react-query'
import { ENDPOINTS } from '../../routes/paths'
import { http, type Wrapped } from '../../shared/lib/http'
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

export function useFiscalProfile() {
  return useQuery({ queryKey: ['fiscal-profile'], queryFn: invoicingApi.profile })
}

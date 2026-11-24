import { zodResolver } from '@hookform/resolvers/zod'
import Alert from '@mui/material/Alert'
import Stack from '@mui/material/Stack'
import Typography from '@mui/material/Typography'
import { useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { useForm, useWatch } from 'react-hook-form'
import { z } from 'zod'
import { daysUntil } from '../../billing/api'
import { formatDate } from '../../../shared/lib/format'
import { ApiError } from '../../../shared/lib/http'
import { Button } from '../../../shared/ui/Button'
import { ConfirmDialog } from '../../../shared/ui/ConfirmDialog'
import { CheckboxField, SelectField, TextField } from '../../../shared/ui/Field'
import { Card, CardTitle, ErrorBox, ListSkeleton } from '../../../shared/ui/States'
import { muted } from '../../../shared/ui/styles'
import { useToast } from '../../../shared/ui/Toast'
import { invoicingApi, percentToBp, removeCertificate, taxRegimeLabel, useFiscalProfile, type FiscalProfile, type TaxRegime } from '../api'

const apiMessage = (e: unknown) => (e instanceof ApiError ? (Object.values(e.errors)[0]?.[0] ?? e.message) : 'Não deu para salvar. Tente de novo.')

const digits = (v: string) => v.replace(/\D/g, '')

const bpToPercent = (bp: number | null) => (bp === null ? '' : `${Math.floor(bp / 100)},${String(bp % 100).padStart(2, '0')}`)

const schema = z
  .object({
    cnpj: z.string().refine((v) => v.toUpperCase().replace(/[^0-9A-Z]/g, '').length === 14, 'CNPJ deve ter 14 posições.'),
    municipal_registration: z.string().max(20),
    city_ibge_code: z.string().refine((v) => digits(v).length === 7, 'Código IBGE tem 7 dígitos.'),
    tax_regime: z.enum(['simples_me_epp', 'mei']),
    national_tax_code: z.string().refine((v) => digits(v).length === 6, 'Código de tributação nacional tem 6 dígitos (ex.: 14.01.01).'),
    municipal_tax_code: z.string().max(20),
    iss_rate: z.string(),
    dps_series: z.string().regex(/^\d{1,5}$/, 'Série: de 1 a 5 dígitos.'),
    include_products: z.boolean(),
    environment: z.enum(['restricted', 'production']),
  })
  .superRefine((f, ctx) => {
    const bp = percentToBp(f.iss_rate)
    if (f.tax_regime === 'simples_me_epp' && (bp === null || Number.isNaN(bp) || bp < 200 || bp > 500)) {
      ctx.addIssue({ code: 'custom', path: ['iss_rate'], message: 'Alíquota do ISS entre 2% e 5% (ex.: 3,00).' })
    }
  })

type Form = z.infer<typeof schema>

const toForm = (p: FiscalProfile | null): Form => ({
  cnpj: p?.cnpj ?? '',
  municipal_registration: p?.municipal_registration ?? '',
  city_ibge_code: p?.city_ibge_code ?? '',
  tax_regime: p?.tax_regime ?? 'simples_me_epp',
  national_tax_code: p?.national_tax_code ?? '140101',
  municipal_tax_code: p?.municipal_tax_code ?? '',
  iss_rate: bpToPercent(p?.iss_rate_bp ?? null),
  dps_series: p?.dps_series ?? '1',
  include_products: p?.include_products ?? true,
  environment: p?.environment ?? 'restricted',
})

function ProfileForm({ profile }: { profile: FiscalProfile | null }) {
  const queryClient = useQueryClient()
  const toast = useToast()
  const [error, setError] = useState<string | null>(null)
  const { register, handleSubmit, control, formState } = useForm<Form>({ resolver: zodResolver(schema), defaultValues: toForm(profile) })
  const isMei = useWatch({ control, name: 'tax_regime' }) === 'mei'

  const onSubmit = handleSubmit(async (f) => {
    setError(null)
    try {
      const saved = await invoicingApi.saveProfile({
        cnpj: f.cnpj,
        municipal_registration: f.municipal_registration.trim() || null,
        city_ibge_code: digits(f.city_ibge_code),
        tax_regime: f.tax_regime,
        national_tax_code: digits(f.national_tax_code),
        municipal_tax_code: f.municipal_tax_code.trim() || null,
        iss_rate_bp: f.tax_regime === 'mei' ? null : percentToBp(f.iss_rate),
        dps_series: f.dps_series,
        include_products: f.include_products,
        environment: f.environment,
      })
      queryClient.setQueryData(['fiscal-profile'], saved)
      toast('Dados fiscais salvos.')
    } catch (e) {
      setError(apiMessage(e))
    }
  })

  const e = formState.errors
  return (
    <Stack component="form" onSubmit={onSubmit} noValidate sx={{ gap: 2 }}>
      {error ? <ErrorBox message={error} /> : null}
      <TextField label="CNPJ da oficina" inputMode="numeric" error={e.cnpj?.message} {...register('cnpj')} />
      <SelectField label="Regime tributário" error={e.tax_regime?.message} {...register('tax_regime')}>
        {(Object.keys(taxRegimeLabel) as TaxRegime[]).map((r) => (
          <option key={r} value={r}>
            {taxRegimeLabel[r]}
          </option>
        ))}
      </SelectField>
      {isMei ? null : <TextField label="Alíquota do ISS (%)" hint="A do seu anexo do Simples. Peça ao contador." inputMode="decimal" placeholder="3,00" error={e.iss_rate?.message} {...register('iss_rate')} />}
      <TextField label="Inscrição municipal" hint="Opcional se o município não exige." error={e.municipal_registration?.message} {...register('municipal_registration')} />
      <TextField label="Código IBGE do município" hint="7 dígitos. Ex.: Florianópolis = 4205407." inputMode="numeric" error={e.city_ibge_code?.message} {...register('city_ibge_code')} />
      <TextField label="Código de tributação nacional" hint="Manutenção de veículos: 14.01.01." inputMode="numeric" error={e.national_tax_code?.message} {...register('national_tax_code')} />
      <TextField label="Código de tributação municipal" hint="Opcional." error={e.municipal_tax_code?.message} {...register('municipal_tax_code')} />
      <TextField label="Série da DPS" hint="Use uma série que nenhum outro sistema da oficina usa." inputMode="numeric" error={e.dps_series?.message} {...register('dps_series')} />
      <CheckboxField label="Incluir peças na nota de serviço" defaultChecked={profile?.include_products ?? true} {...register('include_products')} />
      <Typography variant="body2" sx={muted}>
        Peças costumam pagar ICMS (nota de produto), e alguns municípios recusam peças na NFS-e. Confirme com seu contador.
      </Typography>
      <SelectField label="Ambiente" hint="Use homologação para testar; notas de lá não valem." {...register('environment')}>
        <option value="restricted">Homologação (produção restrita)</option>
        <option value="production">Produção</option>
      </SelectField>
      <Button type="submit" loading={formState.isSubmitting} loadingText="Salvando…">
        Salvar dados fiscais
      </Button>
    </Stack>
  )
}

function CertificateForm({ profile }: { profile: FiscalProfile }) {
  const queryClient = useQueryClient()
  const toast = useToast()
  const [file, setFile] = useState<File | null>(null)
  const [password, setPassword] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [sending, setSending] = useState(false)
  // Troca a key para limpar o <input type=file> depois de aceito.
  const [inputKey, setInputKey] = useState(0)
  const [confirmRemove, setConfirmRemove] = useState(false)
  const [removing, setRemoving] = useState(false)
  const cert = profile.certificate
  const days = daysUntil(cert?.expires_at ?? null)

  const send = async () => {
    setError(null)
    if (!file) return setError('Escolha o arquivo .pfx do certificado.')
    if (!password) return setError('Informe a senha do certificado.')
    setSending(true)
    try {
      queryClient.setQueryData(['fiscal-profile'], await invoicingApi.uploadCertificate(file, password))
      setFile(null)
      setInputKey((k) => k + 1)
      setPassword('')
      toast('Certificado aceito.')
    } catch (e) {
      setError(apiMessage(e))
    } finally {
      setSending(false)
    }
  }

  const remove = async () => {
    setRemoving(true)
    try {
      queryClient.setQueryData(['fiscal-profile'], await removeCertificate())
      setConfirmRemove(false)
      toast('Certificado removido.')
    } catch (e) {
      setError(apiMessage(e))
      setConfirmRemove(false)
    } finally {
      setRemoving(false)
    }
  }

  return (
    <Stack sx={{ gap: 2 }}>
      {cert === null ? (
        <Typography sx={muted}>Nenhum certificado enviado. Sem ele não dá para emitir nota.</Typography>
      ) : !cert.valid ? (
        <Alert severity="error">Certificado vencido em {formatDate(cert.expires_at)}. Envie o novo.</Alert>
      ) : days <= 30 ? (
        <Alert severity="warning">Certificado vence em {formatDate(cert.expires_at)} ({days} dias). Renove e envie o novo.</Alert>
      ) : (
        <Typography>Certificado válido até {formatDate(cert.expires_at)}.</Typography>
      )}
      {error ? <ErrorBox message={error} /> : null}
      <TextField key={inputKey} label="Certificado A1 (.pfx ou .p12)" type="file" accept=".pfx,.p12,application/x-pkcs12" onChange={(ev) => setFile(ev.target.files?.[0] ?? null)} />
      <TextField label="Senha do certificado" type="password" autoComplete="off" value={password} onChange={(ev) => setPassword(ev.target.value)} />
      <Button variant="secondary" onClick={send} loading={sending} loadingText="Conferindo…">
        {cert === null ? 'Enviar certificado' : 'Trocar certificado'}
      </Button>
      {cert === null ? null : (
        <Button variant="ghost" tone="danger" onClick={() => setConfirmRemove(true)}>
          Remover certificado
        </Button>
      )}
      <ConfirmDialog open={confirmRemove} title="Remover o certificado?" text="O arquivo e a senha são apagados do Prumo. Sem certificado, a oficina não emite nota fiscal." confirmLabel="Remover" danger loading={removing} onConfirm={remove} onClose={() => setConfirmRemove(false)} />
    </Stack>
  )
}

/** Dados da oficina para a NFS-e Nacional e o certificado A1 (só dono/administrador). */
export function FiscalCard() {
  const profile = useFiscalProfile()

  if (profile.isLoading) return <Card><ListSkeleton rows={4} /></Card>
  if (profile.isError) return <Card><ErrorBox message="Não deu para carregar." onRetry={() => profile.refetch()} /></Card>

  const data = profile.data ?? null
  return (
    <>
      <Card>
        <CardTitle>Dados fiscais</CardTitle>
        <ProfileForm profile={data} />
      </Card>
      <Card>
        <CardTitle>Certificado digital</CardTitle>
        {data === null ? <Typography sx={muted}>Salve os dados fiscais primeiro.</Typography> : <CertificateForm profile={data} />}
      </Card>
    </>
  )
}

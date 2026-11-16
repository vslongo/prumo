import Box from '@mui/material/Box'
import Stack from '@mui/material/Stack'
import Typography from '@mui/material/Typography'
import { useQueryClient } from '@tanstack/react-query'
import { ExternalLink, ShieldCheck } from 'lucide-react'
import { useState, type FormEvent } from 'react'
import { formatDate } from '../../../shared/lib/format'
import { ApiError } from '../../../shared/lib/http'
import { Button } from '../../../shared/ui/Button'
import { TextField } from '../../../shared/ui/Field'
import { Card, CardTitle, ErrorBox } from '../../../shared/ui/States'
import { muted } from '../../../shared/ui/styles'
import { useToast } from '../../../shared/ui/Toast'
import { authApi, type TwoFactorSetup, type User } from '../api'

const errorText = (e: unknown) => (e instanceof ApiError ? (Object.values(e.errors)[0]?.[0] ?? e.message) : 'Não deu para concluir. Tente de novo.')

/** Segredo em grupos de 4 para digitar no app sem se perder. */
const grouped = (secret: string) => secret.match(/.{1,4}/g)?.join(' ') ?? secret

/**
 * Verificação em duas etapas da própria conta: ativar (abrir no app autenticador ou digitar a chave, confirmar com
 * o primeiro código, guardar os códigos de recuperação) e desativar com a senha.
 */
export function TwoFactorCard({ me }: { me: User }) {
  const queryClient = useQueryClient()
  const toast = useToast()
  const [setup, setSetup] = useState<TwoFactorSetup | null>(null)
  const [recoveryCodes, setRecoveryCodes] = useState<string[] | null>(null)
  const [value, setValue] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)
  const [disabling, setDisabling] = useState(false)

  const run = async (fn: () => Promise<void>) => {
    setError(null)
    setBusy(true)
    try {
      await fn()
    } catch (e) {
      setError(errorText(e))
    } finally {
      setBusy(false)
    }
  }

  const refreshMe = () => queryClient.invalidateQueries({ queryKey: ['me'] })

  const start = () => run(async () => setSetup(await authApi.startTwoFactor()))

  const confirm = (e: FormEvent) => {
    e.preventDefault()
    void run(async () => {
      setRecoveryCodes(await authApi.confirmTwoFactor(value))
      setSetup(null)
      setValue('')
    })
  }

  const disable = (e: FormEvent) => {
    e.preventDefault()
    void run(async () => {
      await authApi.disableTwoFactor(value)
      setValue('')
      setDisabling(false)
      await refreshMe()
      toast('Verificação em duas etapas desativada.')
    })
  }

  const finish = async () => {
    setRecoveryCodes(null)
    await refreshMe()
    toast('Verificação em duas etapas ativada.')
  }

  return (
    <Card>
      <CardTitle>Verificação em duas etapas</CardTitle>
      {error ? (
        <Box sx={{ mb: 1.5 }}>
          <ErrorBox message={error} />
        </Box>
      ) : null}

      {recoveryCodes ? (
        <Stack sx={{ gap: 1.5 }}>
          <Typography>Guarde estes códigos de recuperação num lugar seguro. Cada um entra uma vez, se você perder o celular.</Typography>
          <Box component="ul" aria-label="Códigos de recuperação" className="num" sx={{ m: 0, p: 1.5, listStyle: 'none', display: 'grid', gridTemplateColumns: 'repeat(2, minmax(0, 1fr))', gap: 1, bgcolor: 'action.hover', borderRadius: 1, fontFamily: 'monospace' }}>
            {recoveryCodes.map((c) => (
              <li key={c}>{c}</li>
            ))}
          </Box>
          <Button onClick={finish}>Já guardei os códigos</Button>
        </Stack>
      ) : me.two_factor.enabled ? (
        disabling ? (
          <Stack component="form" onSubmit={disable} sx={{ gap: 1.5 }}>
            <TextField label="Sua senha" type="password" autoComplete="current-password" value={value} onChange={(e) => setValue(e.target.value)} />
            <Stack direction={{ xs: 'column-reverse', sm: 'row' }} sx={{ gap: 1, justifyContent: 'flex-end' }}>
              <Button variant="secondary" onClick={() => setDisabling(false)}>
                Cancelar
              </Button>
              <Button type="submit" variant="danger" loading={busy}>
                Desativar
              </Button>
            </Stack>
          </Stack>
        ) : (
          <Stack sx={{ gap: 1.5 }}>
            <Stack direction="row" sx={{ gap: 1, alignItems: 'center' }}>
              <Box component={ShieldCheck} size={20} aria-hidden sx={{ color: 'success.main' }} />
              <Typography>Ativa. O login pede o código do app autenticador.</Typography>
            </Stack>
            <Button variant="ghost" onClick={() => setDisabling(true)} sx={{ alignSelf: 'flex-start' }}>
              Desativar
            </Button>
          </Stack>
        )
      ) : setup ? (
        <Stack component="form" onSubmit={confirm} sx={{ gap: 1.5 }}>
          <Typography>1. No celular, abra o app autenticador (Google Authenticator, Microsoft Authenticator…) e adicione a conta:</Typography>
          <Button variant="secondary" icon={<ExternalLink size={18} aria-hidden />} href={setup.otpauth_url}>
            Abrir no app autenticador
          </Button>
          <Typography sx={muted}>Ou digite a chave no app:</Typography>
          <Box className="num" aria-label="Chave para digitar no app" sx={{ p: 1.5, bgcolor: 'action.hover', borderRadius: 1, fontFamily: 'monospace', wordBreak: 'break-all' }}>
            {grouped(setup.secret)}
          </Box>
          <Typography>2. Digite o código de 6 dígitos que aparece no app:</Typography>
          <TextField label="Código do app" inputMode="numeric" autoComplete="one-time-code" maxLength={6} value={value} onChange={(e) => setValue(e.target.value.replace(/\D/g, ''))} />
          <Button type="submit" loading={busy} disabled={value.length !== 6}>
            Ativar
          </Button>
        </Stack>
      ) : (
        <Stack sx={{ gap: 1.5 }}>
          <Typography sx={muted}>
            Além da senha, o login pede um código do app autenticador do celular.
            {me.two_factor.required_from ? ` Obrigatório para o dono a partir de ${formatDate(me.two_factor.required_from)}.` : ''}
          </Typography>
          <Button onClick={start} loading={busy} sx={{ alignSelf: { sm: 'flex-start' } }}>
            Ativar verificação em duas etapas
          </Button>
        </Stack>
      )}
    </Card>
  )
}

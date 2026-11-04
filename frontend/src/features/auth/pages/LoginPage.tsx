import { zodResolver } from '@hookform/resolvers/zod'
import MuiLink from '@mui/material/Link'
import Stack from '@mui/material/Stack'
import Typography from '@mui/material/Typography'
import { useQueryClient } from '@tanstack/react-query'
import { useState, type FormEvent } from 'react'
import { useForm } from 'react-hook-form'
import { Link, Navigate, useNavigate } from 'react-router'
import { z } from 'zod'
import { ApiError } from '../../../shared/lib/http'
import { Button } from '../../../shared/ui/Button'
import { TextField } from '../../../shared/ui/Field'
import { ErrorBox } from '../../../shared/ui/States'
import { authApi, useMe, type User } from '../api'
import { AuthLayout } from '../components/AuthLayout'

const schema = z.object({
  email: z.string().trim().min(1, 'Informe o e-mail.').email('E-mail inválido.'),
  password: z.string().min(1, 'Informe a senha.'),
})

type Form = z.infer<typeof schema>

export function LoginPage() {
  const { data: me } = useMe()
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const [error, setError] = useState<string | null>(null)
  const [twoFactor, setTwoFactor] = useState(false)
  const { register, handleSubmit, formState } = useForm<Form>({ resolver: zodResolver(schema) })

  if (me) return <Navigate to="/" replace />

  const enter = (user: User) => {
    queryClient.setQueryData(['me'], user)
    navigate('/', { replace: true })
  }

  const onSubmit = handleSubmit(async ({ email, password }) => {
    setError(null)
    try {
      const result = await authApi.login(email, password)
      if (result.twoFactor) setTwoFactor(true)
      else enter(result.user)
    } catch (e) {
      setError(e instanceof ApiError ? (e.field('email') ?? e.message) : 'Não deu para entrar. Tente de novo.')
    }
  })

  return (
    <AuthLayout>
      {twoFactor ? (
        <TwoFactorStep onDone={enter} onRestart={() => setTwoFactor(false)} />
      ) : (
        <>
          <Typography variant="h1" sx={{ fontSize: 28 }}>
            Entrar
          </Typography>
          <Typography color="text.secondary" sx={{ mt: 0.5 }}>
            Use o e-mail cadastrado na oficina.
          </Typography>

          <Stack component="form" onSubmit={onSubmit} noValidate sx={{ gap: 2.5, mt: 4 }}>
            {error ? <ErrorBox message={error} /> : null}
            <TextField label="E-mail" type="email" autoComplete="username" inputMode="email" error={formState.errors.email?.message} {...register('email')} />
            <TextField label="Senha" type="password" autoComplete="current-password" error={formState.errors.password?.message} {...register('password')} />
            <Button type="submit" full loading={formState.isSubmitting} loadingText="Entrando…">
              Entrar
            </Button>
          </Stack>

          <Typography color="text.secondary" sx={{ mt: 3, textAlign: 'center' }}>
            Ainda não usa o Prumo?{' '}
            <MuiLink component={Link} to="/cadastro">
              Teste grátis por 14 dias
            </MuiLink>
          </Typography>
        </>
      )}
    </AuthLayout>
  )
}

/** Segunda etapa: código do app autenticador ou, sem o celular, um código de recuperação. */
function TwoFactorStep({ onDone, onRestart }: { onDone: (user: User) => void; onRestart: () => void }) {
  const [recovery, setRecovery] = useState(false)
  const [value, setValue] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)

  const submit = async (e: FormEvent) => {
    e.preventDefault()
    setError(null)
    setBusy(true)
    try {
      onDone(await authApi.loginTwoFactor(recovery ? { recovery_code: value } : { code: value }))
    } catch (err) {
      setError(err instanceof ApiError ? (err.field('code') ?? err.message) : 'Não deu para entrar. Tente de novo.')
      setBusy(false)
    }
  }

  return (
    <>
      <Typography variant="h1" sx={{ fontSize: 28 }}>
        Verificação em duas etapas
      </Typography>
      <Typography color="text.secondary" sx={{ mt: 0.5 }}>
        {recovery ? 'Digite um dos códigos de recuperação que você guardou.' : 'Digite o código de 6 dígitos do app autenticador.'}
      </Typography>

      <Stack component="form" onSubmit={submit} noValidate sx={{ gap: 2.5, mt: 4 }}>
        {error ? <ErrorBox message={error} /> : null}
        {recovery ? (
          <TextField key="recovery" label="Código de recuperação" autoComplete="off" autoFocus value={value} onChange={(e) => setValue(e.target.value)} />
        ) : (
          <TextField key="code" label="Código do app" inputMode="numeric" autoComplete="one-time-code" maxLength={6} autoFocus value={value} onChange={(e) => setValue(e.target.value.replace(/\D/g, ''))} />
        )}
        <Button type="submit" full loading={busy} loadingText="Entrando…" disabled={recovery ? value.trim() === '' : value.length !== 6}>
          Entrar
        </Button>
      </Stack>

      <Stack sx={{ mt: 3, gap: 1, alignItems: 'center' }}>
        <MuiLink component="button" type="button" onClick={() => (setRecovery(!recovery), setValue(''), setError(null))}>
          {recovery ? 'Usar o código do app' : 'Perdi o celular: usar código de recuperação'}
        </MuiLink>
        <MuiLink component="button" type="button" onClick={onRestart} color="text.secondary">
          Voltar e entrar com outra conta
        </MuiLink>
      </Stack>
    </>
  )
}

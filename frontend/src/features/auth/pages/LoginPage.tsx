import { zodResolver } from '@hookform/resolvers/zod'
import MuiLink from '@mui/material/Link'
import Stack from '@mui/material/Stack'
import Typography from '@mui/material/Typography'
import { useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { Link, Navigate, useNavigate } from 'react-router'
import { z } from 'zod'
import { ApiError } from '../../../shared/lib/http'
import { Button } from '../../../shared/ui/Button'
import { TextField } from '../../../shared/ui/Field'
import { ErrorBox } from '../../../shared/ui/States'
import { authApi, useMe } from '../api'
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
  const { register, handleSubmit, formState } = useForm<Form>({ resolver: zodResolver(schema) })

  if (me) return <Navigate to="/" replace />

  const onSubmit = handleSubmit(async ({ email, password }) => {
    setError(null)
    try {
      const user = await authApi.login(email, password)
      queryClient.setQueryData(['me'], user)
      navigate('/', { replace: true })
    } catch (e) {
      setError(e instanceof ApiError ? (e.field('email') ?? e.message) : 'Não deu para entrar. Tente de novo.')
    }
  })

  return (
    <AuthLayout>
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
    </AuthLayout>
  )
}

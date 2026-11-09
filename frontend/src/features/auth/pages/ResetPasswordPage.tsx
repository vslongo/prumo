import { zodResolver } from '@hookform/resolvers/zod'
import MuiLink from '@mui/material/Link'
import Stack from '@mui/material/Stack'
import Typography from '@mui/material/Typography'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { Link, useParams } from 'react-router'
import { z } from 'zod'
import { ApiError } from '../../../shared/lib/http'
import { Button, ButtonLink } from '../../../shared/ui/Button'
import { TextField } from '../../../shared/ui/Field'
import { ErrorBox } from '../../../shared/ui/States'
import { authApi } from '../api'
import { AuthLayout } from '../components/AuthLayout'

const schema = z.object({
  email: z.string().trim().min(1, 'Informe o e-mail.').email('E-mail inválido.'),
  password: z.string().min(8, 'A senha precisa ter pelo menos 8 caracteres.'),
})

type Form = z.infer<typeof schema>

/** Link do e-mail de senha nova. O e-mail não vem na URL: a pessoa digita de novo. */
export function ResetPasswordPage() {
  const { token = '' } = useParams()
  const [error, setError] = useState<string | null>(null)
  const [done, setDone] = useState(false)
  const { register, handleSubmit, formState, setError: setFieldError } = useForm<Form>({ resolver: zodResolver(schema) })

  const onSubmit = handleSubmit(async ({ email, password }) => {
    setError(null)
    try {
      await authApi.resetPassword({ email, token, password })
      setDone(true)
    } catch (e) {
      if (!(e instanceof ApiError)) return setError('Não deu para salvar a senha. Tente de novo.')
      if (e.field('password')) return setFieldError('password', { message: e.field('password') })
      setError(e.status === 429 ? 'Muitas tentativas. Espere um minuto e tente de novo.' : (e.field('token') ?? e.message))
    }
  })

  if (done) {
    return (
      <AuthLayout>
        <Typography variant="h1" sx={{ fontSize: 28 }}>
          Senha nova salva
        </Typography>
        <Typography color="text.secondary" sx={{ mt: 0.5 }}>
          Por segurança, saímos da sua conta em todos os aparelhos. Entre de novo com a senha nova.
        </Typography>
        <ButtonLink to="/login" full sx={{ mt: 4 }}>
          Entrar
        </ButtonLink>
      </AuthLayout>
    )
  }

  return (
    <AuthLayout>
      <Typography variant="h1" sx={{ fontSize: 28 }}>
        Criar senha nova
      </Typography>
      <Typography color="text.secondary" sx={{ mt: 0.5 }}>
        Confirme o e-mail da conta e escolha a senha nova.
      </Typography>

      <Stack component="form" onSubmit={onSubmit} noValidate sx={{ gap: 2.5, mt: 4 }}>
        {error ? <ErrorBox message={error} /> : null}
        <TextField label="E-mail" type="email" autoComplete="username" inputMode="email" error={formState.errors.email?.message} {...register('email')} />
        <TextField label="Senha nova" type="password" autoComplete="new-password" hint="Pelo menos 8 caracteres." error={formState.errors.password?.message} {...register('password')} />
        <Button type="submit" full loading={formState.isSubmitting} loadingText="Salvando…">
          Salvar senha nova
        </Button>
      </Stack>

      <Typography color="text.secondary" sx={{ mt: 3, textAlign: 'center' }}>
        <MuiLink component={Link} to="/esqueci-senha">
          Pedir um link novo
        </MuiLink>
      </Typography>
    </AuthLayout>
  )
}

import { zodResolver } from '@hookform/resolvers/zod'
import MuiLink from '@mui/material/Link'
import Stack from '@mui/material/Stack'
import Typography from '@mui/material/Typography'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { Link } from 'react-router'
import { z } from 'zod'
import { ApiError } from '../../../shared/lib/http'
import { Button } from '../../../shared/ui/Button'
import { TextField } from '../../../shared/ui/Field'
import { ErrorBox } from '../../../shared/ui/States'
import { authApi } from '../api'
import { AuthLayout } from '../components/AuthLayout'

const schema = z.object({ email: z.string().trim().min(1, 'Informe o e-mail.').email('E-mail inválido.') })

type Form = z.infer<typeof schema>

/** "Esqueci minha senha": a resposta é a mesma, tenha o e-mail cadastro ou não. */
export function ForgotPasswordPage() {
  const [error, setError] = useState<string | null>(null)
  const [sent, setSent] = useState(false)
  const { register, handleSubmit, formState } = useForm<Form>({ resolver: zodResolver(schema) })

  const onSubmit = handleSubmit(async ({ email }) => {
    setError(null)
    try {
      await authApi.forgotPassword(email)
      setSent(true)
    } catch (e) {
      setError(e instanceof ApiError && e.status === 429 ? 'Muitas tentativas. Espere um minuto e tente de novo.' : 'Não deu para enviar. Tente de novo.')
    }
  })

  return (
    <AuthLayout>
      <Typography variant="h1" sx={{ fontSize: 28 }}>
        {sent ? 'Confira seu e-mail' : 'Esqueci minha senha'}
      </Typography>
      <Typography color="text.secondary" sx={{ mt: 0.5 }}>
        {sent
          ? 'Se esse e-mail tem cadastro no Prumo, o link para criar uma senha nova chega em alguns minutos. Ele vale por 1 hora. Olhe também a caixa de spam.'
          : 'Digite o e-mail da sua conta. A gente manda um link para você criar uma senha nova.'}
      </Typography>

      {sent ? null : (
        <Stack component="form" onSubmit={onSubmit} noValidate sx={{ gap: 2.5, mt: 4 }}>
          {error ? <ErrorBox message={error} /> : null}
          <TextField label="E-mail" type="email" autoComplete="username" inputMode="email" error={formState.errors.email?.message} {...register('email')} />
          <Button type="submit" full loading={formState.isSubmitting} loadingText="Enviando…">
            Enviar link
          </Button>
        </Stack>
      )}

      <Typography color="text.secondary" sx={{ mt: 3, textAlign: 'center' }}>
        <MuiLink component={Link} to="/login">
          Voltar para o login
        </MuiLink>
      </Typography>
    </AuthLayout>
  )
}

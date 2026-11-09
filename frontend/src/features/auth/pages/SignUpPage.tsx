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
import { CheckboxField, TextField } from '../../../shared/ui/Field'
import { ErrorBox } from '../../../shared/ui/States'
import { authApi, useMe, type SignUpInput } from '../api'
import { AuthLayout } from '../components/AuthLayout'

const schema = z.object({
  workshop_name: z.string().trim().min(1, 'Informe o nome da oficina.').max(120),
  name: z.string().trim().min(1, 'Informe seu nome.').max(120),
  email: z.string().trim().min(1, 'Informe o e-mail.').email('E-mail inválido.'),
  password: z.string().min(8, 'A senha precisa ter pelo menos 8 caracteres.'),
  accept_terms: z.boolean().refine((v) => v, 'Marque que você leu e aceita os termos para criar a conta.'),
})

const fields = ['workshop_name', 'name', 'email', 'password', 'accept_terms'] as const

export function SignUpPage() {
  const { data: me } = useMe()
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const [error, setError] = useState<string | null>(null)
  const { register, handleSubmit, formState, setError: setFieldError } = useForm<SignUpInput>({ resolver: zodResolver(schema), defaultValues: { accept_terms: false } })

  if (me) return <Navigate to="/" replace />

  const onSubmit = handleSubmit(async (input) => {
    setError(null)
    try {
      const user = await authApi.signUp(input)
      queryClient.setQueryData(['me'], user)
      navigate('/', { replace: true })
    } catch (e) {
      if (!(e instanceof ApiError)) return setError('Não deu para criar a conta. Tente de novo.')
      const withField = fields.filter((f) => e.field(f))
      withField.forEach((f) => setFieldError(f, { message: e.field(f) }))
      if (!withField.length) setError(e.status === 429 ? 'Muitas tentativas. Espere um pouco e tente de novo.' : e.message)
    }
  })

  return (
    <AuthLayout>
      <Typography variant="h1" sx={{ fontSize: 28 }}>
        Teste grátis por 14 dias
      </Typography>
      <Typography color="text.secondary" sx={{ mt: 0.5 }}>
        Sem cartão. Você já entra no painel e manda o primeiro orçamento hoje.
      </Typography>

      <Stack component="form" onSubmit={onSubmit} noValidate sx={{ gap: 2.5, mt: 4 }}>
        {error ? <ErrorBox message={error} /> : null}
        <TextField label="Nome da oficina" autoComplete="organization" error={formState.errors.workshop_name?.message} {...register('workshop_name')} />
        <TextField label="Seu nome" autoComplete="name" error={formState.errors.name?.message} {...register('name')} />
        <TextField label="E-mail" type="email" autoComplete="username" inputMode="email" error={formState.errors.email?.message} {...register('email')} />
        <TextField label="Senha" type="password" autoComplete="new-password" hint="Pelo menos 8 caracteres." error={formState.errors.password?.message} {...register('password')} />
        <div>
          <CheckboxField
            label={
              <>
                Li e aceito os{' '}
                <MuiLink component={Link} to="/termos" target="_blank">
                  termos de uso
                </MuiLink>{' '}
                e a{' '}
                <MuiLink component={Link} to="/privacidade" target="_blank">
                  política de privacidade
                </MuiLink>
                .
              </>
            }
            {...register('accept_terms')}
          />
          {formState.errors.accept_terms ? (
            <Typography role="alert" variant="body2" color="error">
              {formState.errors.accept_terms.message}
            </Typography>
          ) : null}
        </div>
        <Button type="submit" full loading={formState.isSubmitting} loadingText="Criando conta…">
          Criar conta
        </Button>
      </Stack>

      <Typography color="text.secondary" sx={{ mt: 3, textAlign: 'center' }}>
        Já tem conta?{' '}
        <MuiLink component={Link} to="/login">
          Entrar
        </MuiLink>
      </Typography>
    </AuthLayout>
  )
}

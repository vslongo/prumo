import { zodResolver } from '@hookform/resolvers/zod'
import Stack from '@mui/material/Stack'
import Typography from '@mui/material/Typography'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { useNavigate, useParams } from 'react-router'
import { z } from 'zod'
import { ApiError } from '../../../shared/lib/http'
import { Button } from '../../../shared/ui/Button'
import { TextField } from '../../../shared/ui/Field'
import { ErrorBox, ListSkeleton } from '../../../shared/ui/States'
import { authApi, roleLabel, type AcceptInvitationInput } from '../api'
import { AuthLayout } from '../components/AuthLayout'

const schema = z.object({
  name: z.string().trim().min(1, 'Informe seu nome.').max(120),
  email: z.string().trim().min(1, 'Informe o e-mail.').email('E-mail inválido.'),
  password: z.string().min(8, 'A senha precisa ter pelo menos 8 caracteres.'),
})

const fields = ['name', 'email', 'password'] as const

/** Link que o funcionário recebe: cria o acesso dele na oficina, com o papel definido pelo dono. */
export function AcceptInvitationPage() {
  const { token = '' } = useParams()
  const invitation = useQuery({ queryKey: ['invitation', token], queryFn: () => authApi.invitation(token), retry: false })

  return (
    <AuthLayout>
      {invitation.isLoading ? (
        <ListSkeleton rows={3} />
      ) : invitation.isError || !invitation.data ? (
        <ErrorBox message="Convite inválido. Confira o link ou peça um novo ao dono da oficina." />
      ) : !invitation.data.usable ? (
        <ErrorBox message="Este convite já foi usado ou expirou. Peça um novo ao dono da oficina." />
      ) : (
        <AcceptForm token={token} name={invitation.data.name} title={`Entrar na ${invitation.data.workshop}`} role={roleLabel[invitation.data.role]} />
      )}
    </AuthLayout>
  )
}

function AcceptForm({ token, name, title, role }: { token: string; name: string; title: string; role: string }) {
  const queryClient = useQueryClient()
  const navigate = useNavigate()
  const [error, setError] = useState<string | null>(null)
  const { register, handleSubmit, formState, setError: setFieldError } = useForm<AcceptInvitationInput>({ resolver: zodResolver(schema), defaultValues: { name } })

  const onSubmit = handleSubmit(async (input) => {
    setError(null)
    try {
      const user = await authApi.acceptInvitation(token, input)
      queryClient.setQueryData(['me'], user)
      navigate('/', { replace: true })
    } catch (e) {
      if (!(e instanceof ApiError)) return setError('Não deu para criar o acesso. Tente de novo.')
      const withField = fields.filter((f) => e.field(f))
      withField.forEach((f) => setFieldError(f, { message: e.field(f) }))
      if (!withField.length) setError(e.field('invitation') ?? e.message)
    }
  })

  return (
    <>
      <Typography variant="h1" sx={{ fontSize: 28 }}>
        {title}
      </Typography>
      <Typography color="text.secondary" sx={{ mt: 0.5 }}>
        Você foi convidado como {role}. Crie seu acesso.
      </Typography>

      <Stack component="form" onSubmit={onSubmit} noValidate sx={{ gap: 2.5, mt: 4 }}>
        {error ? <ErrorBox message={error} /> : null}
        <TextField label="Seu nome" autoComplete="name" error={formState.errors.name?.message} {...register('name')} />
        <TextField label="E-mail" type="email" autoComplete="username" inputMode="email" hint="Você vai usar para entrar." error={formState.errors.email?.message} {...register('email')} />
        <TextField label="Senha" type="password" autoComplete="new-password" hint="Pelo menos 8 caracteres." error={formState.errors.password?.message} {...register('password')} />
        <Button type="submit" full loading={formState.isSubmitting} loadingText="Criando acesso…">
          Criar acesso
        </Button>
      </Stack>
    </>
  )
}

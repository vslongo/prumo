import { zodResolver } from '@hookform/resolvers/zod'
import Box from '@mui/material/Box'
import Stack from '@mui/material/Stack'
import Typography from '@mui/material/Typography'
import { useQueryClient } from '@tanstack/react-query'
import { Check } from 'lucide-react'
import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { Navigate, useNavigate } from 'react-router'
import { z } from 'zod'
import { ApiError } from '../../../shared/lib/http'
import { Button } from '../../../shared/ui/Button'
import { TextField } from '../../../shared/ui/Field'
import { Logo, LogoMark } from '../../../shared/ui/Logo'
import { ErrorBox } from '../../../shared/ui/States'
import { authApi, useMe } from '../api'

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
    <Box sx={{ minHeight: '100dvh', display: 'grid', gridTemplateColumns: { xs: 'minmax(0, 1fr)', lg: 'minmax(420px, 5fr) 7fr' } }}>
      <Box
        component="aside" sx={{ position: 'relative', display: { xs: 'none', lg: 'flex' }, flexDirection: 'column', justifyContent: 'space-between', overflow: 'hidden', bgcolor: 'primary.main', color: 'primary.contrastText', p: 5 }}
      >
        <Stack direction="row" sx={{ alignItems: 'center', gap: 1.5 }}>
          <LogoMark size={40} />
          <Typography sx={{ fontFamily: 'var(--font-display)', fontSize: 24, fontWeight: 700, letterSpacing: '0.025em' }}>Prumo</Typography>
        </Stack>
        <div>
          <Typography sx={{ fontFamily: 'var(--font-display)', fontSize: 40, lineHeight: 1.1, fontWeight: 700 }}>Sua oficina no prumo.</Typography>
          <Stack component="ul" sx={{ gap: 1.5, mt: 4, p: 0, listStyle: 'none', fontSize: 18, opacity: 0.95 }}>
            {['Orçamento pelo celular, aprovado no WhatsApp.', 'OS aberta na hora, com técnico em cada item.', 'Histórico de cada carro, sem papel.'].map((t) => (
              <Stack component="li" key={t} direction="row" sx={{ alignItems: 'flex-start', gap: 1.5 }}>
                <Box component={Check} size={22} aria-hidden sx={{ mt: 0.25, flexShrink: 0, color: 'secondary.main' }} />
                {t}
              </Stack>
            ))}
          </Stack>
        </div>
        <Typography variant="body2" sx={{ opacity: 0.8 }}>
          Feito para oficinas de 2 a 10 pessoas.
        </Typography>
        {/* fio de prumo: elemento gráfico da marca */}
        <Box component="span" aria-hidden sx={{ position: 'absolute', top: 0, right: 64, height: '58%', width: '1px', bgcolor: 'currentColor', opacity: 0.25 }} />
        <Box component="span" aria-hidden sx={{ position: 'absolute', top: '58%', right: 60, width: 9, height: 9, transform: 'rotate(45deg)', bgcolor: 'secondary.main' }} />
      </Box>

      <Box component="main" sx={{ display: 'flex', alignItems: 'center', justifyContent: 'center', px: 2.5, py: 5 }}>
        <Box sx={{ width: '100%', maxWidth: 384 }}>
          <Box sx={{ mb: 4, display: { lg: 'none' } }}>
            <Logo height={34} />
          </Box>
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
        </Box>
      </Box>
    </Box>
  )
}

import { zodResolver } from '@hookform/resolvers/zod'
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
    <div className="grid min-h-dvh lg:grid-cols-[minmax(420px,5fr)_7fr]">
      <aside className="relative hidden flex-col justify-between overflow-hidden bg-brand p-10 text-on-brand lg:flex">
        <div className="flex items-center gap-3">
          <LogoMark size={40} />
          <span className="font-display text-2xl font-bold tracking-wide">Prumo</span>
        </div>
        <div>
          <p className="font-display text-[40px] leading-[1.1] font-bold">Sua oficina no prumo.</p>
          <ul className="mt-8 flex flex-col gap-3 text-lg opacity-95">
            {['Orçamento pelo celular, aprovado no WhatsApp.', 'OS aberta na hora, com técnico em cada item.', 'Histórico de cada carro, sem papel.'].map((t) => (
              <li key={t} className="flex items-start gap-3">
                <Check size={22} className="mt-0.5 shrink-0 text-accent" aria-hidden />
                {t}
              </li>
            ))}
          </ul>
        </div>
        <p className="text-sm opacity-80">Feito para oficinas de 2 a 10 pessoas.</p>
        {/* fio de prumo: elemento gráfico da marca */}
        <span className="absolute top-0 right-16 h-[58%] w-px bg-on-brand/25" aria-hidden />
        <span className="absolute top-[58%] right-[60px] size-[9px] rotate-45 bg-accent" aria-hidden />
      </aside>

      <main className="flex items-center justify-center px-5 py-10">
        <div className="w-full max-w-sm">
          <div className="mb-8 lg:hidden">
            <Logo height={34} />
          </div>
          <h1 className="text-[28px] font-bold">Entrar</h1>
          <p className="mt-1 text-muted">Use o e-mail cadastrado na oficina.</p>

          <form onSubmit={onSubmit} noValidate className="mt-8 flex flex-col gap-5">
            {error ? <ErrorBox message={error} /> : null}
            <TextField label="E-mail" type="email" autoComplete="username" inputMode="email" error={formState.errors.email?.message} {...register('email')} />
            <TextField label="Senha" type="password" autoComplete="current-password" error={formState.errors.password?.message} {...register('password')} />
            <Button type="submit" full loading={formState.isSubmitting} loadingText="Entrando…">
              Entrar
            </Button>
          </form>
        </div>
      </main>
    </div>
  )
}

import { SearchX } from 'lucide-react'
import { ButtonLink } from '../shared/ui/Button'
import { EmptyState } from '../shared/ui/States'

export function NotFoundPage() {
  return (
    <EmptyState
      icon={SearchX}
      title="Página não encontrada."
      text="O endereço pode estar errado ou o registro não existe mais."
      action={<ButtonLink to="/">Voltar ao início</ButtonLink>}
    />
  )
}

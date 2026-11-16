import Skeleton from '@mui/material/Skeleton'
import { PageHeader } from '../../../shared/ui/States'
import { useMe } from '../api'
import { TwoFactorCard } from '../components/TwoFactorCard'

/** Segurança da própria conta (qualquer papel). */
export function SecurityPage() {
  const { data: me } = useMe()
  if (!me) return <Skeleton height={160} />

  return (
    <>
      <PageHeader title="Segurança" subtitle={me.email} />
      <TwoFactorCard me={me} />
    </>
  )
}

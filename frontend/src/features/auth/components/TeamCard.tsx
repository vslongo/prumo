import Box from '@mui/material/Box'
import Stack from '@mui/material/Stack'
import Typography from '@mui/material/Typography'
import { Copy, MessageCircle, UserPlus } from 'lucide-react'
import { useState } from 'react'
import { ApiError } from '../../../shared/lib/http'
import { Button } from '../../../shared/ui/Button'
import { SelectField, TextField } from '../../../shared/ui/Field'
import { Card, CardTitle, ErrorBox, ListSkeleton } from '../../../shared/ui/States'
import { muted } from '../../../shared/ui/styles'
import { useToast } from '../../../shared/ui/Toast'
import { authApi, roleLabel, useUsers, type InvitableRole, type SentInvitation } from '../api'

const invitableRoles: InvitableRole[] = ['technician', 'front', 'admin']

/** Equipe da oficina + convite por link (o funcionário cria o próprio acesso). */
export function TeamCard() {
  const users = useUsers()
  const toast = useToast()
  const [name, setName] = useState('')
  const [role, setRole] = useState<InvitableRole>('technician')
  const [sent, setSent] = useState<SentInvitation | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [saving, setSaving] = useState(false)

  const invite = async () => {
    setError(null)
    if (!name.trim()) return setError('Informe o nome do funcionário.')
    setSaving(true)
    try {
      setSent(await authApi.invite(name.trim(), role))
    } catch (e) {
      setError(e instanceof ApiError ? (Object.values(e.errors)[0]?.[0] ?? e.message) : 'Não deu para gerar o convite. Tente de novo.')
    } finally {
      setSaving(false)
    }
  }

  const copy = async (text: string) => {
    await navigator.clipboard?.writeText(text).catch(() => undefined)
    toast('Link copiado.')
  }

  const reset = () => {
    setSent(null)
    setName('')
  }

  return (
    <Card>
      <CardTitle>Equipe</CardTitle>
      {users.isLoading ? (
        <ListSkeleton rows={2} />
      ) : users.isError ? (
        <ErrorBox message="Não deu para carregar." onRetry={() => users.refetch()} />
      ) : (
        <Box component="ul" sx={{ m: 0, mb: 3, p: 0, listStyle: 'none', '& > li': { py: 1.25 }, '& > li + li': { borderTop: 1, borderColor: 'divider' }, '& > li:first-of-type': { pt: 0 } }}>
          {users.data?.map((u) => (
            <Stack component="li" key={u.id} direction="row" sx={{ alignItems: 'baseline', justifyContent: 'space-between', gap: 1.5 }}>
              <Typography sx={{ fontWeight: 600, minWidth: 0, overflowWrap: 'anywhere' }}>{u.name}</Typography>
              <Typography variant="body2" sx={muted}>
                {roleLabel[u.role]}
              </Typography>
            </Stack>
          ))}
        </Box>
      )}

      {sent ? (
        <Stack sx={{ gap: 1.5 }}>
          <Typography variant="body2" sx={muted}>
            Mande o link para {name.trim()}. Vale por 7 dias e só pode ser usado uma vez.
          </Typography>
          <TextField label="Link de convite" readOnly value={sent.link} onFocus={(e) => e.target.select()} sx={{ '& .MuiOutlinedInput-root': { bgcolor: 'background.default', fontSize: 14 } }} />
          <Button href={sent.whatsapp_url} target="_blank" rel="noreferrer" full icon={<MessageCircle size={18} aria-hidden />}>
            Enviar pelo WhatsApp
          </Button>
          <Button variant="secondary" full icon={<Copy size={18} aria-hidden />} onClick={() => copy(sent.link)}>
            Copiar link
          </Button>
          <Button variant="ghost" full onClick={reset}>
            Convidar outra pessoa
          </Button>
        </Stack>
      ) : (
        <Stack sx={{ gap: 2 }}>
          {error ? <ErrorBox message={error} /> : null}
          <TextField label="Nome do funcionário" placeholder="Ex.: Zé" value={name} onChange={(e) => setName(e.target.value)} />
          <SelectField label="Papel" value={role} onChange={(e) => setRole(e.target.value as InvitableRole)}>
            {invitableRoles.map((r) => (
              <option key={r} value={r}>
                {roleLabel[r]}
              </option>
            ))}
          </SelectField>
          <Button onClick={invite} loading={saving} loadingText="Gerando convite…" icon={<UserPlus size={18} aria-hidden />}>
            Gerar link de convite
          </Button>
        </Stack>
      )}
    </Card>
  )
}

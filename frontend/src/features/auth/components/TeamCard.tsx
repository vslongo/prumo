import Box from '@mui/material/Box'
import Stack from '@mui/material/Stack'
import Typography from '@mui/material/Typography'
import { useQueryClient } from '@tanstack/react-query'
import { Copy, MessageCircle, UserPlus } from 'lucide-react'
import { useState } from 'react'
import { formatWhen } from '../../../shared/lib/format'
import { ApiError } from '../../../shared/lib/http'
import { Button } from '../../../shared/ui/Button'
import { ConfirmDialog } from '../../../shared/ui/ConfirmDialog'
import { SelectField, TextField } from '../../../shared/ui/Field'
import { Card, CardTitle, ErrorBox, ListSkeleton } from '../../../shared/ui/States'
import { muted } from '../../../shared/ui/styles'
import { useToast } from '../../../shared/ui/Toast'
import { authApi, roleLabel, useMe, usePendingInvitations, useUsers, type InvitableRole, type User } from '../api'

const invitableRoles: InvitableRole[] = ['technician', 'front', 'admin']

// Rótulo só para leitor de tela (o contexto visual já diz o que é o campo).
const srOnly = { position: 'absolute', width: 1, height: 1, overflow: 'hidden', clip: 'rect(0 0 0 0)' }

const listSx = { m: 0, p: 0, listStyle: 'none', '& > li': { py: 1.25 }, '& > li + li': { borderTop: 1, borderColor: 'divider' }, '& > li:first-of-type': { pt: 0 } }

const apiMessage = (e: unknown, fallback: string) => (e instanceof ApiError ? (Object.values(e.errors)[0]?.[0] ?? e.message) : fallback)

/** Equipe da oficina: papéis, desativar/reativar, convites pendentes e convite novo por link. */
export function TeamCard() {
  const { data: me } = useMe()
  const users = useUsers()
  const invitations = usePendingInvitations()
  const queryClient = useQueryClient()
  const toast = useToast()
  const [toDeactivate, setToDeactivate] = useState<User | null>(null)
  const [busy, setBusy] = useState(false)

  const run = async (action: () => Promise<unknown>, done: string) => {
    setBusy(true)
    try {
      await action()
      await Promise.all([queryClient.invalidateQueries({ queryKey: ['users'] }), queryClient.invalidateQueries({ queryKey: ['user-invitations'] })])
      toast(done)
    } catch (e) {
      toast(apiMessage(e, 'Não deu para salvar. Tente de novo.'))
    } finally {
      setBusy(false)
    }
  }

  const copy = async (text: string) => {
    await navigator.clipboard?.writeText(text).catch(() => undefined)
    toast('Link copiado.')
  }

  // Espelha a UserPolicy: dono e a própria pessoa nunca; admin só o dono gerencia e cria.
  const isOwner = me?.role === 'owner'
  const roles = isOwner ? invitableRoles : invitableRoles.filter((r) => r !== 'admin')
  const editable = (u: User) => u.role !== 'owner' && u.id !== me?.id && (isOwner || u.role !== 'admin')

  return (
    <Card>
      <CardTitle>Equipe</CardTitle>
      {users.isLoading ? (
        <ListSkeleton rows={2} />
      ) : users.isError ? (
        <ErrorBox message="Não deu para carregar." onRetry={() => users.refetch()} />
      ) : (
        <Box component="ul" sx={listSx}>
          {users.data?.map((u) => (
            <li key={u.id}>
              <Stack direction="row" sx={{ alignItems: 'center', justifyContent: 'space-between', gap: 1.5, flexWrap: 'wrap' }}>
                <Box sx={{ minWidth: 0, opacity: u.is_active ? 1 : 0.6 }}>
                  <Typography sx={{ fontWeight: 600, overflowWrap: 'anywhere' }}>{u.name}</Typography>
                  <Typography variant="body2" sx={muted}>
                    {u.is_active ? roleLabel[u.role] : `${roleLabel[u.role]} · desativado`}
                  </Typography>
                </Box>
                {editable(u) ? (
                  <Stack direction="row" sx={{ alignItems: 'center', gap: 1 }}>
                    {u.is_active ? (
                      <>
                        <SelectField label={`Papel de ${u.name}`} value={u.role} disabled={busy} onChange={(e) => run(() => authApi.changeRole(u.id, e.target.value as InvitableRole), 'Papel alterado.')} sx={{ width: 150, '& label': srOnly }}>
                          {roles.map((r) => (
                            <option key={r} value={r}>
                              {roleLabel[r]}
                            </option>
                          ))}
                        </SelectField>
                        <Button variant="ghost" tone="danger" size="small" disabled={busy} onClick={() => setToDeactivate(u)}>
                          Desativar
                        </Button>
                      </>
                    ) : (
                      <Button variant="secondary" size="small" disabled={busy} onClick={() => run(() => authApi.reactivate(u.id), `${u.name} pode entrar de novo.`)}>
                        Reativar
                      </Button>
                    )}
                  </Stack>
                ) : null}
              </Stack>
            </li>
          ))}
        </Box>
      )}

      {invitations.data?.length ? (
        <Box sx={{ mt: 3 }}>
          <Typography variant="body2" sx={[muted, { fontWeight: 600, mb: 1 }]}>
            Convites pendentes
          </Typography>
          <Box component="ul" sx={listSx}>
            {invitations.data.map((i) => (
              <li key={i.id}>
                <Stack direction="row" sx={{ alignItems: 'center', justifyContent: 'space-between', gap: 1.5 }}>
                  <Box sx={{ minWidth: 0 }}>
                    <Typography sx={{ fontWeight: 600, overflowWrap: 'anywhere' }}>{i.name}</Typography>
                    <Typography variant="body2" sx={muted}>
                      {roleLabel[i.role]} · vale até {formatWhen(i.expires_at)}
                    </Typography>
                  </Box>
                  <Button variant="ghost" tone="danger" size="small" disabled={busy} onClick={() => run(() => authApi.revokeInvitation(i.id), 'Convite cancelado.')}>
                    Cancelar
                  </Button>
                </Stack>
                {i.link && i.whatsapp_url ? (
                  <Stack sx={{ gap: 1, mt: 1 }}>
                    <TextField label={`Link de convite de ${i.name}`} readOnly value={i.link} onFocus={(e) => e.target.select()} sx={{ '& label': srOnly, '& .MuiOutlinedInput-root': { bgcolor: 'background.default', fontSize: 14 } }} />
                    <Box sx={{ display: 'grid', gridTemplateColumns: 'repeat(2, minmax(0, 1fr))', gap: 1 }}>
                      <Button href={i.whatsapp_url} target="_blank" rel="noreferrer" size="small" icon={<MessageCircle size={16} aria-hidden />}>
                        WhatsApp
                      </Button>
                      <Button variant="secondary" size="small" icon={<Copy size={16} aria-hidden />} onClick={() => copy(i.link ?? '')}>
                        Copiar link
                      </Button>
                    </Box>
                  </Stack>
                ) : null}
              </li>
            ))}
          </Box>
        </Box>
      ) : null}

      <InviteForm roles={roles} onSent={() => queryClient.invalidateQueries({ queryKey: ['user-invitations'] })} />

      <ConfirmDialog
        open={toDeactivate !== null}
        title={`Desativar ${toDeactivate?.name ?? ''}?`}
        text="A pessoa sai do sistema na hora e não consegue mais entrar. As OS e orçamentos dela continuam com o nome dela. Dá para reativar depois."
        confirmLabel="Desativar"
        danger
        loading={busy}
        onClose={() => setToDeactivate(null)}
        onConfirm={async () => {
          const u = toDeactivate
          if (!u) return
          await run(() => authApi.deactivate(u.id), `${u.name} foi desativado.`)
          setToDeactivate(null)
        }}
      />
    </Card>
  )
}

/** Convite novo: entra na lista de pendentes, onde fica o link para mandar (vale 7 dias, uso único). */
function InviteForm({ roles, onSent }: { roles: InvitableRole[]; onSent: () => Promise<unknown> }) {
  const toast = useToast()
  const [name, setName] = useState('')
  const [role, setRole] = useState<InvitableRole>('technician')
  const [error, setError] = useState<string | null>(null)
  const [saving, setSaving] = useState(false)

  const invite = async () => {
    setError(null)
    if (!name.trim()) return setError('Informe o nome do funcionário.')
    setSaving(true)
    try {
      await authApi.invite(name.trim(), role)
      await onSent()
      toast(`Convite criado. Mande o link para ${name.trim()} em "Convites pendentes".`)
      setName('')
    } catch (e) {
      setError(apiMessage(e, 'Não deu para gerar o convite. Tente de novo.'))
    } finally {
      setSaving(false)
    }
  }

  return (
    <Stack sx={{ gap: 2, mt: 3, pt: 3, borderTop: 1, borderColor: 'divider' }}>
      <Typography variant="body2" sx={[muted, { fontWeight: 600 }]}>
        Convidar funcionário
      </Typography>
      {error ? <ErrorBox message={error} /> : null}
      <TextField label="Nome do funcionário" placeholder="Ex.: Zé" value={name} onChange={(e) => setName(e.target.value)} />
      <SelectField label="Papel" value={role} onChange={(e) => setRole(e.target.value as InvitableRole)}>
        {roles.map((r) => (
          <option key={r} value={r}>
            {roleLabel[r]}
          </option>
        ))}
      </SelectField>
      <Button onClick={invite} loading={saving} loadingText="Gerando convite…" icon={<UserPlus size={18} aria-hidden />}>
        Gerar link de convite
      </Button>
    </Stack>
  )
}

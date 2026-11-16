import Stack from '@mui/material/Stack'
import Typography from '@mui/material/Typography'
import { Download, UserX } from 'lucide-react'
import { useState } from 'react'
import { ApiError } from '../../../shared/lib/http'
import { Button } from '../../../shared/ui/Button'
import { ConfirmDialog } from '../../../shared/ui/ConfirmDialog'
import { Card, CardTitle } from '../../../shared/ui/States'
import { muted } from '../../../shared/ui/styles'
import { useToast } from '../../../shared/ui/Toast'
import { customersApi, type Customer } from '../api'

function downloadJson(filename: string, data: unknown) {
  const url = URL.createObjectURL(new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' }))
  const a = Object.assign(document.createElement('a'), { href: url, download: filename })
  a.click()
  URL.revokeObjectURL(url)
}

/** Pedidos do titular pela LGPD (só dono e administrador): baixar os dados e anonimizar. */
export function CustomerPrivacyCard({ customer, onAnonymized }: { customer: Customer; onAnonymized: (c: Customer) => void }) {
  const toast = useToast()
  const [exporting, setExporting] = useState(false)
  const [confirming, setConfirming] = useState(false)
  const [anonymizing, setAnonymizing] = useState(false)

  const exportData = async () => {
    setExporting(true)
    try {
      downloadJson(`cliente-${customer.id}.json`, await customersApi.exportData(customer.id))
    } catch (e) {
      toast(e instanceof ApiError ? e.message : 'Não deu para exportar. Tente de novo.')
    } finally {
      setExporting(false)
    }
  }

  const anonymize = async () => {
    setAnonymizing(true)
    try {
      onAnonymized(await customersApi.anonymize(customer.id))
      setConfirming(false)
      toast('Cliente anonimizado.')
    } catch (e) {
      toast(e instanceof ApiError ? e.message : 'Não deu para anonimizar. Tente de novo.')
    } finally {
      setAnonymizing(false)
    }
  }

  return (
    <Card>
      <CardTitle>Privacidade (LGPD)</CardTitle>
      <Typography sx={[muted, { mb: 1.5 }]}>Use quando o cliente pedir uma cópia dos dados dele ou pedir para ser esquecido.</Typography>
      <Stack sx={{ gap: 1 }}>
        <Button variant="secondary" icon={<Download size={18} aria-hidden />} loading={exporting} loadingText="Exportando…" onClick={exportData}>
          Baixar dados do cliente
        </Button>
        {customer.anonymized_at ? null : (
          <Button variant="ghost" icon={<UserX size={18} aria-hidden />} onClick={() => setConfirming(true)}>
            Anonimizar cliente
          </Button>
        )}
      </Stack>

      <ConfirmDialog
        open={confirming}
        title={`Anonimizar ${customer.name}?`}
        text="Nome, celular, e-mail, CPF/CNPJ e autorização de WhatsApp serão apagados de vez. Orçamentos, OS e veículos continuam, sem identificar a pessoa. Não dá para desfazer."
        confirmLabel="Anonimizar"
        danger
        loading={anonymizing}
        onConfirm={anonymize}
        onClose={() => setConfirming(false)}
      />
    </Card>
  )
}

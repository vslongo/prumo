import Box from '@mui/material/Box'
import ButtonBase from '@mui/material/ButtonBase'
import CircularProgress from '@mui/material/CircularProgress'
import Dialog from '@mui/material/Dialog'
import IconButton from '@mui/material/IconButton'
import Stack from '@mui/material/Stack'
import Typography from '@mui/material/Typography'
import { Camera, RotateCw, Trash2, X } from 'lucide-react'
import { useState } from 'react'
import { dismissUpload, retryUpload, useUploads } from '../../../shared/upload/queue'

/** Só o que a tela precisa; serve para fotos internas e do link público. */
export type PhotoView = { key: string; url: string; thumb_url: string; removable?: boolean }

type Props = {
  photos: PhotoView[]
  /** Id do item: liga as prévias da fila de envio a este item. */
  uploadGroup?: string
  onAdd?: (files: File[]) => void
  onRemove?: (key: string) => void
  label?: string
}

const tile = { width: 64, height: 64, flexShrink: 0, borderRadius: 1, overflow: 'hidden', bgcolor: 'action.hover', position: 'relative' } as const

/** Miniaturas do item + botão de câmera. O envio corre em segundo plano; a tela nunca espera. */
export function ItemPhotos({ photos, uploadGroup, onAdd, onRemove, label = 'Fotos do item' }: Props) {
  const uploads = useUploads().filter((u) => uploadGroup && u.group === uploadGroup)
  const [open, setOpen] = useState<PhotoView | null>(null)

  if (!photos.length && !uploads.length && !onAdd) return null

  return (
    <>
      <Stack direction="row" role="list" aria-label={label} sx={{ mt: 1, gap: 1, flexWrap: 'wrap' }}>
        {photos.map((photo) => (
          <Box key={photo.key} role="listitem">
            <ButtonBase aria-label="Ver foto" onClick={() => setOpen(photo)} sx={tile}>
              <Box component="img" src={photo.thumb_url} alt="" loading="lazy" sx={{ width: '100%', height: '100%', objectFit: 'cover' }} />
            </ButtonBase>
          </Box>
        ))}

        {uploads.map((u) => (
          <Box key={u.id} role="listitem" aria-label={u.status === 'failed' ? `Foto não enviada: ${u.error}` : 'Enviando foto'} title={u.error} sx={tile}>
            <Box component="img" src={u.previewUrl} alt="" sx={{ width: '100%', height: '100%', objectFit: 'cover', opacity: 0.5 }} />
            <Stack sx={{ position: 'absolute', inset: 0, alignItems: 'center', justifyContent: 'center' }}>
              {u.status === 'failed' ? (
                <Stack direction="row">
                  <IconButton size="small" aria-label="Tentar enviar de novo" onClick={() => retryUpload(u.id)} sx={{ bgcolor: 'background.paper' }}>
                    <RotateCw size={16} aria-hidden />
                  </IconButton>
                  <IconButton size="small" aria-label="Descartar foto" onClick={() => dismissUpload(u.id)} sx={{ bgcolor: 'background.paper' }}>
                    <X size={16} aria-hidden />
                  </IconButton>
                </Stack>
              ) : (
                <CircularProgress size={20} aria-hidden />
              )}
            </Stack>
          </Box>
        ))}

        {onAdd ? (
          <Box role="listitem">
            <ButtonBase component="label" sx={{ ...tile, border: 1, borderStyle: 'dashed', borderColor: 'var(--color-border-strong)', bgcolor: 'transparent', flexDirection: 'column', gap: 0.25, color: 'text.secondary' }}>
              <Camera size={20} aria-hidden />
              <Typography component="span" sx={{ fontSize: 12 }}>
                Foto
              </Typography>
              <input
                type="file"
                accept="image/*"
                multiple
                hidden
                aria-label="Adicionar foto"
                onChange={(e) => {
                  const files = Array.from(e.target.files ?? [])
                  e.target.value = ''
                  if (files.length) onAdd(files)
                }}
              />
            </ButtonBase>
          </Box>
        ) : null}
      </Stack>

      <Dialog open={open !== null} onClose={() => setOpen(null)} maxWidth="md" fullWidth slotProps={{ paper: { sx: { p: 0, bgcolor: 'common.black' } } }}>
        {open ? (
          <Box sx={{ position: 'relative' }}>
            <Box component="img" src={open.url} alt="Foto do item" sx={{ display: 'block', width: '100%', maxHeight: '85vh', objectFit: 'contain' }} />
            <Stack direction="row" sx={{ position: 'absolute', top: 8, right: 8, gap: 1 }}>
              {open.removable && onRemove ? (
                <IconButton
                  aria-label="Remover foto"
                  onClick={() => {
                    onRemove(open.key)
                    setOpen(null)
                  }}
                  sx={{ bgcolor: 'background.paper', color: 'error.main', '&:hover': { bgcolor: 'background.paper' } }}
                >
                  <Trash2 size={18} aria-hidden />
                </IconButton>
              ) : null}
              <IconButton aria-label="Fechar" onClick={() => setOpen(null)} sx={{ bgcolor: 'background.paper', '&:hover': { bgcolor: 'background.paper' } }}>
                <X size={18} aria-hidden />
              </IconButton>
            </Stack>
          </Box>
        ) : null}
      </Dialog>
    </>
  )
}

import Box from '@mui/material/Box'
import Stack from '@mui/material/Stack'
import { Eraser } from 'lucide-react'
import { useEffect, useImperativeHandle, useRef, useState, type Ref } from 'react'
import { Button } from '../../../shared/ui/Button'

export type SignaturePadHandle = { toPng: () => Promise<Blob | null>; isEmpty: () => boolean }

/**
 * Assinatura com o dedo (ou mouse) num canvas. Exporta PNG com fundo branco e traço escuro,
 * independente do tema, porque é o documento que fica guardado.
 */
export function SignaturePad({ ref, onChange }: { ref?: Ref<SignaturePadHandle>; onChange?: (empty: boolean) => void }) {
  const canvasRef = useRef<HTMLCanvasElement>(null)
  const drawing = useRef(false)
  const [empty, setEmpty] = useState(true)

  // Ajusta a resolução ao tamanho real (nitidez em tela retina).
  useEffect(() => {
    const canvas = canvasRef.current
    if (!canvas) return
    const resize = () => {
      const ratio = window.devicePixelRatio || 1
      const { width, height } = canvas.getBoundingClientRect()
      canvas.width = Math.round(width * ratio)
      canvas.height = Math.round(height * ratio)
      const ctx = canvas.getContext('2d')
      if (!ctx) return
      ctx.scale(ratio, ratio)
      ctx.fillStyle = '#ffffff'
      ctx.fillRect(0, 0, width, height)
      ctx.lineWidth = 2.5
      ctx.lineCap = 'round'
      ctx.lineJoin = 'round'
      ctx.strokeStyle = '#1b1f1d'
      setEmpty(true)
      onChange?.(true)
    }
    resize()
    window.addEventListener('resize', resize)
    return () => window.removeEventListener('resize', resize)
  }, [onChange])

  const point = (e: React.PointerEvent<HTMLCanvasElement>) => {
    const rect = e.currentTarget.getBoundingClientRect()
    return { x: e.clientX - rect.left, y: e.clientY - rect.top }
  }

  const start = (e: React.PointerEvent<HTMLCanvasElement>) => {
    const ctx = e.currentTarget.getContext('2d')
    if (!ctx) return
    e.currentTarget.setPointerCapture(e.pointerId)
    drawing.current = true
    const { x, y } = point(e)
    ctx.beginPath()
    ctx.moveTo(x, y)
    ctx.lineTo(x + 0.1, y + 0.1)
    ctx.stroke()
  }

  const move = (e: React.PointerEvent<HTMLCanvasElement>) => {
    if (!drawing.current) return
    const ctx = e.currentTarget.getContext('2d')
    if (!ctx) return
    const { x, y } = point(e)
    ctx.lineTo(x, y)
    ctx.stroke()
    if (empty) {
      setEmpty(false)
      onChange?.(false)
    }
  }

  const end = () => {
    drawing.current = false
  }

  const clear = () => {
    const canvas = canvasRef.current
    const ctx = canvas?.getContext('2d')
    if (!canvas || !ctx) return
    const { width, height } = canvas.getBoundingClientRect()
    ctx.fillStyle = '#ffffff'
    ctx.fillRect(0, 0, width, height)
    setEmpty(true)
    onChange?.(true)
  }

  useImperativeHandle(ref, () => ({
    isEmpty: () => empty,
    toPng: () => new Promise((resolve) => (canvasRef.current ? canvasRef.current.toBlob((b) => resolve(b), 'image/png') : resolve(null))),
  }))

  return (
    <div>
      <Box sx={{ position: 'relative' }}>
        <Box
          component="canvas"
          ref={canvasRef}
          aria-label="Área de assinatura. Assine com o dedo."
          role="img"
          sx={{ display: 'block', height: 176, width: '100%', touchAction: 'none', borderRadius: 1, border: '2px dashed var(--color-border-strong)', bgcolor: '#ffffff' }}
          onPointerDown={start}
          onPointerMove={move}
          onPointerUp={end}
          onPointerCancel={end}
          onPointerLeave={end}
        />
        {/* Cores fixas: o quadro é sempre branco, em qualquer tema. */}
        {empty ? (
          <Box component="span" sx={{ pointerEvents: 'none', position: 'absolute', insetInline: 0, bottom: 40, textAlign: 'center', fontSize: 14, color: '#565c59' }}>
            Assine aqui com o dedo
          </Box>
        ) : null}
        <Box component="span" aria-hidden sx={{ pointerEvents: 'none', position: 'absolute', insetInline: 24, bottom: 32, borderBottom: '1px solid #d4d1c8' }} />
      </Box>
      <Stack direction="row" sx={{ mt: 1, justifyContent: 'flex-end' }}>
        <Button variant="ghost" icon={<Eraser size={16} aria-hidden />} onClick={clear} disabled={empty}>
          Limpar assinatura
        </Button>
      </Stack>
    </div>
  )
}

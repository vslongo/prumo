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
      <div className="relative">
        <canvas
          ref={canvasRef}
          aria-label="Área de assinatura. Assine com o dedo."
          role="img"
          className="h-44 w-full touch-none rounded-md border-2 border-dashed border-border-strong bg-white"
          onPointerDown={start}
          onPointerMove={move}
          onPointerUp={end}
          onPointerCancel={end}
          onPointerLeave={end}
        />
        {empty ? <span className="pointer-events-none absolute inset-x-0 bottom-10 text-center text-sm text-[#565c59]">Assine aqui com o dedo</span> : null}
        <span className="pointer-events-none absolute inset-x-6 bottom-8 border-b border-[#d4d1c8]" aria-hidden />
      </div>
      <div className="mt-2 flex justify-end">
        <Button variant="ghost" icon={<Eraser size={16} aria-hidden />} onClick={clear} disabled={empty}>
          Limpar assinatura
        </Button>
      </div>
    </div>
  )
}

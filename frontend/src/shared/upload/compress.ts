/** Foto do celular (3–8 MB) → ~1600 px, ~300 KB, WebP (JPEG onde o navegador não gera WebP). */

const MAX_SIDE = 1600
const TARGET_BYTES = 300 * 1024
export const MAX_BYTES = 2 * 1024 * 1024

function toBlob(canvas: HTMLCanvasElement, type: string, quality: number): Promise<Blob | null> {
  return new Promise((resolve) => canvas.toBlob(resolve, type, quality))
}

export async function compressImage(file: Blob): Promise<Blob> {
  const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' }).catch(() => null)
  if (!bitmap) throw new Error('Não deu para ler a imagem. Tente outra foto.')

  const scale = Math.min(1, MAX_SIDE / Math.max(bitmap.width, bitmap.height))
  const canvas = document.createElement('canvas')
  canvas.width = Math.round(bitmap.width * scale)
  canvas.height = Math.round(bitmap.height * scale)
  canvas.getContext('2d')?.drawImage(bitmap, 0, 0, canvas.width, canvas.height)
  bitmap.close()

  let blob: Blob | null = null
  for (const quality of [0.8, 0.7, 0.6, 0.5]) {
    blob = await toBlob(canvas, 'image/webp', quality)
    // Safari antigo ignora WebP e devolve PNG: cai para JPEG.
    if (blob && blob.type !== 'image/webp') blob = await toBlob(canvas, 'image/jpeg', quality)
    if (blob && blob.size <= TARGET_BYTES) break
  }

  if (!blob || blob.size > MAX_BYTES) throw new Error('Foto grande demais mesmo depois de comprimir.')
  return blob
}

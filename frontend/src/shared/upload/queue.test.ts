import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { compressImage } from './compress'
import { dismissUpload, enqueueUpload, getUploads, retryUpload, type UploadTarget } from './queue'

vi.mock('./compress', () => ({ compressImage: vi.fn(async () => new Blob(['small'], { type: 'image/webp' })) }))

const ticket = { media_id: 'm1', url: 'https://r2.test/put', headers: { Host: 'r2.test', 'x-amz-acl': 'private' } }

function target(overrides: Partial<UploadTarget> = {}) {
  return { request: vi.fn(async () => ticket), confirm: vi.fn(async () => undefined), onDone: vi.fn(async () => undefined), ...overrides }
}

const flush = () => vi.advanceTimersByTimeAsync(0)

beforeEach(() => {
  vi.useFakeTimers()
  vi.mocked(compressImage).mockClear()
  URL.createObjectURL = vi.fn(() => 'blob:preview')
  URL.revokeObjectURL = vi.fn()
  vi.stubGlobal('fetch', vi.fn(async () => new Response(null, { status: 200 })))
})

afterEach(() => {
  getUploads().forEach((u) => dismissUpload(u.id))
  vi.useRealTimers()
  vi.unstubAllGlobals()
})

describe('fila de upload de fotos', () => {
  it('comprime, sobe direto para o storage (sem Host) e confirma; a prévia some no fim', async () => {
    const t = target()
    enqueueUpload('item-1', new File(['x'.repeat(5000)], 'foto.jpg', { type: 'image/jpeg' }), t)
    expect(getUploads()).toMatchObject([{ group: 'item-1', previewUrl: 'blob:preview' }])

    await flush()

    expect(compressImage).toHaveBeenCalledOnce()
    expect(t.request).toHaveBeenCalledWith('image/webp', 5)
    const [url, init] = vi.mocked(fetch).mock.calls[0]
    expect(url).toBe(ticket.url)
    expect(init).toMatchObject({ method: 'PUT', headers: { 'Content-Type': 'image/webp', 'x-amz-acl': 'private' } })
    expect(t.confirm).toHaveBeenCalledWith('m1')
    expect(t.onDone).toHaveBeenCalledOnce()
    expect(getUploads()).toEqual([])
  })

  it('rede caiu: tenta de novo com URL nova, sem comprimir de novo', async () => {
    vi.mocked(fetch).mockResolvedValueOnce(new Response(null, { status: 503 }))
    const t = target()
    enqueueUpload('item-1', new Blob(['x']), t)
    await flush()
    expect(t.confirm).not.toHaveBeenCalled()
    expect(getUploads()[0].status).toBe('uploading')

    await vi.advanceTimersByTimeAsync(2_000)

    expect(t.request).toHaveBeenCalledTimes(2)
    expect(t.confirm).toHaveBeenCalledOnce()
    expect(compressImage).toHaveBeenCalledOnce()
    expect(getUploads()).toEqual([])
  })

  it('erro 4xx falha na hora, mantém a prévia e envia ao tentar de novo', async () => {
    const t = target({ confirm: vi.fn().mockRejectedValueOnce(Object.assign(new Error('Arquivo inválido.'), { status: 422 })).mockResolvedValue(undefined) })
    enqueueUpload('item-2', new Blob(['x']), t)
    await flush()

    expect(getUploads()).toMatchObject([{ status: 'failed', error: 'Arquivo inválido.' }])
    expect(t.onDone).not.toHaveBeenCalled()

    retryUpload(getUploads()[0].id)
    await flush()

    expect(t.onDone).toHaveBeenCalledOnce()
    expect(getUploads()).toEqual([])
  })

  it('envia uma foto por vez', async () => {
    let release: () => void = () => undefined
    const slow = target({ confirm: vi.fn(() => new Promise<void>((r) => (release = r))) })
    const fast = target()
    enqueueUpload('a', new Blob(['x']), slow)
    enqueueUpload('b', new Blob(['y']), fast)
    await flush()

    expect(fast.request).not.toHaveBeenCalled()
    release()
    await flush()
    expect(fast.confirm).toHaveBeenCalledOnce()
  })
})

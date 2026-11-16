import { useSyncExternalStore } from 'react'
import { compressImage } from './compress'

/**
 * Fila local de fotos: comprime, pede URL pré-assinada, sobe direto para o storage e confirma, em
 * segundo plano e uma por vez (conexão ruim). Nada na tela espera por ela.
 * ponytail: fila em memória; recarregar a página perde o que não subiu (avisamos no beforeunload).
 * Se virar problema, persistir os blobs no IndexedDB.
 */

export type UploadTicket = { media_id: string; url: string; headers: Record<string, string | string[]> }

export type UploadTarget = {
  request: (mime: string, size: number) => Promise<UploadTicket>
  confirm: (mediaId: string) => Promise<void>
  /** Chamado depois de confirmar; a prévia some só quando ele termina (ex.: refetch da lista). */
  onDone?: () => Promise<unknown> | void
}

export type UploadTask = {
  id: string
  /** Agrupa na tela (ex.: id do item). */
  group: string
  previewUrl: string
  status: 'queued' | 'uploading' | 'failed'
  error?: string
}

type Job = UploadTask & { file: Blob; compressed?: boolean; target: UploadTarget }

const RETRY_DELAYS = [2_000, 5_000, 15_000]

let jobs: Job[] = []
let snapshot: UploadTask[] = []
let running = false
const listeners = new Set<() => void>()

function emit() {
  snapshot = jobs.map(({ id, group, previewUrl, status, error }) => ({ id, group, previewUrl, status, error }))
  listeners.forEach((l) => l())
}

function update(id: string, patch: Partial<Job>) {
  jobs = jobs.map((j) => (j.id === id ? { ...j, ...patch } : j))
  emit()
}

function drop(id: string) {
  const job = jobs.find((j) => j.id === id)
  if (job) URL.revokeObjectURL(job.previewUrl)
  jobs = jobs.filter((j) => j.id !== id)
  emit()
}

/** Erro de rede ou do servidor vale nova tentativa; 4xx (arquivo inválido, sem permissão) não. */
function retryable(e: unknown): boolean {
  const status = (e as { status?: number })?.status
  return status === undefined || status === 0 || status === 408 || status === 429 || status >= 500
}

const sleep = (ms: number) => new Promise((r) => setTimeout(r, ms))

async function put(ticket: UploadTicket, blob: Blob): Promise<void> {
  const headers: Record<string, string> = { 'Content-Type': blob.type }
  for (const [k, v] of Object.entries(ticket.headers ?? {})) {
    if (k.toLowerCase() !== 'host') headers[k] = Array.isArray(v) ? v.join(',') : v
  }
  const res = await fetch(ticket.url, { method: 'PUT', headers, body: blob }).catch(() => null)
  if (!res || !res.ok) throw Object.assign(new Error('Falha ao enviar a foto.'), { status: res?.status ?? 0 })
}

async function process(job: Job): Promise<void> {
  update(job.id, { status: 'uploading', error: undefined })
  try {
    const blob = job.compressed ? job.file : await compressImage(job.file)
    // Guarda o comprimido: nova tentativa não comprime de novo.
    jobs = jobs.map((j) => (j.id === job.id ? { ...j, file: blob, compressed: true } : j))

    for (let attempt = 0; ; attempt++) {
      try {
        // URL nova a cada tentativa: a pré-assinada vence em minutos.
        const ticket = await job.target.request(blob.type, blob.size)
        await put(ticket, blob)
        await job.target.confirm(ticket.media_id)
        break
      } catch (e) {
        if (!retryable(e) || attempt >= RETRY_DELAYS.length) throw e
        await sleep(RETRY_DELAYS[attempt])
      }
    }

    await job.target.onDone?.()
    drop(job.id)
  } catch (e) {
    update(job.id, { status: 'failed', error: e instanceof Error && e.message ? e.message : 'Não deu para enviar a foto.' })
  }
}

async function run() {
  if (running) return
  running = true
  try {
    for (let next = jobs.find((j) => j.status === 'queued'); next; next = jobs.find((j) => j.status === 'queued')) {
      await process(next)
    }
  } finally {
    running = false
  }
}

export function enqueueUpload(group: string, file: Blob, target: UploadTarget): void {
  jobs = [...jobs, { id: crypto.randomUUID(), group, previewUrl: URL.createObjectURL(file), status: 'queued', file, target }]
  emit()
  void run()
}

export function retryUpload(id: string): void {
  update(id, { status: 'queued', error: undefined })
  void run()
}

export const dismissUpload = drop

const subscribe = (l: () => void) => {
  listeners.add(l)
  return () => listeners.delete(l)
}

export const getUploads = (): UploadTask[] => snapshot

export function useUploads(): UploadTask[] {
  return useSyncExternalStore(subscribe, getUploads)
}

if (typeof window !== 'undefined') {
  window.addEventListener('beforeunload', (e) => {
    if (jobs.some((j) => j.status !== 'failed')) e.preventDefault()
  })
}

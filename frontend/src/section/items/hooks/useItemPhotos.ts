import { useMutation, useQuery, useQueryClient, type QueryClient } from '@tanstack/react-query'
import { enqueueUpload, type UploadTicket } from '../../../shared/upload/queue'
import type { ItemPhoto } from '../types'

export type PhotoApi = {
  list: () => Promise<ItemPhoto[]>
  request: (itemId: string, mime: string, size: number) => Promise<UploadTicket>
  confirm: (itemId: string, mediaId: string) => Promise<unknown>
  remove: (itemId: string, mediaId: string) => Promise<unknown>
}

/** Põe as fotos de um item na fila de envio; ao terminar cada uma, recarrega a lista `key`. */
export function enqueueItemPhotos(queryClient: QueryClient, key: readonly unknown[], api: Pick<PhotoApi, 'request' | 'confirm'>, itemId: string, files: Iterable<Blob>) {
  for (const file of files) {
    enqueueUpload(itemId, file, {
      request: (mime, size) => api.request(itemId, mime, size),
      confirm: async (mediaId) => void (await api.confirm(itemId, mediaId)),
      onDone: () => queryClient.invalidateQueries({ queryKey: key }),
    })
  }
}

/** Fotos por item de um orçamento/OS. Envio vai para a fila em segundo plano; remover é otimista. */
export function useItemPhotos(key: readonly unknown[], api: PhotoApi) {
  const queryClient = useQueryClient()
  const photos = useQuery({
    queryKey: key,
    queryFn: api.list,
    // URLs assinadas valem 30 min no backend: renova antes.
    staleTime: 10 * 60_000,
    refetchInterval: 20 * 60_000,
  })

  const add = (itemId: string, files: Iterable<File>) => enqueueItemPhotos(queryClient, key, api, itemId, files)

  const remove = useMutation({
    mutationFn: (photo: ItemPhoto) => api.remove(photo.item_id, photo.id),
    onMutate: (photo) => queryClient.setQueryData<ItemPhoto[]>(key, (old) => old?.filter((p) => p.id !== photo.id)),
    onSettled: () => queryClient.invalidateQueries({ queryKey: key }),
  })

  const byItem = (itemId: string) => (photos.data ?? []).filter((p) => p.item_id === itemId)

  return { byItem, add, remove }
}

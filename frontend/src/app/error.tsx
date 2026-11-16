'use client'
import { useEffect } from 'react'
import * as Sentry from '@sentry/react'
export default function ErrorPage({ error, reset }: { error: Error; reset: () => void }) {
  useEffect(() => { Sentry.captureException(error) }, [error])
  return <main><h1>Não deu para abrir esta página.</h1><button onClick={reset}>Tentar novamente</button></main>
}

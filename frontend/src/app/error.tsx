'use client'
export default function ErrorPage({ reset }: { reset: () => void }) { return <main><h1>Não deu para abrir esta página.</h1><button onClick={reset}>Tentar novamente</button></main> }

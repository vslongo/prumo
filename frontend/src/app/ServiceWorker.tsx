'use client'
import { useEffect } from 'react'
export function ServiceWorker() {
  useEffect(() => {
    if (!('serviceWorker' in navigator)) return
    if (process.env.NODE_ENV === 'production') {
      void navigator.serviceWorker.register('/sw.js', { updateViaCache: 'none' }).catch(console.error)
    } else {
      // Old production workers must not intercept Next's development modules.
      void navigator.serviceWorker.getRegistrations().then(registrations => Promise.all(registrations.filter(registration => registration.scope === `${location.origin}/`).map(registration => registration.unregister())))
    }
  }, [])
  return null
}

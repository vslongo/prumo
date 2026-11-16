'use client'
import { useSyncExternalStore } from 'react'

export type Theme = 'light' | 'dark'
const themeEvent = 'prumo-theme-change'
function readTheme(): Theme { return document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light' }
function subscribe(listener: () => void) {
  window.addEventListener(themeEvent, listener)
  return () => window.removeEventListener(themeEvent, listener)
}
const serverTheme = (): Theme => 'light'

/** The root layout applies the saved theme before paint; the server snapshot is stable. */
export function useTheme(): [Theme, () => void] {
  const theme = useSyncExternalStore(subscribe, readTheme, serverTheme)
  const toggle = () => {
    const next: Theme = readTheme() === 'dark' ? 'light' : 'dark'
    document.documentElement.dataset.theme = next
    try { localStorage.setItem('prumo-theme', next) } catch { /* Private browsing: keep the theme for this tab. */ }
    window.dispatchEvent(new Event(themeEvent))
  }
  return [theme, toggle]
}

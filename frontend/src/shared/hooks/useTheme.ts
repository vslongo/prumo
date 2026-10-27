import { useState } from 'react'

export type Theme = 'light' | 'dark'

/** Tema salvo por aparelho. O valor inicial é aplicado no index.html antes do primeiro paint. */
export function useTheme(): [Theme, () => void] {
  const [theme, setTheme] = useState<Theme>(() => (document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light'))

  const toggle = () => {
    const next: Theme = theme === 'dark' ? 'light' : 'dark'
    document.documentElement.dataset.theme = next
    try {
      localStorage.setItem('prumo-theme', next)
    } catch {
      // modo privado: o tema vale só nesta aba
    }
    setTheme(next)
  }

  return [theme, toggle]
}

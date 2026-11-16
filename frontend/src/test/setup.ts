import '@testing-library/jest-dom/vitest'
import { vi } from 'vitest'

// Existing form tests use an in-memory history; expose that history through the
// Next APIs so the real application navigation adapter is exercised as well.
vi.mock('next/navigation', async () => {
  const router = await import('react-router')
  const { useMemo } = await import('react')
  return {
    useRouter: () => {
      const navigate = router.useNavigate()
      return useMemo(() => ({ push: (url: string) => navigate(url), replace: (url: string) => navigate(url, { replace: true }) }), [navigate])
    },
    usePathname: () => router.useLocation().pathname,
    useParams: () => router.useParams(),
    useSearchParams: () => router.useSearchParams()[0],
  }
})
vi.mock('next/link', async () => {
  const { Link } = await import('react-router')
  const { createElement } = await import('react')
  return { default: ({ href, ...props }: { href: string }) => createElement(Link, { ...props, to: href }) }
})

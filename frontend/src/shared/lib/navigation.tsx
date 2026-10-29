'use client'

import NextLink from 'next/link'
import { useParams as useNextParams, usePathname, useRouter, useSearchParams as useNextSearchParams } from 'next/navigation'
import { useCallback, useEffect, type ComponentProps, type Ref } from 'react'

export type LinkProps = Omit<ComponentProps<typeof NextLink>, 'href'> & { to: string; ref?: Ref<HTMLAnchorElement> }
export function Link({ to, ...props }: LinkProps) {
  return <NextLink href={to} {...props} />
}
export function NavLink({ to, end, className, ...props }: LinkProps & { end?: boolean }) {
  const pathname = usePathname()
  const active = end ? pathname === to : pathname === to || pathname.startsWith(`${to}/`)
  return <NextLink href={to} aria-current={active ? 'page' : undefined} className={[className, active && 'active'].filter(Boolean).join(' ')} {...props} />
}
export function useNavigate() {
  const router = useRouter()
  return useCallback((to: string, options?: { replace?: boolean }) => {
    if (options?.replace) router.replace(to)
    else router.push(to)
  }, [router])
}
export function Navigate({ to, replace }: { to: string; replace?: boolean }) {
  const navigate = useNavigate()
  useEffect(() => { navigate(to, { replace }) }, [navigate, to, replace])
  return null
}
export function useLocation() { return { pathname: usePathname() } }
export function useParams<T extends Record<string, string> = Record<string, string>>() { return useNextParams<T>() }
export function useSearchParams() {
  const params = useNextSearchParams()
  const pathname = usePathname()
  const router = useRouter()
  const setParams = useCallback((values: Record<string, string>, options?: { replace?: boolean }) => {
    const query = new URLSearchParams(values).toString()
    const url = query ? `${pathname}?${query}` : pathname
    if (options?.replace) router.replace(url)
    else router.push(url)
  }, [pathname, router])
  return [params, setParams] as const
}

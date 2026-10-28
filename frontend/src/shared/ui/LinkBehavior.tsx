import type { Ref } from 'react'
import { Link, type LinkProps } from 'react-router'

/** Faz `href` de Button/Link/ListItemButton do MUI navegar pelo react-router (sem recarregar a página). Ligado em app/theme. */
export function LinkBehavior({ href, ref, ...rest }: Omit<LinkProps, 'to'> & { href: string; ref?: Ref<HTMLAnchorElement> }) {
  return <Link ref={ref} to={href} {...rest} />
}

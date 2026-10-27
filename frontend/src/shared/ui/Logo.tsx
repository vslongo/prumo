/**
 * Logo Prumo (docs/brand/logo.svg). O nome usa currentColor para funcionar no tema escuro;
 * o símbolo mantém as cores fixas da marca.
 */
export function LogoMark({ size = 32 }: { size?: number }) {
  return (
    <svg viewBox="0 0 32 32" width={size} height={size} aria-hidden>
      <rect width="32" height="32" rx="7" fill="#0e5a4f" />
      <rect x="14" y="4.5" width="4" height="5.5" fill="#ffffff" />
      <rect x="10.5" y="9.5" width="11" height="3.5" rx="1" fill="#ffffff" />
      <path d="M10 14.5H22L16 27.5Z" fill="#ffffff" />
      <path d="M13.69 22.5H18.31L16 27.5Z" fill="#e8690c" />
    </svg>
  )
}

export function Logo({ height = 32, className = '' }: { height?: number; className?: string }) {
  return (
    <svg viewBox="0 0 150 40" height={height} width={(height * 150) / 40} role="img" aria-label="Prumo" className={className}>
      <g transform="translate(0 4)">
        <rect width="32" height="32" rx="7" fill="#0e5a4f" />
        <rect x="14" y="4.5" width="4" height="5.5" fill="#ffffff" />
        <rect x="10.5" y="9.5" width="11" height="3.5" rx="1" fill="#ffffff" />
        <path d="M10 14.5H22L16 27.5Z" fill="#ffffff" />
        <path d="M13.69 22.5H18.31L16 27.5Z" fill="#e8690c" />
      </g>
      <g fill="none" stroke="currentColor" strokeWidth="4.5" strokeLinejoin="round" transform="translate(-10 0)">
        <path d="M54.25 13V39" />
        <circle cx="61" cy="22" r="6.75" />
        <path d="M76.25 13V31M76.25 22C76.25 17.5 79.5 15.25 84.5 15.25" />
        {/* "umo" 3 unidades mais perto do "r" para o espaçamento ficar uniforme */}
        <g transform="translate(-3 0)">
          <path d="M93.25 13V22A6.75 6.75 0 0 0 106.75 22M106.75 13V31" />
          <path d="M115.25 13V31M115.25 20A4.5 4.5 0 0 1 124.25 20V31M124.25 20A4.5 4.5 0 0 1 133.25 20V31" />
          <circle cx="148.5" cy="22" r="6.75" />
        </g>
      </g>
    </svg>
  )
}

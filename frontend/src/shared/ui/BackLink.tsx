import { ChevronLeft } from 'lucide-react'
import { Link } from 'react-router'

export function BackLink({ to, label }: { to: string; label: string }) {
  return (
    <Link to={to} className="mb-2 -ml-1 inline-flex h-9 items-center gap-1 text-sm font-medium text-muted hover:text-text">
      <ChevronLeft size={18} aria-hidden />
      {label}
    </Link>
  )
}

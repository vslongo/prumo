type Option<T extends string> = { value: T | undefined; label: string }

/** Chips roláveis de filtro por status (docs/brand: acima da lista). */
export function StatusFilter<T extends string>({ options, value, onChange }: { options: Option<T>[]; value: T | undefined; onChange: (v: T | undefined) => void }) {
  return (
    <div role="radiogroup" aria-label="Filtrar por status" className="-mx-4 mb-4 flex gap-2 overflow-x-auto px-4 pb-1 lg:mx-0 lg:px-0">
      {options.map((o) => {
        const active = o.value === value
        return (
          <button
            key={o.label}
            type="button"
            role="radio"
            aria-checked={active}
            onClick={() => onChange(o.value)}
            className={`h-10 shrink-0 rounded-sm border px-3.5 text-sm font-semibold transition-colors ${
              active ? 'border-brand bg-brand text-on-brand' : 'border-border-strong bg-surface text-text hover:bg-surface-2'
            }`}
          >
            {o.label}
          </button>
        )
      })}
    </div>
  )
}

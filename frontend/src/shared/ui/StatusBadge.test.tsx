import { render, screen } from '@testing-library/react'
import { QuoteStatusBadge, ServiceOrderStatusBadge } from './StatusBadge'

describe('StatusBadge', () => {
  it('mostra o rótulo da marca e usa os tokens do status (cor + texto, nunca só cor)', () => {
    render(
      <>
        <ServiceOrderStatusBadge status="in_progress" />
        <QuoteStatusBadge status="viewed" />
      </>,
    )
    expect(screen.getByText('Em execução')).toHaveStyle({ color: 'var(--status-os-in_progress-fg)' })
    expect(screen.getByText('Visualizado').getAttribute('style')).toContain('var(--status-quote-viewed-bg)')
  })
})

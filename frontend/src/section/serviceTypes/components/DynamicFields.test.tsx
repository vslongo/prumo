import { render, screen } from '@testing-library/react'
import { useForm } from 'react-hook-form'
import type { FieldSchema } from '../api'
import { DynamicFields } from './DynamicFields'
import { buildFieldsSchema, cleanFields } from './fieldsSchema'

const oil: FieldSchema[] = [
  { key: 'oil_brand', label: 'Marca do óleo', type: 'text', required: true },
  { key: 'viscosity', label: 'Viscosidade', type: 'select', required: true, options: ['5W30', '10W40'] },
  { key: 'liters', label: 'Litros', type: 'number', required: true, unit: 'L' },
  { key: 'filter_changed', label: 'Trocou filtro', type: 'boolean', required: false },
  { key: 'next_on', label: 'Próxima troca', type: 'date', required: false },
]

describe('buildFieldsSchema', () => {
  const schema = buildFieldsSchema(oil)

  it('aceita valores válidos', () => {
    expect(schema.safeParse({ oil_brand: 'Mobil', viscosity: '5W30', liters: 4, filter_changed: true }).success).toBe(true)
  })

  it('cobra obrigatórios mesmo quando o campo nunca foi tocado', () => {
    const result = schema.safeParse({})
    expect(result.success).toBe(false)
    const messages = result.error!.issues.map((i) => i.message)
    expect(messages).toContain('Informe marca do óleo.')
    expect(messages).toContain('Escolha viscosidade.')
    expect(messages).toContain('Informe litros.')
  })

  it('recusa opção fora da lista, número inválido e data malformada', () => {
    const result = schema.safeParse({ oil_brand: 'Mobil', viscosity: '0W99', liters: Number.NaN, next_on: '31/12/2026' })
    expect(result.success).toBe(false)
    expect(result.error!.issues.map((i) => i.path.join('.'))).toEqual(expect.arrayContaining(['viscosity', 'liters', 'next_on']))
  })

  it('limpa vazios antes de enviar', () => {
    expect(cleanFields({ oil_brand: 'Mobil', liters: Number.NaN, next_on: '', filter_changed: false })).toEqual({ oil_brand: 'Mobil', filter_changed: false })
  })
})

function Harness({ schema }: { schema: FieldSchema[] }) {
  const { register } = useForm()
  return <DynamicFields schema={schema} register={register} />
}

describe('DynamicFields', () => {
  it('gera um campo para cada item do schema, com rótulo e unidade', () => {
    render(<Harness schema={oil} />)
    expect(screen.getByLabelText('Marca do óleo').tagName).toBe('INPUT')
    expect(screen.getByLabelText('Litros (L)')).toHaveAttribute('inputmode', 'decimal')
    expect(screen.getByLabelText('Trocou filtro')).toHaveAttribute('type', 'checkbox')
    expect(screen.getByLabelText('Próxima troca')).toHaveAttribute('type', 'date')
    expect(screen.getByRole('option', { name: '10W40' })).toBeInTheDocument()
  })

  it('não renderiza nada sem campos', () => {
    const { container } = render(<Harness schema={[]} />)
    expect(container).toBeEmptyDOMElement()
  })
})

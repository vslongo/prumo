import { z } from 'zod'
import type { FieldSchema } from '../api'

export const emptyToUndefined = (v: unknown) => (v === '' || v === null || (typeof v === 'number' && Number.isNaN(v)) ? undefined : v)

/** Zod a partir do fields_schema do tipo de serviço (o backend valida de novo com o mesmo schema). */
export function buildFieldsSchema(schema: FieldSchema[]) {
  const shape: Record<string, z.ZodType> = {}

  for (const f of schema) {
    const required = `${f.type === 'select' ? 'Escolha' : 'Informe'} ${f.label.toLowerCase()}.`
    let base: z.ZodType
    switch (f.type) {
      case 'number':
        base = z.number({ error: `${f.label} deve ser um número.` })
        break
      case 'boolean':
        base = z.boolean()
        break
      case 'select':
        base = z.enum((f.options ?? []) as [string, ...string[]], { error: `Escolha ${f.label.toLowerCase()}.` })
        break
      case 'date':
        base = z.string().regex(/^\d{4}-\d{2}-\d{2}$/, 'Data inválida.')
        break
      default:
        base = z.string().max(500, 'Máximo de 500 caracteres.')
    }
    shape[f.key] =
      f.required && f.type !== 'boolean'
        ? z.preprocess(emptyToUndefined, base.optional()).refine((v) => v !== undefined, required)
        : z.preprocess(emptyToUndefined, base.optional())
  }

  // Zod 4 não roda validação de chave ausente que aceita undefined: garante todas as chaves antes.
  const blank = Object.fromEntries(schema.map((f) => [f.key, null]))
  return z.preprocess((v) => ({ ...blank, ...(v && typeof v === 'object' ? v : {}) }), z.object(shape))
}

/** Limpa valores vazios antes de mandar para a API (o backend recusa chave desconhecida, não vazia). */
export function cleanFields(values: Record<string, unknown> | undefined): Record<string, unknown> {
  return Object.fromEntries(Object.entries(values ?? {}).filter(([, v]) => emptyToUndefined(v) !== undefined))
}

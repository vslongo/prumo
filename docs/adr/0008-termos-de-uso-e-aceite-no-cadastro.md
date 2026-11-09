# 0008 — Termos de uso, política de privacidade e aceite no cadastro

- Status: proposto (o texto depende de revisão de advogado e de confirmação do dono do produto)
- Data: 2026-11-09

## Contexto
O Prumo é operador dos dados pessoais que a oficina cadastra (CLAUDE.md §10) e cobra assinatura. Não havia termos de uso, política de privacidade nem registro de aceite.

## Decisão
- **Páginas públicas** `/termos` e `/privacidade` no app (`frontend/src/features/legal`). Os dados da empresa ficam em `features/legal/company.ts`.
- **Aceite obrigatório no cadastro da oficina:** `POST /api/signup` exige `accept_terms`. O backend grava no usuário dono `terms_accepted_at` e `terms_version`.
- **Versão** = data do texto, em `SignUp::TERMS_VERSION` (backend) e `company.version` (front). Mudou o texto, mudam as duas.
- **Funcionário convidado não aceita termos:** quem contrata é a oficina, pelo dono. Contas antigas ficam com os campos nulos.

### Regras que o texto afirma e que ainda precisam de confirmação
O texto foi escrito para descrever o que o sistema já faz. Estes pontos **não estavam documentados** e entraram como proposta:

| Ponto | Proposta no texto | Situação no sistema |
|---|---|---|
| Aviso de reajuste de preço | 30 dias de antecedência | Não há automação; é compromisso operacional |
| Guarda dos dados após cancelar | Até 90 dias, depois apaga ou anonimiza | **Não implementado**: hoje nada é apagado (ADR 0004 proíbe apagar tenant suspenso) |
| Devolução de período pago | Não há, salvo exigência legal | Coerente com o cancelamento atual (acesso até o fim do período) |
| Limite de responsabilidade | Valor pago nos últimos 12 meses | Só contratual |
| Horário de suporte | Dias úteis | Só operacional |
| Cópia de segurança | Diária | Implementado (`backup:database`, `docs/deploy.md`) |

## Consequências
- **Não publicar em produção** antes de: revisão jurídica do texto, preencher `company.ts` e confirmar a tabela acima.
- Se o prazo de 90 dias for confirmado, falta criar a rotina que apaga ou anonimiza os dados de oficina cancelada (e ajustar o ADR 0004).
- Quando o texto mudar de forma relevante, será preciso pedir novo aceite aos donos. Isso ainda não existe; os campos gravados já permitem saber quem aceitou qual versão.

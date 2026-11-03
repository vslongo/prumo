# 0004 — Assinatura pelo Asaas, somente leitura calculada e limite por usuários

- Status: aceito
- Data: 2026-11-04

## Contexto
O Prumo vende por autoatendimento (Solo R$ 49, Oficina R$ 79, Pro R$ 149) com trial de 14 dias. Faltava decidir:
como a oficina paga, o que acontece quando não paga, o que diferencia os planos e quem mexe nisso.

## Decisão
- **Pagamento:** assinatura mensal no Asaas com `billingType: UNDEFINED`. A oficina paga na página de fatura do Asaas
  com Pix, boleto ou cartão. Dado de cartão nunca passa pelo nosso servidor.
- **CPF/CNPJ** do responsável vai só para o Asaas (cliente de lá), por um job criptografado (`ShouldBeEncrypted`).
  Não fica no nosso banco.
- **Integração só por fila.** Assinar grava `pending` e o job cria cliente + assinatura e busca o link da fatura;
  o front consulta até o link aparecer. Webhook é processado na hora (só banco, sem chamada externa).
- **Primeira cobrança no fim do trial** (assinar cedo não come dias de teste) ou hoje, se o trial já acabou.
- **Somente leitura é calculado na hora** (`ResolveTenantAccess`), não por job agendado. Escreve quem está:
  com assinatura ativa; atrasada há no máximo 7 dias (`past_due_grace_days`); cancelada mas paga até `paid_until`;
  ou no trial. Fora disso, toda escrita das rotas logadas responde **402** (`code: read_only`). Leitura, assinar e
  sair continuam liberados. Links públicos (cliente aprovando orçamento) e webhooks não são bloqueados. Nada é apagado.
- **Trial acabou sem pagamento → somente leitura na hora**, sem carência extra.
- **Planos diferem só no número de usuários ativos:** 1 / 5 / ilimitado (`config/plans.php`). Convites pendentes
  ocupam lugar. No trial não há limite. Descer de plano exige caber os usuários ativos de hoje.
- **Só o dono** (`owner`) assina, troca de plano e cancela (Gate `manage-billing`). Todos veem o aviso de situação.
- **Webhook:** autenticado pelo header `asaas-access-token`; idempotente pelo id do evento (`billing_webhook_events`,
  tabela de sistema sem `tenant_id`); o tenant vem da assinatura achada pelo id do Asaas
  (`Tenancy\ResolveWebhookOwner`), nunca do corpo.
- `subscriptions.status` é a fonte da verdade; `tenants.status` é espelho. `suspended` fica reservado para suspensão
  manual (o atraso além da carência aparece como `past_due` + somente leitura).

## Consequências
- Sem `ASAAS_API_KEY` o job de assinatura falha e a tela fica em "Gerando sua fatura…". Em dev, o seed demo já nasce
  com assinatura Pro paga.
- Preço do plano precisa ser em reais inteiros (o Asaas recebe número e não usamos float). Para preço com centavos,
  mandar string decimal (ver `Plan::priceReais`).
- Limites por volume (OS por mês) entram depois, se precisar, no mesmo `config/plans.php`.

# 0003 — WhatsApp: número único do Prumo + wa.me, com espaço para número próprio

- Status: aceito
- Data: 2026-10-28

## Contexto
O core envia por WhatsApp o link do orçamento e o aviso de OS pronta, só pela Cloud API oficial. Faltava decidir **de qual número** as mensagens saem. Os modelos possíveis eram:
- **A. Número único do Prumo:** só o Prumo tem conta na Meta. As mensagens saem do número oficial do Prumo, com o nome da oficina no texto.
- **B. Número da própria oficina:** cada oficina conecta o número dela pelo Embedded Signup da Meta.
- **C. Só `wa.me`:** o técnico manda pelo WhatsApp dele, com o texto pronto.

O produto vende por autoatendimento, sem call de onboarding. O B exige que cada oficina tenha conta na Meta e configure o número, o que trava o cadastro.

## Decisão
- **Agora: A + C.**
  - O envio automático sai do número do Prumo (`WHATSAPP_PHONE_NUMBER_ID`).
  - O botão `wa.me` continua sempre disponível como plano B manual.
  - Sem número configurado no `.env`, nada é enfileirado e só o `wa.me` funciona. Isso vale para o dev local.
- **Envio automático só com consentimento.** Mensagem iniciada pela empresa exige opt-in do cliente (política da Meta). Por isso o envio automático só acontece se o cliente tem `consent_whatsapp_at`. Sem consentimento, o técnico usa o `wa.me`.
- **Espaço para o B:**
  - Cada `messages` guarda `sender_phone_number_id`, o número de onde saiu.
  - O remetente é decidido num ponto só, `ResolveWhatsappSender::handle($tenantId)`. Hoje ele sempre devolve o número do Prumo. Quando o B existir, ele passa a devolver o número conectado pela oficina, se houver.
  - O worker recebe o remetente em cada mensagem, então não precisa mudar.
- O texto dos templates leva o nome da oficina, porque uma resposta do cliente cai no número do Prumo, não no da oficina. O telefone de contato da oficina entra no texto quando o cadastro da oficina tiver esse campo.

## Consequências
- Só o Prumo cadastra na Meta: Business, app WhatsApp, número e templates `utility` aprovados. O token da Meta fica no `.env` do worker, nunca no repositório.
- O custo por mensagem é do Prumo. Pode virar limite por plano em `config/plans.php` quando o Billing existir.
- Respostas de clientes chegam no número do Prumo. Se virar volume, avaliar uma resposta automática apontando para o contato da oficina, ou o B.
- O B entra como uma tabela de contas conectadas por tenant, mais a lógica do `ResolveWhatsappSender`. Nada do que envia ou registra mensagens precisa mudar.

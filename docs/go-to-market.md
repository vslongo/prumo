# Prumo: dá para vender? Mapa até a primeira venda

Avaliação feita em 2026-11-09, em cima do código da `main`. Preços de concorrente e números de mercado são de busca pública; confirme antes de usar em material de venda.

## 1. Veredito

**Sim, é vendável, como produto de entrada para a oficina que hoje usa papel e WhatsApp pessoal.** Não é (ainda) substituto de um ERP de oficina, e não deve tentar ser antes de ter cliente pagando.

O core está pronto e bem feito: orçamento no celular → link → cliente assina → OS automática, OS direta, fotos, equipe, assinatura com trial, LGPD, 2FA. O que falta para vender **não é feature de oficina**, é o que fica em volta: recuperar senha, termos de uso, produção no ar, WhatsApp e Asaas em produção, uma página de venda e oficinas reais usando.

## 2. Por que vende

| Ponto | Evidência |
|---|---|
| Mercado grande e pouco atendido | Entre 75 mil e 120 mil oficinas no Brasil, conforme a fonte (Sebrae, Sindirepa). Levantamento da RepareCar (2026): 65% das pequenas e médias não usam nenhum sistema. |
| Preço muito abaixo dos ERPs | Prumo R$ 49 / 79 / 149. Ultracar publica R$ 324 e R$ 494 por mês. Oficina Inteligente aparece em comparativos de concorrente na faixa de R$ 399 a R$ 599 (não confirmado no site oficial). |
| Uma dor clara, fácil de mostrar | "O cliente aprova e assina pelo WhatsApp, e você tem a prova." Cabe num vídeo de 60 segundos. Evita a briga do "eu não autorizei esse serviço". |
| Autoatendimento de verdade | Cadastro sem cartão, trial de 14 dias, convite de equipe por link. Dá para vender sem reunião. |
| Feito para o celular | O técnico usa no pátio. Os ERPs antigos são de computador de balcão. |

## 3. Riscos (sem enfeite)

1. **Ainda não foi usado por nenhuma oficina real.** É o maior risco. Tudo abaixo é hipótese até o piloto.
2. **Concorrente barato existe.** O Onmotor tem plano gratuito limitado e planos baixos (R$ 35 a R$ 45 em fontes antigas). O Prumo não ganha em preço nem em quantidade de função; ganha em simplicidade e na aprovação assinada. A mensagem de venda tem que ser essa.
3. **Sem recebimento, impressão e NFS-e**, parte das oficinas vai dizer "falta". Para quem está no papel não trava a venda; para quem já usa sistema, trava. Público inicial = quem não usa nada.
4. **Ticket baixo.** A R$ 79 de média, 100 oficinas = R$ 7.900/mês. Não paga anúncio caro nem vendedor. O canal tem que ser barato (indicação, parceria, conteúdo, visita local).
5. **WhatsApp pelo número do Prumo (ADR 0003)** custa por mensagem e depende de aprovação de template e verificação da empresa na Meta. O botão `wa.me` já cobre enquanto isso não sai, então não bloqueia o piloto.
6. **Plano Solo com 1 usuário** num produto para "2 a 10 pessoas": confirmar no piloto se o Solo converte ou só canibaliza o plano Oficina.

## 4. O que falta no produto para cobrar

Verificado no código. Em ordem de prioridade.

### Bloqueia a venda
Situação em 2026-11-09: o código dos cinco itens está pronto. O que sobra em cada um é configuração ou decisão que depende de você.

- [x] **Recuperar senha** (ADR 0007): telas, rotas e e-mail em fila prontos. **Falta:** contratar o provedor de e-mail e verificar o domínio (`docs/deploy.md`, item 6).
- [x] **Termos de uso e política de privacidade** com aceite no cadastro, data e versão gravadas (ADR 0008). **Falta:** revisão de advogado, preencher `frontend/src/features/legal/company.ts` e confirmar os prazos que o texto promete (tabela no ADR 0008). Não publique antes disso.
- [ ] **Produção no ar**: Horizon instalado, backup diário do banco no R2 e guia em `docs/deploy.md` prontos. **Falta:** contratar o VPS e o Forge, domínio, e seguir o guia.
- [ ] **Asaas em produção**: o código é o mesmo do sandbox. **Falta:** conta aprovada, chave e webhook de produção e um ciclo completo testado (`docs/deploy.md`, item 7).
- [x] **Canal de suporte**: link "Fale com o suporte no WhatsApp" no login e em todas as telas do app. **Falta:** pôr o número em `VITE_SUPPORT_WHATSAPP`; vazio, o link não aparece.

### Atrapalha a venda
- [ ] **Ícones PNG do PWA** (192 e 512 px, e `apple-touch-icon`). Hoje o manifesto só tem SVG, e o "instalar na tela inicial" pode não aparecer no Android nem ficar certo no iPhone.
- [ ] **WhatsApp oficial em produção**: verificação da empresa na Meta, número, templates `quote_link` e `service_order_ready` aprovados, worker no ar.
- [ ] **Página de venda** (landing) com vídeo curto, preço e botão para o cadastro. Hoje `/` da API ainda mostra a página padrão do Laravel.
- [ ] **Imprimir ou gerar PDF** do orçamento e da OS. Não existe. Validar no piloto se a oficina pede papel para entregar ao cliente.

### Pode esperar o primeiro cliente pagar
- Payments (recebimento da oficina), lembretes de troca de óleo, NFS-e, estoque, inspeção. Só entram quando o piloto pedir, um por vez (CLAUDE.md §1).

## 5. Mapa

### Fase 0: pronto para cobrar (1 a 2 semanas)
Fechar a lista "Bloqueia a venda" e os ícones do PWA. Saída: uma pessoa de fora se cadastra, usa, assina o plano e recupera a senha sem falar com você.

### Fase 1: piloto com 5 a 10 oficinas (3 a 4 semanas)
- Oficinas da sua cidade, visita presencial, uso gratuito em troca de conversa semanal.
- Você configura junto na primeira vez e anota onde a pessoa trava. Cada trava vira correção de autoatendimento.
- Medir por oficina: orçamentos enviados por semana, % aprovados pelo link, tempo entre envio e aprovação, quem usa (dono ou técnico).
- **Critério para seguir:** pelo menos 3 oficinas enviando orçamento toda semana depois de 3 semanas, sem você lembrar. Se não bater, o problema é o produto ou o público, e não adianta gastar em divulgação.

### Fase 2: primeiras vendas (4 a 8 semanas)
- Landing com depoimento e número do piloto ("aprovação em X minutos").
- Converter o piloto em pagante (desconto de fundador nos primeiros meses).
- Canais baratos, nesta ordem:
  1. **Indicação**: oficina que indica ganha um mês.
  2. **Autopeças e distribuidores da região**: eles falam com todas as oficinas toda semana.
  3. **Grupos de WhatsApp e Facebook de mecânicos**, e conteúdo curto (Instagram, YouTube) mostrando o orçamento sendo aprovado.
  4. **Contadores** que atendem oficinas.
- Anúncio pago só depois de saber a conversão trial → pago.

### Fase 3: segurar o cliente (contínuo)
Ordem sugerida, a confirmar com o piloto: imprimir/PDF → Payments → lembretes (trazem o cliente de volta, é o argumento de retorno financeiro mais fácil) → NFS-e.

## 6. Números para acompanhar
- **Ativação:** % das oficinas que enviam o primeiro orçamento em 24 h após o cadastro.
- **Conversão:** trial → pagante.
- **Uso:** orçamentos enviados por oficina por semana.
- **Cancelamento** mensal e motivo.
- **Custo de WhatsApp** por oficina por mês, contra o preço do plano.

## Fontes
- [Ultracar: preços](https://ultracar.com.br/precos/)
- [Oficina.app: comparativo com Oficina Inteligente](https://oficina.app/oficina-app-vs-oficina-inteligente/) (fonte concorrente)
- [Oficina.app: comparativo com OnMotor](https://oficina.app/oficina-app-vs-onmotor/) (fonte concorrente)
- [Estado de Minas: 65% das oficinas operam sem sistema de gestão](https://www.em.com.br/networking-e-negocios/2026/07/7470212-65-das-oficinas-mecanicas-do-pais-ainda-operam-sem-sistema-de-gestao.html)
- [Sebrae em dados: oficina mecânica](https://sebraepr.com.br/comunidade/artigo/sebrae-em-dados-oficina-mecanica)
- [O Mecânico: lista de oficinas do Sindirepa-SP](https://omecanico.com.br/lista-de-oficinas-do-sindirepa-sp-facilita-pesquisa-de-servico-especializado/)

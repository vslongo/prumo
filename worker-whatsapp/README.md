# Worker WhatsApp

Envia as mensagens do Prumo pela **WhatsApp Cloud API (oficial)** e devolve o status para a API. Não tem regra de negócio: quem decide o que, para quem e quando é o módulo Messaging do Laravel (ver `modules/Messaging/README.md` e ADR 0003).

## Como funciona
1. O Laravel coloca `{tenant_id, message_id, template, language, to, params, sender_phone_number_id}` na lista Redis `<prefixo>whatsapp:outbound`.
2. O worker tira a mensagem da lista (passando por `<prefixo>whatsapp:processing`, para não perder nada se o processo cair) e envia o template para a Meta.
3. Erro temporário da Meta (limite de envio, instabilidade, rede) → mais 3 tentativas (2 s, 10 s e 30 s). Erro definitivo (template, número, token) → `failed` na hora.
4. O resultado vai para `POST /api/internal/whatsapp/status`, assinado com HMAC (`X-Signature: sha256=...`, segredo `WHATSAPP_WORKER_SECRET`).
5. A Meta avisa entrega, leitura e falha no webhook `POST /webhook` (assinatura `X-Hub-Signature-256` conferida com o App Secret). O worker identifica a mensagem pelo `biz_opaque_callback_data` (`tenant_id:message_id`) enviado junto com o template, e repassa o status para a API.

Telefone nunca aparece inteiro no log (só os 4 últimos dígitos).

## Rodar local
Precisa de Node 22.18+ (roda o TypeScript direto, sem build).

```bash
cp .env.example .env
```

Preencha o `.env` (o `WHATSAPP_WORKER_SECRET` tem que ser igual ao do `backend/.env`). Para o backend enfileirar, o `backend/.env` também precisa de `WHATSAPP_PHONE_NUMBER_ID`.

Pelo Docker (na raiz do repositório):

```bash
docker compose --profile whatsapp up -d worker-whatsapp
```

## Configurar na Meta (uma vez, conta do Prumo)
- App com o produto WhatsApp, número verificado e token de usuário de sistema (`META_ACCESS_TOKEN`).
- Templates `quote_link` e `service_order_ready` aprovados na categoria utility, em pt_BR. O texto sugerido está em `MessageTemplate.php`.
- Webhook: URL `https://<worker>/webhook`, token de verificação = `META_VERIFY_TOKEN`, campo `messages` assinado.

## Testes
```bash
npm run typecheck
```

```bash
npm test
```

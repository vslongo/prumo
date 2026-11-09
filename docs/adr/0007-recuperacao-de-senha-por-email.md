# 0007 — Recuperação de senha por e-mail

- Status: aceito
- Data: 2026-11-09

## Contexto
O Prumo vende por autoatendimento e não tinha "esqueci minha senha": quem esquecia a senha ficava sem entrar e sem ter a quem pedir. O backend também não enviava nenhum e-mail.

## Decisão
- **Fluxo:** `POST /api/password/forgot` (e-mail) → e-mail com link `<app>/redefinir-senha/{token}` → `POST /api/password/reset` (e-mail, token, senha nova). Usa o broker de senha do Laravel e a tabela `password_reset_tokens`, que já existia.
- **Não revela cadastro:** o pedido responde sempre 204, tenha o e-mail conta ou não. Na troca, e-mail errado, token errado e token vencido dão a mesma mensagem.
- **Token:** uso único, vale 60 minutos, só o hash fica no banco. Um pedido por minuto por e-mail (broker) e 5 por minuto por IP (`throttle:password-reset`).
- **O e-mail não vai na URL:** a pessoa digita o e-mail de novo na tela de senha nova. Dado pessoal fica fora de URL, log de acesso e Sentry. O caminho `/redefinir-senha/{token}` entra no filtro de token do Sentry do front.
- **Depois da troca:** todas as sessões e tokens do usuário caem, e a troca não abre sessão. A pessoa entra pelo login normal, então **o 2FA continua valendo** (trocar a senha não desliga nem pula o 2FA).
- **Usuário desativado** não recebe link nem consegue trocar a senha.
- **Envio:** notificação em fila (`ResetPasswordNotification`, `tries` e `backoff`), nunca na request. Provedor por SMTP configurado no `.env` (Resend previsto); nenhum pacote novo.
- **Texto do e-mail** em português; as frases fixas do modelo do Laravel são traduzidas em `backend/lang/pt_BR.json`.

## Consequências
- Quem perde a senha **e** o acesso ao e-mail continua dependendo do suporte. Quem perde o celular do 2FA usa os códigos de recuperação (ADR 0006).
- Funcionário convidado informa o próprio e-mail ao aceitar o convite, então também consegue recuperar a senha.
- Em produção é preciso um domínio de envio verificado (SPF/DKIM), senão o e-mail cai no spam (`docs/deploy.md`).
- Em dev o e-mail vai para `storage/logs/laravel.log` (`MAIL_MAILER=log`).

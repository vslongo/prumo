# Colocar o Prumo em produção

Passo a passo para subir num VPS com Laravel Forge. O que o código já entrega está marcado com ✅; o resto é configuração que só o dono das contas consegue fazer.

Tudo num domínio só (ex.: `app.seudominio.com.br`): o cookie de sessão do Sanctum precisa da mesma origem para o app e a API.

## 1. Servidor
- VPS Ubuntu LTS com **4 GB de RAM ou mais**, de preferência em São Paulo.
- No Forge: servidor do tipo "App Server" com **PHP 8.3, PostgreSQL 16, Redis e Node 22+**. Em provedor sem integração, use "Custom VPS".
- Extensões do PHP: `pdo_pgsql`, `redis`, `gd` (com WebP), `intl`, `bcmath`, `zip`, `pcntl`.
- Firewall: só 22, 80 e 443 abertos. Postgres e Redis não ficam expostos.

## 2. Site e código
- Site no Forge apontando para o repositório, branch `main` (staging) ou tag `vX.Y.Z` (produção), **web directory `/backend/public`**.
- HTTPS com Let's Encrypt pelo Forge.
- Script de deploy:

```bash
cd $FORGE_SITE_PATH
git pull origin $FORGE_SITE_BRANCH

cd backend
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan event:cache
php artisan horizon:terminate

cd ../frontend
npm ci
npm run build
```

- Nginx: o app (arquivos de `frontend/dist`) responde em `/`; `/api`, `/sanctum` e `/horizon` vão para o Laravel. No bloco `server` do site:

```nginx
root /home/forge/SEU_SITE/frontend/dist;
index index.html;

location / {
    try_files $uri /index.html;
}

location ~ ^/(api|sanctum|horizon|vendor/horizon) {
    root /home/forge/SEU_SITE/backend/public;
    try_files $uri /index.php?$query_string;
}

location ~ \.php$ {
    root /home/forge/SEU_SITE/backend/public;
    fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

> Teste esse bloco em staging antes de produção: ele não foi rodado em servidor real.

## 3. `.env` do backend (produção)
Parta do `backend/.env.example` e troque:

| Variável | Valor |
|---|---|
| `APP_ENV` / `APP_DEBUG` | `production` / `false` |
| `APP_URL`, `FRONTEND_URL` | `https://app.seudominio.com.br` |
| `SANCTUM_STATEFUL_DOMAINS` | `app.seudominio.com.br` |
| `SESSION_SECURE_COOKIE` | `true` |
| `LOG_LEVEL` | `warning` |
| `DB_*`, `REDIS_HOST` | os do servidor (`127.0.0.1`) |
| `R2_DRIVER` | `s3`, com `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, `R2_BUCKET`, `R2_ENDPOINT` de um bucket **privado** |
| `MAIL_*` | SMTP do provedor de e-mail (ver item 6) |
| `SENTRY_LARAVEL_DSN` | DSN do projeto backend |
| `ASAAS_*` | ver item 7 |
| `WHATSAPP_*` | ver item 8 |
| `HORIZON_ALLOWED_EMAILS` | seu e-mail de login no Prumo, para abrir `/horizon` |

Guarde uma cópia da `APP_KEY` fora do servidor. Sem ela, telefone, CPF e segredos de 2FA criptografados no banco **não podem ser lidos**, nem a partir do backup.

`frontend/.env.production` (lido no `npm run build`): `VITE_SENTRY_DSN`, `VITE_SENTRY_ENVIRONMENT=production`, `VITE_SUPPORT_WHATSAPP` (número do suporte, só dígitos com 55).

## 4. Processos (Forge → Daemons e Scheduler)
- ✅ **Horizon** (filas): daemon `php artisan horizon`, diretório `backend`. Processa a criação da OS depois da aprovação, miniaturas, Asaas, fila do WhatsApp e e-mails.
- ✅ **Scheduler**: ligue o agendador do Forge (`php artisan schedule:run` a cada minuto). Roda o backup (03:00), a limpeza de fotos pendentes (04:00) e as métricas do Horizon.
- **Worker WhatsApp** (só depois do item 8): daemon `npm start`, diretório `worker-whatsapp`, com o `.env` dele (`worker-whatsapp/README.md`). O webhook da Meta precisa chegar nele: um subdomínio (ex.: `wa.seudominio.com.br`) com proxy para a porta 3001.

## 5. Backup ✅
- `php artisan backup:database` roda todo dia às 03:00: `pg_dump` em formato próprio, enviado para `backups/` no bucket do R2, guardando 14 dias. Se falhar, o erro vai para o Sentry.
- **Teste a restauração antes de ter cliente**, e repita de tempos em tempos:

```bash
pg_restore --clean --no-owner -d oficina_restore backups_2026-11-09_030000.dump
```

- As fotos e assinaturas já ficam no R2. Ative o versionamento ou uma regra de retenção no bucket para não perder arquivo apagado por engano.

## 6. E-mail (senha nova)
- Conta num provedor de e-mail transacional (Resend previsto), **domínio verificado com SPF e DKIM**.
- Preencha `MAIL_MAILER=smtp` e os dados do provedor, e `MAIL_FROM_ADDRESS` num endereço do domínio verificado.
- Teste: peça "Esqueci minha senha" com uma conta real e confira se chega na caixa de entrada, não no spam.

## 7. Asaas em produção
O código é o mesmo do sandbox; muda só a configuração.

- [ ] Conta Asaas da empresa aprovada (envio de documentos e conta bancária).
- [ ] Chave de API de **produção** em `ASAAS_API_KEY` e `ASAAS_BASE_URL=https://api.asaas.com/v3`.
- [ ] Webhook no painel do Asaas: URL `https://app.seudominio.com.br/api/webhooks/asaas`, eventos de cobrança, e um **token de autenticação** igual ao `ASAAS_WEBHOOK_TOKEN`.
- [ ] Um ciclo completo com uma oficina de teste e dinheiro de verdade (plano Solo): assinar → pagar → conferir que a conta virou `active`; trocar de plano; deixar uma cobrança vencer e conferir `past_due` e, depois de 7 dias, o modo somente leitura; cancelar.
- [ ] Conferir no painel do Asaas se a nota fiscal de serviço do Prumo para a oficina precisa ser emitida e como (isso é obrigação fiscal da sua empresa, fora do sistema).

## 8. WhatsApp oficial (pode vir depois do piloto)
Sem isso o sistema já funciona: o técnico manda o link pelo botão `wa.me`.

- [ ] Conta Meta Business verificada, app com o produto WhatsApp, número dedicado do Prumo.
- [ ] Modelos `quote_link` e `service_order_ready` aprovados (categoria utility, pt_BR).
- [ ] `WHATSAPP_PHONE_NUMBER_ID` e `WHATSAPP_WORKER_SECRET` no backend, worker no ar e webhook configurado (`worker-whatsapp/README.md`).

## 9. Antes de divulgar
- [ ] `frontend/src/features/legal/company.ts` preenchido e termos revisados por advogado (ADR 0008).
- [ ] Cadastro, primeiro orçamento, aprovação com assinatura e OS feitos pelo celular, em produção.
- [ ] "Esqueci minha senha" testado de ponta a ponta.
- [ ] Erro de teste aparecendo no Sentry dos três projetos, sem dado pessoal.
- [ ] Backup restaurado com sucesso num banco de teste.
- [ ] 2FA ativado na sua conta de dono.

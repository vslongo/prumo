# 0006 — 2FA do dono com app autenticador e prazo de 7 dias

- Status: aceito
- Data: 2026-11-04

## Contexto
O CLAUDE.md pede "2FA para owner" (§10), mas não dizia qual método usar, quando passa a ser obrigatório nem se os outros papéis podem ativar. O Prumo vende por autoatendimento, com teste grátis de 14 dias, e o dono enxerga dados pessoais de todos os clientes da oficina.

## Decisão
- **Método:** app autenticador (TOTP, RFC 6238: SHA1, 6 dígitos, passo de 30 s), compatível com Google e Microsoft Authenticator, mais 8 códigos de recuperação de uso único.
  - É grátis, funciona sem internet e não depende de e-mail nem do WhatsApp do Prumo.
  - A implementação é própria (`Modules/Auth/Support/Totp.php`, testada com os vetores da RFC), sem pacote novo.
  - Segredo e hashes dos códigos de recuperação ficam com cast `encrypted`. O código não pode ser reusado: guardamos o último passo aceito.
- **Obrigatoriedade:** obrigatório só para o dono, a partir de 7 dias da criação da conta.
  - Durante o prazo, o app mostra um aviso.
  - Depois do prazo, o middleware `EnsureTwoFactorEnabled` (grupo `tenant`) responde 403 `two_factor_required` em todas as rotas da oficina, menos `me`, `logout` e as rotas do 2FA. O app mostra só a tela de ativação.
- **Outros papéis:** admin, técnico e balcão podem ativar na tela Segurança, sem obrigação.
- **Login:** com 2FA ativo, a senha certa não abre sessão. O usuário fica pendente na sessão por 5 minutos e o login termina em `POST /api/login/two-factor`, com o código do app ou um código de recuperação. São 5 tentativas por minuto por usuário.
- **Desativar:** pede a senha. Para o dono que já passou do prazo, o bloqueio volta na hora.

## Consequências
- A tela de ativação não tem QR code. No celular, o botão "Abrir no app autenticador" usa o link `otpauth://`; no computador, o dono digita a chave no app. Um QR entra se o suporte mostrar que faz falta, com uma lib pequena no front.
- Gerar novos códigos de recuperação é feito desativando e ativando de novo.
- Se o dono perder o celular e os códigos de recuperação, só o suporte resolve (pós-core: comando admin revisado para zerar o 2FA).
- Quando o módulo Audit existir, ativar, desativar e usar código de recuperação devem entrar no log de auditoria.

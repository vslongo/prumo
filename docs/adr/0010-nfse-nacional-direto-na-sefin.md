# 0010 — NFS-e direto na API Nacional (Sefin), sem intermediário

- Status: aceito
- Data: 2026-11-23

## Contexto
O plano era emitir NFS-e pela Focus NFe (API paga, custo por nota, pós-core). Com o padrão nacional da NFS-e (Sefin Nacional/ADN), a oficina emite direto na API do governo com o próprio certificado digital, sem intermediário. Isso tira o custo por nota e uma dependência externa. A NFS-e está entre as faltas citadas pelas oficinas (`docs/go-to-market.md`), então o módulo entra agora, por pedido explícito.

## Decisão
- **Emissão direta na Sefin Nacional**, sem Focus NFe nem outro intermediário. mTLS com o **certificado A1 (.pfx) da própria oficina** e DPS em XML assinado (XMLDSig RSA-SHA256), sem biblioteca externa de NFS-e.
- **Certificado por tenant:** o .pfx sobe direto do navegador para o R2 (bucket privado) pelo módulo Media, como qualquer upload. A senha fica em `fiscal_profiles` com cast `encrypted`. Ao confirmar, o backend abre o certificado e recusa se o CNPJ não bater com o perfil fiscal ou se estiver vencido. Só owner/admin mexem.
- **Gatilho:** botão "Emitir nota fiscal" na OS `delivered` ou `closed`. Não emite sozinho ao fechar a OS.
- **O que entra na nota:** por padrão, todos os itens da OS (serviços e peças). O perfil fiscal tem `include_products`: desligado, só os itens de serviço entram. A tela avisa para confirmar com o contador, porque a LC 116 (item 14.01) põe peças no ICMS e alguns municípios recusam.
- **Regimes:** só Simples Nacional ME/EPP e MEI, com ISS não retido. Outros regimes são recusados com mensagem clara.
- **Planos:** todos.
- **Idempotência:** o número da DPS (`dps_series` + `dps_number`) é reservado antes do envio e reaproveitado em toda nova tentativa. Antes de reenviar, consulta a DPS na Sefin. Fila `fiscal`.
- **PDF (DANFSe) gerado pelo Prumo** a partir do XML autorizado, com QR code da consulta pública, e salvo no R2. Não dependemos da API de DANFSe do ADN.
- **Cancelamento** pelo evento de cancelamento da Sefin, com motivo obrigatório.

## Consequências
- Guardamos certificado e senha de cada oficina: o vazamento permite emitir nota em nome dela. Ficam no bucket privado e no banco criptografado; nunca vão para log, Sentry ou resposta de API. PEM temporário do mTLS só existe durante o envio (0600, apagado no `finally`).
- O certificado A1 vence em 1 ano; a tela avisa 30 dias antes.
- Testes automatizados usam `Http::fake()` e um certificado gerado no próprio teste. A homologação de verdade (produção restrita) exige o A1 real de uma oficina e é feita à mão.
- O layout da DPS segue os XSD e anexos oficiais publicados em gov.br/nfse; mudanças do governo (ex.: grupo IBS/CBS da reforma tributária) exigem atualizar o módulo.
- Lucro presumido/real, ISS retido e NF-e de peças (modelo 55) ficam fora. Entram só quando um piloto pedir.

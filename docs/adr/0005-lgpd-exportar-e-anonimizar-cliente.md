# 0005 — LGPD: exportar e anonimizar cliente

- Status: aceito
- Data: 2026-11-04

## Contexto
A oficina é controladora dos dados do cliente final, e nós somos operadores. O CLAUDE.md exige `ExportCustomerData` e `AnonymizeCustomer`, mas não dizia o que anonimizar faz com dados que outras regras mandam guardar:
- `quote_approvals` (nome de quem assinou, PNG da assinatura, IP e user agent) é imutável.
- Orçamentos, OS e valores são o registro de trabalho e cobrança da oficina.
- O veículo tem placa e chassi, que também são dado pessoal.

## Decisão
- **Exportar** (direito de acesso, art. 18): `GET /api/customers/{id}/export` devolve um JSON com o cadastro, o consentimento, os veículos, os orçamentos (itens e dados da aprovação), as OS e as mensagens de WhatsApp. A imagem da assinatura não vai no arquivo. Cada módulo entrega a sua parte por Action pública (`ExportCustomerQuotes`, `ExportCustomerServiceOrders`, `ExportCustomerMessages`, `ListCustomerVehicles`).
- **Anonimizar** (eliminação, art. 18): `POST /api/customers/{id}/anonymize`. É irreversível e idempotente.
  - **Apaga:** nome (vira "Cliente anonimizado"), e-mail, telefone, documento, os hashes de busca e o consentimento. Grava `anonymized_at`. O cadastro não é soft-deletado: continua existindo como âncora anônima do histórico.
  - **Limpa nas mensagens:** o módulo Messaging ouve `CustomerAnonymized` e apaga `to` e `params` de cada mensagem do cliente. A linha e o status ficam.
  - **Mantém:**
    - Orçamentos, OS, itens e valores: são registro da oficina e não identificam a pessoa depois que o cadastro fica anônimo.
    - Veículo com placa e chassi: a oficina precisa do histórico por placa.
    - `quote_approvals` intacta, com assinatura, nome, IP e user agent: é a prova do contrato aceito. A guarda tem base no art. 16, II, e no art. 7º, VI (exercício regular de direitos), e a regra de imutabilidade continua valendo.
- **Quem pode:** owner e admin. Técnico e balcão editam o cliente e o consentimento, mas não exportam nem anonimizam.
- **Consentimento de WhatsApp:** marcar grava data e origem (sem sobrescrever um consentimento já dado). Desmarcar é a revogação e apaga os dois.

## Consequências
- Texto livre que mencione o cliente (observações de orçamento ou de OS) não é varrido. A oficina edita à mão se precisar.
- Se a placa precisar sair também, isso vira uma decisão nova (por exemplo, uma opção na anonimização), já pensando no registro global de veículos do pós-core.
- Quando o módulo Audit existir, exportar e anonimizar devem entrar no log de auditoria.

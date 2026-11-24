<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
  h1 { font-size: 14px; margin: 0 0 2px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
  td { border: 1px solid #555; padding: 4px 6px; vertical-align: top; }
  .label { display: block; font-size: 8px; color: #555; text-transform: uppercase; }
  .total { font-size: 13px; font-weight: bold; }
  .warn { color: #b00; font-weight: bold; }
  pre { font-family: DejaVu Sans, sans-serif; white-space: pre-wrap; margin: 0; }
</style>
</head>
<body>
<table>
  <tr>
    <td style="width: 75%">
      <h1>DANFSe · Documento Auxiliar da NFS-e</h1>
      @if ($invoice->environment->value === 'restricted')
        <div class="warn">AMBIENTE DE HOMOLOGAÇÃO · SEM VALOR FISCAL</div>
      @endif
      @if ($invoice->status->value === 'canceled')
        <div class="warn">NFS-E CANCELADA</div>
      @endif
      <span class="label">Chave de acesso</span>{{ $invoice->access_key }}
    </td>
    <td style="text-align: center"><img src="{{ $qr }}" width="110" alt=""></td>
  </tr>
</table>
<table>
  <tr>
    <td><span class="label">Número da NFS-e</span>{{ $invoice->nfse_number ?? '—' }}</td>
    <td><span class="label">DPS (série / número)</span>{{ $invoice->dps_series }} / {{ $invoice->dps_number }}</td>
    <td><span class="label">Emissão</span>{{ $invoice->authorized_at?->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</td>
  </tr>
</table>
<table>
  <tr><td colspan="2"><span class="label">Prestador</span>{{ $provider['name'] }}</td></tr>
  <tr>
    <td><span class="label">CNPJ</span>{{ $provider['cnpj'] }}</td>
    <td><span class="label">Inscrição municipal</span>{{ $provider['im'] ?? '—' }}</td>
  </tr>
</table>
<table>
  <tr>
    <td><span class="label">Tomador</span>{{ $taker['name'] }}</td>
    <td><span class="label">CPF/CNPJ</span>{{ $taker['document'] }}</td>
  </tr>
</table>
<table>
  <tr><td><span class="label">Discriminação do serviço</span><pre>{{ $description }}</pre></td></tr>
</table>
<table>
  <tr>
    <td><span class="label">Valor do serviço</span><span class="total">R$ {{ number_format(intdiv($invoice->amount_cents, 100), 0, ',', '.') }},{{ sprintf('%02d', $invoice->amount_cents % 100) }}</span></td>
    <td><span class="label">ISS</span>Simples Nacional · não retido</td>
  </tr>
</table>
<p>Confira a autenticidade em {{ $queryUrl }}</p>
</body>
</html>

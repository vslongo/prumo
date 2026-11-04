import { createHmac } from 'node:crypto';
import { log, sleep } from './log.ts';

export type DeliveryStatus = 'sent' | 'delivered' | 'read' | 'failed';

export type StatusUpdate = {
  tenant_id: string;
  message_id: string;
  status: DeliveryStatus;
  provider_message_id?: string | null;
  error?: string | null;
};

export type ReportStatus = (update: StatusUpdate) => Promise<void>;

export function sign(body: string, secret: string): string {
  return `sha256=${createHmac('sha256', secret).update(body).digest('hex')}`;
}

/**
 * POST /api/internal/whatsapp/status assinado (VerifyWorkerSignature no backend). A API ignora status repetido
 * ou atrasado, então repetir é seguro. Desiste em 4xx (mensagem sumiu, segredo errado, payload inválido).
 */
export function createStatusReporter(apiUrl: string, secret: string, delaysMs = [1_000, 5_000, 30_000]): ReportStatus {
  return async (update) => {
    const body = JSON.stringify(update);
    for (let attempt = 0; ; attempt++) {
      let retryable = true;
      try {
        const res = await fetch(`${apiUrl}/api/internal/whatsapp/status`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Signature': sign(body, secret) },
          body,
        });
        if (res.ok) return;
        retryable = res.status >= 500 || res.status === 429;
        log('warn', 'callback recusado pela API', { status: res.status, message_id: update.message_id });
      } catch (err) {
        log('warn', 'callback falhou', { error: String(err), message_id: update.message_id });
      }
      const delay = delaysMs[attempt];
      if (!retryable || delay === undefined) {
        log('error', 'callback abandonado', { message_id: update.message_id, status: update.status });
        return;
      }
      await sleep(delay);
    }
  };
}

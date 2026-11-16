import type { ReportStatus } from './api-callback.ts';
import { log, maskPhone, sleep } from './log.ts';
import type { OutboundMessage, SendTemplate } from './meta-client.ts';

// Subconjunto do ioredis que o consumidor usa (facilita o teste).
export type QueueRedis = {
  blmove(source: string, destination: string, from: 'LEFT', to: 'RIGHT', timeout: number): Promise<string | null>;
  lmove(source: string, destination: string, from: 'RIGHT', to: 'LEFT'): Promise<string | null>;
  lrem(key: string, count: number, element: string): Promise<number>;
};

export type ConsumerDeps = {
  send: SendTemplate;
  report: ReportStatus;
  minIntervalMs: number;
  retryDelaysMs?: number[];
};

function parse(raw: string): OutboundMessage | null {
  try {
    const m = JSON.parse(raw) as Partial<OutboundMessage>;
    if (m.tenant_id && m.message_id && m.template && m.to && m.sender_phone_number_id && Array.isArray(m.params)) {
      return { language: 'pt_BR', ...m } as OutboundMessage;
    }
  } catch {
    // cai no log abaixo
  }
  return null;
}

/** Envia uma mensagem da fila: espera o intervalo do remetente, tenta de novo erro temporário e reporta sent/failed. */
export function createMessageHandler(deps: ConsumerDeps) {
  const lastSentAt = new Map<string, number>();
  const retryDelaysMs = deps.retryDelaysMs ?? [2_000, 10_000, 30_000];

  return async (raw: string): Promise<void> => {
    const message = parse(raw);
    if (message === null) {
      log('error', 'mensagem inválida na fila, descartada');
      return;
    }
    const ctx = { message_id: message.message_id, tenant_id: message.tenant_id, to: maskPhone(message.to) };

    for (let attempt = 0; ; attempt++) {
      // ponytail: limite por remetente em memória, vale para um processo só; Redis se rodar mais de um worker.
      const wait = (lastSentAt.get(message.sender_phone_number_id) ?? 0) + deps.minIntervalMs - Date.now();
      if (wait > 0) await sleep(wait);
      lastSentAt.set(message.sender_phone_number_id, Date.now());

      const result = await deps.send(message);
      if (result.ok) {
        log('info', 'mensagem enviada', ctx);
        await deps.report({ tenant_id: message.tenant_id, message_id: message.message_id, status: 'sent', provider_message_id: result.providerMessageId });
        return;
      }

      const delay = retryDelaysMs[attempt];
      if (!result.retryable || delay === undefined) {
        log('warn', 'envio falhou', { ...ctx, error: result.error });
        await deps.report({ tenant_id: message.tenant_id, message_id: message.message_id, status: 'failed', error: result.error });
        return;
      }
      log('warn', 'envio falhou, tentando de novo', { ...ctx, error: result.error, delay_ms: delay });
      await sleep(delay);
    }
  };
}

/**
 * Fila confiável: a mensagem sai de `outbound` para `processing` e só é removida depois de tratada. Se o processo cair,
 * o que ficou em `processing` volta para a fila no próximo start (pode reenviar uma mensagem que a Meta já aceitou).
 * ponytail: processamento serial; paralelizar por remetente se o volume pedir.
 */
export async function runConsumer(
  redis: QueueRedis,
  keys: { outbound: string; processing: string },
  handle: (raw: string) => Promise<void>,
  shouldStop: () => boolean = () => false,
): Promise<void> {
  while ((await redis.lmove(keys.processing, keys.outbound, 'RIGHT', 'LEFT')) !== null) {
    log('warn', 'mensagem recuperada de execução anterior');
  }

  while (!shouldStop()) {
    const raw = await redis.blmove(keys.outbound, keys.processing, 'LEFT', 'RIGHT', 5);
    if (raw === null) continue;
    try {
      await handle(raw);
    } catch (err) {
      log('error', 'erro inesperado ao tratar mensagem', { error: String(err) });
    }
    await redis.lrem(keys.processing, 1, raw);
  }
}

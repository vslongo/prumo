import { createHmac } from 'node:crypto';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { createStatusReporter, sign, type StatusUpdate } from '../src/api-callback.ts';
import { createMessageHandler, runConsumer, type QueueRedis } from '../src/consumer.ts';
import { maskPhone } from '../src/log.ts';
import { createMetaClient, templatePayload, type OutboundMessage, type SendResult } from '../src/meta-client.ts';
import { extractStatuses, verifyMetaSignature } from '../src/webhook.ts';

const TENANT = '0192f0a1-0000-7000-8000-000000000001';
const MESSAGE = '0192f0a1-0000-7000-8000-000000000002';

const message: OutboundMessage = {
  tenant_id: TENANT,
  message_id: MESSAGE,
  template: 'quote_link',
  language: 'pt_BR',
  to: '5511987654321',
  params: ['Ana', 'Oficina do Zé', 'https://app/q/abc'],
  sender_phone_number_id: '123456',
};

const jsonResponse = (status: number, body: unknown) => new Response(JSON.stringify(body), { status });

afterEach(() => vi.unstubAllGlobals());

describe('log', () => {
  it('mascara o telefone deixando só os 4 últimos dígitos', () => {
    expect(maskPhone('5511987654321')).toBe('***4321');
  });
});

describe('api-callback', () => {
  it('assina igual ao VerifyWorkerSignature do Laravel (hash_hmac sha256)', () => {
    const body = '{"a":1}';
    expect(sign(body, 's3cr3t')).toBe(`sha256=${createHmac('sha256', 's3cr3t').update(body).digest('hex')}`);
  });

  it('repete em 5xx e para no sucesso', async () => {
    const fetchMock = vi.fn().mockResolvedValueOnce(new Response(null, { status: 503 })).mockResolvedValueOnce(new Response(null, { status: 204 }));
    vi.stubGlobal('fetch', fetchMock);

    await createStatusReporter('http://api', 'k', [0])({ tenant_id: TENANT, message_id: MESSAGE, status: 'sent' });

    expect(fetchMock).toHaveBeenCalledTimes(2);
    const [url, init] = fetchMock.mock.calls[1] as [string, RequestInit];
    expect(url).toBe('http://api/api/internal/whatsapp/status');
    expect((init.headers as Record<string, string>)['X-Signature']).toBe(sign(init.body as string, 'k'));
  });

  it('desiste em 4xx sem repetir', async () => {
    const fetchMock = vi.fn().mockResolvedValue(new Response(null, { status: 404 }));
    vi.stubGlobal('fetch', fetchMock);

    await createStatusReporter('http://api', 'k', [0, 0])({ tenant_id: TENANT, message_id: MESSAGE, status: 'sent' });

    expect(fetchMock).toHaveBeenCalledTimes(1);
  });
});

describe('meta-client', () => {
  it('monta o template com os parâmetros na ordem e o callback data', () => {
    const payload = templatePayload(message);
    expect(payload.biz_opaque_callback_data).toBe(`${TENANT}:${MESSAGE}`);
    expect(payload.template.components[0]?.parameters.map((p) => p.text)).toEqual(message.params);
  });

  it('devolve o id da Meta no sucesso', async () => {
    const fetchMock = vi.fn().mockResolvedValue(jsonResponse(200, { messages: [{ id: 'wamid.X' }] }));
    vi.stubGlobal('fetch', fetchMock);

    expect(await createMetaClient('tok', 'v23.0')(message)).toEqual({ ok: true, providerMessageId: 'wamid.X' });
    expect(fetchMock.mock.calls[0]?.[0]).toBe('https://graph.facebook.com/v23.0/123456/messages');
  });

  it('limite de envio é temporário; template inexistente é definitivo', async () => {
    vi.stubGlobal('fetch', vi.fn()
      .mockResolvedValueOnce(jsonResponse(400, { error: { code: 130429, message: 'rate limit' } }))
      .mockResolvedValueOnce(jsonResponse(404, { error: { code: 132001, message: 'template não existe' } })));
    const send = createMetaClient('tok', 'v23.0');

    expect(await send(message)).toMatchObject({ ok: false, retryable: true });
    expect(await send(message)).toMatchObject({ ok: false, retryable: false, error: '132001: template não existe' });
  });

  it('erro de rede é temporário', async () => {
    vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new Error('ECONNRESET')));
    expect(await createMetaClient('tok', 'v23.0')(message)).toMatchObject({ ok: false, retryable: true });
  });
});

describe('consumer', () => {
  const setup = (results: SendResult[], retryDelaysMs = [0, 0]) => {
    const send = vi.fn(async () => results.shift() ?? { ok: false as const, retryable: true, error: 'x' });
    const reports: StatusUpdate[] = [];
    const handle = createMessageHandler({ send, report: async (u) => void reports.push(u), minIntervalMs: 0, retryDelaysMs });
    return { send, reports, handle };
  };

  it('envia e reporta sent com o id da Meta', async () => {
    const { reports, handle } = setup([{ ok: true, providerMessageId: 'wamid.1' }]);
    await handle(JSON.stringify(message));
    expect(reports).toEqual([{ tenant_id: TENANT, message_id: MESSAGE, status: 'sent', provider_message_id: 'wamid.1' }]);
  });

  it('tenta de novo erro temporário e depois envia', async () => {
    const { send, reports, handle } = setup([{ ok: false, retryable: true, error: 'x' }, { ok: true, providerMessageId: 'wamid.2' }]);
    await handle(JSON.stringify(message));
    expect(send).toHaveBeenCalledTimes(2);
    expect(reports[0]?.status).toBe('sent');
  });

  it('reporta failed quando as tentativas acabam', async () => {
    const { send, reports, handle } = setup([], [0]);
    await handle(JSON.stringify(message));
    expect(send).toHaveBeenCalledTimes(2);
    expect(reports).toEqual([{ tenant_id: TENANT, message_id: MESSAGE, status: 'failed', error: 'x' }]);
  });

  it('erro definitivo falha na hora', async () => {
    const { send, reports, handle } = setup([{ ok: false, retryable: false, error: '132001: x' }]);
    await handle(JSON.stringify(message));
    expect(send).toHaveBeenCalledTimes(1);
    expect(reports[0]).toMatchObject({ status: 'failed', error: '132001: x' });
  });

  it('descarta mensagem inválida sem enviar', async () => {
    const { send, reports, handle } = setup([]);
    await handle('{"tenant_id":"x"}');
    await handle('não é json');
    expect(send).not.toHaveBeenCalled();
    expect(reports).toEqual([]);
  });

  it('recupera o que ficou em processing e só remove depois de tratar', async () => {
    const lists: Record<string, string[]> = { out: ['b'], proc: ['a'] };
    let stop = false;
    const redis: QueueRedis = {
      lmove: async (src, dst) => {
        const v = lists[src]!.pop() ?? null;
        if (v !== null) lists[dst]!.unshift(v);
        return v;
      },
      blmove: async (src, dst) => {
        const v = lists[src]!.shift() ?? null;
        if (v !== null) lists[dst]!.push(v);
        if (lists[src]!.length === 0) stop = true;
        return v;
      },
      lrem: async (key, _count, el) => {
        lists[key] = lists[key]!.filter((x) => x !== el);
        return 1;
      },
    };
    const handled: string[] = [];

    await runConsumer(redis, { outbound: 'out', processing: 'proc' }, async (raw) => {
      expect(lists.proc).toContain(raw);
      handled.push(raw);
    }, () => stop && lists.out!.length === 0);

    expect(handled).toEqual(['a', 'b']);
    expect(lists).toEqual({ out: [], proc: [] });
  });
});

describe('webhook', () => {
  it('confere a assinatura da Meta', () => {
    const body = '{"entry":[]}';
    const header = `sha256=${createHmac('sha256', 'app').update(body).digest('hex')}`;
    expect(verifyMetaSignature(body, header, 'app')).toBe(true);
    expect(verifyMetaSignature(body, header, 'outro')).toBe(false);
    expect(verifyMetaSignature(body, undefined, 'app')).toBe(false);
  });

  it('extrai só status de mensagens nossas', () => {
    const updates = extractStatuses({
      entry: [{
        changes: [{
          value: {
            statuses: [
              { id: 'wamid.1', status: 'delivered', biz_opaque_callback_data: `${TENANT}:${MESSAGE}` },
              { id: 'wamid.2', status: 'failed', biz_opaque_callback_data: `${TENANT}:${MESSAGE}`, errors: [{ code: 131026, message: 'não entregue' }] },
              { id: 'wamid.3', status: 'read' },
              { id: 'wamid.4', status: 'deleted', biz_opaque_callback_data: `${TENANT}:${MESSAGE}` },
              { id: 'wamid.5', status: 'read', biz_opaque_callback_data: 'lixo:123' },
            ],
          },
        }],
      }],
    });

    expect(updates).toEqual([
      { tenant_id: TENANT, message_id: MESSAGE, status: 'delivered', provider_message_id: 'wamid.1', error: null },
      { tenant_id: TENANT, message_id: MESSAGE, status: 'failed', provider_message_id: 'wamid.2', error: '131026: não entregue' },
    ]);
  });
});

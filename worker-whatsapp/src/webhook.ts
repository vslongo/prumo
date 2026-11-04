import { createHmac, timingSafeEqual } from 'node:crypto';
import { createServer, type IncomingMessage } from 'node:http';
import type { DeliveryStatus, ReportStatus, StatusUpdate } from './api-callback.ts';
import { log } from './log.ts';

const STATUSES = new Set<DeliveryStatus>(['sent', 'delivered', 'read', 'failed']);
const UUID = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

/** `X-Hub-Signature-256: sha256=<hex>` = HMAC-SHA256 do corpo cru com o App Secret da Meta. */
export function verifyMetaSignature(rawBody: string, header: string | undefined, appSecret: string): boolean {
  if (!header) return false;
  const expected = Buffer.from(`sha256=${createHmac('sha256', appSecret).update(rawBody).digest('hex')}`);
  const given = Buffer.from(header);
  return expected.length === given.length && timingSafeEqual(expected, given);
}

type WebhookPayload = {
  entry?: {
    changes?: {
      value?: {
        statuses?: {
          id?: string;
          status?: string;
          biz_opaque_callback_data?: string;
          errors?: { code?: number; title?: string; message?: string }[];
        }[];
      };
    }[];
  }[];
};

/** Status de mensagens nossas (com `biz_opaque_callback_data` = tenant:mensagem). Mensagens recebidas e o resto são ignorados. */
export function extractStatuses(payload: WebhookPayload): StatusUpdate[] {
  const updates: StatusUpdate[] = [];
  for (const entry of payload.entry ?? []) {
    for (const change of entry.changes ?? []) {
      for (const s of change.value?.statuses ?? []) {
        const [tenantId, messageId] = (s.biz_opaque_callback_data ?? '').split(':');
        if (!tenantId || !messageId || !UUID.test(tenantId) || !UUID.test(messageId)) continue;
        if (!STATUSES.has(s.status as DeliveryStatus)) continue;
        const err = s.errors?.[0];
        updates.push({
          tenant_id: tenantId,
          message_id: messageId,
          status: s.status as DeliveryStatus,
          provider_message_id: s.id ?? null,
          error: err ? `${err.code ?? ''}: ${err.message ?? err.title ?? ''}` : null,
        });
      }
    }
  }
  return updates;
}

async function readBody(req: IncomingMessage, limit = 1_000_000): Promise<string> {
  let body = '';
  for await (const chunk of req) {
    body += chunk;
    if (body.length > limit) throw new Error('corpo grande demais');
  }
  return body;
}

export function createWebhookServer(opts: { appSecret: string; verifyToken: string; report: ReportStatus }) {
  return createServer(async (req, res) => {
    const url = new URL(req.url ?? '/', 'http://localhost');

    if (req.method === 'GET' && url.pathname === '/health') {
      res.writeHead(200).end('ok');
      return;
    }

    if (url.pathname !== '/webhook') {
      res.writeHead(404).end();
      return;
    }

    // Verificação do endpoint ao cadastrar o webhook no app da Meta.
    if (req.method === 'GET') {
      const ok = url.searchParams.get('hub.mode') === 'subscribe' && url.searchParams.get('hub.verify_token') === opts.verifyToken;
      res.writeHead(ok ? 200 : 403).end(ok ? (url.searchParams.get('hub.challenge') ?? '') : '');
      return;
    }

    if (req.method !== 'POST') {
      res.writeHead(405).end();
      return;
    }

    let raw: string;
    try {
      raw = await readBody(req);
    } catch {
      res.writeHead(413).end();
      return;
    }
    if (!verifyMetaSignature(raw, req.headers['x-hub-signature-256'] as string | undefined, opts.appSecret)) {
      log('warn', 'webhook com assinatura inválida');
      res.writeHead(401).end();
      return;
    }

    let updates: StatusUpdate[];
    try {
      updates = extractStatuses(JSON.parse(raw) as WebhookPayload);
    } catch {
      res.writeHead(400).end();
      return;
    }

    // Responde logo (a Meta reenvia se demorar) e repassa para a API em segundo plano.
    res.writeHead(200).end();
    for (const update of updates) void opts.report(update);
  });
}

// Cliente mínimo da WhatsApp Cloud API (oficial). Só envia template; nenhuma regra de negócio.

export type OutboundMessage = {
  tenant_id: string;
  message_id: string;
  template: string;
  language: string;
  to: string;
  params: string[];
  sender_phone_number_id: string;
};

export type SendResult =
  | { ok: true; providerMessageId: string }
  | { ok: false; retryable: boolean; error: string };

export type SendTemplate = (message: OutboundMessage) => Promise<SendResult>;

// Erros temporários da Meta (limite de envio, serviço indisponível). O resto é definitivo (template, número, permissão).
const RETRYABLE_CODES = new Set([1, 2, 4, 80007, 130429, 131000, 131016, 131056]);

/** Volta em webhook de status como `biz_opaque_callback_data`; é como o webhook sabe de qual mensagem nossa se trata. */
export function callbackData(message: Pick<OutboundMessage, 'tenant_id' | 'message_id'>): string {
  return `${message.tenant_id}:${message.message_id}`;
}

export function templatePayload(message: OutboundMessage) {
  return {
    messaging_product: 'whatsapp',
    recipient_type: 'individual',
    to: message.to,
    type: 'template',
    biz_opaque_callback_data: callbackData(message),
    template: {
      name: message.template,
      language: { code: message.language },
      components: message.params.length
        ? [{ type: 'body', parameters: message.params.map((text) => ({ type: 'text', text })) }]
        : [],
    },
  };
}

type MetaResponse = {
  messages?: { id: string }[];
  error?: { code?: number; message?: string };
};

export function createMetaClient(accessToken: string, graphVersion: string): SendTemplate {
  return async (message) => {
    let res: Response;
    try {
      res = await fetch(`https://graph.facebook.com/${graphVersion}/${message.sender_phone_number_id}/messages`, {
        method: 'POST',
        headers: { Authorization: `Bearer ${accessToken}`, 'Content-Type': 'application/json' },
        body: JSON.stringify(templatePayload(message)),
      });
    } catch (err) {
      return { ok: false, retryable: true, error: `rede: ${String(err)}` };
    }

    const json = (await res.json().catch(() => ({}))) as MetaResponse;
    const id = json.messages?.[0]?.id;
    if (res.ok && id) return { ok: true, providerMessageId: id };

    const code = json.error?.code;
    return {
      ok: false,
      retryable: res.status >= 500 || res.status === 429 || (code !== undefined && RETRYABLE_CODES.has(code)),
      error: `${code ?? res.status}: ${json.error?.message ?? 'resposta inesperada da Meta'}`,
    };
  };
}

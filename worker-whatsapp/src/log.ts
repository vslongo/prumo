import * as Sentry from '@sentry/node';

// Logs JSON numa linha. Nunca passe telefone inteiro: use maskPhone. Erro também vai para o Sentry (se ligado).
export function log(level: 'info' | 'warn' | 'error', msg: string, context: Record<string, unknown> = {}): void {
  const line = JSON.stringify({ level, msg, time: new Date().toISOString(), ...context });
  (level === 'error' ? console.error : console.log)(line);
  if (level === 'error') Sentry.captureMessage(msg, { level: 'error', extra: context });
}

/** Só com SENTRY_DSN. Coleta automática desligada: o que vai é a mensagem do log, que já sai sem telefone inteiro. */
export function initSentry(dsn: string | undefined): void {
  if (!dsn) return;
  Sentry.init({
    dsn,
    environment: process.env.SENTRY_ENVIRONMENT ?? process.env.NODE_ENV,
    dataCollection: { userInfo: false, cookies: false, httpHeaders: false, httpBodies: [], urlQueryParams: false },
  });
}

export function maskPhone(phone: string): string {
  return `***${phone.slice(-4)}`;
}

export const sleep = (ms: number) => new Promise<void>((resolve) => setTimeout(resolve, ms));

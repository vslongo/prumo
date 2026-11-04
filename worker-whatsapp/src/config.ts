export type Config = {
  redisUrl: string;
  redisPrefix: string;
  apiUrl: string;
  workerSecret: string;
  metaAccessToken: string;
  metaAppSecret: string;
  metaVerifyToken: string;
  metaGraphVersion: string;
  port: number;
  sendMinIntervalMs: number;
};

function required(env: NodeJS.ProcessEnv, name: string): string {
  const value = env[name];
  if (!value) throw new Error(`Variável de ambiente ${name} não configurada.`);
  return value;
}

export function loadConfig(env: NodeJS.ProcessEnv = process.env): Config {
  return {
    redisUrl: env.REDIS_URL ?? 'redis://127.0.0.1:6379/0',
    redisPrefix: env.REDIS_PREFIX ?? 'oficina-database-',
    apiUrl: required(env, 'API_URL').replace(/\/$/, ''),
    workerSecret: required(env, 'WHATSAPP_WORKER_SECRET'),
    metaAccessToken: required(env, 'META_ACCESS_TOKEN'),
    metaAppSecret: required(env, 'META_APP_SECRET'),
    metaVerifyToken: required(env, 'META_VERIFY_TOKEN'),
    metaGraphVersion: env.META_GRAPH_VERSION ?? 'v23.0',
    port: Number(env.PORT ?? 3001),
    sendMinIntervalMs: Number(env.SEND_MIN_INTERVAL_MS ?? 100),
  };
}

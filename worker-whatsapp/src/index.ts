import { Redis } from 'ioredis';
import { createStatusReporter } from './api-callback.ts';
import { loadConfig } from './config.ts';
import { createMessageHandler, runConsumer } from './consumer.ts';
import { log } from './log.ts';
import { createMetaClient } from './meta-client.ts';
import { createWebhookServer } from './webhook.ts';

const config = loadConfig();
const report = createStatusReporter(config.apiUrl, config.workerSecret);

const server = createWebhookServer({ appSecret: config.metaAppSecret, verifyToken: config.metaVerifyToken, report });
server.listen(config.port, () => log('info', 'webhook ouvindo', { port: config.port }));

// Sem keyPrefix do ioredis: o prefixo do Laravel vai explícito nas chaves.
const redis = new Redis(config.redisUrl, { maxRetriesPerRequest: null });
redis.on('error', (err) => log('error', 'redis indisponível', { error: String(err) }));
let stopping = false;
for (const signal of ['SIGINT', 'SIGTERM'] as const) {
  process.on(signal, () => {
    stopping = true;
    server.close();
  });
}

await runConsumer(
  redis,
  { outbound: `${config.redisPrefix}whatsapp:outbound`, processing: `${config.redisPrefix}whatsapp:processing` },
  createMessageHandler({
    send: createMetaClient(config.metaAccessToken, config.metaGraphVersion),
    report,
    minIntervalMs: config.sendMinIntervalMs,
  }),
  () => stopping,
);
redis.disconnect();
log('info', 'worker parado');

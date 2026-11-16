import { execSync } from 'node:child_process'
import { fileURLToPath } from 'node:url'

/**
 * Banco limpo + oficina demo (dono@demo.test / tecnico@demo.test, senha "password") e rate limits zerados.
 * Local roda pelo docker compose; no CI o PHP está no próprio runner (E2E_ARTISAN="php backend/artisan").
 */
export default function globalSetup() {
  const root = fileURLToPath(new URL('../..', import.meta.url))
  const bin = process.env.E2E_ARTISAN ?? 'docker compose exec -T app php artisan'
  const artisan = (cmd: string) => execSync(`${bin} ${cmd}`, { cwd: root, stdio: 'inherit' })
  artisan('migrate:fresh --force --seed --seeder=DemoSeeder')
  artisan('cache:clear')
}

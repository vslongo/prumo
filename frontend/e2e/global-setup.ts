import { execSync } from 'node:child_process'
import { fileURLToPath } from 'node:url'

/** Banco limpo + oficina demo (dono@demo.test / tecnico@demo.test, senha "password") e rate limits zerados. */
export default function globalSetup() {
  const root = fileURLToPath(new URL('../..', import.meta.url))
  const artisan = (cmd: string) => execSync(`docker compose exec -T app php artisan ${cmd}`, { cwd: root, stdio: 'inherit' })
  artisan('migrate:fresh --force --seed --seeder=DemoSeeder')
  artisan('cache:clear')
}

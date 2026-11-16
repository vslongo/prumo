import type { NextConfig } from 'next'
import path from 'node:path'
const api = (process.env.API_URL ?? 'http://localhost:8000').replace(/\/$/, '')
// Brand tokens live in ../docs and are intentionally shared with the backend.
const workspaceRoot = path.resolve(process.cwd(), '..')
const config: NextConfig = {
  turbopack: { root: workspaceRoot },
  outputFileTracingRoot: workspaceRoot,
  async rewrites() {
    return ['/api', '/sanctum', '/r2-local'].map(prefix => ({ source: `${prefix}/:path*`, destination: `${api}${prefix}/:path*` }))
  },
}
export default config

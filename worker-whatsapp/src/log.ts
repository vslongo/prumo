// Logs JSON numa linha. Nunca passe telefone inteiro: use maskPhone.
export function log(level: 'info' | 'warn' | 'error', msg: string, context: Record<string, unknown> = {}): void {
  const line = JSON.stringify({ level, msg, time: new Date().toISOString(), ...context });
  (level === 'error' ? console.error : console.log)(line);
}

export function maskPhone(phone: string): string {
  return `***${phone.slice(-4)}`;
}

export const sleep = (ms: number) => new Promise<void>((resolve) => setTimeout(resolve, ms));

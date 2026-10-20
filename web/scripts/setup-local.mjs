import { existsSync, readFileSync, writeFileSync } from 'node:fs';
import { randomBytes } from 'node:crypto';

// Comparte una clave privada entre Astro y PHP sin sobrescribir configuraciones existentes.
const phpConfig = new URL('../../gestion/config/web.local.php', import.meta.url);
const envFile = new URL('../.env', import.meta.url);
if (existsSync(envFile)) {
  console.log('Ya existe web/.env; se conserva su configuración.');
} else {
  const existing = existsSync(phpConfig) ? readFileSync(phpConfig, 'utf8') : '';
  const key =
    existing.match(/'key'\s*=>\s*'([a-f0-9]{64})'/)?.[1] || randomBytes(32).toString('hex');
  if (existing && !existing.includes(key))
    throw new Error('Revisa la clave PHP existente manualmente.');
  if (!existing) writeFileSync(phpConfig, `<?php\nreturn ['key' => '${key}'];\n`, { flag: 'wx' });
  writeFileSync(
    envFile,
    `BACKEND_URL=http://127.0.0.1:8080\nSITE_URL=http://127.0.0.1:4321\nBACKEND_API_KEY=${key}\n`,
    { flag: 'wx' },
  );
  console.log('Configuración local preparada. Las claves quedan excluidas de Git.');
}

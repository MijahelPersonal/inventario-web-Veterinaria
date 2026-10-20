import type { Catalog } from './types';

// Esta conexión se ejecuta exclusivamente en el servidor Astro, sin credenciales de MySQL.
export async function backend(path: string, options: RequestInit = {}) {
  const base = process.env.BACKEND_URL || import.meta.env.BACKEND_URL || 'http://127.0.0.1:8080';
  const key = process.env.BACKEND_API_KEY || import.meta.env.BACKEND_API_KEY || '';
  return fetch(new URL(path, base), {
    ...options,
    signal: AbortSignal.timeout(6000),
    headers: { Accept: 'application/json', 'X-Strukvet-Key': key, ...options.headers },
  });
}
export async function getCatalog(): Promise<Catalog & { connected: boolean }> {
  try {
    const response = await backend('/api/web/catalogo');
    if (!response.ok) throw new Error('Catálogo no disponible');
    return { ...(await response.json()), connected: true };
  } catch {
    return { products: [], services: [], veterinarians: [], connected: false };
  }
}
export const money = (value: string | number) =>
  new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(Number(value));

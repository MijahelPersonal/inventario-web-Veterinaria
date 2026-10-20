import type { APIRoute } from 'astro';
import { backend } from '../../../lib/backend';
export const GET: APIRoute = async ({ params }) => {
  if (!/^\d+$/.test(params.id || '')) return new Response(null, { status: 404 });
  try {
    const response = await backend(`/api/web/productos/${params.id}/imagen`);
    const type = response.headers.get('content-type') || '';
    if (!response.ok || !['image/jpeg', 'image/png', 'image/webp'].includes(type))
      return new Response(null, { status: 404 });
    return new Response(response.body, {
      headers: { 'Content-Type': type, 'Cache-Control': 'public,max-age=3600' },
    });
  } catch {
    return new Response(null, { status: 503 });
  }
};

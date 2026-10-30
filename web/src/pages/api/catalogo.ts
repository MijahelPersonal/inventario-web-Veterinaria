import type { APIRoute } from 'astro';
import { getCatalog } from '../../lib/backend';
export const GET: APIRoute = async () => {
  const catalog = await getCatalog();
  return Response.json(
    { products: catalog.products },
    { status: catalog.connected ? 200 : 503, headers: { 'Cache-Control': 'no-store' } },
  );
};

import type { APIRoute } from 'astro';
import { createHmac } from 'node:crypto';
import { backend } from '../../lib/backend';

// El navegador habla con Astro; la clave privada nunca forma parte de su JavaScript.
export const POST: APIRoute = async ({ request, url, clientAddress }) => {
  const origin = new URL(process.env.SITE_URL || import.meta.env.SITE_URL || url.origin).origin;
  if (request.headers.get('origin') !== origin)
    return Response.json({ error: 'Origen no permitido.' }, { status: 403 });
  if (Number(request.headers.get('content-length') || 0) > 20000)
    return Response.json({ error: 'Formulario demasiado extenso.' }, { status: 413 });
  try {
    const text = await request.text();
    if (text.length > 20000)
      return Response.json({ error: 'Formulario demasiado extenso.' }, { status: 413 });
    const input = JSON.parse(text);
    const key = process.env.BACKEND_API_KEY || import.meta.env.BACKEND_API_KEY || '';
    if (!key)
      return Response.json(
        { error: 'La recepción de solicitudes todavía no está configurada.' },
        { status: 503 },
      );
    const response = await backend('/api/web/solicitudes', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Client-Hash': createHmac('sha256', key).update(clientAddress).digest('hex'),
      },
      body: JSON.stringify(input),
    });
    const result = await response.json();
    return Response.json(result, {
      status: response.status,
      headers: { 'Cache-Control': 'no-store' },
    });
  } catch {
    return Response.json(
      { error: 'No pudimos enviar tu solicitud. Inténtalo nuevamente en unos minutos.' },
      { status: 503 },
    );
  }
};

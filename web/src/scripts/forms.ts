import { getOrderItems, clearSubmittedCart } from './cart';
// Comparte el envío validado de citas, pedidos y consultas de contacto.
export function mountForms() {
  document.querySelectorAll<HTMLFormElement>('.request-form').forEach((form) =>
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (!form.reportValidity()) return;
      const button = form.querySelector<HTMLButtonElement>('[type="submit"]')!;
      if (button.disabled) return;
      const feedback = form.querySelector<HTMLElement>('[data-feedback]')!;
      const oldText = button.textContent;
      button.disabled = true;
      button.textContent = 'Enviando…';
      feedback.hidden = true;
      try {
        const input = Object.fromEntries(new FormData(form).entries()) as Record<string, unknown>;
        input.kind = form.dataset.kind;
        input.privacy = input.privacy === 'on';
        form.dataset.requestKey ||= Array.from(crypto.getRandomValues(new Uint8Array(32)), (n) =>
          n.toString(16).padStart(2, '0'),
        ).join('');
        input.request_key = form.dataset.requestKey;
        if (input.kind === 'order') {
          if (!getOrderItems().length) throw new Error('Agrega un producto al carrito.');
          input.items = getOrderItems();
        }
        const response = await fetch('/api/solicitudes', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(input),
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.error || 'No se pudo enviar la solicitud.');
        feedback.textContent = `Recibimos tu solicitud. Código: ${result.reference}. El equipo se comunicará contigo para confirmar los detalles.`;
        feedback.className = 'form-feedback success';
        feedback.hidden = false;
        form.reset();
        delete form.dataset.requestKey;
        if (input.kind === 'order') {
          clearSubmittedCart();
          button.hidden = true;
        }
        feedback.scrollIntoView({
          block: 'center',
          behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth',
        });
      } catch (error) {
        feedback.textContent =
          error instanceof Error ? error.message : 'No pudimos enviar tu solicitud.';
        feedback.className = 'form-feedback error';
        feedback.hidden = false;
      } finally {
        button.disabled = false;
        button.textContent = oldText;
      }
    }),
  );
}

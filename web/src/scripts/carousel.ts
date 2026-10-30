export function mountCarousels() {
  document.querySelectorAll<HTMLElement>('[data-carousel]').forEach((root) => {
    const slides = Array.from(root.querySelectorAll<HTMLElement>('[data-slide]'));
    const dots = Array.from(root.querySelectorAll<HTMLButtonElement>('[data-slide-to]'));
    let index = 0;
    function show(next: number) {
      index = (next + slides.length) % slides.length;
      slides.forEach((slide, i) => (slide.hidden = i !== index));
      dots.forEach((dot, i) => dot.setAttribute('aria-pressed', String(i === index)));
      const counter = root.querySelector('[data-slide-counter]');
      if (counter)
        counter.textContent =
          String(index + 1).padStart(2, '0') + ' / ' + String(slides.length).padStart(2, '0');
    }
    root.querySelector('[data-slide-prev]')?.addEventListener('click', () => show(index - 1));
    root.querySelector('[data-slide-next]')?.addEventListener('click', () => show(index + 1));
    dots.forEach((dot, i) => dot.addEventListener('click', () => show(i)));
    root.addEventListener('keydown', (event) => {
      if (event.target instanceof HTMLButtonElement) {
        if (event.key === 'ArrowRight') {
          event.preventDefault();
          show(index + 1);
        }
        if (event.key === 'ArrowLeft') {
          event.preventDefault();
          show(index - 1);
        }
      }
    });
  });
}

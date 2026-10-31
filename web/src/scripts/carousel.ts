export function mountCarousels() {
  document.querySelectorAll<HTMLElement>('[data-carousel]').forEach((root) => {
    const slides = Array.from(root.querySelectorAll<HTMLElement>('[data-slide]'));
    const dots = Array.from(root.querySelectorAll<HTMLButtonElement>('[data-slide-to]'));
    const track = root.querySelector<HTMLElement>('.slides');
    if (!track || slides.length < 2) return;
    const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
    let index = 0;
    let timer: ReturnType<typeof setTimeout> | undefined;

    function schedule() {
      clearTimeout(timer);
      if (!reducedMotion.matches && !document.hidden)
        timer = setTimeout(() => show(index + 1), 6000);
    }
    function show(next: number) {
      index = (next + slides.length) % slides.length;
      track!.style.transform = `translateX(-${index * 100}%)`;
      slides.forEach((slide, i) => {
        slide.inert = i !== index;
        slide.setAttribute('aria-hidden', String(i !== index));
      });
      dots.forEach((dot, i) => dot.setAttribute('aria-pressed', String(i === index)));
      const counter = root.querySelector('[data-slide-counter]');
      if (counter)
        counter.textContent =
          String(index + 1).padStart(2, '0') + ' / ' + String(slides.length).padStart(2, '0');
      schedule();
    }
    slides.forEach((slide) => (slide.hidden = false));
    root.classList.add('carousel-ready');
    root.querySelector('[data-slide-prev]')?.addEventListener('click', () => show(index - 1));
    root.querySelector('[data-slide-next]')?.addEventListener('click', () => show(index + 1));
    dots.forEach((dot, i) => dot.addEventListener('click', () => show(i)));
    document.addEventListener('visibilitychange', schedule);
    window.addEventListener('pagehide', () => clearTimeout(timer));
    reducedMotion.addEventListener('change', schedule);
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
    show(0);
  });
}

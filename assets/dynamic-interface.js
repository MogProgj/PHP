// assets/dynamic-interface.js
// Dedicated to light-weight visual polish and motion separated from core logic.

document.addEventListener('DOMContentLoaded', () => {
  const doc = document;
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function initReveal() {
    const revealEls = doc.querySelectorAll('.reveal');
    if (!revealEls.length) return;
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('show');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12 });
    revealEls.forEach((el) => observer.observe(el));
  }

  function initAmbientBackground() {
    if (prefersReducedMotion) return;
    const host = doc.createElement('div');
    host.id = 'ambientCanvas';
    host.setAttribute('aria-hidden', 'true');
    document.body.appendChild(host);

    const bubbles = Array.from({ length: 6 }, (_, index) => {
      const bubble = doc.createElement('span');
      bubble.className = `ambient-bubble ambient-bubble--${index % 3}`;
      host.appendChild(bubble);
      return {
        el: bubble,
        x: Math.random() * window.innerWidth,
        y: Math.random() * window.innerHeight,
        r: 40 + Math.random() * 120,
        dx: (Math.random() * 0.4 + 0.1) * (Math.random() > 0.5 ? 1 : -1),
        dy: (Math.random() * 0.4 + 0.1) * (Math.random() > 0.5 ? 1 : -1)
      };
    });

    function tick() {
      for (const bubble of bubbles) {
        bubble.x += bubble.dx;
        bubble.y += bubble.dy;
        if (bubble.x < -bubble.r || bubble.x > window.innerWidth + bubble.r) bubble.dx *= -1;
        if (bubble.y < -bubble.r || bubble.y > window.innerHeight + bubble.r) bubble.dy *= -1;
        bubble.el.style.transform = `translate(${bubble.x}px, ${bubble.y}px)`;
        bubble.el.style.width = `${bubble.r}px`;
        bubble.el.style.height = `${bubble.r}px`;
      }
      requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);

    window.addEventListener('resize', () => {
      bubbles.forEach((bubble) => {
        bubble.x = Math.min(Math.max(bubble.x, 0), window.innerWidth);
        bubble.y = Math.min(Math.max(bubble.y, 0), window.innerHeight);
      });
    });
  }

  function initHoverFocus() {
    const list = doc.getElementById('messageList');
    if (!list) return;
    list.addEventListener('pointerenter', (event) => {
      const article = event.target.closest('.msg');
      if (!article) return;
      article.classList.add('msg--focused');
    }, true);
    list.addEventListener('pointerleave', (event) => {
      const article = event.target.closest('.msg');
      if (!article) return;
      article.classList.remove('msg--focused');
    }, true);
  }

  function initTicker() {
    const ticker = doc.querySelector('[data-community-ticker]');
    if (!ticker || prefersReducedMotion) return;
    let index = 0;
    const items = ticker.querySelectorAll('li');
    if (!items.length) return;

    setInterval(() => {
      items.forEach((item, i) => item.classList.toggle('is-active', i === index));
      index = (index + 1) % items.length;
    }, 3200);
  }

  initReveal();
  initAmbientBackground();
  initHoverFocus();
  initTicker();
});

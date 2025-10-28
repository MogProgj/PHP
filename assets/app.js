// assets/app.js
document.addEventListener('DOMContentLoaded', () => {
  /* ------------------ THEME PICKER (data-theme + localStorage) ------------------ */
  const picker = document.getElementById('themePicker');

  const setTheme = (t) => {
    document.body.setAttribute('data-theme', t);
    try { localStorage.setItem('theme', t); } catch {}
  };

  const saved = (() => {
    try { return localStorage.getItem('theme'); } catch { return null; }
  })() || 'dark';
/* Button ripple position (so ripple starts under cursor) */
document.addEventListener('pointerdown', (e)=>{
  const btn = e.target.closest('button');
  if (!btn) return;
  const rect = btn.getBoundingClientRect();
  btn.style.setProperty('--rx', `${e.clientX - rect.left}px`);
  btn.style.setProperty('--ry', `${e.clientY - rect.top}px`);
});

/* When vote returns, pop + flash the right counter (hook into your existing AJAX) */
// Find the place where you set upEl/dnEl.textContent after a vote.
// Immediately after updating numbers, add:
function animateVote(id, type){
  const el = document.getElementById(`${type === 'up' ? 'up' : 'down'}_${id}`);
  if (!el) return;
  el.classList.remove('count-pop','count-up','count-down');
  // force reflow to restart animation
  void el.offsetWidth;
  el.classList.add('count-pop', type==='up' ? 'count-up' : 'count-down');
}
/* AJAX compose: submit without reload, prepend new message with animation */
const compose = document.getElementById('composeForm');
if (compose){
  compose.addEventListener('submit', async (e)=>{
    e.preventDefault();
    const data = new FormData(compose);
    try{
      const res = await fetch(location.pathname + location.search, {
        method: 'POST',
        headers: { 'X-Requested-With':'fetch' },
        body: data
      });
      if (!res.ok) throw new Error(await res.text());
      const json = await res.json();
      if (!json.ok || !json.message) return;

      // Build the new message HTML (keeps your structure)
      const m = json.message;
      const html = `
        <article class="msg reveal show" id="msg_${m.id}">
          <div class="msg-head">
            <span class="badge">${escapeHtml(m.nickname)}</span>
            <span class="time">${escapeHtml(m.created_at)}</span>

            <form method="post" action="" class="actions" style="margin-left:auto;">
              <input type="hidden" name="id" value="${m.id}">
              <input type="hidden" name="type" value="up">
              <input type="hidden" name="action" value="react">
              <input type="hidden" name="csrf" value="${document.querySelector('input[name="csrf"]').value}">
              <button class="btn-outline" data-react="up" data-id="${m.id}">▲ <span id="up_${m.id}">${m.upvotes ?? 0}</span></button>
            </form>
            <form method="post" action="" class="actions">
              <input type="hidden" name="id" value="${m.id}">
              <input type="hidden" name="type" value="down">
              <input type="hidden" name="action" value="react">
              <input type="hidden" name="csrf" value="${document.querySelector('input[name="csrf"]').value}">
              <button class="btn-outline" data-react="down" data-id="${m.id}">▼ <span id="down_${m.id}">${m.downvotes ?? 0}</span></button>
            </form>
            <button class="btn-danger" data-delete data-id="${m.id}" data-snippet="${escapeHtml(m.body).slice(0,60)}">Delete</button>
          </div>
          <p>${nl2br(escapeHtml(m.body))}</p>
        </article>
      `;

      const listCard = document.querySelector('.card:nth-of-type(2) .msg')?.parentElement // section containing messages
                    || document.querySelector('.card:nth-of-type(2)'); // fallback
      const container = listCard?.querySelector('.msg') ? listCard : document.querySelector('.card:nth-of-type(2)');

      const section = container.querySelector('section') || container; // adapt to your markup
      (section || container).insertAdjacentHTML('afterbegin', html);

      // reset composer + little toast
      compose.reset();
      const hint = document.getElementById('countHint'); if (hint) hint.textContent = '0 / 240';
      showToast('Posted!');

    }catch(err){
      console.error('Add failed:', err);
      showToast('Could not post', true);
    }
  });
}

// helpers for HTML injection
function escapeHtml(s=''){ return s.replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
function nl2br(s=''){ return s.replace(/\n/g,'<br>'); }

// tiny toast
function showToast(text, danger=false){
  const t = document.createElement('div');
  t.textContent = text;
  t.style.cssText = `
    position:fixed; left:50%; top:18px; transform:translateX(-50%);
    background:${danger?'rgba(255,107,107,.95)':'rgba(61,220,151,.95)'};
    color:#0b0d12; padding:10px 14px; border-radius:10px; z-index:9999; box-shadow:0 10px 30px rgba(0,0,0,.35);
  `;
  document.body.appendChild(t);
  setTimeout(()=>{ t.style.transition='opacity .4s'; t.style.opacity='0'; setTimeout(()=>t.remove(), 400); }, 900);
}

  setTheme(saved);
  if (picker) {
    picker.value = saved;
    picker.addEventListener('change', () => setTheme(picker.value));
  }

  /* ------------------ AJAX REACT (no scroll / no reload) ------------------ */
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('button[data-react]');
    if (!btn) return;

    e.preventDefault();
    const form = btn.closest('form');
    if (!form) return;

    const data = new FormData(form);
    const id   = btn.dataset.id;

    try {
      const res = await fetch(location.pathname + location.search, {
        method: 'POST',
        headers: { 'X-Requested-With': 'fetch' },
        body: data
      });
      if (!res.ok) throw new Error(await res.text());
      const json = await res.json();
      if (json.ok) {
        const upEl = document.getElementById(`up_${id}`);
        const dnEl = document.getElementById(`down_${id}`);
        if (upEl) upEl.textContent = json.upvotes;
        if (dnEl) dnEl.textContent = json.downvotes;
        btn.animate([{ transform: 'scale(0.96)' }, { transform: 'scale(1)' }], { duration: 120, easing: 'ease-out' });
      }
    } catch (err) {
      console.error('Vote failed:', err);
    }
  });

  /* ------------------ Delete modal (open/close) ------------------ */
  const modal     = document.querySelector('#deleteModal');
  const modalForm = document.querySelector('#deleteForm');
  const modalMsg  = document.querySelector('#deletePreview');

  document.addEventListener('click', (e) => {
    const openBtn = e.target.closest('[data-delete]');
    if (openBtn && modal && modalForm && modalMsg) {
      e.preventDefault();
      modalMsg.textContent = openBtn.dataset.snippet || '';
      modalForm.querySelector('input[name="id"]').value = openBtn.dataset.id || '';
      modal.classList.add('open');
    }

    if (e.target.closest('[data-close]')) {
      if (modal) modal.classList.remove('open');
    }
  });

  /* ------------------ Flash auto-hide ------------------ */
  const flash = document.querySelector('.flash');
  if (flash) setTimeout(() => (flash.style.display = 'none'), 2500);

  /* ------------------ Search: clear shows all + Esc clears ------------------ */
  const qInput = document.querySelector('input[name="q"]');
  if (qInput) {
    const base = location.pathname || '';
    qInput.addEventListener('input', () => {
      if (qInput.value.trim() === '') location.href = base; // show everything
    });
    qInput.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') { qInput.value = ''; location.href = base; }
    });
  }

  /* ------------------ Reveal-on-scroll (optional polish) ------------------ */
  const rev = document.querySelectorAll('.reveal');
  if (rev.length) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((en) => { if (en.isIntersecting) en.target.classList.add('show'); });
    }, { threshold: .12 });
    rev.forEach((el) => io.observe(el));
  }
}
);// Reveal-on-scroll
const revealEls = document.querySelectorAll('.reveal');
if (revealEls.length) {
  const io = new IntersectionObserver((entries) => {
    entries.forEach((en) => {
      if (en.isIntersecting) en.target.classList.add('show');
    });
  }, { threshold: 0.12 });
  revealEls.forEach((el) => io.observe(el));
}


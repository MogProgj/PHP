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
    picker.value = savedTheme;
    picker.addEventListener('change', () => setTheme(picker.value));
  }

  // Button ripple anchor (only runs when not reduced motion)
  if (!prefersReducedMotion) {
    doc.addEventListener('pointerdown', (event) => {
      const button = event.target.closest('button');
      if (!button) return;
      const rect = button.getBoundingClientRect();
      button.style.setProperty('--rx', `${event.clientX - rect.left}px`);
      button.style.setProperty('--ry', `${event.clientY - rect.top}px`);
    }, { passive: true });
  }

  // Composer AJAX submit
  const composer = doc.getElementById('composerForm');
  if (composer) {
    composer.addEventListener('submit', async (event) => {
      event.preventDefault();
      const data = new FormData(composer);
      try {
        const response = await fetch(location.pathname + location.search, {
          method: 'POST',
          headers: { 'X-Requested-With': 'fetch' },
          body: data
        });
        if (!response.ok) throw new Error(await response.text());
        const payload = await response.json();
        if (!payload.ok || !payload.message) return;

        const messageList = doc.getElementById('messageList');
        if (messageList) {
          payload.message.comments = payload.message.comments || [];
          messageList.insertAdjacentHTML('afterbegin', renderMessage(payload.message));
          const empty = messageList.querySelector('.msg-empty');
          if (empty) empty.remove();
        }

        composer.reset();
        const hint = doc.getElementById('countHint');
        if (hint) hint.textContent = '0 / 240';
        showToast('Posted!');
      } catch (error) {
        console.error('Add failed:', error);
        showToast('Could not post', true);
      }
    });
  }

  // Comment submission (delegated)
  doc.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.classList.contains('comment-form')) {
      return;
    }
    event.preventDefault();

    const data = new FormData(form);
    const body = (data.get('body') || '').toString().trim();
    if (!body) {
      showToast('Comment cannot be empty', true);
      return;
    }

    const button = form.querySelector('button');
    if (button) button.disabled = true;

    try {
      const response = await fetch(location.pathname + location.search, {
        method: 'POST',
        headers: { 'X-Requested-With': 'fetch' },
        body: data
      });
      if (!response.ok) throw new Error(await response.text());
      const payload = await response.json();
      if (!payload.ok || !payload.comment) throw new Error(payload.error || 'Unable to save comment');
      const list = form.closest('.comments')?.querySelector('.comments-list');
      if (list) hydrateCommentList(list, renderComment(payload.comment));
      const textarea = form.querySelector('textarea[name="body"]');
      if (textarea) textarea.value = '';
      showToast('Comment posted!');
    } catch (error) {
      console.error('Comment failed:', error);
      showToast('Could not post comment', true);
    } finally {
      if (button) button.disabled = false;
    }
  });

  // Voting (delegated)
  doc.addEventListener('click', async (event) => {
    const button = event.target.closest('button[data-react]');
    if (!button) return;
    event.preventDefault();
    const form = button.closest('form');
    if (!form) return;
    const data = new FormData(form);
    const id = button.dataset.id;

    try {
      const response = await fetch(location.pathname + location.search, {
        method: 'POST',
        headers: { 'X-Requested-With': 'fetch' },
        body: data
      });
      if (!response.ok) throw new Error(await response.text());
      const payload = await response.json();
      if (!payload.ok) return;
      const upEl = doc.getElementById(`up_${id}`);
      const downEl = doc.getElementById(`down_${id}`);
      if (upEl) upEl.textContent = payload.upvotes;
      if (downEl) downEl.textContent = payload.downvotes;
      if (!prefersReducedMotion) {
        button.animate([{ transform: 'scale(0.94)' }, { transform: 'scale(1)' }], { duration: 160, easing: 'ease-out' });
      }
    } catch (error) {
      console.error('Vote failed:', error);
    }
  });

  // Delete modal open/close
  const modal = doc.getElementById('deleteModal');
  const modalForm = doc.getElementById('deleteForm');
  const modalMsg = doc.getElementById('deletePreview');
  doc.addEventListener('click', (event) => {
    const openButton = event.target.closest('[data-delete]');
    if (openButton && modal && modalForm && modalMsg) {
      event.preventDefault();
      modalMsg.textContent = openButton.dataset.snippet || '';
      const hidden = modalForm.querySelector('input[name="id"]');
      if (hidden) hidden.value = openButton.dataset.id || '';
      modal.classList.add('open');
    }
    if (event.target.closest('[data-close]')) {
      if (modal) modal.classList.remove('open');
    }
  });

  // Flash auto-hide
  const flash = doc.querySelector('.flash');
  if (flash) setTimeout(() => (flash.style.display = 'none'), 2500);

  // Search helpers
  const searchInput = doc.querySelector('input[name="q"]');
  if (searchInput) {
    const base = location.pathname || '';
    searchInput.addEventListener('input', () => {
      if (searchInput.value.trim() === '') location.href = base;
    });
    searchInput.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        searchInput.value = '';
        location.href = base;
      }
    });
  }
});


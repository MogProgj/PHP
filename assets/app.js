// assets/app.js
// Core interactivity for Cave of Conspiracies.
// Keeps mutation work small to avoid layout thrash and jank.

const htmlUtils = (() => {
  const escapeMap = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
  const escaper = /[&<>"']/g;
  return {
    escape(value = '') {
      return String(value).replace(escaper, (char) => escapeMap[char] ?? char);
    },
    nl2br(value = '') {
      return String(value).replace(/\n/g, '<br>');
    }
  };
})();

function showToast(text, danger = false) {
  const toast = document.createElement('div');
  toast.className = `toast ${danger ? 'toast--danger' : 'toast--ok'}`;
  toast.textContent = text;
  document.body.appendChild(toast);

  requestAnimationFrame(() => {
    toast.classList.add('toast--visible');
    setTimeout(() => {
      toast.classList.remove('toast--visible');
      setTimeout(() => toast.remove(), 260);
    }, 1400);
  });
}

function renderComment(comment) {
  if (!comment) return '';
  const nick = htmlUtils.escape(comment.nickname ?? 'Anon');
  const created = htmlUtils.escape(comment.created_at ?? '');
  const body = htmlUtils.nl2br(htmlUtils.escape(comment.body ?? ''));
  return `
    <article class="comment" data-comment-id="${comment.id}">
      <header class="comment-head">
        <span class="badge badge-comment">${nick}</span>
        <span class="time">${created}</span>
      </header>
      <p>${body}</p>
    </article>
  `;
}

function renderComments(comments) {
  if (!Array.isArray(comments) || comments.length === 0) {
    return '<p class="comment-empty muted">No comments yet.</p>';
  }
  return comments.map(renderComment).join('');
}

function renderMessage(message) {
  if (!message) return '';
  const csrf = document.querySelector('input[name="csrf"]')?.value ?? '';
  const communityName = htmlUtils.escape(message.community_name ?? 'General');
  const communitySlug = htmlUtils.escape(message.community_slug ?? 'general');
  const nickname = htmlUtils.escape(message.nickname ?? 'Anon');
  const created = htmlUtils.escape(message.created_at ?? '');
  const body = htmlUtils.nl2br(htmlUtils.escape(message.body ?? ''));
  const upvotes = Number.parseInt(message.upvotes ?? 0, 10) || 0;
  const downvotes = Number.parseInt(message.downvotes ?? 0, 10) || 0;

  const csrfField = csrf ? `<input type="hidden" name="csrf" value="${csrf}">` : '';

  return `
    <article class="msg reveal show" id="msg_${message.id}" data-community="${communitySlug}">
      <div class="msg-head">
        <span class="badge">${nickname}</span>
        <span class="community-tag" data-community="${communitySlug}">r/${communityName}</span>
        <span class="time">${created}</span>
        <form method="post" action="" class="actions" style="margin-left:auto;">
          <input type="hidden" name="id" value="${message.id}">
          <input type="hidden" name="type" value="up">
          <input type="hidden" name="action" value="react">
          ${csrfField}
          <button class="btn-outline" data-react="up" data-id="${message.id}">▲ <span id="up_${message.id}">${upvotes}</span></button>
        </form>
        <form method="post" action="" class="actions">
          <input type="hidden" name="id" value="${message.id}">
          <input type="hidden" name="type" value="down">
          <input type="hidden" name="action" value="react">
          ${csrfField}
          <button class="btn-outline" data-react="down" data-id="${message.id}">▼ <span id="down_${message.id}">${downvotes}</span></button>
        </form>
        <button class="btn-danger" data-delete data-id="${message.id}" data-snippet="${body.replace(/<br>/g, ' ').slice(0, 60)}">Delete</button>
      </div>
      <p>${body}</p>
      <section class="comments" data-message="${message.id}">
        <h3 class="comments-title">Comments</h3>
        <div class="comments-list" id="comments_${message.id}">
          ${renderComments(message.comments)}
        </div>
        <form method="post" action="" class="comment-form" data-message-id="${message.id}">
          <div class="row">
            <input type="text" name="nick" placeholder="Alias" maxlength="60" required>
            <button class="btn-outline">Comment</button>
          </div>
          <textarea name="body" placeholder="Share your take…" maxlength="240" required rows="3"></textarea>
          ${csrfField}
          <input type="hidden" name="message_id" value="${message.id}">
          <input type="hidden" name="action" value="comment">
        </form>
      </section>
    </article>
  `;
}

function hydrateCommentList(list, commentMarkup) {
  if (!list) return;
  const empty = list.querySelector('.comment-empty');
  if (empty) empty.remove();
  list.insertAdjacentHTML('beforeend', commentMarkup);
}

document.addEventListener('DOMContentLoaded', () => {
  const doc = document;

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Theme picker with localStorage persistence.
  const picker = doc.getElementById('themePicker');
  const setTheme = (value) => {
    if (!value) return;
    document.body.setAttribute('data-theme', value);
    try {
      localStorage.setItem('theme', value);
    } catch (_) {
      /* ignore quota errors */
    }
  };

  let savedTheme = 'dark';
  try {
    savedTheme = localStorage.getItem('theme') || savedTheme;
  } catch (_) {
    savedTheme = 'dark';
  }
  setTheme(savedTheme);
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


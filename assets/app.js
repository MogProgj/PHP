// assets/app.js

document.addEventListener('DOMContentLoaded', () => {
  const csrfValue = () => {
    const field = document.querySelector('input[name="csrf"]');
    return field ? field.value : '';
  };

  // Vote buttons via fetch
  document.addEventListener('click', async (event) => {
    const btn = event.target.closest('button[data-react]');
    if (!btn) {
      return;
    }
    event.preventDefault();
    const form = btn.closest('form');
    if (!form) {
      return;
    }
    try {
      const res = await fetch(location.pathname + location.search, {
        method: 'POST',
        headers: { 'X-Requested-With': 'fetch' },
        body: new FormData(form),
      });
      if (!res.ok) {
        throw new Error(await res.text());
      }
      const json = await res.json();
      if (json.ok) {
        const upEl = document.getElementById(`up_${json.id}`);
        const downEl = document.getElementById(`down_${json.id}`);
        if (upEl) upEl.textContent = json.upvotes;
        if (downEl) downEl.textContent = json.downvotes;
      }
    } catch (err) {
      console.error('Vote failed', err);
    }
  });

  // Delete modal open/close
  const modal = document.getElementById('deleteModal');
  const modalForm = document.getElementById('deleteForm');
  const modalPreview = document.getElementById('deletePreview');

  document.addEventListener('click', (event) => {
    if (!modal || !modalForm || !modalPreview) {
      return;
    }
    const trigger = event.target.closest('[data-delete]');
    if (trigger) {
      event.preventDefault();
      modalPreview.textContent = trigger.dataset.snippet || '';
      modalForm.querySelector('input[name="id"]').value = trigger.dataset.id || '';
      modal.classList.add('open');
      return;
    }
    if (event.target.closest('[data-close]')) {
      modal.classList.remove('open');
    }
  });

  // Comment submission via fetch
  document.addEventListener('submit', async (event) => {
    const form = event.target.closest('.comment-form');
    if (!form) {
      return;
    }
    event.preventDefault();
    const list = document.getElementById(`comments_${form.dataset.messageId}`);
    const data = new FormData(form);
    try {
      const res = await fetch(location.pathname + location.search, {
        method: 'POST',
        headers: { 'X-Requested-With': 'fetch' },
        body: data,
      });
      if (!res.ok) {
        throw new Error(await res.text());
      }
      const json = await res.json();
      if (!json.ok || !json.comment) {
        return;
      }
      appendComment(list, json.comment);
      form.reset();
      // restore csrf if server rotates tokens
      const csrf = form.querySelector('input[name="csrf"]');
      if (csrf && !csrf.value) {
        csrf.value = csrfValue();
      }
    } catch (err) {
      console.error('Comment failed', err);
    }
  });

  function appendComment(list, comment) {
    if (!list) {
      return;
    }
    const empty = list.querySelector('.comment-empty');
    if (empty) {
      empty.remove();
    }
    const article = document.createElement('article');
    article.className = 'comment';
    article.dataset.commentId = comment.id;
    article.innerHTML = `
      <header class="comment-head">
        <span class="badge badge-comment">${escapeHtml(comment.nickname)}</span>
        <span class="time">${escapeHtml(comment.created_at)}</span>
      </header>
      <p>${nl2br(escapeHtml(comment.body))}</p>
    `;
    list.appendChild(article);
  }

  function escapeHtml(value = '') {
    return value.replace(/[&<>"']/g, (ch) => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;',
    })[ch]);
  }

  function nl2br(value = '') {
    return value.replace(/\n/g, '<br>');
  }
});

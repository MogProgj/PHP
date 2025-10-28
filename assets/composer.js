// assets/composer.js
// Handles lightweight composer UI helpers kept separate for clarity.

document.addEventListener('DOMContentLoaded', () => {
  const textarea = document.getElementById('msgBox');
  const hint = document.getElementById('countHint');
  if (!textarea || !hint) return;

  const update = () => {
    const length = textarea.value.length;
    const max = textarea.maxLength || 240;
    hint.textContent = `${length} / ${max}`;
    hint.classList.toggle('count-limit', length > max * 0.85);
  };

  textarea.addEventListener('input', update);
  update();
});

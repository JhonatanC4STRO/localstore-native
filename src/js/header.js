// ════════════════════════════════════════
// Unified Header — User Dropdown Toggle
// ════════════════════════════════════════
(function () {
  const wrap = document.querySelector('.cl-user-wrap');
  const chip = document.querySelector('.cl-user-chip');

  if (!chip || !wrap) return;

  chip.addEventListener('click', (e) => {
    e.stopPropagation();
    wrap.classList.toggle('open');
  });

  document.addEventListener('click', (e) => {
    if (!wrap.contains(e.target)) {
      wrap.classList.remove('open');
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') wrap.classList.remove('open');
  });
})();

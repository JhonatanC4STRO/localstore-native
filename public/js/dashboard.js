const linkDashboard = document.getElementById('link-dashboard');
const linkPublicar = document.getElementById('link-publicar');
const linkMisProductos = document.getElementById('link-misproductos');

function setActiveLink() {
  const hash = window.location.hash;

  // Limpiar todos
  linkDashboard.classList.remove('active');
  linkPublicar.classList.remove('active');
  linkMisProductos.classList.remove('active');

  if (hash === "#misproductos") {
    linkMisProductos.classList.add('active');
  } else {
    linkDashboard.classList.add('active');
  }
}

window.addEventListener('load', setActiveLink);
window.addEventListener('hashchange', setActiveLink);


/* ── User dropdown toggle ── */
const wrap = document.getElementById('userMenuWrap');
const chip = document.getElementById('userChip');
if (chip) {
  chip.addEventListener('click', e => {
    e.stopPropagation();
    wrap.classList.toggle('open');
  });
  document.addEventListener('click', e => {
    if (!wrap.contains(e.target)) wrap.classList.remove('open');
  });
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') wrap.classList.remove('open');
  });
}

/* ── View toggle ── */
document.querySelectorAll('.vt-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.vt-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const grid = document.getElementById('productsGrid');
    if (btn.querySelector('.bi-list-ul')) {
      grid.style.gridTemplateColumns = '1fr';
      grid.querySelectorAll('.product-card').forEach(c => {
        c.style.display = 'flex';
      });
    } else {
      grid.style.gridTemplateColumns = '';
      grid.querySelectorAll('.product-card').forEach(c => {
        c.style.display = '';
      });
    }
  });
});


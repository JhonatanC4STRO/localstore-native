// ── Filter & search
  function filterProducts() {
    const query  = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const cards  = document.querySelectorAll('.product-card');
    let visible  = 0;

    cards.forEach(card => {
      const title   = card.dataset.title.toLowerCase();
      const cStatus = card.dataset.status;
      const matchQ  = title.includes(query);
      const matchS  = status === 'all' || cStatus === status;

      if (matchQ && matchS) {
        card.style.display = '';
        visible++;
      } else {
        card.style.display = 'none';
      }
    });

    document.getElementById('productCount').textContent = visible + ' producto' + (visible !== 1 ? 's' : '');
    document.getElementById('emptyState').classList.toggle('show', visible === 0);
    document.getElementById('productsGrid').style.display = visible === 0 ? 'none' : '';
  }

  // ── Tag filter
  function setTagFilter(status, el) {
    document.querySelectorAll('.tag').forEach(t => {
      t.style.opacity = '.55';
    });
    el.style.opacity = '1';
    document.getElementById('statusFilter').value = status;
    filterProducts();
    // Reset opacity
    setTimeout(() => document.querySelectorAll('.tag').forEach(t => t.style.opacity = ''), 10);
    el.style.fontWeight = '700';
  }

  // ── View toggle
  function setView(type, btn) {
    document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const grid = document.getElementById('productsGrid');
    if (type === 'list') {
      grid.style.gridTemplateColumns = '1fr';
    } else {
      grid.style.gridTemplateColumns = '';
    }
  }

  // ── Toast
  let toastTimer;
  function showToast(msg, type = 'green') {
    const toast = document.getElementById('toast');
    const icon  = toast.querySelector('i');
    document.getElementById('toastMsg').textContent = msg;
    icon.className = type === 'green' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
    toast.className = 'toast show ' + type;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 3000);
  }

  // ── Stat bar animation on load
  window.addEventListener('load', () => {
    document.querySelectorAll('.stat-bar-fill').forEach(bar => {
      const w = bar.style.width;
      bar.style.width = '0';
      setTimeout(() => {
        bar.style.transition = 'width .8s cubic-bezier(.4,0,.2,1)';
        bar.style.width = w;
      }, 300);
    });
  });

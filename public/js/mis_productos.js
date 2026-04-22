// ── Filter & search
  function filterProducts() {
    const searchEl = document.getElementById('searchInput');
    const statusEl = document.getElementById('statusFilter');
    const query  = (searchEl?.value || '').toLowerCase().trim();
    const status = statusEl?.value || 'all';
    const cards  = document.querySelectorAll('.product-card');
    let visible  = 0;

    cards.forEach(card => {
      const title   = (card.dataset.title  || '').toLowerCase();
      const cStatus = (card.dataset.status || '');
      const matchQ  = !query || title.includes(query);
      const matchS  = status === 'all' || cStatus === status;

      if (matchQ && matchS) {
        card.style.display = '';
        visible++;
      } else {
        card.style.display = 'none';
      }
    });

    const countEl = document.getElementById('productCount');
    if (countEl) countEl.textContent = visible + ' producto' + (visible !== 1 ? 's' : '');

    const grid = document.getElementById('productsGrid');
    const empty = document.getElementById('emptyState');
    const hasCards = cards.length > 0;
    if (grid)  grid.style.display  = (hasCards && visible === 0) ? 'none' : '';
    if (empty) empty.style.display = (hasCards && visible === 0) ? 'flex' : 'none';
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

  // ── Marcar / deshacer venta de un producto
  async function toggleProductSold(productId, btn) {
    const card = document.getElementById('pcard-' + productId);
    const wasSold = card ? (card.dataset.status === 'sold') : false;
    const action = wasSold ? 'undo' : 'mark';

    const confirmMsg = wasSold
      ? '¿Deshacer la venta? El producto volverá a estar disponible en la tienda.'
      : '¿Confirmar que este producto ya fue vendido? Se retirará de la tienda.';
    if (!confirm(confirmMsg)) return;

    const originalHtml = btn ? btn.innerHTML : '';
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Guardando...';
    }

    try {
      const res = await fetch('../../api/products/mark_sold.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ product_id: productId, action: action })
      });
      const data = await res.json().catch(() => ({}));

      if (!res.ok || !data.ok) {
        showToast(data.error || 'No se pudo actualizar el producto', 'red');
        if (btn) { btn.disabled = false; btn.innerHTML = originalHtml; }
        return;
      }

      if (card) {
        const nowSold = (data.status === 'vendido');
        card.dataset.status = nowSold ? 'sold' : 'active';
        const badge = card.querySelector('[data-badge]');
        if (badge) {
          badge.classList.remove('status-active', 'status-inactive', 'status-sold');
          if (nowSold) {
            badge.classList.add('status-sold');
            badge.textContent = '● Vendido';
          } else {
            badge.classList.add('status-active');
            badge.textContent = '● Activo';
          }
        }
        if (btn) {
          btn.disabled = false;
          btn.classList.remove('pc-btn-sold', 'pc-btn-undo');
          btn.classList.add(nowSold ? 'pc-btn-undo' : 'pc-btn-sold');
          btn.innerHTML = nowSold
            ? '<i class="bi bi-arrow-counterclockwise" data-sold-icon></i> <span data-sold-label>Deshacer venta</span>'
            : '<i class="bi bi-check2-circle" data-sold-icon></i> <span data-sold-label>Marcar vendido</span>';
        }
      }
      showToast(wasSold ? 'Venta deshecha — producto disponible' : 'Producto marcado como vendido');
      filterProducts();
    } catch (err) {
      showToast('Error de conexión', 'red');
      if (btn) { btn.disabled = false; btn.innerHTML = originalHtml; }
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


// ════════════════════════════════════════
// ── Hero search → redirect to allProduct ──
// ════════════════════════════════════════
(function () {
  const heroInput = document.getElementById('heroSearchInput');
  const heroBtn   = document.getElementById('heroSearchBtn');

  function heroSearch() {
    const q = heroInput?.value?.trim();
    const city = document.getElementById('heroSearchCity')?.value;
    
    if (!q && !city) {
      heroInput?.classList.add('shake');
      setTimeout(() => heroInput?.classList.remove('shake'), 500);
      return;
    }
    
    let url = 'products/all.php?';
    const params = new URLSearchParams();
    if (q) params.append('search', q);
    if (city) params.append('city', city);
    
    window.location.href = url + params.toString();
  }

  heroInput?.addEventListener('keydown', e => { if (e.key === 'Enter') heroSearch(); });
  heroBtn?.addEventListener('click', heroSearch);
})();

// ════════════════════════════════════════
// ── Swiper: Categories carousel ──
// ════════════════════════════════════════
const categoriesSwiper = new Swiper('.categories-swiper', {
  slidesPerView: 3,
  spaceBetween: 8,
  loop: true,
  freeMode: true,
  allowTouchMove: false,
  speed: 5000,
  autoplay: {
    delay: 0,
    disableOnInteraction: false,
  },
  breakpoints: {
    480: {
      slidesPerView: 4,
      spaceBetween: 10
    },
    768: {
      slidesPerView: 6,
      spaceBetween: 12
    },
    1024: {
      slidesPerView: 8,
      spaceBetween: 14
    },
    1280: {
      slidesPerView: 10,
      spaceBetween: 16
    },
  },
});

// ════════════════════════════════════════
// ── User dropdown toggle ──
// ════════════════════════════════════════
const wrap = document.getElementById('userMenuWrap');
const chip = document.getElementById('userChip');

if (chip) {
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
}

// ════════════════════════════════════════
// ── Category toggle ──
// ════════════════════════════════════════
document.querySelectorAll('.cat-card').forEach(card => {
  card.addEventListener('click', () => {
    const categoryId = card.getAttribute('data-id');
    if (!categoryId) return;

    // Redirigir a la página de todos los productos con el parámetro de categoría
    window.location.href = 'products/all.php?category=' + encodeURIComponent(categoryId);
  });
});

// ════════════════════════════════════════
// ── Condition tag toggle (sidebar filters) ──
// ════════════════════════════════════════
document.querySelectorAll('#filterCondition .cond-tag').forEach(tag => {
  tag.addEventListener('click', () => {
    document.querySelectorAll('#filterCondition .cond-tag').forEach(t => t.classList.remove('active'));
    tag.classList.add('active');
  });
});

// ════════════════════════════════════════
// ── Category row toggle visual ──
// ════════════════════════════════════════
document.querySelectorAll('#filterCategories .filter-opt').forEach(opt => {
  const cb = opt.querySelector('input[type="checkbox"]');
  opt.addEventListener('click', (e) => {
    if (e.target !== cb) {
      cb.checked = !cb.checked;
    }
    opt.classList.toggle('selected', cb.checked);
  });
});

// ════════════════════════════════════════
// ── Fav toggle (manejado por favorites.js) ──
// ════════════════════════════════════════

// ════════════════════════════════════════
// ── FILTERS FUNCTIONALITY ──
// ════════════════════════════════════════

const applyFiltersBtn  = document.getElementById('applyFiltersBtn');
const resetFiltersBtn  = document.getElementById('resetFiltersBtn');
const priceMinInput    = document.getElementById('priceMinInput');
const priceMaxInput    = document.getElementById('priceMaxInput');
const rangeSlider      = document.getElementById('rangeSlider');
const sortSelect       = document.getElementById('sortSelect');

// Sincronizar slider ↔ input máximo
rangeSlider?.addEventListener('input', (e) => {
  priceMaxInput.value = parseInt(e.target.value).toLocaleString('es-CO');
});
priceMaxInput?.addEventListener('change', (e) => {
  const val = parseInt(e.target.value.replace(/\./g, '').replace(/,/g, '')) || 10000000;
  rangeSlider.value = Math.min(val, 10000000);
});

// Recolectar valores de los filtros
function getFilterValues() {
  const categories = Array.from(document.querySelectorAll('#filterCategories input:checked'))
    .map(cb => cb.value);
  const priceMin = parseInt((priceMinInput?.value || '0').replace(/\./g, '').replace(/,/g, '')) || 0;
  const priceMax = parseInt((priceMaxInput?.value || '10000000').replace(/\./g, '').replace(/,/g, '')) || 10000000;
  const conditionTag = document.querySelector('#filterCondition .cond-tag.active');
  const condition = conditionTag?.getAttribute('data-condition') || 'all';
  const order = sortSelect?.value || 'recent';
  return { categories, price_min: priceMin, price_max: priceMax, condition, order };
}

// Obtener target grid (segunda .product-grid = productos recientes)
function getRecentGrid() {
  return document.querySelectorAll('.product-grid')[1] || document.querySelector('.product-grid');
}

// Mostrar loading en el grid
function setGridLoading(grid) {
  grid.style.opacity = '0.5';
  grid.style.pointerEvents = 'none';
}
function setGridLoaded(grid) {
  grid.style.opacity = '1';
  grid.style.pointerEvents = '';
}

// Aplicar filtros: Redirigir a all.php con los parámetros
async function applyFilters() {
  const filters = getFilterValues();
  const params = new URLSearchParams();
  
  // Agregar categorías
  filters.categories.forEach(cat => params.append('categories[]', cat));
  
  // Agregar otros filtros
  params.append('price_min', filters.price_min);
  params.append('price_max', filters.price_max);
  params.append('condition', filters.condition);
  params.append('order', filters.order);

  // Redirigir
  window.location.href = 'products/all.php?' + params.toString();
}

// Renderizar productos en el grid
function updateProductGrid(products, grid) {
  if (!grid) return;

  // Remover clases de animación temporalmente
  grid.classList.remove('animate__animated', 'animate__fadeIn');

  if (!products || products.length === 0) {
    grid.innerHTML = '<p style="text-align:center;padding:20px;color:var(--text-soft);">No hay productos que coincidan con los filtros.</p>';
    return;
  }

  /* ── Promo badge helper ───────────────────────────────── */
  const PROMO_BADGE = {
    premium:     { cls: 'promo-badge-premium',     icon: '💎', label: 'Premium' },
    recommended: { cls: 'promo-badge-recommended', icon: '🚀', label: 'Recomendado' },
    basic:       { cls: 'promo-badge-basic',        icon: '⭐', label: 'Destacado' },
  };

  grid.innerHTML = products.map(p => {
    const condL     = (p.condition_type || '').toLowerCase();
    const badge     = condL === 'nuevo' ? 'badge-new' : (condL === 'reacondicionado' ? 'badge-refurbished' : 'badge-used');
    const badgeTxt  = p.condition_type ? p.condition_type.charAt(0).toUpperCase() + p.condition_type.slice(1) : 'Usado';
    const imgPath   = p.image_url ? '../../public/uploads/products/' + p.image_url : null;
    const imgHTML   = imgPath
      ? `<img src="${imgPath}" alt="${p.title.replace(/"/g,'&quot;')}">`
      : '<div class="pc-img-ph"><i class="bi bi-box-seam"></i></div>';

    /* Promo badge overlay */
    const pb = p.promotion_type ? PROMO_BADGE[p.promotion_type] : null;
    const promoBadgeHTML = pb
      ? `<div class="promo-pill ${pb.cls}">${pb.icon} ${pb.label}</div>`
      : '';

    const catHTML = p.category_name
      ? `<div class="pc-cat"><i class="bi bi-tag-fill"></i> ${p.category_name}</div>`
      : '';

    return `
      <a href="products/detalle.php?id=${p.id}" style="display:contents;">
        <div class="product-card">
          <div class="pc-img">
            ${imgHTML}
            <div class="badge ${badge}">${badgeTxt}</div>
            ${promoBadgeHTML}
            <button class="fav-btn" data-product-id="${p.id}">
              <i class="bi ${Favorites.has(p.id) ? 'bi-heart-fill' : 'bi-heart'}"></i>
            </button>
          </div>
          <div class="pc-body">
            ${catHTML}
            <div class="pc-title">${p.title}</div>
            <div class="pc-price">$${p.price}</div>
            <div class="pc-meta">
              <div class="pc-meta-row"><i class="bi bi-geo-alt-fill"></i> Bogotá</div>
              <div class="pc-meta-row"><i class="bi bi-clock-fill"></i> ${p.time_label}</div>
            </div>
            <div class="pc-seller">
              <div class="seller-av">${p.seller_initials}</div>
              <div class="seller-name-txt">${p.seller_name}</div>
              <div class="seller-stars">
                ${(() => {
                  const rating = parseFloat(p.avg_rating) || 0;
                  const total = p.total_reviews || 0;
                  const fullStars = Math.floor(rating);
                  const halfStar = (rating - fullStars) >= 0.5;
                  const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);
                  let html = '';
                  for (let i = 0; i < fullStars; i++) html += '<i class="bi bi-star-fill"></i>';
                  if (halfStar) html += '<i class="bi bi-star-half"></i>';
                  for (let i = 0; i < emptyStars; i++) html += '<i class="bi bi-star"></i>';
                  html += `<span>${rating.toFixed(1)} (${total})</span>`;
                  return html;
                })()}
              </div>
            </div>
          </div>
        </div>
      </a>`;
  }).join('');

  // Forzar reflow para que el navegador detecte el cambio
  void grid.offsetHeight;

  // Restaurar clases de animación
  grid.classList.add('animate__animated', 'animate__fadeIn');

  // Re-vincular botones de favorito en las tarjetas nuevas
  if (typeof Favorites !== 'undefined') {
    Favorites.applyToButtons('.fav-btn');
    Favorites.bindButtons('.fav-btn', '../../');
  }
}

// Botón Aplicar filtros
applyFiltersBtn?.addEventListener('click', applyFilters);

// Botón Limpiar filtros
resetFiltersBtn?.addEventListener('click', () => {
  document.querySelectorAll('#filterCategories input[type="checkbox"]').forEach(cb => cb.checked = false);
  document.querySelectorAll('#filterCondition .cond-tag').forEach((t, i) => t.classList.toggle('active', i === 0));
  if (priceMinInput) priceMinInput.value = '0';
  if (priceMaxInput) priceMaxInput.value = '10.000.000';
  if (rangeSlider)   rangeSlider.value   = 10000000;
  if (sortSelect)    sortSelect.value     = 'recent';
  applyFilters();
});

// Ordenar por select
sortSelect?.addEventListener('change', applyFilters);

/* ════════════════════════════════════════════════════════════
   ANIMACIONES — Hero (carga inmediata) + Scroll
════════════════════════════════════════════════════════════ */

// ── Utilidad: animar un elemento ──────────────────────────
function triggerAnim(el, animClass, delay = 0, duration = '0.7s') {
  setTimeout(() => {
    el.style.animationDuration = duration;
    el.classList.add('animate__animated', animClass);
    el.classList.remove('sa-hidden');
  }, delay);
}

// ── Hero: animaciones al cargar (above the fold) ──────────
(function heroAnims() {
  const items = [
    ['.hero-badge',          'animate__fadeInDown',  0,    '0.6s'],
    ['.hero-content h1',     'animate__fadeInLeft',  150,  '0.8s'],
    ['.hero-content > p',    'animate__fadeInUp',    350,  '0.7s'],
    ['.hero-search',         'animate__fadeInUp',    520,  '0.7s'],
    ['.hero-stats',          'animate__fadeInUp',    700,  '0.7s'],
    ['.hero-illustration',   'animate__fadeInRight', 120,  '0.9s'],
  ];
  items.forEach(([sel, anim, delay, dur]) => {
    document.querySelectorAll(sel).forEach(el => {
      el.classList.add('sa-hidden');
      triggerAnim(el, anim, delay, dur);
    });
  });
})();

// ── Scroll: IntersectionObserver + animate.css ────────────
(function scrollAnims() {
  // [selector, animClass, baseDelay, staggerDelay]
  const cfg = [
    // Categorías
    { s: '.categories-section .section-header',                              a: 'animate__fadeInDown', d: 0   },
    { s: '.categories-section .swiper',                                      a: 'animate__fadeInUp',   d: 120 },

    // Patrocinados
    { s: '.sponsored-section .sponsored-eyebrow',                            a: 'animate__fadeInDown', d: 0   },
    { s: '.sponsored-section .section-title',                                a: 'animate__fadeInUp',   d: 80  },
    { s: '.sponsored-info-pill',                                             a: 'animate__fadeInUp',   d: 160 },
    { s: '.sp-card',                                                         a: 'animate__fadeInUp',   d: 0,  sg: 70  },

    // Productos cerca de ti
    { s: '.nearby-section .section-header',                                  a: 'animate__fadeInLeft', d: 0   },
    { s: '.nearby-section .product-card',                                    a: 'animate__fadeInUp',   d: 0,  sg: 65 },

    // Productos recientes — header + cards
    { s: '.content-area > .content-section:nth-child(2) .section-header',   a: 'animate__fadeInLeft', d: 0   },
    { s: '.content-area > .content-section:nth-child(2) .product-card',     a: 'animate__fadeInUp',   d: 0,  sg: 65 },

    // ¿Cómo funciona?
    { s: '.how-header',                                                      a: 'animate__fadeInDown', d: 0        },
    { s: '.how-step',                                                        a: 'animate__fadeInUp',   d: 0, sg: 140 },

    // CTA Banner
    { s: '.cta-banner-badge',                                                a: 'animate__fadeInLeft', d: 0   },
    { s: '.cta-banner h2',                                                   a: 'animate__fadeInLeft', d: 100 },
    { s: '.cta-banner-left > p',                                             a: 'animate__fadeInLeft', d: 220 },
    { s: '.cta-banner-actions',                                              a: 'animate__fadeInUp',   d: 340 },
    { s: '.cta-trust-row',                                                   a: 'animate__fadeInUp',   d: 460 },
    { s: '.cta-stat-card',                                                   a: 'animate__zoomIn',     d: 0,  sg: 80 },
  ];

  const observer = new IntersectionObserver((entries, obs) => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      const el        = entry.target;
      const animClass = el.dataset.saAnim;
      const delay     = parseInt(el.dataset.saDelay || 0);
      const dur       = el.dataset.saDur || '0.65s';
      triggerAnim(el, animClass, delay, dur);
      obs.unobserve(el);
    });
  }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });

  cfg.forEach(({ s, a, d = 0, sg = 0 }) => {
    document.querySelectorAll(s).forEach((el, i) => {
      el.classList.add('sa-hidden');
      el.dataset.saAnim  = a;
      el.dataset.saDelay = d + sg * i;
      observer.observe(el);
    });
  });
})();


/* ── LOGIC PRESERVED: thumbnail gallery ── */
    function changeMainImage(thumb, idx) {
      const mainImg = document.getElementById('mainImage');
      if (mainImg) mainImg.src = thumb.querySelector('img').src;
      document.querySelectorAll('.thumb-item').forEach(t => t.classList.remove('active'));
      thumb.classList.add('active');
      const counter = document.getElementById('imgCounter');
      if (counter) counter.textContent = idx;
    }

    /* ── Fav toggle (via favorites.js) ── */
    (async () => {
      if (typeof Favorites === 'undefined') return;
      const base = '../../../';
      await Favorites.load(base);
      Favorites.applyToButtons('#favBtn');
      Favorites.bindButtons('#favBtn', base);
    })();

    /* ── LEAFLET MAP — todos los productos con coordenadas ── */
    document.addEventListener('DOMContentLoaded', function () {
      if (typeof L === 'undefined' || !document.getElementById('productMap')) return;
      const products = window.ALL_PRODUCTS_MAP || [];
      if (!products.length) return;

      /* Centro: producto actual si tiene coords, si no Colombia */
      const centerLat  = window.PRODUCT_LAT ?? 4.5709;
      const centerLon  = window.PRODUCT_LON ?? -74.2973;
      const centerZoom = window.PRODUCT_LAT ? 14 : 6;

      const map = L.map('productMap', { zoomControl: true })
                   .setView([centerLat, centerLon], centerZoom);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a>',
        maxZoom: 19
      }).addTo(map);

      /* Helpers de markup */
      const escapeHtml = (s) => String(s || '')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;').replace(/'/g,'&#39;');

      const thumbHtml = (p) => p.thumb
        ? `<img src="../../../public/uploads/products/${p.thumb}" alt="${escapeHtml(p.title)}" class="mp-img">`
        : `<div class="mp-img mp-img-ph"><i class="bi bi-box-seam"></i></div>`;

      const liThumbHtml = (p) => p.thumb
        ? `<img src="../../../public/uploads/products/${p.thumb}" alt="" class="mp-li-thumb">`
        : `<div class="mp-li-thumb mp-li-thumb-ph"><i class="bi bi-box-seam"></i></div>`;

      /* Iconos: pin estilo "lágrima". Estructura con wrap para soportar badge de conteo. */
      const makePinHtml = (variant, opts = {}) => {
        const iconCls = opts.icon || (variant === 'current' ? 'bi-geo-alt-fill' : 'bi-tag-fill');
        const countBadge = opts.count > 1
          ? `<div class="map-pin-count">${opts.count}</div>` : '';
        return `<div class="map-pin-wrap">
                  <div class="map-pin map-pin--${variant}">
                    <i class="bi ${iconCls}"></i>
                  </div>
                  ${countBadge}
                </div>`;
      };

      /* Agrupar productos por coordenada (5 decimales ≈ 1m) */
      const groups = new Map();
      products.forEach(p => {
        const key = `${p.lat.toFixed(5)}|${p.lon.toFixed(5)}`;
        if (!groups.has(key)) groups.set(key, { lat: p.lat, lon: p.lon, items: [] });
        groups.get(key).items.push(p);
      });

      groups.forEach(g => {
        const isCurrent = g.items.some(it => it.id === window.PRODUCT_CURRENT_ID);
        const count     = g.items.length;
        const variant   = isCurrent ? 'current' : 'other';

        /* ── 1 producto: card individual ── */
        if (count === 1) {
          const p = g.items[0];
          const popup = `
            <div class="mp-card${isCurrent ? ' is-current' : ''}">
              <div class="mp-img-wrap">
                ${thumbHtml(p)}
                ${isCurrent ? '<div class="mp-current-badge"><i class="bi bi-geo-alt-fill"></i> Aquí</div>' : ''}
              </div>
              <div class="mp-body">
                <div class="mp-price">$${p.price}</div>
                <div class="mp-title">${escapeHtml(p.title)}</div>
                ${isCurrent
                  ? '<div class="mp-current-tag"><i class="bi bi-eye-fill"></i> Estás viendo este</div>'
                  : `<a href="detalle.php?id=${p.id}" class="mp-btn">
                       Ver producto <i class="bi bi-arrow-right"></i>
                     </a>`}
              </div>
            </div>`;

          const icon = L.divIcon({
            html: makePinHtml(variant),
            iconSize: variant === 'current' ? [38, 46] : [30, 38],
            iconAnchor: variant === 'current' ? [19, 46] : [15, 38],
            popupAnchor: variant === 'current' ? [0, -42] : [0, -34],
            className: ''
          });

          const m = L.marker([g.lat, g.lon], {
            icon, zIndexOffset: isCurrent ? 1000 : 0, riseOnHover: true
          }).bindPopup(popup, { maxWidth: 230, minWidth: 210, className: 'mp-popup', autoPan: true });

          m.addTo(map);
          if (isCurrent) m.openPopup();
          return;
        }

        /* ── Varios productos en la misma ubicación: pin con conteo + lista ── */
        // Producto actual primero, resto por id desc (más recientes primero)
        const sorted = [...g.items].sort((a, b) => {
          if (a.id === window.PRODUCT_CURRENT_ID) return -1;
          if (b.id === window.PRODUCT_CURRENT_ID) return  1;
          return b.id - a.id;
        });

        const listHtml = sorted.map(p => {
          const cur = p.id === window.PRODUCT_CURRENT_ID;
          const cta = cur
            ? '<span class="mp-li-cur"><i class="bi bi-eye-fill"></i> Viendo</span>'
            : `<a href="detalle.php?id=${p.id}" class="mp-li-link" aria-label="Ver ${escapeHtml(p.title)}">
                 <i class="bi bi-arrow-right"></i>
               </a>`;
          return `
            <div class="mp-li${cur ? ' is-current' : ''}">
              ${liThumbHtml(p)}
              <div class="mp-li-body">
                <div class="mp-li-price">$${p.price}</div>
                <div class="mp-li-title">${escapeHtml(p.title)}</div>
              </div>
              ${cta}
            </div>`;
        }).join('');

        const popup = `
          <div class="mp-card mp-card--multi${isCurrent ? ' is-current' : ''}">
            <div class="mp-multi-head">
              <i class="bi bi-shop"></i>
              <span>${count} productos en esta ubicación</span>
            </div>
            <div class="mp-list">${listHtml}</div>
          </div>`;

        const icon = L.divIcon({
          html: makePinHtml(variant, { icon: 'bi-shop', count }),
          iconSize:    isCurrent ? [42, 50] : [36, 44],
          iconAnchor:  isCurrent ? [21, 50] : [18, 44],
          popupAnchor: isCurrent ? [0, -46] : [0, -40],
          className: ''
        });

        const m = L.marker([g.lat, g.lon], {
          icon, zIndexOffset: isCurrent ? 1000 : 0, riseOnHover: true
        }).bindPopup(popup, {
          maxWidth: 280, minWidth: 240,
          className: 'mp-popup mp-popup--multi',
          autoPan: true
        });

        m.addTo(map);
        if (isCurrent) m.openPopup();
      });
    });


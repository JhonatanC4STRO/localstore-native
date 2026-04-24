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

      /* Iconos: pin estilo "tag" con color */
      const currentIcon = L.divIcon({
        html: `<div class="map-pin map-pin--current" aria-label="Producto actual">
                 <i class="bi bi-geo-alt-fill"></i>
               </div>`,
        iconSize: [38, 46], iconAnchor: [19, 46], popupAnchor: [0, -42], className: ''
      });
      const otherIcon = L.divIcon({
        html: `<div class="map-pin map-pin--other">
                 <i class="bi bi-tag-fill"></i>
               </div>`,
        iconSize: [30, 38], iconAnchor: [15, 38], popupAnchor: [0, -34], className: ''
      });

      products.forEach(p => {
        const isCurrent = p.id === window.PRODUCT_CURRENT_ID;
        const thumbHtml = p.thumb
          ? `<img src="../../../public/uploads/products/${p.thumb}"
                  alt="${(p.title || '').replace(/"/g,'&quot;')}"
                  class="mp-img">`
          : `<div class="mp-img mp-img-ph"><i class="bi bi-box-seam"></i></div>`;

        const popup = `
          <div class="mp-card${isCurrent ? ' is-current' : ''}">
            <div class="mp-img-wrap">
              ${thumbHtml}
              ${isCurrent ? '<div class="mp-current-badge"><i class="bi bi-geo-alt-fill"></i> Aquí</div>' : ''}
            </div>
            <div class="mp-body">
              <div class="mp-price">$${p.price}</div>
              <div class="mp-title">${p.title}</div>
              ${isCurrent
                ? '<div class="mp-current-tag"><i class="bi bi-eye-fill"></i> Estás viendo este</div>'
                : `<a href="detalle.php?id=${p.id}" class="mp-btn">
                     Ver producto <i class="bi bi-arrow-right"></i>
                   </a>`}
            </div>
          </div>`;

        const marker = L.marker([p.lat, p.lon], {
          icon: isCurrent ? currentIcon : otherIcon,
          zIndexOffset: isCurrent ? 1000 : 0,
          riseOnHover: true
        }).bindPopup(popup, {
          maxWidth: 230,
          minWidth: 210,
          className: 'mp-popup',
          closeButton: true,
          autoPan: true
        });

        marker.addTo(map);

        /* Abrir popup del producto actual automáticamente */
        if (isCurrent) {
          marker.on('add', () => marker.openPopup());
          marker.openPopup();
        }
      });
    });


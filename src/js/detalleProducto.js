/* ── LOGIC PRESERVED: thumbnail gallery ── */
    function changeMainImage(thumb, idx) {
      const mainImg = document.getElementById('mainImage');
      if (mainImg) mainImg.src = thumb.querySelector('img').src;
      document.querySelectorAll('.thumb-item').forEach(t => t.classList.remove('active'));
      thumb.classList.add('active');
      const counter = document.getElementById('imgCounter');
      if (counter) counter.textContent = idx;
    }

    /* ── Fav toggle ── */
    const favBtn = document.getElementById('favBtn');
    if (favBtn) {
      favBtn.addEventListener('click', () => {
        favBtn.classList.toggle('active');
        const icon = favBtn.querySelector('i');
        icon.className = favBtn.classList.contains('active') ? 'bi bi-heart-fill' : 'bi bi-heart';
        icon.style.color = favBtn.classList.contains('active') ? '#ef4444' : '';
      });
    }

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

      /* Iconos */
      const currentIcon = L.divIcon({
        html: `<div style="background:#16a34a;width:34px;height:34px;border-radius:50%;
                display:flex;align-items:center;justify-content:center;
                border:3px solid #fff;box-shadow:0 3px 14px rgba(0,0,0,.35);
                font-size:1.05rem;">📍</div>`,
        iconSize: [34, 34], iconAnchor: [17, 34], className: ''
      });
      const otherIcon = L.divIcon({
        html: `<div style="background:#3b82f6;width:24px;height:24px;border-radius:50%;
                display:flex;align-items:center;justify-content:center;
                border:2px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.25);
                font-size:.75rem;">🏷</div>`,
        iconSize: [24, 24], iconAnchor: [12, 24], className: ''
      });

      /* Cluster group */
      const cluster = L.markerClusterGroup({
        maxClusterRadius: 50,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true
      });

      products.forEach(p => {
        const isCurrent = p.id === window.PRODUCT_CURRENT_ID;
        const thumbHtml = p.thumb
          ? `<img src="../productos/uploads/${p.thumb}"
               style="width:100%;height:82px;object-fit:cover;border-radius:7px;margin-bottom:7px;display:block;">`
          : '';
        const popup = `
          <div style="min-width:160px;max-width:200px;font-family:'DM Sans',sans-serif;padding:2px;">
            ${thumbHtml}
            <div style="font-weight:700;font-size:.84rem;line-height:1.3;margin-bottom:3px;color:#0f172a;">
              ${p.title}
            </div>
            <div style="color:#16a34a;font-weight:800;font-size:.95rem;margin-bottom:9px;">
              $${p.price}
            </div>
            <a href="detalleProducto.php?id=${p.id}"
               style="display:block;text-align:center;background:#16a34a;color:#fff;
                      padding:6px 10px;border-radius:7px;font-size:.78rem;
                      text-decoration:none;font-weight:700;">
              Ver producto →
            </a>
          </div>`;

        const marker = L.marker([p.lat, p.lon], {
          icon: isCurrent ? currentIcon : otherIcon,
          zIndexOffset: isCurrent ? 1000 : 0
        }).bindPopup(popup, { maxWidth: 210 });

        cluster.addLayer(marker);

        /* Abrir popup del producto actual automáticamente */
        if (isCurrent) {
          marker.on('add', () => marker.openPopup());
        }
      });

      map.addLayer(cluster);
    });

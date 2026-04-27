<?php
/* ══════════════════════════════════════════════════════════════
   city_map_modal.php
   Modal reutilizable para elegir la ciudad sobre un minimapa Leaflet.
   Se conecta al botón #manualCityBtn (banner de ciudad) y al
   "Mi ubicación" propio del modal. Cuando el usuario aplica una
   ciudad, recarga la URL actual con ?city=<ciudad>.

   Inclúyelo cerca del cierre del <body> de cualquier vista que ya
   tenga el banner de ciudad. NO requiere que Leaflet esté cargado:
   este modal lo trae bajo demanda la primera vez que se abre.
══════════════════════════════════════════════════════════════ */
?>
<!-- ══ CITY MAP MODAL ══ -->
<div id="cityMapModal" class="cmm-overlay" style="display:none;" aria-hidden="true">
  <div class="cmm-dialog" role="dialog" aria-modal="true" aria-labelledby="cmm-title">
    <div class="cmm-header">
      <div>
        <h3 id="cmm-title"><i class="bi bi-globe-americas" style="color:#16a34a;"></i> Elige tu ciudad en el mapa</h3>
        <p>Navega libremente y haz clic en cualquier ciudad. Doble clic = aplicar al instante.</p>
      </div>
      <button type="button" class="cmm-close" id="cmmClose" aria-label="Cerrar">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <div class="cmm-body">
      <div class="cmm-tools">
        <div class="cmm-search">
          <i class="bi bi-search"></i>
          <input type="text" id="cmmSearchInput" placeholder="O busca por nombre (ej. Florencia, Caquetá)" autocomplete="off">
        </div>
        <button type="button" class="cmm-btn cmm-btn-ghost" id="cmmGeoBtn">
          <i class="bi bi-crosshair"></i> Mi ubicación
        </button>
      </div>

      <div id="cmmMap" class="cmm-map">
        <div class="cmm-map-loading">Cargando mapa…</div>
        <div class="cmm-map-hint" id="cmmHint">
          <i class="bi bi-hand-index-thumb-fill"></i> Haz clic en cualquier ciudad
        </div>
      </div>

      <div class="cmm-detected" id="cmmDetected">
        <i class="bi bi-geo-alt-fill"></i>
        <span>Aún no has elegido ciudad. Haz clic en el mapa.</span>
      </div>
    </div>

    <div class="cmm-footer">
      <button type="button" class="cmm-btn cmm-btn-ghost" id="cmmCancel">Cancelar</button>
      <button type="button" class="cmm-btn cmm-btn-primary" id="cmmApply" disabled>
        <i class="bi bi-check2"></i> <span id="cmmApplyLabel">Aplicar ciudad</span>
      </button>
    </div>
  </div>
</div>

<style>
  .cmm-overlay {
    position: fixed; inset: 0;
    background: rgba(15,23,42,.55);
    z-index: 10000;
    display: flex; align-items: center; justify-content: center;
    padding: 16px;
  }
  .cmm-dialog {
    background: #fff; border-radius: 16px;
    width: 100%; max-width: 1000px; max-height: 94vh;
    display: flex; flex-direction: column;
    box-shadow: 0 24px 60px rgba(0,0,0,.25);
    overflow: hidden;
  }
  .cmm-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 12px; padding: 18px 20px 12px;
    border-bottom: 1px solid #f1f5f9;
  }
  .cmm-header h3 { margin:0; font-size:1.05rem; font-weight:800; color:#0f172a; }
  .cmm-header p  { margin:4px 0 0; font-size:.82rem; color:#64748b; }
  .cmm-close {
    background:#f1f5f9; border:none; border-radius:50%;
    width:34px; height:34px; cursor:pointer; color:#0f172a;
    display:inline-flex; align-items:center; justify-content:center;
  }
  .cmm-close:hover { background:#e2e8f0; }

  .cmm-body { padding: 14px 20px; display:flex; flex-direction:column; gap:12px; min-height:0; }
  .cmm-tools { display:flex; gap:10px; flex-wrap:wrap; }
  .cmm-search {
    flex:1; min-width: 220px;
    display:flex; align-items:center; gap:8px;
    border:1.5px solid #d4d4d8; border-radius:10px; padding:8px 12px;
    background:#fff;
  }
  .cmm-search i { color:#64748b; }
  .cmm-search input {
    border:none; outline:none; flex:1; font-size:.9rem; color:#0f172a;
  }
  .cmm-btn {
    background:#fff; border:1.5px solid #d4d4d8; color:#0f172a;
    border-radius:10px; padding:8px 14px; font-size:.84rem; font-weight:600;
    cursor:pointer; display:inline-flex; align-items:center; gap:6px;
    transition:all .15s ease;
  }
  .cmm-btn:hover:not(:disabled) { border-color:#16a34a; color:#16a34a; }
  .cmm-btn:disabled { opacity:.55; cursor:not-allowed; }
  .cmm-btn-primary { background:#16a34a; color:#fff; border-color:#16a34a; }
  .cmm-btn-primary:hover:not(:disabled) { background:#15803d; color:#fff; border-color:#15803d; }
  .cmm-btn-ghost { background:transparent; border-color:#e2e8f0; color:#475569; }

  .cmm-map {
    height: 60vh; min-height: 380px; max-height: 620px;
    border-radius:12px; overflow:hidden;
    background:#f1f5f9;
    position:relative;
    cursor: crosshair;
  }
  .cmm-map .leaflet-container { cursor: crosshair; }
  .cmm-map-loading {
    position:absolute; inset:0; display:flex; align-items:center; justify-content:center;
    color:#64748b; font-size:.9rem;
  }
  .cmm-map-hint {
    position:absolute; top:12px; left:50%; transform:translateX(-50%);
    z-index: 1000;
    background: rgba(15,23,42,.85); color:#fff;
    border-radius: 999px; padding: 7px 14px;
    font-size:.78rem; font-weight:600;
    display:inline-flex; align-items:center; gap:6px;
    pointer-events:none;
    transition: opacity .25s ease;
  }
  .cmm-map-hint.hidden { opacity: 0; }
  .cmm-detected {
    background:#f0fdf4; border:1.5px solid #bbf7d0; color:#15803d;
    border-radius:10px; padding:10px 14px;
    display:flex; align-items:center; gap:10px;
    font-size:.9rem; font-weight:600;
  }
  .cmm-detected.empty { background:#f8fafc; border-color:#e2e8f0; color:#64748b; }
  .cmm-detected.error { background:#fef2f2; border-color:#fca5a5; color:#dc2626; }

  .cmm-footer {
    display:flex; justify-content:flex-end; gap:10px;
    padding: 14px 20px 18px;
    border-top:1px solid #f1f5f9;
  }

  @media (max-width: 560px) {
    .cmm-overlay { padding: 0; }
    .cmm-dialog  { max-height: 100vh; height: 100vh; border-radius: 0; }
    .cmm-map { height: 50vh; min-height: 320px; }
    .cmm-tools { flex-direction: column; }
    .cmm-search, .cmm-tools .cmm-btn { width: 100%; justify-content:center; }
  }
</style>

<script>
(function cityMapModal(){
  const overlay   = document.getElementById('cityMapModal');
  const closeBtn  = document.getElementById('cmmClose');
  const cancelBtn = document.getElementById('cmmCancel');
  const applyBtn  = document.getElementById('cmmApply');
  const applyLabel = document.getElementById('cmmApplyLabel');
  const detected  = document.getElementById('cmmDetected');
  const detectedTxt = detected.querySelector('span');
  const searchInp = document.getElementById('cmmSearchInput');
  const geoBtn    = document.getElementById('cmmGeoBtn');
  const mapEl     = document.getElementById('cmmMap');
  const hintEl    = document.getElementById('cmmHint');

  // El botón del banner de ciudad abre el modal
  const openTriggers = document.querySelectorAll('#manualCityBtn');

  let map = null;
  let marker = null;
  let leafletPromise = null;
  let pendingCity = '';

  function loadLeaflet() {
    if (window.L) return Promise.resolve();
    if (leafletPromise) return leafletPromise;
    leafletPromise = new Promise((resolve, reject) => {
      // CSS
      if (!document.querySelector('link[data-leaflet]')) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://unpkg.com/leaflet/dist/leaflet.css';
        link.dataset.leaflet = '1';
        document.head.appendChild(link);
      }
      // JS
      const script = document.createElement('script');
      script.src = 'https://unpkg.com/leaflet/dist/leaflet.js';
      script.async = true;
      script.onload  = () => resolve();
      script.onerror = () => reject(new Error('No se pudo cargar Leaflet'));
      document.head.appendChild(script);
    });
    return leafletPromise;
  }

  function setDetected(state, label) {
    detected.classList.remove('empty','error');
    if (state === 'empty') detected.classList.add('empty');
    if (state === 'error') detected.classList.add('error');
    detectedTxt.textContent = label;
    if (state === 'ok') {
      pendingCity = label;
      applyBtn.disabled = false;
      applyLabel.textContent = `Aplicar "${label}"`;
    } else {
      pendingCity = '';
      applyBtn.disabled = true;
      applyLabel.textContent = 'Aplicar ciudad';
    }
  }

  function reverseGeocode(lat, lon) {
    setDetected('empty', 'Identificando ciudad…');
    const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lon}&zoom=10&addressdetails=1&accept-language=es`;
    return fetch(url, { headers:{ 'Accept':'application/json' } })
      .then(r => r.json())
      .then(data => {
        const a = data.address || {};
        const city = (a.city || a.town || a.village || a.municipality || a.county || a.state_district || a.state || '').trim();
        if (city) setDetected('ok', city);
        else      setDetected('error', 'No se pudo identificar la ciudad. Prueba otro punto.');
        return city;
      })
      .catch(() => setDetected('error', 'Error consultando el servicio de ubicación.'));
  }

  function setMarker(lat, lon, fly = true) {
    if (!map) return;
    if (!marker) {
      marker = L.marker([lat, lon], { draggable: true }).addTo(map);
      marker.on('dragend', e => {
        const p = e.target.getLatLng();
        reverseGeocode(p.lat, p.lng);
      });
    } else {
      marker.setLatLng([lat, lon]);
    }
    if (fly) map.flyTo([lat, lon], Math.max(map.getZoom(), 11), { duration: .6 });
  }

  function initMap() {
    return loadLeaflet().then(() => {
      if (map) { map.invalidateSize(); return; }
      mapEl.querySelector('.cmm-map-loading')?.remove();

      // Vista mundial inicial — usuario navega libre y elige ciudad con un clic
      map = L.map(mapEl, {
        zoomControl: true,
        worldCopyJump: true,
        minZoom: 2,
        maxZoom: 19,
        scrollWheelZoom: true,
        doubleClickZoom: false,   // doble clic lo usamos para aplicar la ciudad
      }).setView([10, -50], 3);   // vista mundial

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 19,
      }).addTo(map);

      // Clic simple → detectar ciudad
      map.on('click', (e) => {
        hintEl?.classList.add('hidden');
        setMarker(e.latlng.lat, e.latlng.lng, false);
        reverseGeocode(e.latlng.lat, e.latlng.lng);
      });

      // Doble clic → detectar y aplicar al instante
      map.on('dblclick', (e) => {
        hintEl?.classList.add('hidden');
        setMarker(e.latlng.lat, e.latlng.lng, false);
        reverseGeocode(e.latlng.lat, e.latlng.lng).then(city => {
          if (city) applyCity();
        });
      });

      // Centrar en ciudad activa o ubicación del usuario al abrir
      const u = new URL(window.location.href);
      const current = u.searchParams.get('city') || u.searchParams.get('location') || window.__activeCity || '';
      if (current) {
        forwardGeocode(current).then(res => {
          if (res) {
            setMarker(res.lat, res.lon, true);
            setDetected('ok', res.label);
          }
        });
      } else if ('geolocation' in navigator) {
        // Sin ciudad activa: pre-centrar suavemente en la ubicación del usuario (sin pin)
        navigator.geolocation.getCurrentPosition(
          (pos) => { map.setView([pos.coords.latitude, pos.coords.longitude], 6); },
          () => {},
          { enableHighAccuracy:false, timeout:5000, maximumAge:600000 }
        );
      }
    });
  }

  function forwardGeocode(query) {
    const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&q=${encodeURIComponent(query)}&addressdetails=1&limit=1&accept-language=es`;
    return fetch(url, { headers:{ 'Accept':'application/json' } })
      .then(r => r.json())
      .then(arr => {
        if (!arr || !arr.length) return null;
        const r = arr[0];
        const a = r.address || {};
        const label = (a.city || a.town || a.village || a.municipality || a.county || a.state_district || a.state || query).trim();
        return { lat: parseFloat(r.lat), lon: parseFloat(r.lon), label };
      })
      .catch(() => null);
  }

  function openModal() {
    overlay.style.display = 'flex';
    overlay.setAttribute('aria-hidden','false');
    document.body.style.overflow = 'hidden';
    hintEl?.classList.remove('hidden');
    setDetected('empty', 'Aún no has elegido ciudad. Haz clic en el mapa.');
    initMap();
  }

  function closeModal() {
    overlay.style.display = 'none';
    overlay.setAttribute('aria-hidden','true');
    document.body.style.overflow = '';
  }

  function applyCity() {
    if (!pendingCity) return;
    sessionStorage.setItem('detectedCity', pendingCity);
    sessionStorage.removeItem('cityAutoSkip');
    const u = new URL(window.location.href);
    u.searchParams.set('city', pendingCity);
    window.location.assign(u.toString());
  }

  // Triggers para abrir el modal
  openTriggers.forEach(btn => {
    btn.addEventListener('click', (e) => {
      // Reemplazamos el comportamiento del banner: en lugar del input inline, abrimos el modal
      e.preventDefault();
      e.stopPropagation();
      openModal();
    }, { capture: true });
  });

  closeBtn.addEventListener('click', closeModal);
  cancelBtn.addEventListener('click', closeModal);
  overlay.addEventListener('click', (e) => { if (e.target === overlay) closeModal(); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && overlay.style.display === 'flex') closeModal(); });

  applyBtn.addEventListener('click', applyCity);

  // Mi ubicación
  geoBtn.addEventListener('click', () => {
    if (!('geolocation' in navigator)) {
      setDetected('error', 'Tu navegador no soporta geolocalización.');
      return;
    }
    setDetected('empty', 'Obteniendo tu ubicación…');
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        setMarker(pos.coords.latitude, pos.coords.longitude, true);
        reverseGeocode(pos.coords.latitude, pos.coords.longitude);
      },
      (err) => {
        const msg = err.code === 1 ? 'Permiso de ubicación denegado'
                  : err.code === 3 ? 'La detección tardó demasiado'
                  : 'No se pudo obtener tu ubicación';
        setDetected('error', msg);
      },
      { enableHighAccuracy: false, timeout: 10000, maximumAge: 600000 }
    );
  });

  // Búsqueda por texto (Enter o cuando deja de escribir)
  let searchTimer = null;
  function runSearch() {
    const q = searchInp.value.trim();
    if (!q) return;
    setDetected('empty', `Buscando "${q}"…`);
    forwardGeocode(q).then(res => {
      if (!res) { setDetected('error', 'No encontramos esa ciudad.'); return; }
      setMarker(res.lat, res.lon, true);
      setDetected('ok', res.label);
    });
  }
  searchInp.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); runSearch(); }
  });
  searchInp.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(runSearch, 700);
  });
})();
</script>

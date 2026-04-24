/* ── User dropdown ── */
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
                if (e.key === 'Escape') {
                    wrap.classList.remove('open');
                    document.getElementById('deleteModal').classList.remove('show');
                }
            });
        }

        /* ── Close modal on overlay click ── */
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('show');
        });

        /* ── LOGIC PRESERVED: geolocation + Leaflet ── */
        const estado = document.getElementById("est");
        const btn = document.getElementById("btnUbicacion");
        const cityInput  = document.getElementById("city");
        const cityChip   = document.getElementById("cityChip");
        const cityNameEl = document.getElementById("cityName");
        let map;
        let cityAbort = null;
        let cityFetchTimer = null;

        /* ── Reverse geocoding: lat/lon → ciudad (Nominatim / OpenStreetMap) ── */
        function setCityChip(state, label) {
          if (!cityChip) return;
          cityChip.classList.remove('loading', 'error');
          if (state === 'loading') {
            cityChip.classList.add('loading');
            cityNameEl.textContent = label || 'Detectando ciudad…';
            cityChip.querySelector('i').className = 'bi bi-arrow-clockwise';
            cityChip.style.display = 'flex';
          } else if (state === 'ok') {
            cityNameEl.textContent = label || '—';
            cityChip.querySelector('i').className = 'bi bi-geo-fill';
            cityChip.style.display = 'flex';
          } else if (state === 'error') {
            cityChip.classList.add('error');
            cityNameEl.textContent = label || 'No pudimos detectar la ciudad';
            cityChip.querySelector('i').className = 'bi bi-exclamation-triangle-fill';
            cityChip.style.display = 'flex';
          } else {
            cityChip.style.display = 'none';
          }
        }
        function fetchCity(lat, lon) {
          clearTimeout(cityFetchTimer);
          cityFetchTimer = setTimeout(() => {
            if (cityAbort) cityAbort.abort();
            cityAbort = new AbortController();
            setCityChip('loading');
            const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lon}&zoom=10&addressdetails=1&accept-language=es`;
            fetch(url, { signal: cityAbort.signal, headers: { 'Accept': 'application/json' } })
              .then(r => r.json())
              .then(data => {
                const a = data.address || {};
                const city = a.city || a.town || a.village || a.municipality
                          || a.county || a.state_district || a.state || '';
                if (city) {
                  if (cityInput) cityInput.value = city;
                  setCityChip('ok', city);
                } else {
                  if (cityInput) cityInput.value = '';
                  setCityChip('error', 'No identificamos la ciudad — el producto se guardará sin ciudad');
                }
              })
              .catch(err => {
                if (err.name === 'AbortError') return;
                setCityChip('error');
              });
          }, 600);
        }

        /* usa window.PRODUCT_LAT/LON inyectados desde PHP */
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof L !== 'undefined') {
                if (window.PRODUCT_LAT !== null && window.PRODUCT_LON !== null) {
                    map = L.map('map').setView([window.PRODUCT_LAT, window.PRODUCT_LON], 15);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap'
                    }).addTo(map);
                    L.marker([window.PRODUCT_LAT, window.PRODUCT_LON]).addTo(map).bindPopup("📍 Ubicación actual").openPopup();
                } else {
                    map = L.map('map').setView([4.711, -74.0721], 11);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap'
                    }).addTo(map);
                }
            }
        });

        btn.addEventListener("click", () => {
            if (!navigator.geolocation) {
                estado.textContent = "Geolocalización no soportada.";
                return;
            }
            estado.innerHTML = '<i class="bi bi-hourglass-split"></i> Obteniendo ubicación...';
            navigator.geolocation.getCurrentPosition((pos) => {
                const lat = pos.coords.latitude;
                const lon = pos.coords.longitude;
                document.getElementById("latitude").value = lat;
                document.getElementById("longitude").value = lon;
                estado.innerHTML = `<i class="bi bi-geo-alt-fill" style="color:var(--g500);"></i> Lat ${lat.toFixed(5)}, Lon ${lon.toFixed(5)}`;
                if (map) {
                    map.remove();
                }
                map = L.map('map').setView([lat, lon], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);
                L.marker([lat, lon]).addTo(map).bindPopup("📍 Estás aquí").openPopup();
                markDone('chk-ubicacion');
                fetchCity(lat, lon);
            }, (err) => {
                estado.innerHTML = `<i class="bi bi-exclamation-circle" style="color:#dc2626;"></i> ${err.message}`;
            });
        });

        /* ── CONDITION TOGGLE ── */
        function selectCondition(el, val) {
            document.querySelectorAll('.cond-opt').forEach(o => o.classList.remove('active'));
            el.classList.add('active');
            document.getElementById('condicionHidden').value = val;
            const badge = document.getElementById('previewCond');
            badge.textContent = val === 'nuevo' ? 'Nuevo' : 'Usado';
            badge.style.background = val === 'nuevo' ? 'var(--g500)' : 'var(--y400)';
            badge.style.color = val === 'nuevo' ? '#fff' : 'var(--g900)';
        }

        /* ── LIVE PREVIEW ── */
        const titleInput = document.getElementById('nombre');
        const precioInput = document.getElementById('precio');

        titleInput.addEventListener('input', () => {
            document.getElementById('previewTitle').textContent = titleInput.value || 'Título del producto';
            toggle('chk-titulo', !!titleInput.value.trim());
        });

        precioInput.addEventListener('input', () => {
            const v = parseInt(precioInput.value) || 0;
            document.getElementById('previewPrice').textContent = v ? `$${v.toLocaleString('es-CO')}` : '$0';
            toggle('chk-precio', v > 0);
        });

        document.getElementById('descripcion').addEventListener('input', function() {
            toggle('chk-descripcion', this.value.length > 10);
        });

        document.getElementById('categoria').addEventListener('change', function() {
            toggle('chk-categoria', !!this.value);
        });

        /* ── REMOVE EXISTING IMAGE ── */
        function removeExistingImg(btn, imgId) {
            const card = btn.closest('.existing-img-card');
            card.style.opacity = '0';
            card.style.transform = 'scale(.85)';
            card.style.transition = 'all .25s';
            setTimeout(() => card.remove(), 250);
            const hidden = document.getElementById('keep_img_' + imgId);
            if (hidden) hidden.remove();
            // if no more existing images, uncheck checklist
            const remaining = document.querySelectorAll('.existing-img-card').length - 1;
            if (remaining === 0 && document.querySelectorAll('.img-thumb img').length === 0) {
                toggle('chk-fotos', false);
            }
        }

        /* ── DRAG & DROP NEW IMAGES ── */
        const dropZone = document.getElementById('dropZone');
        const fotosInput = document.getElementById('fotos');
        const gridEl = document.getElementById('imgPreviewGrid');

        dropZone.addEventListener('dragover', e => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            dropZone.classList.remove('dragover');

            const droppedFiles = e.dataTransfer.files;
            if (!droppedFiles.length) return;

            // Asignar archivos al input para que se envíen con el formulario
            const dt = new DataTransfer();
            Array.from(droppedFiles).slice(0, 8).forEach(f => dt.items.add(f));
            fotosInput.files = dt.files;

            handleFiles(fotosInput.files);
        });
        fotosInput.addEventListener('change', () => handleFiles(fotosInput.files));

        function handleFiles(files) {
            if (!files.length) return;
            gridEl.style.display = 'grid';
            gridEl.innerHTML = '';
            Array.from(files).slice(0, 8).forEach((f, i) => {
                const reader = new FileReader();
                reader.onload = ev => {
                    const div = document.createElement('div');
                    div.className = 'img-thumb';
                    div.innerHTML = `<img src="${ev.target.result}" alt="">` + (i === 0 ? '<div class="main-badge">Nueva</div>' : '');
                    gridEl.appendChild(div);
                    if (i === 0) {
                        const slot = document.getElementById('previewImgSlot');
                        const existing = slot.querySelector('img');
                        if (existing) existing.src = ev.target.result;
                        else {
                            slot.innerHTML = `<img id="previewMainImg" src="${ev.target.result}" alt=""><div class="preview-cond-badge" id="previewCond">${document.getElementById('condicionHidden').value}</div>`;
                        }
                    }
                };
                reader.readAsDataURL(f);
            });
            markDone('chk-fotos');
        }

        /* ── CHECKLIST HELPERS ── */
        function markDone(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.add('done');
            el.querySelector('i').className = 'bi bi-check-circle-fill';
        }

        function toggle(id, done) {
            const el = document.getElementById(id);
            if (!el) return;
            if (done) {
                el.classList.add('done');
                el.querySelector('i').className = 'bi bi-check-circle-fill';
            } else {
                el.classList.remove('done');
                el.querySelector('i').className = 'bi bi-circle';
            }
        }

        /* ── SHOW SUCCESS TOAST after form submit (demo) ── */
        document.getElementById('editForm').addEventListener('submit', function() {
            // In a real scenario this shows after redirect; here we show optimistically
            setTimeout(() => document.getElementById('successToast').classList.add('show'), 600);
        });


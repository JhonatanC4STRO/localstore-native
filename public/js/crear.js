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

        /* ── Geolocation + Leaflet map ── */
        const estado = document.getElementById("est");
        const btn = document.getElementById("btnUbicacion");
        const mainForm = document.getElementById("mainForm");
        const latInput = document.getElementById("latitude");
        const lonInput = document.getElementById("longitude");
        const cityInput = document.getElementById("city");
        const cityChip = document.getElementById("cityChip");
        const cityNameEl = document.getElementById("cityName");
        let map, marker;
        let cityAbort = null;
        let cityFetchTimer = null;

        /* ── Reverse geocoding: lat/lon → ciudad (Nominatim / OpenStreetMap) ──
           Debounce 600ms para no spamear el servicio si el usuario arrastra el pin. */
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
            fetch(url, {
              signal: cityAbort.signal,
              headers: { 'Accept': 'application/json' }
            })
              .then(r => r.json())
              .then(data => {
                const a = data.address || {};
                // Limpia prefijos administrativos (DANE Colombia)
                const clean = (raw) => {
                  if (!raw) return '';
                  let s = String(raw).trim();
                  s = s.replace(/^per[ií]metro\s+urbano\s+/i, '');
                  s = s.replace(/^centro\s+urbano\s+/i, '');
                  s = s.replace(/^zona\s+urbana\s+(de\s+)?/i, '');
                  s = s.replace(/^municipio\s+(de\s+)?/i, '');
                  s = s.replace(/^localidad\s+(de\s+)?/i, '');
                  s = s.replace(/^corregimiento\s+(de\s+)?/i, '');
                  s = s.replace(/^comuna\s+\d+\s+(de\s+)?/i, '');
                  return s.trim();
                };
                // Prioriza municipality (suele estar limpio en Colombia)
                let city = '';
                for (const c of [a.municipality, a.city, a.town, a.village, a.county, a.state_district, a.state]) {
                  const v = clean(c);
                  if (v) { city = v; break; }
                }
                if (city) {
                  cityInput.value = city;
                  setCityChip('ok', city);
                } else {
                  cityInput.value = '';
                  setCityChip('error', 'No identificamos la ciudad — el producto se guardará sin ciudad');
                }
              })
              .catch(err => {
                if (err.name === 'AbortError') return;
                cityInput.value = '';
                setCityChip('error');
              });
          }, 600);
        }

        function setStatus(kind, html) {
            const colors = {
                ok:      'color:var(--g500);',
                loading: 'color:var(--ink3);',
                error:   'color:#dc2626;',
                warn:    'color:#b45309;',
            };
            estado.style.cssText = colors[kind] || '';
            estado.innerHTML = html;
        }

        function renderMap(lat, lon, label) {
            if (map) { map.remove(); map = null; }
            map = L.map('map').setView([lat, lon], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap',
                maxZoom: 19,
            }).addTo(map);
            marker = L.marker([lat, lon], { draggable: true }).addTo(map).bindPopup(label || "📍 Aquí").openPopup();

            // Permite ajustar arrastrando el pin
            marker.on('dragend', (e) => {
                const p = e.target.getLatLng();
                latInput.value = p.lat;
                lonInput.value = p.lng;
                setStatus('ok', `<i class="bi bi-geo-alt-fill"></i> Lat ${p.lat.toFixed(5)}, Lon ${p.lng.toFixed(5)} <span style="color:var(--ink3);">· ajustado manualmente</span>`);
                fetchCity(p.lat, p.lng);
            });

            // Clic en el mapa reubica el pin
            map.on('click', (e) => {
                marker.setLatLng(e.latlng);
                latInput.value = e.latlng.lat;
                lonInput.value = e.latlng.lng;
                setStatus('ok', `<i class="bi bi-geo-alt-fill"></i> Lat ${e.latlng.lat.toFixed(5)}, Lon ${e.latlng.lng.toFixed(5)} <span style="color:var(--ink3);">· ajustado manualmente</span>`);
                markDone('chk-ubicacion');
                fetchCity(e.latlng.lat, e.latlng.lng);
            });
        }

        function describeGeoError(err) {
            // err.code: 1=PERMISSION_DENIED, 2=POSITION_UNAVAILABLE, 3=TIMEOUT
            switch (err && err.code) {
                case 1: return {
                    title: 'Permiso denegado',
                    msg:   'Bloqueaste el acceso a tu ubicación. Habilítalo en el candado 🔒 junto a la URL y vuelve a intentarlo, o marca la ubicación tocando el mapa.',
                };
                case 2: return {
                    title: 'Ubicación no disponible',
                    msg:   'No pudimos obtener tu ubicación (GPS apagado o sin señal). Intenta de nuevo o marca el punto manualmente en el mapa.',
                };
                case 3: return {
                    title: 'Tiempo agotado',
                    msg:   'La solicitud de ubicación tardó demasiado. Revisa tu conexión e inténtalo de nuevo.',
                };
                default: return {
                    title: 'Error de ubicación',
                    msg:   (err && err.message) || 'No se pudo obtener tu ubicación.',
                };
            }
        }

        // Mapa base neutro (Colombia) antes de pedir ubicación, para permitir clic manual
        function ensureBaseMap() {
            if (!map) {
                map = L.map('map').setView([4.65, -74.08], 12);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap', maxZoom: 19,
                }).addTo(map);
                map.on('click', (e) => {
                    if (!marker) {
                        marker = L.marker(e.latlng, { draggable: true }).addTo(map).bindPopup("📍 Aquí").openPopup();
                        marker.on('dragend', (ev) => {
                            const p = ev.target.getLatLng();
                            latInput.value = p.lat; lonInput.value = p.lng;
                            setStatus('ok', `<i class="bi bi-geo-alt-fill"></i> Lat ${p.lat.toFixed(5)}, Lon ${p.lng.toFixed(5)} <span style="color:var(--ink3);">· ajustado manualmente</span>`);
                            fetchCity(p.lat, p.lng);
                        });
                    } else {
                        marker.setLatLng(e.latlng);
                    }
                    latInput.value = e.latlng.lat;
                    lonInput.value = e.latlng.lng;
                    setStatus('ok', `<i class="bi bi-geo-alt-fill"></i> Lat ${e.latlng.lat.toFixed(5)}, Lon ${e.latlng.lng.toFixed(5)} <span style="color:var(--ink3);">· ajustado manualmente</span>`);
                    markDone('chk-ubicacion');
                    fetchCity(e.latlng.lat, e.latlng.lng);
                });
            }
        }
        ensureBaseMap();

        btn.addEventListener("click", () => {
            if (!navigator.geolocation) {
                setStatus('error', '<i class="bi bi-exclamation-circle"></i> Tu navegador no soporta geolocalización. Marca la ubicación tocando el mapa.');
                return;
            }

            // Aviso de contexto inseguro (HTTP). getCurrentPosition falla sin HTTPS salvo localhost.
            const isSecure = window.isSecureContext || ['localhost','127.0.0.1'].includes(location.hostname);
            if (!isSecure) {
                setStatus('warn', '<i class="bi bi-shield-exclamation"></i> La geolocalización requiere HTTPS. Marca el punto manualmente en el mapa.');
                return;
            }

            btn.disabled = true;
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Solicitando ubicación…';
            setStatus('loading', '<i class="bi bi-hourglass-split"></i> Obteniendo tu ubicación… acepta el permiso del navegador.');

            navigator.geolocation.getCurrentPosition((pos) => {
                const lat = pos.coords.latitude;
                const lon = pos.coords.longitude;
                const acc = Math.round(pos.coords.accuracy || 0);

                latInput.value = lat;
                lonInput.value = lon;
                setStatus('ok', `<i class="bi bi-geo-alt-fill"></i> Lat ${lat.toFixed(5)}, Lon ${lon.toFixed(5)} <span style="color:var(--ink3);">· precisión ≈ ${acc} m</span>`);

                renderMap(lat, lon, "📍 Estás aquí");
                markDone('chk-ubicacion');
                fetchCity(lat, lon);

                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-crosshair"></i> Actualizar mi ubicación';
            }, (error) => {
                const info = describeGeoError(error);
                setStatus('error', `<i class="bi bi-exclamation-circle"></i> <strong>${info.title}.</strong> ${info.msg}`);
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0,
            });
        });

        // Validar ubicación: ahora es OBLIGATORIA — bloqueamos el submit si falta.
        if (mainForm) {
            mainForm.addEventListener('submit', (e) => {
                // Quitar separadores de miles del precio antes de enviar
                const precioEl = document.getElementById('precio');
                if (precioEl) precioEl.value = precioEl.value.replace(/\./g, '');

                const lat = parseFloat(latInput.value);
                const lon = parseFloat(lonInput.value);
                const hasLocation = !isNaN(lat) && !isNaN(lon) && (lat !== 0 || lon !== 0)
                               && lat >= -90 && lat <= 90 && lon >= -180 && lon <= 180;

                if (!hasLocation) {
                    e.preventDefault();
                    setStatus('error', '<i class="bi bi-exclamation-circle-fill"></i> <strong>Falta marcar la ubicación.</strong> Tocá el mapa o usá "Usar mi ubicación actual".');
                    const mapEl = document.getElementById('map');
                    if (mapEl) mapEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    if (btn) {
                        btn.classList.add('btn-required-pulse');
                        setTimeout(() => btn.classList.remove('btn-required-pulse'), 2000);
                    }
                    return;
                }
            });
        }


        /* ── LIVE PREVIEW ── */
        const titleInput = document.getElementById('nombre');
        const precioInput = document.getElementById('precio');
        const previewTitle = document.getElementById('previewTitle');
        const previewPrice = document.getElementById('previewPrice');
        const previewImgSlot = document.getElementById('previewImgSlot');
        const previewCond = document.getElementById('previewCond');

        titleInput.addEventListener('input', () => {
            previewTitle.textContent = titleInput.value || 'Título del producto...';
            previewTitle.style.color = titleInput.value ? 'var(--ink)' : '#ccc';
            toggle('chk-titulo', !!titleInput.value);
        });

        precioInput.addEventListener('input', () => {
            // Posición del cursor antes del reformat
            const prevLen = precioInput.value.length;
            const caret   = precioInput.selectionStart;

            // Solo dígitos → formato con punto miles
            const raw = precioInput.value.replace(/\D/g, '');
            precioInput.value = raw ? Number(raw).toLocaleString('es-CO') : '';

            // Restaurar cursor compensando los puntos añadidos/quitados
            const diff = precioInput.value.length - prevLen;
            if (caret !== null) {
                const pos = Math.max(0, caret + diff);
                precioInput.setSelectionRange(pos, pos);
            }

            const v = parseInt(raw) || 0;
            previewPrice.innerHTML = v
                ? `$${v.toLocaleString('es-CO')}`
                : `<span class="preview-price-placeholder">$0</span>`;
            toggle('chk-precio', v > 0);
        });

        document.getElementById('descripcion').addEventListener('input', function() {
            toggle('chk-descripcion', this.value.length > 10);
        });

        document.querySelector('select[name="categoria"]').addEventListener('change', function() {
            toggle('chk-categoria', !!this.value);
        });

        /* ── CONDITION TOGGLE ── */
        function selectCondition(el, val) {
            document.querySelectorAll('.cond-opt').forEach(o => o.classList.remove('active'));
            el.classList.add('active');
            document.getElementById('condicionHidden').value = val;

            const labels = { nuevo: 'Nuevo', usado: 'Usado', reacondicionado: 'Reacondicionado' };
            const colors = {
                nuevo:            { bg: 'var(--g500)', fg: '#fff' },
                usado:            { bg: 'var(--y400)', fg: 'var(--g900)' },
                reacondicionado:  { bg: '#6366f1',     fg: '#fff' }
            };

            previewCond.textContent    = labels[val] || val;
            previewCond.style.background = (colors[val] || colors.usado).bg;
            previewCond.style.color      = (colors[val] || colors.usado).fg;
        }

        /* ── DRAG & DROP ── */
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
            gridEl.innerHTML = '';
            Array.from(files).slice(0, 8).forEach((f, i) => {
                const reader = new FileReader();
                reader.onload = ev => {
                    const div = document.createElement('div');
                    div.className = 'img-thumb';
                    div.innerHTML = `<img src="${ev.target.result}" alt="">` +
                        (i === 0 ? '<div class="main-badge">Principal</div>' : '');
                    gridEl.appendChild(div);
                    if (i === 0) {
                        previewImgSlot.innerHTML =
                            `<img src="${ev.target.result}" alt=""><div class="preview-cond-badge" id="previewCond">${previewCond.textContent}</div>`;
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


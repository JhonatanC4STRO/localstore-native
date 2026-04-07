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

        /* ── LOGIC PRESERVED: geolocation + Leaflet map ── */
        const estado = document.getElementById("est");
        const btn = document.getElementById("btnUbicacion");
        let map;

        btn.addEventListener("click", () => {
            if (!navigator.geolocation) {
                estado.textContent = "La geolocalización no es soportada por tu navegador.";
                return;
            }
            estado.innerHTML = '<i class="bi bi-hourglass-split"></i> Obteniendo ubicación...';

            navigator.geolocation.getCurrentPosition((pos) => {
                const lat = pos.coords.latitude;
                const lon = pos.coords.longitude;

                document.getElementById("latitude").value = lat;
                document.getElementById("longitude").value = lon;
                estado.innerHTML = `<i class="bi bi-geo-alt-fill" style="color:var(--g500);"></i> Lat ${lat.toFixed(5)}, Lon ${lon.toFixed(5)}`;

                /* LOGIC PRESERVED: remove old map before creating new */
                if (map !== undefined && map !== null) {
                    map.remove();
                }

                map = L.map('map').setView([lat, lon], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);
                L.marker([lat, lon]).addTo(map).bindPopup("📍 Estás aquí").openPopup();

                /* checklist update */
                markDone('chk-ubicacion');
            }, (error) => {
                estado.innerHTML = `<i class="bi bi-exclamation-circle" style="color:#dc2626;"></i> ${error.message}`;
            });
        });

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
            const v = parseInt(precioInput.value) || 0;
            previewPrice.innerHTML = v ?
                `$${v.toLocaleString('es-CO')}` :
                `<span class="preview-price-placeholder">$0</span>`;
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
            previewCond.textContent = val === 'nuevo' ? 'Nuevo' : 'Usado';
            previewCond.style.background = val === 'nuevo' ? 'var(--g500)' : 'var(--y400)';
            previewCond.style.color = val === 'nuevo' ? '#fff' : 'var(--g900)';
        }

        /* ── DRAG & DROP VISUAL ── */
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
            handleFiles(e.dataTransfer.files);
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

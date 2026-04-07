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
                if (e.key === 'Escape') wrap.classList.remove('open');
            });
        }

        /* ── View toggle (grid / list) ── */
        const productGrid = document.getElementById('productGrid');
        const gridBtn = document.getElementById('gridBtn');
        const listBtn = document.getElementById('listBtn');

        gridBtn?.addEventListener('click', () => {
            gridBtn.classList.add('active');
            listBtn.classList.remove('active');
            productGrid.style.gridTemplateColumns = '';
            productGrid.querySelectorAll('.product-card').forEach(c => {
                c.style.flexDirection = '';
                const img = c.querySelector('.pc-img');
                if (img) img.style.cssText = '';
            });
        });
        listBtn?.addEventListener('click', () => {
            listBtn.classList.add('active');
            gridBtn.classList.remove('active');
            productGrid.style.gridTemplateColumns = '1fr';
            productGrid.querySelectorAll('.product-card').forEach(c => {
                c.style.flexDirection = 'row';
                const img = c.querySelector('.pc-img');
                if (img) img.style.cssText = 'width:160px;height:auto;flex-shrink:0;';
            });
        });

        /* ══════════════════════════
           FILTER & AJAX SYSTEM
        ══════════════════════════ */

        const sortInline     = document.getElementById('sortInline');
        const applyBtn       = document.getElementById('applyFiltersBtn');
        const loadMoreBtn    = document.querySelector('.btn-load-more');
        const resultsCounter = document.querySelector('.results-count');
        const searchInput    = document.getElementById('searchInput');
        const searchBtn      = document.getElementById('searchBtn');
        const searchClear    = document.getElementById('searchClear');

        /* currentOffset y totalProducts se inyectan inline desde allProduct.php */
        const PAGE_SIZE    = 12;

        /* ─ Recolectar todos los filtros ─ */
        function getAllFilters() {
            // Categorías
            const cats = Array.from(document.querySelectorAll('#filterCategories input[type="checkbox"]'))
                .filter(cb => cb.checked && cb.value && !isNaN(cb.value))
                .map(cb => cb.value);

            // Precio
            const priceMin = parseInt((document.getElementById('priceMinInput')?.value || '0').replace(/\./g, '').replace(/,/g, '')) || 0;
            const priceMax = parseInt((document.getElementById('priceMaxInput')?.value || '10000000').replace(/\./g, '').replace(/,/g, '')) || 10000000;

            // Condición — usa data-condition igual que inde.php
            const activeCond = document.querySelector('#filterCondition .cond-tag.active');
            const condition  = activeCond?.dataset.condition || 'all';

            // Orden — solo inline (ya no hay sort en sidebar)
            const order = sortInline?.value || 'recent';

            // Búsqueda por texto
            const search = searchInput?.value?.trim() || '';

            return { cats, priceMin, priceMax, condition, order, search };
        }

        /* ─ Construir URL params ─ */
        function buildParams(filters, offset = 0, limit = PAGE_SIZE) {
            const p = new URLSearchParams();
            filters.cats.forEach(c => p.append('categories[]', c));
            p.append('price_min', filters.priceMin);
            p.append('price_max', filters.priceMax);
            p.append('condition', filters.condition);
            p.append('order', filters.order);
            p.append('limit', limit);
            p.append('offset', offset);
            if (filters.search) p.append('search', filters.search);
            return p;
        }

        /* ─ Mostrar/ocultar botón clear ─ */
        function toggleClear(val) {
            if (searchClear) searchClear.style.display = val ? '' : 'none';
        }

        /* ─ Promo badge map ─ */
        const PROMO_BADGE = {
            premium:     { cls: 'promo-badge-premium',     icon: '💎', label: 'Premium' },
            recommended: { cls: 'promo-badge-recommended', icon: '🚀', label: 'Recomendado' },
            basic:       { cls: 'promo-badge-basic',        icon: '⭐', label: 'Destacado' },
        };

        /* ─ Renderizar una tarjeta de producto ─ */
        function renderCard(p) {
            const isNew      = (p.condition_type || '').toLowerCase() === 'nuevo';
            const badgeClass = isNew ? 'badge badge-new' : 'badge badge-used';
            const badgeTxt   = p.condition_type
                ? p.condition_type.charAt(0).toUpperCase() + p.condition_type.slice(1)
                : 'Usado';
            const imgHTML    = p.image_path
                ? `<img src="${p.image_path}" alt="${p.title.replace(/"/g, '&quot;')}">`
                : '<div class="pc-img-ph"><i class="bi bi-box-seam"></i></div>';
            const pb = p.promotion_type ? PROMO_BADGE[p.promotion_type] : null;
            const promoPill = pb
                ? `<div class="promo-pill ${pb.cls}">${pb.icon} ${pb.label}</div>`
                : '';
            return `
                <a href="./actions/detalleProducto.php?id=${p.id}" style="display:contents;">
                    <div class="product-card">
                        <div class="pc-img promo-pill-wrap">
                            ${imgHTML}
                            ${promoPill}
                            <div class="${badgeClass}">${badgeTxt}</div>
                            <button class="fav-btn" onclick="event.preventDefault();this.classList.toggle('active');this.querySelector('i').className=this.classList.contains('active')?'bi bi-heart-fill':'bi bi-heart'">
                                <i class="bi bi-heart"></i>
                            </button>
                        </div>
                        <div class="pc-body">
                            <div class="pc-cat"><i class="bi bi-tag-fill"></i> ${p.category_name || 'General'}</div>
                            <div class="pc-title">${p.title}</div>
                            <div class="pc-price">$${p.price}</div>
                            <div class="pc-meta">
                                <div class="pc-meta-row"><i class="bi bi-geo-alt-fill"></i> Colombia</div>
                                <div class="pc-meta-row"><i class="bi bi-clock-fill"></i> ${p.time_label}</div>
                            </div>
                            <div class="pc-seller">
                                <div class="seller-av">${p.seller_initials}</div>
                                <a href="./seller_profile.php?seller_id=${p.seller_id}" class="seller-name-txt hover:underline">${p.seller_name}</a>
                                <div class="seller-stars">
                                    <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-half"></i><span>4.5</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>`;
        }

        /* ─ Actualizar contador ─ */
        function updateCounter(total, query) {
            if (!resultsCounter) return;
            const qPart = query ? ` para <strong>"${query}"</strong>` : '';
            resultsCounter.innerHTML =
                `<i class="bi bi-grid-3x3-gap-fill"></i> ${total} producto${total !== 1 ? 's' : ''}${qPart}`;
        }

        /* ─ Aplicar filtros (reload completo del grid) ─ */
        async function applyFiltersAP() {
            const filters = getAllFilters();
            const params  = buildParams(filters, 0, PAGE_SIZE);

            productGrid.style.opacity = '0.5';
            try {
                const res  = await fetch('./filter_products.php?' + params.toString());
                const data = await res.json();
                productGrid.style.opacity = '1';

                if (data.ok) {
                    totalProducts  = data.total;
                    currentOffset  = data.count;
                    updateCounter(data.total, filters.search);

                    if (data.products.length === 0) {
                        productGrid.innerHTML = `
                            <div class="empty-state">
                                <div class="empty-icon"><i class="bi bi-search"></i></div>
                                <div class="empty-title">Sin resultados</div>
                                <div class="empty-sub">Intenta con otros filtros.</div>
                            </div>`;
                    } else {
                        productGrid.innerHTML = data.products.map(renderCard).join('');
                    }

                    // Mostrar/ocultar "Cargar más"
                    if (loadMoreBtn) {
                        loadMoreBtn.parentElement.style.display = currentOffset < totalProducts ? '' : 'none';
                    }
                }
            } catch (e) {
                productGrid.style.opacity = '1';
                console.error('Filter error:', e);
            }
        }

        /* ─ Cargar más (append al grid) ─ */
        async function loadMore() {
            if (currentOffset >= totalProducts) return;
            const filters = getAllFilters();
            const params  = buildParams(filters, currentOffset, PAGE_SIZE);

            if (loadMoreBtn) {
                loadMoreBtn.innerHTML = '<i class="bi bi-arrow-clockwise" style="animation:spin 1s linear infinite;"></i> Cargando...';
                loadMoreBtn.disabled = true;
            }

            try {
                const res  = await fetch('./filter_products.php?' + params.toString());
                const data = await res.json();
                if (data.ok && data.products.length > 0) {
                    currentOffset += data.count;
                    productGrid.insertAdjacentHTML('beforeend', data.products.map(renderCard).join(''));
                    if (currentOffset >= totalProducts && loadMoreBtn) {
                        loadMoreBtn.parentElement.style.display = 'none';
                    }
                }
            } catch (e) {
                console.error('Load more error:', e);
            } finally {
                if (loadMoreBtn) {
                    loadMoreBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Cargar más productos';
                    loadMoreBtn.disabled = false;
                }
            }
        }

        /* ─ Resetear filtros ─ */
        function resetFilters() {
            document.querySelectorAll('#filterCategories input[type="checkbox"]').forEach(cb => cb.checked = false);
            document.querySelectorAll('#filterCategories .filter-opt').forEach(l => l.classList.remove('selected'));
            document.querySelectorAll('#filterCondition .cond-tag').forEach((t, i) => t.classList.toggle('active', i === 0));
            const pMin = document.getElementById('priceMinInput');
            const pMax = document.getElementById('priceMaxInput');
            const range = document.getElementById('rangeSlider');
            if (pMin)  pMin.value  = '0';
            if (pMax)  pMax.value  = '10.000.000';
            if (range) range.value = 10000000;
            if (sortInline) sortInline.value = 'recent';
            if (searchInput) { searchInput.value = ''; toggleClear(''); }
            const hdr = document.getElementById('clSearchInput');
            if (hdr) hdr.value = '';
            applyFiltersAP();
        }

        /* ─ Quick-filter chips (por categoría) ─ */
        const catCheckboxes = Array.from(
            document.querySelectorAll('#filterCategories input[type="checkbox"]')
        ).filter(cb => !isNaN(cb.value) && cb.value !== '');

        document.querySelectorAll('.qf-chip').forEach(chip => {
            chip.addEventListener('click', () => {
                document.querySelectorAll('.qf-chip').forEach(c => c.classList.remove('active'));
                chip.classList.add('active');

                const label = chip.textContent.trim().toLowerCase();

                if (label === 'todos') {
                    catCheckboxes.forEach(cb => {
                        cb.checked = false;
                        cb.closest('.filter-opt')?.classList.remove('selected');
                    });
                } else {
                    catCheckboxes.forEach(cb => {
                        const optLabel = cb.closest('label')?.querySelector('.filter-opt-label')?.textContent.trim().toLowerCase() || '';
                        cb.checked = optLabel.includes(label) || label.includes(optLabel.split(' ')[0]);
                        cb.closest('.filter-opt')?.classList.toggle('selected', cb.checked);
                    });
                }
                applyFiltersAP();
            });
        });

        /* ─ Categoría checkboxes: toggle clase selected (visual inde style) ─ */
        document.querySelectorAll('#filterCategories .filter-opt').forEach(opt => {
            const cb = opt.querySelector('input[type="checkbox"]');
            opt.addEventListener('click', e => {
                if (e.target !== cb) cb.checked = !cb.checked;
                opt.classList.toggle('selected', cb.checked);
            });
        });

        /* ─ Condición tags ─ */
        document.querySelectorAll('#filterCondition .cond-tag').forEach(tag => {
            tag.addEventListener('click', () => {
                document.querySelectorAll('#filterCondition .cond-tag').forEach(t => t.classList.remove('active'));
                tag.classList.add('active');
            });
        });

        /* ─ Range slider ─ */
        const rangeSlider = document.getElementById('rangeSlider');
        const priceMaxInput = document.getElementById('priceMaxInput');
        rangeSlider?.addEventListener('input', e => {
            if (priceMaxInput) priceMaxInput.value = parseInt(e.target.value).toLocaleString('es-CO');
        });
        priceMaxInput?.addEventListener('change', e => {
            const val = parseInt(e.target.value.replace(/\./g, '').replace(/,/g, '')) || 10000000;
            if (rangeSlider) rangeSlider.value = Math.min(val, 10000000);
        });

        /* ─ Wiring de botones y selects ─ */
        applyBtn?.addEventListener('click', applyFiltersAP);
        document.getElementById('resetFiltersBtn')?.addEventListener('click', resetFilters);
        loadMoreBtn?.addEventListener('click', loadMore);
        sortInline?.addEventListener('change', applyFiltersAP);

        /* ─ Paginación (ahora decorativa, load-more es el mecanismo real) ─ */
        document.querySelectorAll('.pg-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                if (btn.querySelector('i')) return;
                document.querySelectorAll('.pg-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            });
        });

        /* ─ Spin animation para load-more ─ */
        const spinStyle = document.createElement('style');
        spinStyle.textContent = '@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}';
        document.head.appendChild(spinStyle);

        /* ─ SQL original devuelve TODOS los productos; ajustar offset inicial ─ */
        // currentOffset ya se inicializó con PHP num_rows
        if (currentOffset >= totalProducts && loadMoreBtn) {
            loadMoreBtn.parentElement.style.display = 'none';
        }

        /* ══════════════════════════
           SEARCH BAR WIRING
        ══════════════════════════ */

        /* Debounce helper */
        let _searchTimer = null;
        function debounceSearch(ms = 480) {
            clearTimeout(_searchTimer);
            _searchTimer = setTimeout(applyFiltersAP, ms);
        }

        if (searchInput) {
            searchInput.addEventListener('input', () => {
                toggleClear(searchInput.value);
                debounceSearch();
            });
            searchInput.addEventListener('keydown', e => {
                if (e.key === 'Enter') { clearTimeout(_searchTimer); applyFiltersAP(); }
            });
        }

        searchBtn?.addEventListener('click', () => {
            clearTimeout(_searchTimer);
            applyFiltersAP();
        });

        searchClear?.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            toggleClear('');
            // Also clear the header search input if present
            const hdr = document.getElementById('clSearchInput');
            if (hdr) hdr.value = '';
            applyFiltersAP();
        });

        /* ─ Sincronizar búsqueda con el input del header ─ */
        const headerSearch = document.getElementById('clSearchInput');
        if (headerSearch && searchInput) {
            searchInput.addEventListener('input', () => { headerSearch.value = searchInput.value; });
        }

        /* ─ Auto-trigger si llegamos con ?search=... desde el hero o header ─ */
        if (initialSearch) {
            // searchInput ya está pre-llenado por PHP (value="...")
            toggleClear(initialSearch);
            if (headerSearch) headerSearch.value = initialSearch;
            applyFiltersAP();
        }

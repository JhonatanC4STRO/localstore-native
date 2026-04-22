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

        /* currentOffset y totalProducts se inyectan inline desde all.php */
        const PAGE_SIZE    = 12;

        /* ─ Recolectar todos los filtros ─ */
        function getAllFilters() {
            // Categorías
            const categories = Array.from(document.querySelectorAll('#filterCategories input[type="checkbox"]'))
                .filter(cb => cb.checked && cb.value && !isNaN(cb.value))
                .map(cb => cb.value);

            // Precio
            const price_min = parseInt((document.getElementById('priceMinInput')?.value || '0').replace(/\./g, '').replace(/,/g, '')) || 0;
            const price_max = parseInt((document.getElementById('priceMaxInput')?.value || '10000000').replace(/\./g, '').replace(/,/g, '')) || 10000000;

            // Condición — usa data-condition igual que home.php
            const activeCond = document.querySelector('#filterCondition .cond-tag.active');
            const condition  = activeCond?.dataset.condition || 'all';

            // Orden — solo inline (ya no hay sort en sidebar)
            const order = sortInline?.value || 'recent';

            // Búsqueda por texto
            const search = searchInput?.value?.trim() || '';

            // Ubicación (desde la URL inicial)
            const location = initialLocation || '';

            return { categories, price_min, price_max, condition, order, search, location };
        }

        /* ─ Construir URL params ─ */
        function buildParams(filters, offset = 0, limit = PAGE_SIZE) {
            const p = new URLSearchParams();
            filters.categories.forEach(c => p.append('categories[]', c));
            p.append('price_min', filters.price_min);
            p.append('price_max', filters.price_max);
            p.append('condition', filters.condition);
            p.append('order', filters.order);
            p.append('limit', limit);
            p.append('offset', offset);
            if (filters.search) p.append('search', filters.search);
            if (filters.location) p.append('location', filters.location);
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
            const condLower  = (p.condition_type || '').toLowerCase();
            const isNew      = condLower === 'nuevo';
            const isRefurb   = condLower === 'reacondicionado';
            const badgeClass = isNew ? 'badge badge-new' : (isRefurb ? 'badge badge-refurbished' : 'badge badge-used');
            const badgeTxt   = p.condition_type
                ? p.condition_type.charAt(0).toUpperCase() + p.condition_type.slice(1)
                : 'Usado';
            const imgPath    = p.image_url ? '../../../public/uploads/products/' + p.image_url : null;
            const imgHTML    = imgPath
                ? `<img src="${imgPath}" alt="${p.title.replace(/"/g, '&quot;')}">`
                : '<div class="pc-img-ph"><i class="bi bi-box-seam"></i></div>';
            const pb = p.promotion_type ? PROMO_BADGE[p.promotion_type] : null;
            const promoPill = pb
                ? `<div class="promo-pill ${pb.cls}">${pb.icon} ${pb.label}</div>`
                : '';
            return `
                <a href="./detalle.php?id=${p.id}" style="display:contents;">
                    <div class="product-card">
                        <div class="pc-img promo-pill-wrap">
                            ${imgHTML}
                            ${promoPill}
                            <div class="${badgeClass}">${badgeTxt}</div>
                            <button class="fav-btn" data-product-id="${p.id}">
                                <i class="bi ${typeof Favorites !== 'undefined' && Favorites.has(p.id) ? 'bi-heart-fill' : 'bi-heart'}"></i>
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
                                <a href="../seller_profile.php?seller_id=${p.seller_id}" class="seller-name-txt hover:underline">${p.seller_name}</a>
                                <div class="seller-stars">
                                    ${(() => {
                                        const rating = parseFloat(p.avg_rating) || 0;
                                        const total = parseInt(p.total_reviews) || 0;
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
                const res  = await fetch('../../api/products/filter.php?' + params.toString());
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

                    // Re-vincular favoritos en tarjetas nuevas
                    if (typeof Favorites !== 'undefined') {
                        Favorites.applyToButtons('.fav-btn');
                        Favorites.bindButtons('.fav-btn', '../../../');
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
                const res  = await fetch('../../api/products/filter.php?' + params.toString());
                const data = await res.json();
                if (data.ok && data.products.length > 0) {
                    currentOffset += data.count;
                    productGrid.insertAdjacentHTML('beforeend', data.products.map(renderCard).join(''));
                    // Re-vincular favoritos
                    if (typeof Favorites !== 'undefined') {
                        Favorites.applyToButtons('.fav-btn');
                        Favorites.bindButtons('.fav-btn', '../../../');
                    }
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

        /* ─ Resetear filtros: Redirigir a la URL base sin parámetros ─ */
        function resetFilters() {
            window.location.href = 'all.php';
        }

        /* ─ Quick-filter chips (por categoría) ─ */
        const catCheckboxes = Array.from(
            document.querySelectorAll('#filterCategories input[type="checkbox"]')
        ).filter(cb => !isNaN(cb.value) && cb.value !== '');

        document.querySelectorAll('.qf-chip').forEach(chip => {
            chip.addEventListener('click', () => {
                document.querySelectorAll('.qf-chip').forEach(c => c.classList.remove('active'));
                chip.classList.add('active');

                const catId   = chip.dataset.catId;
                const special = chip.dataset.special;

                // Deseleccionar todas las categorías primero
                catCheckboxes.forEach(cb => {
                    cb.checked = false;
                    cb.closest('.filter-opt')?.classList.remove('selected');
                });

                if (catId && catId !== 'all') {
                    // Seleccionar la categoría específica por ID
                    const cb = catCheckboxes.find(cb => cb.value === catId);
                    if (cb) {
                        cb.checked = true;
                        cb.closest('.filter-opt')?.classList.add('selected');
                    }
                }

                if (special === 'recent' && sortInline) {
                    sortInline.value = 'recent';
                }

                applyFiltersAP();
            });
        });

        /* ─ Categoría checkboxes: toggle clase selected (visual inde style) ─ */
        document.querySelectorAll('#filterCategories .filter-opt').forEach(opt => {
            const cb = opt.querySelector('input[type="checkbox"]');
            opt.addEventListener('click', e => {
                // Si el click no fue directamente en el checkbox, lo invertimos manualmente
                if (e.target !== cb) {
                    cb.checked = !cb.checked;
                }
                // Actualizar la clase visual basada en el estado real del checkbox
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

        /* ─ Auto-trigger si llegamos con ?category=... desde el slider de categorías ─ */
        if (initialCategory) {
            const cb = document.querySelector(`#filterCategories input[value="${initialCategory}"]`);
            if (cb) {
                cb.checked = true;
                cb.closest('.filter-opt')?.classList.add('selected');
            }
            // Highlight matching quick-bar chip
            const qfChip = document.querySelector(`.qf-chip[data-cat-id="${initialCategory}"]`);
            if (qfChip) {
                document.querySelectorAll('.qf-chip').forEach(c => c.classList.remove('active'));
                qfChip.classList.add('active');
            }
        }

        /* ─ Auto-trigger para filtros múltiples desde el Home ─ */
        // Categorías múltiples
        if (Array.isArray(initialCategories) && initialCategories.length > 0) {
            initialCategories.forEach(id => {
                const cb = document.querySelector(`#filterCategories input[value="${id}"]`);
                if (cb) {
                    cb.checked = true;
                    cb.closest('.filter-opt')?.classList.add('selected');
                }
            });
        }

        // Precios
        if (initialPriceMin !== '') {
            const pMin = document.getElementById('priceMinInput');
            if (pMin) pMin.value = parseInt(initialPriceMin).toLocaleString('es-CO');
        }
        if (initialPriceMax !== '') {
            const pMax = document.getElementById('priceMaxInput');
            const range = document.getElementById('rangeSlider');
            if (pMax) pMax.value = parseInt(initialPriceMax).toLocaleString('es-CO');
            if (range) range.value = initialPriceMax;
        }

        // Condición
        if (initialCondition && initialCondition !== 'all') {
            document.querySelectorAll('#filterCondition .cond-tag').forEach(tag => {
                if (tag.dataset.condition.toLowerCase() === initialCondition.toLowerCase()) {
                    document.querySelectorAll('#filterCondition .cond-tag').forEach(t => t.classList.remove('active'));
                    tag.classList.add('active');
                }
            });
        }

        // Disparar filtrado inicial si hay cualquier filtro activo (excepto el default)
        if (initialSearch || initialCategory || (initialCategories.length > 0) || initialPriceMin !== '' || (initialPriceMax !== '' && initialPriceMax !== '10000000') || initialCondition !== 'all' || initialLocation) {
            applyFiltersAP();
        }


// ════════════════════════════════════════
// Unified Header — ComercioLocal
// ════════════════════════════════════════
(function () {
  /* ── User Dropdown ── */
  const wrap = document.getElementById('clUserWrap');
  const chip = document.getElementById('clUserChip');
  const dropdown = document.getElementById('clUserDropdown');

  function openDropdown() {
    if (!wrap) return;
    wrap.classList.add('open');
    chip?.setAttribute('aria-expanded', 'true');
    dropdown?.setAttribute('aria-hidden', 'false');
    // Focus first menu item
    const first = dropdown?.querySelector('[role="menuitem"]');
    if (first) first.focus();
  }

  function closeDropdown() {
    if (!wrap) return;
    wrap.classList.remove('open');
    chip?.setAttribute('aria-expanded', 'false');
    dropdown?.setAttribute('aria-hidden', 'true');
  }

  if (chip && wrap) {
    chip.addEventListener('click', function (e) {
      e.stopPropagation();
      var isOpen = wrap.classList.contains('open');
      if (isOpen) closeDropdown();
      else openDropdown();
    });

    // Keyboard navigation inside dropdown menu
    if (dropdown) {
      dropdown.addEventListener('keydown', function (e) {
        var items = Array.from(dropdown.querySelectorAll('[role="menuitem"]'));
        var idx = items.indexOf(document.activeElement);

        if (e.key === 'ArrowDown') {
          e.preventDefault();
          var next = idx < items.length - 1 ? idx + 1 : 0;
          items[next].focus();
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          var prev = idx > 0 ? idx - 1 : items.length - 1;
          items[prev].focus();
        } else if (e.key === 'Escape') {
          closeDropdown();
          chip.focus();
        } else if (e.key === 'Tab') {
          closeDropdown();
        }
      });
    }
  }

  // Close dropdown on outside click
  document.addEventListener('click', function (e) {
    if (wrap && !wrap.contains(e.target)) closeDropdown();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeDropdown();
  });

  /* ── Header Search (intercept on all.php) ── */
  var searchForm = document.getElementById('clSearchForm');
  if (searchForm) {
    searchForm.addEventListener('submit', function (e) {
      // If we're on all.php, use inline AJAX filter instead of navigating
      if (typeof applyFiltersAP === 'function') {
        e.preventDefault();
        var inp = document.getElementById('clSearchInput');
        var si = document.getElementById('searchInput');
        if (si && inp) {
          si.value = inp.value.trim();
          if (typeof toggleClear === 'function') toggleClear(si.value);
        }
        applyFiltersAP();
      }
    });
  }

  /* ── Mobile Search Toggle ── */
  var searchToggle = document.getElementById('clSearchToggle');
  var mobileSearch = document.getElementById('clMobileSearch');
  var mobileSearchInput = document.getElementById('clMobileSearchInput');
  var mobileSearchClose = document.getElementById('clMobileSearchClose');

  function openMobileSearch() {
    if (!mobileSearch) return;
    mobileSearch.classList.add('visible');
    mobileSearch.setAttribute('aria-hidden', 'false');
    searchToggle?.setAttribute('aria-expanded', 'true');
    if (mobileSearchInput) mobileSearchInput.focus();
  }

  function closeMobileSearch() {
    if (!mobileSearch) return;
    mobileSearch.classList.remove('visible');
    mobileSearch.setAttribute('aria-hidden', 'true');
    searchToggle?.setAttribute('aria-expanded', 'false');
    if (searchToggle) searchToggle.focus();
  }

  searchToggle?.addEventListener('click', openMobileSearch);
  mobileSearchClose?.addEventListener('click', closeMobileSearch);

  if (mobileSearch) {
    mobileSearch.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeMobileSearch();
    });
  }

  /* ── Mobile Drawer ── */
  var hamburger = document.getElementById('clHamburger');
  var drawer = document.getElementById('clMobileDrawer');
  var overlay = document.getElementById('clDrawerOverlay');
  var drawerClose = document.getElementById('clDrawerClose');

  function openDrawer() {
    if (!drawer || !overlay) return;
    drawer.classList.add('open');
    overlay.classList.add('visible');
    drawer.setAttribute('aria-hidden', 'false');
    hamburger?.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
    // Focus first link
    var firstLink = drawer.querySelector('.cl-drawer-link');
    if (firstLink) firstLink.focus();
  }

  function closeDrawer() {
    if (!drawer || !overlay) return;
    drawer.classList.remove('open');
    overlay.classList.remove('visible');
    drawer.setAttribute('aria-hidden', 'true');
    hamburger?.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
    if (hamburger) hamburger.focus();
  }

  hamburger?.addEventListener('click', openDrawer);
  drawerClose?.addEventListener('click', closeDrawer);
  overlay?.addEventListener('click', closeDrawer);

  if (drawer) {
    drawer.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeDrawer();

      // Trap focus inside drawer
      if (e.key === 'Tab') {
        var focusable = Array.from(drawer.querySelectorAll('a[href], button, input, [tabindex]:not([tabindex="-1"])'));
        if (focusable.length === 0) return;
        var first = focusable[0];
        var last = focusable[focusable.length - 1];

        if (e.shiftKey && document.activeElement === first) {
          e.preventDefault();
          last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      }
    });
  }
  /* ── Notifications System ── */
  if (window.__CL && window.__CL.userId) {
    var notifWrap     = document.getElementById('clNotifWrap');
    var notifBtn      = document.getElementById('clNotifBtn');
    var notifDropdown = document.getElementById('clNotifDropdown');
    var notifList     = document.getElementById('clNdList');
    var notifEmpty    = document.getElementById('clNdEmpty');
    var notifTotal    = document.getElementById('clNdTotal');
    var notifDot      = document.getElementById('clNotifDot');
    var notifCount    = document.getElementById('clNotifCount');
    var msgDot        = document.getElementById('clMsgDot');
    var msgCount      = document.getElementById('clMsgCount');
    var apiBase       = window.__CL.apiPath + 'chat/';
    var viewsBase     = window.__CL.viewsPath;

    // Toggle notification dropdown
    if (notifBtn && notifWrap) {
      notifBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        var isOpen = notifWrap.classList.contains('open');
        if (isOpen) {
          closeNotifs();
        } else {
          openNotifs();
          fetchNotifications(); // refresh on open
        }
      });
    }

    function openNotifs() {
      if (!notifWrap) return;
      notifWrap.classList.add('open');
      notifBtn.setAttribute('aria-expanded', 'true');
      notifDropdown.setAttribute('aria-hidden', 'false');
    }

    function closeNotifs() {
      if (!notifWrap) return;
      notifWrap.classList.remove('open');
      notifBtn.setAttribute('aria-expanded', 'false');
      notifDropdown.setAttribute('aria-hidden', 'true');
    }

    // Close on outside click
    document.addEventListener('click', function (e) {
      if (notifWrap && !notifWrap.contains(e.target)) closeNotifs();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeNotifs();
    });

    // Update badge numbers
    function updateBadges(total) {
      // Bell icon badge
      if (total > 0) {
        if (notifDot)   notifDot.style.display = 'block';
        if (notifCount) { notifCount.style.display = 'flex'; notifCount.textContent = total > 99 ? '99+' : total; }
        if (msgDot)     msgDot.style.display = 'block';
        if (msgCount)   { msgCount.style.display = 'flex'; msgCount.textContent = total > 99 ? '99+' : total; }
        if (notifTotal) { notifTotal.textContent = total + (total === 1 ? ' mensaje' : ' mensajes'); notifTotal.classList.add('show'); }
      } else {
        if (notifDot)   notifDot.style.display = 'none';
        if (notifCount) notifCount.style.display = 'none';
        if (msgDot)     msgDot.style.display = 'none';
        if (msgCount)   msgCount.style.display = 'none';
        if (notifTotal) notifTotal.classList.remove('show');
      }
    }

    function renderNotifications(data) {
      if (!notifList) return;
      var notifications = data.notifications || [];
      var total = data.total_unread || 0;

      updateBadges(total);

      // Clear list (keep empty state)
      var items = notifList.querySelectorAll('.cl-nd-item');
      for (var i = 0; i < items.length; i++) items[i].remove();

      if (notifications.length === 0) {
        if (notifEmpty) notifEmpty.style.display = 'block';
        return;
      }

      if (notifEmpty) notifEmpty.style.display = 'none';

      notifications.forEach(function (n) {
        var a = document.createElement('a');
        a.className = 'cl-nd-item';
        a.href = viewsBase + 'chat/chat.php?conversation_id=' + n.conversation_id;

        var avatarContent = n.sender_photo
          ? '<img src="' + window.__CL.apiPath + '../../public/uploads/avatars/' + n.sender_photo + '" alt="">'
          : n.sender_initial;

        var productTag = n.product_name
          ? '<div class="nd-product-tag"><i class="bi bi-tag-fill"></i> ' + escapeHtml(n.product_name) + '</div>'
          : '';

        var unreadBadge = '<span class="nd-unread-badge">' + n.unread_count + '</span>';

        a.innerHTML =
          '<div class="nd-avatar">' + avatarContent + '</div>' +
          '<div class="nd-body">' +
            '<div class="nd-top">' +
              '<span class="nd-name">' + escapeHtml(n.sender_name) + '</span>' +
              '<span class="nd-time">' + n.time_ago + '</span>' +
            '</div>' +
            '<div class="nd-msg">' + escapeHtml(n.message) + '</div>' +
            productTag +
          '</div>' +
          unreadBadge;

        notifList.insertBefore(a, notifEmpty);
      });
    }

    function escapeHtml(text) {
      var d = document.createElement('div');
      d.textContent = text || '';
      return d.innerHTML;
    }

    function fetchNotifications() {
      fetch(apiBase + 'get_notifications.php')
        .then(function (r) { return r.json(); })
        .then(function (data) {
          renderNotifications(data);
        })
        .catch(function () {});
    }

    // Quick poll: just the count (lightweight)
    function fetchUnreadCount() {
      fetch(apiBase + 'get_unread_count.php')
        .then(function (r) { return r.json(); })
        .then(function (data) {
          updateBadges(data.unread || 0);
        })
        .catch(function () {});
    }

    // Initial load
    fetchNotifications();

    // Poll count every 10 seconds
    setInterval(fetchUnreadCount, 10000);
  }

  /* ════════════════════════════════════════
     Autocomplete (smart search)
     ════════════════════════════════════════ */
  (function () {
    if (!window.__CL) return;
    const apiBase     = window.__CL.apiPath + 'products/';
    const viewsBase   = window.__CL.viewsPath;
    const allUrl      = viewsBase + 'products/all.php';
    // Resolver URL absoluta de uploads/products a partir de viewsBase
    const uploadsBase = new URL('../../public/uploads/products/',
                                new URL(viewsBase, window.location.href)).href;

    /* Helpers */
    function escHtml(s) {
      const d = document.createElement('div');
      d.textContent = s == null ? '' : String(s);
      return d.innerHTML;
    }
    function highlight(text, q) {
      if (!q) return escHtml(text);
      const safe = escHtml(text);
      const re = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'ig');
      return safe.replace(re, '<mark>$1</mark>');
    }
    function debounce(fn, ms) {
      let t;
      return function () {
        const args = arguments;
        clearTimeout(t);
        t = setTimeout(() => fn.apply(this, args), ms);
      };
    }
    function buildUrl(params) {
      const url = new URL(allUrl, window.location.href);
      Object.entries(params).forEach(([k, v]) => {
        if (v != null && v !== '') url.searchParams.set(k, v);
      });
      return url.toString();
    }

    /* Render del dropdown completo a partir del JSON */
    function render(panel, data, q) {
      const sections = [];
      const items    = []; // referencia plana para navegar con teclado

      // Búsquedas recientes (solo si hay)
      if (data.history && data.history.length) {
        const rows = data.history.map(h => {
          const url = buildUrl({ search: h });
          return `<a class="cl-sg-item cl-sg-history" role="option" href="${escHtml(url)}" data-q="${escHtml(h)}">
                    <div class="cl-sg-ico"><i class="bi bi-clock-history"></i></div>
                    <div class="cl-sg-text"><div class="cl-sg-primary">${highlight(h, q)}</div></div>
                    <i class="cl-sg-arrow bi bi-arrow-up-left"></i>
                  </a>`;
        }).join('');
        const clearBtn = window.__CL.isLoggedIn
          ? '<button type="button" class="cl-sg-clear" data-action="clear-history">Limpiar</button>'
          : '';
        sections.push(`<div class="cl-sg-section">
                         <span class="cl-sg-section-title"><i class="bi bi-clock-history"></i> Búsquedas recientes</span>
                         ${clearBtn}
                       </div>${rows}`);
      }

      // Productos
      if (data.products && data.products.length) {
        const rows = data.products.map(p => {
          const href = viewsBase + 'products/detalle.php?id=' + p.id;
          const thumb = p.thumb
            ? `<img src="${uploadsBase}${escHtml(p.thumb)}" alt="">`
            : '<i class="bi bi-box-seam"></i>';
          const sub = [p.category, p.city].filter(Boolean).map(escHtml).join(' · ');
          return `<a class="cl-sg-item cl-sg-product" role="option" href="${escHtml(href)}">
                    <div class="cl-sg-ico">${thumb}</div>
                    <div class="cl-sg-text">
                      <div class="cl-sg-primary">${highlight(p.title, q)}</div>
                      ${sub ? `<div class="cl-sg-secondary">${sub}</div>` : ''}
                    </div>
                    <span class="cl-sg-price">${escHtml(p.price)}</span>
                  </a>`;
        }).join('');
        sections.push(`<div class="cl-sg-section">
                         <span class="cl-sg-section-title"><i class="bi bi-box-seam"></i> Productos</span>
                       </div>${rows}`);
      }

      // Categorías
      if (data.categories && data.categories.length) {
        const rows = data.categories.map(c => {
          const url = buildUrl({ category: c.id });
          return `<a class="cl-sg-item cl-sg-cat" role="option" href="${escHtml(url)}">
                    <div class="cl-sg-ico"><i class="bi bi-tag-fill"></i></div>
                    <div class="cl-sg-text">
                      <div class="cl-sg-primary">${highlight(c.name, q)}</div>
                      <div class="cl-sg-secondary">Categoría</div>
                    </div>
                    <i class="cl-sg-arrow bi bi-arrow-right"></i>
                  </a>`;
        }).join('');
        sections.push(`<div class="cl-sg-section">
                         <span class="cl-sg-section-title"><i class="bi bi-tag-fill"></i> Categorías</span>
                       </div>${rows}`);
      }

      // Ciudades
      if (data.cities && data.cities.length) {
        const rows = data.cities.map(city => {
          const url = buildUrl({ location: city });
          return `<a class="cl-sg-item cl-sg-city" role="option" href="${escHtml(url)}">
                    <div class="cl-sg-ico"><i class="bi bi-geo-alt-fill"></i></div>
                    <div class="cl-sg-text">
                      <div class="cl-sg-primary">${highlight(city, q)}</div>
                      <div class="cl-sg-secondary">Productos en esta ciudad</div>
                    </div>
                    <i class="cl-sg-arrow bi bi-arrow-right"></i>
                  </a>`;
        }).join('');
        sections.push(`<div class="cl-sg-section">
                         <span class="cl-sg-section-title"><i class="bi bi-geo-alt-fill"></i> Ciudades</span>
                       </div>${rows}`);
      }

      if (!sections.length) {
        if (q) {
          panel.innerHTML = `<div class="cl-sg-empty">
                               <i class="bi bi-search"></i>
                               Sin sugerencias para "<strong>${escHtml(q)}</strong>"
                             </div>`;
        } else {
          panel.innerHTML = `<div class="cl-sg-empty">
                               <i class="bi bi-lightbulb"></i>
                               Empezá a escribir para ver sugerencias
                             </div>`;
        }
      } else {
        panel.innerHTML = sections.join('');
      }

      // Indexar items para navegación con teclado
      panel.querySelectorAll('.cl-sg-item').forEach(el => items.push(el));
      panel._items = items;
      panel._activeIdx = -1;
    }

    /* Wire-up para un par input + panel */
    function attach(input, panel) {
      if (!input || !panel) return;

      let lastQ      = null;
      let abortCtl   = null;
      let opened     = false;

      function open() {
        if (opened) return;
        panel.hidden = false;
        opened = true;
        input.setAttribute('aria-expanded', 'true');
      }
      function close() {
        if (!opened) return;
        panel.hidden = true;
        opened = false;
        input.setAttribute('aria-expanded', 'false');
        clearActive();
      }
      function clearActive() {
        if (!panel._items) return;
        panel._items.forEach(el => el.classList.remove('is-active'));
        panel._activeIdx = -1;
      }
      function setActive(idx) {
        if (!panel._items || !panel._items.length) return;
        clearActive();
        const i = ((idx % panel._items.length) + panel._items.length) % panel._items.length;
        panel._activeIdx = i;
        panel._items[i].classList.add('is-active');
        panel._items[i].scrollIntoView({ block: 'nearest' });
      }

      const fetchSuggest = debounce(function (q) {
        if (abortCtl) abortCtl.abort();
        abortCtl = new AbortController();

        // Estado loading suave (solo si hay query)
        if (q) {
          panel.innerHTML = '<div class="cl-sg-loading"><i class="bi bi-arrow-clockwise"></i> Buscando…</div>';
          open();
        }

        fetch(apiBase + 'suggest.php?q=' + encodeURIComponent(q), { signal: abortCtl.signal })
          .then(r => r.json())
          .then(data => {
            // Si el usuario cambió el query, descartar
            if (input.value.trim() !== q) return;
            const hasAny = (data.history && data.history.length)
                       || (data.products && data.products.length)
                       || (data.categories && data.categories.length)
                       || (data.cities && data.cities.length);
            if (!hasAny && !q) { close(); return; }
            render(panel, data, q);
            open();
          })
          .catch(() => {});
      }, 200);

      input.addEventListener('input', () => {
        const q = input.value.trim();
        if (q === lastQ) return;
        lastQ = q;
        fetchSuggest(q);
      });

      input.addEventListener('focus', () => {
        const q = input.value.trim();
        if (q || window.__CL.isLoggedIn) {
          fetchSuggest(q);
        }
      });

      input.addEventListener('keydown', (e) => {
        if (!opened) return;
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          setActive((panel._activeIdx ?? -1) + 1);
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          setActive((panel._activeIdx ?? 0) - 1);
        } else if (e.key === 'Enter') {
          if (panel._activeIdx >= 0 && panel._items[panel._activeIdx]) {
            e.preventDefault();
            panel._items[panel._activeIdx].click();
          }
        } else if (e.key === 'Escape') {
          close();
          input.blur();
        }
      });

      // Click fuera cierra
      document.addEventListener('click', (e) => {
        if (!opened) return;
        if (e.target === input || panel.contains(e.target)) return;
        close();
      });

      // Limpiar historial
      panel.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action="clear-history"]');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        fetch(apiBase + 'clear_history.php', { method: 'POST' })
          .then(r => r.json())
          .then(() => {
            lastQ = null;
            fetchSuggest(input.value.trim());
            input.focus();
          })
          .catch(() => {});
      });
    }

    attach(document.getElementById('clSearchInput'),       document.getElementById('clSuggest'));
    attach(document.getElementById('clMobileSearchInput'), document.getElementById('clMobileSuggest'));
  })();
})();

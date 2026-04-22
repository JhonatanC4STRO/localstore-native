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
})();

/* ══════════════════════════════════════
   favorites.js
   Modulo compartido para manejar favoritos
   en todas las vistas (home, all, detalle, seller_profile).
══════════════════════════════════════ */

const Favorites = (() => {
  let _ids = new Set();
  let _loaded = false;

  /** Cargar IDs de favoritos del usuario */
  async function load(apiBase) {
    if (_loaded) return _ids;
    try {
      const res = await fetch(apiBase + 'src/api/favorites/list.php');
      const data = await res.json();
      if (data.ok) {
        data.product_ids.forEach(id => _ids.add(id));
      }
      _loaded = true;
    } catch (e) {
      // No autenticado o error de red, silenciar
    }
    return _ids;
  }

  /** Verificar si un producto esta en favoritos */
  function has(productId) {
    return _ids.has(Number(productId));
  }

  /** Toggle favorito via API */
  async function toggle(productId, apiBase) {
    productId = Number(productId);
    try {
      const res = await fetch(apiBase + 'src/api/favorites/toggle.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId })
      });
      const data = await res.json();
      if (data.ok) {
        if (data.favorited) {
          _ids.add(productId);
        } else {
          _ids.delete(productId);
        }
        return data.favorited;
      }
    } catch (e) {
      // error de red
    }
    return null;
  }

  /** Aplicar estado visual a botones existentes en el DOM */
  function applyToButtons(selector) {
    document.querySelectorAll(selector).forEach(btn => {
      const id = Number(btn.dataset.productId);
      if (!id) return;
      const icon = btn.querySelector('i');
      if (has(id)) {
        btn.classList.add('active');
        if (icon) {
          icon.className = 'bi bi-heart-fill';
          icon.style.color = '#ef4444';
        }
      }
    });
  }

  /** Vincular click en botones de favorito */
  function bindButtons(selector, apiBase) {
    document.querySelectorAll(selector).forEach(btn => {
      if (btn._favBound) return;
      btn._favBound = true;
      btn.addEventListener('click', async (e) => {
        e.preventDefault();
        e.stopPropagation();
        // Prevenir navegacion del <a> padre si existe
        const parentLink = btn.closest('a');
        if (parentLink) {
          parentLink.addEventListener('click', preventOnce, { capture: true, once: true });
        }

        const id = btn.dataset.productId;
        if (!id) return;

        // Optimistic UI
        const icon = btn.querySelector('i');
        const wasActive = btn.classList.contains('active');
        btn.classList.toggle('active');
        if (icon) {
          icon.className = wasActive ? 'bi bi-heart' : 'bi bi-heart-fill';
          icon.style.color = wasActive ? '' : '#ef4444';
        }

        const result = await toggle(id, apiBase);
        if (result === null) {
          // Revertir si fallo
          btn.classList.toggle('active');
          if (icon) {
            icon.className = wasActive ? 'bi bi-heart-fill' : 'bi bi-heart';
            icon.style.color = wasActive ? '#ef4444' : '';
          }
        }
      });
    });
  }

  function preventOnce(e) {
    e.preventDefault();
    e.stopPropagation();
  }

  return { load, has, toggle, applyToButtons, bindButtons };
})();

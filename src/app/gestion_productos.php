<?php
/**
 * Gestión de Productos — ComercioLocal Admin
 * Data-driven via /api/admin_products.php
 */
require_once('../config/conexion.php');
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: ./auth/login.php'); exit();
}
if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    header('Location: ./inde.php'); exit();
}

$user        = $_SESSION['user'];
$userInitial = strtoupper(mb_substr($user['full_name'], 0, 1));
$userName    = explode(' ', $user['full_name'])[0];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de Productos — ComercioLocal Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../style/gestion_productos.css">
</head>
<body class="admin-body">

<!-- ══════════════════════════════════
     TOP NAV BAR
══════════════════════════════════ -->
<header class="adm-topbar">
  <a href="./admin_dashboard.php" class="adm-topbar__logo">
    <div class="adm-topbar__logo-icon">🏪</div>
    <div class="adm-topbar__logo-text">
      <strong>ComercioLocal</strong>
      <span>Admin Panel</span>
    </div>
  </a>

  <div class="adm-topbar__divider"></div>

  <div class="adm-topbar__search">
    <i class="bi bi-search"></i>
    <input type="text" placeholder="Buscar productos o vendedores…" id="topbarQ">
  </div>

  <div class="adm-topbar__actions">
    <span style="font-size:.8rem;color:#64748b;display:flex;align-items:center;">
      <span class="adm-live-dot"></span>En vivo
    </span>
    <button class="adm-topbar__icon-btn" title="Notificaciones">
      <i class="bi bi-bell-fill"></i>
      <span class="badge"></span>
    </button>
    <button class="adm-topbar__icon-btn" title="Volver al dashboard" onclick="location.href='./admin_dashboard.php'">
      <i class="bi bi-speedometer2"></i>
    </button>
    <div class="adm-avatar-btn">
      <div class="adm-avatar-circle"><?= htmlspecialchars($userInitial) ?></div>
      <div class="adm-avatar-btn-info">
        <strong><?= htmlspecialchars($userName) ?></strong>
        <span>Super Admin</span>
      </div>
    </div>
  </div>
</header>

<!-- ══════════════════════════════════
     SHELL
══════════════════════════════════ -->
<div class="adm-shell">

  <!-- ── SIDEBAR ── -->
  <aside class="adm-sidebar">
    <div class="adm-sb-label">Principal</div>
    <a class="adm-sb-link" href="./admin_dashboard.php"><i class="bi bi-speedometer2"></i>Dashboard</a>
    <a class="adm-sb-link" href="./verificaciones.php">
      <i class="bi bi-patch-check"></i>Solicitudes de verificación
    </a>
    <a class="adm-sb-link" href="./gestion_usuarios.php">
      <i class="bi bi-people"></i>Gestión de usuarios
    </a>
    <a class="adm-sb-link active" href="./gestion_productos.php">
      <i class="bi bi-box-seam"></i>Gestión de productos
    </a>
    <a class="adm-sb-link" href="#"><i class="bi bi-flag"></i>Productos reportados</a>
    <hr class="adm-sb-divider">
    <div class="adm-sb-label">Administración</div>
    <a class="adm-sb-link" href="./gestion_admins.php"><i class="bi bi-shield-check"></i>Gestión de admins</a>
    <a class="adm-sb-link" href="#"><i class="bi bi-journal-text"></i>Registros de actividad</a>
    <a class="adm-sb-link" href="#"><i class="bi bi-gear"></i>Configuración</a>
    <hr class="adm-sb-divider">
    <a class="adm-sb-link" href="../controller/logout.php" style="color:rgba(239,68,68,.7);">
      <i class="bi bi-box-arrow-right"></i>Cerrar sesión
    </a>
    <div class="adm-sb-footer">
      <strong>📦 Gestión de productos</strong>
      <p>Activa, desactiva o elimina publicaciones. Todas las acciones quedan registradas.</p>
    </div>
  </aside>

  <!-- ══════════════════════════════════
       MAIN CONTENT
  ══════════════════════════════════ -->
  <main class="adm-main" id="mainArea">

    <!-- Page header -->
    <div class="adm-page-header">
      <div class="adm-page-header__title">
        <div class="adm-breadcrumb">
          <a href="./admin_dashboard.php">Dashboard</a>
          <i class="bi bi-chevron-right"></i>
          <span>Gestión de productos</span>
        </div>
        <h1>Gestión de productos</h1>
        <p>Supervisa, activa, desactiva y elimina publicaciones del marketplace.</p>
      </div>
      <div class="adm-page-header__actions">
        <button class="gp-btn gp-btn--ghost" onclick="loadStats(); loadTable();">
          <i class="bi bi-arrow-clockwise"></i> Actualizar
        </button>
        <button class="gp-btn gp-btn--ghost" onclick="exportCSV()">
          <i class="bi bi-download"></i> CSV
        </button>
      </div>
    </div>

    <!-- Stats row -->
    <div class="gp-stats">
      <div class="gp-stat gp-stat--total">
        <div class="gp-stat__icon"><i class="bi bi-box-seam-fill"></i></div>
        <div class="gp-stat__num" id="s-total">—</div>
        <div class="gp-stat__lbl">Total productos</div>
      </div>
      <div class="gp-stat gp-stat--active">
        <div class="gp-stat__icon"><i class="bi bi-check-circle-fill"></i></div>
        <div class="gp-stat__num" id="s-active">—</div>
        <div class="gp-stat__lbl">Activos (admin)</div>
      </div>
      <div class="gp-stat gp-stat--inactive">
        <div class="gp-stat__icon"><i class="bi bi-pause-circle-fill"></i></div>
        <div class="gp-stat__num" id="s-inactive">—</div>
        <div class="gp-stat__lbl">Inactivos</div>
      </div>
      <div class="gp-stat gp-stat--deleted">
        <div class="gp-stat__icon"><i class="bi bi-trash3-fill"></i></div>
        <div class="gp-stat__num" id="s-deleted">—</div>
        <div class="gp-stat__lbl">Eliminados</div>
      </div>
      <div class="gp-stat gp-stat--disponible">
        <div class="gp-stat__icon"><i class="bi bi-tag-fill"></i></div>
        <div class="gp-stat__num" id="s-disponible">—</div>
        <div class="gp-stat__lbl">Disponibles</div>
      </div>
      <div class="gp-stat gp-stat--vendido">
        <div class="gp-stat__icon"><i class="bi bi-bag-check-fill"></i></div>
        <div class="gp-stat__num" id="s-vendido">—</div>
        <div class="gp-stat__lbl">Vendidos</div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="gp-toolbar">
      <div class="gp-search">
        <i class="bi bi-search si"></i>
        <input type="text" id="searchQ" placeholder="Buscar por título o vendedor…" autocomplete="off">
        <button class="sc" onclick="clearSearch()" title="Limpiar"><i class="bi bi-x-lg"></i></button>
      </div>

      <div class="gp-toolbar__sep"></div>

      <select class="gp-filter" id="fCategory" onchange="applyFilters()">
        <option value="">Todas las categorías</option>
        <!-- filled by JS -->
      </select>

      <select class="gp-filter" id="fStatus" onchange="applyFilters()">
        <option value="">Todos los estados</option>
        <option value="active">Activo</option>
        <option value="inactive">Inactivo</option>
        <option value="deleted">Eliminado</option>
      </select>

      <input type="text" class="gp-filter" id="fCity" placeholder="Filtrar ciudad…" style="max-width:140px;" oninput="debouncedCityFilter()">

      <input type="date" class="gp-date" id="fDateFrom" onchange="applyFilters()" title="Desde">
      <input type="date" class="gp-date" id="fDateTo"   onchange="applyFilters()" title="Hasta">

      <div class="gp-toolbar__sep"></div>
      <span class="gp-results-info" id="resultsInfo">Cargando…</span>
    </div>

    <!-- Table card -->
    <div class="gp-table-card">
      <div class="gp-table-wrap">
        <div class="gp-loading-overlay active" id="loadingOverlay">
          <div class="gp-spinner"></div>
        </div>
        <table class="gp-table" id="productsTable">
          <thead>
            <tr>
              <th style="width:36px;"><input type="checkbox" id="selectAll" onchange="toggleAll(this)" title="Seleccionar todos"></th>
              <th class="sortable" data-col="title" onclick="sortBy('title')">Producto <span class="sort-icon"></span></th>
              <th class="sortable" data-col="price" onclick="sortBy('price')">Precio <span class="sort-icon"></span></th>
              <th class="sortable" data-col="category" onclick="sortBy('category')">Categoría <span class="sort-icon"></span></th>
              <th class="sortable" data-col="seller" onclick="sortBy('seller')">Vendedor <span class="sort-icon"></span></th>
              <th>Ciudad</th>
              <th class="sortable" data-col="admin_status" onclick="sortBy('admin_status')">Estado <span class="sort-icon"></span></th>
              <th>Publicación</th>
              <th class="sortable" data-col="created_at" onclick="sortBy('created_at')">Fecha <span class="sort-icon"></span></th>
              <th style="text-align:right;">Acciones</th>
            </tr>
          </thead>
          <tbody id="tableBody">
            <!-- filled by JS -->
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="gp-pagination" id="paginationBar">
        <div class="gp-per-page">
          <span>Mostrar</span>
          <select id="perPage" onchange="changePerPage(this.value)">
            <option value="15">15</option>
            <option value="25" selected>25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
          <span>por página</span>
        </div>
        <div class="gp-page-info" id="pageInfo"></div>
        <div class="gp-page-btns" id="pageBtns"></div>
      </div>
    </div>

  </main>
</div><!-- /shell -->

<!-- ══════════════════════════════════
     DETAIL PANEL
══════════════════════════════════ -->
<aside class="gp-detail" id="detailPanel">
  <div class="gp-detail__topbar">
    <h3><i class="bi bi-box-seam-fill" style="color:var(--g600);margin-right:6px;"></i>Detalle del producto</h3>
    <button class="gp-detail__close" onclick="closeDetail()"><i class="bi bi-x-lg"></i></button>
  </div>
  <div class="gp-detail__body" id="detailBody">
    <!-- Injected by JS -->
  </div>
  <div class="gp-detail__actions" id="detailActions">
    <!-- Injected by JS -->
  </div>
</aside>

<!-- ══════════════════════════════════
     CONFIRM MODAL
══════════════════════════════════ -->
<div class="gp-modal-backdrop" id="confirmModal">
  <div class="gp-modal">
    <div class="gp-modal__header">
      <div class="gp-modal__icon" id="modalIcon"><i class="bi bi-exclamation-triangle-fill"></i></div>
    </div>
    <div class="gp-modal__body">
      <h3 id="modalTitle">¿Confirmar acción?</h3>
      <p id="modalMsg">Esta acción modificará el estado del producto.</p>
      <div class="gp-modal__btns">
        <button class="gp-modal-cancel" onclick="closeModal()">Cancelar</button>
        <button id="modalOkBtn" class="gp-modal-ok-red" onclick="execModalAction()">Confirmar</button>
      </div>
    </div>
  </div>
</div>

<!-- Toasts -->
<div class="gp-toasts" id="toastArea"></div>

<!-- ══════════════════════════════════
     JAVASCRIPT
══════════════════════════════════ -->
<script>
const API = '../api/admin_products.php';

let state = {
  page: 1, limit: 25,
  sort: 'created_at', dir: 'DESC',
  search: '', category_id: '', admin_status: '', city: '',
  date_from: '', date_to: '',
};

let pendingAction    = null;
let currentDetailId  = null;
let searchTimer      = null;
let cityTimer        = null;
let selectedRows     = new Set();

/* ══ BOOT ══ */
document.addEventListener('DOMContentLoaded', () => {
  loadStats();
  loadCategories();
  loadTable();

  document.getElementById('searchQ').addEventListener('input', function () {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
      state.search = this.value.trim();
      state.page   = 1;
      loadTable();
    }, 380);
  });

  document.getElementById('topbarQ').addEventListener('input', function () {
    document.getElementById('searchQ').value = this.value;
    document.getElementById('searchQ').dispatchEvent(new Event('input'));
  });

  document.getElementById('confirmModal').addEventListener('click', e => {
    if (e.target === document.getElementById('confirmModal')) closeModal();
  });
});

/* ══ STATS ══ */
async function loadStats() {
  try {
    const json = await fetch(`${API}?action=stats`).then(r => r.json());
    if (!json.ok) return;
    const d = json.data.stats;
    document.getElementById('s-total').textContent      = fmt(d.total);
    document.getElementById('s-active').textContent     = fmt(d.active);
    document.getElementById('s-inactive').textContent   = fmt(d.inactive);
    document.getElementById('s-deleted').textContent    = fmt(d.deleted);
    document.getElementById('s-disponible').textContent = fmt(d.disponible);
    document.getElementById('s-vendido').textContent    = fmt(d.vendido);
  } catch(e) { console.warn('Stats error', e); }
}

/* ══ CATEGORIES ══ */
async function loadCategories() {
  try {
    const json = await fetch(`${API}?action=categories`).then(r => r.json());
    if (!json.ok) return;
    const sel = document.getElementById('fCategory');
    json.data.forEach(c => {
      const opt  = document.createElement('option');
      opt.value  = c.id;
      opt.textContent = c.name;
      sel.appendChild(opt);
    });
  } catch(e) {}
}

/* ══ LOAD TABLE ══ */
async function loadTable() {
  showLoading(true);
  const params = new URLSearchParams({
    action:       'list',
    page:         state.page,
    limit:        state.limit,
    sort:         state.sort,
    dir:          state.dir,
    search:       state.search,
    category_id:  state.category_id,
    admin_status: state.admin_status,
    city:         state.city,
    date_from:    state.date_from,
    date_to:      state.date_to,
  });
  try {
    const json = await fetch(`${API}?${params}`).then(r => r.json());
    if (!json.ok) { toast('error', 'Error', json.error); showLoading(false); return; }
    renderTable(json.data);
    renderPagination(json.pagination);
    updateSortHeaders();
  } catch(e) {
    toast('error', 'Error de red', 'No se pudo conectar con el servidor');
  }
  showLoading(false);
}

/* ══ RENDER TABLE ══ */
function renderTable(rows) {
  const tbody = document.getElementById('tableBody');
  if (!rows || rows.length === 0) {
    tbody.innerHTML = `<tr><td colspan="10">
      <div class="gp-empty">
        <div class="gp-empty-icon">📦</div>
        <h3>No se encontraron productos</h3>
        <p>Intenta con otros filtros o términos de búsqueda.</p>
      </div></td></tr>`;
    document.getElementById('resultsInfo').innerHTML = '<strong>0</strong> resultados';
    return;
  }

  tbody.innerHTML = rows.map(p => {
    const thumb    = thumbHTML(p);
    const stBadge  = adminStatusBadge(p.admin_status);
    const pubBadge = pubStatusBadge(p.product_status);
    const isSuspicious = p.title && p.title.length < 5;
    const flagHTML = isSuspicious
      ? `<span class="gp-flag"><i class="bi bi-exclamation-triangle-fill"></i> Revisar</span>`
      : '';
    const condHTML = p.condition_type
      ? `<span class="condition ${esc(p.condition_type)}">${condLabel(p.condition_type)}</span>` : '';
    const sel = selectedRows.has(p.id) ? 'selected' : '';

    return `<tr class="${sel}" id="row-${p.id}">
      <td><input type="checkbox" class="row-cb" data-id="${p.id}" ${selectedRows.has(p.id)?'checked':''} onchange="toggleRow(${p.id},this)"></td>
      <td>
        <div class="gp-prod-cell">
          ${thumb}
          <div class="gp-prod-info">
            <strong title="${esc(p.title)}" onclick="openDetail(${p.id})">${esc(p.title)}</strong>
            <div style="display:flex;gap:5px;align-items:center;flex-wrap:wrap;margin-top:2px;">
              ${condHTML}
              ${flagHTML}
            </div>
          </div>
        </div>
      </td>
      <td><span class="gp-price">${fmtPrice(p.price)}</span></td>
      <td style="font-size:.82rem;color:var(--slate600);">${esc(p.category_name || '—')}</td>
      <td>
        <div class="gp-seller-cell">
          <strong>${esc(p.seller_name || '—')}</strong>
          <span>${esc(p.seller_email || '')}</span>
        </div>
      </td>
      <td style="font-size:.82rem;color:var(--slate500);">${esc(p.city || '—')}</td>
      <td>${stBadge}</td>
      <td>${pubBadge}</td>
      <td style="font-size:.78rem;color:var(--slate500);white-space:nowrap;">${fmtDate(p.created_at)}</td>
      <td>
        <div class="gp-actions">
          <button class="gp-act-btn view"       title="Ver detalle"    onclick="openDetail(${p.id})"><i class="bi bi-eye-fill"></i></button>
          ${p.admin_status !== 'active'
            ? `<button class="gp-act-btn activate"   title="Activar"        onclick="promptAction('activate',${p.id},'${esc(p.title)}')"><i class="bi bi-check-circle-fill"></i></button>`
            : `<button class="gp-act-btn deactivate" title="Desactivar"     onclick="promptAction('deactivate',${p.id},'${esc(p.title)}')"><i class="bi bi-pause-circle-fill"></i></button>`
          }
          <button class="gp-act-btn delete" title="Eliminar producto" onclick="promptAction('delete',${p.id},'${esc(p.title)}')"><i class="bi bi-trash3-fill"></i></button>
        </div>
      </td>
    </tr>`;
  }).join('');
}

/* ══ PAGINATION ══ */
function renderPagination(p) {
  const { page, limit, total, total_pages } = p;
  const from = total === 0 ? 0 : (page - 1) * limit + 1;
  const to   = Math.min(page * limit, total);

  document.getElementById('resultsInfo').innerHTML =
    `<strong>${fmt(from)}–${fmt(to)}</strong> de <strong>${fmt(total)}</strong> productos`;
  document.getElementById('pageInfo').innerHTML =
    total === 0 ? '' : `Página <strong>${page}</strong> de <strong>${total_pages}</strong>`;

  const btns = document.getElementById('pageBtns');
  btns.innerHTML = '';
  const add = (label, pg, disabled=false, active=false) => {
    const b = document.createElement('button');
    b.className = 'gp-page-btn' + (active ? ' active' : '');
    b.innerHTML = label;
    b.disabled  = disabled;
    b.onclick   = () => { state.page = pg; loadTable(); };
    btns.appendChild(b);
  };
  add('<i class="bi bi-chevron-double-left"></i>', 1, page === 1);
  add('<i class="bi bi-chevron-left"></i>', page - 1, page === 1);
  const pages = buildPageNums(page, total_pages);
  let prev = null;
  pages.forEach(pg => {
    if (prev !== null && pg - prev > 1) {
      const d = document.createElement('span'); d.className = 'gp-page-dots'; d.textContent = '…'; btns.appendChild(d);
    }
    add(pg, pg, false, pg === page);
    prev = pg;
  });
  add('<i class="bi bi-chevron-right"></i>', page + 1, page === total_pages || total_pages === 0);
  add('<i class="bi bi-chevron-double-right"></i>', total_pages, page === total_pages || total_pages === 0);
}

function buildPageNums(cur, total) {
  if (total <= 7) return Array.from({length: total}, (_, i) => i + 1);
  const s = new Set([1, 2, cur-1, cur, cur+1, total-1, total].filter(n => n >= 1 && n <= total));
  return [...s].sort((a, b) => a - b);
}

/* ══ SORT ══ */
function sortBy(col) {
  if (state.sort === col) state.dir = state.dir === 'ASC' ? 'DESC' : 'ASC';
  else { state.sort = col; state.dir = 'ASC'; }
  state.page = 1;
  updateSortHeaders();
  loadTable();
}
function updateSortHeaders() {
  document.querySelectorAll('.gp-table thead th[data-col]').forEach(th => {
    th.classList.remove('sort-asc','sort-desc');
    if (th.dataset.col === state.sort) th.classList.add(state.dir === 'ASC' ? 'sort-asc' : 'sort-desc');
  });
}

/* ══ FILTERS ══ */
function applyFilters() {
  state.category_id  = document.getElementById('fCategory').value;
  state.admin_status = document.getElementById('fStatus').value;
  state.date_from    = document.getElementById('fDateFrom').value;
  state.date_to      = document.getElementById('fDateTo').value;
  state.page = 1;
  document.querySelectorAll('.gp-filter').forEach(s => {
    s.classList.toggle('active', s.value !== '' && s.tagName === 'SELECT');
  });
  loadTable();
}
function debouncedCityFilter() {
  clearTimeout(cityTimer);
  cityTimer = setTimeout(() => {
    state.city  = document.getElementById('fCity').value.trim();
    state.page  = 1;
    loadTable();
  }, 420);
}
function clearSearch() {
  document.getElementById('searchQ').value = '';
  state.search = ''; state.page = 1;
  loadTable();
}
function changePerPage(val) {
  state.limit = parseInt(val); state.page = 1; loadTable();
}

/* ══ CHECKBOXES ══ */
function toggleAll(cb) {
  document.querySelectorAll('.row-cb').forEach(c => {
    c.checked = cb.checked;
    const id  = parseInt(c.dataset.id);
    cb.checked ? selectedRows.add(id) : selectedRows.delete(id);
    document.getElementById(`row-${id}`)?.classList.toggle('selected', cb.checked);
  });
}
function toggleRow(id, cb) {
  cb.checked ? selectedRows.add(id) : selectedRows.delete(id);
  document.getElementById(`row-${id}`)?.classList.toggle('selected', cb.checked);
}

/* ══ DETAIL PANEL ══ */
async function openDetail(id) {
  currentDetailId = id;
  document.getElementById('detailPanel').classList.add('open');
  document.getElementById('mainArea').classList.add('panel-open');
  document.getElementById('detailBody').innerHTML = `
    <div style="display:flex;align-items:center;justify-content:center;min-height:300px;">
      <div class="gp-spinner"></div>
    </div>`;
  document.getElementById('detailActions').innerHTML = '';

  try {
    const json = await fetch(`${API}?action=detail&id=${id}`).then(r => r.json());
    if (!json.ok) { toast('error', 'Error', json.error); return; }
    renderDetail(json.data);
  } catch(e) { toast('error', 'Error de red', 'No se pudo cargar el detalle'); }
}

function closeDetail() {
  document.getElementById('detailPanel').classList.remove('open');
  document.getElementById('mainArea').classList.remove('panel-open');
  currentDetailId = null;
}

function renderDetail(p) {
  /* Highlight table row */
  document.querySelectorAll('.gp-table tbody tr').forEach(tr => tr.classList.remove('selected'));
  document.getElementById(`row-${p.id}`)?.classList.add('selected');

  const stBadge  = adminStatusBadge(p.admin_status);
  const pubBadge = pubStatusBadge(p.product_status);
  const condHTML = p.condition_type
    ? `<span class="gp-badge" style="background:var(--g100);color:var(--g700);">${condLabel(p.condition_type)}</span>` : '';

  /* Gallery */
  const imgs   = p.images || [];
  const imgDir = './productos/uploads/';
  let galleryHTML;
  if (imgs.length > 0) {
    const mainSrc = imgDir + esc(imgs[0].image_url);
    const thumbsHTML = imgs.map((img, i) =>
      `<img class="gp-gallery__thumb${i===0?' active':''}" src="${imgDir}${esc(img.image_url)}"
            alt="" onclick="switchMainImg(this,'${imgDir}${esc(img.image_url)}')">`
    ).join('');
    galleryHTML = `
      <div class="gp-gallery">
        <img class="gp-gallery__main" id="galleryMain" src="${mainSrc}" alt="${esc(p.title)}">
        ${imgs.length > 1 ? `<div class="gp-gallery__thumbs">${thumbsHTML}</div>` : ''}
      </div>`;
  } else {
    galleryHTML = `
      <div class="gp-gallery">
        <div class="gp-gallery__placeholder"><i class="bi bi-image"></i></div>
      </div>`;
  }

  /* Seller initial */
  const sellerInitial = (p.seller_name || 'V').charAt(0).toUpperCase();

  document.getElementById('detailBody').innerHTML = `
    ${galleryHTML}

    <div class="gp-detail-section">
      <div class="gp-detail-section__title"><i class="bi bi-box-seam-fill"></i> Información del producto</div>
      <div style="margin-bottom:12px;display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
        <div style="font-size:1.4rem;font-weight:800;color:var(--g700);">${fmtPrice(p.price)}</div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;">${stBadge}${pubBadge}${condHTML}</div>
      </div>
      <div class="gp-info-list">
        <div class="gp-info-row"><span class="lbl">ID</span><span class="val">#${p.id}</span></div>
        <div class="gp-info-row"><span class="lbl">Título</span><span class="val">${esc(p.title)}</span></div>
        <div class="gp-info-row"><span class="lbl">Categoría</span><span class="val">${esc(p.category_name || '—')}</span></div>
        <div class="gp-info-row"><span class="lbl">Ciudad</span><span class="val">${esc(p.city || '—')}</span></div>
        <div class="gp-info-row"><span class="lbl">Publicado</span><span class="val">${fmtDateFull(p.created_at)}</span></div>
        ${p.updated_at ? `<div class="gp-info-row"><span class="lbl">Actualizado</span><span class="val">${fmtDateFull(p.updated_at)}</span></div>` : ''}
      </div>
    </div>

    ${p.description ? `
    <div class="gp-detail-section">
      <div class="gp-detail-section__title"><i class="bi bi-card-text"></i> Descripción</div>
      <div class="gp-description">${esc(p.description)}</div>
    </div>` : ''}

    <div class="gp-detail-section">
      <div class="gp-detail-section__title"><i class="bi bi-person-fill"></i> Vendedor</div>
      <div class="gp-seller-card">
        <div class="gp-seller-card__header">
          <div class="gp-seller-avatar">${sellerInitial}</div>
          <div>
            <span class="gp-seller-card__name">${esc(p.seller_name || '—')}</span>
            <span class="gp-seller-card__role">ID vendedor: #${p.user_id}</span>
          </div>
        </div>
        <div class="gp-info-list">
          ${p.seller_email ? `<div class="gp-info-row"><span class="lbl">Correo</span><span class="val"><a href="mailto:${esc(p.seller_email)}">${esc(p.seller_email)}</a></span></div>` : ''}
          ${p.seller_phone ? `<div class="gp-info-row"><span class="lbl">Teléfono</span><span class="val">${esc(p.seller_phone)}</span></div>` : ''}
          ${p.city         ? `<div class="gp-info-row"><span class="lbl">Ciudad</span><span class="val">${esc(p.city)}</span></div>` : ''}
        </div>
      </div>
    </div>
  `;

  /* Action buttons */
  const isActive = p.admin_status === 'active';
  document.getElementById('detailActions').innerHTML = `
    ${!isActive
      ? `<button class="gp-detail-btn gp-detail-btn--activate" onclick="promptAction('activate',${p.id},'${esc(p.title)}')"><i class="bi bi-check-circle-fill"></i> Activar producto</button>`
      : `<button class="gp-detail-btn gp-detail-btn--deactivate" onclick="promptAction('deactivate',${p.id},'${esc(p.title)}')"><i class="bi bi-pause-circle-fill"></i> Desactivar producto</button>`}
    <button class="gp-detail-btn gp-detail-btn--delete" onclick="promptAction('delete',${p.id},'${esc(p.title)}')"><i class="bi bi-trash3-fill"></i> Eliminar producto</button>
  `;
}

function switchMainImg(thumb, src) {
  document.getElementById('galleryMain').src = src;
  document.querySelectorAll('.gp-gallery__thumb').forEach(t => t.classList.remove('active'));
  thumb.classList.add('active');
}

/* ══ ACTIONS ══ */
function promptAction(action, id, title) {
  const cfg = {
    activate: {
      icon:'green', ico:'bi-check-circle-fill',
      title:`Activar "${title}"`,
      msg:'El producto volverá a estar visible en el marketplace.',
      okClass:'gp-modal-ok-green', okLabel:'Activar'
    },
    deactivate: {
      icon:'yellow', ico:'bi-pause-circle-fill',
      title:`Desactivar "${title}"`,
      msg:'El producto dejará de mostrarse a los compradores. El vendedor será notificado.',
      okClass:'gp-modal-ok-yellow', okLabel:'Desactivar'
    },
    delete: {
      icon:'red', ico:'bi-trash3-fill',
      title:`Eliminar "${title}"`,
      msg:'⚠️ Esta acción marca el producto como eliminado. El vendedor no podrá verlo ni recuperarlo desde su cuenta.',
      okClass:'gp-modal-ok-red', okLabel:'Eliminar definitivamente'
    },
  };
  const c = cfg[action]; if (!c) return;
  pendingAction = { action, id };
  document.getElementById('modalIcon').className = `gp-modal__icon ${c.icon}`;
  document.getElementById('modalIcon').innerHTML = `<i class="bi ${c.ico}"></i>`;
  document.getElementById('modalTitle').textContent  = c.title;
  document.getElementById('modalMsg').innerHTML      = c.msg;
  document.getElementById('modalOkBtn').className    = c.okClass;
  document.getElementById('modalOkBtn').textContent  = c.okLabel;
  document.getElementById('confirmModal').classList.add('open');
}

function closeModal() {
  document.getElementById('confirmModal').classList.remove('open');
  pendingAction = null;
}

async function execModalAction() {
  if (!pendingAction) return;
  closeModal();
  const { action, id } = pendingAction;
  const statusMap = { activate: 'active', deactivate: 'inactive', delete: 'deleted' };
  const newStatus = statusMap[action];

  try {
    const res = await fetch(`${API}?action=set_status`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id, status: newStatus }),
    });
    const json = await res.json();
    if (json.ok) {
      const labels = { active: 'activado', inactive: 'desactivado', deleted: 'eliminado' };
      toast('success', 'Listo', `Producto ${labels[newStatus]} correctamente.`);
      loadTable(); loadStats();
      if (currentDetailId === id) {
        newStatus === 'deleted' ? closeDetail() : openDetail(id);
      }
    } else {
      toast('error', 'Error', json.error);
    }
  } catch(e) { toast('error', 'Error de red', 'No se pudo completar la acción'); }
}

/* ══ EXPORT CSV ══ */
function exportCSV() {
  const params = new URLSearchParams({
    action:'list', page:1, limit:10000,
    sort:state.sort, dir:state.dir,
    search:state.search, category_id:state.category_id,
    admin_status:state.admin_status, city:state.city,
    date_from:state.date_from, date_to:state.date_to,
  });
  fetch(`${API}?${params}`).then(r => r.json()).then(json => {
    if (!json.ok) return;
    const cols = ['id','title','price','category_name','seller_name','city','admin_status','product_status','created_at'];
    const rows = [cols.join(',')].concat(
      json.data.map(p => cols.map(c => `"${(p[c]??'').toString().replace(/"/g,'""')}"`).join(','))
    );
    const blob = new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `comerciolocal_productos_${new Date().toISOString().slice(0,10)}.csv`;
    a.click();
    toast('info', 'Exportación lista', `${json.data.length} productos exportados.`);
  });
}

/* ══ UI HELPERS ══ */
function showLoading(on) {
  document.getElementById('loadingOverlay').classList.toggle('active', on);
}

function thumbHTML(p) {
  const src = p.thumb ? `./productos/uploads/${esc(p.thumb)}` : null;
  return `<div class="gp-thumb" onclick="openDetail(${p.id})" title="Ver detalle">
    ${src ? `<img src="${src}" alt="${esc(p.title)}" onerror="this.style.display='none';this.parentElement.innerHTML='<i class=\\'bi bi-image\\'></i>'">` : '<i class="bi bi-image"></i>'}
  </div>`;
}

function adminStatusBadge(s) {
  const map = {
    active:   ['active',   'bi-check-circle-fill', 'Activo'],
    inactive: ['inactive', 'bi-pause-circle-fill', 'Inactivo'],
    deleted:  ['deleted',  'bi-trash3-fill',        'Eliminado'],
  };
  const [cls, ico, lbl] = map[s] ?? ['inactive','bi-question-circle','—'];
  return `<span class="gp-badge ${cls}"><i class="bi ${ico}"></i> ${lbl}</span>`;
}

function pubStatusBadge(s) {
  if (s === 'disponible') return `<span class="gp-badge disponible"><i class="bi bi-tag-fill"></i> Disponible</span>`;
  if (s === 'vendido')    return `<span class="gp-badge vendido"><i class="bi bi-bag-check-fill"></i> Vendido</span>`;
  return `<span class="gp-badge" style="background:var(--slate100);color:var(--slate500);">—</span>`;
}

function condLabel(c) {
  return {nuevo:'Nuevo', usado:'Usado', reacondicionado:'Reacondicionado'}[c] ?? c;
}

function fmtDate(dt) {
  if (!dt) return '—';
  const d = new Date(dt);
  return isNaN(d) ? dt : d.toLocaleDateString('es-CO', {day:'2-digit',month:'short',year:'numeric'});
}
function fmtDateFull(dt) {
  if (!dt) return '—';
  const d = new Date(dt);
  return isNaN(d) ? dt : d.toLocaleString('es-CO', {day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'});
}
function fmtPrice(p) {
  if (!p && p !== 0) return '—';
  return '$' + parseFloat(p).toLocaleString('es-CO');
}
function fmt(n) { return Number(n).toLocaleString('es-CO'); }
function esc(s) {
  return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

/* ══ TOAST ══ */
function toast(type, title, msg) {
  const icons = { success:'bi-check-circle-fill', error:'bi-x-circle-fill', info:'bi-info-circle-fill', warning:'bi-exclamation-triangle-fill' };
  const t = document.createElement('div');
  t.className = `gp-toast ${type}`;
  t.innerHTML = `<i class="bi ${icons[type]??'bi-info-circle-fill'}"></i>
    <div><strong>${title}</strong><br><span style="font-weight:400;font-size:.8rem;">${msg}</span></div>
    <button class="gp-toast__x" onclick="this.parentElement.remove()"><i class="bi bi-x-lg"></i></button>`;
  document.getElementById('toastArea').appendChild(t);
  setTimeout(() => { t.classList.add('out'); setTimeout(() => t.remove(), 300); }, 4500);
}
</script>
</body>
</html>

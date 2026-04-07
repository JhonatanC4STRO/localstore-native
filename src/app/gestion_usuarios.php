<?php
/**
 * Gestión de Usuarios — ComercioLocal Admin
 * Data-driven via /api/admin_users.php JSON API
 */
require_once("../config/conexion.php");
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ./auth/login.php"); exit();
}
if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    header("Location: ./inde.php"); exit();
}

$user        = $_SESSION['user'];
$userInitial = strtoupper(mb_substr($user['full_name'], 0, 1));
$userName    = explode(' ', $user['full_name'])[0];

/* API base (same-origin) */
$api = '../api/admin_users.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de Usuarios — ComercioLocal Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../style/gestion_usuarios.css">
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
    <input type="text" placeholder="Buscar en el panel…" id="topbarQ">
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
    <a class="adm-sb-link active" href="./gestion_usuarios.php">
      <i class="bi bi-people"></i>Gestión de usuarios
    </a>
    <a class="adm-sb-link" href="./gestion_productos.php"><i class="bi bi-box-seam"></i>Gestión de productos</a>
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
      <strong>👥 Gestión segura</strong>
      <p>Todas las acciones sobre usuarios quedan registradas en el log.</p>
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
          <span>Gestión de usuarios</span>
        </div>
        <h1>Gestión de usuarios</h1>
        <p>Administra cuentas, roles y estados de todos los usuarios registrados en la plataforma.</p>
      </div>
      <button class="gu-btn-export" id="refreshBtn" onclick="loadStats(); loadTable();" style="height:42px;padding:0 18px;">
        <i class="bi bi-arrow-clockwise"></i> Actualizar
      </button>
    </div>

    <!-- Stats row -->
    <div class="gu-stats" id="statsRow">
      <!-- filled by JS -->
      <div class="gu-stat gu-stat--total"><div class="gu-stat__icon"><i class="bi bi-people-fill"></i></div><div class="gu-stat__num" id="s-total">—</div><div class="gu-stat__lbl">Total usuarios</div></div>
      <div class="gu-stat gu-stat--active"><div class="gu-stat__icon"><i class="bi bi-check-circle-fill"></i></div><div class="gu-stat__num" id="s-active">—</div><div class="gu-stat__lbl">Activos</div></div>
      <div class="gu-stat gu-stat--blocked"><div class="gu-stat__icon"><i class="bi bi-slash-circle-fill"></i></div><div class="gu-stat__num" id="s-blocked">—</div><div class="gu-stat__lbl">Bloqueados</div></div>
      <div class="gu-stat gu-stat--suspended"><div class="gu-stat__icon"><i class="bi bi-pause-circle-fill"></i></div><div class="gu-stat__num" id="s-suspended">—</div><div class="gu-stat__lbl">Suspendidos</div></div>
      <div class="gu-stat gu-stat--sellers"><div class="gu-stat__icon"><i class="bi bi-shop"></i></div><div class="gu-stat__num" id="s-sellers">—</div><div class="gu-stat__lbl">Vendedores</div></div>
      <div class="gu-stat gu-stat--buyers"><div class="gu-stat__icon"><i class="bi bi-bag"></i></div><div class="gu-stat__num" id="s-buyers">—</div><div class="gu-stat__lbl">Compradores</div></div>
      <div class="gu-stat gu-stat--admins"><div class="gu-stat__icon"><i class="bi bi-shield-fill"></i></div><div class="gu-stat__num" id="s-admins">—</div><div class="gu-stat__lbl">Admins</div></div>
    </div>

    <!-- Toolbar: search + filters -->
    <div class="gu-toolbar">
      <!-- Search -->
      <div class="gu-search">
        <i class="bi bi-search si"></i>
        <input type="text" id="searchQ" placeholder="Buscar por nombre, correo, teléfono…" autocomplete="off">
        <button class="sc" onclick="clearSearch()" title="Limpiar"><i class="bi bi-x-lg"></i></button>
      </div>

      <div class="gu-toolbar__sep"></div>

      <!-- Role filter -->
      <select class="gu-filter" id="fRole" onchange="applyFilters()">
        <option value="">Todos los roles</option>
        <option value="user">Comprador</option>
        <option value="seller">Vendedor</option>
        <option value="admin">Admin</option>
      </select>

      <!-- Status filter -->
      <select class="gu-filter" id="fStatus" onchange="applyFilters()">
        <option value="">Todos los estados</option>
        <option value="active">Activo</option>
        <option value="blocked">Bloqueado</option>
        <option value="suspended">Suspendido</option>
      </select>

      <!-- Verified filter -->
      <select class="gu-filter" id="fVerified" onchange="applyFilters()">
        <option value="">Verificación: todas</option>
        <option value="1">Solo verificados</option>
        <option value="0">Sin verificar</option>
      </select>

      <!-- Date range -->
      <input type="date" class="gu-date" id="fDateFrom" onchange="applyFilters()" title="Desde">
      <input type="date" class="gu-date" id="fDateTo"   onchange="applyFilters()" title="Hasta">

      <div class="gu-toolbar__sep"></div>

      <span class="gu-results-info" id="resultsInfo">Cargando…</span>

      <button class="gu-btn-export" onclick="exportCSV()"><i class="bi bi-download"></i> CSV</button>
    </div>

    <!-- Table card -->
    <div class="gu-table-card">
      <div class="gu-table-wrap">
        <div class="gu-loading-overlay active" id="loadingOverlay">
          <div class="gu-spinner"></div>
        </div>
        <table class="gu-table" id="usersTable">
          <thead>
            <tr>
              <th style="width:36px;"><input type="checkbox" id="selectAll" onchange="toggleAll(this)" title="Seleccionar todos"></th>
              <th class="sortable" data-col="full_name" onclick="sortBy('full_name')">
                Usuario <span class="sort-icon"></span>
              </th>
              <th class="sortable" data-col="role" onclick="sortBy('role')">
                Rol <span class="sort-icon"></span>
              </th>
              <th class="sortable" data-col="status" onclick="sortBy('status')">
                Estado <span class="sort-icon"></span>
              </th>
              <th>Verificado</th>
              <th class="sortable" data-col="city" onclick="sortBy('city')">
                Ciudad <span class="sort-icon"></span>
              </th>
              <th>Productos</th>
              <th class="sortable" data-col="created_at" onclick="sortBy('created_at')">
                Registro <span class="sort-icon"></span>
              </th>
              <th style="text-align:right;">Acciones</th>
            </tr>
          </thead>
          <tbody id="tableBody">
            <!-- rows injected by JS -->
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="gu-pagination" id="paginationBar">
        <div class="gu-per-page">
          <span>Mostrar</span>
          <select id="perPage" onchange="changePerPage(this.value)">
            <option value="15">15</option>
            <option value="25" selected>25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
          <span>por página</span>
        </div>
        <div class="gu-page-info" id="pageInfo"></div>
        <div class="gu-page-btns" id="pageBtns"></div>
      </div>
    </div>

  </main>
</div><!-- /shell -->

<!-- ══════════════════════════════════
     DETAIL PANEL (slide-in)
══════════════════════════════════ -->
<aside class="gu-detail" id="detailPanel">
  <div class="gu-detail__topbar">
    <h3><i class="bi bi-person-lines-fill" style="color:var(--g600);margin-right:6px;"></i>Perfil del usuario</h3>
    <button class="gu-detail__close" onclick="closeDetail()"><i class="bi bi-x-lg"></i></button>
  </div>

  <div class="gu-detail__body" id="detailBody">
    <!-- Injected by loadDetail() -->
  </div>

  <div class="gu-detail__actions" id="detailActions">
    <!-- Injected by loadDetail() -->
  </div>
</aside>

<!-- ══════════════════════════════════
     CONFIRM MODAL
══════════════════════════════════ -->
<div class="gu-modal-backdrop" id="confirmModal">
  <div class="gu-modal">
    <div class="gu-modal__header">
      <div class="gu-modal__icon" id="modalIcon"><i class="bi bi-exclamation-triangle-fill"></i></div>
      <div></div>
    </div>
    <div class="gu-modal__body">
      <h3 id="modalTitle">¿Confirmar acción?</h3>
      <p id="modalMsg">Esta acción modificará el estado del usuario.</p>
      <div class="gu-modal__btns">
        <button class="gu-modal-cancel" onclick="closeModal()">Cancelar</button>
        <button id="modalOkBtn" class="gu-modal-ok-red" onclick="execModalAction()">Confirmar</button>
      </div>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════
     ROLE CHANGE MODAL
══════════════════════════════════ -->
<div class="gu-modal-backdrop" id="roleModal">
  <div class="gu-modal">
    <div class="gu-modal__header">
      <div class="gu-modal__icon yellow"><i class="bi bi-person-gear"></i></div>
    </div>
    <div class="gu-modal__body">
      <h3 id="roleModalTitle">Cambiar rol</h3>
      <p id="roleModalMsg" style="margin-bottom:14px;">Selecciona el nuevo rol para este usuario.</p>
      <div class="gu-role-picker">
        <label class="gu-role-option user" onclick="selectRole('user',this)">
          <input type="radio" name="newRole" value="user">
          <div class="gu-role-option__icon"><i class="bi bi-bag"></i></div>
          <div class="gu-role-option__text">
            <strong>Comprador</strong>
            <span>Solo puede comprar y ver productos</span>
          </div>
        </label>
        <label class="gu-role-option seller" onclick="selectRole('seller',this)">
          <input type="radio" name="newRole" value="seller">
          <div class="gu-role-option__icon"><i class="bi bi-shop"></i></div>
          <div class="gu-role-option__text">
            <strong>Vendedor</strong>
            <span>Puede publicar y gestionar productos</span>
          </div>
        </label>
        <label class="gu-role-option admin" onclick="selectRole('admin',this)">
          <input type="radio" name="newRole" value="admin">
          <div class="gu-role-option__icon"><i class="bi bi-shield-fill"></i></div>
          <div class="gu-role-option__text">
            <strong>Administrador</strong>
            <span>Acceso completo al panel de administración</span>
          </div>
        </label>
      </div>
      <div class="gu-modal__btns">
        <button class="gu-modal-cancel" onclick="closeRoleModal()">Cancelar</button>
        <button id="roleOkBtn" class="gu-modal-ok-yellow" onclick="execRoleChange()">Aplicar cambio</button>
      </div>
    </div>
  </div>
</div>

<!-- Toasts -->
<div class="gu-toasts" id="toastArea"></div>

<!-- ══════════════════════════════════
     JAVASCRIPT
══════════════════════════════════ -->
<script>
/* ═══════════════════════════════
   STATE
═══════════════════════════════ */
const API = '../api/admin_users.php';

let state = {
  page: 1, limit: 25,
  sort: 'created_at', dir: 'DESC',
  search: '', role: '', status: '', verified: '',
  date_from: '', date_to: '',
};

let pendingAction = null; // { action, id, label }
let pendingRoleId = null;
let searchTimer   = null;
let selectedRows  = new Set();
let currentDetailId = null;

/* ═══════════════════════════════
   BOOT
═══════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
  loadStats();
  loadTable();

  /* Debounced search */
  document.getElementById('searchQ').addEventListener('input', function () {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
      state.search = this.value.trim();
      state.page   = 1;
      loadTable();
    }, 380);
  });

  /* Sync topbar search */
  document.getElementById('topbarQ').addEventListener('input', function() {
    document.getElementById('searchQ').value = this.value;
    document.getElementById('searchQ').dispatchEvent(new Event('input'));
  });

  /* Close modal on backdrop click */
  document.getElementById('confirmModal').addEventListener('click', e => {
    if (e.target === document.getElementById('confirmModal')) closeModal();
  });
  document.getElementById('roleModal').addEventListener('click', e => {
    if (e.target === document.getElementById('roleModal')) closeRoleModal();
  });
});

/* ═══════════════════════════════
   LOAD STATS
═══════════════════════════════ */
async function loadStats() {
  try {
    const res  = await fetch(`${API}?action=stats`);
    const json = await res.json();
    if (!json.ok) return;
    const d = json.data;
    document.getElementById('s-total').textContent     = fmt(d.total);
    document.getElementById('s-active').textContent    = fmt(d.active);
    document.getElementById('s-blocked').textContent   = fmt(d.blocked);
    document.getElementById('s-suspended').textContent = fmt(d.suspended);
    document.getElementById('s-sellers').textContent   = fmt(d.sellers);
    document.getElementById('s-buyers').textContent    = fmt(d.buyers);
    document.getElementById('s-admins').textContent    = fmt(d.admins);
  } catch(e) { console.warn('Stats error', e); }
}

/* ═══════════════════════════════
   LOAD TABLE
═══════════════════════════════ */
async function loadTable() {
  showLoading(true);

  const params = new URLSearchParams({
    action:    'list',
    page:      state.page,
    limit:     state.limit,
    sort:      state.sort,
    dir:       state.dir,
    search:    state.search,
    role:      state.role,
    status:    state.status,
    verified:  state.verified,
    date_from: state.date_from,
    date_to:   state.date_to,
  });

  try {
    const res  = await fetch(`${API}?${params}`);
    const json = await res.json();
    if (!json.ok) { showLoading(false); toast('error','Error al cargar',json.error); return; }
    renderTable(json.data);
    renderPagination(json.pagination);
    updateSortHeaders();
  } catch(e) {
    toast('error','Error de red', 'No se pudo conectar con el servidor');
  }

  showLoading(false);
}

/* ═══════════════════════════════
   RENDER TABLE
═══════════════════════════════ */
function renderTable(rows) {
  const tbody = document.getElementById('tableBody');

  if (!rows || rows.length === 0) {
    tbody.innerHTML = `
      <tr><td colspan="9">
        <div class="gu-empty">
          <div class="gu-empty-icon">🔍</div>
          <h3>No se encontraron usuarios</h3>
          <p>Intenta con otros filtros o términos de búsqueda.</p>
        </div>
      </td></tr>`;
    document.getElementById('resultsInfo').innerHTML = '<strong>0</strong> resultados';
    return;
  }

  tbody.innerHTML = rows.map(u => {
    const avatar  = avatarHTML(u);
    const status  = statusBadge(u.status);
    const role    = roleBadge(u.role);
    const verBadge= u.is_verified
      ? `<span class="gu-badge verified"><i class="bi bi-patch-check-fill"></i> Verificado</span>`
      : `<span class="gu-badge unverified"><i class="bi bi-dash-circle"></i> No verif.</span>`;
    const products = u.product_count > 0
      ? `<span style="font-weight:700;color:var(--g600);">${u.product_count}</span><span style="font-size:.75rem;color:var(--slate400);"> prod.</span>`
      : `<span style="color:var(--slate300);">—</span>`;
    const date = fmtDate(u.created_at);
    const sel  = selectedRows.has(u.id) ? 'selected' : '';

    return `<tr class="${sel}" id="row-${u.id}">
      <td><input type="checkbox" class="row-cb" data-id="${u.id}" ${selectedRows.has(u.id)?'checked':''} onchange="toggleRow(${u.id},this)"></td>
      <td>
        <div class="gu-user-cell" style="cursor:pointer;" onclick="openDetail(${u.id})">
          ${avatar}
          <div class="gu-user-info">
            <strong>${esc(u.full_name)}</strong>
            <span>${esc(u.email)}</span>
            ${u.phone ? `<span style="font-size:.73rem;color:var(--slate400);">${esc(u.phone)}</span>` : ''}
          </div>
        </div>
      </td>
      <td>
        <select class="gu-role-select" onchange="quickRoleChange(${u.id},this)" title="Cambiar rol">
          <option value="user"   ${u.role==='user'  ?'selected':''}>Comprador</option>
          <option value="seller" ${u.role==='seller'?'selected':''}>Vendedor</option>
          <option value="admin"  ${u.role==='admin' ?'selected':''}>Admin</option>
        </select>
      </td>
      <td>${status}</td>
      <td>${verBadge}</td>
      <td style="font-size:.84rem;color:var(--slate600);">${esc(u.city || '—')}</td>
      <td>${products}</td>
      <td style="font-size:.8rem;color:var(--slate500);white-space:nowrap;">${date}</td>
      <td>
        <div class="gu-actions" style="justify-content:flex-end;">
          <button class="gu-act-btn view"     title="Ver perfil"           onclick="openDetail(${u.id})"><i class="bi bi-eye-fill"></i></button>
          ${u.status !== 'active'
            ? `<button class="gu-act-btn activate" title="Activar cuenta" onclick="promptAction('activate',${u.id},'${esc(u.full_name)}')"><i class="bi bi-check-circle-fill"></i></button>`
            : `<button class="gu-act-btn block"    title="Bloquear cuenta" onclick="promptAction('block',${u.id},'${esc(u.full_name)}')"><i class="bi bi-slash-circle-fill"></i></button>`
          }
          <button class="gu-act-btn block" title="Suspender"              onclick="promptAction('suspend',${u.id},'${esc(u.full_name)}')"><i class="bi bi-pause-circle-fill"></i></button>
          <button class="gu-act-btn delete" title="Eliminar usuario"       onclick="promptAction('delete',${u.id},'${esc(u.full_name)}')"><i class="bi bi-trash3-fill"></i></button>
        </div>
      </td>
    </tr>`;
  }).join('');
}

/* ═══════════════════════════════
   RENDER PAGINATION
═══════════════════════════════ */
function renderPagination(p) {
  const { page, limit, total, total_pages } = p;
  const from = total === 0 ? 0 : (page - 1) * limit + 1;
  const to   = Math.min(page * limit, total);

  document.getElementById('resultsInfo').innerHTML =
    `<strong>${fmt(from)}–${fmt(to)}</strong> de <strong>${fmt(total)}</strong> usuarios`;

  document.getElementById('pageInfo').innerHTML =
    total === 0 ? '' : `Página <strong>${page}</strong> de <strong>${total_pages}</strong>`;

  const btns = document.getElementById('pageBtns');
  btns.innerHTML = '';

  const add = (label, pg, disabled=false, active=false) => {
    const b = document.createElement('button');
    b.className = 'gu-page-btn' + (active?' active':'');
    b.innerHTML = label;
    b.disabled  = disabled;
    b.onclick   = () => { state.page = pg; loadTable(); };
    btns.appendChild(b);
  };

  add('<i class="bi bi-chevron-double-left"></i>', 1, page === 1);
  add('<i class="bi bi-chevron-left"></i>', page - 1, page === 1);

  /* Page numbers with ellipsis */
  const pages = buildPageNums(page, total_pages);
  let prev = null;
  pages.forEach(pg => {
    if (prev !== null && pg - prev > 1) {
      const d = document.createElement('span');
      d.className = 'gu-page-dots'; d.textContent = '…';
      btns.appendChild(d);
    }
    add(pg, pg, false, pg === page);
    prev = pg;
  });

  add('<i class="bi bi-chevron-right"></i>', page + 1, page === total_pages || total_pages === 0);
  add('<i class="bi bi-chevron-double-right"></i>', total_pages, page === total_pages || total_pages === 0);
}

function buildPageNums(cur, total) {
  if (total <= 7) return Array.from({length: total}, (_,i) => i+1);
  const s = new Set([1, 2, cur-1, cur, cur+1, total-1, total].filter(n => n >= 1 && n <= total));
  return [...s].sort((a,b) => a-b);
}

/* ═══════════════════════════════
   SORT
═══════════════════════════════ */
function sortBy(col) {
  if (state.sort === col) state.dir = state.dir === 'ASC' ? 'DESC' : 'ASC';
  else { state.sort = col; state.dir = 'ASC'; }
  state.page = 1;
  updateSortHeaders();
  loadTable();
}

function updateSortHeaders() {
  document.querySelectorAll('.gu-table thead th[data-col]').forEach(th => {
    th.classList.remove('sort-asc','sort-desc');
    if (th.dataset.col === state.sort) th.classList.add(state.dir === 'ASC' ? 'sort-asc' : 'sort-desc');
  });
}

/* ═══════════════════════════════
   FILTERS
═══════════════════════════════ */
function applyFilters() {
  state.role      = document.getElementById('fRole').value;
  state.status    = document.getElementById('fStatus').value;
  state.verified  = document.getElementById('fVerified').value;
  state.date_from = document.getElementById('fDateFrom').value;
  state.date_to   = document.getElementById('fDateTo').value;
  state.page      = 1;

  document.querySelectorAll('.gu-filter').forEach(s => {
    s.classList.toggle('active', s.value !== '');
  });
  loadTable();
}

function clearSearch() {
  document.getElementById('searchQ').value = '';
  state.search = ''; state.page = 1;
  loadTable();
}

function changePerPage(val) {
  state.limit = parseInt(val); state.page = 1; loadTable();
}

/* ═══════════════════════════════
   SELECT ROWS
═══════════════════════════════ */
function toggleAll(cb) {
  document.querySelectorAll('.row-cb').forEach(c => {
    c.checked = cb.checked;
    const id = parseInt(c.dataset.id);
    if (cb.checked) selectedRows.add(id); else selectedRows.delete(id);
    document.getElementById(`row-${id}`)?.classList.toggle('selected', cb.checked);
  });
}

function toggleRow(id, cb) {
  if (cb.checked) selectedRows.add(id); else selectedRows.delete(id);
  document.getElementById(`row-${id}`)?.classList.toggle('selected', cb.checked);
}

/* ═══════════════════════════════
   OPEN DETAIL PANEL
═══════════════════════════════ */
async function openDetail(id) {
  currentDetailId = id;
  document.getElementById('detailPanel').classList.add('open');
  document.getElementById('mainArea').classList.add('panel-open');
  document.getElementById('detailBody').innerHTML = `
    <div style="display:flex;align-items:center;justify-content:center;min-height:300px;">
      <div class="gu-spinner"></div>
    </div>`;
  document.getElementById('detailActions').innerHTML = '';

  try {
    const res  = await fetch(`${API}?action=detail&id=${id}`);
    const json = await res.json();
    if (!json.ok) { toast('error','Error',json.error); return; }
    renderDetail(json.data);
  } catch(e) { toast('error','Error de red','No se pudo cargar el perfil'); }
}

function closeDetail() {
  document.getElementById('detailPanel').classList.remove('open');
  document.getElementById('mainArea').classList.remove('panel-open');
  currentDetailId = null;
  document.querySelectorAll('.gu-table tbody tr').forEach(tr => tr.classList.remove('selected'));
}

/* ═══════════════════════════════
   RENDER DETAIL PANEL
═══════════════════════════════ */
function renderDetail(u) {
  /* Highlight row */
  document.querySelectorAll('.gu-table tbody tr').forEach(tr => tr.classList.remove('selected'));
  document.getElementById(`row-${u.id}`)?.classList.add('selected');

  const avatarBg = avatarColor(u.full_name);
  const statusB  = statusBadge(u.status);
  const roleB    = roleBadge(u.role);
  const verB     = u.is_verified
    ? `<span class="gu-badge verified"><i class="bi bi-patch-check-fill"></i> Verificado</span>`
    : `<span class="gu-badge unverified"><i class="bi bi-dash-circle"></i> Sin verificar</span>`;

  /* Products */
  const prodHTML = u.products && u.products.length > 0
    ? u.products.map(p => `
      <div class="gu-mini-product">
        <div class="gu-mini-product__img">
          ${p.img ? `<img src="${esc(p.img)}" alt="${esc(p.title)}">` : '<i class="bi bi-image"></i>'}
        </div>
        <div class="gu-mini-product__info">
          <strong>${esc(p.title)}</strong>
          <span>${p.status == 1 ? '<span style="color:var(--g600);">Activo</span>' : '<span style="color:var(--slate400);">Inactivo</span>'}</span>
        </div>
        <div class="gu-mini-product__price">${fmtPrice(p.price)}</div>
      </div>`).join('')
    : `<p style="font-size:.84rem;color:var(--slate400);padding:8px 0;">Este usuario no tiene productos publicados.</p>`;

  document.getElementById('detailBody').innerHTML = `
    <div class="gu-profile-hero">
      <div class="gu-profile-avatar" style="background:${avatarBg};">
        ${u.profile_photo ? `<img src="../app/uploads/avatars/${esc(u.profile_photo)}" alt="${esc(u.full_name)}">` : ''}
        ${u.initials}
        ${u.is_verified ? '<div class="verified-ring"></div>' : ''}
      </div>
      <div class="gu-profile-name">${esc(u.full_name)}</div>
      <div class="gu-profile-email">${esc(u.email)}</div>
      <div class="gu-profile-badges">${statusB}${roleB}${verB}</div>
    </div>

    <div class="gu-detail-section">
      <div class="gu-detail-section__title"><i class="bi bi-person-fill"></i> Información de contacto</div>
      <div class="gu-info-list">
        <div class="gu-info-row"><span class="lbl">ID</span><span class="val">#${u.id}</span></div>
        <div class="gu-info-row"><span class="lbl">Nombre</span><span class="val">${esc(u.full_name)}</span></div>
        <div class="gu-info-row"><span class="lbl">Correo</span><span class="val"><a href="mailto:${esc(u.email)}">${esc(u.email)}</a></span></div>
        <div class="gu-info-row"><span class="lbl">Teléfono</span><span class="val">${esc(u.phone || '—')}</span></div>
        <div class="gu-info-row"><span class="lbl">Ciudad</span><span class="val">${esc(u.city || '—')}</span></div>
        ${u.bio ? `<div class="gu-info-row"><span class="lbl">Bio</span><span class="val" style="font-size:.8rem;line-height:1.5;">${esc(u.bio)}</span></div>` : ''}
      </div>
    </div>

    <div class="gu-detail-section">
      <div class="gu-detail-section__title"><i class="bi bi-shield-fill-check"></i> Estado de la cuenta</div>
      <div class="gu-info-list">
        <div class="gu-info-row"><span class="lbl">Estado</span><span class="val">${statusB}</span></div>
        <div class="gu-info-row"><span class="lbl">Rol</span><span class="val">${roleB}</span></div>
        <div class="gu-info-row"><span class="lbl">Verificado</span><span class="val">${verB}</span></div>
        <div class="gu-info-row"><span class="lbl">Registro</span><span class="val">${fmtDateFull(u.created_at)}</span></div>
        ${u.updated_at ? `<div class="gu-info-row"><span class="lbl">Actualizado</span><span class="val">${fmtDateFull(u.updated_at)}</span></div>` : ''}
      </div>
    </div>

    <div class="gu-detail-section">
      <div class="gu-detail-section__title"><i class="bi bi-box-seam-fill"></i> Productos
        <span style="margin-left:auto;font-size:.8rem;color:var(--slate400);">${u.product_count} total · ${u.active_products} activos</span>
      </div>
      ${prodHTML}
    </div>
  `;

  /* Action buttons based on current status */
  const isBlocked = u.status === 'blocked' || u.status === 'suspended';
  document.getElementById('detailActions').innerHTML = `
    ${isBlocked
      ? `<button class="gu-detail-btn gu-detail-btn--activate" onclick="promptAction('activate',${u.id},'${esc(u.full_name)}')"><i class="bi bi-check-circle-fill"></i> Activar cuenta</button>`
      : `<button class="gu-detail-btn gu-detail-btn--block" onclick="promptAction('block',${u.id},'${esc(u.full_name)}')"><i class="bi bi-slash-circle-fill"></i> Bloquear cuenta</button>`}
    <button class="gu-detail-btn gu-detail-btn--suspend" onclick="promptAction('suspend',${u.id},'${esc(u.full_name)}')"><i class="bi bi-pause-circle-fill"></i> Suspender temporalmente</button>
    <button class="gu-detail-btn gu-detail-btn--delete" onclick="promptAction('delete',${u.id},'${esc(u.full_name)}')"><i class="bi bi-trash3-fill"></i> Eliminar usuario</button>
  `;
}

/* ═══════════════════════════════
   ACTIONS
═══════════════════════════════ */
function promptAction(action, id, name) {
  const cfg = {
    activate: {
      icon:'green', iclass:'bi-check-circle-fill', title:`Activar a "${name}"`,
      msg:'La cuenta quedará activa y el usuario podrá acceder nuevamente a la plataforma.',
      okClass:'gu-modal-ok-green', okLabel:'Activar'
    },
    block: {
      icon:'red', iclass:'bi-slash-circle-fill', title:`Bloquear a "${name}"`,
      msg:'El usuario no podrá iniciar sesión ni realizar acciones en la plataforma. Puedes revertirlo en cualquier momento.',
      okClass:'gu-modal-ok-red', okLabel:'Bloquear'
    },
    suspend: {
      icon:'yellow', iclass:'bi-pause-circle-fill', title:`Suspender a "${name}"`,
      msg:'Se suspenderá temporalmente la cuenta. El usuario recibirá un aviso.',
      okClass:'gu-modal-ok-yellow', okLabel:'Suspender'
    },
    delete: {
      icon:'red', iclass:'bi-trash3-fill', title:`Eliminar a "${name}"`,
      msg:'⚠️ Esta acción es irreversible. El usuario será eliminado del sistema (borrado lógico). Sus productos serán desactivados.',
      okClass:'gu-modal-ok-red', okLabel:'Eliminar definitivamente'
    },
  };
  const c = cfg[action];
  if (!c) return;

  pendingAction = { action, id };
  document.getElementById('modalIcon').className = `gu-modal__icon ${c.icon}`;
  document.getElementById('modalIcon').innerHTML = `<i class="bi ${c.iclass}"></i>`;
  document.getElementById('modalTitle').textContent = c.title;
  document.getElementById('modalMsg').innerHTML     = c.msg;
  document.getElementById('modalOkBtn').className   = c.okClass;
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

  try {
    const res  = await fetch(`${API}?action=${action}`, {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({ id }),
    });
    const json = await res.json();
    if (json.ok) {
      const msgs = { block:'bloqueado', activate:'activado', suspend:'suspendido', delete:'eliminado' };
      toast('success','Listo', `Usuario ${msgs[action] || 'actualizado'} correctamente.`);
      loadTable(); loadStats();
      if (currentDetailId === id) {
        action === 'delete' ? closeDetail() : openDetail(id);
      }
    } else {
      toast('error','Error', json.error);
    }
  } catch(e) { toast('error','Error de red', 'No se pudo completar la acción'); }
}

/* ── Quick role change (inline select) ── */
function quickRoleChange(id, sel) {
  const newRole = sel.value;
  const row = document.getElementById(`row-${id}`);
  const name = row?.querySelector('.gu-user-info strong')?.textContent || `Usuario #${id}`;
  pendingRoleId = { id, newRole, sel };
  openRoleModal(id, name, newRole);
}

function openRoleModal(id, name, currentRole) {
  pendingRoleId = { id, sel: document.querySelector(`#row-${id} .gu-role-select`) };
  document.getElementById('roleModalTitle').textContent = `Cambiar rol de "${name}"`;
  document.querySelectorAll('.gu-role-option').forEach(opt => {
    opt.classList.remove('selected');
    const radio = opt.querySelector('input');
    if (radio.value === currentRole) {
      radio.checked = true; opt.classList.add('selected');
    } else { radio.checked = false; }
  });
  document.getElementById('roleModal').classList.add('open');
}

function selectRole(val, el) {
  document.querySelectorAll('.gu-role-option').forEach(o => o.classList.remove('selected'));
  el.classList.add('selected');
  el.querySelector('input').checked = true;
}

function closeRoleModal() {
  document.getElementById('roleModal').classList.remove('open');
  /* reset select to previous value */
  if (pendingRoleId?.sel) { loadTable(); } /* re-render table to sync */
  pendingRoleId = null;
}

async function execRoleChange() {
  const checked = document.querySelector('input[name="newRole"]:checked');
  if (!checked || !pendingRoleId) return;
  const { id } = pendingRoleId;
  const role    = checked.value;
  closeRoleModal();

  try {
    const res  = await fetch(`${API}?action=change_role`, {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({ id, role }),
    });
    const json = await res.json();
    if (json.ok) {
      toast('success','Rol actualizado', `El usuario ahora tiene el rol: ${role}`);
      loadTable(); loadStats();
      if (currentDetailId === id) openDetail(id);
    } else { toast('error','Error', json.error); }
  } catch(e) { toast('error','Error de red', 'No se pudo cambiar el rol'); }
}

/* ═══════════════════════════════
   EXPORT CSV
═══════════════════════════════ */
function exportCSV() {
  const params = new URLSearchParams({
    action:'list', page:1, limit:10000,
    sort:state.sort, dir:state.dir,
    search:state.search, role:state.role,
    status:state.status, verified:state.verified,
    date_from:state.date_from, date_to:state.date_to,
  });
  fetch(`${API}?${params}`).then(r=>r.json()).then(json => {
    if (!json.ok) return;
    const cols = ['id','full_name','email','phone','role','status','city','created_at','product_count'];
    const rows = [cols.join(',')].concat(json.data.map(u => cols.map(c => `"${(u[c]??'').toString().replace(/"/g,'""')}"`).join(',')));
    const blob = new Blob([rows.join('\n')], {type:'text/csv;charset=utf-8;'});
    const a = document.createElement('a'); a.href = URL.createObjectURL(blob);
    a.download = `comerciolocal_usuarios_${new Date().toISOString().slice(0,10)}.csv`;
    a.click();
    toast('info','Exportación lista', `${json.data.length} usuarios exportados.`);
  });
}

/* ═══════════════════════════════
   UI HELPERS
═══════════════════════════════ */
function showLoading(on) {
  document.getElementById('loadingOverlay').classList.toggle('active', on);
}

function statusBadge(s) {
  const map = {
    active:    ['active',    'bi-check-circle-fill',  'Activo'],
    blocked:   ['blocked',   'bi-slash-circle-fill',  'Bloqueado'],
    suspended: ['suspended', 'bi-pause-circle-fill',  'Suspendido'],
  };
  const [cls, ico, lbl] = map[s] ?? ['unverified','bi-question-circle','—'];
  return `<span class="gu-badge ${cls}"><i class="bi ${ico}"></i> ${lbl}</span>`;
}

function roleBadge(r) {
  const map = {
    user:   ['role-user',   'bi-bag',          'Comprador'],
    seller: ['role-seller', 'bi-shop',         'Vendedor'],
    admin:  ['role-admin',  'bi-shield-fill',  'Admin'],
  };
  const [cls, ico, lbl] = map[r] ?? ['role-user','bi-question','—'];
  return `<span class="gu-badge ${cls}"><i class="bi ${ico}"></i> ${lbl}</span>`;
}

const AVATAR_COLORS = [
  'linear-gradient(135deg,#16a34a,#22c55e)',
  'linear-gradient(135deg,#ca8a04,#facc15)',
  'linear-gradient(135deg,#3b82f6,#60a5fa)',
  'linear-gradient(135deg,#8b5cf6,#a78bfa)',
  'linear-gradient(135deg,#f97316,#fb923c)',
  'linear-gradient(135deg,#14b8a6,#2dd4bf)',
  'linear-gradient(135deg,#ec4899,#f472b6)',
];
function avatarColor(name) {
  let h=0; for(let i=0;i<(name||'').length;i++) h=(h+name.charCodeAt(i))%AVATAR_COLORS.length;
  return AVATAR_COLORS[h];
}

function avatarHTML(u) {
  const bg = avatarColor(u.full_name);
  const img = u.profile_photo ? `<img src="../app/uploads/avatars/${esc(u.profile_photo)}" alt="">` : '';
  const ring = u.is_verified ? '<div class="verified-ring"></div>' : '';
  return `<div class="gu-avatar" style="background:${bg};">${img}${ring}${esc(u.initials)}</div>`;
}

function fmtDate(dt) {
  if (!dt) return '—';
  const d = new Date(dt);
  return isNaN(d) ? dt : d.toLocaleDateString('es-CO',{day:'2-digit',month:'short',year:'numeric'});
}

function fmtDateFull(dt) {
  if (!dt) return '—';
  const d = new Date(dt);
  return isNaN(d) ? dt : d.toLocaleString('es-CO',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'});
}

function fmtPrice(p) {
  if (!p) return '—';
  return '$' + parseFloat(p).toLocaleString('es-CO');
}

function fmt(n) { return Number(n).toLocaleString('es-CO'); }
function esc(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

/* ═══════════════════════════════
   TOAST
═══════════════════════════════ */
function toast(type, title, msg) {
  const icons = {success:'bi-check-circle-fill', error:'bi-x-circle-fill', info:'bi-info-circle-fill', warning:'bi-exclamation-triangle-fill'};
  const t = document.createElement('div');
  t.className = `gu-toast ${type}`;
  t.innerHTML = `<i class="bi ${icons[type]??'bi-info-circle-fill'}"></i>
    <div><strong>${title}</strong><br><span style="font-weight:400;font-size:.8rem;">${msg}</span></div>
    <button class="gu-toast__x" onclick="this.parentElement.remove()"><i class="bi bi-x-lg"></i></button>`;
  document.getElementById('toastArea').appendChild(t);
  setTimeout(() => { t.classList.add('out'); setTimeout(() => t.remove(), 300); }, 4500);
}
</script>
</body>
</html>

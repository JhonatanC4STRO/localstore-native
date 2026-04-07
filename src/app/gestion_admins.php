<?php
/**
 * Gestión de Administradores — ComercioLocal Admin Panel
 * Data-driven via /api/admin_admins.php  (JSON API)
 * Access: role = 'admin' | 'super_admin'
 */
require_once("../config/conexion.php");
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ./auth/login.php"); exit();
}

$role = $_SESSION['user']['role'] ?? '';
if ($role !== 'admin' && $role !== 'super_admin') {
    header("Location: ./inde.php"); exit();
}

$user         = $_SESSION['user'];
$userInitial  = strtoupper(mb_substr($user['full_name'] ?? 'A', 0, 1));
$userName     = explode(' ', $user['full_name'] ?? 'Admin')[0];
$isSuperAdmin = ($role === 'super_admin');

/* API base path (same-origin) */
$API = '../api/admin_admins.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de Administradores — ComercioLocal</title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">

  <!-- Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <!-- Styles -->
  <link rel="stylesheet" href="../style/gestion_admins.css">
</head>
<body class="admin-body">

<!-- ══════════════════════════════════════
     TOP NAVIGATION BAR
══════════════════════════════════════ -->
<header class="adm-topbar">

  <!-- Logo -->
  <a href="./admin_dashboard.php" class="adm-topbar__logo">
    <div class="adm-topbar__logo-icon">🏪</div>
    <div class="adm-topbar__logo-text">
      <strong>ComercioLocal</strong>
      <span>Admin Panel</span>
    </div>
  </a>

  <div class="adm-topbar__divider"></div>

  <!-- Global search (synced with table search) -->
  <div class="adm-topbar__search">
    <i class="bi bi-search"></i>
    <input type="text" placeholder="Buscar administrador…" id="topbarQ" autocomplete="off">
  </div>

  <div class="adm-topbar__actions">
    <!-- Live indicator -->
    <div class="adm-live-wrap">
      <div class="adm-live-dot"></div>
      <span>En vivo</span>
    </div>

    <!-- Notifications -->
    <button class="adm-topbar__icon-btn" title="Notificaciones">
      <i class="bi bi-bell-fill"></i>
      <span class="adm-badge"></span>
    </button>

    <!-- Back to dashboard -->
    <button class="adm-topbar__icon-btn" title="Ir al dashboard"
            onclick="location.href='./admin_dashboard.php'">
      <i class="bi bi-speedometer2"></i>
    </button>

    <!-- Avatar chip -->
    <div class="adm-avatar-btn" title="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
      <div class="adm-avatar-circle"><?= htmlspecialchars($userInitial) ?></div>
      <div class="adm-avatar-info">
        <strong><?= htmlspecialchars($userName) ?></strong>
        <span><?= $isSuperAdmin ? 'Super Admin' : 'Admin' ?></span>
      </div>
    </div>
  </div>

</header>

<!-- ══════════════════════════════════════
     SHELL
══════════════════════════════════════ -->
<div class="adm-shell">

  <!-- ── SIDEBAR ── -->
  <aside class="adm-sidebar">
    <div class="adm-sb-label">Principal</div>
    <a class="adm-sb-link" href="./admin_dashboard.php">
      <i class="bi bi-speedometer2"></i>Dashboard
    </a>
    <a class="adm-sb-link" href="./verificaciones.php">
      <i class="bi bi-patch-check"></i>Solicitudes de verificación
      <span class="adm-sb-badge yellow" id="sbBadgeVerif">—</span>
    </a>
    <a class="adm-sb-link" href="./gestion_usuarios.php">
      <i class="bi bi-people"></i>Gestión de usuarios
    </a>
    <a class="adm-sb-link" href="./gestion_productos.php">
      <i class="bi bi-box-seam"></i>Gestión de productos
    </a>
    <a class="adm-sb-link" href="#">
      <i class="bi bi-flag"></i>Productos reportados
      <span class="adm-sb-badge red">!</span>
    </a>

    <hr class="adm-sb-divider">
    <div class="adm-sb-label">Administración</div>

    <a class="adm-sb-link active" href="./gestion_admins.php">
      <i class="bi bi-shield-check"></i>Gestión de admins
    </a>
    <a class="adm-sb-link" href="#">
      <i class="bi bi-journal-text"></i>Registros de actividad
    </a>
    <a class="adm-sb-link" href="#">
      <i class="bi bi-gear"></i>Configuración
    </a>

    <hr class="adm-sb-divider">
    <a class="adm-sb-link" href="../controller/logout.php" style="color:rgba(239,68,68,.7);">
      <i class="bi bi-box-arrow-right"></i>Cerrar sesión
    </a>

    <div class="adm-sb-footer">
      <strong>🛡️ Gestión segura</strong>
      <p>Todas las acciones sobre administradores quedan registradas automáticamente.</p>
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
          <span>Gestión de administradores</span>
        </div>
        <h1>Gestión de administradores</h1>
        <p>Administra cuentas, roles y estados de los administradores de la plataforma.</p>
      </div>

      <div class="adm-page-header__actions">
        <button class="ga-btn ga-btn-secondary" onclick="loadStats(); loadTable();" title="Actualizar datos">
          <i class="bi bi-arrow-clockwise"></i> Actualizar
        </button>
        <?php if ($isSuperAdmin): ?>
        <button class="ga-btn ga-btn-primary" id="createAdminBtn" onclick="openCreateModal()">
          <i class="bi bi-plus-circle-fill"></i> Crear administrador
        </button>
        <?php endif; ?>
      </div>
    </div>

    <!-- ── Stats row ── -->
    <div class="ga-stats" id="statsRow">
      <div class="ga-stat ga-stat--total">
        <div class="ga-stat__top">
          <div class="ga-stat__icon"><i class="bi bi-shield-fill-check"></i></div>
        </div>
        <div class="ga-stat__num" id="s-total">—</div>
        <div class="ga-stat__lbl">Total administradores</div>
      </div>
      <div class="ga-stat ga-stat--super">
        <div class="ga-stat__top">
          <div class="ga-stat__icon"><i class="bi bi-star-fill"></i></div>
        </div>
        <div class="ga-stat__num" id="s-super">—</div>
        <div class="ga-stat__lbl">Super Admins</div>
      </div>
      <div class="ga-stat ga-stat--admin">
        <div class="ga-stat__top">
          <div class="ga-stat__icon"><i class="bi bi-shield-check"></i></div>
        </div>
        <div class="ga-stat__num" id="s-admins">—</div>
        <div class="ga-stat__lbl">Administradores</div>
      </div>
      <div class="ga-stat ga-stat--active">
        <div class="ga-stat__top">
          <div class="ga-stat__icon"><i class="bi bi-check-circle-fill"></i></div>
        </div>
        <div class="ga-stat__num" id="s-active">—</div>
        <div class="ga-stat__lbl">Activos</div>
      </div>
      <div class="ga-stat ga-stat--inactive">
        <div class="ga-stat__top">
          <div class="ga-stat__icon"><i class="bi bi-dash-circle-fill"></i></div>
        </div>
        <div class="ga-stat__num" id="s-inactive">—</div>
        <div class="ga-stat__lbl">Inactivos</div>
      </div>
    </div>

    <!-- ── Toolbar: search + filters ── -->
    <div class="ga-toolbar">
      <div class="ga-search">
        <i class="bi bi-search si"></i>
        <input type="text" id="searchQ" placeholder="Buscar por nombre o correo…" autocomplete="off">
        <button class="sc" onclick="clearSearch()" title="Limpiar búsqueda">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <div class="ga-toolbar__sep"></div>

      <select class="ga-filter" id="fRole" onchange="applyFilters()">
        <option value="">Todos los roles</option>
        <option value="admin">Admin</option>
        <option value="super_admin">Super Admin</option>
      </select>

      <select class="ga-filter" id="fStatus" onchange="applyFilters()">
        <option value="">Todos los estados</option>
        <option value="active">Activo</option>
        <option value="inactive">Inactivo</option>
        <option value="blocked">Bloqueado</option>
      </select>

      <input type="date" class="ga-date" id="fDateFrom" onchange="applyFilters()" title="Registrado desde">
      <input type="date" class="ga-date" id="fDateTo"   onchange="applyFilters()" title="Registrado hasta">

      <div class="ga-toolbar__sep"></div>

      <span class="ga-results" id="resultsInfo">Cargando…</span>
    </div>

    <!-- ── Table card ── -->
    <div class="ga-card">
      <div class="ga-table-wrap">
        <div class="ga-loading active" id="loadingOverlay">
          <div class="ga-spinner"></div>
        </div>

        <table class="ga-table" id="adminsTable">
          <thead>
            <tr>
              <th style="width:50px;padding-left:20px;">#</th>
              <th class="sortable" data-col="full_name" onclick="sortBy('full_name')">
                Administrador <span class="sort-icon"></span>
              </th>
              <th class="sortable" data-col="role" onclick="sortBy('role')">
                Rol <span class="sort-icon"></span>
              </th>
              <th class="sortable" data-col="status" onclick="sortBy('status')">
                Estado <span class="sort-icon"></span>
              </th>
              <th class="sortable" data-col="created_at" onclick="sortBy('created_at')">
                Creado <span class="sort-icon"></span>
              </th>
              <th class="sortable" data-col="last_login" onclick="sortBy('last_login')">
                Último acceso <span class="sort-icon"></span>
              </th>
              <th style="text-align:right;padding-right:20px;">Acciones</th>
            </tr>
          </thead>
          <tbody id="tableBody">
            <!-- rows injected by JS -->
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="ga-pagination" id="paginationBar">
        <div class="ga-per-page">
          <span>Mostrar</span>
          <select id="perPage" onchange="changePerPage(this.value)">
            <option value="10">10</option>
            <option value="20" selected>20</option>
            <option value="50">50</option>
          </select>
          <span>por página</span>
        </div>
        <div class="ga-page-info" id="pageInfo"></div>
        <div class="ga-page-btns" id="pageBtns"></div>
      </div>
    </div>

  </main>
</div><!-- /shell -->

<!-- ══════════════════════════════════
     DETAIL SLIDE-IN PANEL
══════════════════════════════════ -->
<aside class="ga-detail" id="detailPanel">
  <div class="ga-detail__topbar">
    <h3>
      <i class="bi bi-shield-check" style="color:var(--g600);"></i>
      Perfil del administrador
    </h3>
    <button class="ga-detail__close" onclick="closeDetail()">
      <i class="bi bi-x-lg"></i>
    </button>
  </div>

  <div class="ga-detail__body" id="detailBody">
    <!-- Injected by loadDetail() -->
  </div>

  <div class="ga-detail__footer" id="detailFooter">
    <!-- Injected by loadDetail() -->
  </div>
</aside>

<!-- ══════════════════════════════════
     CREATE / EDIT MODAL
══════════════════════════════════ -->
<div class="ga-modal-backdrop" id="adminModal">
  <div class="ga-modal" role="dialog" aria-modal="true">

    <div class="ga-modal-header">
      <div class="ga-modal-icon green" id="adminModalIcon">
        <i class="bi bi-person-plus-fill"></i>
      </div>
      <div>
        <div class="ga-modal-title" id="adminModalTitle">Crear administrador</div>
        <div class="ga-modal-subtitle" id="adminModalSubtitle">Completa los datos del nuevo administrador</div>
      </div>
      <button class="ga-modal-close" onclick="closeAdminModal()">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <div class="ga-modal-body">
      <form id="adminForm" onsubmit="return false;" novalidate>
        <input type="hidden" id="formAdminId" value="">

        <div class="ga-form-grid" style="margin-bottom:16px;">
          <!-- Name -->
          <div class="ga-form-field full">
            <label class="ga-form-label" for="formName">
              Nombre completo <span>*</span>
            </label>
            <input class="ga-form-input" type="text" id="formName"
                   placeholder="Ej. María García López" autocomplete="off">
            <span class="ga-form-error" id="errName"></span>
          </div>

          <!-- Email -->
          <div class="ga-form-field full">
            <label class="ga-form-label" for="formEmail">
              Correo electrónico <span>*</span>
            </label>
            <input class="ga-form-input" type="email" id="formEmail"
                   placeholder="correo@ejemplo.com" autocomplete="off">
            <span class="ga-form-error" id="errEmail"></span>
          </div>

          <!-- Password -->
          <div class="ga-form-field full" id="passwordField">
            <label class="ga-form-label" for="formPassword">
              Contraseña <span id="passRequired">*</span>
            </label>
            <input class="ga-form-input" type="password" id="formPassword"
                   placeholder="Mínimo 8 caracteres" autocomplete="new-password">
            <span class="ga-form-hint" id="passHint">Al menos 8 caracteres</span>
            <span class="ga-form-error" id="errPassword"></span>
          </div>
        </div>

        <!-- Role selector -->
        <div class="ga-form-field" style="margin-bottom:4px;">
          <label class="ga-form-label">Rol del administrador <span>*</span></label>
        </div>
        <div class="ga-role-cards">
          <label class="ga-role-card selected" id="roleCardAdmin" onclick="selectRoleCard('admin', this)">
            <input type="radio" name="adminRole" value="admin" checked>
            <div class="ga-role-card-icon green"><i class="bi bi-shield-check"></i></div>
            <div class="ga-role-card-text">
              <strong>Admin</strong>
              <span>Acceso al panel con permisos estándar de administración</span>
            </div>
          </label>
          <label class="ga-role-card super" id="roleCardSuper" onclick="selectRoleCard('super_admin', this)">
            <input type="radio" name="adminRole" value="super_admin">
            <div class="ga-role-card-icon yellow"><i class="bi bi-star-fill"></i></div>
            <div class="ga-role-card-text">
              <strong>Super Admin</strong>
              <span>Control total, incluye gestión de otros administradores</span>
            </div>
          </label>
        </div>
        <span class="ga-form-error" id="errRole" style="margin-top:6px;display:none;"></span>
      </form>
    </div>

    <div class="ga-modal-footer">
      <button class="ga-btn ga-btn-ghost" onclick="closeAdminModal()">
        <i class="bi bi-x"></i> Cancelar
      </button>
      <button class="ga-btn ga-btn-primary" id="adminModalSubmit" onclick="submitAdminForm()">
        <i class="bi bi-check-lg"></i> <span id="submitBtnLabel">Crear administrador</span>
      </button>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════
     CONFIRM MODAL  (for status changes)
══════════════════════════════════ -->
<div class="ga-modal-backdrop" id="confirmModal">
  <div class="ga-modal" style="max-width:440px;" role="dialog" aria-modal="true">

    <div class="ga-modal-header">
      <div class="ga-modal-icon red" id="confirmIcon">
        <i class="bi bi-exclamation-triangle-fill"></i>
      </div>
      <div>
        <div class="ga-modal-title" id="confirmTitle">¿Confirmar acción?</div>
        <div class="ga-modal-subtitle" id="confirmSubtitle">Esta acción modificará el estado del administrador.</div>
      </div>
      <button class="ga-modal-close" onclick="closeConfirmModal()">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <div class="ga-modal-body">
      <div class="ga-confirm-body">
        <p id="confirmMsg">¿Estás seguro de que deseas realizar esta acción?</p>
        <div class="ga-confirm-target" id="confirmTarget"></div>
      </div>
    </div>

    <div class="ga-modal-footer">
      <button class="ga-btn ga-btn-ghost" onclick="closeConfirmModal()">
        <i class="bi bi-x"></i> Cancelar
      </button>
      <button class="ga-btn" id="confirmOkBtn" onclick="execConfirmedAction()">
        <i class="bi bi-check-lg"></i> <span id="confirmOkLabel">Confirmar</span>
      </button>
    </div>
  </div>
</div>

<!-- Toasts -->
<div class="ga-toasts" id="toastArea"></div>

<!-- ══════════════════════════════════
     JAVASCRIPT
══════════════════════════════════ -->
<script>
/* ═══════════════════════════
   CONFIG & STATE
═══════════════════════════ */
const API            = '../api/admin_admins.php';
const IS_SUPER_ADMIN = <?= $isSuperAdmin ? 'true' : 'false' ?>;
const SESSION_ID     = <?= (int)($user['id'] ?? 0) ?>;

let state = {
  page: 1, limit: 20,
  sort: 'created_at', dir: 'DESC',
  search: '', role: '', status: '',
  date_from: '', date_to: '',
};

let pendingAction   = null; // { action, id, name }
let searchTimer     = null;
let currentDetailId = null;
let selectedRole    = 'admin';
let editMode        = false;

/* ═══════════════════════════
   BOOT
═══════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
  loadStats();
  loadTable();

  /* Debounced search in toolbar */
  document.getElementById('searchQ').addEventListener('input', function () {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
      state.search = this.value.trim();
      state.page   = 1;
      loadTable();
    }, 380);
  });

  /* Sync topbar search */
  document.getElementById('topbarQ').addEventListener('input', function () {
    const q = this.value.trim();
    document.getElementById('searchQ').value = q;
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => { state.search = q; state.page = 1; loadTable(); }, 380);
  });

  /* Close modals on backdrop click */
  ['adminModal','confirmModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', e => {
      if (e.target.id === id) { id === 'adminModal' ? closeAdminModal() : closeConfirmModal(); }
    });
  });

  /* ESC closes modals */
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      closeAdminModal();
      closeConfirmModal();
      closeDetail();
    }
  });
});

/* ═══════════════════════════
   LOAD STATS
═══════════════════════════ */
async function loadStats() {
  try {
    const res  = await fetch(`${API}?action=stats`);
    const json = await res.json();
    if (!json.ok) return;
    const d = json.data;
    setText('s-total',   fmt(d.total));
    setText('s-super',   fmt(d.super_admins));
    setText('s-admins',  fmt(d.admins));
    setText('s-active',  fmt(d.active));
    setText('s-inactive',fmt(d.inactive));
  } catch(e) { console.warn('Stats error', e); }
}

/* ═══════════════════════════
   LOAD TABLE
═══════════════════════════ */
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
    date_from: state.date_from,
    date_to:   state.date_to,
  });

  try {
    const res  = await fetch(`${API}?${params}`);
    const json = await res.json();
    if (!json.ok) {
      showLoading(false);
      toast('error', 'Error al cargar', json.error);
      return;
    }
    renderTable(json.data, json.pagination);
    renderPagination(json.pagination);
    updateSortHeaders();
    const info = document.getElementById('resultsInfo');
    info.innerHTML = `<strong>${fmt(json.pagination.total)}</strong> resultado${json.pagination.total !== 1 ? 's' : ''}`;
  } catch(e) {
    toast('error', 'Error de red', 'No se pudo conectar con el servidor');
  }

  showLoading(false);
}

/* ═══════════════════════════
   RENDER TABLE
═══════════════════════════ */
function renderTable(rows) {
  const tbody = document.getElementById('tableBody');

  if (!rows || rows.length === 0) {
    const canCreate = IS_SUPER_ADMIN
      ? `<button class="ga-btn ga-btn-primary" onclick="openCreateModal()" style="margin-top:8px;">
           <i class="bi bi-plus-circle-fill"></i> Crear primer administrador
         </button>` : '';

    tbody.innerHTML = `
      <tr><td colspan="7">
        <div class="ga-empty">
          <div class="ga-empty-icon">🛡️</div>
          <h3>No se encontraron administradores</h3>
          <p>${state.search || state.role || state.status
              ? 'No hay resultados con los filtros actuales. Intenta ajustar los criterios de búsqueda.'
              : 'Aún no hay administradores registrados en la plataforma.'
            }</p>
          ${canCreate}
        </div>
      </td></tr>`;
    return;
  }

  tbody.innerHTML = rows.map((a, idx) => {
    const isMe = (a.id === SESSION_ID);
    const isSuper = (a.role === 'super_admin');

    /* Avatar */
    const avatarClass = isSuper ? 'ga-avatar super' : 'ga-avatar';
    const onlineDot   = a.status === 'active'
      ? '<div class="ga-avatar-online"></div>'
      : '<div class="ga-avatar-offline"></div>';
    const avatar = a.profile_photo
      ? `<div class="${avatarClass}"><img class="ga-avatar-img" src="${esc(a.profile_photo)}" alt="">${onlineDot}</div>`
      : `<div class="${avatarClass}">${esc(a.initials)}${onlineDot}</div>`;

    /* Role badge */
    const roleBadge = isSuper
      ? `<span class="ga-role-badge ga-role-super"><i class="bi bi-star-fill"></i>Super Admin</span>`
      : `<span class="ga-role-badge ga-role-admin"><i class="bi bi-shield-check"></i>Admin</span>`;

    /* Status badge */
    const statusBadge = {
      'active':   `<span class="ga-badge ga-badge-active">Activo</span>`,
      'inactive': `<span class="ga-badge ga-badge-inactive">Inactivo</span>`,
      'blocked':  `<span class="ga-badge ga-badge-blocked">Bloqueado</span>`,
    }[a.status] ?? `<span class="ga-badge">${esc(a.status)}</span>`;

    /* Dates */
    const createdAt  = fmtDateCell(a.created_at);
    const lastLogin  = a.last_login
      ? fmtDateCell(a.last_login)
      : `<span class="ga-date-never">Nunca</span>`;

    /* Actions — availability depends on role + whether it's the session user */
    const canEdit = IS_SUPER_ADMIN;
    const canToggle = IS_SUPER_ADMIN && !isMe;

    const toggleBtn = a.status === 'active'
      ? `<button class="ga-act-btn deactivate" title="Desactivar" onclick="confirmDeactivate(${a.id},'${escAttr(a.full_name)}')" ${!canToggle?'disabled':''}>
           <i class="bi bi-dash-circle"></i></button>`
      : `<button class="ga-act-btn activate" title="Activar" onclick="confirmActivate(${a.id},'${escAttr(a.full_name)}')" ${!canToggle?'disabled':''}>
           <i class="bi bi-check-circle"></i></button>`;

    return `
    <tr id="row-${a.id}">
      <td style="padding-left:20px;color:var(--slate400);font-size:.8rem;font-weight:600;">#${a.id}</td>
      <td>
        <div class="ga-admin-cell" onclick="openDetail(${a.id})">
          ${avatar}
          <div class="ga-admin-info">
            <strong>${esc(a.full_name)}${isMe ? ' <span style="color:var(--g600);font-size:.72rem;">(tú)</span>' : ''}</strong>
            <span>${esc(a.email)}</span>
          </div>
        </div>
      </td>
      <td>${roleBadge}</td>
      <td>${statusBadge}</td>
      <td>${createdAt}</td>
      <td>${lastLogin}</td>
      <td>
        <div class="ga-row-actions">
          <button class="ga-act-btn view" title="Ver detalles" onclick="openDetail(${a.id})">
            <i class="bi bi-eye"></i>
          </button>
          <button class="ga-act-btn edit" title="Editar" onclick="openEditModal(${a.id})" ${!canEdit?'disabled':''}>
            <i class="bi bi-pencil"></i>
          </button>
          ${toggleBtn}
        </div>
      </td>
    </tr>`;
  }).join('');
}

/* ═══════════════════════════
   PAGINATION
═══════════════════════════ */
function renderPagination(pg) {
  const { page, limit, total, total_pages } = pg;
  const from = total === 0 ? 0 : (page - 1) * limit + 1;
  const to   = Math.min(page * limit, total);

  document.getElementById('pageInfo').innerHTML =
    total > 0 ? `Mostrando <strong>${from}–${to}</strong> de <strong>${fmt(total)}</strong>` : '';

  const btns = document.getElementById('pageBtns');
  if (total_pages <= 1) { btns.innerHTML = ''; return; }

  let html = `<button class="ga-page-btn" onclick="goPage(${page-1})" ${page<=1?'disabled':''}><i class="bi bi-chevron-left"></i></button>`;

  const range = pageRange(page, total_pages);
  let prev = null;
  for (const p of range) {
    if (prev !== null && p - prev > 1) html += `<button class="ga-page-btn" disabled>…</button>`;
    html += `<button class="ga-page-btn ${p===page?'active':''}" onclick="goPage(${p})">${p}</button>`;
    prev = p;
  }

  html += `<button class="ga-page-btn" onclick="goPage(${page+1})" ${page>=total_pages?'disabled':''}><i class="bi bi-chevron-right"></i></button>`;
  btns.innerHTML = html;
}

function pageRange(current, total) {
  const delta = 2, range = [], left = current - delta, right = current + delta + 1;
  for (let i = 1; i <= total; i++) {
    if (i === 1 || i === total || (i >= left && i < right)) range.push(i);
  }
  return range;
}

function goPage(p) { state.page = p; loadTable(); }
function changePerPage(v) { state.limit = parseInt(v); state.page = 1; loadTable(); }

/* ═══════════════════════════
   SORT
═══════════════════════════ */
function sortBy(col) {
  if (state.sort === col) { state.dir = state.dir === 'ASC' ? 'DESC' : 'ASC'; }
  else { state.sort = col; state.dir = 'DESC'; }
  state.page = 1;
  loadTable();
}
function updateSortHeaders() {
  document.querySelectorAll('.ga-table th.sortable').forEach(th => {
    th.classList.remove('sort-asc','sort-desc');
    if (th.dataset.col === state.sort) th.classList.add(state.dir === 'ASC' ? 'sort-asc' : 'sort-desc');
  });
}

/* ═══════════════════════════
   FILTERS
═══════════════════════════ */
function applyFilters() {
  state.role      = document.getElementById('fRole').value;
  state.status    = document.getElementById('fStatus').value;
  state.date_from = document.getElementById('fDateFrom').value;
  state.date_to   = document.getElementById('fDateTo').value;
  state.page      = 1;
  loadTable();
}

function clearSearch() {
  document.getElementById('searchQ').value = '';
  document.getElementById('topbarQ').value = '';
  state.search = '';
  state.page   = 1;
  loadTable();
}

/* ═══════════════════════════
   DETAIL PANEL
═══════════════════════════ */
async function openDetail(id) {
  currentDetailId = id;
  document.getElementById('detailPanel').classList.add('open');
  document.getElementById('mainArea').classList.add('panel-open');
  document.getElementById('detailBody').innerHTML = `
    <div style="padding:40px 0;text-align:center;">
      <div class="ga-spinner" style="margin:0 auto;"></div>
      <p style="margin-top:16px;font-size:.85rem;color:var(--slate400);">Cargando perfil…</p>
    </div>`;
  document.getElementById('detailFooter').innerHTML = '';

  try {
    const res  = await fetch(`${API}?action=detail&id=${id}`);
    const json = await res.json();
    if (!json.ok) { document.getElementById('detailBody').innerHTML = `<p style="color:var(--red600);padding:20px;">${esc(json.error)}</p>`; return; }
    renderDetail(json.data);
  } catch(e) {
    document.getElementById('detailBody').innerHTML = `<p style="color:var(--red600);padding:20px;">Error al cargar el perfil.</p>`;
  }
}

function renderDetail(a) {
  const isSuper   = a.role === 'super_admin';
  const avatarCls = isSuper ? 'ga-detail-avatar super' : 'ga-detail-avatar';
  const isMe      = a.id === SESSION_ID;

  /* Profile header */
  const profileHTML = `
    <div class="ga-detail-profile">
      <div class="${avatarCls}">${esc(a.initials)}</div>
      <div class="ga-detail-name">${esc(a.full_name)}${isMe?' <span style="color:var(--g600);font-size:.8rem;">(tú)</span>':''}</div>
      <div class="ga-detail-email">${esc(a.email)}</div>
      <div class="ga-detail-badges">
        ${isSuper
          ? `<span class="ga-role-badge ga-role-super"><i class="bi bi-star-fill"></i>Super Admin</span>`
          : `<span class="ga-role-badge ga-role-admin"><i class="bi bi-shield-check"></i>Admin</span>`}
        ${{
            active:   `<span class="ga-badge ga-badge-active">Activo</span>`,
            inactive: `<span class="ga-badge ga-badge-inactive">Inactivo</span>`,
            blocked:  `<span class="ga-badge ga-badge-blocked">Bloqueado</span>`,
          }[a.status] ?? ''}
      </div>
    </div>`;

  /* Info rows */
  const infoHTML = `
    <div class="ga-detail-section">
      <div class="ga-detail-section-title">Información de la cuenta</div>
      <div class="ga-detail-row"><span class="lbl">ID</span><span class="val">#${a.id}</span></div>
      <div class="ga-detail-row"><span class="lbl">Nombre</span><span class="val">${esc(a.full_name)}</span></div>
      <div class="ga-detail-row"><span class="lbl">Correo</span><span class="val" style="word-break:break-all;">${esc(a.email)}</span></div>
      <div class="ga-detail-row"><span class="lbl">Rol</span><span class="val">${isSuper ? 'Super Admin' : 'Admin'}</span></div>
      <div class="ga-detail-row"><span class="lbl">Estado</span><span class="val">${a.status === 'active' ? '✅ Activo' : a.status === 'inactive' ? '⛔ Inactivo' : '⚠️ Bloqueado'}</span></div>
      ${a.phone ? `<div class="ga-detail-row"><span class="lbl">Teléfono</span><span class="val">${esc(a.phone)}</span></div>` : ''}
      ${a.city  ? `<div class="ga-detail-row"><span class="lbl">Ciudad</span><span class="val">${esc(a.city)}</span></div>` : ''}
    </div>`;

  /* Dates section */
  const datesHTML = `
    <div class="ga-detail-section">
      <div class="ga-detail-section-title">Actividad</div>
      <div class="ga-detail-row">
        <span class="lbl">Cuenta creada</span>
        <span class="val">${fmtFull(a.created_at)}</span>
      </div>
      <div class="ga-detail-row">
        <span class="lbl">Último acceso</span>
        <span class="val">${a.last_login ? fmtFull(a.last_login) : '<em style="color:var(--slate300);">Nunca</em>'}</span>
      </div>
      ${a.updated_at ? `
      <div class="ga-detail-row">
        <span class="lbl">Actualizado</span>
        <span class="val">${fmtFull(a.updated_at)}</span>
      </div>` : ''}
    </div>`;

  /* Activity log */
  let actHTML = '';
  if (a.activity && a.activity.length > 0) {
    actHTML = `
      <div class="ga-detail-section">
        <div class="ga-detail-section-title">Actividad reciente</div>
        ${a.activity.map(act => `
          <div class="ga-activity-item">
            <div class="ga-activity-dot"></div>
            <div>
              <div class="act-text">${esc(act.action)}</div>
              <div class="act-time">${fmtFull(act.created_at)}</div>
            </div>
          </div>`).join('')}
      </div>`;
  }

  document.getElementById('detailBody').innerHTML = profileHTML + infoHTML + datesHTML + actHTML;

  /* Footer actions */
  const canEdit   = IS_SUPER_ADMIN;
  const canToggle = IS_SUPER_ADMIN && !isMe;

  const toggleBtn = a.status === 'active'
    ? `<button class="ga-btn ga-btn-secondary" onclick="confirmDeactivate(${a.id},'${escAttr(a.full_name)}')" ${!canToggle?'disabled':''}>
         <i class="bi bi-dash-circle"></i> Desactivar
       </button>`
    : `<button class="ga-btn ga-btn-primary" onclick="confirmActivate(${a.id},'${escAttr(a.full_name)}')" ${!canToggle?'disabled':''}>
         <i class="bi bi-check-circle"></i> Activar
       </button>`;

  document.getElementById('detailFooter').innerHTML = `
    <button class="ga-btn ga-btn-secondary" onclick="openEditModal(${a.id})" ${!canEdit?'disabled':''} style="flex:0;white-space:nowrap;">
      <i class="bi bi-pencil"></i> Editar
    </button>
    ${toggleBtn}`;
}

function closeDetail() {
  document.getElementById('detailPanel').classList.remove('open');
  document.getElementById('mainArea').classList.remove('panel-open');
  currentDetailId = null;
}

/* ═══════════════════════════
   CREATE MODAL
═══════════════════════════ */
function openCreateModal() {
  if (!IS_SUPER_ADMIN) { toast('warning','Sin permisos','Solo un Super Admin puede crear administradores'); return; }
  editMode = false;
  document.getElementById('formAdminId').value  = '';
  document.getElementById('formName').value     = '';
  document.getElementById('formEmail').value    = '';
  document.getElementById('formPassword').value = '';
  document.getElementById('passRequired').textContent = '*';
  document.getElementById('passHint').textContent     = 'Al menos 8 caracteres';

  selectRoleCard('admin', document.getElementById('roleCardAdmin'));

  document.getElementById('adminModalIcon').innerHTML    = '<i class="bi bi-person-plus-fill"></i>';
  document.getElementById('adminModalIcon').className    = 'ga-modal-icon green';
  document.getElementById('adminModalTitle').textContent    = 'Crear administrador';
  document.getElementById('adminModalSubtitle').textContent = 'Completa los datos del nuevo administrador';
  document.getElementById('submitBtnLabel').textContent     = 'Crear administrador';
  clearFormErrors();
  document.getElementById('adminModal').classList.add('open');
  setTimeout(() => document.getElementById('formName').focus(), 100);
}

/* ═══════════════════════════
   EDIT MODAL
═══════════════════════════ */
async function openEditModal(id) {
  if (!IS_SUPER_ADMIN) { toast('warning','Sin permisos','Solo un Super Admin puede editar administradores'); return; }
  editMode = true;

  /* Load admin data */
  document.getElementById('adminModal').classList.add('open');
  document.getElementById('adminModalTitle').textContent    = 'Cargando…';
  document.getElementById('adminModalSubtitle').textContent = '';

  try {
    const res  = await fetch(`${API}?action=detail&id=${id}`);
    const json = await res.json();
    if (!json.ok) { closeAdminModal(); toast('error','Error',json.error); return; }
    const a = json.data;

    document.getElementById('formAdminId').value  = a.id;
    document.getElementById('formName').value     = a.full_name;
    document.getElementById('formEmail').value    = a.email;
    document.getElementById('formPassword').value = '';
    document.getElementById('passRequired').textContent = '';
    document.getElementById('passHint').textContent = 'Deja en blanco para no cambiarla';

    selectRoleCard(a.role, document.getElementById(a.role === 'super_admin' ? 'roleCardSuper' : 'roleCardAdmin'));

    document.getElementById('adminModalIcon').innerHTML    = '<i class="bi bi-pencil-square"></i>';
    document.getElementById('adminModalIcon').className    = 'ga-modal-icon yellow';
    document.getElementById('adminModalTitle').textContent    = 'Editar administrador';
    document.getElementById('adminModalSubtitle').textContent = `Modificando cuenta de ${a.full_name}`;
    document.getElementById('submitBtnLabel').textContent     = 'Guardar cambios';
    clearFormErrors();
  } catch(e) {
    closeAdminModal(); toast('error','Error de red','No se pudo cargar el administrador');
  }
}

function closeAdminModal() {
  document.getElementById('adminModal').classList.remove('open');
}

function selectRoleCard(role, el) {
  selectedRole = role;
  document.querySelectorAll('.ga-role-card').forEach(c => c.classList.remove('selected'));
  if (el) el.classList.add('selected');
}

/* ═══════════════════════════
   SUBMIT FORM
═══════════════════════════ */
async function submitAdminForm() {
  const id       = document.getElementById('formAdminId').value;
  const name     = document.getElementById('formName').value.trim();
  const email    = document.getElementById('formEmail').value.trim();
  const password = document.getElementById('formPassword').value;
  const role     = selectedRole;
  const isEdit   = editMode && id !== '';

  /* Client-side validation */
  clearFormErrors();
  let valid = true;
  if (name.length < 2)                       { showFieldError('errName','Mínimo 2 caracteres'); valid = false; }
  if (!isValidEmail(email))                   { showFieldError('errEmail','Correo electrónico inválido'); valid = false; }
  if (!isEdit && password.length < 8)         { showFieldError('errPassword','Mínimo 8 caracteres'); valid = false; }
  if (isEdit && password !== '' && password.length < 8) { showFieldError('errPassword','Mínimo 8 caracteres'); valid = false; }
  if (!valid) return;

  const btn = document.getElementById('adminModalSubmit');
  btn.disabled = true;
  btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Procesando…';

  const action   = isEdit ? 'update' : 'create';
  const payload  = { action, name, email, role };
  if (password)  payload.password = password;
  if (isEdit)    payload.id = parseInt(id);

  try {
    const res  = await fetch(API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const json = await res.json();

    if (json.ok) {
      toast('success', isEdit ? 'Administrador actualizado' : 'Administrador creado', json.message);
      closeAdminModal();
      loadStats();
      loadTable();
      if (currentDetailId && isEdit && parseInt(id) === currentDetailId) openDetail(currentDetailId);
    } else {
      toast('error', 'No se pudo guardar', json.error);
    }
  } catch(e) {
    toast('error', 'Error de red', 'No se pudo conectar con el servidor');
  }

  btn.disabled = false;
  btn.innerHTML = `<i class="bi bi-check-lg"></i> <span id="submitBtnLabel">${isEdit ? 'Guardar cambios' : 'Crear administrador'}</span>`;
}

/* ═══════════════════════════
   CONFIRM: STATUS CHANGES
═══════════════════════════ */
function confirmActivate(id, name) {
  pendingAction = { action: 'activate', id, name };
  document.getElementById('confirmIcon').className       = 'ga-modal-icon green';
  document.getElementById('confirmIcon').innerHTML       = '<i class="bi bi-check-circle-fill"></i>';
  document.getElementById('confirmTitle').textContent    = 'Activar administrador';
  document.getElementById('confirmSubtitle').textContent = 'Esta acción reactivará el acceso al panel.';
  document.getElementById('confirmMsg').textContent      = '¿Confirmas que deseas activar la cuenta de:';
  document.getElementById('confirmTarget').innerHTML     = `<i class="bi bi-person-fill" style="color:var(--g600);"></i> ${esc(name)}`;
  document.getElementById('confirmOkBtn').className      = 'ga-btn ga-btn-primary';
  document.getElementById('confirmOkLabel').textContent  = 'Activar';
  document.getElementById('confirmModal').classList.add('open');
}

function confirmDeactivate(id, name) {
  pendingAction = { action: 'deactivate', id, name };
  document.getElementById('confirmIcon').className       = 'ga-modal-icon red';
  document.getElementById('confirmIcon').innerHTML       = '<i class="bi bi-dash-circle-fill"></i>';
  document.getElementById('confirmTitle').textContent    = 'Desactivar administrador';
  document.getElementById('confirmSubtitle').textContent = 'El administrador perderá acceso al panel.';
  document.getElementById('confirmMsg').textContent      = '¿Confirmas que deseas desactivar la cuenta de:';
  document.getElementById('confirmTarget').innerHTML     = `<i class="bi bi-person-fill" style="color:var(--red600);"></i> ${esc(name)}`;
  document.getElementById('confirmOkBtn').className      = 'ga-btn ga-btn-secondary';
  document.getElementById('confirmOkBtn').style.cssText  = 'background:var(--red600);color:#fff;border-color:transparent;';
  document.getElementById('confirmOkLabel').textContent  = 'Desactivar';
  document.getElementById('confirmModal').classList.add('open');
}

function closeConfirmModal() {
  document.getElementById('confirmModal').classList.remove('open');
  pendingAction = null;
}

async function execConfirmedAction() {
  if (!pendingAction) return;

  const { action, id, name } = pendingAction;
  const btn = document.getElementById('confirmOkBtn');
  btn.disabled = true;

  try {
    const res  = await fetch(API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action, id }),
    });
    const json = await res.json();

    if (json.ok) {
      toast('success', 'Estado actualizado', json.message);
      closeConfirmModal();
      loadStats();
      loadTable();
      if (currentDetailId === id) openDetail(id);
    } else {
      toast('error', 'Error', json.error);
    }
  } catch(e) {
    toast('error', 'Error de red', 'No se pudo conectar con el servidor');
  }

  btn.disabled = false;
}

/* ═══════════════════════════
   TOAST SYSTEM
═══════════════════════════ */
function toast(type, title, msg) {
  const icons = { success: 'bi-check-circle-fill', error: 'bi-x-circle-fill', warning: 'bi-exclamation-triangle-fill' };
  const el = document.createElement('div');
  el.className = `ga-toast ${type}`;
  el.innerHTML = `
    <i class="bi ${icons[type] || 'bi-info-circle-fill'} ga-toast__icon"></i>
    <div class="ga-toast__body">
      <div class="ga-toast__title">${esc(title)}</div>
      ${msg ? `<div class="ga-toast__msg">${esc(msg)}</div>` : ''}
    </div>`;
  document.getElementById('toastArea').appendChild(el);
  setTimeout(() => { el.classList.add('exit'); setTimeout(() => el.remove(), 300); }, 4000);
}

/* ═══════════════════════════
   HELPERS
═══════════════════════════ */
function showLoading(on) {
  document.getElementById('loadingOverlay').classList.toggle('active', on);
}

function setText(id, val) {
  const el = document.getElementById(id);
  if (el) el.textContent = val;
}

function fmt(n) {
  return n == null ? '—' : Number(n).toLocaleString('es-CO');
}

function fmtDateCell(dt) {
  if (!dt) return '<span class="ga-date-never">—</span>';
  const d = new Date(dt);
  if (isNaN(d)) return '<span class="ga-date-never">—</span>';
  const date = d.toLocaleDateString('es-CO', { day:'2-digit', month:'short', year:'numeric' });
  const time = d.toLocaleTimeString('es-CO', { hour:'2-digit', minute:'2-digit' });
  return `<div class="ga-date-cell"><span class="d">${date}</span><span class="t">${time}</span></div>`;
}

function fmtFull(dt) {
  if (!dt) return '—';
  const d = new Date(dt);
  if (isNaN(d)) return '—';
  return d.toLocaleString('es-CO', { day:'2-digit', month:'long', year:'numeric', hour:'2-digit', minute:'2-digit' });
}

function esc(str) {
  if (str == null) return '';
  return String(str)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

function escAttr(str) {
  if (str == null) return '';
  return String(str).replace(/'/g, "\\'").replace(/"/g, '&quot;');
}

function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function showFieldError(id, msg) {
  const el = document.getElementById(id);
  if (!el) return;
  el.textContent = msg;
  el.classList.add('show');
  /* Highlight the associated input */
  const inputId = { errName:'formName', errEmail:'formEmail', errPassword:'formPassword' }[id];
  if (inputId) document.getElementById(inputId)?.classList.add('error');
}

function clearFormErrors() {
  ['errName','errEmail','errPassword','errRole'].forEach(id => {
    const el = document.getElementById(id);
    if (el) { el.textContent=''; el.classList.remove('show'); }
  });
  ['formName','formEmail','formPassword'].forEach(id => {
    document.getElementById(id)?.classList.remove('error');
  });
}
</script>

</body>
</html>

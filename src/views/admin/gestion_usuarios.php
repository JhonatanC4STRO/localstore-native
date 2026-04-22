<?php
/**
 * Gestión de Usuarios — ComercioLocal Admin
 * Rediseño con topbar único (consistente con dashboard).
 */
require_once('../../config/conexion.php');
session_start();

if (!isset($_SESSION['user'])) { header("Location: ../auth/login.php"); exit(); }
$role = $_SESSION['user']['role'] ?? '';
if ($role !== 'admin' && $role !== 'super_admin') { header("Location: ../home.php"); exit(); }

$user         = $_SESSION['user'];
$userInitial  = strtoupper(mb_substr($user['full_name'] ?? 'A', 0, 1));
$userName     = $user['full_name'] ?? 'Administrador';
$isSuperAdmin = ($role === 'super_admin');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Usuarios — ComercioLocal Admin</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Syne:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <style>
    :root {
      --primary:#0B2E17; --primary-2:#123D20; --accent:#F5A81C; --accent-2:#FFC154;
      --red:#E53935; --red-soft:#FEECEB; --green-soft:#E6F3EB; --yellow-soft:#FFF3D6; --blue-soft:#EEF5FF;
      --bg:#F8F9FA; --white:#FFF; --ink:#0F172A; --ink2:#334155; --ink3:#64748B; --ink4:#94A3B8;
      --bord:#E5E7EB; --bord-soft:#EEF0F3; --shadow:0 2px 12px rgba(0,0,0,.06); --shadow-lg:0 8px 28px rgba(0,0,0,.08);
    }
    *{box-sizing:border-box;} html,body{margin:0;padding:0;}
    body{background:var(--bg);color:var(--ink);font-family:'DM Sans',system-ui,sans-serif;font-size:14px;line-height:1.45;}
    h1,h2,h3,h4{font-family:'Syne','DM Sans',sans-serif;color:var(--primary);letter-spacing:-.01em;margin:0;}
    a{color:inherit;text-decoration:none;}
    button{font-family:inherit;}

    /* ══ TOPBAR ══ */
    .topbar{background:var(--white);border-bottom:1px solid var(--bord);padding:14px 32px;display:flex;align-items:center;gap:32px;position:sticky;top:0;z-index:50;}
    .tb-logo{font-family:'Syne',sans-serif;font-weight:800;font-size:1.15rem;color:var(--primary);display:flex;align-items:center;gap:8px;}
    .tb-logo-mark{width:32px;height:32px;border-radius:9px;background:var(--primary);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:.85rem;}
    .tb-nav{display:flex;gap:4px;margin:0 auto;}
    .tb-link{padding:8px 14px;border-radius:10px;font-weight:500;font-size:.88rem;color:var(--ink2);transition:.15s;display:inline-flex;align-items:center;gap:6px;}
    .tb-link:hover{background:var(--bg);color:var(--primary);}
    .tb-link.active{background:var(--primary);color:var(--white);}
    .tb-right{display:flex;align-items:center;gap:14px;}
    .tb-bell{position:relative;width:40px;height:40px;border-radius:10px;border:1px solid var(--bord);background:var(--white);color:var(--ink2);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:.15s;}
    .tb-bell:hover{border-color:var(--primary);color:var(--primary);}
    .tb-bell__badge{position:absolute;top:-4px;right:-4px;background:var(--accent);color:var(--primary);font-size:.65rem;font-weight:800;min-width:18px;height:18px;padding:0 5px;border-radius:9px;display:flex;align-items:center;justify-content:center;border:2px solid var(--white);}
    .tb-user-wrap{position:relative;}
    .tb-user{display:flex;align-items:center;gap:10px;padding:4px 10px 4px 4px;border-radius:10px;border:1px solid var(--bord);background:var(--white);cursor:pointer;font-family:inherit;transition:.15s;}
    .tb-user:hover{border-color:var(--primary);}
    .tb-user__av{width:34px;height:34px;border-radius:8px;background:var(--primary);color:var(--accent);font-weight:700;display:flex;align-items:center;justify-content:center;font-size:.88rem;}
    .tb-user__info{text-align:left;}
    .tb-user__info strong{font-size:.85rem;color:var(--ink);display:block;line-height:1.1;}
    .tb-user__info span{font-size:.7rem;color:var(--ink4);}
    .tb-user__caret{font-size:.7rem;color:var(--ink4);transition:.2s;}
    .tb-user-wrap.open .tb-user__caret{transform:rotate(180deg);color:var(--primary);}
    .tb-menu{position:absolute;right:0;top:calc(100% + 8px);background:var(--white);border:1px solid var(--bord);border-radius:12px;box-shadow:var(--shadow-lg);min-width:220px;padding:6px;z-index:60;opacity:0;pointer-events:none;transform:translateY(-4px);transition:.15s;}
    .tb-user-wrap.open .tb-menu{opacity:1;pointer-events:auto;transform:translateY(0);}
    .tb-menu__head{padding:10px 12px 6px;border-bottom:1px solid var(--bord-soft);margin-bottom:4px;}
    .tb-menu__head strong{display:block;font-size:.86rem;color:var(--ink);line-height:1.2;}
    .tb-menu__head small{font-size:.72rem;color:var(--ink4);}
    .tb-menu__item{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;font-size:.86rem;color:var(--ink2);cursor:pointer;transition:.12s;}
    .tb-menu__item i{color:var(--ink3);width:16px;font-size:.95rem;}
    .tb-menu__item:hover{background:var(--bg);color:var(--primary);}
    .tb-menu__item:hover i{color:var(--primary);}
    .tb-menu__item.danger{color:var(--red);}
    .tb-menu__item.danger i{color:var(--red);}
    .tb-menu__item.danger:hover{background:var(--red-soft);}
    .tb-menu__sep{height:1px;background:var(--bord-soft);margin:4px 0;}

    /* ══ PAGE ══ */
    .page{max-width:1320px;margin:0 auto;padding:28px 32px 48px;}
    .page-head{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin-bottom:20px;flex-wrap:wrap;}
    .page-head h1{font-size:1.8rem;font-weight:700;margin-bottom:4px;}
    .page-head p{margin:0;color:var(--ink3);font-size:.9rem;}
    .btn-primary{background:var(--primary);color:var(--white);border:0;padding:12px 20px;border-radius:11px;font-weight:600;font-size:.88rem;display:inline-flex;align-items:center;gap:8px;cursor:pointer;transition:.15s;box-shadow:0 1px 2px rgba(0,0,0,.05);}
    .btn-primary:hover{background:var(--primary-2);transform:translateY(-1px);}

    /* ══ LAYOUT 2 COL ══ */
    .layout{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:20px;align-items:start;}

    /* ══ CARD ══ */
    .card{background:var(--white);border-radius:16px;box-shadow:var(--shadow);overflow:hidden;}

    /* ══ FILTROS ══ */
    .filters{padding:18px 20px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;border-bottom:1px solid var(--bord-soft);}
    .filters .search{position:relative;flex:1;min-width:260px;}
    .filters .search i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--ink4);}
    .filters .search input{width:100%;padding:10px 12px 10px 36px;border:1px solid var(--bord);border-radius:10px;background:var(--bg);font-size:.88rem;font-family:inherit;outline:none;transition:.15s;}
    .filters .search input:focus{background:var(--white);border-color:var(--primary);box-shadow:0 0 0 3px rgba(11,46,23,.07);}
    .filters select{padding:10px 34px 10px 12px;border:1px solid var(--bord);border-radius:10px;background:var(--white) url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2364748B' viewBox='0 0 16 16'%3e%3cpath d='M3.204 5h9.592L8 10.481 3.204 5zm-.753.659 4.796 5.48a1 1 0 0 0 1.506 0l4.796-5.48c.566-.647.106-1.659-.753-1.659H3.204a1 1 0 0 0-.753 1.659z'/%3e%3c/svg%3e") no-repeat right 12px center;appearance:none;font-size:.84rem;font-weight:500;color:var(--ink2);cursor:pointer;font-family:inherit;}
    .filters select:focus{outline:none;border-color:var(--primary);}
    .filters .count{margin-left:auto;font-size:.82rem;color:var(--ink3);}
    .filters .count strong{color:var(--primary);font-weight:700;}

    /* ══ TABLA ══ */
    .table-wrap{position:relative;min-height:280px;overflow-x:auto;}
    .u-table{width:100%;border-collapse:collapse;font-size:.86rem;min-width:960px;}
    .u-table thead th{text-align:left;font-weight:600;color:var(--ink3);font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;padding:12px 18px;background:var(--bg);border-bottom:1px solid var(--bord);white-space:nowrap;}
    .u-table thead th.th-sort{cursor:pointer;user-select:none;transition:.15s;}
    .u-table thead th.th-sort:hover{color:var(--primary);}
    .u-table thead th .s-ico{font-size:.78rem;opacity:.45;margin-left:3px;vertical-align:middle;}
    .u-table thead th.sorted .s-ico{opacity:1;color:var(--primary);}
    .u-table tbody td{padding:14px 18px;border-bottom:1px solid var(--bord-soft);vertical-align:middle;}
    .u-table tbody tr:hover{background:var(--bg);}
    .u-table tbody tr:last-child td{border-bottom:none;}
    .u-table tbody tr.sel{background:#EEF5EF !important;}

    .cb{width:16px;height:16px;accent-color:var(--primary);cursor:pointer;}

    .cell-user{display:flex;align-items:center;gap:10px;min-width:180px;}
    .av{width:38px;height:38px;border-radius:50%;background:var(--primary);color:var(--accent);font-weight:700;display:flex;align-items:center;justify-content:center;font-size:.85rem;overflow:hidden;flex-shrink:0;}
    .av img{width:100%;height:100%;object-fit:cover;}
    .cell-user strong{color:var(--ink);font-weight:600;font-size:.88rem;display:block;line-height:1.25;}
    .cell-user small{color:var(--ink4);font-size:.72rem;}
    .cell-email{color:var(--ink2);font-size:.84rem;}
    .cell-city{color:var(--ink2);font-size:.84rem;}
    .cell-date{color:var(--ink3);font-size:.82rem;white-space:nowrap;}

    .badge{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:999px;font-size:.72rem;font-weight:600;white-space:nowrap;}
    .b-admin{background:var(--primary);color:var(--white);}
    .b-user{background:#F1F5F9;color:var(--ink2);}
    .b-seller{background:var(--blue-soft);color:#1E40AF;}
    .b-verified{background:var(--green-soft);color:var(--primary);}
    .b-unverified{background:#F1F5F9;color:var(--ink3);}
    .b-active{background:var(--green-soft);color:var(--primary);}
    .b-suspended{background:var(--red-soft);color:var(--red);}
    .b-blocked{background:#FEF0E4;color:#B45309;}

    /* ══ MENÚ DE 3 PUNTOS ══ */
    .dots{width:32px;height:32px;border-radius:8px;border:1px solid transparent;background:transparent;color:var(--ink3);display:inline-flex;align-items:center;justify-content:center;cursor:pointer;transition:.15s;}
    .dots:hover{background:var(--bg);color:var(--primary);border-color:var(--bord);}
    .menu{position:absolute;right:0;top:calc(100% + 4px);background:var(--white);border:1px solid var(--bord);border-radius:12px;box-shadow:var(--shadow-lg);min-width:200px;padding:6px;z-index:30;opacity:0;pointer-events:none;transform:translateY(-4px);transition:.15s;}
    .menu.open{opacity:1;pointer-events:auto;transform:translateY(0);}
    .menu-item{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;cursor:pointer;font-size:.84rem;color:var(--ink2);transition:.12s;}
    .menu-item i{font-size:.95rem;color:var(--ink3);width:16px;}
    .menu-item:hover{background:var(--bg);color:var(--primary);}
    .menu-item:hover i{color:var(--primary);}
    .menu-item.danger:hover{background:var(--red-soft);color:var(--red);}
    .menu-item.danger:hover i{color:var(--red);}
    .menu-sep{height:1px;background:var(--bord-soft);margin:4px 0;}
    .menu-anchor{position:relative;display:inline-block;}

    /* ══ PAGINACIÓN ══ */
    .pagination{display:flex;justify-content:space-between;align-items:center;padding:14px 20px;border-top:1px solid var(--bord-soft);flex-wrap:wrap;gap:10px;}
    .page-info{font-size:.82rem;color:var(--ink3);}
    .page-info strong{color:var(--primary);}
    .page-btns{display:flex;gap:4px;}
    .pg-btn{min-width:34px;height:34px;padding:0 10px;border:1px solid var(--bord);background:var(--white);border-radius:8px;font-size:.82rem;font-weight:600;color:var(--ink2);cursor:pointer;transition:.15s;display:inline-flex;align-items:center;justify-content:center;}
    .pg-btn:hover:not(:disabled){border-color:var(--primary);color:var(--primary);}
    .pg-btn.active{background:var(--primary);border-color:var(--primary);color:var(--white);}
    .pg-btn:disabled{opacity:.4;cursor:not-allowed;}
    .pg-dots{display:inline-flex;align-items:center;padding:0 6px;color:var(--ink4);}

    /* ══ SIDEBAR STATS ══ */
    .sbar{display:flex;flex-direction:column;gap:14px;}
    .sbar-title{font-family:'Syne',sans-serif;font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--ink3);padding:0 4px;margin-bottom:2px;}
    .stat-mini{background:var(--white);border-radius:14px;padding:18px 18px;box-shadow:var(--shadow);position:relative;overflow:hidden;}
    .stat-mini__icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;margin-bottom:12px;}
    .stat-mini__num{font-family:'Syne',sans-serif;font-size:1.75rem;font-weight:800;color:var(--primary);line-height:1;margin-bottom:4px;}
    .stat-mini__lbl{font-size:.8rem;color:var(--ink3);font-weight:500;}
    .stat-mini__delta{font-size:.7rem;margin-top:8px;font-weight:600;display:inline-flex;align-items:center;gap:3px;}
    .stat-mini--total .stat-mini__icon{background:var(--green-soft);color:var(--primary);}
    .stat-mini--admins .stat-mini__icon{background:var(--yellow-soft);color:#B45309;}
    .stat-mini--susp .stat-mini__icon{background:var(--red-soft);color:var(--red);}
    .delta-up{color:var(--primary);}
    .delta-down{color:var(--red);}

    /* ══ LOADING ══ */
    .loading{position:absolute;inset:0;background:rgba(255,255,255,.7);display:none;align-items:center;justify-content:center;z-index:10;}
    .loading.active{display:flex;}
    .spinner{width:36px;height:36px;border:3px solid var(--bord);border-top-color:var(--primary);border-radius:50%;animation:spin .7s linear infinite;}
    @keyframes spin{to{transform:rotate(360deg);}}

    .empty{padding:56px 24px;text-align:center;color:var(--ink4);}
    .empty i{font-size:2.6rem;color:#CBD5E1;display:block;margin-bottom:10px;}
    .empty strong{color:var(--ink2);display:block;margin-bottom:4px;}

    /* ══ MODALES ══ */
    .modal-bg{position:fixed;inset:0;background:rgba(11,46,23,.55);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center;z-index:100;padding:16px;}
    .modal-bg.open{display:flex;}
    .modal{background:var(--white);border-radius:18px;width:100%;max-width:460px;box-shadow:var(--shadow-lg);overflow:hidden;animation:modalIn .22s ease-out;}
    @keyframes modalIn{from{opacity:0;transform:translateY(12px) scale(.97);}to{opacity:1;transform:none;}}
    .modal-head{padding:22px 24px 6px;}
    .modal-head h2{font-size:1.2rem;font-weight:700;margin-bottom:4px;}
    .modal-head p{margin:0;font-size:.84rem;color:var(--ink3);}
    .modal-body{padding:18px 24px 8px;display:flex;flex-direction:column;gap:14px;}
    .field label{display:block;font-size:.78rem;font-weight:600;color:var(--ink2);margin-bottom:6px;}
    .field input,.field select{width:100%;padding:10px 12px;border:1px solid var(--bord);border-radius:10px;font-size:.88rem;font-family:inherit;outline:none;transition:.15s;background:var(--bg);}
    .field input:focus,.field select:focus{background:var(--white);border-color:var(--primary);box-shadow:0 0 0 3px rgba(11,46,23,.07);}
    .field small{display:block;margin-top:5px;font-size:.72rem;color:var(--ink4);}
    .modal-actions{padding:18px 24px 22px;display:flex;gap:10px;justify-content:flex-end;}
    .btn-secondary{background:var(--bg);color:var(--ink2);border:1px solid var(--bord);padding:10px 16px;border-radius:10px;font-weight:600;font-size:.85rem;cursor:pointer;transition:.15s;}
    .btn-secondary:hover{background:var(--bord-soft);}
    .btn-modal-primary{background:var(--primary);color:var(--white);border:0;padding:10px 18px;border-radius:10px;font-weight:600;font-size:.85rem;cursor:pointer;transition:.15s;}
    .btn-modal-primary:hover{background:var(--primary-2);}
    .btn-modal-primary:disabled{opacity:.5;cursor:not-allowed;}

    .role-option{display:flex;align-items:center;gap:12px;padding:12px;border:1.5px solid var(--bord);border-radius:12px;cursor:pointer;transition:.12s;}
    .role-option:hover{border-color:var(--primary);background:var(--bg);}
    .role-option input{accent-color:var(--primary);}
    .role-option.sel{border-color:var(--primary);background:var(--green-soft);}
    .role-option .ro-ico{width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:var(--bg);color:var(--primary);}
    .role-option strong{display:block;font-size:.88rem;color:var(--ink);line-height:1.2;}
    .role-option span{font-size:.75rem;color:var(--ink3);}

    .form-err{background:var(--red-soft);color:var(--red);padding:10px 12px;border-radius:10px;font-size:.8rem;display:none;}
    .form-err.show{display:block;}

    /* ══ TOAST ══ */
    .toast-area{position:fixed;bottom:24px;right:24px;display:flex;flex-direction:column;gap:10px;z-index:200;}
    .toast{background:var(--primary);color:var(--white);padding:13px 18px;border-radius:12px;box-shadow:var(--shadow-lg);font-size:.86rem;font-weight:600;display:flex;align-items:center;gap:10px;min-width:260px;animation:toastIn .25s;}
    .toast.err{background:var(--red);}
    .toast.warn{background:var(--accent);color:var(--primary);}
    @keyframes toastIn{from{opacity:0;transform:translateX(20px);}to{opacity:1;transform:none;}}
    .toast button{background:transparent;border:0;color:inherit;opacity:.7;cursor:pointer;margin-left:auto;}

    /* ══ RESPONSIVE ══ */
    @media (max-width:1100px){
      .layout{grid-template-columns:1fr;}
      .sbar{flex-direction:row;overflow-x:auto;}
      .stat-mini{min-width:220px;}
    }
    @media (max-width:720px){
      .topbar{padding:12px 16px;flex-wrap:wrap;gap:12px;}
      .tb-nav{order:3;width:100%;overflow-x:auto;margin:0;}
      .page{padding:20px 16px 40px;}
      .filters{padding:14px;}
      .tb-user__info{display:none;}
    }
  </style>
</head>
<body>

  <!-- ══ TOPBAR ══ -->
  <header class="topbar">
    <a href="./dashboard.php" class="tb-logo">
      <span class="tb-logo-mark"><i class="bi bi-shop-window"></i></span>
      ComercioLocal
    </a>
    <nav class="tb-nav">
      <a class="tb-link" href="./dashboard.php"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
      <a class="tb-link active" href="./gestion_usuarios.php"><i class="bi bi-people"></i> Usuarios</a>
      <a class="tb-link" href="./gestion_admins.php"><i class="bi bi-shield-fill-check"></i> Administradores</a>
      <a class="tb-link" href="./verificaciones.php"><i class="bi bi-patch-check"></i> Verificaciones</a>
      <a class="tb-link" href="./gestion_productos.php"><i class="bi bi-box-seam"></i> Productos</a>
      <a class="tb-link" href="./estadisticas.php"><i class="bi bi-bar-chart-line"></i> Estadísticas</a>
      <a class="tb-link" href="./reportes.php"><i class="bi bi-flag-fill"></i> Reportes</a>
    </nav>
    <div class="tb-right">
      <button class="tb-bell" title="Notificaciones"><i class="bi bi-bell-fill"></i></button>
      <div class="tb-user-wrap">
        <button class="tb-user" type="button" onclick="toggleTbMenu(event)">
          <div class="tb-user__av"><?= htmlspecialchars($userInitial) ?></div>
          <div class="tb-user__info">
            <strong><?= htmlspecialchars($userName) ?></strong>
            <span><?= $isSuperAdmin ? 'Super Administrador' : 'Administrador' ?></span>
          </div>
          <i class="bi bi-chevron-down tb-user__caret"></i>
        </button>
        <div class="tb-menu">
          <div class="tb-menu__head">
            <strong><?= htmlspecialchars($userName) ?></strong>
            <small><?= htmlspecialchars($user['email'] ?? '') ?></small>
          </div>
          <a class="tb-menu__item" href="../home.php"><i class="bi bi-house-door"></i> Ir al sitio</a>
          <a class="tb-menu__item" href="../configuracion.php"><i class="bi bi-gear"></i> Configuración</a>
          <div class="tb-menu__sep"></div>
          <a class="tb-menu__item danger" href="../../controllers/auth_logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
        </div>
      </div>
    </div>
  </header>

  <!-- ══ PAGE ══ -->
  <div class="page">

    <!-- Header -->
    <div class="page-head">
      <div>
        <h1>Usuarios</h1>
        <p>Administra cuentas, roles y permisos de toda la plataforma.</p>
      </div>
      <button class="btn-primary" onclick="openCreateModal()">
        <i class="bi bi-plus-lg"></i> Crear administrador
      </button>
    </div>

    <div class="layout">

      <!-- ══ COLUMNA PRINCIPAL ══ -->
      <section>
        <div class="card">
          <!-- Filtros -->
          <div class="filters">
            <div class="search">
              <i class="bi bi-search"></i>
              <input type="text" id="fSearch" placeholder="Buscar por nombre o correo...">
            </div>
            <select id="fStatus">
              <option value="">Estado: Todos</option>
              <option value="active">Activo</option>
              <option value="suspended">Suspendido</option>
              <option value="blocked">Bloqueado</option>
            </select>
            <select id="fRole">
              <option value="">Rol: Todos</option>
              <option value="user">Usuario</option>
              <option value="seller">Vendedor</option>
              <option value="admin">Administrador</option>
            </select>
            <select id="fVerified">
              <option value="">Verificación: Todos</option>
              <option value="1">Verificado</option>
              <option value="0">Sin verificar</option>
            </select>
            <div class="count" id="countInfo">Cargando…</div>
          </div>

          <!-- Tabla -->
          <div class="table-wrap">
            <div class="loading active" id="loading"><div class="spinner"></div></div>
            <table class="u-table" id="usersTable">
              <thead>
                <tr>
                  <th style="width:38px;"><input type="checkbox" class="cb" id="selAll" onchange="toggleAll(this)"></th>
                  <th class="th-sort" data-sort="full_name">Usuario <i class="bi bi-chevron-expand s-ico"></i></th>
                  <th class="th-sort" data-sort="email">Correo <i class="bi bi-chevron-expand s-ico"></i></th>
                  <th class="th-sort" data-sort="city">Ciudad <i class="bi bi-chevron-expand s-ico"></i></th>
                  <th class="th-sort" data-sort="role">Rol <i class="bi bi-chevron-expand s-ico"></i></th>
                  <th>Verificado</th>
                  <th class="th-sort" data-sort="status">Estado <i class="bi bi-chevron-expand s-ico"></i></th>
                  <th class="th-sort" data-sort="created_at">Registro <i class="bi bi-chevron-expand s-ico"></i></th>
                  <th style="width:40px;text-align:center;">Acciones</th>
                </tr>
              </thead>
              <tbody id="tbody"></tbody>
            </table>
          </div>

          <!-- Paginación -->
          <div class="pagination">
            <div class="page-info" id="pageInfo">—</div>
            <div class="page-btns" id="pageBtns"></div>
          </div>
        </div>
      </section>

      <!-- ══ PANEL LATERAL ══ -->
      <aside class="sbar">
        <div class="sbar-title">Estadísticas rápidas</div>

        <div class="stat-mini stat-mini--total">
          <div class="stat-mini__icon"><i class="bi bi-people-fill"></i></div>
          <div class="stat-mini__num" id="statTotal">—</div>
          <div class="stat-mini__lbl">Total usuarios</div>
          <div class="stat-mini__delta delta-up" id="statTotalDelta"></div>
        </div>

        <div class="stat-mini stat-mini--admins">
          <div class="stat-mini__icon"><i class="bi bi-shield-fill-check"></i></div>
          <div class="stat-mini__num" id="statAdmins">—</div>
          <div class="stat-mini__lbl">Administradores activos</div>
        </div>

        <div class="stat-mini stat-mini--susp">
          <div class="stat-mini__icon"><i class="bi bi-pause-circle-fill"></i></div>
          <div class="stat-mini__num" id="statSusp">—</div>
          <div class="stat-mini__lbl">Cuentas suspendidas</div>
        </div>
      </aside>

    </div>
  </div>

  <!-- ══ MODAL: CREAR ADMIN (SCJ-48) ══ -->
  <div class="modal-bg" id="mCreate">
    <div class="modal">
      <div class="modal-head">
        <h2>Crear administrador</h2>
        <p>Se enviará una contraseña temporal al nuevo administrador.</p>
      </div>
      <div class="modal-body">
        <div class="form-err" id="createErr"></div>
        <div class="field"><label>Nombre completo</label><input type="text" id="cName" placeholder="Ej. Mariana Torres"></div>
        <div class="field"><label>Correo electrónico</label><input type="email" id="cEmail" placeholder="admin@comerciolocal.co"></div>
        <div class="field">
          <label>Contraseña temporal</label>
          <input type="text" id="cPass" placeholder="Mínimo 8 caracteres">
          <small>Debe tener al menos 8 caracteres. Se solicitará cambiarla en el primer ingreso.</small>
        </div>
      </div>
      <div class="modal-actions">
        <button class="btn-secondary" onclick="closeModal('mCreate')">Cancelar</button>
        <button class="btn-modal-primary" id="cSubmit" onclick="submitCreate()">Crear admin</button>
      </div>
    </div>
  </div>

  <!-- ══ MODAL: CAMBIAR ROL (SCJ-12) ══ -->
  <div class="modal-bg" id="mRole">
    <div class="modal" style="max-width:420px;">
      <div class="modal-head">
        <h2>Cambiar rol</h2>
        <p id="rUserName">—</p>
      </div>
      <div class="modal-body">
        <label class="role-option" data-val="user" onclick="pickRole(this)">
          <input type="radio" name="newRole" value="user">
          <div class="ro-ico"><i class="bi bi-person"></i></div>
          <div><strong>Usuario</strong><span>Acceso estándar (comprador / vendedor)</span></div>
        </label>
        <label class="role-option" data-val="admin" onclick="pickRole(this)">
          <input type="radio" name="newRole" value="admin">
          <div class="ro-ico"><i class="bi bi-shield-fill-check"></i></div>
          <div><strong>Administrador</strong><span>Acceso al panel de administración</span></div>
        </label>
      </div>
      <div class="modal-actions">
        <button class="btn-secondary" onclick="closeModal('mRole')">Cancelar</button>
        <button class="btn-modal-primary" id="rSubmit" onclick="submitRole()">Confirmar</button>
      </div>
    </div>
  </div>

  <div class="toast-area" id="toastArea"></div>

  <!-- ══ SCRIPTS ══ -->
  <script>
    function toggleTbMenu(e){e.stopPropagation();e.currentTarget.closest('.tb-user-wrap').classList.toggle('open');}
    document.addEventListener('click',e=>{if(!e.target.closest('.tb-user-wrap'))document.querySelectorAll('.tb-user-wrap.open').forEach(el=>el.classList.remove('open'));});
  </script>
  <script>
    const API_USERS  = '../../api/admin/users.php';
    const API_ADMINS = '../../api/admin/admins.php';
    const IS_SUPER   = <?= json_encode($isSuperAdmin) ?>;

    let state = { page:1, limit:10, sort:'created_at', dir:'DESC', search:'', role:'', status:'', verified:'' };
    let selected = new Set();
    let searchTimer = null;
    let openMenuEl = null;
    let roleContext = null; // { id, name }

    /* ══ BOOT ══ */
    document.addEventListener('DOMContentLoaded', () => {
      loadStats();
      loadList();

      document.getElementById('fSearch').addEventListener('input', e => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => { state.search = e.target.value.trim(); state.page = 1; loadList(); }, 380);
      });
      ['fStatus','fRole','fVerified'].forEach(id => {
        document.getElementById(id).addEventListener('change', e => {
          if (id==='fStatus') state.status = e.target.value;
          if (id==='fRole')   state.role   = e.target.value;
          if (id==='fVerified') state.verified = e.target.value;
          state.page = 1;
          loadList();
        });
      });

      document.querySelectorAll('.th-sort').forEach(th => {
        th.addEventListener('click', () => {
          const col = th.dataset.sort;
          if (state.sort === col) state.dir = (state.dir === 'ASC') ? 'DESC' : 'ASC';
          else { state.sort = col; state.dir = 'ASC'; }
          updateSortHeaders();
          loadList();
        });
      });
      updateSortHeaders();

      document.addEventListener('click', e => {
        if (openMenuEl && !e.target.closest('.menu-anchor')) closeMenu();
      });
      document.addEventListener('keydown', e => {
        if (e.key === 'Escape') { closeAllModals(); closeMenu(); }
      });
      ['mCreate','mRole'].forEach(id => {
        document.getElementById(id).addEventListener('click', e => {
          if (e.target.id === id) closeModal(id);
        });
      });
    });

    /* ══ DATA ══ */
    async function loadStats() {
      try {
        const r = await fetch(`${API_USERS}?action=stats`);
        const j = await r.json();
        if (!j.ok) return;
        const d = j.data;
        document.getElementById('statTotal').textContent  = fmtNum(d.total);
        document.getElementById('statAdmins').textContent = fmtNum(d.admins);
        document.getElementById('statSusp').textContent   = fmtNum(d.suspended);
        if (d.total) document.getElementById('statTotalDelta').innerHTML = `<i class="bi bi-arrow-up-right"></i> ${fmtNum(d.active || 0)} activos`;
      } catch (e) { /* silent */ }
    }

    async function loadList() {
      setLoading(true);
      const q = new URLSearchParams({ action:'list', page:state.page, limit:state.limit, sort:state.sort, dir:state.dir,
        search:state.search, role:state.role, status:state.status, verified:state.verified });
      try {
        const r = await fetch(`${API_USERS}?${q}`);
        const j = await r.json();
        if (!j.ok) { toast('error', j.error || 'Error al cargar'); setLoading(false); return; }
        renderRows(j.data);
        renderPagination(j.pagination);
      } catch (e) { toast('error', 'Error de conexión'); }
      setLoading(false);
    }

    /* ══ RENDER ══ */
    function renderRows(rows) {
      const tb = document.getElementById('tbody');
      if (!rows || rows.length === 0) {
        tb.innerHTML = `<tr><td colspan="9"><div class="empty"><i class="bi bi-inbox"></i><strong>Sin resultados</strong>Intenta con otros filtros.</div></td></tr>`;
        return;
      }
      tb.innerHTML = rows.map(u => {
        const isSel = selected.has(u.id) ? 'sel' : '';
        const av = u.profile_photo
          ? `<div class="av"><img src="../../../public/uploads/perfiles/${esc(u.profile_photo)}" onerror="this.parentNode.textContent='${esc(u.initials||'U')}'"></div>`
          : `<div class="av">${esc(u.initials || (u.full_name||'?').charAt(0).toUpperCase())}</div>`;

        return `<tr class="${isSel}" id="row-${u.id}">
          <td><input type="checkbox" class="cb" ${selected.has(u.id)?'checked':''} onchange="toggleRow(${u.id},this)"></td>
          <td>
            <div class="cell-user">
              ${av}
              <div><strong>${esc(u.full_name||'Sin nombre')}</strong><small>ID #${u.id}</small></div>
            </div>
          </td>
          <td class="cell-email">${esc(u.email||'—')}</td>
          <td class="cell-city">${esc(u.city||'—')}</td>
          <td>${roleBadge(u.role)}</td>
          <td>${u.is_verified ? `<span class="badge b-verified"><i class="bi bi-patch-check-fill"></i> Verificado</span>` : `<span class="badge b-unverified">Sin verificar</span>`}</td>
          <td>${statusBadge(u.status)}</td>
          <td class="cell-date">${fmtDate(u.created_at)}</td>
          <td style="text-align:center;">
            <div class="menu-anchor">
              <button class="dots" onclick="toggleMenu(event,${u.id})" title="Acciones"><i class="bi bi-three-dots-vertical"></i></button>
              <div class="menu" id="menu-${u.id}">
                <div class="menu-item" onclick="openRoleModal(${u.id},'${escJs(u.full_name||'')}','${u.role||''}')"><i class="bi bi-person-gear"></i> Cambiar rol</div>
                ${u.status === 'active'
                  ? `<div class="menu-item" onclick="act('suspend',${u.id},'${escJs(u.full_name||'')}')"><i class="bi bi-pause-circle"></i> Suspender cuenta</div>
                     <div class="menu-item" onclick="act('block',${u.id},'${escJs(u.full_name||'')}')"><i class="bi bi-slash-circle"></i> Bloquear cuenta</div>`
                  : `<div class="menu-item" onclick="act('activate',${u.id},'${escJs(u.full_name||'')}')"><i class="bi bi-play-circle"></i> Reactivar cuenta</div>`}
                <div class="menu-sep"></div>
                <div class="menu-item danger" onclick="act('delete',${u.id},'${escJs(u.full_name||'')}')"><i class="bi bi-trash3"></i> Eliminar cuenta</div>
              </div>
            </div>
          </td>
        </tr>`;
      }).join('');
    }

    function renderPagination(p) {
      const { page, limit, total, total_pages } = p;
      const from = total === 0 ? 0 : (page-1)*limit+1;
      const to   = Math.min(page*limit, total);

      document.getElementById('countInfo').innerHTML = `<strong>${fmtNum(total)}</strong> usuarios encontrados`;
      document.getElementById('pageInfo').innerHTML = total === 0 ? 'Sin resultados' : `Mostrando <strong>${fmtNum(from)}–${fmtNum(to)}</strong> de <strong>${fmtNum(total)}</strong>`;

      const btns = document.getElementById('pageBtns'); btns.innerHTML = '';
      if (total_pages <= 1) return;
      const add = (label, pg, dis=false, act=false) => {
        const b = document.createElement('button');
        b.className = 'pg-btn' + (act?' active':'');
        b.innerHTML = label; b.disabled = dis;
        b.onclick = () => { state.page = pg; loadList(); };
        btns.appendChild(b);
      };
      add('<i class="bi bi-chevron-left"></i>', page-1, page===1);
      const nums = buildPages(page, total_pages);
      let prev = null;
      nums.forEach(n => {
        if (prev !== null && n - prev > 1) { const s = document.createElement('span'); s.className='pg-dots'; s.textContent='…'; btns.appendChild(s); }
        add(n, n, false, n===page); prev = n;
      });
      add('<i class="bi bi-chevron-right"></i>', page+1, page===total_pages);
    }
    function updateSortHeaders() {
      document.querySelectorAll('.th-sort').forEach(th => {
        const isActive = th.dataset.sort === state.sort;
        th.classList.toggle('sorted', isActive);
        const ico = th.querySelector('.s-ico');
        if (!ico) return;
        ico.className = 's-ico bi ' + (isActive
          ? (state.dir === 'ASC' ? 'bi-chevron-up' : 'bi-chevron-down')
          : 'bi-chevron-expand');
      });
    }

    function buildPages(cur, tot) {
      if (tot <= 6) return Array.from({length:tot},(_,i)=>i+1);
      const s = new Set([1, cur-1, cur, cur+1, tot].filter(n => n>=1 && n<=tot));
      return [...s].sort((a,b)=>a-b);
    }

    /* ══ BADGES ══ */
    function roleBadge(r) {
      if (r === 'admin' || r === 'super_admin') return `<span class="badge b-admin"><i class="bi bi-shield-fill"></i> Admin</span>`;
      if (r === 'seller') return `<span class="badge b-seller"><i class="bi bi-shop"></i> Vendedor</span>`;
      return `<span class="badge b-user">Usuario</span>`;
    }
    function statusBadge(s) {
      if (s === 'active') return `<span class="badge b-active"><i class="bi bi-check-circle-fill"></i> Activo</span>`;
      if (s === 'suspended') return `<span class="badge b-suspended"><i class="bi bi-pause-circle-fill"></i> Suspendido</span>`;
      if (s === 'blocked') return `<span class="badge b-blocked"><i class="bi bi-slash-circle-fill"></i> Bloqueado</span>`;
      return `<span class="badge b-user">—</span>`;
    }

    /* ══ MENÚ ══ */
    function toggleMenu(e, id) {
      e.stopPropagation();
      const el = document.getElementById(`menu-${id}`);
      if (openMenuEl && openMenuEl !== el) openMenuEl.classList.remove('open');
      el.classList.toggle('open');
      openMenuEl = el.classList.contains('open') ? el : null;
    }
    function closeMenu() { if (openMenuEl) { openMenuEl.classList.remove('open'); openMenuEl = null; } }

    /* ══ ACCIONES ══ */
    function toggleAll(cb) {
      document.querySelectorAll('#tbody .cb').forEach(c => {
        c.checked = cb.checked;
        const id = parseInt(c.closest('tr').id.replace('row-',''));
        if (cb.checked) selected.add(id); else selected.delete(id);
        c.closest('tr').classList.toggle('sel', cb.checked);
      });
    }
    function toggleRow(id, cb) {
      if (cb.checked) selected.add(id); else selected.delete(id);
      document.getElementById(`row-${id}`)?.classList.toggle('sel', cb.checked);
    }

    async function act(action, id, name='') {
      closeMenu();
      let conf = true;
      if (action === 'suspend')  conf = confirm(`¿Suspender la cuenta de "${name}"?\nEl usuario no podrá iniciar sesión hasta ser reactivado.`);
      if (action === 'block')    conf = confirm(`¿Bloquear la cuenta de "${name}"?\nEl usuario quedará permanentemente sin acceso.`);
      if (action === 'activate') conf = confirm(`¿Reactivar la cuenta de "${name}"?`);
      if (action === 'delete')   conf = confirm(`⚠️ ¿Eliminar la cuenta de "${name}"?\nEsta acción es irreversible.`);
      if (!conf) return;

      try {
        const r = await fetch(`${API_USERS}?action=${action}`, {
          method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ id }),
        });
        const j = await r.json();
        if (j.ok) {
          const messages = { delete:'Cuenta eliminada', suspend:'Cuenta suspendida', block:'Cuenta bloqueada', activate:'Cuenta reactivada' };
          toast('success', messages[action] || 'Listo');
          loadList(); loadStats();
        } else toast('error', j.error || 'No se pudo completar');
      } catch (e) { toast('error', 'Error de red'); }
    }

    /* ══ CREAR ADMIN ══ */
    function openCreateModal() {
      ['cName','cEmail','cPass'].forEach(id => document.getElementById(id).value = '');
      hideErr('createErr');
      openModal('mCreate');
      setTimeout(() => document.getElementById('cName').focus(), 100);
    }
    async function submitCreate() {
      const name  = document.getElementById('cName').value.trim();
      const email = document.getElementById('cEmail').value.trim();
      const pass  = document.getElementById('cPass').value;
      if (name.length < 2)  return showErr('createErr', 'El nombre debe tener al menos 2 caracteres.');
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return showErr('createErr', 'Correo electrónico inválido.');
      if (pass.length === 0) return showErr('createErr', 'La contraseña es obligatoria.');

      const btn = document.getElementById('cSubmit');
      btn.disabled = true; btn.textContent = 'Creando…';
      try {
        const r = await fetch(`${API_ADMINS}?action=create`, {
          method:'POST', headers:{'Content-Type':'application/json'},
          body:JSON.stringify({ name, email, password:pass, role:'admin' }),
        });
        const j = await r.json();
        if (j.ok) { closeModal('mCreate'); toast('success', 'Administrador creado correctamente'); loadList(); loadStats(); }
        else showErr('createErr', j.error || 'Error al crear administrador');
      } catch (e) { showErr('createErr', 'Error de conexión'); }
      btn.disabled = false; btn.textContent = 'Crear admin';
    }

    /* ══ CAMBIAR ROL ══ */
    function openRoleModal(id, name, currentRole) {
      closeMenu();
      roleContext = { id, name };
      document.getElementById('rUserName').textContent = name;
      document.querySelectorAll('.role-option').forEach(o => {
        const isMatch = (currentRole === 'admin' || currentRole === 'super_admin') ? o.dataset.val === 'admin' : o.dataset.val === 'user';
        o.classList.toggle('sel', isMatch);
        o.querySelector('input').checked = isMatch;
      });
      openModal('mRole');
    }
    function pickRole(el) {
      document.querySelectorAll('.role-option').forEach(o => o.classList.remove('sel'));
      el.classList.add('sel');
      el.querySelector('input').checked = true;
    }
    async function submitRole() {
      if (!roleContext) return;
      const chosen = document.querySelector('input[name="newRole"]:checked');
      if (!chosen) return toast('warn', 'Selecciona un rol');
      const role = chosen.value;
      const btn = document.getElementById('rSubmit');
      btn.disabled = true; btn.textContent = 'Aplicando…';
      try {
        const r = await fetch(`${API_USERS}?action=change_role`, {
          method:'POST', headers:{'Content-Type':'application/json'},
          body:JSON.stringify({ id:roleContext.id, role }),
        });
        const j = await r.json();
        if (j.ok) { closeModal('mRole'); toast('success', `Rol actualizado a ${role === 'admin' ? 'Administrador' : 'Usuario'}`); loadList(); loadStats(); }
        else toast('error', j.error || 'No se pudo cambiar el rol');
      } catch (e) { toast('error', 'Error de red'); }
      btn.disabled = false; btn.textContent = 'Confirmar';
    }

    /* ══ MODAL / TOAST HELPERS ══ */
    function openModal(id) { document.getElementById(id).classList.add('open'); }
    function closeModal(id) { document.getElementById(id).classList.remove('open'); }
    function closeAllModals() { document.querySelectorAll('.modal-bg').forEach(m => m.classList.remove('open')); }
    function showErr(id, msg) { const el = document.getElementById(id); el.textContent = msg; el.classList.add('show'); }
    function hideErr(id) { document.getElementById(id).classList.remove('show'); }
    function setLoading(on) { document.getElementById('loading').classList.toggle('active', on); }

    function toast(type, msg) {
      const el = document.createElement('div');
      el.className = 'toast ' + (type === 'error' ? 'err' : type === 'warn' ? 'warn' : '');
      const ico = type === 'error' ? 'bi-x-circle-fill' : type === 'warn' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill';
      el.innerHTML = `<i class="bi ${ico}"></i>${esc(msg)}<button onclick="this.parentElement.remove()"><i class="bi bi-x-lg"></i></button>`;
      document.getElementById('toastArea').appendChild(el);
      setTimeout(() => el.remove(), 4200);
    }

    /* ══ UTILS ══ */
    function fmtNum(n) { return Number(n||0).toLocaleString('es-CO'); }
    function fmtDate(s) {
      if (!s) return '—';
      const d = new Date(s);
      return isNaN(d) ? s : d.toLocaleDateString('es-CO', { day:'2-digit', month:'short', year:'numeric' });
    }
    function esc(v) { return String(v??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    function escJs(v) { return String(v??'').replace(/\\/g,'\\\\').replace(/'/g,"\\'").replace(/"/g,'&quot;'); }
  </script>

</body>
</html>

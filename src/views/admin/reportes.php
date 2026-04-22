<?php
/**
 * Reportes — ComercioLocal Admin
 * Gestión de reportes de productos y usuarios.
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
  <title>Reportes — ComercioLocal Admin</title>

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
    .tb-nav{display:flex;gap:4px;margin:0 auto;flex-wrap:wrap;}
    .tb-link{padding:8px 14px;border-radius:10px;font-weight:500;font-size:.88rem;color:var(--ink2);transition:.15s;display:inline-flex;align-items:center;gap:6px;}
    .tb-link:hover{background:var(--bg);color:var(--primary);}
    .tb-link.active{background:var(--primary);color:var(--white);}
    .tb-right{display:flex;align-items:center;gap:14px;}
    .tb-bell{position:relative;width:40px;height:40px;border-radius:10px;border:1px solid var(--bord);background:var(--white);color:var(--ink2);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:.15s;}
    .tb-bell:hover{border-color:var(--primary);color:var(--primary);}
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
    .page-head{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin-bottom:22px;flex-wrap:wrap;}
    .page-head h1{font-size:1.8rem;font-weight:700;margin-bottom:4px;}
    .page-head p{margin:0;color:var(--ink3);font-size:.9rem;}

    /* ══ TABS ══ */
    .tabs{display:flex;gap:4px;background:var(--white);padding:6px;border-radius:12px;border:1px solid var(--bord);margin-bottom:20px;width:max-content;max-width:100%;}
    .tab-btn{padding:9px 16px;border:0;background:transparent;border-radius:9px;font-size:.86rem;font-weight:600;color:var(--ink3);cursor:pointer;transition:.15s;display:inline-flex;align-items:center;gap:8px;white-space:nowrap;}
    .tab-btn:hover{color:var(--primary);}
    .tab-btn.active{background:var(--primary);color:var(--white);}
    .tab-badge{background:var(--accent);color:var(--primary);padding:2px 8px;border-radius:999px;font-size:.7rem;font-weight:800;}
    .tab-btn.active .tab-badge{background:rgba(255,255,255,.2);color:var(--white);}

    .filters{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px;}
    .filters select{padding:9px 34px 9px 14px;border:1px solid var(--bord);border-radius:10px;background:var(--white) url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2364748B' viewBox='0 0 16 16'%3e%3cpath d='M3.204 5h9.592L8 10.481 3.204 5zm-.753.659 4.796 5.48a1 1 0 0 0 1.506 0l4.796-5.48c.566-.647.106-1.659-.753-1.659H3.204a1 1 0 0 0-.753 1.659z'/%3e%3c/svg%3e") no-repeat right 12px center;appearance:none;font-size:.84rem;font-weight:500;color:var(--ink2);cursor:pointer;font-family:inherit;}

    /* ══ CARD GRID ══ */
    .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(360px,1fr));gap:16px;}
    .report-card{background:var(--white);border-radius:14px;box-shadow:var(--shadow);overflow:hidden;display:flex;flex-direction:column;border:1px solid var(--bord-soft);}
    .rc-head{padding:14px 16px;border-bottom:1px solid var(--bord-soft);display:flex;align-items:center;justify-content:space-between;gap:10px;}
    .rc-head .reason{display:inline-flex;align-items:center;gap:6px;font-size:.78rem;font-weight:700;color:var(--red);background:var(--red-soft);padding:4px 10px;border-radius:999px;}
    .rc-head .date{font-size:.74rem;color:var(--ink4);}
    .rc-body{padding:14px 16px;flex:1;display:flex;flex-direction:column;gap:12px;}
    .rc-target{display:flex;gap:12px;align-items:center;padding:10px;background:var(--bg);border-radius:10px;}
    .rc-thumb{width:52px;height:52px;border-radius:10px;overflow:hidden;background:var(--bord-soft);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--ink4);}
    .rc-thumb img{width:100%;height:100%;object-fit:cover;}
    .rc-av{width:44px;height:44px;border-radius:50%;background:var(--primary);color:var(--accent);font-weight:700;display:flex;align-items:center;justify-content:center;font-size:1rem;overflow:hidden;flex-shrink:0;}
    .rc-av img{width:100%;height:100%;object-fit:cover;}
    .rc-target-info{flex:1;min-width:0;}
    .rc-target-info strong{display:block;color:var(--ink);font-size:.9rem;line-height:1.2;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
    .rc-target-info small{font-size:.74rem;color:var(--ink4);}
    .rc-desc{background:#FBFBFC;border:1px solid var(--bord-soft);border-radius:10px;padding:10px 12px;font-size:.84rem;color:var(--ink2);line-height:1.5;max-height:120px;overflow-y:auto;}
    .rc-reporter{display:flex;align-items:center;gap:8px;font-size:.78rem;color:var(--ink3);}
    .rc-reporter i{color:var(--ink4);}
    .rc-evidence{display:block;border-radius:10px;overflow:hidden;border:1px solid var(--bord);position:relative;cursor:zoom-in;}
    .rc-evidence img{display:block;width:100%;max-height:180px;object-fit:cover;}
    .rc-evidence::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,transparent 60%,rgba(0,0,0,.25));pointer-events:none;}
    .rc-foot{padding:12px 16px;border-top:1px solid var(--bord-soft);display:flex;gap:8px;}
    .btn-resolve,.btn-dismiss{flex:1;padding:9px 12px;border-radius:9px;font-size:.8rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:6px;border:1px solid transparent;transition:.15s;}
    .btn-resolve{background:var(--primary);color:var(--white);}
    .btn-resolve:hover{background:var(--primary-2);}
    .btn-dismiss{background:var(--white);color:var(--ink2);border-color:var(--bord);}
    .btn-dismiss:hover{background:var(--bg);border-color:var(--ink3);}
    .btn-delete{background:var(--red);color:var(--white);border-color:var(--red);}
    .btn-delete:hover{background:#C62828;border-color:#C62828;}
    .btn-primary.danger{background:var(--red);}
    .btn-primary.danger:hover{background:#C62828;}
    .rc-status-pill{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:999px;font-size:.72rem;font-weight:700;}
    .rc-status-resolved{background:var(--green-soft);color:var(--primary);}
    .rc-status-dismissed{background:#F1F5F9;color:var(--ink3);}

    .empty{background:var(--white);border-radius:14px;padding:60px 24px;text-align:center;color:var(--ink4);box-shadow:var(--shadow);}
    .empty i{font-size:2.8rem;color:#CBD5E1;display:block;margin-bottom:12px;}
    .empty strong{color:var(--ink2);display:block;margin-bottom:4px;font-size:1rem;}

    .loading{text-align:center;padding:50px 0;color:var(--ink3);}
    .spinner{width:36px;height:36px;border:3px solid var(--bord);border-top-color:var(--primary);border-radius:50%;animation:spin .7s linear infinite;margin:0 auto 12px;}
    @keyframes spin{to{transform:rotate(360deg);}}

    /* ══ MODAL LIGHTBOX ══ */
    .lightbox{position:fixed;inset:0;background:rgba(0,0,0,.85);display:none;align-items:center;justify-content:center;z-index:200;padding:20px;}
    .lightbox.open{display:flex;}
    .lightbox img{max-width:100%;max-height:100%;border-radius:8px;box-shadow:0 10px 40px rgba(0,0,0,.5);}
    .lightbox .close{position:absolute;top:20px;right:24px;width:44px;height:44px;border-radius:50%;background:rgba(255,255,255,.15);color:#fff;border:0;font-size:1.3rem;cursor:pointer;}
    .lightbox .close:hover{background:rgba(255,255,255,.25);}

    /* ══ MODAL RESOLVE ══ */
    .modal-bg{position:fixed;inset:0;background:rgba(11,46,23,.55);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center;z-index:150;padding:16px;}
    .modal-bg.open{display:flex;}
    .modal{background:var(--white);border-radius:16px;width:100%;max-width:460px;box-shadow:var(--shadow-lg);overflow:hidden;}
    .modal h2{padding:22px 24px 4px;font-size:1.15rem;}
    .modal p{padding:0 24px 10px;color:var(--ink3);font-size:.85rem;margin:0;}
    .modal textarea{width:calc(100% - 48px);margin:0 24px;padding:10px 12px;border:1px solid var(--bord);border-radius:10px;font-size:.88rem;font-family:inherit;resize:vertical;min-height:90px;outline:none;background:var(--bg);}
    .modal textarea:focus{background:var(--white);border-color:var(--primary);}
    .modal-actions{padding:18px 24px 22px;display:flex;gap:10px;justify-content:flex-end;}
    .btn-secondary{background:var(--bg);border:1px solid var(--bord);color:var(--ink2);padding:10px 16px;border-radius:10px;font-weight:600;font-size:.85rem;cursor:pointer;}
    .btn-primary{background:var(--primary);color:var(--white);border:0;padding:10px 18px;border-radius:10px;font-weight:600;font-size:.85rem;cursor:pointer;}

    /* ══ TOAST ══ */
    .toast-area{position:fixed;bottom:24px;right:24px;display:flex;flex-direction:column;gap:10px;z-index:300;}
    .toast{background:var(--primary);color:var(--white);padding:13px 18px;border-radius:12px;box-shadow:var(--shadow-lg);font-size:.86rem;font-weight:600;display:flex;align-items:center;gap:10px;min-width:240px;}
    .toast.err{background:var(--red);}

    @media (max-width:720px){
      .topbar{padding:12px 16px;flex-wrap:wrap;gap:12px;}
      .tb-nav{order:3;width:100%;overflow-x:auto;margin:0;}
      .page{padding:20px 16px 40px;}
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
      <a class="tb-link" href="./gestion_usuarios.php"><i class="bi bi-people"></i> Usuarios</a>
      <a class="tb-link" href="./gestion_admins.php"><i class="bi bi-shield-fill-check"></i> Admins</a>
      <a class="tb-link" href="./verificaciones.php"><i class="bi bi-patch-check"></i> Verificaciones</a>
      <a class="tb-link" href="./gestion_productos.php"><i class="bi bi-box-seam"></i> Productos</a>
      <a class="tb-link active" href="./reportes.php"><i class="bi bi-flag-fill"></i> Reportes</a>
      <a class="tb-link" href="./estadisticas.php"><i class="bi bi-bar-chart-line"></i> Estadísticas</a>
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
          <a class="tb-menu__item" href="../perfil.php"><i class="bi bi-person-circle"></i> Mi perfil</a>
          <div class="tb-menu__sep"></div>
          <a class="tb-menu__item danger" href="../../controllers/auth_logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
        </div>
      </div>
    </div>
  </header>

  <div class="page">

    <div class="page-head">
      <div>
        <h1>Reportes</h1>
        <p>Revisa, resuelve o descarta los reportes enviados por la comunidad.</p>
      </div>
    </div>

    <div class="tabs">
      <button class="tab-btn active" data-type="product" onclick="switchTab('product')">
        <i class="bi bi-box-seam"></i> Productos <span class="tab-badge" id="badgeProduct">0</span>
      </button>
      <button class="tab-btn" data-type="user" onclick="switchTab('user')">
        <i class="bi bi-person"></i> Usuarios <span class="tab-badge" id="badgeUser">0</span>
      </button>
    </div>

    <div class="filters">
      <select id="fStatus" onchange="loadReports()">
        <option value="pending">Pendientes</option>
        <option value="resolved">Resueltos</option>
        <option value="dismissed">Descartados</option>
        <option value="all">Todos</option>
      </select>
    </div>

    <div id="content">
      <div class="loading"><div class="spinner"></div> Cargando…</div>
    </div>
  </div>

  <!-- ══ MODAL resolver/descartar ══ -->
  <div class="modal-bg" id="mAction">
    <div class="modal">
      <h2 id="mTitle">Resolver reporte</h2>
      <p id="mSubtitle">Agrega una nota interna sobre la decisión (opcional).</p>
      <textarea id="mNotes" placeholder="Ej. Producto retirado por contener información falsa..."></textarea>
      <div class="modal-actions">
        <button class="btn-secondary" onclick="closeActionModal()">Cancelar</button>
        <button class="btn-primary" id="mSubmit" onclick="submitAction()">Confirmar</button>
      </div>
    </div>
  </div>

  <!-- ══ LIGHTBOX evidencia ══ -->
  <div class="lightbox" id="lightbox" onclick="closeLightbox(event)">
    <button class="close" onclick="closeLightbox(event,true)"><i class="bi bi-x-lg"></i></button>
    <img id="lbImg" alt="">
  </div>

  <div class="toast-area" id="toastArea"></div>

  <script>
    function toggleTbMenu(e){e.stopPropagation();e.currentTarget.closest('.tb-user-wrap').classList.toggle('open');}
    document.addEventListener('click',e=>{if(!e.target.closest('.tb-user-wrap'))document.querySelectorAll('.tb-user-wrap.open').forEach(el=>el.classList.remove('open'));});
  </script>
  <script>
    const API = '../../api/admin/reports.php';
    const UPLOADS = '../../../public/uploads/reportes/';
    const REASON_LABELS = {
      spam: 'Spam',
      fraude: 'Fraude o estafa',
      contenido_ofensivo: 'Contenido ofensivo',
      producto_prohibido: 'Producto prohibido',
      suplantacion: 'Suplantación',
      precio_enganoso: 'Precio engañoso',
      otro: 'Otro',
    };

    let currentType = 'product';
    let actionCtx = null; // { id, type, newStatus }

    document.addEventListener('DOMContentLoaded', () => {
      loadStats();
      loadReports();
      document.addEventListener('keydown', e => {
        if (e.key === 'Escape') { closeLightbox(); closeActionModal(); }
      });
    });

    function switchTab(type) {
      currentType = type;
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.toggle('active', b.dataset.type === type));
      loadReports();
    }

    async function loadStats() {
      try {
        const r = await fetch(`${API}?action=stats`);
        const j = await r.json();
        if (!j.ok) return;
        document.getElementById('badgeProduct').textContent = j.data.product.pending;
        document.getElementById('badgeUser').textContent    = j.data.user.pending;
      } catch (e) { /* silent */ }
    }

    async function loadReports() {
      const status = document.getElementById('fStatus').value;
      const content = document.getElementById('content');
      content.innerHTML = `<div class="loading"><div class="spinner"></div> Cargando…</div>`;
      try {
        const r = await fetch(`${API}?action=list&type=${currentType}&status=${status}`);
        const j = await r.json();
        if (!j.ok) { content.innerHTML = `<div class="empty"><i class="bi bi-exclamation-triangle"></i><strong>${esc(j.error||'Error')}</strong></div>`; return; }
        renderList(j.data);
      } catch (e) {
        content.innerHTML = `<div class="empty"><i class="bi bi-wifi-off"></i><strong>Error de conexión</strong></div>`;
      }
    }

    function renderList(rows) {
      const content = document.getElementById('content');
      if (!rows.length) {
        content.innerHTML = `<div class="empty"><i class="bi bi-clipboard-check"></i><strong>Sin reportes en esta vista</strong><div>Cuando alguien reporte algo, aparecerá aquí.</div></div>`;
        return;
      }
      content.innerHTML = `<div class="grid">${rows.map(r => cardHtml(r)).join('')}</div>`;
    }

    function cardHtml(r) {
      const reasonLbl = REASON_LABELS[r.reason] || r.reason;
      const date = fmtDate(r.created_at);
      const evidence = r.evidence_path
        ? `<div class="rc-evidence" onclick="openLightbox('${UPLOADS}${r.evidence_path}')"><img src="${UPLOADS}${r.evidence_path}" alt="Evidencia"></div>`
        : '';

      let targetBlock = '';
      if (currentType === 'product') {
        const img = r.target_img
          ? `<img src="../../../public/uploads/products/${esc(r.target_img)}" alt="">`
          : `<i class="bi bi-box-seam"></i>`;
        const link = r.product_id ? `../products/detalle.php?id=${r.product_id}` : '#';
        targetBlock = `
          <div class="rc-target">
            <div class="rc-thumb">${img}</div>
            <div class="rc-target-info">
              <strong>${esc(r.target_title || 'Producto #' + r.product_id)}</strong>
              <small>ID #${r.product_id}</small>
            </div>
            <a href="${link}" target="_blank" title="Ver producto" style="color:var(--ink3);"><i class="bi bi-box-arrow-up-right"></i></a>
          </div>`;
      } else {
        const av = r.target_photo
          ? `<img src="../../../public/uploads/avatars/${esc(r.target_photo)}" alt="">`
          : esc((r.target_name||'?').charAt(0).toUpperCase());
        targetBlock = `
          <div class="rc-target">
            <div class="rc-av">${av}</div>
            <div class="rc-target-info">
              <strong>${esc(r.target_name || 'Usuario #' + r.reported_user_id)}</strong>
              <small>${esc(r.target_email || '')}${r.target_status ? ' · '+esc(r.target_status) : ''}</small>
            </div>
            <a href="../gestion_usuarios.php" title="Ver en usuarios" style="color:var(--ink3);"><i class="bi bi-box-arrow-up-right"></i></a>
          </div>`;
      }

      let foot;
      if (r.status === 'pending') {
        const delBtn = currentType === 'product'
          ? `<button class="btn-delete" onclick="askAction(${r.id},'${currentType}','delete_product','${esc(r.target_title || 'este producto')}')" title="Eliminar producto"><i class="bi bi-trash-fill"></i></button>`
          : '';
        foot = `
          <div class="rc-foot">
            <button class="btn-resolve" onclick="askAction(${r.id},'${currentType}','resolve')"><i class="bi bi-check-lg"></i> Resolver</button>
            <button class="btn-dismiss" onclick="askAction(${r.id},'${currentType}','dismiss')"><i class="bi bi-x-lg"></i> Descartar</button>
            ${delBtn}
          </div>`;
      } else {
        const pillCls = r.status === 'resolved' ? 'rc-status-resolved' : 'rc-status-dismissed';
        const pillTxt = r.status === 'resolved' ? 'Resuelto' : 'Descartado';
        foot = `<div class="rc-foot" style="justify-content:flex-start;"><span class="rc-status-pill ${pillCls}"><i class="bi bi-check2-circle"></i> ${pillTxt}${r.reviewed_at ? ' — '+fmtDate(r.reviewed_at) : ''}</span></div>`;
      }

      return `
        <div class="report-card">
          <div class="rc-head">
            <span class="reason"><i class="bi bi-flag-fill"></i> ${esc(reasonLbl)}</span>
            <span class="date">${date}</span>
          </div>
          <div class="rc-body">
            ${targetBlock}
            <div class="rc-desc">${esc(r.description)}</div>
            ${evidence}
            ${r.admin_notes ? `<div class="rc-desc" style="background:var(--yellow-soft);border-color:#FEDB8F;"><strong style="color:#B45309;">Nota admin:</strong> ${esc(r.admin_notes)}</div>` : ''}
            <div class="rc-reporter">
              <i class="bi bi-person-circle"></i>
              Reportado por <strong style="color:var(--ink2);margin-left:4px;">${esc(r.reporter_name || 'Usuario #'+r.reporter_id)}</strong>
            </div>
          </div>
          ${foot}
        </div>`;
    }

    /* ── Resolve / Dismiss / Delete ── */
    function askAction(id, type, kind, targetName) {
      actionCtx = { id, type, kind };
      const submitBtn = document.getElementById('mSubmit');
      submitBtn.classList.remove('danger');

      if (kind === 'delete_product') {
        document.getElementById('mTitle').textContent = 'Eliminar producto';
        document.getElementById('mSubtitle').innerHTML =
          `Se eliminará <strong>«${esc(targetName || '')}»</strong> y todas sus imágenes de forma permanente. Esta acción no se puede deshacer. Agrega una nota interna (opcional).`;
        submitBtn.textContent = 'Eliminar producto';
        submitBtn.classList.add('danger');
      } else {
        document.getElementById('mTitle').textContent = kind === 'resolve' ? 'Resolver reporte' : 'Descartar reporte';
        document.getElementById('mSubtitle').textContent = kind === 'resolve'
          ? 'Se marcará como resuelto. Agrega una nota interna sobre la decisión (opcional).'
          : 'Se descartará sin acción. Puedes agregar una nota explicando por qué (opcional).';
        submitBtn.textContent = kind === 'resolve' ? 'Resolver' : 'Descartar';
      }
      document.getElementById('mNotes').value = '';
      document.getElementById('mAction').classList.add('open');
      setTimeout(() => document.getElementById('mNotes').focus(), 80);
    }
    function closeActionModal() {
      document.getElementById('mAction').classList.remove('open');
      actionCtx = null;
    }
    async function submitAction() {
      if (!actionCtx) return;
      const { id, type, kind } = actionCtx;
      const notes = document.getElementById('mNotes').value.trim();
      const btn = document.getElementById('mSubmit');
      const originalTxt = btn.textContent;
      btn.disabled = true; btn.textContent = kind === 'delete_product' ? 'Eliminando…' : 'Guardando…';
      try {
        const r = await fetch(`${API}?action=${kind}`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id, type, notes }),
        });
        const j = await r.json();
        if (j.ok) {
          toast(j.message || 'Listo');
          closeActionModal();
          loadStats();
          loadReports();
        } else toast(j.error || 'Error', true);
      } catch (e) { toast('Error de red', true); }
      btn.disabled = false;
      btn.textContent = originalTxt;
    }

    /* ── Lightbox ── */
    function openLightbox(src) {
      document.getElementById('lbImg').src = src;
      document.getElementById('lightbox').classList.add('open');
    }
    function closeLightbox(e, force) {
      if (force || !e || e.target.id === 'lightbox' || e.target.classList.contains('close') || e.target.closest('.close')) {
        document.getElementById('lightbox').classList.remove('open');
      }
    }

    /* ── Helpers ── */
    function fmtDate(s) {
      if (!s) return '—';
      const d = new Date(s.replace(' ','T'));
      if (isNaN(d)) return s;
      return d.toLocaleDateString('es-CO', { day:'2-digit', month:'short', year:'numeric' })
           + ' ' + d.toLocaleTimeString('es-CO', { hour:'2-digit', minute:'2-digit' });
    }
    function esc(v) { return String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    function toast(msg, isErr=false) {
      const el = document.createElement('div');
      el.className = 'toast' + (isErr ? ' err' : '');
      el.innerHTML = `<i class="bi ${isErr?'bi-x-circle-fill':'bi-check-circle-fill'}"></i> ${esc(msg)}`;
      document.getElementById('toastArea').appendChild(el);
      setTimeout(() => el.remove(), 3800);
    }
  </script>
</body>
</html>

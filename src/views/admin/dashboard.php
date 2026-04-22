<?php
/**
 * Admin Dashboard — ComercioLocal
 * Diseño inspirado en SaaS de gestión, paleta verde oscuro + amarillo.
 */
require_once('../../config/conexion.php');
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit();
}
$role = $_SESSION['user']['role'] ?? '';
if ($role !== 'admin' && $role !== 'super_admin') {
    header("Location: ../home.php");
    exit();
}

$user         = $_SESSION['user'];
$userInitial  = strtoupper(mb_substr($user['full_name'] ?? 'A', 0, 1));
$userName     = $user['full_name'] ?? 'Administrador';
$isSuperAdmin = ($role === 'super_admin');

function tbl_exists(mysqli $c, string $t): bool {
    $r = @mysqli_query($c, "SHOW TABLES LIKE '" . mysqli_real_escape_string($c, $t) . "'");
    return ($r && mysqli_num_rows($r) > 0);
}

/* ══════════════════════════════════════════════ MÉTRICAS ══════════════════════════════════════════════ */

// 1) Usuarios totales (excluye admins y soft-deleted)
$total_users = 0;
$r = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role NOT IN ('admin','super_admin') AND deleted_at IS NULL");
if ($r) $total_users = (int) mysqli_fetch_assoc($r)['c'];

// 2) Productos activos
$active_products = 0;
$r = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM products WHERE status = 'disponible'");
if ($r) $active_products = (int) mysqli_fetch_assoc($r)['c'];

// 3) Verificaciones pendientes
$pending_verif = 0;
if (tbl_exists($conn, 'seller_verifications')) {
    $r = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM seller_verifications WHERE status = 'pending'");
    if ($r) $pending_verif = (int) mysqli_fetch_assoc($r)['c'];
}

// 4) Ingresos por promociones este mes (COP)
$promo_revenue = 0;
if (tbl_exists($conn, 'product_promotions')) {
    $r = @mysqli_query($conn, "
        SELECT COALESCE(SUM(price), 0) AS total
        FROM product_promotions
        WHERE MONTH(created_at) = MONTH(NOW())
          AND YEAR(created_at)  = YEAR(NOW())
          AND status IN ('active','expired')
    ");
    if ($r) $promo_revenue = (float) mysqli_fetch_assoc($r)['total'];
}

/* ══════════════════════════════════════════════ VERIFICACIONES: LISTA + DONA ══════════════════════════════════════════════ */

$pending_list = [];
$verif_counts = ['approved' => 0, 'pending' => 0, 'rejected' => 0];

if (tbl_exists($conn, 'seller_verifications')) {
    $r = @mysqli_query($conn, "
        SELECT v.id, v.user_id, v.verification_type, v.document_type, v.document_path,
               v.created_at, u.full_name, u.email, u.profile_photo
        FROM seller_verifications v
        LEFT JOIN users u ON u.id = v.user_id
        WHERE v.status = 'pending'
        ORDER BY v.created_at ASC
        LIMIT 12
    ");
    if ($r) while ($row = mysqli_fetch_assoc($r)) $pending_list[] = $row;

    $r = @mysqli_query($conn, "SELECT status, COUNT(*) AS c FROM seller_verifications GROUP BY status");
    if ($r) while ($row = mysqli_fetch_assoc($r)) {
        if (isset($verif_counts[$row['status']])) $verif_counts[$row['status']] = (int) $row['c'];
    }
}

/* ══════════════════════════════════════════════ REPORTES DE PRODUCTOS ══════════════════════════════════════════════ */

$reports_list = [];
$reports_module_ok = false;
foreach (['product_reports','reports','product_flags'] as $rtbl) {
    if (tbl_exists($conn, $rtbl)) {
        $reports_module_ok = true;
        $r = @mysqli_query($conn, "
            SELECT r.id, r.product_id, r.reason, r.created_at,
                   p.title AS product_title,
                   rp.full_name AS reporter_name
            FROM `$rtbl` r
            LEFT JOIN products p ON p.id = r.product_id
            LEFT JOIN users rp   ON rp.id = r.reporter_id
            ORDER BY r.created_at DESC
            LIMIT 5
        ");
        if ($r) while ($row = mysqli_fetch_assoc($r)) $reports_list[] = $row;
        break;
    }
}

// Precargar JSON para Chart.js (dona)
$donut_json = json_encode([
    (int) $verif_counts['approved'],
    (int) $verif_counts['pending'],
    (int) $verif_counts['rejected'],
]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de administración — ComercioLocal</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Syne:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" defer></script>

  <style>
    :root {
      --primary:  #0B2E17;
      --primary-2:#123D20;
      --accent:   #F5A81C;
      --accent-2: #FFC154;
      --red:      #E53935;
      --red-soft: #FEECEB;
      --green-soft:#E6F3EB;
      --yellow-soft:#FFF3D6;
      --bg:       #F8F9FA;
      --white:    #FFFFFF;
      --ink:      #0F172A;
      --ink2:     #334155;
      --ink3:     #64748B;
      --ink4:     #94A3B8;
      --bord:     #E5E7EB;
      --bord-soft:#EEF0F3;
      --shadow:   0 2px 12px rgba(0,0,0,.06);
      --shadow-lg:0 8px 28px rgba(0,0,0,.08);
    }

    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    body {
      background: var(--bg);
      color: var(--ink);
      font-family: 'DM Sans', system-ui, -apple-system, sans-serif;
      font-size: 14px;
      line-height: 1.45;
    }
    h1, h2, h3, h4 {
      font-family: 'Syne', 'DM Sans', sans-serif;
      color: var(--primary);
      letter-spacing: -.01em;
      margin: 0;
    }
    a { color: inherit; text-decoration: none; }

    /* ══ TOPBAR ══ */
    .topbar {
      background: var(--white);
      border-bottom: 1px solid var(--bord);
      padding: 14px 32px;
      display: flex;
      align-items: center;
      gap: 32px;
      position: sticky;
      top: 0;
      z-index: 50;
    }
    .tb-logo {
      font-family: 'Syne', sans-serif;
      font-weight: 800;
      font-size: 1.15rem;
      color: var(--primary);
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .tb-logo-mark {
      width: 32px; height: 32px;
      border-radius: 9px;
      background: var(--primary);
      color: var(--accent);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: .85rem;
    }
    .tb-nav {
      display: flex;
      gap: 4px;
      margin: 0 auto;
    }
    .tb-link {
      padding: 8px 14px;
      border-radius: 10px;
      font-weight: 500;
      font-size: .88rem;
      color: var(--ink2);
      transition: all .15s;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .tb-link:hover { background: var(--bg); color: var(--primary); }
    .tb-link.active {
      background: var(--primary);
      color: var(--white);
    }
    .tb-right { display: flex; align-items: center; gap: 14px; }

    .tb-bell {
      position: relative;
      width: 40px; height: 40px;
      border-radius: 10px;
      border: 1px solid var(--bord);
      background: var(--white);
      color: var(--ink2);
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: .15s;
    }
    .tb-bell:hover { border-color: var(--primary); color: var(--primary); }
    .tb-bell__badge {
      position: absolute;
      top: -4px; right: -4px;
      background: var(--accent);
      color: var(--primary);
      font-size: .65rem;
      font-weight: 800;
      min-width: 18px;
      height: 18px;
      padding: 0 5px;
      border-radius: 9px;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 2px solid var(--white);
    }

    .tb-user-wrap { position: relative; }
    .tb-user {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 4px 12px 4px 4px;
      border-radius: 10px;
      border: 1px solid var(--bord);
      background: var(--white);
      cursor: pointer;
      font-family: inherit;
      transition: border-color .15s, box-shadow .15s;
    }
    .tb-user:hover { border-color: var(--primary); }
    .tb-user[aria-expanded="true"] {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(11,46,23,.08);
    }
    .tb-user__av {
      width: 34px; height: 34px;
      border-radius: 8px;
      background: var(--primary);
      color: var(--accent);
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: .88rem;
    }
    .tb-user__info { text-align: left; }
    .tb-user__info strong {
      font-size: .85rem;
      color: var(--ink);
      display: block;
      line-height: 1.1;
    }
    .tb-user__info span {
      font-size: .7rem;
      color: var(--ink4);
    }
    .tb-user__caret {
      font-size: .7rem;
      color: var(--ink3);
      transition: transform .2s;
    }
    .tb-user[aria-expanded="true"] .tb-user__caret { transform: rotate(180deg); }

    .tb-dropdown {
      position: absolute;
      top: calc(100% + 8px);
      right: 0;
      min-width: 240px;
      background: var(--white);
      border: 1px solid var(--bord);
      border-radius: 12px;
      box-shadow: var(--shadow-lg);
      padding: 6px;
      opacity: 0;
      visibility: hidden;
      transform: translateY(-6px);
      transition: opacity .18s, transform .18s, visibility .18s;
      z-index: 60;
    }
    .tb-dropdown.show {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }
    .tb-dd-head {
      padding: 12px 12px 10px;
      border-bottom: 1px solid var(--bord-soft);
      margin-bottom: 6px;
    }
    .tb-dd-name {
      font-weight: 700;
      font-size: .88rem;
      color: var(--ink);
      line-height: 1.2;
    }
    .tb-dd-mail {
      font-size: .74rem;
      color: var(--ink3);
      margin-top: 2px;
      word-break: break-all;
    }
    .tb-dd-link {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 9px 12px;
      font-size: .85rem;
      color: var(--ink2);
      border-radius: 8px;
      transition: background .12s, color .12s;
    }
    .tb-dd-link:hover { background: var(--bg); color: var(--primary); }
    .tb-dd-link i { font-size: 1rem; color: var(--ink3); }
    .tb-dd-link:hover i { color: var(--primary); }
    .tb-dd-sep {
      height: 1px;
      background: var(--bord-soft);
      margin: 6px 4px;
    }
    .tb-dd-logout {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 9px 12px;
      font-size: .85rem;
      color: var(--red);
      font-weight: 600;
      border-radius: 8px;
      transition: background .12s;
    }
    .tb-dd-logout:hover { background: var(--red-soft); }
    .tb-dd-logout i { font-size: 1rem; }

    /* ══ PAGE ══ */
    .page {
      max-width: 1320px;
      margin: 0 auto;
      padding: 28px 32px 48px;
    }
    .page-head {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      gap: 20px;
      margin-bottom: 24px;
      flex-wrap: wrap;
    }
    .page-head h1 {
      font-size: 1.6rem;
      font-weight: 700;
      margin-bottom: 4px;
    }
    .page-head p {
      margin: 0;
      color: var(--ink3);
      font-size: .88rem;
    }
    .page-date {
      font-size: .8rem;
      color: var(--ink3);
      background: var(--white);
      border: 1px solid var(--bord);
      padding: 8px 14px;
      border-radius: 10px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    /* ══ METRIC CARDS ══ */
    .metrics {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
      margin-bottom: 24px;
    }
    .metric {
      background: var(--white);
      border-radius: 16px;
      padding: 22px 22px;
      box-shadow: var(--shadow);
      position: relative;
      overflow: hidden;
    }
    .metric-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 14px;
    }
    .metric-icon {
      width: 44px; height: 44px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.15rem;
      background: var(--green-soft);
      color: var(--primary);
    }
    .metric--products .metric-icon { background: #EEF5FF; color: #1E40AF; }
    .metric--verif    .metric-icon { background: var(--yellow-soft); color: #B45309; }
    .metric--revenue  .metric-icon { background: var(--green-soft); color: var(--primary); }

    .metric-alert {
      background: var(--accent);
      color: var(--primary);
      font-size: .7rem;
      font-weight: 700;
      padding: 4px 10px;
      border-radius: 999px;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }
    .metric-num {
      font-family: 'Syne', sans-serif;
      font-size: 2rem;
      font-weight: 800;
      color: var(--primary);
      line-height: 1;
      margin-bottom: 6px;
    }
    .metric-label {
      font-size: .83rem;
      color: var(--ink3);
      font-weight: 500;
    }

    /* ══ GRID CENTER ══ */
    .grid-center {
      display: grid;
      grid-template-columns: minmax(0, 1.55fr) minmax(0, 1fr);
      gap: 20px;
      align-items: start;
    }

    .card {
      background: var(--white);
      border-radius: 16px;
      box-shadow: var(--shadow);
      overflow: hidden;
    }
    .card-head {
      padding: 20px 24px 12px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      flex-wrap: wrap;
    }
    .card-head h2 {
      font-size: 1.05rem;
      font-weight: 700;
    }
    .card-head-sub {
      font-size: .78rem;
      color: var(--ink3);
      margin-top: 2px;
    }
    .card-search {
      position: relative;
      min-width: 220px;
    }
    .card-search input {
      width: 100%;
      padding: 8px 12px 8px 34px;
      border: 1px solid var(--bord);
      border-radius: 10px;
      font-size: .82rem;
      font-family: inherit;
      outline: none;
      transition: border-color .15s;
    }
    .card-search input:focus { border-color: var(--primary); }
    .card-search i {
      position: absolute;
      left: 11px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--ink4);
      font-size: .95rem;
    }

    /* ══ TABLE ══ */
    .verif-table {
      width: 100%;
      border-collapse: collapse;
      font-size: .84rem;
    }
    .verif-table thead th {
      text-align: left;
      font-weight: 600;
      color: var(--ink3);
      font-size: .72rem;
      text-transform: uppercase;
      letter-spacing: .05em;
      padding: 12px 18px;
      background: var(--bg);
      border-bottom: 1px solid var(--bord);
    }
    .verif-table tbody tr { transition: background .15s; }
    .verif-table tbody tr:nth-child(even) { background: var(--bg); }
    .verif-table tbody tr:hover { background: #EEF5EF; }
    .verif-table tbody td {
      padding: 14px 18px;
      border-bottom: 1px solid var(--bord-soft);
      vertical-align: middle;
    }
    .verif-table tbody tr:last-child td { border-bottom: none; }

    .doc-thumb {
      width: 46px; height: 46px;
      border-radius: 10px;
      background: var(--bg);
      border: 1px solid var(--bord);
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .doc-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .doc-thumb .bi { color: var(--ink4); font-size: 1.15rem; }

    .seller-cell {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .seller-name { font-weight: 600; color: var(--ink); }
    .seller-email { font-size: .74rem; color: var(--ink4); }

    .status-pill {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 3px 10px;
      border-radius: 999px;
      font-size: .72rem;
      font-weight: 600;
    }
    .status-pending  { background: var(--yellow-soft); color: #B45309; }
    .status-approved { background: var(--green-soft);  color: var(--primary); }
    .status-rejected { background: var(--red-soft);    color: var(--red); }

    .btn {
      padding: 7px 12px;
      font-size: .78rem;
      font-weight: 600;
      border-radius: 8px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-family: inherit;
      transition: all .15s;
      border: 1px solid transparent;
    }
    .btn-approve {
      background: var(--primary);
      color: var(--white);
    }
    .btn-approve:hover { background: var(--primary-2); }
    .btn-reject {
      background: var(--white);
      color: var(--red);
      border-color: #FCA5A5;
    }
    .btn-reject:hover { background: var(--red-soft); }
    .btn:disabled { opacity: .5; cursor: not-allowed; }

    .actions-cell { display: flex; gap: 6px; justify-content: flex-end; }

    .table-empty {
      padding: 48px 24px;
      text-align: center;
      color: var(--ink4);
    }
    .table-empty i { font-size: 2.4rem; display: block; margin-bottom: 10px; color: #CBD5E1; }

    /* ══ RIGHT WIDGETS ══ */
    .widgets {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .donut-body {
      padding: 10px 24px 24px;
      display: grid;
      grid-template-columns: 160px 1fr;
      gap: 20px;
      align-items: center;
    }
    .donut-canvas-wrap { position: relative; }
    .donut-center {
      position: absolute;
      inset: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      pointer-events: none;
    }
    .donut-center strong {
      font-family: 'Syne', sans-serif;
      font-size: 1.6rem;
      color: var(--primary);
      font-weight: 800;
      line-height: 1;
    }
    .donut-center span { font-size: .7rem; color: var(--ink3); }

    .donut-legend { display: flex; flex-direction: column; gap: 12px; }
    .legend-item {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: .82rem;
    }
    .legend-dot {
      width: 10px; height: 10px;
      border-radius: 3px;
      flex-shrink: 0;
    }
    .legend-label { color: var(--ink2); flex: 1; }
    .legend-value { font-weight: 700; color: var(--ink); }

    /* ══ REPORTS LIST ══ */
    .reports-list {
      padding: 4px 8px 14px;
    }
    .report-item {
      padding: 12px 16px;
      display: flex;
      align-items: center;
      gap: 12px;
      border-radius: 10px;
      transition: background .15s;
    }
    .report-item:hover { background: var(--bg); }
    .report-info { flex: 1; min-width: 0; }
    .report-title {
      font-weight: 600;
      color: var(--ink);
      font-size: .88rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .report-meta {
      font-size: .74rem;
      color: var(--ink3);
      margin-top: 2px;
    }
    .report-reason {
      display: inline-flex;
      align-items: center;
      gap: 3px;
      background: var(--red-soft);
      color: var(--red);
      font-size: .7rem;
      font-weight: 600;
      padding: 2px 8px;
      border-radius: 6px;
      margin-right: 6px;
    }
    .report-actions { display: flex; gap: 4px; }
    .ico-btn {
      width: 32px; height: 32px;
      border-radius: 8px;
      border: 1px solid var(--bord);
      background: var(--white);
      color: var(--ink2);
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: .15s;
      font-size: .88rem;
    }
    .ico-btn:hover { border-color: var(--primary); color: var(--primary); }
    .ico-btn.danger:hover { border-color: var(--red); color: var(--red); }

    /* ══ TOAST ══ */
    .toast {
      position: fixed;
      bottom: 24px;
      right: 24px;
      padding: 14px 20px;
      background: var(--primary);
      color: var(--white);
      border-radius: 12px;
      font-weight: 600;
      font-size: .88rem;
      box-shadow: var(--shadow-lg);
      display: flex;
      align-items: center;
      gap: 10px;
      opacity: 0;
      transform: translateY(12px);
      transition: all .25s;
      z-index: 100;
    }
    .toast.show { opacity: 1; transform: translateY(0); }
    .toast.err { background: var(--red); }

    /* Responsive */
    @media (max-width: 1100px) {
      .metrics { grid-template-columns: repeat(2, 1fr); }
      .grid-center { grid-template-columns: 1fr; }
    }
    @media (max-width: 720px) {
      .topbar { padding: 12px 16px; flex-wrap: wrap; gap: 12px; }
      .tb-nav { order: 3; width: 100%; overflow-x: auto; margin: 0; }
      .page { padding: 20px 16px 40px; }
      .metrics { grid-template-columns: 1fr; }
      .donut-body { grid-template-columns: 1fr; text-align: center; }
      .tb-user__info { display: none; }
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
      <a class="tb-link active" href="./dashboard.php"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
      <a class="tb-link" href="./gestion_usuarios.php"><i class="bi bi-people"></i> Usuarios</a>
      <a class="tb-link" href="./gestion_admins.php"><i class="bi bi-shield-fill-check"></i> Administradores</a>
      <a class="tb-link" href="./verificaciones.php"><i class="bi bi-patch-check"></i> Verificaciones</a>
      <a class="tb-link" href="./gestion_productos.php"><i class="bi bi-box-seam"></i> Productos</a>
      <a class="tb-link" href="./estadisticas.php"><i class="bi bi-bar-chart-line"></i> Estadísticas</a>
      <a class="tb-link" href="./reportes.php"><i class="bi bi-flag-fill"></i> Reportes</a>
    </nav>

    <div class="tb-right">
      <button class="tb-bell" title="Notificaciones">
        <i class="bi bi-bell-fill"></i>
        <?php if ($pending_verif > 0): ?>
          <span class="tb-bell__badge"><?= $pending_verif > 9 ? '9+' : $pending_verif ?></span>
        <?php endif; ?>
      </button>

      <div class="tb-user-wrap" id="tbUserWrap">
        <button type="button" class="tb-user" id="tbUserBtn" aria-haspopup="true" aria-expanded="false" aria-controls="tbDropdown">
          <span class="tb-user__av"><?= htmlspecialchars($userInitial) ?></span>
          <span class="tb-user__info">
            <strong><?= htmlspecialchars($userName) ?></strong>
            <span><?= $isSuperAdmin ? 'Super Administrador' : 'Administrador' ?></span>
          </span>
          <i class="bi bi-chevron-down tb-user__caret" aria-hidden="true"></i>
        </button>

        <div class="tb-dropdown" id="tbDropdown" role="menu" aria-label="Menú de usuario">
          <div class="tb-dd-head">
            <div class="tb-dd-name"><?= htmlspecialchars($user['full_name'] ?? 'Administrador') ?></div>
            <?php if (!empty($user['email'])): ?>
              <div class="tb-dd-mail"><?= htmlspecialchars($user['email']) ?></div>
            <?php endif; ?>
          </div>
          <a class="tb-dd-link" href="../home.php" role="menuitem">
            <i class="bi bi-house-door"></i> Ir al sitio
          </a>
          <a class="tb-dd-link" href="../perfil.php" role="menuitem">
            <i class="bi bi-person-circle"></i> Mi perfil
          </a>
          <div class="tb-dd-sep" role="separator"></div>
          <a class="tb-dd-logout" href="../../controllers/auth_logout.php" role="menuitem">
            <i class="bi bi-box-arrow-right"></i> Cerrar sesión
          </a>
        </div>
      </div>
    </div>
  </header>

  <!-- ══ PAGE ══ -->
  <div class="page">

    <div class="page-head">
      <div>
        <h1>Dashboard</h1>
        <p>Resumen general de la plataforma.</p>
      </div>
      <div class="page-date">
        <i class="bi bi-calendar3"></i>
        <?php
          setlocale(LC_TIME, 'es_CO.UTF-8', 'es_ES.UTF-8', 'es_CO', 'es_ES', 'Spanish');
          echo date('d / m / Y');
        ?>
      </div>
    </div>

    <!-- ══ MÉTRICAS ══ -->
    <div class="metrics">

      <div class="metric metric--users">
        <div class="metric-top">
          <div class="metric-icon"><i class="bi bi-people-fill"></i></div>
        </div>
        <div class="metric-num"><?= number_format($total_users, 0, ',', '.') ?></div>
        <div class="metric-label">Usuarios registrados</div>
      </div>

      <div class="metric metric--products">
        <div class="metric-top">
          <div class="metric-icon"><i class="bi bi-box-seam-fill"></i></div>
        </div>
        <div class="metric-num"><?= number_format($active_products, 0, ',', '.') ?></div>
        <div class="metric-label">Productos activos</div>
      </div>

      <div class="metric metric--verif">
        <div class="metric-top">
          <div class="metric-icon"><i class="bi bi-shield-fill-check"></i></div>
          <?php if ($pending_verif > 0): ?>
            <span class="metric-alert">
              <i class="bi bi-exclamation-circle-fill"></i>
              <?= $pending_verif ?> pendiente<?= $pending_verif !== 1 ? 's' : '' ?>
            </span>
          <?php endif; ?>
        </div>
        <div class="metric-num"><?= number_format($pending_verif, 0, ',', '.') ?></div>
        <div class="metric-label">Verificaciones pendientes</div>
      </div>

      <div class="metric metric--revenue">
        <div class="metric-top">
          <div class="metric-icon"><i class="bi bi-rocket-takeoff-fill"></i></div>
        </div>
        <div class="metric-num">$<?= number_format($promo_revenue, 0, ',', '.') ?></div>
        <div class="metric-label">Ingresos por promociones (mes)</div>
      </div>

    </div>

    <!-- ══ GRID CENTRAL ══ -->
    <div class="grid-center">

      <!-- ── TABLA VERIFICACIONES PENDIENTES ── -->
      <div class="card">
        <div class="card-head">
          <div>
            <h2>Verificaciones pendientes</h2>
            <div class="card-head-sub" id="pendingCount">
              <?= count($pending_list) ?> pendiente<?= count($pending_list) !== 1 ? 's' : '' ?> de revisión
            </div>
          </div>
          <div class="card-search">
            <i class="bi bi-search"></i>
            <input type="text" id="verifSearch" placeholder="Buscar vendedor o documento...">
          </div>
        </div>

        <?php if (empty($pending_list)): ?>
          <div class="table-empty">
            <i class="bi bi-patch-check"></i>
            <strong style="color:var(--ink2);">Sin solicitudes pendientes</strong>
            <div style="margin-top:4px;font-size:.85rem;">Todas las verificaciones están al día.</div>
          </div>
        <?php else: ?>
          <table class="verif-table" id="verifTable">
            <thead>
              <tr>
                <th style="width:72px;">Documento</th>
                <th>Vendedor</th>
                <th>Tipo</th>
                <th>Fecha</th>
                <th style="text-align:right;">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pending_list as $v):
                $ini = strtoupper(mb_substr($v['full_name'] ?? 'V', 0, 1));
                $docLabel = $v['document_type'] ?: ($v['verification_type'] === 'persona' ? 'Identificación' : 'Documento negocio');
                $docImg   = !empty($v['document_path'])
                  ? '../../../public/uploads/verificaciones/' . htmlspecialchars($v['document_path'])
                  : '';
                $date = date('d M Y', strtotime($v['created_at']));
              ?>
                <tr data-row-id="<?= (int) $v['id'] ?>" data-search="<?= strtolower(htmlspecialchars(($v['full_name'] ?? '') . ' ' . ($v['email'] ?? '') . ' ' . $docLabel)) ?>">
                  <td>
                    <div class="doc-thumb">
                      <?php if ($docImg): ?>
                        <img src="<?= $docImg ?>" alt="Documento">
                      <?php else: ?>
                        <i class="bi bi-file-earmark-text"></i>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td>
                    <div class="seller-cell">
                      <div>
                        <div class="seller-name"><?= htmlspecialchars($v['full_name'] ?? 'Usuario #' . $v['user_id']) ?></div>
                        <div class="seller-email"><?= htmlspecialchars($v['email'] ?? '') ?></div>
                      </div>
                    </div>
                  </td>
                  <td><span style="color:var(--ink2);"><?= htmlspecialchars($docLabel) ?></span></td>
                  <td><span style="color:var(--ink3);"><?= $date ?></span></td>
                  <td class="actions-cell">
                    <button class="btn btn-approve" onclick="verifAction(this, <?= (int) $v['id'] ?>, 'approve')">
                      <i class="bi bi-check-lg"></i> Aprobar
                    </button>
                    <button class="btn btn-reject" onclick="verifAction(this, <?= (int) $v['id'] ?>, 'reject')">
                      <i class="bi bi-x-lg"></i> Rechazar
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>

      <!-- ── WIDGETS DERECHA ── -->
      <div class="widgets">

        <!-- Estado de verificaciones (dona) -->
        <div class="card">
          <div class="card-head">
            <div>
              <h2>Estado de verificaciones</h2>
              <div class="card-head-sub">Desglose general.</div>
            </div>
          </div>
          <div class="donut-body">
            <?php $donut_total = array_sum($verif_counts); ?>
            <div class="donut-canvas-wrap">
              <canvas id="donutChart" width="160" height="160"></canvas>
              <div class="donut-center">
                <strong><?= $donut_total ?></strong>
                <span>Total</span>
              </div>
            </div>
            <div class="donut-legend">
              <div class="legend-item">
                <span class="legend-dot" style="background:#0B2E17;"></span>
                <span class="legend-label">Aprobadas</span>
                <span class="legend-value"><?= $verif_counts['approved'] ?></span>
              </div>
              <div class="legend-item">
                <span class="legend-dot" style="background:#F5A81C;"></span>
                <span class="legend-label">Pendientes</span>
                <span class="legend-value"><?= $verif_counts['pending'] ?></span>
              </div>
              <div class="legend-item">
                <span class="legend-dot" style="background:#E53935;"></span>
                <span class="legend-label">Rechazadas</span>
                <span class="legend-value"><?= $verif_counts['rejected'] ?></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Productos reportados -->
        <div class="card">
          <div class="card-head">
            <div>
              <h2>Productos reportados</h2>
              <div class="card-head-sub">Últimos 5 reportes.</div>
            </div>
            <?php if (!empty($reports_list)): ?>
              <span class="status-pill status-rejected"><?= count($reports_list) ?> activos</span>
            <?php endif; ?>
          </div>

          <?php if (empty($reports_list)): ?>
            <div class="table-empty" style="padding:32px 20px;">
              <i class="bi bi-flag"></i>
              <strong style="color:var(--ink2);">Sin reportes recientes</strong>
              <div style="margin-top:4px;font-size:.82rem;">
                <?= $reports_module_ok ? 'No hay productos reportados.' : 'Módulo de reportes no configurado.' ?>
              </div>
            </div>
          <?php else: ?>
            <div class="reports-list">
              <?php foreach ($reports_list as $rep): ?>
                <div class="report-item">
                  <div class="report-info">
                    <div class="report-title"><?= htmlspecialchars($rep['product_title'] ?? 'Producto #' . $rep['product_id']) ?></div>
                    <div class="report-meta">
                      <span class="report-reason"><i class="bi bi-flag-fill"></i> <?= htmlspecialchars($rep['reason'] ?? 'Sin motivo') ?></span>
                      <?= htmlspecialchars($rep['reporter_name'] ?? 'Anónimo') ?>
                    </div>
                  </div>
                  <div class="report-actions">
                    <a href="../products/detalle.php?id=<?= (int) $rep['product_id'] ?>" class="ico-btn" title="Ver producto">
                      <i class="bi bi-eye"></i>
                    </a>
                    <button class="ico-btn danger" title="Eliminar producto" onclick="alert('Aún no implementado');">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

      </div>
    </div>

  </div>

  <div class="toast" id="toast"><i class="bi bi-check-circle-fill"></i><span id="toastMsg">Listo</span></div>

  <script>
    // ══ User dropdown ══
    (() => {
      const wrap = document.getElementById('tbUserWrap');
      const btn  = document.getElementById('tbUserBtn');
      const dd   = document.getElementById('tbDropdown');
      if (!wrap || !btn || !dd) return;

      const close = () => {
        dd.classList.remove('show');
        btn.setAttribute('aria-expanded', 'false');
      };
      const open = () => {
        dd.classList.add('show');
        btn.setAttribute('aria-expanded', 'true');
      };

      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        dd.classList.contains('show') ? close() : open();
      });
      document.addEventListener('click', (e) => {
        if (!wrap.contains(e.target)) close();
      });
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
      });
    })();

    // ══ Dona: estado de verificaciones ══
    window.addEventListener('load', () => {
      const data = <?= $donut_json ?>;
      const total = data.reduce((a, b) => a + b, 0);
      const ctx = document.getElementById('donutChart');
      if (ctx && total > 0 && window.Chart) {
        new Chart(ctx, {
          type: 'doughnut',
          data: {
            labels: ['Aprobadas', 'Pendientes', 'Rechazadas'],
            datasets: [{
              data,
              backgroundColor: ['#0B2E17', '#F5A81C', '#E53935'],
              borderWidth: 3,
              borderColor: '#fff',
              hoverOffset: 6,
            }],
          },
          options: {
            cutout: '70%',
            responsive: false,
            plugins: { legend: { display: false } },
          },
        });
      }
    });

    // ══ Búsqueda en tabla ══
    const search = document.getElementById('verifSearch');
    if (search) {
      search.addEventListener('input', () => {
        const q = search.value.trim().toLowerCase();
        const rows = document.querySelectorAll('#verifTable tbody tr');
        let visible = 0;
        rows.forEach(r => {
          const match = !q || (r.dataset.search || '').includes(q);
          r.style.display = match ? '' : 'none';
          if (match) visible++;
        });
        const counter = document.getElementById('pendingCount');
        if (counter) counter.textContent = `${visible} pendiente${visible !== 1 ? 's' : ''} de revisión`;
      });
    }

    // ══ Toast ══
    function showToast(msg, isErr = false) {
      const el = document.getElementById('toast');
      el.classList.toggle('err', isErr);
      el.querySelector('i').className = isErr
        ? 'bi bi-exclamation-circle-fill'
        : 'bi bi-check-circle-fill';
      document.getElementById('toastMsg').textContent = msg;
      el.classList.add('show');
      setTimeout(() => el.classList.remove('show'), 2600);
    }

    // ══ Aprobar / Rechazar ══
    async function verifAction(btn, id, action) {
      const row = btn.closest('tr');
      if (action === 'reject') {
        const reason = prompt('Motivo del rechazo (opcional):') ?? '';
        if (reason === null) return;
        await sendVerifAction(btn, id, action, reason, row);
      } else {
        await sendVerifAction(btn, id, action, '', row);
      }
    }

    async function sendVerifAction(btn, id, action, reason, row) {
      const buttons = row.querySelectorAll('button');
      buttons.forEach(b => b.disabled = true);
      btn.innerHTML = '<i class="bi bi-hourglass-split"></i> ...';

      try {
        const res = await fetch('../../api/verificacion.php?action=' + action, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id, reason }),
        });
        const data = await res.json();
        if (data.ok) {
          showToast(action === 'approve' ? 'Solicitud aprobada.' : 'Solicitud rechazada.');
          row.style.transition = 'opacity .3s';
          row.style.opacity = '0';
          setTimeout(() => {
            row.remove();
            const remaining = document.querySelectorAll('#verifTable tbody tr').length;
            const counter = document.getElementById('pendingCount');
            if (counter) counter.textContent = `${remaining} pendiente${remaining !== 1 ? 's' : ''} de revisión`;
            if (remaining === 0) setTimeout(() => location.reload(), 700);
          }, 300);
        } else {
          showToast(data.error || 'Error al procesar.', true);
          buttons.forEach(b => b.disabled = false);
          btn.innerHTML = action === 'approve'
            ? '<i class="bi bi-check-lg"></i> Aprobar'
            : '<i class="bi bi-x-lg"></i> Rechazar';
        }
      } catch (e) {
        showToast('Error de conexión.', true);
        buttons.forEach(b => b.disabled = false);
        btn.innerHTML = action === 'approve'
          ? '<i class="bi bi-check-lg"></i> Aprobar'
          : '<i class="bi bi-x-lg"></i> Rechazar';
      }
    }
  </script>

</body>
</html>

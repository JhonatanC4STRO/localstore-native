<?php
/**
 * Estadísticas — ComercioLocal Admin
 * Analíticas de plataforma: crecimiento de usuarios, productos, ingresos y verificaciones.
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

/* ══════ RANGO DE TIEMPO ══════ */
$range   = $_GET['range'] ?? '30d';
$ranges  = ['7d' => 7, '30d' => 30, '90d' => 90, '365d' => 365];
$days    = $ranges[$range] ?? 30;
$rangeLbl = ['7d' => 'Últimos 7 días', '30d' => 'Últimos 30 días', '90d' => 'Últimos 90 días', '365d' => 'Último año'][$range] ?? 'Últimos 30 días';

/* ══════ KPIs DEL RANGO ══════ */
$kpi_new_users     = 0;
$kpi_new_products  = 0;
$kpi_verifs_ok     = 0;
$kpi_revenue       = 0.0;

$r = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role NOT IN ('admin','super_admin') AND deleted_at IS NULL AND created_at >= NOW() - INTERVAL $days DAY");
if ($r) $kpi_new_users = (int) mysqli_fetch_assoc($r)['c'];

$r = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM products WHERE created_at >= NOW() - INTERVAL $days DAY");
if ($r) $kpi_new_products = (int) mysqli_fetch_assoc($r)['c'];

if (tbl_exists($conn, 'seller_verifications')) {
    $r = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM seller_verifications WHERE status = 'approved' AND created_at >= NOW() - INTERVAL $days DAY");
    if ($r) $kpi_verifs_ok = (int) mysqli_fetch_assoc($r)['c'];
}
if (tbl_exists($conn, 'product_promotions')) {
    $r = @mysqli_query($conn, "SELECT COALESCE(SUM(price),0) AS t FROM product_promotions WHERE status IN ('active','expired') AND created_at >= NOW() - INTERVAL $days DAY");
    if ($r) $kpi_revenue = (float) mysqli_fetch_assoc($r)['t'];
}

/* ══════ SERIE TEMPORAL (usuarios vs productos) ══════
   Agrupamos por día (hasta 90d) o por mes (365d). */
$groupBy = $days >= 120 ? 'month' : 'day';
$series_labels   = [];
$series_users    = [];
$series_products = [];

if ($groupBy === 'day') {
    // Inicializar buckets
    $buckets = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $buckets[$d] = ['u' => 0, 'p' => 0];
    }
    $r = @mysqli_query($conn, "SELECT DATE(created_at) d, COUNT(*) c FROM users WHERE role NOT IN ('admin','super_admin') AND deleted_at IS NULL AND created_at >= NOW() - INTERVAL $days DAY GROUP BY DATE(created_at)");
    if ($r) while ($row = mysqli_fetch_assoc($r)) if (isset($buckets[$row['d']])) $buckets[$row['d']]['u'] = (int) $row['c'];
    $r = @mysqli_query($conn, "SELECT DATE(created_at) d, COUNT(*) c FROM products WHERE created_at >= NOW() - INTERVAL $days DAY GROUP BY DATE(created_at)");
    if ($r) while ($row = mysqli_fetch_assoc($r)) if (isset($buckets[$row['d']])) $buckets[$row['d']]['p'] = (int) $row['c'];

    foreach ($buckets as $d => $v) {
        $series_labels[]   = date('d M', strtotime($d));
        $series_users[]    = $v['u'];
        $series_products[] = $v['p'];
    }
} else {
    $buckets = [];
    for ($i = 11; $i >= 0; $i--) {
        $k = date('Y-m', strtotime("-$i months"));
        $buckets[$k] = ['u' => 0, 'p' => 0];
    }
    $r = @mysqli_query($conn, "SELECT DATE_FORMAT(created_at,'%Y-%m') m, COUNT(*) c FROM users WHERE role NOT IN ('admin','super_admin') AND deleted_at IS NULL AND created_at >= NOW() - INTERVAL 12 MONTH GROUP BY m");
    if ($r) while ($row = mysqli_fetch_assoc($r)) if (isset($buckets[$row['m']])) $buckets[$row['m']]['u'] = (int) $row['c'];
    $r = @mysqli_query($conn, "SELECT DATE_FORMAT(created_at,'%Y-%m') m, COUNT(*) c FROM products WHERE created_at >= NOW() - INTERVAL 12 MONTH GROUP BY m");
    if ($r) while ($row = mysqli_fetch_assoc($r)) if (isset($buckets[$row['m']])) $buckets[$row['m']]['p'] = (int) $row['c'];

    $meses = ['01' => 'Ene', '02' => 'Feb', '03' => 'Mar', '04' => 'Abr', '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dic'];
    foreach ($buckets as $k => $v) {
        [$y, $m] = explode('-', $k);
        $series_labels[]   = $meses[$m] . ' ' . substr($y, 2);
        $series_users[]    = $v['u'];
        $series_products[] = $v['p'];
    }
}

/* ══════ TOP CATEGORÍAS ══════ */
$cat_labels = [];
$cat_values = [];
$r = @mysqli_query($conn, "
    SELECT c.name AS name, COUNT(p.id) AS cnt
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id, c.name
    ORDER BY cnt DESC
    LIMIT 6
");
if ($r) while ($row = mysqli_fetch_assoc($r)) {
    $cat_labels[] = $row['name'] ?? 'Sin categoría';
    $cat_values[] = (int) $row['cnt'];
}

/* ══════ DONUT VERIFICACIONES ══════ */
$verif_counts = ['approved' => 0, 'pending' => 0, 'rejected' => 0];
if (tbl_exists($conn, 'seller_verifications')) {
    $r = @mysqli_query($conn, "SELECT status, COUNT(*) c FROM seller_verifications GROUP BY status");
    if ($r) while ($row = mysqli_fetch_assoc($r)) if (isset($verif_counts[$row['status']])) $verif_counts[$row['status']] = (int) $row['c'];
}
$donut_total = array_sum($verif_counts);

/* ══════ TOP VENDEDORES ══════ */
$top_sellers = [];
$r = @mysqli_query($conn, "
    SELECT u.id, u.full_name, u.email, u.profile_photo, u.city,
           COUNT(p.id) AS total_products,
           SUM(CASE WHEN p.status = 'disponible' THEN 1 ELSE 0 END) AS active_products
    FROM users u
    INNER JOIN products p ON p.user_id = u.id
    WHERE u.role NOT IN ('admin','super_admin') AND u.deleted_at IS NULL
    GROUP BY u.id
    ORDER BY total_products DESC
    LIMIT 8
");
if ($r) while ($row = mysqli_fetch_assoc($r)) $top_sellers[] = $row;

/* ══════ JSON para JS ══════ */
$js_series = json_encode([
    'labels'   => $series_labels,
    'users'    => $series_users,
    'products' => $series_products,
]);
$js_cats = json_encode([
    'labels' => $cat_labels,
    'values' => $cat_values,
]);
$js_donut = json_encode([
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
  <title>Estadísticas — ComercioLocal Admin</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Syne:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" defer></script>

  <style>
    :root {
      --primary:#0B2E17; --primary-2:#123D20; --accent:#F5A81C; --accent-2:#FFC154;
      --red:#E53935; --red-soft:#FEECEB; --green-soft:#E6F3EB; --yellow-soft:#FFF3D6; --blue-soft:#EEF5FF;
      --bg:#F8F9FA; --white:#FFF; --ink:#0F172A; --ink2:#334155; --ink3:#64748B; --ink4:#94A3B8;
      --bord:#E5E7EB; --bord-soft:#EEF0F3; --shadow:0 2px 12px rgba(0,0,0,.06); --shadow-lg:0 8px 28px rgba(0,0,0,.08);
    }
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    body { background: var(--bg); color: var(--ink); font-family: 'DM Sans', system-ui, sans-serif; font-size: 14px; line-height: 1.45; }
    h1, h2, h3, h4 { font-family: 'Syne', 'DM Sans', sans-serif; color: var(--primary); letter-spacing: -.01em; margin: 0; }
    a { color: inherit; text-decoration: none; }

    /* ══ TOPBAR (igual que dashboard) ══ */
    .topbar { background: var(--white); border-bottom: 1px solid var(--bord); padding: 14px 32px; display: flex; align-items: center; gap: 32px; position: sticky; top: 0; z-index: 50; }
    .tb-logo { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.15rem; color: var(--primary); display: flex; align-items: center; gap: 8px; }
    .tb-logo-mark { width: 32px; height: 32px; border-radius: 9px; background: var(--primary); color: var(--accent); display: flex; align-items: center; justify-content: center; font-size: .85rem; }
    .tb-nav { display: flex; gap: 4px; margin: 0 auto; }
    .tb-link { padding: 8px 14px; border-radius: 10px; font-weight: 500; font-size: .88rem; color: var(--ink2); transition: all .15s; display: inline-flex; align-items: center; gap: 6px; }
    .tb-link:hover { background: var(--bg); color: var(--primary); }
    .tb-link.active { background: var(--primary); color: var(--white); }
    .tb-right { display: flex; align-items: center; gap: 14px; }
    .tb-bell { position: relative; width: 40px; height: 40px; border-radius: 10px; border: 1px solid var(--bord); background: var(--white); color: var(--ink2); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: .15s; }
    .tb-bell:hover { border-color: var(--primary); color: var(--primary); }
    .tb-user-wrap { position: relative; }
    .tb-user { display: flex; align-items: center; gap: 10px; padding: 4px 10px 4px 4px; border-radius: 10px; border: 1px solid var(--bord); background: var(--white); cursor: pointer; font-family: inherit; transition: .15s; }
    .tb-user:hover { border-color: var(--primary); }
    .tb-user__av { width: 34px; height: 34px; border-radius: 8px; background: var(--primary); color: var(--accent); font-weight: 700; display: flex; align-items: center; justify-content: center; font-size: .88rem; }
    .tb-user__info { text-align: left; }
    .tb-user__info strong { font-size: .85rem; color: var(--ink); display: block; line-height: 1.1; }
    .tb-user__info span { font-size: .7rem; color: var(--ink4); }
    .tb-user__caret { font-size: .7rem; color: var(--ink4); transition: .2s; }
    .tb-user-wrap.open .tb-user__caret { transform: rotate(180deg); color: var(--primary); }
    .tb-menu { position: absolute; right: 0; top: calc(100% + 8px); background: var(--white); border: 1px solid var(--bord); border-radius: 12px; box-shadow: var(--shadow-lg); min-width: 220px; padding: 6px; z-index: 60; opacity: 0; pointer-events: none; transform: translateY(-4px); transition: .15s; }
    .tb-user-wrap.open .tb-menu { opacity: 1; pointer-events: auto; transform: translateY(0); }
    .tb-menu__head { padding: 10px 12px 6px; border-bottom: 1px solid var(--bord-soft); margin-bottom: 4px; }
    .tb-menu__head strong { display: block; font-size: .86rem; color: var(--ink); line-height: 1.2; }
    .tb-menu__head small { font-size: .72rem; color: var(--ink4); }
    .tb-menu__item { display: flex; align-items: center; gap: 10px; padding: 9px 12px; border-radius: 8px; font-size: .86rem; color: var(--ink2); cursor: pointer; transition: .12s; }
    .tb-menu__item i { color: var(--ink3); width: 16px; font-size: .95rem; }
    .tb-menu__item:hover { background: var(--bg); color: var(--primary); }
    .tb-menu__item:hover i { color: var(--primary); }
    .tb-menu__item.danger { color: var(--red); }
    .tb-menu__item.danger i { color: var(--red); }
    .tb-menu__item.danger:hover { background: var(--red-soft); }
    .tb-menu__sep { height: 1px; background: var(--bord-soft); margin: 4px 0; }

    /* ══ PAGE ══ */
    .page { max-width: 1320px; margin: 0 auto; padding: 28px 32px 48px; }
    .page-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; margin-bottom: 24px; flex-wrap: wrap; }
    .page-head h1 { font-size: 1.6rem; font-weight: 700; margin-bottom: 4px; }
    .page-head p { margin: 0; color: var(--ink3); font-size: .88rem; }

    /* ══ FILTRO DE RANGO ══ */
    .range-group { display: inline-flex; background: var(--white); border: 1px solid var(--bord); border-radius: 10px; padding: 4px; gap: 2px; }
    .range-btn { padding: 7px 14px; font-size: .8rem; font-weight: 600; color: var(--ink3); border-radius: 7px; transition: .15s; cursor: pointer; border: 0; background: transparent; font-family: inherit; }
    .range-btn:hover { color: var(--primary); }
    .range-btn.active { background: var(--primary); color: var(--white); }

    /* ══ METRICS ══ */
    .metrics { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    .metric { background: var(--white); border-radius: 16px; padding: 22px; box-shadow: var(--shadow); position: relative; }
    .metric-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
    .metric-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; background: var(--green-soft); color: var(--primary); }
    .metric--products .metric-icon { background: var(--blue-soft); color: #1E40AF; }
    .metric--verif    .metric-icon { background: var(--yellow-soft); color: #B45309; }
    .metric--revenue  .metric-icon { background: var(--green-soft); color: var(--primary); }
    .metric-trend { font-size: .72rem; font-weight: 700; padding: 3px 9px; border-radius: 999px; display: inline-flex; align-items: center; gap: 3px; }
    .trend-up   { background: var(--green-soft); color: var(--primary); }
    .trend-down { background: var(--red-soft); color: var(--red); }
    .metric-num { font-family: 'Syne', sans-serif; font-size: 2rem; font-weight: 800; color: var(--primary); line-height: 1; margin-bottom: 6px; }
    .metric-label { font-size: .83rem; color: var(--ink3); font-weight: 500; }

    /* ══ GRID ══ */
    .grid-main { display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr); gap: 20px; align-items: start; margin-bottom: 20px; }
    .grid-second { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 20px; align-items: start; }

    .card { background: var(--white); border-radius: 16px; box-shadow: var(--shadow); overflow: hidden; }
    .card-head { padding: 20px 24px 12px; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; }
    .card-head h2 { font-size: 1.05rem; font-weight: 700; }
    .card-head-sub { font-size: .78rem; color: var(--ink3); margin-top: 2px; }
    .card-body { padding: 8px 24px 24px; }

    /* Leyenda mini */
    .mini-legend { display: flex; gap: 14px; align-items: center; }
    .mini-legend .dot { display: inline-flex; align-items: center; gap: 6px; font-size: .78rem; color: var(--ink2); font-weight: 500; }
    .mini-legend .dot::before { content: ''; width: 10px; height: 10px; border-radius: 3px; display: inline-block; }
    .mini-legend .dot.users::before    { background: var(--primary); }
    .mini-legend .dot.products::before { background: var(--accent); }

    /* Donut */
    .donut-body { padding: 10px 24px 24px; display: grid; grid-template-columns: 160px 1fr; gap: 20px; align-items: center; }
    .donut-canvas-wrap { position: relative; }
    .donut-center { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; pointer-events: none; }
    .donut-center strong { font-family: 'Syne', sans-serif; font-size: 1.6rem; color: var(--primary); font-weight: 800; line-height: 1; }
    .donut-center span { font-size: .7rem; color: var(--ink3); }
    .donut-legend { display: flex; flex-direction: column; gap: 12px; }
    .legend-item { display: flex; align-items: center; gap: 10px; font-size: .82rem; }
    .legend-dot { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }
    .legend-label { color: var(--ink2); flex: 1; }
    .legend-value { font-weight: 700; color: var(--ink); }

    /* Tabla */
    .seller-table { width: 100%; border-collapse: collapse; font-size: .84rem; }
    .seller-table thead th { text-align: left; font-weight: 600; color: var(--ink3); font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; padding: 12px 18px; background: var(--bg); border-bottom: 1px solid var(--bord); }
    .seller-table tbody td { padding: 12px 18px; border-bottom: 1px solid var(--bord-soft); vertical-align: middle; }
    .seller-table tbody tr:nth-child(even) { background: var(--bg); }
    .seller-table tbody tr:hover { background: #EEF5EF; }
    .seller-table tbody tr:last-child td { border-bottom: none; }
    .seller-cell { display: flex; align-items: center; gap: 10px; }
    .seller-av { width: 36px; height: 36px; border-radius: 10px; background: var(--primary); color: var(--accent); font-weight: 700; display: flex; align-items: center; justify-content: center; font-size: .82rem; overflow: hidden; flex-shrink: 0; }
    .seller-av img { width: 100%; height: 100%; object-fit: cover; }
    .seller-name { font-weight: 600; color: var(--ink); }
    .seller-email { font-size: .74rem; color: var(--ink4); }
    .num-pill { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 999px; font-size: .74rem; font-weight: 700; background: var(--green-soft); color: var(--primary); }

    .table-empty { padding: 48px 24px; text-align: center; color: var(--ink4); }
    .table-empty i { font-size: 2.4rem; display: block; margin-bottom: 10px; color: #CBD5E1; }

    .rank-badge { width: 24px; height: 24px; border-radius: 6px; background: var(--bord-soft); color: var(--ink3); font-weight: 800; font-size: .72rem; display: inline-flex; align-items: center; justify-content: center; font-family: 'Syne', sans-serif; }
    .rank-badge.gold    { background: var(--accent); color: var(--primary); }
    .rank-badge.silver  { background: #E2E8F0; color: var(--ink2); }
    .rank-badge.bronze  { background: #FBD9B5; color: #92400E; }

    /* Canvas size */
    #lineChart { max-height: 300px; }
    #catChart  { max-height: 280px; }

    @media (max-width: 1100px) {
      .metrics { grid-template-columns: repeat(2, 1fr); }
      .grid-main, .grid-second { grid-template-columns: 1fr; }
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
      <a class="tb-link" href="./dashboard.php"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
      <a class="tb-link" href="./gestion_usuarios.php"><i class="bi bi-people"></i> Usuarios</a>
      <a class="tb-link" href="./gestion_admins.php"><i class="bi bi-shield-fill-check"></i> Administradores</a>
      <a class="tb-link" href="./verificaciones.php"><i class="bi bi-patch-check"></i> Verificaciones</a>
      <a class="tb-link" href="./gestion_productos.php"><i class="bi bi-box-seam"></i> Productos</a>
      <a class="tb-link active" href="./estadisticas.php"><i class="bi bi-bar-chart-line"></i> Estadísticas</a>
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
          <a class="tb-menu__item" href="../perfil.php"><i class="bi bi-person-circle"></i> Mi perfil</a>
          <div class="tb-menu__sep"></div>
          <a class="tb-menu__item danger" href="../../controllers/auth_logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
        </div>
      </div>
    </div>
  </header>

  <script>
    function toggleTbMenu(e){e.stopPropagation();e.currentTarget.closest('.tb-user-wrap').classList.toggle('open');}
    document.addEventListener('click',e=>{if(!e.target.closest('.tb-user-wrap'))document.querySelectorAll('.tb-user-wrap.open').forEach(el=>el.classList.remove('open'));});
  </script>

  <div class="page">

    <!-- ══ HEAD + RANGO ══ -->
    <div class="page-head">
      <div>
        <h1>Estadísticas</h1>
        <p>Analítica completa de la plataforma — <?= $rangeLbl ?>.</p>
      </div>
      <div class="range-group" role="tablist">
        <?php foreach ([['7d','7 días'], ['30d','30 días'], ['90d','90 días'], ['365d','1 año']] as [$k,$lbl]): ?>
          <a href="?range=<?= $k ?>" class="range-btn <?= $range === $k ? 'active' : '' ?>"><?= $lbl ?></a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ══ KPIs ══ -->
    <div class="metrics">
      <div class="metric metric--users">
        <div class="metric-top">
          <div class="metric-icon"><i class="bi bi-person-plus-fill"></i></div>
          <span class="metric-trend trend-up"><i class="bi bi-arrow-up-short"></i> +<?= $kpi_new_users ?></span>
        </div>
        <div class="metric-num"><?= number_format($kpi_new_users, 0, ',', '.') ?></div>
        <div class="metric-label">Nuevos usuarios</div>
      </div>

      <div class="metric metric--products">
        <div class="metric-top">
          <div class="metric-icon"><i class="bi bi-box-seam-fill"></i></div>
          <span class="metric-trend trend-up"><i class="bi bi-arrow-up-short"></i> +<?= $kpi_new_products ?></span>
        </div>
        <div class="metric-num"><?= number_format($kpi_new_products, 0, ',', '.') ?></div>
        <div class="metric-label">Productos publicados</div>
      </div>

      <div class="metric metric--verif">
        <div class="metric-top">
          <div class="metric-icon"><i class="bi bi-shield-fill-check"></i></div>
        </div>
        <div class="metric-num"><?= number_format($kpi_verifs_ok, 0, ',', '.') ?></div>
        <div class="metric-label">Verificaciones aprobadas</div>
      </div>

      <div class="metric metric--revenue">
        <div class="metric-top">
          <div class="metric-icon"><i class="bi bi-rocket-takeoff-fill"></i></div>
        </div>
        <div class="metric-num">$<?= number_format($kpi_revenue, 0, ',', '.') ?></div>
        <div class="metric-label">Ingresos por promociones</div>
      </div>
    </div>

    <!-- ══ GRID: LÍNEA + DONUT ══ -->
    <div class="grid-main">

      <div class="card">
        <div class="card-head">
          <div>
            <h2>Crecimiento de la plataforma</h2>
            <div class="card-head-sub">Usuarios nuevos vs productos publicados.</div>
          </div>
          <div class="mini-legend">
            <span class="dot users">Usuarios</span>
            <span class="dot products">Productos</span>
          </div>
        </div>
        <div class="card-body">
          <canvas id="lineChart"></canvas>
        </div>
      </div>

      <div class="card">
        <div class="card-head">
          <div>
            <h2>Estado de verificaciones</h2>
            <div class="card-head-sub">Desglose histórico.</div>
          </div>
        </div>
        <div class="donut-body">
          <div class="donut-canvas-wrap">
            <canvas id="donutChart" width="160" height="160"></canvas>
            <div class="donut-center">
              <strong><?= $donut_total ?></strong>
              <span>Total</span>
            </div>
          </div>
          <div class="donut-legend">
            <div class="legend-item"><span class="legend-dot" style="background:#0B2E17;"></span><span class="legend-label">Aprobadas</span><span class="legend-value"><?= $verif_counts['approved'] ?></span></div>
            <div class="legend-item"><span class="legend-dot" style="background:#F5A81C;"></span><span class="legend-label">Pendientes</span><span class="legend-value"><?= $verif_counts['pending'] ?></span></div>
            <div class="legend-item"><span class="legend-dot" style="background:#E53935;"></span><span class="legend-label">Rechazadas</span><span class="legend-value"><?= $verif_counts['rejected'] ?></span></div>
          </div>
        </div>
      </div>

    </div>

    <!-- ══ GRID: BARRAS + TABLA ══ -->
    <div class="grid-second">

      <div class="card">
        <div class="card-head">
          <div>
            <h2>Productos por categoría</h2>
            <div class="card-head-sub">Top 6 categorías con más publicaciones.</div>
          </div>
        </div>
        <div class="card-body">
          <?php if (empty($cat_values) || array_sum($cat_values) === 0): ?>
            <div class="table-empty"><i class="bi bi-bar-chart"></i><strong style="color:var(--ink2);">Sin datos</strong><div style="margin-top:4px;font-size:.85rem;">Aún no hay productos registrados.</div></div>
          <?php else: ?>
            <canvas id="catChart"></canvas>
          <?php endif; ?>
        </div>
      </div>

      <div class="card">
        <div class="card-head">
          <div>
            <h2>Top vendedores</h2>
            <div class="card-head-sub">Con más productos en catálogo.</div>
          </div>
        </div>
        <?php if (empty($top_sellers)): ?>
          <div class="table-empty"><i class="bi bi-people"></i><strong style="color:var(--ink2);">Sin vendedores activos</strong></div>
        <?php else: ?>
          <table class="seller-table">
            <thead>
              <tr>
                <th style="width:44px;">#</th>
                <th>Vendedor</th>
                <th>Ciudad</th>
                <th style="text-align:right;">Productos</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($top_sellers as $i => $s):
                $ini = strtoupper(mb_substr($s['full_name'] ?? 'V', 0, 1));
                $badge = $i === 0 ? 'gold' : ($i === 1 ? 'silver' : ($i === 2 ? 'bronze' : ''));
                $photo = !empty($s['profile_photo']) ? '../../../public/uploads/perfiles/' . htmlspecialchars($s['profile_photo']) : '';
              ?>
                <tr>
                  <td><span class="rank-badge <?= $badge ?>"><?= $i + 1 ?></span></td>
                  <td>
                    <div class="seller-cell">
                      <div class="seller-av">
                        <?php if ($photo): ?><img src="<?= $photo ?>" alt=""><?php else: ?><?= htmlspecialchars($ini) ?><?php endif; ?>
                      </div>
                      <div>
                        <div class="seller-name"><?= htmlspecialchars($s['full_name'] ?? 'Usuario') ?></div>
                        <div class="seller-email"><?= htmlspecialchars($s['email'] ?? '') ?></div>
                      </div>
                    </div>
                  </td>
                  <td><span style="color:var(--ink2);"><?= htmlspecialchars($s['city'] ?? '—') ?></span></td>
                  <td style="text-align:right;">
                    <span class="num-pill"><i class="bi bi-box-seam"></i><?= (int) $s['total_products'] ?></span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>

    </div>
  </div>

  <script>
    const SERIES = <?= $js_series ?>;
    const CATS   = <?= $js_cats ?>;
    const DONUT  = <?= $js_donut ?>;

    window.addEventListener('load', () => {
      if (!window.Chart) return;

      // Línea — crecimiento
      const lc = document.getElementById('lineChart');
      if (lc) {
        new Chart(lc, {
          type: 'line',
          data: {
            labels: SERIES.labels,
            datasets: [
              {
                label: 'Usuarios', data: SERIES.users,
                borderColor: '#0B2E17', backgroundColor: 'rgba(11,46,23,.08)',
                tension: .35, borderWidth: 2.5, fill: true, pointRadius: 0, pointHoverRadius: 5,
              },
              {
                label: 'Productos', data: SERIES.products,
                borderColor: '#F5A81C', backgroundColor: 'rgba(245,168,28,.12)',
                tension: .35, borderWidth: 2.5, fill: true, pointRadius: 0, pointHoverRadius: 5,
              },
            ],
          },
          options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: false }, tooltip: { backgroundColor: '#0B2E17', padding: 10, cornerRadius: 8 } },
            scales: {
              x: { grid: { display: false }, ticks: { color: '#64748B', font: { size: 11 } } },
              y: { beginAtZero: true, grid: { color: '#EEF0F3' }, ticks: { color: '#64748B', font: { size: 11 }, precision: 0 } },
            },
          },
        });
      }

      // Barras — categorías
      const cc = document.getElementById('catChart');
      if (cc && CATS.values.length) {
        new Chart(cc, {
          type: 'bar',
          data: {
            labels: CATS.labels,
            datasets: [{
              label: 'Productos', data: CATS.values,
              backgroundColor: '#0B2E17', borderRadius: 8, barThickness: 28, maxBarThickness: 36,
              hoverBackgroundColor: '#F5A81C',
            }],
          },
          options: {
            responsive: true, maintainAspectRatio: false, indexAxis: 'y',
            plugins: { legend: { display: false }, tooltip: { backgroundColor: '#0B2E17', padding: 10, cornerRadius: 8 } },
            scales: {
              x: { beginAtZero: true, grid: { color: '#EEF0F3' }, ticks: { color: '#64748B', font: { size: 11 }, precision: 0 } },
              y: { grid: { display: false }, ticks: { color: '#334155', font: { size: 12, weight: '600' } } },
            },
          },
        });
      }

      // Donut
      const dc = document.getElementById('donutChart');
      if (dc && DONUT.reduce((a, b) => a + b, 0) > 0) {
        new Chart(dc, {
          type: 'doughnut',
          data: { labels: ['Aprobadas', 'Pendientes', 'Rechazadas'], datasets: [{ data: DONUT, backgroundColor: ['#0B2E17', '#F5A81C', '#E53935'], borderWidth: 3, borderColor: '#fff', hoverOffset: 6 }] },
          options: { cutout: '70%', responsive: false, plugins: { legend: { display: false } } },
        });
      }
    });
  </script>

</body>
</html>

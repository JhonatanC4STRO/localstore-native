<?php
/**
 * Admin Dashboard — ComercioLocal
 * All values come from live DB queries. No mock data.
 */
require_once("../config/conexion.php");
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ./auth/login.php");
    exit();
}
$role = $_SESSION['user']['role'] ?? '';
if ($role !== 'admin' && $role !== 'super_admin') {
    header("Location: ./inde.php");
    exit();
}

$user        = $_SESSION['user'];
$userInitial = strtoupper(mb_substr($user['full_name'] ?? 'A', 0, 1));
$userName    = explode(' ', $user['full_name'] ?? 'Admin')[0];
$isSuperAdmin = ($role === 'super_admin');

/* ══════════════════════════════════════════════
   HELPERS
══════════════════════════════════════════════ */
function tbl_exists(mysqli $c, string $t): bool {
    $r = @mysqli_query($c, "SHOW TABLES LIKE '" . mysqli_real_escape_string($c, $t) . "'");
    return ($r && mysqli_num_rows($r) > 0);
}

function safe_count(mysqli $c, string $sql): int {
    $r = @mysqli_query($c, $sql);
    if (!$r) return 0;
    $row = mysqli_fetch_assoc($r);
    return (int)($row['c'] ?? $row[array_key_first($row)] ?? 0);
}

function time_ago(?string $dt): string {
    if (!$dt) return '—';
    $diff = max(0, time() - strtotime($dt));
    if ($diff < 60)    return 'Hace ' . $diff . ' s';
    if ($diff < 3600)  return 'Hace ' . floor($diff / 60) . ' min';
    if ($diff < 86400) return 'Hace ' . floor($diff / 3600) . ' h';
    return 'Hace ' . floor($diff / 86400) . ' días';
}

function pct_change(int $curr, int $prev): array {
    if ($prev === 0) {
        return ['val' => $curr > 0 ? '+' . $curr : '—', 'dir' => $curr > 0 ? 'up' : 'neutral'];
    }
    $p = round((($curr - $prev) / $prev) * 100, 1);
    return ['val' => ($p >= 0 ? '+' : '') . $p . '%', 'dir' => $p >= 0 ? 'up' : 'down'];
}

/* ══════════════════════════════════════════════
   1. USERS
══════════════════════════════════════════════ */
$total_users    = 0;
$total_sellers  = 0;
$total_admins   = 0;
$active_users   = 0;
$blocked_users  = 0;
$users_this_month = 0;
$users_last_month = 0;

$r = @mysqli_query($conn, "
    SELECT
        SUM(role NOT IN ('admin','super_admin'))  AS regular_users,
        SUM(role = 'seller')                      AS sellers,
        SUM(role IN ('admin','super_admin'))       AS admins,
        SUM(status = 'active' AND role NOT IN ('admin','super_admin')) AS active_cnt,
        SUM(status IN ('blocked','suspended') AND role NOT IN ('admin','super_admin')) AS blocked_cnt,
        SUM(
            role NOT IN ('admin','super_admin')
            AND MONTH(created_at) = MONTH(NOW())
            AND YEAR(created_at)  = YEAR(NOW())
        ) AS this_month,
        SUM(
            role NOT IN ('admin','super_admin')
            AND MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH))
            AND YEAR(created_at)  = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))
        ) AS last_month
    FROM users
    WHERE deleted_at IS NULL
");
if ($r && $row = mysqli_fetch_assoc($r)) {
    $total_users      = (int)$row['regular_users'];
    $total_sellers    = (int)$row['sellers'];
    $total_admins     = (int)$row['admins'];
    $active_users     = (int)$row['active_cnt'];
    $blocked_users    = (int)$row['blocked_cnt'];
    $users_this_month = (int)$row['this_month'];
    $users_last_month = (int)$row['last_month'];
}

/* ══════════════════════════════════════════════
   2. PRODUCTS
══════════════════════════════════════════════ */
$total_products    = 0;
$active_products   = 0;
$prods_this_month  = 0;
$prods_last_month  = 0;

$r = @mysqli_query($conn, "
    SELECT
        COUNT(*)                                           AS total,
        SUM(status = 'disponible')                         AS active_cnt,
        SUM(MONTH(created_at) = MONTH(NOW())   AND YEAR(created_at) = YEAR(NOW()))                             AS this_month,
        SUM(MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))) AS last_month
    FROM products
");
if ($r && $row = mysqli_fetch_assoc($r)) {
    $total_products   = (int)$row['total'];
    $active_products  = (int)$row['active_cnt'];
    $prods_this_month = (int)$row['this_month'];
    $prods_last_month = (int)$row['last_month'];
}

/* ══════════════════════════════════════════════
   3. VERIFICATIONS
══════════════════════════════════════════════ */
$pending_verifications = 0;
$recent_verifications  = [];
$verif_table           = null;

foreach (['seller_verifications','verifications','user_verifications','verification_requests'] as $vtbl) {
    if (tbl_exists($conn, $vtbl)) {
        $verif_table = $vtbl;
        $r = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM `$vtbl` WHERE status = 'pending'");
        if ($r) $pending_verifications = (int)mysqli_fetch_assoc($r)['c'];

        /* Recent pending verifications with user info */
        $has_store = tbl_exists($conn, 'stores');
        $rv = @mysqli_query($conn, "
            SELECT v.id, v.user_id, v.created_at, v.status,
                   u.full_name, u.email
            FROM `$vtbl` v
            LEFT JOIN users u ON u.id = v.user_id
            WHERE v.status = 'pending'
            ORDER BY v.created_at ASC
            LIMIT 5
        ");
        if ($rv) {
            while ($vrow = mysqli_fetch_assoc($rv)) $recent_verifications[] = $vrow;
        }
        break;
    }
}

/* ══════════════════════════════════════════════
   4. REPORTED PRODUCTS
══════════════════════════════════════════════ */
$reported_products = 0;
foreach (['product_reports','reports','reported_products','product_flags'] as $rtbl) {
    if (tbl_exists($conn, $rtbl)) {
        $r = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM `$rtbl` WHERE status = 'pending'");
        if ($r) { $reported_products = (int)mysqli_fetch_assoc($r)['c']; break; }
    }
}

/* ══════════════════════════════════════════════
   5. TRENDS
══════════════════════════════════════════════ */
$trend_users    = pct_change($users_this_month, $users_last_month);
$trend_products = pct_change($prods_this_month, $prods_last_month);

/* ══════════════════════════════════════════════
   6. RECENT USERS
══════════════════════════════════════════════ */
$recent_users = [];
$r = @mysqli_query($conn, "
    SELECT u.id, u.full_name, u.email, u.role, u.status, u.created_at,
           (SELECT COUNT(*) FROM products p WHERE p.user_id = u.id) AS product_count
    FROM users u
    WHERE u.role NOT IN ('admin','super_admin')
      AND u.deleted_at IS NULL
    ORDER BY u.created_at DESC
    LIMIT 6
");
if ($r) while ($row = mysqli_fetch_assoc($r)) $recent_users[] = $row;

/* ══════════════════════════════════════════════
   7. ACTIVITY LOG
══════════════════════════════════════════════ */
$recent_activity = [];
if (tbl_exists($conn, 'activity_log')) {
    $r = @mysqli_query($conn, "
        SELECT al.action, al.created_at,
               u.full_name AS admin_name
        FROM activity_log al
        LEFT JOIN users u ON u.id = al.admin_id
        ORDER BY al.created_at DESC
        LIMIT 8
    ");
    if ($r) while ($row = mysqli_fetch_assoc($r)) $recent_activity[] = $row;
}

/* ══════════════════════════════════════════════
   8. CHART DATA  (last 7 months)
══════════════════════════════════════════════ */
$mn = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
$chart_labels   = [];
$chart_users_m  = [];
$chart_sellers_m= [];
$chart_prods_m  = [];

/* Monthly user registrations */
$mu = [];
$r  = @mysqli_query($conn, "
    SELECT DATE_FORMAT(created_at,'%Y-%m') AS ym,
           SUM(role NOT IN ('admin','super_admin')) AS users,
           SUM(role = 'seller')                     AS sellers
    FROM users
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at,'%Y-%m')
");
if ($r) while ($row = mysqli_fetch_assoc($r)) $mu[$row['ym']] = $row;

/* Monthly product publications */
$mp = [];
$r  = @mysqli_query($conn, "
    SELECT DATE_FORMAT(created_at,'%Y-%m') AS ym, COUNT(*) AS total
    FROM products
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at,'%Y-%m')
");
if ($r) while ($row = mysqli_fetch_assoc($r)) $mp[$row['ym']] = (int)$row['total'];

for ($i = 6; $i >= 0; $i--) {
    $ts              = mktime(0, 0, 0, (int)date('n') - $i, 1);
    $key             = date('Y-m', $ts);
    $chart_labels[]  = $mn[(int)date('n', $ts) - 1];
    $chart_users_m[] = (int)($mu[$key]['users']   ?? 0);
    $chart_sellers_m[]= (int)($mu[$key]['sellers'] ?? 0);
    $chart_prods_m[] = $mp[$key] ?? 0;
}

/* ══════════════════════════════════════════════
   9. USER DISTRIBUTION  (donut)
══════════════════════════════════════════════ */
$dist_buyers  = 0;
$dist_sellers = 0;
$dist_total   = max(1, $total_users);

$r = @mysqli_query($conn, "
    SELECT SUM(role='user') AS buyers, SUM(role='seller') AS sellers
    FROM users WHERE deleted_at IS NULL AND role NOT IN ('admin','super_admin')
");
if ($r && $row = mysqli_fetch_assoc($r)) {
    $dist_buyers  = (int)$row['buyers'];
    $dist_sellers = (int)$row['sellers'];
}
$dist_buyers_pct  = $dist_total > 0 ? round($dist_buyers  / $dist_total * 100) : 0;
$dist_sellers_pct = $dist_total > 0 ? round($dist_sellers / $dist_total * 100) : 0;

/* ── JSON for charts ── */
$json_labels   = json_encode($chart_labels);
$json_users    = json_encode($chart_users_m);
$json_sellers  = json_encode($chart_sellers_m);
$json_products = json_encode($chart_prods_m);
$json_donut    = json_encode([$dist_buyers, $dist_sellers]);

/* ── Percent bars ── */
$bar_users    = $total_users    > 0 ? min(99, round($active_users    / $total_users    * 100)) : 0;
$bar_products = $total_products > 0 ? min(99, round($active_products / $total_products * 100)) : 0;
$bar_verif    = ($total_users   > 0 && $pending_verifications > 0) ? min(99, round($pending_verifications / max(1,$total_sellers) * 100)) : 0;
$bar_reports  = $total_products > 0 ? min(99, round($reported_products / max(1,$total_products) * 100)) : 0;
$bar_sellers  = $total_users    > 0 ? min(99, round($total_sellers    / $total_users    * 100)) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Administración — ComercioLocal</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../style/admin_dashboard.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" defer></script>
</head>
<body class="admin-body">

<!-- ══════════════════════════════════════
     TOP NAVIGATION BAR
══════════════════════════════════════ -->
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
    <input type="text" placeholder="Buscar usuarios, productos, reportes…">
  </div>

  <div class="adm-topbar__actions">

    <span style="font-size:.8rem;color:#64748b;display:flex;align-items:center;">
      <span class="adm-live-dot"></span> En vivo
    </span>

    <button class="adm-topbar__icon-btn" title="Notificaciones">
      <i class="bi bi-bell-fill"></i>
      <?php if ($pending_verifications > 0): ?>
        <span class="badge"></span>
      <?php endif; ?>
    </button>

    <?php if ($reported_products > 0): ?>
    <button class="adm-topbar__icon-btn" title="Productos reportados">
      <i class="bi bi-flag-fill" style="color:#f97316;"></i>
    </button>
    <?php endif; ?>

    <!-- Quick actions -->
    <div class="adm-quick-actions" id="qaMenu">
      <button class="adm-quick-actions__btn" onclick="toggleDropdown('qaDropdown')">
        <i class="bi bi-lightning-fill"></i>
        Acciones rápidas
        <i class="bi bi-chevron-down" style="font-size:.75rem;"></i>
      </button>
      <div class="adm-quick-actions__dropdown" id="qaDropdown">
        <a href="./verificaciones.php" class="adm-quick-actions__item">
          <i class="bi bi-patch-check-fill green"></i>
          Revisar verificaciones
        </a>
        <a href="./gestion_usuarios.php" class="adm-quick-actions__item">
          <i class="bi bi-people-fill blue"></i>
          Gestionar usuarios
        </a>
        <a href="./gestion_productos.php" class="adm-quick-actions__item">
          <i class="bi bi-box-seam-fill green"></i>
          Gestionar productos
        </a>
        <?php if ($isSuperAdmin): ?>
        <a href="./gestion_admins.php" class="adm-quick-actions__item">
          <i class="bi bi-shield-fill-check green"></i>
          Gestión de admins
        </a>
        <?php endif; ?>
        <hr style="border:none;border-top:1px solid #e2e8f0;margin:4px 0;">
        <a href="#" class="adm-quick-actions__item" onclick="location.reload()">
          <i class="bi bi-arrow-clockwise yellow"></i>
          Refrescar estadísticas
        </a>
      </div>
    </div>

    <!-- Avatar -->
    <div class="adm-avatar-menu" id="avatarMenu">
      <button class="adm-avatar-btn" onclick="toggleDropdown('avatarDropdown')">
        <div class="adm-avatar-circle"><?= htmlspecialchars($userInitial) ?></div>
        <div class="adm-avatar-btn-info">
          <strong><?= htmlspecialchars($userName) ?></strong>
          <span><?= $isSuperAdmin ? 'Super Admin' : 'Admin' ?></span>
        </div>
        <i class="bi bi-chevron-down" style="font-size:.72rem;color:#94a3b8;margin-left:2px;"></i>
      </button>
      <div class="adm-avatar-dropdown" id="avatarDropdown">
        <a href="./gestion_admins.php"><i class="bi bi-shield-check" style="width:18px;text-align:center;"></i> Gestión de admins</a>
        <a href="#"><i class="bi bi-gear" style="width:18px;text-align:center;"></i> Configuración</a>
        <hr>
        <a href="../controller/logout.php" class="danger"><i class="bi bi-box-arrow-right" style="width:18px;text-align:center;"></i> Cerrar sesión</a>
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

    <a class="adm-sb-link active" href="./admin_dashboard.php">
      <i class="bi bi-speedometer2"></i>Dashboard
    </a>

    <a class="adm-sb-link" href="./verificaciones.php">
      <i class="bi bi-patch-check"></i>Solicitudes de verificación
      <?php if ($pending_verifications > 0): ?>
        <span class="adm-sb-badge red"><?= $pending_verifications ?></span>
      <?php endif; ?>
    </a>

    <a class="adm-sb-link" href="./gestion_usuarios.php">
      <i class="bi bi-people"></i>Gestión de usuarios
      <?php if ($total_users > 0): ?>
        <span class="adm-sb-badge green"><?= number_format($total_users) ?></span>
      <?php endif; ?>
    </a>

    <a class="adm-sb-link" href="./gestion_productos.php">
      <i class="bi bi-box-seam"></i>Gestión de productos
    </a>

    <a class="adm-sb-link" href="#">
      <i class="bi bi-flag"></i>Productos reportados
      <?php if ($reported_products > 0): ?>
        <span class="adm-sb-badge yellow"><?= $reported_products ?></span>
      <?php endif; ?>
    </a>

    <hr class="adm-sb-divider">
    <div class="adm-sb-label">Administración</div>

    <a class="adm-sb-link" href="./gestion_admins.php">
      <i class="bi bi-shield-check"></i>Gestión de admins
    </a>

    <a class="adm-sb-link" href="#">
      <i class="bi bi-journal-text"></i>Registros de actividad
    </a>

    <a class="adm-sb-link" href="#">
      <i class="bi bi-bar-chart-line"></i>Reportes y analítica
    </a>

    <hr class="adm-sb-divider">
    <div class="adm-sb-label">Sistema</div>

    <a class="adm-sb-link" href="#">
      <i class="bi bi-gear"></i>Configuración
    </a>

    <a class="adm-sb-link" href="../controller/logout.php" style="color:rgba(239,68,68,.7);">
      <i class="bi bi-box-arrow-right"></i>Cerrar sesión
    </a>

    <div class="adm-sb-footer">
      <?php if ($pending_verifications > 0 || $reported_products > 0): ?>
        <strong>⚠️ Atención requerida</strong>
        <p>
          <?php if ($pending_verifications > 0): ?>
            <?= $pending_verifications ?> verificación<?= $pending_verifications !== 1 ? 'es' : '' ?> pendiente<?= $pending_verifications !== 1 ? 's' : '' ?>.
          <?php endif; ?>
          <?php if ($reported_products > 0): ?>
            <?= $reported_products ?> reporte<?= $reported_products !== 1 ? 's' : '' ?> sin revisar.
          <?php endif; ?>
        </p>
      <?php else: ?>
        <strong>🟢 Todo al día</strong>
        <p>No hay alertas pendientes en este momento.</p>
      <?php endif; ?>
    </div>

  </aside>

  <!-- ══════════════════════════════════════
       MAIN CONTENT
  ══════════════════════════════════════ -->
  <main class="adm-main">

    <!-- Page Header -->
    <div class="adm-page-header">
      <div class="adm-page-header__title">
        <div class="adm-breadcrumb">
          <a href="./admin_dashboard.php">Inicio</a>
          <i class="bi bi-chevron-right"></i>
          <span>Dashboard</span>
        </div>
        <h1>Panel de administración</h1>
        <p>Bienvenido, <strong><?= htmlspecialchars($userName) ?></strong>. Resumen de hoy.</p>
      </div>
      <div class="adm-page-header__date">
        <i class="bi bi-calendar3"></i>
        <?= date('d \d\e F \d\e Y') ?>
        &nbsp;·&nbsp;
        <i class="bi bi-clock"></i>
        <span id="liveTime"></span>
      </div>
    </div>

    <!-- ══════════════════════════════════════
         STATISTICS CARDS
    ══════════════════════════════════════ -->
    <div class="adm-stats-grid">

      <!-- Total Usuarios -->
      <div class="adm-stat-card adm-stat-card--green">
        <div class="adm-stat-card__header">
          <div class="adm-stat-card__icon"><i class="bi bi-people-fill"></i></div>
          <?php if ($trend_users['dir'] !== 'neutral'): ?>
          <div class="adm-stat-card__trend <?= $trend_users['dir'] ?>">
            <i class="bi bi-arrow-<?= $trend_users['dir'] === 'up' ? 'up' : 'down' ?>-short"></i>
            <?= htmlspecialchars($trend_users['val']) ?>
          </div>
          <?php endif; ?>
        </div>
        <div class="adm-stat-card__num"><?= number_format($total_users) ?></div>
        <div class="adm-stat-card__label">Usuarios totales</div>
        <div class="adm-stat-card__sub">
          <i class="bi bi-person-plus"></i>
          <?= $users_this_month > 0 ? "+{$users_this_month} este mes" : "Sin registros este mes" ?>
        </div>
        <div class="adm-stat-card__bar">
          <div class="adm-stat-card__bar-fill" style="width:<?= $bar_users ?>%;background:var(--g500);"></div>
        </div>
      </div>

      <!-- Total Productos -->
      <div class="adm-stat-card adm-stat-card--blue">
        <div class="adm-stat-card__header">
          <div class="adm-stat-card__icon"><i class="bi bi-box-seam-fill"></i></div>
          <?php if ($trend_products['dir'] !== 'neutral'): ?>
          <div class="adm-stat-card__trend <?= $trend_products['dir'] ?>">
            <i class="bi bi-arrow-<?= $trend_products['dir'] === 'up' ? 'up' : 'down' ?>-short"></i>
            <?= htmlspecialchars($trend_products['val']) ?>
          </div>
          <?php endif; ?>
        </div>
        <div class="adm-stat-card__num"><?= number_format($total_products) ?></div>
        <div class="adm-stat-card__label">Productos publicados</div>
        <div class="adm-stat-card__sub">
          <i class="bi bi-check-circle"></i>
          <?= number_format($active_products) ?> disponibles
        </div>
        <div class="adm-stat-card__bar">
          <div class="adm-stat-card__bar-fill" style="width:<?= $bar_products ?>%;background:var(--blue500);"></div>
        </div>
      </div>

      <!-- Verificaciones pendientes -->
      <div class="adm-stat-card adm-stat-card--yellow">
        <div class="adm-stat-card__header">
          <div class="adm-stat-card__icon"><i class="bi bi-patch-check-fill"></i></div>
          <?php if ($pending_verifications > 0): ?>
          <div class="adm-stat-card__trend up" style="background:rgba(239,68,68,.1);color:var(--red500);">
            <i class="bi bi-exclamation-circle-fill"></i> Pendiente<?= $pending_verifications !== 1 ? 's' : '' ?>
          </div>
          <?php endif; ?>
        </div>
        <div class="adm-stat-card__num"><?= $pending_verifications ?></div>
        <div class="adm-stat-card__label">Verificaciones pendientes</div>
        <div class="adm-stat-card__sub">
          <i class="bi bi-hourglass-split"></i>
          <?= $verif_table ? 'Requieren revisión' : 'Módulo no configurado' ?>
        </div>
        <div class="adm-stat-card__bar">
          <div class="adm-stat-card__bar-fill" style="width:<?= $bar_verif ?>%;background:var(--y500);"></div>
        </div>
      </div>

      <!-- Productos reportados -->
      <div class="adm-stat-card adm-stat-card--red">
        <div class="adm-stat-card__header">
          <div class="adm-stat-card__icon"><i class="bi bi-flag-fill"></i></div>
        </div>
        <div class="adm-stat-card__num"><?= $reported_products ?></div>
        <div class="adm-stat-card__label">Productos reportados</div>
        <div class="adm-stat-card__sub">
          <i class="bi bi-shield-exclamation"></i>
          <?= $reported_products > 0 ? 'En cola de revisión' : 'Sin reportes pendientes' ?>
        </div>
        <div class="adm-stat-card__bar">
          <div class="adm-stat-card__bar-fill" style="width:<?= $bar_reports ?>%;background:var(--red500);"></div>
        </div>
      </div>

      <!-- Vendedores -->
      <div class="adm-stat-card adm-stat-card--purple">
        <div class="adm-stat-card__header">
          <div class="adm-stat-card__icon"><i class="bi bi-shop-fill"></i></div>
        </div>
        <div class="adm-stat-card__num"><?= number_format($total_sellers) ?></div>
        <div class="adm-stat-card__label">Vendedores registrados</div>
        <div class="adm-stat-card__sub">
          <i class="bi bi-graph-up"></i>
          <?= $dist_sellers_pct ?>% del total de usuarios
        </div>
        <div class="adm-stat-card__bar">
          <div class="adm-stat-card__bar-fill" style="width:<?= $bar_sellers ?>%;background:var(--purple500);"></div>
        </div>
      </div>

    </div>

    <!-- ══════════════════════════════════════
         QUICK ACTION BUTTONS
    ══════════════════════════════════════ -->
    <div class="adm-quick-row">
      <a href="./verificaciones.php" class="adm-quick-btn adm-quick-btn--green">
        <div class="adm-quick-btn__icon"><i class="bi bi-patch-check-fill"></i></div>
        <div class="adm-quick-btn__text">
          <strong>Revisar verificaciones</strong>
          <span>
            <?= $pending_verifications > 0
                ? "{$pending_verifications} solicitud" . ($pending_verifications !== 1 ? 'es' : '') . " pendiente" . ($pending_verifications !== 1 ? 's' : '')
                : "Sin solicitudes pendientes" ?>
          </span>
        </div>
        <i class="bi bi-arrow-right" style="margin-left:auto;opacity:.7;"></i>
      </a>

      <a href="#" class="adm-quick-btn adm-quick-btn--yellow">
        <div class="adm-quick-btn__icon"><i class="bi bi-flag-fill"></i></div>
        <div class="adm-quick-btn__text">
          <strong>Productos reportados</strong>
          <span>
            <?= $reported_products > 0
                ? "{$reported_products} en cola de revisión"
                : "Sin reportes pendientes" ?>
          </span>
        </div>
        <i class="bi bi-arrow-right" style="margin-left:auto;opacity:.7;"></i>
      </a>

      <a href="./gestion_usuarios.php" class="adm-quick-btn adm-quick-btn--dark">
        <div class="adm-quick-btn__icon"><i class="bi bi-people-fill"></i></div>
        <div class="adm-quick-btn__text">
          <strong>Gestionar usuarios</strong>
          <span><?= number_format($total_users) ?> usuarios · <?= number_format($blocked_users) ?> bloqueados</span>
        </div>
        <i class="bi bi-arrow-right" style="margin-left:auto;opacity:.7;"></i>
      </a>

      <a href="./gestion_admins.php" class="adm-quick-btn adm-quick-btn--outline-green">
        <div class="adm-quick-btn__icon"><i class="bi bi-shield-check"></i></div>
        <div class="adm-quick-btn__text">
          <strong>Gestión de admins</strong>
          <span><?= $total_admins ?> administrador<?= $total_admins !== 1 ? 'es' : '' ?> activo<?= $total_admins !== 1 ? 's' : '' ?></span>
        </div>
        <i class="bi bi-arrow-right" style="margin-left:auto;color:var(--g600);"></i>
      </a>
    </div>

    <!-- ══════════════════════════════════════
         CHARTS ROW
    ══════════════════════════════════════ -->
    <div class="adm-grid-2" style="margin-bottom:22px;">

      <!-- User Growth -->
      <div class="adm-panel">
        <div class="adm-panel__header">
          <div class="adm-panel__title">
            <span class="dot" style="background:var(--g500);"></span>
            Crecimiento de usuarios
          </div>
          <div style="display:flex;gap:8px;align-items:center;">
            <?php if ($trend_users['dir'] !== 'neutral'): ?>
              <span class="adm-panel__badge <?= $trend_users['dir'] === 'up' ? 'green' : 'red' ?>">
                <?= htmlspecialchars($trend_users['val']) ?> este mes
              </span>
            <?php endif; ?>
            <a href="./gestion_usuarios.php" class="adm-panel__link">Ver todos <i class="bi bi-arrow-right"></i></a>
          </div>
        </div>
        <div class="adm-chart-wrap">
          <canvas id="userGrowthChart"></canvas>
        </div>
      </div>

      <!-- Product Activity -->
      <div class="adm-panel">
        <div class="adm-panel__header">
          <div class="adm-panel__title">
            <span class="dot" style="background:var(--y500);"></span>
            Publicaciones de productos
          </div>
          <div style="display:flex;gap:8px;align-items:center;">
            <?php if ($trend_products['dir'] !== 'neutral'): ?>
              <span class="adm-panel__badge <?= $trend_products['dir'] === 'up' ? 'yellow' : 'red' ?>">
                <?= htmlspecialchars($trend_products['val']) ?> este mes
              </span>
            <?php endif; ?>
            <a href="./gestion_productos.php" class="adm-panel__link">Ver todos <i class="bi bi-arrow-right"></i></a>
          </div>
        </div>
        <div class="adm-chart-wrap">
          <canvas id="productActivityChart"></canvas>
        </div>
      </div>

    </div>

    <!-- ══════════════════════════════════════
         ACTIVITY + DISTRIBUTION
    ══════════════════════════════════════ -->
    <div class="adm-grid-3col">

      <!-- Recent Activity -->
      <div class="adm-panel">
        <div class="adm-panel__header">
          <div class="adm-panel__title">
            <span class="dot" style="background:var(--g500);"></span>
            Actividad reciente
          </div>
          <a href="#" class="adm-panel__link">Ver todo <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="adm-panel__body" style="padding-top:6px;padding-bottom:6px;">

          <?php if (empty($recent_activity)): ?>
            <div style="padding:32px 22px;text-align:center;color:var(--slate400);">
              <i class="bi bi-journal-x" style="font-size:2rem;display:block;margin-bottom:10px;"></i>
              <p style="font-size:.85rem;">No hay actividad registrada aún.</p>
            </div>

          <?php else: ?>
          <ul class="adm-activity-list">
            <?php foreach ($recent_activity as $act):
              $ago = time_ago($act['created_at']);
            ?>
            <li class="adm-activity-item">
              <div class="adm-activity-icon admin"><i class="bi bi-shield-fill-check"></i></div>
              <div class="adm-activity-content">
                <strong><?= htmlspecialchars($act['action']) ?></strong>
                <?php if (!empty($act['admin_name'])): ?>
                  <p>Por <?= htmlspecialchars($act['admin_name']) ?></p>
                <?php endif; ?>
              </div>
              <div class="adm-activity-time"><?= $ago ?></div>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>

        </div>
      </div>

      <!-- Right column -->
      <div style="display:flex;flex-direction:column;gap:22px;">

        <!-- Alerts -->
        <div class="adm-panel">
          <div class="adm-panel__header">
            <div class="adm-panel__title">
              <span class="dot" style="background:var(--red500);"></span>
              Alertas del sistema
            </div>
            <?php
            $alert_count = ($pending_verifications > 0 ? 1 : 0) + ($reported_products > 0 ? 1 : 0) + ($blocked_users > 0 ? 1 : 0);
            ?>
            <?php if ($alert_count > 0): ?>
              <span class="adm-panel__badge red"><?= $alert_count ?> activa<?= $alert_count !== 1 ? 's' : '' ?></span>
            <?php else: ?>
              <span class="adm-panel__badge green">Sin alertas</span>
            <?php endif; ?>
          </div>
          <div class="adm-alert-list">

            <?php if ($pending_verifications > 0): ?>
            <div class="adm-alert-item warn">
              <div class="adm-alert-icon"><i class="bi bi-patch-question-fill"></i></div>
              <div class="adm-alert-content">
                <strong><?= $pending_verifications ?> verificación<?= $pending_verifications !== 1 ? 'es' : '' ?> pendiente<?= $pending_verifications !== 1 ? 's' : '' ?></strong>
                <p>Esperando revisión de solicitudes.</p>
              </div>
              <a href="./verificaciones.php" class="adm-alert-cta">Ver</a>
            </div>
            <?php endif; ?>

            <?php if ($reported_products > 0): ?>
            <div class="adm-alert-item urgent">
              <div class="adm-alert-icon"><i class="bi bi-flag-fill"></i></div>
              <div class="adm-alert-content">
                <strong><?= $reported_products ?> producto<?= $reported_products !== 1 ? 's' : '' ?> reportado<?= $reported_products !== 1 ? 's' : '' ?></strong>
                <p>Requieren revisión.</p>
              </div>
              <a href="#" class="adm-alert-cta">Revisar</a>
            </div>
            <?php endif; ?>

            <?php if ($blocked_users > 0): ?>
            <div class="adm-alert-item info">
              <div class="adm-alert-icon"><i class="bi bi-person-slash"></i></div>
              <div class="adm-alert-content">
                <strong><?= number_format($blocked_users) ?> cuenta<?= $blocked_users !== 1 ? 's' : '' ?> bloqueada<?= $blocked_users !== 1 ? 's' : '' ?></strong>
                <p>Usuarios con acceso restringido.</p>
              </div>
              <a href="./gestion_usuarios.php" class="adm-alert-cta">Gestionar</a>
            </div>
            <?php endif; ?>

            <?php if ($alert_count === 0): ?>
            <div style="padding:28px 20px;text-align:center;color:var(--slate400);">
              <i class="bi bi-shield-check" style="font-size:1.8rem;color:var(--g500);display:block;margin-bottom:8px;"></i>
              <p style="font-size:.85rem;">Todo en orden. Sin alertas pendientes.</p>
            </div>
            <?php endif; ?>

          </div>
        </div>

        <!-- Distribution donut -->
        <div class="adm-panel">
          <div class="adm-panel__header">
            <div class="adm-panel__title">
              <span class="dot" style="background:var(--purple500);"></span>
              Distribución de usuarios
            </div>
          </div>
          <?php if ($total_users > 0): ?>
          <div class="adm-chart-wrap" style="padding-bottom:8px;">
            <canvas id="donutChart" style="max-height:160px;"></canvas>
          </div>
          <div class="adm-donut-grid">
            <div class="adm-donut-item">
              <div class="adm-donut-label"><span style="color:var(--g500);">●</span> Compradores</div>
              <div class="adm-donut-bar"><div class="adm-donut-fill" style="width:<?= $dist_buyers_pct ?>%;background:var(--g500);"></div></div>
              <div class="adm-donut-value"><?= $dist_buyers_pct ?>% · <?= number_format($dist_buyers) ?> usuarios</div>
            </div>
            <div class="adm-donut-item">
              <div class="adm-donut-label"><span style="color:var(--y500);">●</span> Vendedores</div>
              <div class="adm-donut-bar"><div class="adm-donut-fill" style="width:<?= $dist_sellers_pct ?>%;background:var(--y500);"></div></div>
              <div class="adm-donut-value"><?= $dist_sellers_pct ?>% · <?= number_format($dist_sellers) ?> usuarios</div>
            </div>
          </div>
          <?php else: ?>
          <div style="padding:28px 20px;text-align:center;color:var(--slate400);">
            <i class="bi bi-people" style="font-size:1.8rem;display:block;margin-bottom:8px;"></i>
            <p style="font-size:.85rem;">Sin usuarios registrados aún.</p>
          </div>
          <?php endif; ?>
        </div>

      </div>
    </div>

    <!-- ══════════════════════════════════════
         RECENT USERS TABLE
    ══════════════════════════════════════ -->
    <div class="adm-section-sep">Usuarios recientes</div>

    <div class="adm-panel" style="margin-bottom:28px;">
      <div class="adm-panel__header">
        <div class="adm-panel__title">
          <span class="dot" style="background:var(--blue500);"></span>
          Últimos usuarios registrados
        </div>
        <a href="./gestion_usuarios.php" class="adm-panel__link">Ver todos <i class="bi bi-arrow-right"></i></a>
      </div>

      <?php if (empty($recent_users)): ?>
        <div style="padding:40px 24px;text-align:center;color:var(--slate400);">
          <i class="bi bi-people" style="font-size:2rem;display:block;margin-bottom:10px;"></i>
          <p style="font-size:.88rem;">No hay usuarios registrados aún.</p>
        </div>
      <?php else: ?>
      <div class="adm-table-wrap">
        <table class="adm-table">
          <thead>
            <tr>
              <th>Usuario</th>
              <th>Correo</th>
              <th>Rol</th>
              <th>Estado</th>
              <th>Registro</th>
              <th>Productos</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent_users as $u):
              /* Initials */
              $parts = array_filter(explode(' ', trim($u['full_name'] ?? '')));
              $initials = '';
              foreach ($parts as $p) $initials .= strtoupper($p[0]);
              $initials = substr($initials ?: 'U', 0, 2);

              /* Role tag */
              $roleTag = match($u['role'] ?? 'user') {
                'seller' => '<span class="adm-tag yellow"><i class="bi bi-shop"></i> Vendedor</span>',
                'admin','super_admin' => '<span class="adm-tag green"><i class="bi bi-shield-check"></i> Admin</span>',
                default  => '<span class="adm-tag blue"><i class="bi bi-bag"></i> Comprador</span>',
              };

              /* Status tag */
              $statusTag = match($u['status'] ?? 'active') {
                'active'    => '<span class="adm-tag green">Activo</span>',
                'blocked'   => '<span class="adm-tag red">Bloqueado</span>',
                'suspended' => '<span class="adm-tag red">Suspendido</span>',
                default     => '<span class="adm-tag gray">' . htmlspecialchars($u['status']) . '</span>',
              };

              $regDate = date('d/m/Y', strtotime($u['created_at']));
            ?>
            <tr>
              <td style="display:flex;align-items:center;gap:10px;">
                <div class="adm-avatar-sm"><?= htmlspecialchars($initials) ?></div>
                <strong><?= htmlspecialchars($u['full_name']) ?></strong>
              </td>
              <td><small><?= htmlspecialchars($u['email']) ?></small></td>
              <td><?= $roleTag ?></td>
              <td><?= $statusTag ?></td>
              <td><small><?= $regDate ?></small></td>
              <td>
                <?php if ((int)$u['product_count'] > 0): ?>
                  <span class="adm-tag gray"><i class="bi bi-box"></i> <?= (int)$u['product_count'] ?></span>
                <?php else: ?>
                  <span style="color:#94a3b8;font-size:.8rem;">—</span>
                <?php endif; ?>
              </td>
              <td style="display:flex;gap:6px;">
                <a href="./gestion_usuarios.php" class="adm-btn-icon green" title="Ver en gestión"><i class="bi bi-eye"></i></a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- ══════════════════════════════════════
         VERIFICATIONS + ADMINS
    ══════════════════════════════════════ -->
    <div class="adm-grid-2">

      <!-- Verifications mini-list -->
      <div class="adm-panel">
        <div class="adm-panel__header">
          <div class="adm-panel__title">
            <span class="dot" style="background:var(--y500);"></span>
            Solicitudes de verificación
          </div>
          <?php if ($pending_verifications > 0): ?>
            <span class="adm-panel__badge yellow"><?= $pending_verifications ?> pendiente<?= $pending_verifications !== 1 ? 's' : '' ?></span>
          <?php endif; ?>
        </div>

        <?php if (empty($recent_verifications)): ?>
          <div style="padding:36px 22px;text-align:center;color:var(--slate400);">
            <i class="bi bi-patch-check" style="font-size:2rem;color:var(--g400);display:block;margin-bottom:10px;"></i>
            <p style="font-size:.85rem;">
              <?= $verif_table ? 'No hay solicitudes pendientes.' : 'Módulo de verificaciones no configurado aún.' ?>
            </p>
          </div>
        <?php else: ?>
          <div class="adm-panel__body" style="padding:0;">
            <?php foreach ($recent_verifications as $v):
              $parts = array_filter(explode(' ', trim($v['full_name'] ?? '')));
              $ini = '';
              foreach ($parts as $p) $ini .= strtoupper($p[0]);
              $ini = substr($ini ?: 'V', 0, 2);
              $waitDays = max(0, (int)floor((time() - strtotime($v['created_at'])) / 86400));
              $waitStr  = $waitDays === 0 ? 'Hoy' : ($waitDays === 1 ? '1 día' : "{$waitDays} días");
            ?>
            <div style="display:flex;align-items:center;gap:12px;padding:12px 22px;border-bottom:1px solid var(--slate100);">
              <div class="adm-avatar-sm" style="background:linear-gradient(135deg,var(--y600),var(--g600));"><?= htmlspecialchars($ini) ?></div>
              <div style="flex:1;min-width:0;">
                <div style="font-size:.87rem;font-weight:700;color:var(--slate800);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  <?= htmlspecialchars($v['full_name'] ?? 'Usuario #' . $v['user_id']) ?>
                </div>
                <div style="font-size:.77rem;color:var(--slate500);">
                  Espera: <span style="color:var(--y600);font-weight:600;"><?= $waitStr ?></span>
                </div>
              </div>
              <a href="./verificaciones.php" style="font-size:.78rem;color:var(--g600);font-weight:700;text-decoration:none;padding:4px 10px;border:1.5px solid var(--g300);border-radius:6px;transition:.15s;" title="Revisar">Revisar</a>
            </div>
            <?php endforeach; ?>
            <?php if ($pending_verifications > count($recent_verifications)): ?>
            <div style="padding:14px 22px;">
              <a href="./verificaciones.php" style="display:flex;align-items:center;justify-content:center;gap:6px;padding:10px;background:var(--y50);border:1.5px solid var(--y200);border-radius:var(--r-md);color:var(--y600);font-size:.85rem;font-weight:700;text-decoration:none;">
                <i class="bi bi-arrow-down-circle"></i>
                Ver <?= $pending_verifications - count($recent_verifications) ?> más
              </a>
            </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Admins mini-list -->
      <div class="adm-panel">
        <div class="adm-panel__header">
          <div class="adm-panel__title">
            <span class="dot" style="background:var(--g500);"></span>
            Administradores activos
          </div>
          <a href="./gestion_admins.php" class="adm-panel__link">Gestionar <i class="bi bi-arrow-right"></i></a>
        </div>
        <?php
        $admins_list = [];
        $ra = @mysqli_query($conn, "
            SELECT id, full_name, email, role, status, last_login
            FROM users
            WHERE role IN ('admin','super_admin') AND (deleted_at IS NULL OR deleted_at IS NULL)
            ORDER BY role DESC, full_name ASC
            LIMIT 6
        ");
        if ($ra) while ($arow = mysqli_fetch_assoc($ra)) $admins_list[] = $arow;
        ?>
        <?php if (empty($admins_list)): ?>
          <div style="padding:36px 22px;text-align:center;color:var(--slate400);">
            <i class="bi bi-shield" style="font-size:2rem;color:var(--slate300);display:block;margin-bottom:10px;"></i>
            <p style="font-size:.85rem;">No hay administradores registrados.</p>
          </div>
        <?php else: ?>
          <div class="adm-panel__body" style="padding:0;">
            <?php foreach ($admins_list as $adm):
              $aparts = array_filter(explode(' ', trim($adm['full_name'] ?? '')));
              $aini = '';
              foreach ($aparts as $p) $aini .= strtoupper($p[0]);
              $aini = substr($aini ?: 'A', 0, 2);
              $isSuper = ($adm['role'] === 'super_admin');
              $avatarBg = $isSuper
                ? 'background:linear-gradient(135deg,var(--y600),var(--y400));color:var(--slate900);'
                : 'background:linear-gradient(135deg,var(--g700),var(--g500));';
              $loginText = !empty($adm['last_login'])
                ? 'Último: ' . date('d/m/Y', strtotime($adm['last_login']))
                : 'Sin accesos registrados';
            ?>
            <div style="display:flex;align-items:center;gap:12px;padding:12px 22px;border-bottom:1px solid var(--slate100);">
              <div class="adm-avatar-sm" style="<?= $avatarBg ?>"><?= htmlspecialchars($aini) ?></div>
              <div style="flex:1;min-width:0;">
                <div style="font-size:.87rem;font-weight:700;color:var(--slate800);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  <?= htmlspecialchars($adm['full_name']) ?>
                  <?php if ($adm['id'] == ($user['id'] ?? -1)): ?>
                    <span style="font-size:.7rem;color:var(--g600);font-weight:600;">(tú)</span>
                  <?php endif; ?>
                </div>
                <div style="font-size:.77rem;color:var(--slate500);">
                  <span style="color:<?= $isSuper ? 'var(--y600)' : 'var(--g600)' ?>;font-weight:600;">
                    <?= $isSuper ? 'Super Admin' : 'Admin' ?>
                  </span>
                  · <?= $loginText ?>
                </div>
              </div>
              <span style="width:8px;height:8px;border-radius:50%;background:<?= $adm['status']==='active'?'var(--g500)':'var(--slate300)' ?>;flex-shrink:0;" title="<?= $adm['status']==='active'?'Activo':'Inactivo' ?>"></span>
            </div>
            <?php endforeach; ?>
            <?php if ($total_admins > count($admins_list)): ?>
            <div style="padding:14px 22px;">
              <a href="./gestion_admins.php" style="display:flex;align-items:center;justify-content:center;gap:6px;padding:10px;background:var(--g50);border:1.5px solid var(--g100);border-radius:var(--r-md);color:var(--g700);font-size:.85rem;font-weight:700;text-decoration:none;">
                <i class="bi bi-shield-check"></i> Ver todos (<?= $total_admins ?>)
              </a>
            </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>

    </div>

  </main>
</div>

<!-- ══════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════ -->
<script>
/* ── Dropdowns ── */
function toggleDropdown(id) {
  document.getElementById(id).classList.toggle('open');
}
document.addEventListener('click', function(e) {
  if (!e.target.closest('#qaMenu'))     document.getElementById('qaDropdown')?.classList.remove('open');
  if (!e.target.closest('#avatarMenu')) document.getElementById('avatarDropdown')?.classList.remove('open');
});

/* ── Live clock ── */
(function tick() {
  const el = document.getElementById('liveTime');
  if (el) el.textContent = new Date().toLocaleTimeString('es-CO', { hour:'2-digit', minute:'2-digit' });
  setTimeout(tick, 30000);
})();

/* ── Charts ── */
window.addEventListener('load', function () {
  const green  = '#22c55e';
  const yellow = '#eab308';
  const baseFont = { family: 'Outfit', size: 12 };

  /* PHP-injected real data */
  const labels   = <?= $json_labels ?>;
  const usersD   = <?= $json_users ?>;
  const sellersD = <?= $json_sellers ?>;
  const prodsD   = <?= $json_products ?>;
  const donutD   = <?= $json_donut ?>;

  /* ── User Growth — Line ── */
  const ctxU = document.getElementById('userGrowthChart');
  if (ctxU) {
    new Chart(ctxU, {
      type: 'line',
      data: {
        labels,
        datasets: [
          {
            label: 'Compradores',
            data: usersD,
            borderColor: green,
            backgroundColor: 'rgba(34,197,94,.12)',
            borderWidth: 2.5,
            tension: .4,
            fill: true,
            pointBackgroundColor: green,
            pointRadius: 4,
            pointHoverRadius: 7,
          },
          {
            label: 'Vendedores',
            data: sellersD,
            borderColor: yellow,
            backgroundColor: 'rgba(234,179,8,.08)',
            borderWidth: 2,
            tension: .4,
            fill: true,
            pointBackgroundColor: yellow,
            pointRadius: 3,
            pointHoverRadius: 6,
            borderDash: [4, 3],
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: { labels: { font: baseFont, color: '#64748b', boxWidth: 12 } },
          tooltip: { mode: 'index', intersect: false },
        },
        scales: {
          x: { grid: { color: '#f1f5f9' }, ticks: { font: baseFont, color: '#94a3b8' } },
          y: {
            grid: { color: '#f1f5f9' },
            ticks: { font: baseFont, color: '#94a3b8', precision: 0 },
            beginAtZero: true,
          },
        },
      },
    });
  }

  /* ── Product Publications — Bar ── */
  const ctxP = document.getElementById('productActivityChart');
  if (ctxP) {
    new Chart(ctxP, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Publicaciones',
          data: prodsD,
          backgroundColor: 'rgba(234,179,8,.85)',
          borderRadius: 6,
          borderSkipped: false,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: { labels: { font: baseFont, color: '#64748b', boxWidth: 12 } },
          tooltip: { mode: 'index', intersect: false },
        },
        scales: {
          x: { grid: { display: false }, ticks: { font: baseFont, color: '#94a3b8' } },
          y: {
            grid: { color: '#f1f5f9' },
            ticks: { font: baseFont, color: '#94a3b8', precision: 0 },
            beginAtZero: true,
          },
        },
      },
    });
  }

  /* ── User Distribution — Doughnut ── */
  const ctxD = document.getElementById('donutChart');
  if (ctxD && donutD.some(v => v > 0)) {
    new Chart(ctxD, {
      type: 'doughnut',
      data: {
        labels: ['Compradores', 'Vendedores'],
        datasets: [{
          data: donutD,
          backgroundColor: [green, yellow],
          borderWidth: 3,
          borderColor: '#fff',
          hoverOffset: 8,
        }],
      },
      options: {
        responsive: true,
        cutout: '68%',
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed.toLocaleString('es-CO')} usuarios` } },
        },
      },
    });
  }
});
</script>
</body>
</html>
}

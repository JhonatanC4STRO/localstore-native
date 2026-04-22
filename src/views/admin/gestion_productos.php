<?php
/**
 * Moderación de Productos — ComercioLocal Admin
 * Datos reales desde la BD (tiendalocal).
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

/* ══════════════════════════════════════════════════════════════════
   HELPERS
══════════════════════════════════════════════════════════════════ */
function tbl_exists(mysqli $c, string $t): bool {
    $r = @mysqli_query($c, "SHOW TABLES LIKE '" . mysqli_real_escape_string($c, $t) . "'");
    return ($r && mysqli_num_rows($r) > 0);
}
function fmt_cop($n)   { return '$' . number_format((float)$n, 0, ',', '.'); }
function fmt_date($dt) { return $dt ? strftime_es($dt) : '—'; }
function strftime_es($dt) {
    $t = is_numeric($dt) ? (int)$dt : strtotime($dt);
    if ($t === false) return '—';
    $meses = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    return date('d', $t) . ' ' . $meses[(int)date('n', $t) - 1] . ' ' . date('Y', $t);
}
function initial_of(?string $s): string {
    return $s ? strtoupper(mb_substr($s, 0, 1)) : '—';
}

/* ══════════════════════════════════════════════════════════════════
   DETECCIÓN DE MÓDULO DE REPORTES
══════════════════════════════════════════════════════════════════ */
$reports_table = null;
foreach (['product_reports', 'reports', 'product_flags'] as $t) {
    if (tbl_exists($conn, $t)) { $reports_table = $t; break; }
}

/* ══════════════════════════════════════════════════════════════════
   TAB: REPORTADOS
══════════════════════════════════════════════════════════════════ */
$reportados = [];
$reportados_count = 0;
if ($reports_table) {
    $r = @mysqli_query($conn, "
        SELECT r.id, r.product_id, r.reason, r.created_at,
               p.title, p.price,
               u.full_name AS seller_name,
               c.name AS category_name,
               rp.full_name AS reporter_name,
               (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.id ASC LIMIT 1) AS thumb
        FROM `$reports_table` r
        LEFT JOIN products p ON p.id = r.product_id
        LEFT JOIN users u    ON u.id = p.user_id
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN users rp   ON rp.id = r.reporter_id
        ORDER BY r.created_at DESC
    ");
    if ($r) while ($row = mysqli_fetch_assoc($r)) $reportados[] = $row;
    $reportados_count = count($reportados);
}

/* ══════════════════════════════════════════════════════════════════
   TAB: TODOS LOS PRODUCTOS (admin_status != deleted)
══════════════════════════════════════════════════════════════════ */
$todos = [];
$r = @mysqli_query($conn, "
    SELECT p.id, p.title, p.price, p.status AS product_status, p.admin_status, p.created_at,
           u.full_name AS seller_name, u.city,
           c.name AS category_name,
           (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.id ASC LIMIT 1) AS thumb,
           (SELECT COUNT(*) FROM favorites f WHERE f.product_id = p.id) AS fav_count
    FROM products p
    LEFT JOIN users u ON u.id = p.user_id
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.admin_status != 'deleted'
    ORDER BY p.created_at DESC
");
if ($r) while ($row = mysqli_fetch_assoc($r)) $todos[] = $row;

/* Categorías y ciudades para los filtros */
$filter_categorias = [];
$r = @mysqli_query($conn, "SELECT DISTINCT c.name FROM categories c INNER JOIN products p ON p.category_id = c.id ORDER BY c.name ASC");
if ($r) while ($row = mysqli_fetch_assoc($r)) $filter_categorias[] = $row['name'];

$filter_ciudades = [];
$r = @mysqli_query($conn, "SELECT DISTINCT u.city FROM users u INNER JOIN products p ON p.user_id = u.id WHERE u.city IS NOT NULL AND u.city != '' ORDER BY u.city ASC");
if ($r) while ($row = mysqli_fetch_assoc($r)) $filter_ciudades[] = $row['city'];

/* ══════════════════════════════════════════════════════════════════
   TAB: DESTACADOS (product_promotions activas)
══════════════════════════════════════════════════════════════════ */
$destacados = [];
$r = @mysqli_query($conn, "
    SELECT pp.id AS promo_id, pp.product_id, pp.plan_type, pp.start_date, pp.end_date, pp.status AS promo_status, pp.price AS promo_price,
           p.title, p.price,
           u.full_name AS seller_name,
           c.name AS category_name,
           (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.id ASC LIMIT 1) AS thumb
    FROM product_promotions pp
    LEFT JOIN products p ON p.id = pp.product_id
    LEFT JOIN users u    ON u.id = p.user_id
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE pp.status = 'active'
    ORDER BY pp.end_date ASC
");
if ($r) while ($row = mysqli_fetch_assoc($r)) $destacados[] = $row;

/* ══════════════════════════════════════════════════════════════════
   TAB: ELIMINADOS
══════════════════════════════════════════════════════════════════ */
$eliminados = [];
$r = @mysqli_query($conn, "
    SELECT p.id, p.title, p.price, p.updated_at, p.created_at,
           u.full_name AS seller_name,
           c.name AS category_name,
           (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.id ASC LIMIT 1) AS thumb
    FROM products p
    LEFT JOIN users u ON u.id = p.user_id
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.admin_status = 'deleted'
    ORDER BY p.updated_at DESC, p.created_at DESC
");
if ($r) while ($row = mysqli_fetch_assoc($r)) $eliminados[] = $row;

/* ══════════════════════════════════════════════════════════════════
   ESTADÍSTICAS
══════════════════════════════════════════════════════════════════ */
// Productos activos
$stat_active = 0;
$r = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM products WHERE admin_status = 'active' AND status = 'disponible'");
if ($r) $stat_active = (int) mysqli_fetch_assoc($r)['c'];

// Publicados hoy
$stat_today = 0;
$r = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM products WHERE DATE(created_at) = CURDATE()");
if ($r) $stat_today = (int) mysqli_fetch_assoc($r)['c'];

// Ingresos por promociones este mes
$promo_revenue = 0.0;
$r = @mysqli_query($conn, "
    SELECT COALESCE(SUM(price), 0) AS total
    FROM product_promotions
    WHERE MONTH(created_at) = MONTH(NOW())
      AND YEAR(created_at)  = YEAR(NOW())
      AND status IN ('active','expired')
");
if ($r) $promo_revenue = (float) mysqli_fetch_assoc($r)['total'];

// Top 3 categorías
$top_categorias = [];
$r = @mysqli_query($conn, "
    SELECT c.name, COUNT(*) AS cnt
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.admin_status != 'deleted' AND c.name IS NOT NULL
    GROUP BY p.category_id
    ORDER BY cnt DESC
    LIMIT 3
");
if ($r) while ($row = mysqli_fetch_assoc($r)) $top_categorias[] = $row;
$top_max = !empty($top_categorias) ? max(array_map(fn($c) => (int)$c['cnt'], $top_categorias)) : 1;

$mes_actual = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'][(int)date('n') - 1] . ' ' . date('Y');

/* Plan label map */
$plan_label = ['basic' => 'Básico', 'recommended' => 'Recomendado', 'premium' => 'Premium'];

/* Base path para imágenes del producto */
$img_base = '../../../public/uploads/products/';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Productos — ComercioLocal Admin</title>

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

    /* ══ TOPBAR (igual que dashboard) ══ */
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
    .page-head h1{font-size:1.8rem;font-weight:700;margin-bottom:4px;display:inline-flex;align-items:center;gap:12px;flex-wrap:wrap;}
    .page-head p{margin:0;color:var(--ink3);font-size:.9rem;}
    .header-badge{display:inline-flex;align-items:center;gap:6px;background:var(--red-soft);color:var(--red);border:1px solid #FCA5A5;font-size:.72rem;font-weight:700;padding:5px 12px;border-radius:999px;letter-spacing:.02em;}
    .header-badge .dot{width:6px;height:6px;border-radius:50%;background:var(--red);animation:pulse 1.6s ease-in-out infinite;}
    @keyframes pulse{0%,100%{opacity:1;}50%{opacity:.45;}}

    /* ══ LAYOUT 2 COL ══ */
    .layout{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:20px;align-items:start;}

    /* ══ CARD ══ */
    .card{background:var(--white);border-radius:16px;box-shadow:var(--shadow);overflow:hidden;}

    /* ══ TABS ══ */
    .tabs{display:flex;gap:2px;padding:6px 14px 0;border-bottom:1px solid var(--bord-soft);overflow-x:auto;}
    .tab{padding:12px 16px;background:transparent;border:0;border-bottom:2px solid transparent;font-size:.86rem;font-weight:600;color:var(--ink3);cursor:pointer;display:inline-flex;align-items:center;gap:8px;white-space:nowrap;transition:.15s;}
    .tab:hover{color:var(--primary);}
    .tab.active{color:var(--primary);border-bottom-color:var(--primary);}
    .tab-badge{background:var(--red);color:var(--white);font-size:.66rem;font-weight:800;min-width:18px;height:18px;padding:0 5px;border-radius:9px;display:inline-flex;align-items:center;justify-content:center;}
    .tab.active .tab-badge{background:var(--red);}

    .tab-pane{display:none;}
    .tab-pane.active{display:block;animation:fade .2s ease-out;}
    @keyframes fade{from{opacity:0;transform:translateY(4px);}to{opacity:1;transform:none;}}

    /* ══ BANNER de aviso arriba de tabla ══ */
    .pane-banner{padding:12px 20px;display:flex;align-items:center;gap:10px;font-size:.82rem;border-bottom:1px solid var(--bord-soft);}
    .pane-banner--danger{background:var(--red-soft);color:#B91C1C;}
    .pane-banner--accent{background:var(--yellow-soft);color:#92400E;}
    .pane-banner--mute{background:var(--bg);color:var(--ink2);}
    .pane-banner i{font-size:1.05rem;}

    /* ══ FILTROS ══ */
    .filters{padding:18px 20px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;border-bottom:1px solid var(--bord-soft);}
    .filters .search{position:relative;flex:1;min-width:240px;}
    .filters .search i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--ink4);}
    .filters .search input{width:100%;padding:10px 12px 10px 36px;border:1px solid var(--bord);border-radius:10px;background:var(--bg);font-size:.88rem;font-family:inherit;outline:none;transition:.15s;}
    .filters .search input:focus{background:var(--white);border-color:var(--primary);box-shadow:0 0 0 3px rgba(11,46,23,.07);}
    .filters select{padding:10px 34px 10px 12px;border:1px solid var(--bord);border-radius:10px;background:var(--white) url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2364748B' viewBox='0 0 16 16'%3e%3cpath d='M3.204 5h9.592L8 10.481 3.204 5zm-.753.659 4.796 5.48a1 1 0 0 0 1.506 0l4.796-5.48c.566-.647.106-1.659-.753-1.659H3.204a1 1 0 0 0-.753 1.659z'/%3e%3c/svg%3e") no-repeat right 12px center;appearance:none;font-size:.84rem;font-weight:500;color:var(--ink2);cursor:pointer;font-family:inherit;}
    .filters select:focus{outline:none;border-color:var(--primary);}
    .filters .btn-csv{margin-left:auto;background:var(--primary);color:var(--white);border:0;padding:10px 16px;border-radius:10px;font-weight:600;font-size:.84rem;display:inline-flex;align-items:center;gap:8px;cursor:pointer;transition:.15s;}
    .filters .btn-csv:hover{background:var(--primary-2);}

    /* ══ TABLE ══ */
    .table-wrap{position:relative;overflow-x:auto;}
    .u-table{width:100%;border-collapse:collapse;font-size:.86rem;}
    .u-table thead th{text-align:left;font-weight:600;color:var(--ink3);font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;padding:12px 18px;background:var(--bg);border-bottom:1px solid var(--bord);white-space:nowrap;}
    .u-table tbody td{padding:14px 18px;border-bottom:1px solid var(--bord-soft);vertical-align:middle;}
    .u-table tbody tr:hover{background:var(--bg);}
    .u-table tbody tr:last-child td{border-bottom:none;}
    .u-table .num{text-align:right;font-variant-numeric:tabular-nums;}
    .u-table .num-head{text-align:right;}

    /* ══ Celda producto (thumb + título) ══ */
    .prod-cell{display:flex;align-items:center;gap:12px;min-width:220px;}
    .prod-thumb{width:46px;height:46px;border-radius:10px;background:var(--bg);border:1px solid var(--bord);overflow:hidden;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
    .prod-thumb img{width:100%;height:100%;object-fit:cover;}
    .prod-thumb i{color:var(--ink4);font-size:1.15rem;}
    .prod-info strong{display:block;color:var(--ink);font-weight:600;font-size:.88rem;line-height:1.25;max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
    .prod-info small{color:var(--ink4);font-size:.72rem;}

    /* ══ Celda vendedor (avatar + nombre) ══ */
    .seller-cell{display:flex;align-items:center;gap:8px;min-width:140px;}
    .seller-av{width:30px;height:30px;border-radius:50%;background:var(--primary);color:var(--accent);font-weight:700;display:flex;align-items:center;justify-content:center;font-size:.75rem;flex-shrink:0;}
    .seller-cell strong{color:var(--ink);font-weight:600;font-size:.84rem;display:block;line-height:1.2;}
    .seller-cell small{color:var(--ink4);font-size:.7rem;}

    .cell-mute{color:var(--ink2);font-size:.84rem;}
    .cell-date{color:var(--ink3);font-size:.8rem;white-space:nowrap;}
    .cell-price{color:var(--primary);font-weight:700;font-size:.9rem;white-space:nowrap;}

    /* ══ Badges ══ */
    .badge{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:999px;font-size:.7rem;font-weight:600;white-space:nowrap;}
    .b-activo   {background:var(--green-soft); color:var(--primary);}
    .b-pausado  {background:var(--yellow-soft);color:#B45309;}
    .b-vendido  {background:#F1F5F9;           color:var(--ink3);}
    .b-eliminado{background:var(--red-soft);   color:var(--red);}
    .b-reason   {background:var(--red-soft);   color:var(--red);}
    .b-premium  {background:linear-gradient(135deg,#F5A81C,#FFC154);color:var(--primary);}
    .b-rec      {background:var(--green-soft); color:var(--primary);}
    .b-basico   {background:#F1F5F9;           color:var(--ink2);}

    /* ══ Progress (días restantes) ══ */
    .prog{display:flex;align-items:center;gap:10px;min-width:140px;}
    .prog-track{flex:1;height:6px;background:var(--bord);border-radius:999px;overflow:hidden;}
    .prog-bar{height:100%;border-radius:999px;transition:width .3s;}
    .prog-bar.ok{background:var(--primary);}
    .prog-bar.warn{background:var(--accent);}
    .prog-bar.danger{background:var(--red);}
    .prog-label{font-size:.74rem;font-weight:700;color:var(--ink2);white-space:nowrap;}
    .prog-label.danger{color:var(--red);}

    /* ══ Acciones ══ */
    .actions-cell{display:flex;gap:6px;justify-content:flex-end;}
    .ico-btn{width:32px;height:32px;border-radius:8px;border:1px solid var(--bord);background:var(--white);color:var(--ink2);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:.15s;font-size:.88rem;}
    .ico-btn:hover{border-color:var(--primary);color:var(--primary);}
    .ico-btn.danger{color:var(--red);border-color:#FCA5A5;}
    .ico-btn.danger:hover{background:var(--red-soft);color:var(--red);}
    .ico-btn.ok{color:var(--primary);}
    .ico-btn.ok:hover{background:var(--green-soft);}

    .empty{padding:56px 24px;text-align:center;color:var(--ink4);}
    .empty i{font-size:2.6rem;color:#CBD5E1;display:block;margin-bottom:10px;}
    .empty strong{color:var(--ink2);display:block;margin-bottom:4px;}

    /* ══ SIDEBAR STATS ══ */
    .sbar{display:flex;flex-direction:column;gap:14px;}
    .sbar-title{font-family:'Syne',sans-serif;font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--ink3);padding:0 4px;margin-bottom:2px;}
    .stat-mini{background:var(--white);border-radius:14px;padding:18px;box-shadow:var(--shadow);position:relative;overflow:hidden;}
    .stat-mini__icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;margin-bottom:12px;}
    .stat-mini__num{font-family:'Syne',sans-serif;font-size:1.75rem;font-weight:800;color:var(--primary);line-height:1;margin-bottom:4px;}
    .stat-mini__lbl{font-size:.8rem;color:var(--ink3);font-weight:500;}
    .stat-mini__delta{font-size:.7rem;margin-top:8px;font-weight:600;display:inline-flex;align-items:center;gap:3px;color:var(--primary);}
    .stat-mini--active .stat-mini__icon{background:var(--green-soft);color:var(--primary);}
    .stat-mini--today  .stat-mini__icon{background:var(--blue-soft);color:#1E40AF;}

    /* Stat destacada (ingresos) */
    .stat-feat{background:var(--primary);color:var(--white);border-radius:14px;padding:20px;position:relative;overflow:hidden;box-shadow:var(--shadow);}
    .stat-feat::after{content:"";position:absolute;top:-30px;right:-30px;width:110px;height:110px;border-radius:50%;background:rgba(245,168,28,.18);}
    .stat-feat::before{content:"";position:absolute;bottom:-20px;right:20px;width:60px;height:60px;border-radius:50%;background:rgba(245,168,28,.1);}
    .stat-feat__icon{width:38px;height:38px;border-radius:10px;background:rgba(245,168,28,.2);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:1rem;margin-bottom:12px;position:relative;z-index:1;}
    .stat-feat__num{font-family:'Syne',sans-serif;font-size:1.55rem;font-weight:800;color:var(--accent);line-height:1;margin-bottom:4px;position:relative;z-index:1;}
    .stat-feat__lbl{font-size:.78rem;color:rgba(255,255,255,.75);font-weight:500;position:relative;z-index:1;}
    .stat-feat__meta{margin-top:14px;padding-top:12px;border-top:1px solid rgba(255,255,255,.14);font-size:.72rem;color:rgba(255,255,255,.7);display:flex;justify-content:space-between;position:relative;z-index:1;}
    .stat-feat__meta strong{color:var(--white);font-weight:600;}

    /* Top categorías */
    .topcat{background:var(--white);border-radius:14px;padding:18px;box-shadow:var(--shadow);}
    .topcat h3{font-size:.92rem;font-weight:700;margin-bottom:14px;}
    .topcat-list{display:flex;flex-direction:column;gap:12px;}
    .topcat-item .row{display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;}
    .topcat-item .row strong{font-size:.84rem;color:var(--ink);display:inline-flex;align-items:center;gap:8px;font-weight:600;}
    .topcat-rank{width:22px;height:22px;border-radius:6px;background:var(--bg);color:var(--primary);font-size:.68rem;font-weight:800;display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;}
    .topcat-item:nth-child(1) .topcat-rank{background:var(--primary);color:var(--accent);}
    .topcat-item:nth-child(2) .topcat-rank{background:var(--yellow-soft);color:#B45309;}
    .topcat-item:nth-child(3) .topcat-rank{background:var(--green-soft);color:var(--primary);}
    .topcat-item .row span{font-size:.78rem;font-weight:700;color:var(--ink2);font-variant-numeric:tabular-nums;}
    .topcat-bar{height:5px;border-radius:999px;background:var(--bord);overflow:hidden;}
    .topcat-bar > div{height:100%;border-radius:999px;background:var(--primary);}
    .topcat-item:nth-child(2) .topcat-bar > div{background:var(--accent);}
    .topcat-item:nth-child(3) .topcat-bar > div{background:#94A3B8;}

    /* ══ MODAL ══ */
    .modal-bg{position:fixed;inset:0;background:rgba(11,46,23,.55);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center;z-index:100;padding:16px;}
    .modal-bg.open{display:flex;}
    .modal{background:var(--white);border-radius:18px;width:100%;max-width:460px;box-shadow:var(--shadow-lg);overflow:hidden;animation:modalIn .22s ease-out;}
    @keyframes modalIn{from{opacity:0;transform:translateY(12px) scale(.97);}to{opacity:1;transform:none;}}
    .modal-head{padding:22px 24px 6px;display:flex;gap:14px;align-items:flex-start;}
    .modal-ico{width:44px;height:44px;border-radius:12px;background:var(--red-soft);color:var(--red);display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;}
    .modal-head h2{font-size:1.15rem;font-weight:700;margin-bottom:4px;}
    .modal-head p{margin:0;font-size:.85rem;color:var(--ink3);}
    .modal-head p strong{color:var(--ink);}
    .modal-body{padding:6px 24px 4px;}
    .modal-warn{background:var(--red-soft);color:#991B1B;border:1px solid #FCA5A5;border-radius:10px;padding:10px 12px;display:flex;gap:8px;font-size:.78rem;}
    .modal-warn i{color:var(--red);flex-shrink:0;margin-top:1px;}
    .modal-warn strong{display:block;font-weight:700;margin-bottom:2px;color:#991B1B;}
    .modal-actions{padding:18px 24px 22px;display:flex;gap:10px;justify-content:flex-end;}
    .btn-secondary{background:var(--bg);color:var(--ink2);border:1px solid var(--bord);padding:10px 16px;border-radius:10px;font-weight:600;font-size:.85rem;cursor:pointer;transition:.15s;}
    .btn-secondary:hover{background:var(--bord-soft);}
    .btn-danger{background:var(--red);color:var(--white);border:0;padding:10px 18px;border-radius:10px;font-weight:600;font-size:.85rem;cursor:pointer;transition:.15s;display:inline-flex;align-items:center;gap:8px;}
    .btn-danger:hover{background:#C62828;}

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
      .sbar > *{min-width:220px;}
      .sbar-title{display:none;}
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
      <a class="tb-link" href="./gestion_usuarios.php"><i class="bi bi-people"></i> Usuarios</a>
      <a class="tb-link" href="./gestion_admins.php"><i class="bi bi-shield-fill-check"></i> Administradores</a>
      <a class="tb-link" href="./verificaciones.php"><i class="bi bi-patch-check"></i> Verificaciones</a>
      <a class="tb-link active" href="./gestion_productos.php"><i class="bi bi-box-seam"></i> Productos</a>
      <a class="tb-link" href="./estadisticas.php"><i class="bi bi-bar-chart-line"></i> Estadísticas</a>
      <a class="tb-link" href="./reportes.php"><i class="bi bi-flag-fill"></i> Reportes</a>
    </nav>
    <div class="tb-right">
      <button class="tb-bell" title="Notificaciones">
        <i class="bi bi-bell-fill"></i>
        <?php if ($reportados_count > 0): ?>
          <span class="tb-bell__badge"><?= $reportados_count > 9 ? '9+' : $reportados_count ?></span>
        <?php endif; ?>
      </button>
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

  <!-- ══ PAGE ══ -->
  <div class="page">

    <!-- Header -->
    <div class="page-head">
      <div>
        <h1>
          Moderación de productos
          <?php if ($reportados_count > 0): ?>
            <span class="header-badge"><span class="dot"></span> <?= $reportados_count ?> reportado<?= $reportados_count !== 1 ? 's' : '' ?></span>
          <?php endif; ?>
        </h1>
        <p>Supervisa reportes, publicaciones activas, destacados y productos eliminados del marketplace.</p>
      </div>
    </div>

    <div class="layout">

      <!-- ══ COLUMNA PRINCIPAL ══ -->
      <section>
        <div class="card">

          <!-- Tabs (primera activa según haya reportes o no) -->
          <?php $default_tab = $reportados_count > 0 ? 'reportados' : 'todos'; ?>
          <div class="tabs">
            <button class="tab <?= $default_tab === 'reportados' ? 'active' : '' ?>" data-tab="reportados" onclick="showTab('reportados')">
              <i class="bi bi-flag-fill"></i> Reportados
              <?php if ($reportados_count > 0): ?>
                <span class="tab-badge"><?= $reportados_count ?></span>
              <?php endif; ?>
            </button>
            <button class="tab <?= $default_tab === 'todos' ? 'active' : '' ?>" data-tab="todos" onclick="showTab('todos')">
              <i class="bi bi-box-seam"></i> Todos los productos
            </button>
            <button class="tab" data-tab="destacados" onclick="showTab('destacados')">
              <i class="bi bi-star-fill"></i> Destacados
            </button>
            <button class="tab" data-tab="eliminados" onclick="showTab('eliminados')">
              <i class="bi bi-trash3"></i> Eliminados
            </button>
          </div>

          <!-- ══════════════ TAB: REPORTADOS ══════════════ -->
          <div class="tab-pane <?= $default_tab === 'reportados' ? 'active' : '' ?>" id="pane-reportados">
            <?php if (!$reports_table): ?>
              <div class="pane-banner pane-banner--mute">
                <i class="bi bi-info-circle-fill"></i>
                <span>Módulo de reportes no configurado. Cuando los usuarios reporten productos, aparecerán aquí.</span>
              </div>
              <div class="empty" style="padding:56px 24px;">
                <i class="bi bi-flag"></i>
                <strong>Sin reportes pendientes</strong>
                <div style="font-size:.82rem;margin-top:4px;">No hay productos reportados por la comunidad.</div>
              </div>
            <?php elseif (empty($reportados)): ?>
              <div class="pane-banner pane-banner--mute">
                <i class="bi bi-check-circle-fill"></i>
                <span>Todos los reportes han sido atendidos.</span>
              </div>
              <div class="empty" style="padding:56px 24px;">
                <i class="bi bi-flag"></i>
                <strong>Sin reportes pendientes</strong>
              </div>
            <?php else: ?>
              <div class="pane-banner pane-banner--danger">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span><strong><?= $reportados_count ?> producto<?= $reportados_count !== 1 ? 's' : '' ?></strong> requieren tu revisión por reportes de la comunidad.</span>
              </div>
              <div class="table-wrap">
                <table class="u-table">
                  <thead>
                    <tr>
                      <th>Producto</th>
                      <th>Vendedor</th>
                      <th>Categoría</th>
                      <th class="num-head">Precio</th>
                      <th>Motivo</th>
                      <th>Reportado por</th>
                      <th>Fecha</th>
                      <th style="text-align:right;">Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($reportados as $rp):
                      $thumb_src = $rp['thumb'] ? $img_base . htmlspecialchars($rp['thumb']) : '';
                      $title     = $rp['title'] ?? 'Producto #' . $rp['product_id'];
                    ?>
                      <tr data-product-id="<?= (int)$rp['product_id'] ?>">
                        <td>
                          <div class="prod-cell">
                            <div class="prod-thumb">
                              <?php if ($thumb_src): ?>
                                <img src="<?= $thumb_src ?>" alt="" onerror="this.parentElement.innerHTML='<i class=\'bi bi-image\'></i>'">
                              <?php else: ?>
                                <i class="bi bi-image"></i>
                              <?php endif; ?>
                            </div>
                            <div class="prod-info">
                              <strong title="<?= htmlspecialchars($title) ?>"><?= htmlspecialchars($title) ?></strong>
                              <small>#<?= (int)$rp['product_id'] ?></small>
                            </div>
                          </div>
                        </td>
                        <td>
                          <div class="seller-cell">
                            <div class="seller-av"><?= htmlspecialchars(initial_of($rp['seller_name'] ?? '—')) ?></div>
                            <strong><?= htmlspecialchars($rp['seller_name'] ?? '—') ?></strong>
                          </div>
                        </td>
                        <td><span class="cell-mute"><?= htmlspecialchars($rp['category_name'] ?? '—') ?></span></td>
                        <td class="num cell-price"><?= fmt_cop($rp['price'] ?? 0) ?></td>
                        <td><span class="badge b-reason"><i class="bi bi-flag-fill"></i> <?= htmlspecialchars($rp['reason'] ?? 'Sin motivo') ?></span></td>
                        <td><span class="cell-mute"><?= htmlspecialchars($rp['reporter_name'] ?? 'Anónimo') ?></span></td>
                        <td class="cell-date"><?= fmt_date($rp['created_at']) ?></td>
                        <td>
                          <div class="actions-cell">
                            <button class="ico-btn danger" title="Eliminar producto"
                              onclick="askDelete(<?= (int)$rp['product_id'] ?>, <?= htmlspecialchars(json_encode($title), ENT_QUOTES) ?>)">
                              <i class="bi bi-trash3-fill"></i>
                            </button>
                            <button class="ico-btn ok" title="Ignorar reporte" onclick="ignoreReport(<?= (int)$rp['id'] ?>)">
                              <i class="bi bi-check-lg"></i>
                            </button>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>

          <!-- ══════════════ TAB: TODOS ══════════════ -->
          <div class="tab-pane <?= $default_tab === 'todos' ? 'active' : '' ?>" id="pane-todos">
            <div class="filters">
              <div class="search">
                <i class="bi bi-search"></i>
                <input type="text" id="fSearchTodos" placeholder="Buscar por título...">
              </div>
              <select id="fCategoria">
                <option value="">Categoría: Todas</option>
                <?php foreach ($filter_categorias as $cat): ?>
                  <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                <?php endforeach; ?>
              </select>
              <select id="fEstado">
                <option value="">Estado: Todos</option>
                <option value="Activo">Activo</option>
                <option value="Pausado">Pausado</option>
                <option value="Vendido">Vendido</option>
              </select>
              <select id="fCiudad">
                <option value="">Ciudad: Todas</option>
                <?php foreach ($filter_ciudades as $city): ?>
                  <option value="<?= htmlspecialchars($city) ?>"><?= htmlspecialchars($city) ?></option>
                <?php endforeach; ?>
              </select>
              <button class="btn-csv" onclick="exportCSV()">
                <i class="bi bi-download"></i> Exportar CSV
              </button>
            </div>
            <div class="table-wrap">
              <?php if (empty($todos)): ?>
                <div class="empty">
                  <i class="bi bi-box-seam"></i>
                  <strong>Aún no hay productos publicados</strong>
                  <div style="font-size:.82rem;margin-top:4px;">Los productos aparecerán aquí cuando los vendedores publiquen.</div>
                </div>
              <?php else: ?>
                <table class="u-table" id="tTodos">
                  <thead>
                    <tr>
                      <th>Producto</th>
                      <th>Vendedor</th>
                      <th>Categoría</th>
                      <th class="num-head">Precio</th>
                      <th>Estado</th>
                      <th>Publicado</th>
                      <th class="num-head">Interés</th>
                      <th style="text-align:right;">Acciones</th>
                    </tr>
                  </thead>
                  <tbody id="tbody-todos">
                    <?php foreach ($todos as $p):
                      $thumb_src = $p['thumb'] ? $img_base . htmlspecialchars($p['thumb']) : '';
                      // Estado combinado: si admin_status=inactive => Pausado; si status=vendido => Vendido; si no => Activo
                      if ($p['admin_status'] === 'inactive') { $st = 'Pausado';  $stCls='b-pausado';  $stIco='bi-pause-circle-fill'; }
                      elseif ($p['product_status'] === 'vendido') { $st = 'Vendido'; $stCls='b-vendido'; $stIco='bi-bag-check-fill'; }
                      else { $st = 'Activo'; $stCls='b-activo'; $stIco='bi-check-circle-fill'; }
                    ?>
                      <tr
                        data-title="<?= htmlspecialchars(strtolower($p['title'])) ?>"
                        data-category="<?= htmlspecialchars($p['category_name'] ?? '') ?>"
                        data-status="<?= $st ?>"
                        data-city="<?= htmlspecialchars($p['city'] ?? '') ?>">
                        <td>
                          <div class="prod-cell">
                            <div class="prod-thumb">
                              <?php if ($thumb_src): ?>
                                <img src="<?= $thumb_src ?>" alt="" onerror="this.parentElement.innerHTML='<i class=\'bi bi-image\'></i>'">
                              <?php else: ?>
                                <i class="bi bi-image"></i>
                              <?php endif; ?>
                            </div>
                            <div class="prod-info">
                              <strong title="<?= htmlspecialchars($p['title']) ?>"><?= htmlspecialchars($p['title']) ?></strong>
                              <small>#<?= (int)$p['id'] ?> · <?= htmlspecialchars($p['city'] ?? 'Sin ciudad') ?></small>
                            </div>
                          </div>
                        </td>
                        <td>
                          <div class="seller-cell">
                            <div class="seller-av"><?= htmlspecialchars(initial_of($p['seller_name'])) ?></div>
                            <strong><?= htmlspecialchars($p['seller_name'] ?? '—') ?></strong>
                          </div>
                        </td>
                        <td><span class="cell-mute"><?= htmlspecialchars($p['category_name'] ?? '—') ?></span></td>
                        <td class="num cell-price"><?= fmt_cop($p['price']) ?></td>
                        <td><span class="badge <?= $stCls ?>"><i class="bi <?= $stIco ?>"></i> <?= $st ?></span></td>
                        <td class="cell-date"><?= fmt_date($p['created_at']) ?></td>
                        <td class="num cell-mute" title="Favoritos recibidos"><?= (int)$p['fav_count'] ?></td>
                        <td>
                          <div class="actions-cell">
                            <a href="../products/detalle.php?id=<?= (int)$p['id'] ?>" class="ico-btn" title="Ver detalle">
                              <i class="bi bi-eye-fill"></i>
                            </a>
                            <button class="ico-btn danger" title="Eliminar producto"
                              onclick="askDelete(<?= (int)$p['id'] ?>, <?= htmlspecialchars(json_encode($p['title']), ENT_QUOTES) ?>)">
                              <i class="bi bi-trash3-fill"></i>
                            </button>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              <?php endif; ?>
            </div>
          </div>

          <!-- ══════════════ TAB: DESTACADOS ══════════════ -->
          <div class="tab-pane" id="pane-destacados">
            <?php if (empty($destacados)): ?>
              <div class="pane-banner pane-banner--mute">
                <i class="bi bi-star"></i>
                <span>Ningún producto tiene promoción activa en este momento.</span>
              </div>
              <div class="empty">
                <i class="bi bi-stars"></i>
                <strong>Sin productos destacados</strong>
                <div style="font-size:.82rem;margin-top:4px;">Cuando un vendedor active una promoción, aparecerá aquí.</div>
              </div>
            <?php else: ?>
              <div class="pane-banner pane-banner--accent">
                <i class="bi bi-star-fill"></i>
                <span><strong><?= count($destacados) ?> producto<?= count($destacados) !== 1 ? 's' : '' ?></strong> con promoción activa en el marketplace.</span>
              </div>
              <div class="table-wrap">
                <table class="u-table">
                  <thead>
                    <tr>
                      <th>Producto</th>
                      <th>Vendedor</th>
                      <th>Plan</th>
                      <th>Inicio</th>
                      <th>Vencimiento</th>
                      <th style="min-width:170px;">Días restantes</th>
                      <th class="num-head">Precio</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($destacados as $d):
                      $thumb_src = $d['thumb'] ? $img_base . htmlspecialchars($d['thumb']) : '';
                      $now       = time();
                      $end       = strtotime($d['end_date']);
                      $start     = strtotime($d['start_date']);
                      $totalDays = max(1, (int)ceil(($end - $start) / 86400));
                      $daysLeft  = max(0, (int)ceil(($end - $now) / 86400));
                      $pct       = max(0, min(100, ($daysLeft / $totalDays) * 100));
                      $progCls   = 'ok';
                      if ($pct < 50) $progCls = 'warn';
                      if ($pct < 20) $progCls = 'danger';
                      $plan      = $plan_label[$d['plan_type']] ?? ucfirst($d['plan_type']);
                      $planCls   = ['Básico'=>'b-basico','Recomendado'=>'b-rec','Premium'=>'b-premium'][$plan] ?? 'b-basico';
                      $planIco   = ['Básico'=>'bi-circle-fill','Recomendado'=>'bi-hand-thumbs-up-fill','Premium'=>'bi-star-fill'][$plan] ?? 'bi-circle-fill';
                      $title     = $d['title'] ?? 'Producto #' . $d['product_id'];
                    ?>
                      <tr>
                        <td>
                          <div class="prod-cell">
                            <div class="prod-thumb">
                              <?php if ($thumb_src): ?>
                                <img src="<?= $thumb_src ?>" alt="" onerror="this.parentElement.innerHTML='<i class=\'bi bi-image\'></i>'">
                              <?php else: ?>
                                <i class="bi bi-image"></i>
                              <?php endif; ?>
                            </div>
                            <div class="prod-info">
                              <strong title="<?= htmlspecialchars($title) ?>"><?= htmlspecialchars($title) ?></strong>
                              <small>#<?= (int)$d['product_id'] ?></small>
                            </div>
                          </div>
                        </td>
                        <td>
                          <div class="seller-cell">
                            <div class="seller-av"><?= htmlspecialchars(initial_of($d['seller_name'])) ?></div>
                            <strong><?= htmlspecialchars($d['seller_name'] ?? '—') ?></strong>
                          </div>
                        </td>
                        <td><span class="badge <?= $planCls ?>"><i class="bi <?= $planIco ?>"></i> <?= $plan ?></span></td>
                        <td class="cell-date"><?= fmt_date($d['start_date']) ?></td>
                        <td class="cell-date"><?= fmt_date($d['end_date']) ?></td>
                        <td>
                          <div class="prog">
                            <div class="prog-track"><div class="prog-bar <?= $progCls ?>" style="width:<?= $pct ?>%"></div></div>
                            <span class="prog-label <?= $daysLeft === 0 ? 'danger' : '' ?>">
                              <?= $daysLeft === 0 ? 'Vencida' : $daysLeft . ' día' . ($daysLeft !== 1 ? 's' : '') ?>
                            </span>
                          </div>
                        </td>
                        <td class="num cell-price"><?= fmt_cop($d['price']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>

          <!-- ══════════════ TAB: ELIMINADOS ══════════════ -->
          <div class="tab-pane" id="pane-eliminados">
            <?php if (empty($eliminados)): ?>
              <div class="pane-banner pane-banner--mute">
                <i class="bi bi-trash3"></i>
                <span>No hay productos eliminados.</span>
              </div>
              <div class="empty">
                <i class="bi bi-trash3"></i>
                <strong>Sin productos eliminados</strong>
              </div>
            <?php else: ?>
              <div class="pane-banner pane-banner--mute">
                <i class="bi bi-trash3"></i>
                <span><strong><?= count($eliminados) ?> producto<?= count($eliminados) !== 1 ? 's' : '' ?></strong> retirado<?= count($eliminados) !== 1 ? 's' : '' ?> del marketplace.</span>
              </div>
              <div class="table-wrap">
                <table class="u-table">
                  <thead>
                    <tr>
                      <th>Producto</th>
                      <th>Vendedor</th>
                      <th>Categoría</th>
                      <th class="num-head">Precio</th>
                      <th>Publicado</th>
                      <th>Eliminado</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($eliminados as $e):
                      $thumb_src = $e['thumb'] ? $img_base . htmlspecialchars($e['thumb']) : '';
                    ?>
                      <tr style="opacity:.75;">
                        <td>
                          <div class="prod-cell">
                            <div class="prod-thumb">
                              <?php if ($thumb_src): ?>
                                <img src="<?= $thumb_src ?>" alt="" onerror="this.parentElement.innerHTML='<i class=\'bi bi-image\'></i>'">
                              <?php else: ?>
                                <i class="bi bi-image"></i>
                              <?php endif; ?>
                            </div>
                            <div class="prod-info">
                              <strong><?= htmlspecialchars($e['title']) ?></strong>
                              <small>#<?= (int)$e['id'] ?></small>
                            </div>
                          </div>
                        </td>
                        <td>
                          <div class="seller-cell">
                            <div class="seller-av"><?= htmlspecialchars(initial_of($e['seller_name'])) ?></div>
                            <strong><?= htmlspecialchars($e['seller_name'] ?? '—') ?></strong>
                          </div>
                        </td>
                        <td><span class="cell-mute"><?= htmlspecialchars($e['category_name'] ?? '—') ?></span></td>
                        <td class="num cell-price" style="color:var(--ink3);"><?= fmt_cop($e['price']) ?></td>
                        <td class="cell-date"><?= fmt_date($e['created_at']) ?></td>
                        <td class="cell-date"><?= fmt_date($e['updated_at'] ?: $e['created_at']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <!-- ══ SIDEBAR ══ -->
      <aside class="sbar">
        <div class="sbar-title">Estadísticas rápidas</div>

        <div class="stat-mini stat-mini--active">
          <div class="stat-mini__icon"><i class="bi bi-box-seam-fill"></i></div>
          <div class="stat-mini__num"><?= number_format($stat_active, 0, ',', '.') ?></div>
          <div class="stat-mini__lbl">Productos activos</div>
        </div>

        <div class="stat-mini stat-mini--today">
          <div class="stat-mini__icon"><i class="bi bi-calendar-event-fill"></i></div>
          <div class="stat-mini__num"><?= number_format($stat_today, 0, ',', '.') ?></div>
          <div class="stat-mini__lbl">Publicados hoy</div>
        </div>

        <div class="stat-feat">
          <div class="stat-feat__icon"><i class="bi bi-rocket-takeoff-fill"></i></div>
          <div class="stat-feat__num"><?= fmt_cop($promo_revenue) ?></div>
          <div class="stat-feat__lbl">Ingresos por promociones · <?= htmlspecialchars($mes_actual) ?></div>
          <div class="stat-feat__meta">
            <span>Destacados activos</span>
            <strong><?= count($destacados) ?></strong>
          </div>
        </div>

        <div class="topcat">
          <h3>Top categorías</h3>
          <div class="topcat-list">
            <?php if (empty($top_categorias)): ?>
              <div class="empty" style="padding:18px 0;">
                <i class="bi bi-bar-chart"></i>
                <strong>Sin datos aún</strong>
              </div>
            <?php else: foreach ($top_categorias as $i => $tc):
              $pct = $top_max > 0 ? round(((int)$tc['cnt'] / $top_max) * 100) : 0;
            ?>
              <div class="topcat-item">
                <div class="row">
                  <strong><span class="topcat-rank"><?= $i + 1 ?></span> <?= htmlspecialchars($tc['name'] ?? '—') ?></strong>
                  <span><?= number_format((int)$tc['cnt'], 0, ',', '.') ?></span>
                </div>
                <div class="topcat-bar"><div style="width:<?= $pct ?>%"></div></div>
              </div>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </aside>

    </div>
  </div>

  <!-- ══ MODAL: ELIMINAR PRODUCTO ══ -->
  <div class="modal-bg" id="mDelete">
    <div class="modal">
      <div class="modal-head">
        <div class="modal-ico"><i class="bi bi-trash3-fill"></i></div>
        <div>
          <h2>Eliminar producto</h2>
          <p>¿Seguro que quieres eliminar <strong id="mDeleteTitle">este producto</strong>?</p>
        </div>
      </div>
      <div class="modal-body">
        <div class="modal-warn">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <div>
            <strong>Esta acción no se puede deshacer.</strong>
            El producto será retirado del marketplace y el vendedor recibirá una notificación.
          </div>
        </div>
      </div>
      <div class="modal-actions">
        <button class="btn-secondary" onclick="closeModal('mDelete')">Cancelar</button>
        <button class="btn-danger" onclick="confirmDelete()">
          <i class="bi bi-trash3-fill"></i> Eliminar producto
        </button>
      </div>
    </div>
  </div>

  <!-- Toast container -->
  <div class="toast-area" id="toastArea"></div>

<script>
/* ════════════════════════════════════════ TABS ════════════════════════════════════════ */
function showTab(name) {
  document.querySelectorAll('.tab').forEach(t => t.classList.toggle('active', t.dataset.tab === name));
  document.querySelectorAll('.tab-pane').forEach(p => p.classList.toggle('active', p.id === 'pane-' + name));
}

/* ════════════════════════════════════════ FILTROS TAB "TODOS" (DOM) ════════════════════════════════════════ */
function applyFiltersTodos() {
  const tbl = document.getElementById('tTodos');
  if (!tbl) return;
  const q   = (document.getElementById('fSearchTodos').value || '').trim().toLowerCase();
  const cat = document.getElementById('fCategoria').value;
  const est = document.getElementById('fEstado').value;
  const ciu = document.getElementById('fCiudad').value;

  let visible = 0;
  tbl.querySelectorAll('tbody tr').forEach(tr => {
    const t = tr.dataset.title    || '';
    const c = tr.dataset.category || '';
    const s = tr.dataset.status   || '';
    const z = tr.dataset.city     || '';
    const show =
      (!q   || t.includes(q)) &&
      (!cat || c === cat) &&
      (!est || s === est) &&
      (!ciu || z === ciu);
    tr.style.display = show ? '' : 'none';
    if (show) visible++;
  });

  // Mostrar fila vacía si no hay resultados
  let emptyRow = tbl.querySelector('tr.filter-empty');
  if (visible === 0) {
    if (!emptyRow) {
      emptyRow = document.createElement('tr');
      emptyRow.className = 'filter-empty';
      emptyRow.innerHTML = `<td colspan="8"><div class="empty"><i class="bi bi-search"></i><strong>Sin resultados</strong><div style="font-size:.82rem;margin-top:4px;">Prueba ajustar los filtros.</div></div></td>`;
      tbl.querySelector('tbody').appendChild(emptyRow);
    }
    emptyRow.style.display = '';
  } else if (emptyRow) {
    emptyRow.style.display = 'none';
  }
}

/* ════════════════════════════════════════ MODAL ELIMINAR ════════════════════════════════════════ */
let pendingDeleteId = null;

function askDelete(id, title) {
  pendingDeleteId = id;
  document.getElementById('mDeleteTitle').textContent = '"' + title + '"';
  document.getElementById('mDelete').classList.add('open');
}

function confirmDelete() {
  if (!pendingDeleteId) { closeModal('mDelete'); return; }
  const id  = pendingDeleteId;
  const btn = document.querySelector('#mDelete .btn-danger');
  btn.disabled = true;
  btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Eliminando...';

  fetch('../../api/admin/products.php?action=set_status', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: id, status: 'deleted' })
  })
    .then(r => r.json().catch(() => ({ ok: false, error: 'Respuesta inválida' })))
    .then(data => {
      closeModal('mDelete');
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-trash3-fill"></i> Eliminar producto';

      if (!data.ok) {
        toast('err', 'No se pudo eliminar', data.error || 'Intenta de nuevo.');
        return;
      }
      toast('ok', 'Producto eliminado', 'La publicación fue retirada del marketplace.');
      document.querySelectorAll('tr[data-product-id="' + id + '"]').forEach(tr => tr.remove());
      setTimeout(() => location.reload(), 900);
    })
    .catch(() => {
      closeModal('mDelete');
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-trash3-fill"></i> Eliminar producto';
      toast('err', 'Error de red', 'Verifica tu conexión.');
    });

  pendingDeleteId = null;
}

function ignoreReport(id) {
  toast('info', 'Reporte ignorado', 'El producto continuará publicado.');
}

function exportCSV() {
  const tbl = document.getElementById('tTodos');
  if (!tbl) { toast('warn', 'Sin datos', 'No hay productos para exportar.'); return; }
  const rows = [['ID','Título','Vendedor','Categoría','Precio','Estado','Publicado','Ciudad']];
  tbl.querySelectorAll('tbody tr').forEach(tr => {
    if (tr.style.display === 'none' || tr.classList.contains('filter-empty')) return;
    const tds = tr.querySelectorAll('td');
    if (tds.length < 7) return;
    const id     = (tr.querySelector('.prod-info small')?.textContent || '').replace('#','').split('·')[0].trim();
    const title  = tr.querySelector('.prod-info strong')?.textContent.trim() || '';
    const seller = tr.querySelector('.seller-cell strong')?.textContent.trim() || '';
    const cat    = tds[2].innerText.trim();
    const price  = tds[3].innerText.trim();
    const est    = tr.dataset.status || '';
    const pub    = tds[5].innerText.trim();
    const ciu    = tr.dataset.city || '';
    rows.push([id, title, seller, cat, price, est, pub, ciu]);
  });
  const csv = rows.map(r => r.map(c => `"${String(c).replace(/"/g,'""')}"`).join(',')).join('\n');
  const blob = new Blob(["\uFEFF" + csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `productos_${new Date().toISOString().slice(0,10)}.csv`;
  a.click();
  URL.revokeObjectURL(url);
  toast('ok', 'CSV exportado', `${rows.length - 1} producto${rows.length === 2 ? '' : 's'} descargado${rows.length === 2 ? '' : 's'}.`);
}

function closeModal(id) { document.getElementById(id).classList.remove('open'); }

/* Toast */
function toast(type, title, msg) {
  const cls  = type === 'err' ? 'err' : (type === 'warn' ? 'warn' : '');
  const ico  = type === 'err' ? 'bi-x-circle-fill' : (type === 'warn' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill');
  const t = document.createElement('div');
  t.className = `toast ${cls}`;
  t.innerHTML = `<i class="bi ${ico}"></i><div><strong style="display:block;">${title}</strong><span style="font-weight:500;opacity:.9;font-size:.78rem;">${msg}</span></div>
    <button onclick="this.parentElement.remove()"><i class="bi bi-x-lg"></i></button>`;
  document.getElementById('toastArea').appendChild(t);
  setTimeout(() => t.remove(), 4500);
}

/* ════════════════════════════════════════ INIT ════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
  // Cerrar modal al click en backdrop
  const mDelete = document.getElementById('mDelete');
  if (mDelete) {
    mDelete.addEventListener('click', e => {
      if (e.target.id === 'mDelete') closeModal('mDelete');
    });
  }

  // Filtros del tab "Todos" (sólo si la tabla existe)
  ['fSearchTodos','fCategoria','fEstado','fCiudad'].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener(el.tagName === 'SELECT' ? 'change' : 'input', applyFiltersTodos);
  });
});
</script>
</body>
</html>

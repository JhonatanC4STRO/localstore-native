<?php include('../config/conexion.php'); ?>
<?php
session_start();
$isLoggedIn  = isset($_SESSION['user']);
$user        = $isLoggedIn ? $_SESSION['user'] : null;
$userInitial = $isLoggedIn ? strtoupper(mb_substr($user['full_name'], 0, 1)) : '';
$userName    = $isLoggedIn ? explode(' ', $user['full_name'])[0] : '';

/* ══════════════════════════════════════════
   LOGIC PRESERVED — original query + extras
══════════════════════════════════════════ */
$sql = "SELECT
    p.id,
    p.title,
    p.description,
    p.price,
    p.condition_type,
    p.status,
    u.full_name AS seller_name,
    (SELECT pi.image_url
     FROM product_images pi
     WHERE pi.product_id = p.id
     ORDER BY pi.id ASC
     LIMIT 1) AS image_url
  FROM products p
  LEFT JOIN users u ON p.user_id = u.id
  ORDER BY p.id DESC";

$result     = mysqli_query($conn, $sql);
$totalCount = mysqli_num_rows($result);

/* categories for sidebar */
$cat_sql    = "SELECT id, name FROM categories ORDER BY name ASC";
$cat_result = mysqli_query($conn, $cat_sql);
$categories = [];
while ($c = mysqli_fetch_assoc($cat_result)) $categories[] = $c;

function getInitials($name)
{
    if (empty($name)) return 'U';
    $parts = explode(' ', trim($name));
    $out   = '';
    foreach ($parts as $p) if (!empty($p)) $out .= strtoupper($p[0]);
    return substr($out, 0, 2);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todos los productos – ComercioLocal</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <link rel="stylesheet" href="../output.css">
    <style>
        :root {
            --g900: #0b2e17;
            --g800: #103d1e;
            --g700: #185228;
            --g600: #1e6b33;
            --g500: #25883f;
            --g400: #34b357;
            --g300: #55d475;
            --g200: #96e8b0;
            --g100: #c8f2d5;
            --g50: #edfaf3;

            --y600: #b07000;
            --y500: #d48c0a;
            --y400: #f5a81c;
            --y300: #fcc034;
            --y200: #fdd878;
            --y100: #fef0bc;
            --y50: #fffbe8;

            --ink: #0d1f13;
            --ink2: #2d4035;
            --ink3: #5a7065;
            --bg: #f0f7f2;
            --card: #ffffff;
            --border: #d4e8da;
            --r: 14px;
            --sh: 0 2px 14px rgba(10, 40, 20, .08), 0 1px 3px rgba(10, 40, 20, .05);
            --sh-lg: 0 8px 32px rgba(10, 40, 20, .14), 0 2px 8px rgba(10, 40, 20, .06);
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--ink);
            min-height: 100vh;
        }

        h1,
        h2,
        h3,
        h4,
        h5 {
            font-family: 'Syne', sans-serif;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* ══ NAVBAR ══ */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 200;
            background: var(--g900);
            border-bottom: 3px solid var(--y400);
            height: 88px;
            padding: 0 28px;
            display: flex;
            align-items: center;
            gap: 18px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, .3);
        }

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.25rem;
            color: #fff;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .logo-box {
            width: 36px;
            height: 36px;
            background: var(--y400);
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--g900);
            font-size: 1rem;
            box-shadow: 0 3px 10px rgba(245, 168, 28, .4);
        }

        .nav-logo em {
            color: var(--y400);
            font-style: normal;
        }

        .nav-search {
            flex: 1;
            max-width: 520px;
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, .12);
            border: 1.5px solid rgba(255, 255, 255, .18);
            border-radius: 50px;
            overflow: hidden;
            transition: all .2s;
        }

        .nav-search:focus-within {
            background: rgba(255, 255, 255, .18);
            border-color: var(--y400);
        }

        .nav-search input {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            color: #fff;
            font-family: 'DM Sans', sans-serif;
            font-size: .9rem;
            padding: 10px 18px;
        }

        .nav-search input::placeholder {
            color: rgba(255, 255, 255, .4);
        }

        .nav-search button {
            background: var(--y400);
            border: none;
            cursor: pointer;
            padding: 10px 18px;
            color: var(--g900);
            font-size: 1rem;
            transition: background .2s;
        }

        .nav-search button:hover {
            background: var(--y300);
        }

        .nav-spacer {
            flex: 1;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-icon-btn {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: rgba(255, 255, 255, .08);
            border: 1.5px solid rgba(255, 255, 255, .12);
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, .7);
            font-size: 1rem;
            cursor: pointer;
            transition: all .2s;
            position: relative;
        }

        .nav-icon-btn:hover {
            background: rgba(255, 255, 255, .16);
            color: var(--y300);
        }

        .notif-dot {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--y400);
            border: 2px solid var(--g900);
        }

        .btn-ghost-nav {
            background: transparent;
            border: 1.5px solid rgba(255, 255, 255, .3);
            color: #fff;
            border-radius: 50px;
            padding: 7px 16px;
            font-family: 'DM Sans', sans-serif;
            font-size: .86rem;
            cursor: pointer;
            transition: all .2s;
        }

        .btn-ghost-nav:hover {
            border-color: var(--y400);
            color: var(--y400);
        }

        .btn-publish-nav {
            background: var(--y400);
            border: none;
            color: var(--g900);
            border-radius: 50px;
            padding: 9px 20px;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: .87rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 7px;
            box-shadow: 0 3px 12px rgba(245, 168, 28, .4);
            transition: all .2s;
            white-space: nowrap;
        }

        .btn-publish-nav:hover {
            background: var(--y300);
            transform: translateY(-1px);
        }

        /* user chip */
        .user-chip {
            display: flex;
            align-items: center;
            gap: 9px;
            background: rgba(255, 255, 255, .08);
            border: 1.5px solid rgba(255, 255, 255, .18);
            border-radius: 50px;
            padding: 5px 13px 5px 5px;
            cursor: pointer;
            transition: all .2s;
            user-select: none;
            position: relative;
        }

        .user-chip:hover {
            background: rgba(255, 255, 255, .14);
            border-color: var(--y400);
        }

        .user-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--y400), var(--y300));
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: .88rem;
            color: var(--g900);
            flex-shrink: 0;
            box-shadow: 0 0 0 2px rgba(245, 168, 28, .4);
        }

        .online-dot {
            position: absolute;
            bottom: 1px;
            right: 1px;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: var(--g400);
            border: 2px solid var(--g900);
        }

        .uc-name {
            font-size: .83rem;
            font-weight: 600;
            color: #fff;
        }

        .uc-greet {
            font-size: .67rem;
            color: rgba(255, 255, 255, .4);
        }

        .uc-arrow {
            font-size: .68rem;
            color: rgba(255, 255, 255, .4);
            transition: transform .2s;
        }

        .user-menu-wrap {
            position: relative;
        }

        .user-menu-wrap.open .uc-arrow {
            transform: rotate(180deg);
        }

        .user-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 220px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 12px 40px rgba(10, 40, 20, .18), 0 2px 8px rgba(10, 40, 20, .07);
            border: 1.5px solid var(--g100);
            overflow: hidden;
            opacity: 0;
            pointer-events: none;
            transform: translateY(-8px) scale(.97);
            transition: all .22s cubic-bezier(.22, .9, .36, 1);
            z-index: 300;
        }

        .user-menu-wrap.open .user-dropdown {
            opacity: 1;
            pointer-events: all;
            transform: translateY(0) scale(1);
        }

        .ud-head {
            background: linear-gradient(135deg, var(--g900), var(--g800));
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .ud-av {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--y400), var(--y300));
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1rem;
            color: var(--g900);
            flex-shrink: 0;
        }

        .ud-name {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: .88rem;
            color: #fff;
        }

        .ud-email {
            font-size: .7rem;
            color: rgba(255, 255, 255, .45);
            margin-top: 1px;
            max-width: 130px;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .ud-body {
            padding: 6px 0;
        }

        .ud-lnk {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 16px;
            font-size: .84rem;
            color: var(--ink2);
            cursor: pointer;
            transition: background .15s;
        }

        .ud-lnk:hover {
            background: var(--g50);
            color: var(--g700);
        }

        .ud-lnk i {
            width: 18px;
            text-align: center;
            color: var(--g500);
            font-size: .9rem;
        }

        .ud-sep {
            height: 1px;
            background: var(--g100);
            margin: 3px 0;
        }

        .ud-logout {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 16px;
            font-size: .84rem;
            color: #dc2626;
            cursor: pointer;
            transition: background .15s;
        }

        .ud-logout:hover {
            background: #fef2f2;
        }

        .ud-logout i {
            width: 18px;
            text-align: center;
        }

        /* ══ BREADCRUMB ══ */
        .breadcrumb-bar {
            background: #fff;
            border-bottom: 1.5px solid var(--border);
            padding: 0 32px;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: .78rem;
            color: var(--ink3);
            height: 42px;
        }

        .breadcrumb a {
            color: var(--g500);
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .breadcrumb i {
            font-size: .65rem;
        }

        /* ══ QUICK FILTER BAR ══ */
        .quick-bar {
            background: #fff;
            border-bottom: 1.5px solid var(--border);
            padding: 0 32px;
            display: flex;
            align-items: center;
            gap: 10px;
            height: 56px;
            overflow-x: auto;
            scrollbar-width: none;
        }

        .quick-bar::-webkit-scrollbar {
            display: none;
        }

        .qf-chip {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            gap: 6px;
            background: var(--bg);
            border: 1.5px solid var(--border);
            border-radius: 50px;
            padding: 6px 14px;
            font-size: .8rem;
            font-weight: 600;
            color: var(--ink2);
            cursor: pointer;
            transition: all .2s;
            white-space: nowrap;
        }

        .qf-chip:hover {
            border-color: var(--g400);
            color: var(--g600);
            background: var(--g50);
        }

        .qf-chip.active {
            background: var(--g500);
            border-color: var(--g500);
            color: #fff;
        }

        .qf-chip i {
            font-size: .85rem;
        }

        .qf-sep {
            width: 1px;
            height: 24px;
            background: var(--border);
            flex-shrink: 0;
        }

        /* ══ PAGE SHELL ══ */
        .page-shell {
            display: flex;
            max-width: 1440px;
            margin: 0 auto;
            padding: 28px 28px 80px;
            gap: 24px;
            align-items: flex-start;
        }

        /* ══ SIDEBAR ══ */
        .sidebar {
            width: 256px;
            flex-shrink: 0;
            position: sticky;
            top: calc(66px + 12px);
        }

        .filter-panel {
            background: var(--card);
            border-radius: var(--r);
            border: 1.5px solid var(--border);
            box-shadow: var(--sh);
            overflow: hidden;
        }

        .fp-header {
            background: linear-gradient(135deg, var(--g900), var(--g800));
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .fp-header h3 {
            font-size: .95rem;
            font-weight: 700;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .fp-header h3 i {
            color: var(--y300);
        }

        .fp-reset {
            font-size: .75rem;
            color: rgba(255, 255, 255, .5);
            cursor: pointer;
            transition: color .2s;
        }

        .fp-reset:hover {
            color: var(--y300);
        }

        .fp-section {
            padding: 16px 18px;
            border-bottom: 1.5px solid var(--border);
        }

        .fp-section:last-child {
            border-bottom: none;
        }

        .fp-label {
            font-size: .7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--ink3);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .fp-label i {
            color: var(--g500);
            font-size: .8rem;
        }

        .fp-option {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 7px 6px;
            border-radius: 9px;
            cursor: pointer;
            transition: background .15s;
        }

        .fp-option:hover {
            background: var(--g50);
        }

        .fp-option input[type="checkbox"] {
            accent-color: var(--g500);
            width: 15px;
            height: 15px;
            flex-shrink: 0;
        }

        .fp-option input[type="radio"] {
            accent-color: var(--g500);
            width: 15px;
            height: 15px;
            flex-shrink: 0;
        }

        .fp-opt-label {
            font-size: .84rem;
            color: var(--ink2);
            flex: 1;
        }

        .fp-opt-count {
            font-size: .72rem;
            color: var(--ink3);
            background: #f0f0f0;
            padding: 1px 7px;
            border-radius: 20px;
        }

        /* price range */
        .price-inputs {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-bottom: 12px;
        }

        .pi {
            flex: 1;
            border: 1.5px solid var(--border);
            border-radius: 9px;
            padding: 8px 10px;
            font-family: 'DM Sans', sans-serif;
            font-size: .82rem;
            outline: none;
            color: var(--ink);
            transition: border-color .2s;
        }

        .pi:focus {
            border-color: var(--g400);
        }

        .pi-sep {
            font-size: .8rem;
            color: var(--ink3);
        }

        .range-track {
            width: 100%;
            accent-color: var(--g500);
            cursor: pointer;
        }

        /* condition tags */
        .cond-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .cond-tag {
            padding: 5px 13px;
            border-radius: 50px;
            border: 1.5px solid var(--border);
            font-size: .8rem;
            cursor: pointer;
            transition: all .2s;
            color: var(--ink2);
        }

        .cond-tag:hover {
            border-color: var(--g300);
            color: var(--g600);
        }

        .cond-tag.active {
            background: var(--g500);
            border-color: var(--g500);
            color: #fff;
        }

        /* sort select */
        .sort-select {
            width: 100%;
            border: 1.5px solid var(--border);
            border-radius: 9px;
            padding: 9px 12px;
            font-family: 'DM Sans', sans-serif;
            font-size: .84rem;
            color: var(--ink);
            outline: none;
            cursor: pointer;
            transition: border-color .2s;
            background: #fff;
        }

        .sort-select:focus {
            border-color: var(--g400);
        }

        /* apply btn */
        .btn-apply {
            width: 100%;
            background: linear-gradient(135deg, var(--g500), var(--g400));
            border: none;
            color: #fff;
            padding: 12px;
            border-radius: 10px;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: .9rem;
            cursor: pointer;
            transition: all .2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            margin-top: 4px;
        }

        .btn-apply:hover {
            background: linear-gradient(135deg, var(--g600), var(--g500));
            transform: translateY(-1px);
        }

        /* sidebar promo */
        .sb-promo {
            background: linear-gradient(135deg, var(--g800), var(--g900));
            border-radius: var(--r);
            border: 1.5px solid rgba(255, 255, 255, .08);
            padding: 20px;
            margin-top: 16px;
            position: relative;
            overflow: hidden;
        }

        .sb-promo::before {
            content: '';
            position: absolute;
            right: -20px;
            top: -20px;
            width: 90px;
            height: 90px;
            background: rgba(245, 168, 28, .15);
            border-radius: 50%;
        }

        .sb-promo .pt {
            font-size: .7rem;
            font-weight: 700;
            background: var(--y400);
            color: var(--g900);
            padding: 3px 9px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 10px;
        }

        .sb-promo h4 {
            font-size: .95rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 6px;
        }

        .sb-promo p {
            font-size: .78rem;
            color: rgba(255, 255, 255, .55);
            line-height: 1.5;
            margin-bottom: 14px;
        }

        .sb-promo a {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: var(--y400);
            color: var(--g900);
            padding: 9px;
            border-radius: 9px;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: .8rem;
            transition: background .2s;
        }

        .sb-promo a:hover {
            background: var(--y300);
        }

        /* ══ MAIN CONTENT ══ */
        .main-content {
            flex: 1;
            min-width: 0;
        }

        /* Results header */
        .results-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .results-left h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--ink);
        }

        .results-left h1 span {
            color: var(--g500);
        }

        .results-count {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--g100);
            color: var(--g700);
            font-size: .78rem;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 50px;
            margin-top: 5px;
        }

        .results-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .view-toggle {
            display: flex;
            gap: 4px;
        }

        .vt-btn {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            border: 1.5px solid var(--border);
            background: var(--card);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--ink3);
            font-size: .9rem;
            transition: all .2s;
        }

        .vt-btn:hover,
        .vt-btn.active {
            border-color: var(--g400);
            color: var(--g600);
            background: var(--g50);
        }

        .sort-inline {
            border: 1.5px solid var(--border);
            background: var(--card);
            border-radius: 9px;
            padding: 8px 12px;
            font-family: 'DM Sans', sans-serif;
            font-size: .84rem;
            color: var(--ink);
            outline: none;
            cursor: pointer;
        }

        .sort-inline:focus {
            border-color: var(--g400);
        }

        /* ══ PRODUCT GRID ══ */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 18px;
        }

        .product-card {
            background: var(--card);
            border-radius: var(--r);
            border: 1.5px solid var(--border);
            box-shadow: var(--sh);
            overflow: hidden;
            cursor: pointer;
            position: relative;
            transition: all .3s cubic-bezier(.175, .885, .32, 1.275);
            display: flex;
            flex-direction: column;
            animation: fadeUp .4s both;
        }

        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--sh-lg);
            border-color: var(--g200);
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(18px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* stagger delays */
        .product-card:nth-child(1) {
            animation-delay: .03s;
        }

        .product-card:nth-child(2) {
            animation-delay: .06s;
        }

        .product-card:nth-child(3) {
            animation-delay: .09s;
        }

        .product-card:nth-child(4) {
            animation-delay: .12s;
        }

        .product-card:nth-child(5) {
            animation-delay: .15s;
        }

        .product-card:nth-child(6) {
            animation-delay: .18s;
        }

        .product-card:nth-child(7) {
            animation-delay: .21s;
        }

        .product-card:nth-child(8) {
            animation-delay: .24s;
        }

        .product-card:nth-child(n+9) {
            animation-delay: .27s;
        }

        /* image */
        .pc-img {
            height: 178px;
            position: relative;
            background: linear-gradient(135deg, var(--g50), #f8fdf9);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .pc-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .4s ease;
        }

        .product-card:hover .pc-img img {
            transform: scale(1.06);
        }

        .pc-img-ph {
            font-size: 3rem;
            color: var(--g200);
        }

        /* badges */
        .badge {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 2;
            font-size: .63rem;
            font-weight: 700;
            padding: 3px 9px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .badge-new {
            background: var(--g500);
            color: #fff;
        }

        .badge-used {
            background: var(--y400);
            color: var(--g900);
        }

        .badge-featured {
            background: linear-gradient(135deg, var(--y500), var(--y400));
            color: var(--g900);
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .fav-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 2;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .88);
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: .85rem;
            color: #ccc;
            transition: all .2s;
            backdrop-filter: blur(4px);
        }

        .fav-btn:hover {
            color: #ef4444;
            transform: scale(1.12);
        }

        .fav-btn.active {
            color: #ef4444;
        }

        /* body */
        .pc-body {
            padding: 14px 16px 16px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .pc-cat {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: var(--g100);
            color: var(--g700);
            font-size: .65rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 20px;
            margin-bottom: 7px;
            width: fit-content;
        }

        .pc-title {
            font-size: .88rem;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 8px;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            flex: 1;
        }

        .pc-price {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.1rem;
            color: var(--g600);
            margin-bottom: 10px;
        }

        .pc-meta {
            display: flex;
            flex-direction: column;
            gap: 3px;
            margin-bottom: 12px;
        }

        .pc-meta-row {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: .74rem;
            color: var(--ink3);
        }

        .pc-meta-row i {
            color: var(--g400);
            font-size: .78rem;
        }

        .pc-seller {
            display: flex;
            align-items: center;
            gap: 8px;
            padding-top: 11px;
            border-top: 1.5px solid var(--border);
        }

        .seller-av {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: var(--g100);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .65rem;
            font-weight: 700;
            color: var(--g700);
            flex-shrink: 0;
        }

        .seller-name-txt {
            font-size: .76rem;
            font-weight: 600;
            color: var(--ink);
            flex: 1;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .seller-stars {
            display: flex;
            align-items: center;
            gap: 2px;
            font-size: .65rem;
            color: var(--y400);
        }

        .seller-stars span {
            color: var(--ink3);
            margin-left: 2px;
            font-size: .68rem;
        }

        /* ══ LOAD MORE ══ */
        .load-more-wrap {
            text-align: center;
            margin-top: 36px;
        }

        .btn-load-more {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--card);
            border: 2px solid var(--g400);
            color: var(--g600);
            border-radius: 50px;
            padding: 13px 36px;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: .92rem;
            cursor: pointer;
            transition: all .25s;
            box-shadow: var(--sh);
        }

        .btn-load-more:hover {
            background: var(--g500);
            border-color: var(--g500);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 136, 63, .3);
        }

        /* pagination */
        .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 16px;
        }

        .pg-btn {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            border: 1.5px solid var(--border);
            background: var(--card);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: .85rem;
            color: var(--ink2);
            transition: all .2s;
            font-family: 'DM Sans', sans-serif;
            font-weight: 500;
        }

        .pg-btn:hover {
            border-color: var(--g400);
            color: var(--g600);
        }

        .pg-btn.active {
            background: var(--g500);
            border-color: var(--g500);
            color: #fff;
        }

        .pg-dots {
            color: var(--ink3);
            padding: 0 4px;
        }

        /* empty state */
        .empty-state {
            grid-column: 1/-1;
            text-align: center;
            padding: 70px 20px;
            background: var(--card);
            border-radius: var(--r);
            border: 2px dashed var(--border);
        }

        .empty-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: var(--g100);
            color: var(--g500);
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .empty-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 6px;
        }

        .empty-sub {
            font-size: .85rem;
            color: var(--ink3);
            line-height: 1.55;
        }

        /* scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--g200);
            border-radius: 6px;
        }

        @media(max-width:900px) {
            .sidebar {
                display: none;
            }

            .page-shell {
                padding: 16px 16px 60px;
            }
        }

        @media(max-width:600px) {
            .product-grid {
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }
        }
    </style>
</head>

<body>

    <!-- ══ NAVBAR ══ -->
    <nav class="navbar">
            <a href="./inde.php" class="nav-logo"><img class="h-28 w-28" src="./Logo de Comercio Local.png" alt=""></a>
        <div class="nav-search">
            <input type="text" placeholder="Buscar productos, marcas, categorías...">
            <button><i class="bi bi-search"></i></button>
        </div>

        <div class="nav-spacer"></div>

        <div class="nav-actions">
            <div class="nav-icon-btn">
                <i class="bi bi-bell"></i>
                <div class="notif-dot"></div>
            </div>
            <div class="nav-icon-btn"><i class="bi bi-chat-dots"></i></div>

            <?php if ($isLoggedIn): ?>
                <div class="user-menu-wrap" id="userMenuWrap">
                    <div class="user-chip" id="userChip">
                        <div style="position:relative;">
                            <div class="user-avatar"><?php echo $userInitial; ?></div>
                            <div class="online-dot"></div>
                        </div>
                        <div>
                            <div class="uc-greet">Hola,</div>
                            <div class="uc-name"><?php echo htmlspecialchars($userName); ?></div>
                        </div>
                        <i class="bi bi-chevron-down uc-arrow"></i>
                    </div>
                    <div class="user-dropdown" id="userDropdown">
                        <div class="ud-head">
                            <div class="ud-av"><?php echo $userInitial; ?></div>
                            <div>
                                <div class="ud-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                <div class="ud-email"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                        </div>
                        <div class="ud-body">
                            <a class="ud-lnk" href="../app/dashboard.php"><i class="bi bi-speedometer2"></i> Mi panel</a>
                            <a class="ud-lnk" href="../app/crear.php"><i class="bi bi-plus-square"></i> Publicar anuncio</a>
                            <a class="ud-lnk" href="#"><i class="bi bi-box-seam"></i> Mis anuncios</a>
                            <a class="ud-lnk" href="#"><i class="bi bi-heart"></i> Favoritos</a>
                            <div class="ud-sep"></div>
                            <a class="ud-logout" href="../controller/logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <a class="btn-ghost-nav" href="../app/auth/login.php"><i class="bi bi-person"></i> Iniciar sesión</a>
                <a class="btn-ghost-nav" href="../app/auth/register.php">Registrarse</a>
            <?php endif; ?>

            <a class="btn-publish-nav" href="../app/crear.php">
                <i class="bi bi-plus-circle-fill"></i> Publicar
            </a>
        </div>
    </nav>

    <!-- ══ BREADCRUMB ══ -->
    <div class="breadcrumb-bar">
        <div class="breadcrumb">
            <a href="../index.php">Inicio</a>
            <i class="bi bi-chevron-right"></i>
            <span>Todos los productos</span>
        </div>
    </div>

    <!-- ══ QUICK FILTER BAR ══ -->
    <div class="quick-bar">
        <div class="qf-chip active"><i class="bi bi-grid-fill"></i> Todos</div>
        <div class="qf-chip"><i class="bi bi-cpu-fill"></i> Tecnología</div>
        <div class="qf-chip"><i class="bi bi-car-front-fill"></i> Vehículos</div>
        <div class="qf-chip"><i class="bi bi-bag-heart-fill"></i> Ropa</div>
        <div class="qf-chip"><i class="bi bi-house-door-fill"></i> Hogar</div>
        <div class="qf-chip"><i class="bi bi-trophy-fill"></i> Deportes</div>
        <div class="qf-chip"><i class="bi bi-tools"></i> Herramientas</div>
        <div class="qf-chip"><i class="bi bi-book-fill"></i> Libros</div>
        <div class="qf-sep"></div>
        <div class="qf-chip"><i class="bi bi-star-fill" style="color:var(--y400);"></i> Destacados</div>
        <div class="qf-chip"><i class="bi bi-fire" style="color:#ef4444;"></i> Más vistos</div>
        <div class="qf-chip"><i class="bi bi-clock-fill"></i> Recientes</div>
    </div>

    <!-- ══ PAGE SHELL ══ -->
    <div class="page-shell">

        <!-- ── SIDEBAR ── -->
        <aside class="sidebar">
            <div class="filter-panel">
                <div class="fp-header">
                    <h3><i class="bi bi-sliders"></i> Filtros</h3>
                    <span class="fp-reset" onclick="resetFilters()">Limpiar todo</span>
                </div>

                <!-- Sort -->
                <div class="fp-section">
                    <div class="fp-label"><i class="bi bi-sort-down"></i> Ordenar por</div>
                    <select class="sort-select">
                        <option>Más recientes</option>
                        <option>Precio: menor a mayor</option>
                        <option>Precio: mayor a menor</option>
                        <option>Más populares</option>
                        <option>Destacados primero</option>
                    </select>
                </div>

                <!-- Categories -->
                <div class="fp-section">
                    <div class="fp-label"><i class="bi bi-grid-fill"></i> Categoría</div>
                    <?php
                    $iconMap = [
                        'tecnolog' => 'bi-cpu-fill',
                        'ropa'     => 'bi-bag-heart-fill',
                        'moda'     => 'bi-bag-heart-fill',
                        'vehicul'  => 'bi-car-front-fill',
                        'vehícul'  => 'bi-car-front-fill',
                        'hogar'    => 'bi-house-door-fill',
                        'deporte'  => 'bi-trophy-fill',
                        'herramie' => 'bi-tools',
                        'jardin'   => 'bi-leaf-fill',
                        'jardín'   => 'bi-leaf-fill',
                        'libros'   => 'bi-book-fill',
                        'electron' => 'bi-lightning-charge-fill',
                    ];
                    function catIcon($name, $map)
                    {
                        $k = strtolower(trim($name));
                        foreach ($map as $kw => $ic) {
                            if (str_contains($k, $kw)) return $ic;
                        }
                        return 'bi-grid-fill';
                    }
                    if (!empty($categories)): foreach ($categories as $cat): ?>
                            <label class="fp-option">
                                <input type="checkbox">
                                <span class="fp-opt-label">
                                    <i class="bi <?php echo catIcon($cat['name'], $iconMap); ?>" style="color:var(--g500);margin-right:4px;font-size:.8rem;"></i>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </span>
                            </label>
                        <?php endforeach;
                    else: ?>
                        <label class="fp-option"><input type="checkbox"><span class="fp-opt-label"><i class="bi bi-cpu-fill" style="color:var(--g500);margin-right:4px;font-size:.8rem;"></i> Tecnología</span><span class="fp-opt-count">4.2K</span></label>
                        <label class="fp-option"><input type="checkbox"><span class="fp-opt-label"><i class="bi bi-car-front-fill" style="color:var(--g500);margin-right:4px;font-size:.8rem;"></i> Vehículos</span><span class="fp-opt-count">2.9K</span></label>
                        <label class="fp-option"><input type="checkbox"><span class="fp-opt-label"><i class="bi bi-house-door-fill" style="color:var(--g500);margin-right:4px;font-size:.8rem;"></i> Hogar</span><span class="fp-opt-count">5.6K</span></label>
                        <label class="fp-option"><input type="checkbox"><span class="fp-opt-label"><i class="bi bi-trophy-fill" style="color:var(--g500);margin-right:4px;font-size:.8rem;"></i> Deportes</span><span class="fp-opt-count">3.3K</span></label>
                    <?php endif; ?>
                </div>

                <!-- Price range -->
                <div class="fp-section">
                    <div class="fp-label"><i class="bi bi-cash-stack"></i> Precio</div>
                    <div class="price-inputs">
                        <input type="text" class="pi" placeholder="Mín" value="0">
                        <span class="pi-sep">–</span>
                        <input type="text" class="pi" placeholder="Máx" value="10.000.000">
                    </div>
                    <input type="range" class="range-track" min="0" max="10000000" value="5000000">
                </div>

                <!-- Condition -->
                <div class="fp-section">
                    <div class="fp-label"><i class="bi bi-award-fill"></i> Condición</div>
                    <div class="cond-tags">
                        <div class="cond-tag active">Todos</div>
                        <div class="cond-tag">Nuevo</div>
                        <div class="cond-tag">Usado</div>
                        <div class="cond-tag">Reacondicionado</div>
                    </div>
                </div>

                <!-- Seller type -->
                <div class="fp-section">
                    <div class="fp-label"><i class="bi bi-person-fill"></i> Tipo de vendedor</div>
                    <label class="fp-option"><input type="radio" name="seller_type" checked><span class="fp-opt-label">Todos</span></label>
                    <label class="fp-option"><input type="radio" name="seller_type"><span class="fp-opt-label">Persona particular</span></label>
                    <label class="fp-option"><input type="radio" name="seller_type"><span class="fp-opt-label">Tienda verificada</span></label>
                </div>

                <!-- Location -->
                <div class="fp-section">
                    <div class="fp-label"><i class="bi bi-geo-alt-fill"></i> Ubicación</div>
                    <label class="fp-option"><input type="checkbox" checked><span class="fp-opt-label">Bogotá</span><span class="fp-opt-count">18K</span></label>
                    <label class="fp-option"><input type="checkbox"><span class="fp-opt-label">Medellín</span><span class="fp-opt-count">9K</span></label>
                    <label class="fp-option"><input type="checkbox"><span class="fp-opt-label">Cali</span><span class="fp-opt-count">7K</span></label>
                    <label class="fp-option"><input type="checkbox"><span class="fp-opt-label">Barranquilla</span><span class="fp-opt-count">5K</span></label>
                    <label class="fp-option"><input type="checkbox"><span class="fp-opt-label">Cartagena</span><span class="fp-opt-count">3K</span></label>
                </div>

                <div class="fp-section">
                    <button class="btn-apply"><i class="bi bi-funnel-fill"></i> Aplicar filtros</button>
                </div>
            </div>

            <div class="sb-promo">
                <div class="pt">🚀 Premium</div>
                <h4>¿Vendes algo?</h4>
                <p>Publica gratis y llega a miles de compradores en tu ciudad hoy mismo.</p>
                <a href="../app/crear.php">Publicar ahora <i class="bi bi-arrow-right"></i></a>
            </div>
        </aside>

        <!-- ── MAIN CONTENT ── -->
        <div class="main-content">

            <!-- Results header -->
            <div class="results-header">
                <div class="results-left">
                    <h1>Todos los productos en <span>tu ciudad</span></h1>
                    <div class="results-count">
                        <i class="bi bi-grid-3x3-gap-fill"></i>
                        Mostrando <?php echo $totalCount; ?> producto<?php echo $totalCount !== 1 ? 's' : ''; ?>
                    </div>
                </div>
                <div class="results-right">
                    <div class="view-toggle">
                        <button class="vt-btn active" id="gridBtn" title="Cuadrícula"><i class="bi bi-grid-3x3-gap-fill"></i></button>
                        <button class="vt-btn" id="listBtn" title="Lista"><i class="bi bi-list-ul"></i></button>
                    </div>
                    <select class="sort-inline">
                        <option>Más recientes</option>
                        <option>Precio ↑</option>
                        <option>Precio ↓</option>
                        <option>Más vistos</option>
                    </select>
                </div>
            </div>

            <!-- ══ PRODUCT GRID — LOGIC PRESERVED ══ -->
            <div class="product-grid" id="productGrid">
                <?php
                $i = 0;
                if (mysqli_num_rows($result) > 0):
                    while ($row = mysqli_fetch_assoc($result)):
                        $i++;
                        $image      = $row['image_url']
                            ? "./productos/uploads/" . $row['image_url']
                            : null;
                        $isNew      = ($i % 3 !== 0); // alternate for demo; real: use condition_type
                        $isFeatured = ($i % 5 === 0); // every 5th card gets "Destacado"
                        $condType   = strtolower($row['condition_type'] ?? '');
                        $isNewCond  = ($condType === 'nuevo');
                        $initials   = getInitials($row['seller_name'] ?? '');
                        $price      = number_format($row['price'], 0, ',', '.');
                ?>
                        <a href="./actions/detalleProducto.php?id=<?php echo $row['id']; ?>"
                            style="display:contents;">
                            <div class="product-card">

                                <!-- Image -->
                                <div class="pc-img">
                                    <?php if ($image): ?>
                                        <img src="<?php echo htmlspecialchars($image); ?>"
                                            alt="<?php echo htmlspecialchars($row['title']); ?>">
                                    <?php else: ?>
                                        <div class="pc-img-ph"><i class="bi bi-box-seam"></i></div>
                                    <?php endif; ?>

                                    <!-- Condition badge -->
                                    <?php if ($isFeatured): ?>
                                        <div class="badge badge-featured"><i class="bi bi-star-fill"></i> Destacado</div>
                                    <?php elseif ($isNewCond): ?>
                                        <div class="badge badge-new">Nuevo</div>
                                    <?php else: ?>
                                        <div class="badge badge-used">Usado</div>
                                    <?php endif; ?>

                                    <!-- Fav -->
                                    <button class="fav-btn" onclick="event.preventDefault();this.classList.toggle('active');this.querySelector('i').className=this.classList.contains('active')?'bi bi-heart-fill':'bi bi-heart';">
                                        <i class="bi bi-heart"></i>
                                    </button>
                                </div>

                                <!-- Body -->
                                <div class="pc-body">
                                    <div class="pc-cat"><i class="bi bi-tag-fill"></i> General</div>
                                    <div class="pc-title"><?php echo htmlspecialchars($row['title']); ?></div>
                                    <div class="pc-price">$<?php echo $price; ?></div>
                                    <div class="pc-meta">
                                        <div class="pc-meta-row"><i class="bi bi-geo-alt-fill"></i> Bogotá, Colombia</div>
                                        <div class="pc-meta-row"><i class="bi bi-clock-fill"></i> Recientemente</div>
                                    </div>
                                    <div class="pc-seller">
                                        <div class="seller-av"><?php echo $initials; ?></div>
                                        <span class="seller-name-txt"><?php echo htmlspecialchars($row['seller_name'] ?? 'Usuario'); ?></span>
                                        <div class="seller-stars">
                                            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                                            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                                            <i class="bi bi-star-half"></i>
                                            <span>4.5</span>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </a>
                    <?php endwhile;
                else: ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="bi bi-search"></i></div>
                        <div class="empty-title">No hay productos disponibles</div>
                        <div class="empty-sub">Sé el primero en publicar un producto en tu ciudad.</div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ══ LOAD MORE ══ -->
            <?php if ($totalCount > 0): ?>
                <div class="load-more-wrap">
                    <button class="btn-load-more">
                        <i class="bi bi-arrow-repeat"></i>
                        Cargar más productos
                    </button>
                    <div class="pagination" style="margin-top:20px;">
                        <button class="pg-btn"><i class="bi bi-chevron-left"></i></button>
                        <button class="pg-btn active">1</button>
                        <button class="pg-btn">2</button>
                        <button class="pg-btn">3</button>
                        <span class="pg-dots">...</span>
                        <button class="pg-btn">12</button>
                        <button class="pg-btn"><i class="bi bi-chevron-right"></i></button>
                    </div>
                </div>
            <?php endif; ?>

        </div><!-- /main-content -->
    </div><!-- /page-shell -->

    <script>
        /* ── User dropdown ── */
        const wrap = document.getElementById('userMenuWrap');
        const chip = document.getElementById('userChip');
        if (chip) {
            chip.addEventListener('click', e => {
                e.stopPropagation();
                wrap.classList.toggle('open');
            });
            document.addEventListener('click', e => {
                if (!wrap.contains(e.target)) wrap.classList.remove('open');
            });
            document.addEventListener('keydown', e => {
                if (e.key === 'Escape') wrap.classList.remove('open');
            });
        }

        /* ── Quick filter chips ── */
        document.querySelectorAll('.qf-chip').forEach(chip => {
            chip.addEventListener('click', () => {
                document.querySelectorAll('.qf-chip').forEach(c => c.classList.remove('active'));
                chip.classList.add('active');
            });
        });

        /* ── Condition tags ── */
        document.querySelectorAll('.cond-tag').forEach(tag => {
            tag.addEventListener('click', () => {
                document.querySelectorAll('.cond-tag').forEach(t => t.classList.remove('active'));
                tag.classList.add('active');
            });
        });

        /* ── View toggle (grid / list) ── */
        const grid = document.getElementById('productGrid');
        const gridBtn = document.getElementById('gridBtn');
        const listBtn = document.getElementById('listBtn');

        gridBtn.addEventListener('click', () => {
            gridBtn.classList.add('active');
            listBtn.classList.remove('active');
            grid.style.gridTemplateColumns = '';
        });

        listBtn.addEventListener('click', () => {
            listBtn.classList.add('active');
            gridBtn.classList.remove('active');
            grid.style.gridTemplateColumns = '1fr';
            grid.querySelectorAll('.product-card').forEach(c => {
                c.style.flexDirection = 'row';
                const img = c.querySelector('.pc-img');
                if (img) img.style.cssText = 'width:160px;height:auto;flex-shrink:0;';
            });
        });

        /* ── Reset filters ── */
        function resetFilters() {
            document.querySelectorAll('.filter-panel input[type="checkbox"]').forEach(cb => cb.checked = false);
            document.querySelectorAll('.filter-panel input[type="radio"]').forEach((rb, i) => rb.checked = i === 0);
            document.querySelectorAll('.cond-tag').forEach((t, i) => t.classList.toggle('active', i === 0));
        }

        /* ── Pagination buttons ── */
        document.querySelectorAll('.pg-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                if (btn.querySelector('i')) return;
                document.querySelectorAll('.pg-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            });
        });
    </script>
</body>

</html>
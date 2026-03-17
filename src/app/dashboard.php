<?php require_once("../config/conexion.php"); ?>
<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ./auth/register.php");
    exit();
}

/* ── LOGIC PRESERVED ── */
$isLoggedIn = isset($_SESSION['user']);
$user       = $isLoggedIn ? $_SESSION['user'] : null;
$userInitial = $isLoggedIn ? strtoupper(mb_substr($user['full_name'], 0, 1)) : '';
$userName   = $isLoggedIn ? explode(' ', $user['full_name'])[0] : '';
$user_id    = $_SESSION['user']['id'];

/* Quick stats queries (preserved / extended) */
$totalProducts = 0;
$activeListings = 0;
$sql_count = "SELECT COUNT(*) as total FROM products WHERE user_id = '$user_id'";
$r = mysqli_query($conn, $sql_count);
if ($r) { $row_c = mysqli_fetch_assoc($r); $totalProducts = $row_c['total']; }

$sql_active = "SELECT COUNT(*) as total FROM products WHERE user_id = '$user_id' AND status = 1";
$r2 = mysqli_query($conn, $sql_active);
if ($r2) { $row_a = mysqli_fetch_assoc($r2); $activeListings = $row_a['total']; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard – ComercioLocal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <style>
    :root {
      --g900: #0b2e17; --g800: #103d1e; --g700: #185228;
      --g600: #1e6b33; --g500: #25883f; --g400: #34b357;
      --g300: #55d475; --g200: #96e8b0; --g100: #c8f2d5; --g50: #edfaf3;

      --y600: #b07000; --y500: #d48c0a; --y400: #f5a81c;
      --y300: #fcc034; --y200: #fdd878; --y100: #fef0bc; --y50: #fffbe8;

      --ink:  #0d1f13; --ink2: #2d4035; --ink3: #5a7065;
      --bg:   #f0f7f2; --card: #ffffff;
      --border: #d4e8da;
      --r: 16px;
      --shadow: 0 2px 14px rgba(10,40,20,.08), 0 1px 3px rgba(10,40,20,.05);
      --shadow-lg: 0 8px 32px rgba(10,40,20,.14), 0 2px 8px rgba(10,40,20,.06);
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--bg);
      color: var(--ink);
      min-height: 100vh;
      display: flex; flex-direction: column;
    }

    h1,h2,h3,h4,h5 { font-family: 'Syne', sans-serif; }
    a { text-decoration: none; color: inherit; }

    /* ══════════════════════════════════
       TOP BAR
    ══════════════════════════════════ */
    .topbar {
      height: 66px;
      background: var(--g900);
      border-bottom: 3px solid var(--y400);
      display: flex; align-items: center;
      padding: 0 24px; gap: 16px;
      position: sticky; top: 0; z-index: 200;
      box-shadow: 0 4px 24px rgba(0,0,0,.3);
      flex-shrink: 0;
    }

    .tb-logo {
      display: flex; align-items: center; gap: 10px;
      font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.25rem;
      color: #fff; white-space: nowrap; flex-shrink: 0;
      text-decoration: none;
    }
    .tb-logo-box {
      width: 36px; height: 36px; background: var(--y400);
      border-radius: 9px; display: flex; align-items: center;
      justify-content: center; color: var(--g900); font-size: 1rem;
      box-shadow: 0 3px 10px rgba(245,168,28,.4);
    }
    .tb-logo em { color: var(--y400); font-style: normal; }

    .tb-search {
      flex: 1; max-width: 380px;
      display: flex; align-items: center;
      background: rgba(255,255,255,.1);
      border: 1.5px solid rgba(255,255,255,.15);
      border-radius: 50px; overflow: hidden;
    }
    .tb-search input {
      flex: 1; background: transparent; border: none; outline: none;
      color: #fff; font-family: 'DM Sans', sans-serif;
      font-size: .87rem; padding: 9px 16px;
    }
    .tb-search input::placeholder { color: rgba(255,255,255,.4); }
    .tb-search button {
      background: none; border: none; cursor: pointer;
      color: rgba(255,255,255,.5); padding: 9px 14px; font-size: .95rem;
      transition: color .2s;
    }
    .tb-search button:hover { color: var(--y300); }

    .tb-spacer { flex: 1; }

    .tb-actions { display: flex; align-items: center; gap: 10px; }

    .tb-icon-btn {
      width: 38px; height: 38px; border-radius: 10px;
      background: rgba(255,255,255,.08);
      border: 1.5px solid rgba(255,255,255,.12);
      display: flex; align-items: center; justify-content: center;
      color: rgba(255,255,255,.7); font-size: 1.05rem;
      cursor: pointer; transition: all .2s; position: relative;
    }
    .tb-icon-btn:hover { background: rgba(255,255,255,.16); color: var(--y300); }

    .notif-dot {
      position: absolute; top: 6px; right: 6px;
      width: 8px; height: 8px; border-radius: 50%;
      background: var(--y400);
      border: 2px solid var(--g900);
      animation: pulse-dot 2s infinite;
    }
    @keyframes pulse-dot {
      0%,100% { transform: scale(1); opacity: 1; }
      50%      { transform: scale(1.3); opacity: .7; }
    }

    /* User chip */
    .user-chip {
      display: flex; align-items: center; gap: 9px;
      background: rgba(255,255,255,.08);
      border: 1.5px solid rgba(255,255,255,.18);
      border-radius: 50px; padding: 5px 13px 5px 5px;
      cursor: pointer; transition: all .2s; user-select: none;
      position: relative;
    }
    .user-chip:hover { background: rgba(255,255,255,.14); border-color: var(--y400); }

    .user-avatar {
      width: 34px; height: 34px; border-radius: 50%;
      background: linear-gradient(135deg, var(--y400), var(--y300));
      display: flex; align-items: center; justify-content: center;
      font-family: 'Syne', sans-serif; font-weight: 800; font-size: .88rem;
      color: var(--g900); flex-shrink: 0;
      box-shadow: 0 0 0 2px rgba(245,168,28,.4);
    }
    .online-dot {
      position: absolute; bottom: 1px; right: 1px;
      width: 9px; height: 9px; border-radius: 50%;
      background: var(--g400); border: 2px solid var(--g900);
    }
    .uc-info { line-height: 1.2; }
    .uc-greeting { font-size: .67rem; color: rgba(255,255,255,.4); }
    .uc-name { font-size: .83rem; font-weight: 600; color: #fff; max-width: 90px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
    .uc-arrow { font-size: .68rem; color: rgba(255,255,255,.4); transition: transform .2s; }
    .user-menu-wrap.open .uc-arrow { transform: rotate(180deg); }

    /* User dropdown */
    .user-dropdown {
      position: absolute; top: calc(100% + 10px); right: 0;
      width: 220px; background: #fff;
      border-radius: 14px;
      box-shadow: 0 12px 40px rgba(10,40,20,.18), 0 2px 8px rgba(10,40,20,.07);
      border: 1.5px solid var(--g100);
      overflow: hidden;
      opacity: 0; pointer-events: none;
      transform: translateY(-8px) scale(.97);
      transition: all .2s cubic-bezier(.22,.9,.36,1);
      z-index: 300;
    }
    .user-menu-wrap.open .user-dropdown { opacity: 1; pointer-events: all; transform: translateY(0) scale(1); }

    .ud-head {
      background: linear-gradient(135deg, var(--g900), var(--g800));
      padding: 16px; display: flex; align-items: center; gap: 10px;
    }
    .ud-av { width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, var(--y400), var(--y300)); display: flex; align-items: center; justify-content: center; font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1rem; color: var(--g900); box-shadow: 0 0 0 2px rgba(245,168,28,.3); flex-shrink: 0; }
    .ud-name { font-family: 'Syne', sans-serif; font-weight: 700; font-size: .88rem; color: #fff; }
    .ud-email { font-size: .7rem; color: rgba(255,255,255,.45); margin-top: 1px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; max-width: 130px; }

    .ud-body { padding: 6px 0; }
    .ud-lnk { display: flex; align-items: center; gap: 10px; padding: 9px 16px; font-size: .84rem; color: var(--ink2); cursor: pointer; transition: background .15s; }
    .ud-lnk:hover { background: var(--g50); color: var(--g700); }
    .ud-lnk i { width: 18px; text-align: center; color: var(--g500); font-size: .9rem; }
    .ud-sep { height: 1px; background: var(--g100); margin: 3px 0; }
    .ud-logout { display: flex; align-items: center; gap: 10px; padding: 9px 16px; font-size: .84rem; color: #dc2626; cursor: pointer; transition: background .15s; }
    .ud-logout:hover { background: #fef2f2; }
    .ud-logout i { width: 18px; text-align: center; }

    .user-menu-wrap { position: relative; }

    /* ══════════════════════════════════
       PAGE SHELL
    ══════════════════════════════════ */
    .page-shell {
      display: flex; flex: 1; min-height: 0;
    }

    /* ══════════════════════════════════
       SIDEBAR
    ══════════════════════════════════ */
    .sidebar {
      width: 228px; flex-shrink: 0;
      background: var(--g900);
      display: flex; flex-direction: column;
      padding: 24px 0 40px;
      position: sticky; top: 66px;
      height: calc(100vh - 66px);
      overflow-y: auto;
    }

    .sb-section-label {
      font-size: .67rem; font-weight: 700;
      text-transform: uppercase; letter-spacing: .1em;
      color: rgba(255,255,255,.28);
      padding: 16px 20px 8px;
    }

    .sb-link {
      display: flex; align-items: center; gap: 11px;
      padding: 10px 20px;
      color: rgba(255,255,255,.55); font-size: .86rem;
      border-left: 3px solid transparent;
      transition: all .2s; cursor: pointer;
      text-decoration: none; position: relative;
    }
    .sb-link i { font-size: .98rem; width: 18px; text-align: center; }
    .sb-link:hover { color: #fff; background: rgba(255,255,255,.05); }
    .sb-link.active { color: var(--y300); background: rgba(245,168,28,.08); border-left-color: var(--y400); }

    .sb-badge {
      margin-left: auto; background: var(--g500); color: #fff;
      font-size: .65rem; font-weight: 700;
      padding: 1px 6px; border-radius: 20px;
    }
    .sb-badge.yellow { background: var(--y400); color: var(--g900); }

    .sb-divider { height: 1px; background: rgba(255,255,255,.07); margin: 10px 16px; }

    .sb-promo {
      margin: 18px 14px 0;
      background: linear-gradient(135deg, var(--g700), var(--g800));
      border: 1px solid rgba(255,255,255,.1);
      border-radius: 14px; padding: 16px;
    }
    .sb-promo-icon { font-size: 1.5rem; margin-bottom: 7px; }
    .sb-promo p { font-size: .76rem; color: rgba(255,255,255,.55); line-height: 1.5; margin-bottom: 12px; }
    .sb-promo a {
      display: block; text-align: center;
      background: var(--y400); color: var(--g900);
      padding: 7px; border-radius: 8px;
      font-family: 'Syne', sans-serif; font-weight: 700; font-size: .76rem;
      transition: background .2s;
    }
    .sb-promo a:hover { background: var(--y300); }

    /* ══════════════════════════════════
       MAIN CONTENT
    ══════════════════════════════════ */
    .main-content {
      flex: 1; padding: 28px 32px 60px;
      overflow-y: auto; min-width: 0;
    }

    /* Page header */
    .pg-header {
      display: flex; align-items: flex-end; justify-content: space-between;
      margin-bottom: 28px;
    }
    .pg-breadcrumb {
      display: flex; align-items: center; gap: 6px;
      font-size: .75rem; color: var(--ink3); margin-bottom: 6px;
    }
    .pg-breadcrumb a { color: var(--g500); }
    .pg-breadcrumb a:hover { text-decoration: underline; }
    .pg-title { font-size: 1.75rem; font-weight: 800; color: var(--ink); line-height: 1.2; }
    .pg-title span { color: var(--g500); }
    .pg-subtitle { font-size: .875rem; color: var(--ink3); margin-top: 4px; }

    .btn-publish-new {
      display: flex; align-items: center; gap: 9px;
      background: linear-gradient(135deg, var(--g500), var(--g400));
      color: #fff; border: none; border-radius: 14px;
      padding: 13px 22px;
      font-family: 'Syne', sans-serif; font-weight: 700; font-size: .92rem;
      cursor: pointer; text-decoration: none;
      box-shadow: 0 4px 18px rgba(37,136,63,.35);
      transition: all .25s; white-space: nowrap;
    }
    .btn-publish-new:hover {
      background: linear-gradient(135deg, var(--g600), var(--g500));
      box-shadow: 0 6px 24px rgba(37,136,63,.45);
      transform: translateY(-2px);
    }
    .btn-publish-new .btn-icon {
      width: 26px; height: 26px; border-radius: 50%;
      background: rgba(255,255,255,.2);
      display: flex; align-items: center; justify-content: center;
      font-size: .95rem;
    }

    /* ══════════════════════════════════
       STATS CARDS
    ══════════════════════════════════ */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 18px; margin-bottom: 32px;
    }

    .stat-card {
      background: var(--card);
      border-radius: var(--r);
      border: 1.5px solid var(--border);
      padding: 22px;
      box-shadow: var(--shadow);
      position: relative; overflow: hidden;
      transition: all .25s;
      animation: fadeUp .45s both;
    }
    .stat-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); }
    .stat-card:nth-child(1) { animation-delay: .05s; }
    .stat-card:nth-child(2) { animation-delay: .10s; }
    .stat-card:nth-child(3) { animation-delay: .15s; }
    .stat-card:nth-child(4) { animation-delay: .20s; }

    .stat-card::before {
      content: ''; position: absolute;
      top: -18px; right: -18px;
      width: 80px; height: 80px; border-radius: 50%;
      opacity: .08;
    }
    .stat-card.green-card::before { background: var(--g400); }
    .stat-card.yellow-card::before { background: var(--y400); }
    .stat-card.blue-card::before   { background: #3b82f6; }
    .stat-card.red-card::before    { background: #ef4444; }

    .stat-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 16px; }
    .stat-icon {
      width: 46px; height: 46px; border-radius: 13px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.25rem;
    }
    .si-green  { background: var(--g100); color: var(--g700); }
    .si-yellow { background: var(--y100); color: var(--y600); }
    .si-blue   { background: #dbeafe; color: #1d4ed8; }
    .si-red    { background: #fee2e2; color: #b91c1c; }

    .stat-trend {
      display: flex; align-items: center; gap: 4px;
      font-size: .73rem; font-weight: 600; padding: 3px 8px;
      border-radius: 20px;
    }
    .trend-up   { background: var(--g100); color: var(--g700); }
    .trend-down { background: #fee2e2; color: #b91c1c; }
    .trend-flat { background: #f0f0f0; color: var(--ink3); }

    .stat-value {
      font-family: 'Syne', sans-serif;
      font-weight: 800; font-size: 2rem;
      color: var(--ink); line-height: 1;
      margin-bottom: 4px;
    }
    .stat-label { font-size: .82rem; color: var(--ink3); font-weight: 500; }

    .stat-bar-wrap {
      margin-top: 14px; height: 4px;
      background: var(--border); border-radius: 4px; overflow: hidden;
    }
    .stat-bar { height: 100%; border-radius: 4px; }
    .bar-green  { background: linear-gradient(to right, var(--g400), var(--g300)); }
    .bar-yellow { background: linear-gradient(to right, var(--y400), var(--y300)); }
    .bar-blue   { background: linear-gradient(to right, #3b82f6, #93c5fd); }
    .bar-red    { background: linear-gradient(to right, #ef4444, #fca5a5); }

    /* ══════════════════════════════════
       QUICK ACTIONS ROW
    ══════════════════════════════════ */
    .quick-row {
      display: grid; grid-template-columns: repeat(4,1fr);
      gap: 14px; margin-bottom: 32px;
    }
    .quick-card {
      background: var(--card); border: 1.5px solid var(--border);
      border-radius: 14px; padding: 16px;
      display: flex; align-items: center; gap: 12px;
      cursor: pointer; transition: all .2s;
      box-shadow: var(--shadow);
      text-decoration: none;
      animation: fadeUp .45s both;
    }
    .quick-card:nth-child(1) { animation-delay: .25s; }
    .quick-card:nth-child(2) { animation-delay: .30s; }
    .quick-card:nth-child(3) { animation-delay: .35s; }
    .quick-card:nth-child(4) { animation-delay: .40s; }

    .quick-card:hover { border-color: var(--g300); background: var(--g50); transform: translateY(-2px); }
    .qc-icon {
      width: 40px; height: 40px; border-radius: 11px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.1rem; flex-shrink: 0;
    }
    .qc-label { font-size: .83rem; font-weight: 600; color: var(--ink2); }
    .qc-sub   { font-size: .71rem; color: var(--ink3); margin-top: 1px; }

    /* ══════════════════════════════════
       MY PRODUCTS SECTION
    ══════════════════════════════════ */
    .section-block { margin-bottom: 36px; }

    .section-head {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 20px;
    }
    .section-head-left {}
    .section-title-main {
      font-size: 1.3rem; font-weight: 800; color: var(--ink);
    }
    .section-title-main span { color: var(--g500); }
    .section-sub-txt { font-size: .8rem; color: var(--ink3); margin-top: 3px; }

    .section-accent-line {
      width: 40px; height: 3px;
      background: linear-gradient(to right, var(--y400), var(--g400));
      border-radius: 3px; margin-top: 7px;
    }

    .section-head-actions { display: flex; gap: 10px; align-items: center; }

    .btn-outline {
      border: 1.5px solid var(--border); background: var(--card);
      color: var(--ink2); border-radius: 10px; padding: 8px 16px;
      font-family: 'DM Sans', sans-serif; font-size: .84rem; font-weight: 500;
      cursor: pointer; display: flex; align-items: center; gap: 6px;
      transition: all .2s; text-decoration: none;
    }
    .btn-outline:hover { border-color: var(--g400); color: var(--g600); background: var(--g50); }

    .view-toggle { display: flex; gap: 4px; }
    .vt-btn {
      width: 34px; height: 34px; border-radius: 8px;
      border: 1.5px solid var(--border); background: var(--card);
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; color: var(--ink3); font-size: .9rem;
      transition: all .2s;
    }
    .vt-btn:hover, .vt-btn.active {
      border-color: var(--g400); color: var(--g600); background: var(--g50);
    }

    /* Product grid */
    .products-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
      gap: 18px;
    }

    .product-card {
      background: var(--card);
      border-radius: var(--r);
      border: 1.5px solid var(--border);
      box-shadow: var(--shadow);
      overflow: hidden;
      transition: all .28s cubic-bezier(.175,.885,.32,1.275);
      position: relative;
      animation: fadeUp .45s both;
    }
    .product-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); border-color: var(--g200); }

    .pc-img-slot {
      height: 162px; position: relative;
      background: linear-gradient(135deg, var(--g50), var(--g100));
      display: flex; align-items: center; justify-content: center;
      font-size: 3rem; color: var(--g200);
      overflow: hidden;
    }
    .pc-img-slot img { width: 100%; height: 100%; object-fit: cover; display: block; }

    .pc-status-badge {
      position: absolute; top: 10px; left: 10px;
      font-size: .65rem; font-weight: 700; padding: 3px 9px; border-radius: 20px;
      text-transform: uppercase; letter-spacing: .04em;
    }
    .status-active { background: var(--g500); color: #fff; }
    .status-inactive { background: #f0f0f0; color: var(--ink3); }

    .pc-actions-top {
      position: absolute; top: 10px; right: 10px;
      display: flex; gap: 5px;
    }
    .pc-action-btn {
      width: 30px; height: 30px; border-radius: 50%;
      border: none; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      font-size: .8rem; transition: all .2s;
      backdrop-filter: blur(6px);
    }
    .pc-action-btn.edit { background: rgba(255,255,255,.88); color: var(--g600); }
    .pc-action-btn.edit:hover { background: var(--g500); color: #fff; transform: scale(1.1); }
    .pc-action-btn.del  { background: rgba(255,255,255,.88); color: #dc2626; }
    .pc-action-btn.del:hover  { background: #dc2626; color: #fff; transform: scale(1.1); }

    .pc-body { padding: 14px 16px 16px; }

    .pc-category {
      display: inline-flex; align-items: center; gap: 4px;
      background: var(--g100); color: var(--g700);
      font-size: .68rem; font-weight: 600;
      padding: 2px 8px; border-radius: 20px; margin-bottom: 7px;
    }

    .pc-title {
      font-size: .9rem; font-weight: 600; color: var(--ink);
      margin-bottom: 6px; line-height: 1.35;
      display: -webkit-box; -webkit-line-clamp: 2;
      -webkit-box-orient: vertical; overflow: hidden;
    }

    .pc-price {
      font-family: 'Syne', sans-serif;
      font-weight: 800; font-size: 1.1rem;
      color: var(--g600); margin-bottom: 10px;
    }

    .pc-meta { display: flex; flex-direction: column; gap: 3px; margin-bottom: 14px; }
    .pc-meta-row { display: flex; align-items: center; gap: 5px; font-size: .75rem; color: var(--ink3); }
    .pc-meta-row i { font-size: .78rem; color: var(--g400); }

    .pc-footer {
      border-top: 1.5px solid var(--border);
      padding-top: 12px;
      display: flex; gap: 8px;
    }

    .pc-btn {
      flex: 1; border-radius: 10px; padding: 8px;
      font-family: 'DM Sans', sans-serif; font-size: .81rem; font-weight: 600;
      cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px;
      transition: all .2s; text-decoration: none;
    }
    .pc-btn-edit {
      background: var(--g50); border: 1.5px solid var(--g200);
      color: var(--g700);
    }
    .pc-btn-edit:hover { background: var(--g500); border-color: var(--g500); color: #fff; }

    .pc-btn-del {
      background: #fef2f2; border: 1.5px solid #fecaca;
      color: #dc2626;
    }
    .pc-btn-del:hover { background: #dc2626; border-color: #dc2626; color: #fff; }

    /* Empty state */
    .empty-state {
      text-align: center; padding: 60px 20px;
      background: var(--card); border-radius: var(--r);
      border: 2px dashed var(--border);
    }
    .empty-icon {
      width: 72px; height: 72px; border-radius: 50%;
      background: var(--g100); color: var(--g500);
      font-size: 1.8rem;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 16px;
    }
    .empty-title { font-size: 1.1rem; font-weight: 700; color: var(--ink); margin-bottom: 6px; }
    .empty-sub { font-size: .85rem; color: var(--ink3); margin-bottom: 20px; line-height: 1.55; }

    /* ══════════════════════════════════
       BOTTOM STRIP — Activity
    ══════════════════════════════════ */
    .activity-strip {
      background: var(--card);
      border-radius: var(--r); border: 1.5px solid var(--border);
      box-shadow: var(--shadow); padding: 22px 24px;
      margin-bottom: 32px;
    }
    .as-header {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 18px;
    }
    .as-title { font-size: 1rem; font-weight: 700; color: var(--ink); }
    .as-see-all { font-size: .8rem; color: var(--g500); font-weight: 600; display: flex; align-items: center; gap: 4px; }
    .as-see-all:hover { text-decoration: underline; }

    .activity-list { display: flex; flex-direction: column; gap: 12px; }
    .act-item {
      display: flex; align-items: center; gap: 14px;
      padding: 10px 14px; border-radius: 12px;
      border: 1.5px solid var(--border);
      transition: background .2s;
    }
    .act-item:hover { background: var(--g50); }
    .act-icon {
      width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center; font-size: 1rem;
    }
    .ai-green  { background: var(--g100); color: var(--g700); }
    .ai-yellow { background: var(--y100); color: var(--y600); }
    .ai-blue   { background: #dbeafe; color: #1d4ed8; }
    .act-text { flex: 1; }
    .act-title { font-size: .84rem; font-weight: 600; color: var(--ink); }
    .act-sub   { font-size: .74rem; color: var(--ink3); margin-top: 1px; }
    .act-time  { font-size: .72rem; color: var(--ink3); white-space: nowrap; }

    /* ══════════════════════════════════
       ANIMATIONS
    ══════════════════════════════════ */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(18px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* scrollbar */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--g200); border-radius: 6px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--g300); }
  </style>
</head>
<body>

<!-- ══════════════════════════════════
     TOP BAR
══════════════════════════════════ -->
<header class="topbar">
  <a href="./index.php" class="tb-logo">
    <div class="tb-logo-box"><i class="bi bi-shop-window"></i></div>
    Comercio<em>Local</em>
  </a>

  <div class="tb-search">
    <input type="text" placeholder="Buscar productos, anuncios...">
    <button><i class="bi bi-search"></i></button>
  </div>

  <div class="tb-spacer"></div>

  <div class="tb-actions">

    <div class="tb-icon-btn">
      <i class="bi bi-bell"></i>
      <div class="notif-dot"></div>
    </div>
    <div class="tb-icon-btn">
      <i class="bi bi-chat-dots"></i>
    </div>
    <div class="tb-icon-btn">
      <i class="bi bi-question-circle"></i>
    </div>

    <!-- User chip + dropdown — LOGIC PRESERVED -->
    <?php if ($isLoggedIn): ?>
    <div class="user-menu-wrap" id="userMenuWrap">
      <div class="user-chip" id="userChip">
        <div style="position:relative;">
          <div class="user-avatar"><?php echo $userInitial; ?></div>
          <div class="online-dot"></div>
        </div>
        <div class="uc-info">
          <div class="uc-greeting">Hola,</div>
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
          <a class="ud-lnk" href="#"><i class="bi bi-speedometer2"></i> Dashboard</a>
          <a class="ud-lnk" href="./crear.php"><i class="bi bi-plus-square"></i> Publicar producto</a>
          <a class="ud-lnk" href="#"><i class="bi bi-box-seam"></i> Mis anuncios</a>
          <a class="ud-lnk" href="#"><i class="bi bi-person-circle"></i> Mi perfil</a>
          <div class="ud-sep"></div>
          <a class="ud-logout" href="../controller/logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>
</header>

<div class="page-shell">

  <!-- ══════════════════════════════════
       SIDEBAR
  ══════════════════════════════════ -->
  <aside class="sidebar">
    <div class="sb-section-label">Principal</div>

    <a class="sb-link active" href="#"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a class="sb-link" href="./crear.php"><i class="bi bi-plus-square-fill"></i> Publicar producto</a>
    <a class="sb-link" href="#">
      <i class="bi bi-box-seam"></i> Mis productos
      <span class="sb-badge"><?php echo $totalProducts; ?></span>
    </a>
    <a class="sb-link" href="#">
      <i class="bi bi-chat-dots"></i> Mensajes
      <span class="sb-badge yellow">5</span>
    </a>
    <a class="sb-link" href="#"><i class="bi bi-graph-up-arrow"></i> Ventas</a>
    <a class="sb-link" href="#"><i class="bi bi-heart"></i> Favoritos</a>

    <div class="sb-divider"></div>
    <div class="sb-section-label">Cuenta</div>

    <a class="sb-link" href="#"><i class="bi bi-person-circle"></i> Mi perfil</a>
    <a class="sb-link" href="#"><i class="bi bi-star"></i> Reseñas</a>
    <a class="sb-link" href="#"><i class="bi bi-gear"></i> Configuración</a>
    <a class="sb-link" href="../controller/logout.php" style="color:rgba(239,68,68,.7);">
      <i class="bi bi-box-arrow-right"></i> Cerrar sesión
    </a>

    <div class="sb-divider"></div>

    <div class="sb-promo">
      <div class="sb-promo-icon">⭐</div>
      <p>Destaca tu anuncio y llega a 10× más compradores hoy.</p>
      <a href="#">Ver planes</a>
    </div>
  </aside>

  <!-- ══════════════════════════════════
       MAIN
  ══════════════════════════════════ -->
  <main class="main-content">

    <!-- Page header -->
    <div class="pg-header">
      <div>
        <div class="pg-breadcrumb">
          <a href="./index.php">Inicio</a>
          <i class="bi bi-chevron-right" style="font-size:.65rem;"></i>
          <span>Dashboard</span>
        </div>
        <h1 class="pg-title">
          Panel de <span>vendedor</span>
        </h1>
        <p class="pg-subtitle">
          Bienvenido, <strong><?php echo htmlspecialchars($user['full_name']); ?></strong> — aquí está el resumen de tu actividad.
        </p>
      </div>
      <a href="./crear.php" class="btn-publish-new">
        <div class="btn-icon"><i class="bi bi-plus-lg"></i></div>
        Publicar producto
      </a>
    </div>

    <!-- ── STATS CARDS ── -->
    <div class="stats-grid">

      <div class="stat-card green-card">
        <div class="stat-top">
          <div class="stat-icon si-green"><i class="bi bi-box-seam-fill"></i></div>
          <div class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> +2</div>
        </div>
        <!-- LOGIC PRESERVED: PHP count query -->
        <div class="stat-value"><?php echo $totalProducts; ?></div>
        <div class="stat-label">Total de productos</div>
        <div class="stat-bar-wrap"><div class="stat-bar bar-green" style="width:<?php echo min($totalProducts * 10, 100); ?>%"></div></div>
      </div>

      <div class="stat-card yellow-card">
        <div class="stat-top">
          <div class="stat-icon si-yellow"><i class="bi bi-chat-dots-fill"></i></div>
          <div class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> +3</div>
        </div>
        <div class="stat-value">5</div>
        <div class="stat-label">Mensajes recibidos</div>
        <div class="stat-bar-wrap"><div class="stat-bar bar-yellow" style="width:50%"></div></div>
      </div>

      <div class="stat-card blue-card">
        <div class="stat-top">
          <div class="stat-icon si-blue"><i class="bi bi-eye-fill"></i></div>
          <div class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> +12%</div>
        </div>
        <div class="stat-value">248</div>
        <div class="stat-label">Vistas en productos</div>
        <div class="stat-bar-wrap"><div class="stat-bar bar-blue" style="width:68%"></div></div>
      </div>

      <div class="stat-card green-card">
        <div class="stat-top">
          <div class="stat-icon si-green"><i class="bi bi-check-circle-fill"></i></div>
          <!-- LOGIC PRESERVED: active listings count -->
          <div class="stat-trend trend-flat"><i class="bi bi-dash"></i> estable</div>
        </div>
        <div class="stat-value"><?php echo $activeListings; ?></div>
        <div class="stat-label">Anuncios activos</div>
        <div class="stat-bar-wrap"><div class="stat-bar bar-green" style="width:<?php echo $totalProducts > 0 ? round($activeListings/$totalProducts*100) : 0; ?>%"></div></div>
      </div>

    </div>

    <!-- ── QUICK ACTIONS ── -->
    <div class="quick-row">
      <a href="./crear.php" class="quick-card">
        <div class="qc-icon" style="background:var(--g100);color:var(--g700);"><i class="bi bi-plus-square-fill"></i></div>
        <div><div class="qc-label">Nuevo anuncio</div><div class="qc-sub">Publicar producto</div></div>
      </a>
      <a href="#" class="quick-card">
        <div class="qc-icon" style="background:var(--y100);color:var(--y600);"><i class="bi bi-chat-dots-fill"></i></div>
        <div><div class="qc-label">Ver mensajes</div><div class="qc-sub">5 sin leer</div></div>
      </a>
      <a href="#" class="quick-card">
        <div class="qc-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="bi bi-graph-up-arrow"></i></div>
        <div><div class="qc-label">Estadísticas</div><div class="qc-sub">Ver rendimiento</div></div>
      </a>
      <a href="#" class="quick-card">
        <div class="qc-icon" style="background:#f3e8ff;color:#7c3aed;"><i class="bi bi-star-fill"></i></div>
        <div><div class="qc-label">Destacar</div><div class="qc-sub">Planes premium</div></div>
      </a>
    </div>

    <!-- ── MY PRODUCTS — LOGIC PRESERVED ── -->
    <div class="section-block">
      <div class="section-head">
        <div class="section-head-left">
          <div class="section-title-main">Mis <span>productos</span></div>
          <div class="section-sub-txt">Administra y edita tus anuncios publicados</div>
          <div class="section-accent-line"></div>
        </div>
        <div class="section-head-actions">
          <div class="view-toggle">
            <button class="vt-btn active" title="Cuadrícula"><i class="bi bi-grid-3x3-gap-fill"></i></button>
            <button class="vt-btn" title="Lista"><i class="bi bi-list-ul"></i></button>
          </div>
          <a href="./crear.php" class="btn-outline"><i class="bi bi-plus-lg"></i> Nuevo</a>
        </div>
      </div>

      <div class="products-grid" id="productsGrid">

        <?php
        /* ── LOGIC 100% PRESERVED: original query ── */
        $sql = "SELECT p.*, c.name as category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.user_id = '$user_id'
                ORDER BY p.id DESC";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) === 0): ?>

          <div class="empty-state" style="grid-column:1/-1;">
            <div class="empty-icon"><i class="bi bi-box-seam"></i></div>
            <div class="empty-title">No tienes productos publicados aún</div>
            <div class="empty-sub">Publica tu primer anuncio y empieza a vender<br>en tu ciudad hoy mismo.</div>
            <a href="./crear.php" class="btn-publish-new" style="display:inline-flex;">
              <div class="btn-icon"><i class="bi bi-plus-lg"></i></div>
              Publicar primer producto
            </a>
          </div>

        <?php else: ?>

          <?php $card_i = 0; while ($row = mysqli_fetch_assoc($result)): $card_i++; ?>

            <div class="product-card" style="animation-delay:<?php echo $card_i * 0.07; ?>s;">

              <!-- Image slot -->
              <div class="pc-img-slot">
                <?php
                /* LOGIC PRESERVED: fetch product images */
                $pid = $row['id'];
                $img_q = "SELECT * FROM product_images WHERE product_id = '$pid' LIMIT 1";
                $img_r = mysqli_query($conn, $img_q);
                $img_row = mysqli_fetch_assoc($img_r);
                if ($img_row): ?>
                  <img src="./productos/uploads/<?php echo htmlspecialchars($img_row['image_url']); ?>" alt="">
                <?php else: ?>
                  <i class="bi bi-box-seam"></i>
                <?php endif; ?>

                <!-- Status badge -->
                <div class="pc-status-badge <?php echo ($row['status'] == 1) ? 'status-active' : 'status-inactive'; ?>">
                  <?php echo ($row['status'] == 1) ? '● Activo' : '○ Inactivo'; ?>
                </div>

                <!-- Quick action buttons on image -->
                <div class="pc-actions-top">
                  <a href="./edit_product.php?id=<?php echo $row['id']; ?>" class="pc-action-btn edit" title="Editar">
                    <i class="bi bi-pencil-fill"></i>
                  </a>
                  <a href="./delete_product.php?id=<?php echo $row['id']; ?>" class="pc-action-btn del" title="Eliminar"
                     onclick="return confirm('¿Eliminar este producto?')">
                    <i class="bi bi-trash-fill"></i>
                  </a>
                </div>
              </div>

              <!-- Card body -->
              <div class="pc-body">
                <?php if (!empty($row['category_name'])): ?>
                  <div class="pc-category"><i class="bi bi-tag-fill"></i><?php echo htmlspecialchars($row['category_name']); ?></div>
                <?php endif; ?>

                <div class="pc-title"><?php echo htmlspecialchars($row['title']); ?></div>
                <div class="pc-price">$<?php echo number_format($row['price'], 0, ',', '.'); ?></div>

                <div class="pc-meta">
                  <?php if (!empty($row['condition_type'])): ?>
                    <div class="pc-meta-row">
                      <i class="bi bi-award-fill"></i>
                      <?php echo $row['condition_type'] === 'new' ? 'Nuevo' : 'Usado'; ?>
                    </div>
                  <?php endif; ?>
                  <?php if (!empty($row['latitude']) && !empty($row['longitude'])): ?>
                    <div class="pc-meta-row">
                      <i class="bi bi-geo-alt-fill"></i>
                      <?php echo round($row['latitude'], 4); ?>, <?php echo round($row['longitude'], 4); ?>
                    </div>
                  <?php endif; ?>
                  <div class="pc-meta-row">
                    <i class="bi bi-clock-fill"></i>
                    <?php echo !empty($row['created_at']) ? date('d M Y', strtotime($row['created_at'])) : 'Publicado'; ?>
                  </div>
                </div>

                <!-- Footer action buttons -->
                <div class="pc-footer">
                  <a href="./edit_product.php?id=<?php echo $row['id']; ?>" class="pc-btn pc-btn-edit">
                    <i class="bi bi-pencil"></i> Editar
                  </a>
                  <a href="./delete_product.php?id=<?php echo $row['id']; ?>" class="pc-btn pc-btn-del"
                     onclick="return confirm('¿Seguro que deseas eliminar este producto?')">
                    <i class="bi bi-trash"></i> Eliminar
                  </a>
                </div>
              </div>

            </div>

          <?php endwhile; ?>
        <?php endif; ?>

      </div>
    </div>

    <!-- ── RECENT ACTIVITY ── -->
    <div class="activity-strip">
      <div class="as-header">
        <div class="as-title">Actividad reciente</div>
        <a href="#" class="as-see-all">Ver todo <i class="bi bi-arrow-right"></i></a>
      </div>
      <div class="activity-list">
        <div class="act-item">
          <div class="act-icon ai-blue"><i class="bi bi-eye-fill"></i></div>
          <div class="act-text">
            <div class="act-title">Alguien vio tu anuncio "iPhone 13"</div>
            <div class="act-sub">Chapinero, Bogotá</div>
          </div>
          <div class="act-time">Hace 5 min</div>
        </div>
        <div class="act-item">
          <div class="act-icon ai-yellow"><i class="bi bi-chat-dots-fill"></i></div>
          <div class="act-text">
            <div class="act-title">Nuevo mensaje de Carlos A.</div>
            <div class="act-sub">"¿Está disponible el viernes?"</div>
          </div>
          <div class="act-time">Hace 23 min</div>
        </div>
        <div class="act-item">
          <div class="act-icon ai-green"><i class="bi bi-check-circle-fill"></i></div>
          <div class="act-text">
            <div class="act-title">Tu anuncio fue aprobado</div>
            <div class="act-sub">Bicicleta MTB Trek 29"</div>
          </div>
          <div class="act-time">Hace 1 hora</div>
        </div>
      </div>
    </div>

  </main>
</div><!-- /page-shell -->

<script>
  /* ── User dropdown toggle ── */
  const wrap = document.getElementById('userMenuWrap');
  const chip = document.getElementById('userChip');
  if (chip) {
    chip.addEventListener('click', e => { e.stopPropagation(); wrap.classList.toggle('open'); });
    document.addEventListener('click', e => { if (!wrap.contains(e.target)) wrap.classList.remove('open'); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') wrap.classList.remove('open'); });
  }

  /* ── View toggle ── */
  document.querySelectorAll('.vt-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.vt-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const grid = document.getElementById('productsGrid');
      if (btn.querySelector('.bi-list-ul')) {
        grid.style.gridTemplateColumns = '1fr';
        grid.querySelectorAll('.product-card').forEach(c => { c.style.display = 'flex'; });
      } else {
        grid.style.gridTemplateColumns = '';
        grid.querySelectorAll('.product-card').forEach(c => { c.style.display = ''; });
      }
    });
  });
</script>

</body>
</html>
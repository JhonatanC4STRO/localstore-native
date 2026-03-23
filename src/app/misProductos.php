<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ComercioLocal — Mis Productos</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  :root {
    --green-deep:   #1a6b3c;
    --green-mid:    #22913f;
    --green-bright: #2ec15a;
    --green-light:  #d4f5e2;
    --green-pale:   #f0faf4;
    --yellow:       #f5c800;
    --yellow-light: #fff8d6;
    --yellow-dark:  #c9a200;
    --ink:          #0f1f14;
    --ink-mid:      #2e4535;
    --muted:        #6b8577;
    --border:       #d6ead9;
    --surface:      #ffffff;
    --bg:           #f3faf6;
    --red:          #e8344a;
    --red-light:    #fdeef1;
    --orange:       #f57c00;
    --blue:         #1976d2;
    --shadow-sm:    0 2px 8px rgba(34,145,63,.10);
    --shadow-md:    0 6px 24px rgba(26,107,60,.14);
    --shadow-lg:    0 16px 48px rgba(26,107,60,.18);
    --radius:       14px;
    --radius-sm:    8px;
    --sidebar-w:    252px;
    --nav-h:        68px;
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--ink);
    min-height: 100vh;
    overflow-x: hidden;
  }

  /* ─── TOPBAR ─────────────────────────────────────────── */
  .topbar {
    position: fixed; top: 0; left: 0; right: 0; z-index: 100;
    height: var(--nav-h);
    background: var(--green-deep);
    display: flex; align-items: center;
    padding: 0 24px 0 0;
    box-shadow: 0 2px 20px rgba(0,0,0,.22);
  }

  .topbar-logo {
    width: var(--sidebar-w);
    display: flex; align-items: center; gap: 10px;
    padding: 0 24px;
    flex-shrink: 0;
    text-decoration: none;
  }

  .logo-mark {
    width: 38px; height: 38px;
    background: var(--yellow);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    box-shadow: 0 0 0 3px rgba(245,200,0,.35);
  }

  .logo-text {
    font-family: 'Syne', sans-serif;
    font-weight: 800;
    color: #fff;
    font-size: 17px;
    line-height: 1.1;
  }
  .logo-text span { color: var(--yellow); }

  .topbar-center {
    flex: 1;
    display: flex; align-items: center;
    padding: 0 16px;
  }

  .topbar-search {
    max-width: 420px; width: 100%;
    background: rgba(255,255,255,.12);
    border: 1.5px solid rgba(255,255,255,.18);
    border-radius: 50px;
    display: flex; align-items: center; gap: 10px;
    padding: 8px 18px;
    transition: background .2s;
  }
  .topbar-search:focus-within {
    background: rgba(255,255,255,.22);
    border-color: var(--yellow);
  }
  .topbar-search i { color: rgba(255,255,255,.6); font-size: 14px; }
  .topbar-search input {
    background: none; border: none; outline: none;
    color: #fff; font-family: 'DM Sans', sans-serif;
    font-size: 14px; width: 100%;
  }
  .topbar-search input::placeholder { color: rgba(255,255,255,.5); }

  .topbar-actions {
    display: flex; align-items: center; gap: 6px; margin-left: auto;
  }

  .icon-btn {
    width: 40px; height: 40px; border-radius: 50%;
    border: none; cursor: pointer;
    background: rgba(255,255,255,.12);
    color: rgba(255,255,255,.9);
    font-size: 16px;
    display: flex; align-items: center; justify-content: center;
    transition: background .2s, transform .15s;
    position: relative;
  }
  .icon-btn:hover { background: rgba(255,255,255,.22); transform: scale(1.08); }

  .notif-badge {
    position: absolute; top: 5px; right: 5px;
    width: 8px; height: 8px;
    background: var(--yellow);
    border-radius: 50%;
    border: 2px solid var(--green-deep);
  }

  .avatar-btn {
    width: 40px; height: 40px; border-radius: 50%;
    overflow: hidden; cursor: pointer;
    border: 2.5px solid var(--yellow);
    transition: transform .15s, box-shadow .15s;
  }
  .avatar-btn:hover { transform: scale(1.08); box-shadow: 0 0 0 4px rgba(245,200,0,.3); }
  .avatar-btn img { width: 100%; height: 100%; object-fit: cover; }

  .avatar-placeholder {
    width: 100%; height: 100%;
    background: linear-gradient(135deg, #22913f, #2ec15a);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Syne', sans-serif;
    font-weight: 700; color: #fff; font-size: 15px;
  }

  /* ─── LAYOUT ─────────────────────────────────────────── */
  .layout {
    display: flex;
    margin-top: var(--nav-h);
    min-height: calc(100vh - var(--nav-h));
  }

  /* ─── SIDEBAR ─────────────────────────────────────────── */
  .sidebar {
    width: var(--sidebar-w);
    flex-shrink: 0;
    background: var(--surface);
    border-right: 1.5px solid var(--border);
    position: sticky; top: var(--nav-h);
    height: calc(100vh - var(--nav-h));
    overflow-y: auto;
    display: flex; flex-direction: column;
    padding: 16px 0 24px;
  }

  .sidebar-section-label {
    font-size: 10px; font-weight: 600;
    letter-spacing: .12em;
    color: var(--muted);
    text-transform: uppercase;
    padding: 12px 20px 6px;
  }

  .nav-item {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 20px; margin: 2px 10px;
    border-radius: var(--radius-sm);
    cursor: pointer;
    text-decoration: none;
    color: var(--ink-mid);
    font-size: 14px; font-weight: 500;
    transition: background .15s, color .15s;
    position: relative;
  }
  .nav-item:hover {
    background: var(--green-pale);
    color: var(--green-deep);
  }
  .nav-item.active {
    background: var(--green-light);
    color: var(--green-deep);
    font-weight: 600;
  }
  .nav-item.active::before {
    content: '';
    position: absolute; left: -10px; top: 50%; transform: translateY(-50%);
    width: 4px; height: 28px;
    background: var(--green-mid);
    border-radius: 0 4px 4px 0;
  }

  .nav-icon {
    width: 32px; height: 32px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px;
    background: rgba(34,145,63,.1);
    color: var(--green-mid);
    transition: background .15s;
    flex-shrink: 0;
  }
  .nav-item.active .nav-icon { background: var(--green-mid); color: #fff; }
  .nav-item:hover .nav-icon { background: var(--green-mid); color: #fff; }

  .nav-badge {
    margin-left: auto; background: var(--yellow);
    color: var(--ink); font-size: 10px; font-weight: 700;
    padding: 2px 7px; border-radius: 20px;
  }

  .sidebar-publish-btn {
    margin: 16px 12px 0;
    background: linear-gradient(135deg, var(--yellow) 0%, #ffda00 100%);
    color: var(--ink);
    border: none; border-radius: var(--radius);
    padding: 12px 16px;
    font-family: 'Syne', sans-serif;
    font-weight: 700; font-size: 13px;
    cursor: pointer;
    display: flex; align-items: center; gap: 10px;
    box-shadow: 0 4px 16px rgba(245,200,0,.4);
    transition: transform .15s, box-shadow .15s;
  }
  .sidebar-publish-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(245,200,0,.5);
  }
  .sidebar-publish-btn i { font-size: 15px; }

  .sidebar-footer {
    margin-top: auto; padding: 16px 12px 0;
    border-top: 1.5px solid var(--border);
  }
  .sidebar-user {
    display: flex; align-items: center; gap: 10px;
    padding: 10px;
    border-radius: var(--radius);
    background: var(--green-pale);
  }
  .sidebar-user-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: linear-gradient(135deg, var(--green-mid), var(--green-bright));
    display: flex; align-items: center; justify-content: center;
    font-family: 'Syne', sans-serif; font-weight: 700;
    color: #fff; font-size: 14px;
    flex-shrink: 0;
  }
  .sidebar-user-info { flex: 1; min-width: 0; }
  .sidebar-user-name {
    font-size: 13px; font-weight: 600;
    color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }
  .sidebar-user-role { font-size: 11px; color: var(--muted); }

  /* ─── MAIN CONTENT ─────────────────────────────────────── */
  .main {
    flex: 1;
    padding: 28px 32px 48px;
    max-width: 1200px;
  }

  /* Page header */
  .page-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    margin-bottom: 28px;
    flex-wrap: wrap; gap: 16px;
  }
  .page-title-group {}
  .page-breadcrumb {
    font-size: 12px; color: var(--muted);
    display: flex; align-items: center; gap: 6px; margin-bottom: 6px;
  }
  .page-breadcrumb span { color: var(--green-mid); font-weight: 500; }
  .page-title {
    font-family: 'Syne', sans-serif;
    font-size: 30px; font-weight: 800;
    color: var(--ink);
    display: flex; align-items: center; gap: 12px;
  }
  .page-title-badge {
    font-size: 13px; font-weight: 600;
    background: var(--green-light);
    color: var(--green-deep);
    padding: 4px 12px; border-radius: 20px;
    font-family: 'DM Sans', sans-serif;
  }
  .page-subtitle { font-size: 14px; color: var(--muted); margin-top: 4px; }

  .publish-btn {
    background: linear-gradient(135deg, #22913f 0%, var(--green-bright) 100%);
    color: #fff; border: none;
    padding: 12px 22px;
    border-radius: var(--radius);
    font-family: 'Syne', sans-serif;
    font-weight: 700; font-size: 14px;
    cursor: pointer;
    display: flex; align-items: center; gap: 10px;
    box-shadow: 0 4px 18px rgba(34,145,63,.4);
    transition: transform .15s, box-shadow .15s;
  }
  .publish-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(34,145,63,.5);
  }
  .publish-btn-alt {
    background: linear-gradient(135deg, var(--yellow) 0%, #ffda00 100%);
    color: var(--ink);
    box-shadow: 0 4px 18px rgba(245,200,0,.4);
  }
  .publish-btn-alt:hover { box-shadow: 0 8px 28px rgba(245,200,0,.55); }

  /* ─── STAT CARDS ─────────────────────────────────────────── */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 28px;
  }

  .stat-card {
    background: var(--surface);
    border-radius: var(--radius);
    padding: 20px;
    border: 1.5px solid var(--border);
    box-shadow: var(--shadow-sm);
    transition: transform .2s, box-shadow .2s;
    position: relative;
    overflow: hidden;
  }
  .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }

  .stat-card::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 4px;
  }
  .stat-card.c1::before { background: linear-gradient(90deg, var(--green-mid), var(--green-bright)); }
  .stat-card.c2::before { background: linear-gradient(90deg, #1976d2, #42a5f5); }
  .stat-card.c3::before { background: linear-gradient(90deg, var(--orange), #ffca28); }
  .stat-card.c4::before { background: linear-gradient(90deg, #9c27b0, #e040fb); }

  .stat-card-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
  .stat-icon {
    width: 44px; height: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
  }
  .stat-card.c1 .stat-icon { background: var(--green-light); color: var(--green-deep); }
  .stat-card.c2 .stat-icon { background: #e3f2fd; color: #1976d2; }
  .stat-card.c3 .stat-icon { background: #fff3e0; color: var(--orange); }
  .stat-card.c4 .stat-icon { background: #f3e5f5; color: #9c27b0; }

  .stat-trend {
    font-size: 11px; font-weight: 600;
    display: flex; align-items: center; gap: 3px;
    padding: 3px 8px; border-radius: 20px;
  }
  .stat-trend.up { background: #d4f5e2; color: var(--green-deep); }
  .stat-trend.down { background: #fdeef1; color: var(--red); }
  .stat-trend.neutral { background: #fff8d6; color: var(--yellow-dark); }

  .stat-value {
    font-family: 'Syne', sans-serif;
    font-size: 32px; font-weight: 800;
    color: var(--ink); line-height: 1;
    margin-bottom: 4px;
  }
  .stat-label { font-size: 13px; color: var(--muted); font-weight: 500; }
  .stat-sub { font-size: 11px; color: var(--muted); margin-top: 8px; }

  .stat-bar-bg { height: 4px; background: var(--border); border-radius: 2px; margin-top: 12px; overflow: hidden; }
  .stat-bar-fill { height: 100%; border-radius: 2px; }
  .stat-card.c1 .stat-bar-fill { background: var(--green-mid); }
  .stat-card.c2 .stat-bar-fill { background: #1976d2; }
  .stat-card.c3 .stat-bar-fill { background: var(--orange); }
  .stat-card.c4 .stat-bar-fill { background: #9c27b0; }

  /* ─── CONTROLS BAR ─────────────────────────────────────── */
  .controls-bar {
    display: flex; align-items: center; gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
  }

  .search-box {
    display: flex; align-items: center; gap: 10px;
    background: var(--surface);
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 9px 16px;
    flex: 1; min-width: 200px; max-width: 340px;
    transition: border-color .2s, box-shadow .2s;
  }
  .search-box:focus-within {
    border-color: var(--green-mid);
    box-shadow: 0 0 0 3px rgba(34,145,63,.12);
  }
  .search-box i { color: var(--muted); font-size: 14px; }
  .search-box input {
    border: none; outline: none; font-family: 'DM Sans', sans-serif;
    font-size: 14px; color: var(--ink); background: none; width: 100%;
  }
  .search-box input::placeholder { color: var(--muted); }

  .filter-select {
    background: var(--surface);
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 9px 14px;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px; color: var(--ink);
    cursor: pointer; outline: none;
    transition: border-color .2s;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%236b8577' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    padding-right: 32px;
  }
  .filter-select:focus { border-color: var(--green-mid); }

  .view-toggle {
    display: flex; background: var(--surface); border: 1.5px solid var(--border);
    border-radius: var(--radius-sm); overflow: hidden;
  }
  .view-btn {
    padding: 9px 14px; border: none; background: none;
    color: var(--muted); cursor: pointer;
    font-size: 14px; transition: background .15s, color .15s;
  }
  .view-btn.active { background: var(--green-mid); color: #fff; }

  .controls-right { margin-left: auto; display: flex; align-items: center; gap: 10px; }

  .tag-filters {
    display: flex; gap: 8px; flex-wrap: wrap;
    margin-bottom: 20px;
  }
  .tag {
    padding: 5px 14px; border-radius: 20px; font-size: 12px;
    font-weight: 600; cursor: pointer; border: 1.5px solid transparent;
    transition: all .15s;
  }
  .tag.all { background: var(--green-mid); color: #fff; }
  .tag.active-tag { background: var(--green-light); color: var(--green-deep); border-color: var(--green-mid); }
  .tag.sold-tag { background: #fff3e0; color: var(--orange); border-color: var(--orange); }
  .tag.paused-tag { background: #f5f5f5; color: #757575; border-color: #bdbdbd; }

  /* ─── PRODUCT GRID ─────────────────────────────────────── */
  .products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 18px;
  }

  .product-card {
    background: var(--surface);
    border-radius: var(--radius);
    border: 1.5px solid var(--border);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    transition: transform .2s, box-shadow .2s;
    display: flex; flex-direction: column;
  }
  .product-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }

  .product-img-wrap {
    position: relative; height: 190px; overflow: hidden;
    background: linear-gradient(135deg, #e8f5e9, #f1f8e9);
  }
  .product-img-wrap img {
    width: 100%; height: 100%; object-fit: cover;
    transition: transform .4s;
  }
  .product-card:hover .product-img-wrap img { transform: scale(1.05); }

  .product-img-placeholder {
    width: 100%; height: 100%;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 8px; color: var(--muted);
  }
  .product-img-placeholder i { font-size: 40px; opacity: .4; }
  .product-img-placeholder span { font-size: 12px; opacity: .6; }

  .product-status-badge {
    position: absolute; top: 12px; left: 12px;
    padding: 4px 12px; border-radius: 20px;
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .04em;
    backdrop-filter: blur(8px);
  }
  .product-status-badge.active {
    background: rgba(34,145,63,.85); color: #fff;
  }
  .product-status-badge.sold {
    background: rgba(245,124,0,.88); color: #fff;
  }
  .product-status-badge.paused {
    background: rgba(100,100,100,.78); color: #fff;
  }

  .product-fav-btn {
    position: absolute; top: 10px; right: 10px;
    width: 32px; height: 32px; border-radius: 50%;
    background: rgba(255,255,255,.85);
    backdrop-filter: blur(8px);
    border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; color: var(--muted);
    transition: color .15s, transform .15s;
  }
  .product-fav-btn:hover { color: var(--red); transform: scale(1.12); }

  .product-views {
    position: absolute; bottom: 10px; right: 10px;
    background: rgba(0,0,0,.55); color: rgba(255,255,255,.9);
    font-size: 11px; padding: 3px 9px; border-radius: 20px;
    display: flex; align-items: center; gap: 4px;
    backdrop-filter: blur(6px);
  }

  .product-body { padding: 16px; flex: 1; display: flex; flex-direction: column; gap: 8px; }

  .product-category {
    font-size: 11px; font-weight: 600; text-transform: uppercase;
    letter-spacing: .06em; color: var(--green-mid);
  }

  .product-title {
    font-family: 'Syne', sans-serif;
    font-size: 15px; font-weight: 700; color: var(--ink);
    line-height: 1.3;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
  }

  .product-price {
    font-family: 'Syne', sans-serif;
    font-size: 20px; font-weight: 800;
    color: var(--green-deep);
  }
  .product-price .currency { font-size: 14px; font-weight: 600; }

  .product-meta { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
  .product-meta-item {
    display: flex; align-items: center; gap: 4px;
    font-size: 12px; color: var(--muted);
  }
  .product-meta-item i { font-size: 11px; }

  .product-footer {
    padding: 12px 16px;
    border-top: 1px solid var(--border);
    display: flex; gap: 8px; align-items: center;
  }

  .prod-btn {
    flex: 1; padding: 9px 0; border-radius: 8px;
    border: none; cursor: pointer; font-family: 'DM Sans', sans-serif;
    font-size: 13px; font-weight: 600;
    display: flex; align-items: center; justify-content: center; gap: 6px;
    transition: transform .15s, box-shadow .15s;
  }
  .prod-btn:hover { transform: translateY(-1px); }
  .prod-btn-edit {
    background: var(--green-light); color: var(--green-deep);
  }
  .prod-btn-edit:hover { background: var(--green-mid); color: #fff; box-shadow: 0 4px 12px rgba(34,145,63,.3); }
  .prod-btn-delete {
    background: var(--red-light); color: var(--red);
    flex: 0 0 38px; border-radius: 8px;
    width: 38px; height: 38px; padding: 0;
  }
  .prod-btn-delete:hover { background: var(--red); color: #fff; box-shadow: 0 4px 12px rgba(232,52,74,.3); }

  /* ─── EMPTY STATE ─────────────────────────────────────── */
  .empty-state {
    text-align: center; padding: 80px 40px;
    background: var(--surface); border-radius: var(--radius);
    border: 2px dashed var(--border);
    display: none;
  }
  .empty-state.show { display: block; }

  .empty-illustration {
    width: 120px; height: 120px; border-radius: 50%;
    background: linear-gradient(135deg, var(--green-light), var(--yellow-light));
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 24px;
    font-size: 52px;
  }

  .empty-title {
    font-family: 'Syne', sans-serif; font-size: 22px; font-weight: 800;
    color: var(--ink); margin-bottom: 10px;
  }
  .empty-sub { font-size: 15px; color: var(--muted); max-width: 320px; margin: 0 auto 28px; }

  /* ─── SECTION HEADER ─────────────────────────────────────── */
  .section-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 16px;
  }
  .section-title {
    font-family: 'Syne', sans-serif;
    font-size: 16px; font-weight: 700; color: var(--ink);
    display: flex; align-items: center; gap: 10px;
  }
  .section-count {
    background: var(--green-light); color: var(--green-deep);
    font-size: 12px; font-weight: 700;
    padding: 2px 9px; border-radius: 20px;
  }
  .section-action {
    font-size: 13px; color: var(--green-mid); font-weight: 600;
    cursor: pointer; text-decoration: none;
    display: flex; align-items: center; gap: 4px;
  }
  .section-action:hover { text-decoration: underline; }

  /* ─── PROMO BANNER ─────────────────────────────────────── */
  .promo-banner {
    background: linear-gradient(135deg, var(--green-deep) 0%, #1a5e3c 50%, #0d3e27 100%);
    border-radius: var(--radius);
    padding: 24px 28px;
    display: flex; align-items: center; justify-content: space-between;
    gap: 20px;
    margin-bottom: 28px;
    position: relative; overflow: hidden;
  }
  .promo-banner::before {
    content: '';
    position: absolute; right: -40px; top: -40px;
    width: 200px; height: 200px;
    background: radial-gradient(circle, rgba(245,200,0,.18) 0%, transparent 70%);
    border-radius: 50%;
  }
  .promo-banner::after {
    content: '';
    position: absolute; right: 80px; bottom: -60px;
    width: 160px; height: 160px;
    background: radial-gradient(circle, rgba(46,193,90,.22) 0%, transparent 70%);
    border-radius: 50%;
  }
  .promo-text { z-index: 1; }
  .promo-label {
    font-size: 11px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
    color: var(--yellow); margin-bottom: 8px;
    display: flex; align-items: center; gap: 6px;
  }
  .promo-title {
    font-family: 'Syne', sans-serif; font-size: 20px; font-weight: 800;
    color: #fff; margin-bottom: 8px; line-height: 1.2;
  }
  .promo-sub { font-size: 13px; color: rgba(255,255,255,.7); }
  .promo-cta {
    z-index: 1;
    background: var(--yellow); color: var(--ink);
    border: none; padding: 12px 24px; border-radius: var(--radius);
    font-family: 'Syne', sans-serif; font-weight: 700; font-size: 14px;
    cursor: pointer; white-space: nowrap;
    box-shadow: 0 4px 18px rgba(245,200,0,.45);
    transition: transform .15s, box-shadow .15s;
  }
  .promo-cta:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(245,200,0,.55); }

  /* ─── ANIMATIONS ─────────────────────────────────────────── */
  @keyframes fadeSlideUp {
    from { opacity: 0; transform: translateY(18px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .stat-card { animation: fadeSlideUp .4s ease both; }
  .stat-card:nth-child(1) { animation-delay: .05s; }
  .stat-card:nth-child(2) { animation-delay: .10s; }
  .stat-card:nth-child(3) { animation-delay: .15s; }
  .stat-card:nth-child(4) { animation-delay: .20s; }
  .product-card { animation: fadeSlideUp .4s ease both; }
  .product-card:nth-child(1) { animation-delay: .10s; }
  .product-card:nth-child(2) { animation-delay: .15s; }
  .product-card:nth-child(3) { animation-delay: .20s; }
  .product-card:nth-child(4) { animation-delay: .25s; }
  .product-card:nth-child(5) { animation-delay: .30s; }
  .product-card:nth-child(6) { animation-delay: .35s; }

  /* ─── TOAST ─────────────────────────────────────────────── */
  .toast {
    position: fixed; bottom: 24px; right: 24px;
    background: var(--ink); color: #fff;
    padding: 12px 20px; border-radius: var(--radius);
    font-size: 14px; font-weight: 500;
    display: flex; align-items: center; gap: 10px;
    box-shadow: var(--shadow-lg);
    z-index: 9999;
    transform: translateY(80px); opacity: 0;
    transition: transform .3s, opacity .3s;
  }
  .toast.show { transform: translateY(0); opacity: 1; }
  .toast i { font-size: 16px; }
  .toast.green i { color: var(--green-bright); }
  .toast.red i { color: #ff8a8a; }

  /* ─── RESPONSIVE ─────────────────────────────────────────── */
  @media (max-width: 1100px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .main { padding: 20px 20px 40px; }
  }
  @media (max-width: 768px) {
    .sidebar { display: none; }
    .stats-grid { grid-template-columns: 1fr 1fr; }
    .topbar-search { display: none; }
  }
</style>
</head>
<body>

<!-- ═══════════════ TOPBAR ═══════════════ -->
<nav class="topbar">
  <a href="#" class="topbar-logo">
    <div class="logo-mark">🌿</div>
    <div class="logo-text">Comercio<span>Local</span></div>
  </a>

  <div class="topbar-center">
    <div class="topbar-search">
      <i class="fas fa-search"></i>
      <input type="text" placeholder="Buscar en el marketplace…">
    </div>
  </div>

  <div class="topbar-actions">
    <button class="icon-btn" title="Mensajes">
      <i class="fas fa-comment-alt"></i>
      <span class="notif-badge"></span>
    </button>
    <button class="icon-btn" title="Notificaciones">
      <i class="fas fa-bell"></i>
      <span class="notif-badge"></span>
    </button>
    <button class="icon-btn" title="Ayuda">
      <i class="fas fa-question-circle"></i>
    </button>
    <div class="avatar-btn" title="Mi perfil">
      <div class="avatar-placeholder">CG</div>
    </div>
  </div>
</nav>

<!-- ═══════════════ LAYOUT ═══════════════ -->
<div class="layout">

  <!-- ═══════════════ SIDEBAR ═══════════════ -->
  <aside class="sidebar">
    <div class="sidebar-section-label">Principal</div>

    <a href="#" class="nav-item">
      <div class="nav-icon"><i class="fas fa-th-large"></i></div>
      Dashboard
    </a>
    <a href="#" class="nav-item active">
      <div class="nav-icon"><i class="fas fa-box-open"></i></div>
      Mis Productos
      <span class="nav-badge">12</span>
    </a>
    <a href="#" class="nav-item">
      <div class="nav-icon"><i class="fas fa-comment-dots"></i></div>
      Mensajes
      <span class="nav-badge">3</span>
    </a>

    <div class="sidebar-section-label" style="margin-top:8px">Gestión</div>

    <a href="#" class="nav-item">
      <div class="nav-icon"><i class="fas fa-chart-line"></i></div>
      Estadísticas
    </a>
    <a href="#" class="nav-item">
      <div class="nav-icon"><i class="fas fa-star"></i></div>
      Reseñas
    </a>
    <a href="#" class="nav-item">
      <div class="nav-icon"><i class="fas fa-wallet"></i></div>
      Pagos
    </a>

    <div class="sidebar-section-label" style="margin-top:8px">Cuenta</div>

    <a href="#" class="nav-item">
      <div class="nav-icon"><i class="fas fa-cog"></i></div>
      Configuración
    </a>
    <a href="#" class="nav-item">
      <div class="nav-icon"><i class="fas fa-shield-alt"></i></div>
      Privacidad
    </a>

    <button class="sidebar-publish-btn" onclick="showToast('¡Listo! Redirigiendo a publicar producto...', 'green')">
      <i class="fas fa-plus-circle"></i>
      Publicar Producto
    </button>

    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="sidebar-user-avatar">CG</div>
        <div class="sidebar-user-info">
          <div class="sidebar-user-name">Carlos García</div>
          <div class="sidebar-user-role">Vendedor verificado ✓</div>
        </div>
      </div>
    </div>
  </aside>

  <!-- ═══════════════ MAIN ═══════════════ -->
  <main class="main">

    <!-- Page header -->
    <div class="page-header">
      <div class="page-title-group">
        <div class="page-breadcrumb">
          <i class="fas fa-home"></i>
          <i class="fas fa-chevron-right" style="font-size:9px"></i>
          <span>Mis Productos</span>
        </div>
        <h1 class="page-title">
          Mis productos
          <span class="page-title-badge">12 publicaciones</span>
        </h1>
        <p class="page-subtitle">Administra, edita y controla todas tus publicaciones</p>
      </div>
      <button class="publish-btn publish-btn-alt" onclick="showToast('Redirigiendo a nuevo producto...', 'green')">
        <i class="fas fa-plus"></i>
        Publicar nuevo producto
      </button>
    </div>

    <!-- Promo banner -->
    <div class="promo-banner">
      <div class="promo-text">
        <div class="promo-label">⚡ Consejo del día</div>
        <div class="promo-title">Mejora tus ventas con fotos de calidad</div>
        <div class="promo-sub">Los anuncios con 5+ fotos generan un 78% más de contactos</div>
      </div>
      <button class="promo-cta">Ver guía gratuita →</button>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card c1">
        <div class="stat-card-top">
          <div class="stat-icon"><i class="fas fa-box-open"></i></div>
          <div class="stat-trend up"><i class="fas fa-arrow-up"></i> +2</div>
        </div>
        <div class="stat-value">12</div>
        <div class="stat-label">Total productos</div>
        <div class="stat-sub">Última publicación hace 2 días</div>
        <div class="stat-bar-bg"><div class="stat-bar-fill" style="width:75%"></div></div>
      </div>
      <div class="stat-card c2">
        <div class="stat-card-top">
          <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
          <div class="stat-trend up"><i class="fas fa-arrow-up"></i> 83%</div>
        </div>
        <div class="stat-value">10</div>
        <div class="stat-label">Productos activos</div>
        <div class="stat-sub">2 en revisión por moderación</div>
        <div class="stat-bar-bg"><div class="stat-bar-fill" style="width:83%"></div></div>
      </div>
      <div class="stat-card c3">
        <div class="stat-card-top">
          <div class="stat-icon"><i class="fas fa-handshake"></i></div>
          <div class="stat-trend neutral"><i class="fas fa-minus"></i> Este mes</div>
        </div>
        <div class="stat-value">7</div>
        <div class="stat-label">Vendidos</div>
        <div class="stat-sub">+3 respecto al mes pasado</div>
        <div class="stat-bar-bg"><div class="stat-bar-fill" style="width:58%"></div></div>
      </div>
      <div class="stat-card c4">
        <div class="stat-card-top">
          <div class="stat-icon"><i class="fas fa-eye"></i></div>
          <div class="stat-trend up"><i class="fas fa-arrow-up"></i> +320</div>
        </div>
        <div class="stat-value">4.2K</div>
        <div class="stat-label">Vistas totales</div>
        <div class="stat-sub">Promedio 350 por semana</div>
        <div class="stat-bar-bg"><div class="stat-bar-fill" style="width:91%"></div></div>
      </div>
    </div>

    <!-- Controls -->
    <div class="controls-bar">
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Buscar mis productos..." id="searchInput" oninput="filterProducts()">
      </div>
      <select class="filter-select" id="statusFilter" onchange="filterProducts()">
        <option value="all">Todos los estados</option>
        <option value="active">Activos</option>
        <option value="sold">Vendidos</option>
        <option value="paused">Pausados</option>
      </select>
      <select class="filter-select" id="sortFilter" onchange="filterProducts()">
        <option value="newest">Más recientes</option>
        <option value="price-asc">Precio: menor a mayor</option>
        <option value="price-desc">Precio: mayor a menor</option>
        <option value="views">Más vistos</option>
      </select>
      <div class="view-toggle">
        <button class="view-btn active" onclick="setView('grid',this)" title="Cuadrícula"><i class="fas fa-th"></i></button>
        <button class="view-btn" onclick="setView('list',this)" title="Lista"><i class="fas fa-list"></i></button>
      </div>
    </div>

    <div class="tag-filters">
      <span class="tag all" onclick="setTagFilter('all',this)">Todos (12)</span>
      <span class="tag active-tag" onclick="setTagFilter('active',this)">● Activos (10)</span>
      <span class="tag sold-tag" onclick="setTagFilter('sold',this)">✓ Vendidos (7)</span>
      <span class="tag paused-tag" onclick="setTagFilter('paused',this)">⏸ Pausados (2)</span>
    </div>

    <!-- Section header -->
    <div class="section-header">
      <div class="section-title">
        Publicaciones
        <span class="section-count" id="productCount">12 productos</span>
      </div>
      <a href="#" class="section-action">Ver archivados <i class="fas fa-arrow-right"></i></a>
    </div>

    <!-- Products Grid -->
    <div class="products-grid" id="productsGrid">

      <!-- Card 1 -->
      <div class="product-card" data-status="active" data-title="Bicicleta de montaña Trek" data-price="850000">
        <div class="product-img-wrap" style="background: linear-gradient(135deg,#e8f5e9,#c8e6c9)">
          <div class="product-img-placeholder">
            <i class="fas fa-bicycle"></i>
            <span>Sin imagen</span>
          </div>
          <span class="product-status-badge active">● Activo</span>
          <button class="product-fav-btn"><i class="fas fa-heart"></i></button>
          <div class="product-views"><i class="fas fa-eye"></i> 312</div>
        </div>
        <div class="product-body">
          <div class="product-category">Deportes</div>
          <div class="product-title">Bicicleta de montaña Trek Marlin 7 2023</div>
          <div class="product-price"><span class="currency">$</span>850.000</div>
          <div class="product-meta">
            <div class="product-meta-item"><i class="fas fa-map-marker-alt"></i> Bogotá, Chapinero</div>
            <div class="product-meta-item"><i class="fas fa-clock"></i> Hace 2 días</div>
          </div>
        </div>
        <div class="product-footer">
          <button class="prod-btn prod-btn-edit" onclick="showToast('Editando: Bicicleta Trek', 'green')"><i class="fas fa-pen"></i> Editar</button>
          <button class="prod-btn prod-btn-delete" onclick="showToast('Producto eliminado', 'red')" title="Eliminar"><i class="fas fa-trash"></i></button>
        </div>
      </div>

      <!-- Card 2 -->
      <div class="product-card" data-status="active" data-title="iPhone 13 Pro Max" data-price="3200000">
        <div class="product-img-wrap" style="background: linear-gradient(135deg,#e3f2fd,#bbdefb)">
          <div class="product-img-placeholder">
            <i class="fas fa-mobile-alt"></i>
            <span>Sin imagen</span>
          </div>
          <span class="product-status-badge active">● Activo</span>
          <button class="product-fav-btn"><i class="fas fa-heart"></i></button>
          <div class="product-views"><i class="fas fa-eye"></i> 843</div>
        </div>
        <div class="product-body">
          <div class="product-category">Electrónica</div>
          <div class="product-title">iPhone 13 Pro Max 256GB Azul Sierra</div>
          <div class="product-price"><span class="currency">$</span>3.200.000</div>
          <div class="product-meta">
            <div class="product-meta-item"><i class="fas fa-map-marker-alt"></i> Medellín, El Poblado</div>
            <div class="product-meta-item"><i class="fas fa-clock"></i> Hace 5 días</div>
          </div>
        </div>
        <div class="product-footer">
          <button class="prod-btn prod-btn-edit" onclick="showToast('Editando: iPhone 13 Pro Max', 'green')"><i class="fas fa-pen"></i> Editar</button>
          <button class="prod-btn prod-btn-delete" onclick="showToast('Producto eliminado', 'red')" title="Eliminar"><i class="fas fa-trash"></i></button>
        </div>
      </div>

      <!-- Card 3 - SOLD -->
      <div class="product-card" data-status="sold" data-title="Silla de oficina ergonómica" data-price="450000">
        <div class="product-img-wrap" style="background: linear-gradient(135deg,#fff3e0,#ffe0b2)">
          <div class="product-img-placeholder">
            <i class="fas fa-chair"></i>
            <span>Sin imagen</span>
          </div>
          <span class="product-status-badge sold">✓ Vendido</span>
          <button class="product-fav-btn"><i class="fas fa-heart"></i></button>
          <div class="product-views"><i class="fas fa-eye"></i> 176</div>
        </div>
        <div class="product-body">
          <div class="product-category" style="color:var(--orange)">Hogar</div>
          <div class="product-title">Silla de oficina ergonómica Herman Miller Aeron</div>
          <div class="product-price" style="color:var(--muted);text-decoration:line-through"><span class="currency">$</span>450.000</div>
          <div class="product-meta">
            <div class="product-meta-item"><i class="fas fa-map-marker-alt"></i> Cali, Ciudad Jardín</div>
            <div class="product-meta-item"><i class="fas fa-check-circle" style="color:var(--orange)"></i> Vendido hace 3 días</div>
          </div>
        </div>
        <div class="product-footer">
          <button class="prod-btn prod-btn-edit" onclick="showToast('No puedes editar un producto vendido', 'red')" style="background:#f5f5f5;color:#aaa;cursor:not-allowed"><i class="fas fa-pen"></i> Editar</button>
          <button class="prod-btn prod-btn-delete" onclick="showToast('Publicación archivada', 'red')" title="Archivar"><i class="fas fa-archive"></i></button>
        </div>
      </div>

      <!-- Card 4 -->
      <div class="product-card" data-status="active" data-title="Mesa de madera artesanal" data-price="780000">
        <div class="product-img-wrap" style="background: linear-gradient(135deg,#efebe9,#d7ccc8)">
          <div class="product-img-placeholder">
            <i class="fas fa-couch"></i>
            <span>Sin imagen</span>
          </div>
          <span class="product-status-badge active">● Activo</span>
          <button class="product-fav-btn"><i class="fas fa-heart"></i></button>
          <div class="product-views"><i class="fas fa-eye"></i> 228</div>
        </div>
        <div class="product-body">
          <div class="product-category">Hogar & Muebles</div>
          <div class="product-title">Mesa de madera artesanal roble macizo 6 puestos</div>
          <div class="product-price"><span class="currency">$</span>780.000</div>
          <div class="product-meta">
            <div class="product-meta-item"><i class="fas fa-map-marker-alt"></i> Bogotá, Usaquén</div>
            <div class="product-meta-item"><i class="fas fa-clock"></i> Hace 1 semana</div>
          </div>
        </div>
        <div class="product-footer">
          <button class="prod-btn prod-btn-edit" onclick="showToast('Editando: Mesa artesanal', 'green')"><i class="fas fa-pen"></i> Editar</button>
          <button class="prod-btn prod-btn-delete" onclick="showToast('Producto eliminado', 'red')" title="Eliminar"><i class="fas fa-trash"></i></button>
        </div>
      </div>

      <!-- Card 5 - PAUSED -->
      <div class="product-card" data-status="paused" data-title="Cámara Canon EOS R5" data-price="9500000">
        <div class="product-img-wrap" style="background: linear-gradient(135deg,#f5f5f5,#e0e0e0)">
          <div class="product-img-placeholder">
            <i class="fas fa-camera"></i>
            <span>Sin imagen</span>
          </div>
          <span class="product-status-badge paused">⏸ Pausado</span>
          <button class="product-fav-btn"><i class="fas fa-heart"></i></button>
          <div class="product-views"><i class="fas fa-eye"></i> 94</div>
        </div>
        <div class="product-body">
          <div class="product-category" style="color:#757575">Fotografía</div>
          <div class="product-title">Cámara Canon EOS R5 Full Frame + Lente 24-70mm</div>
          <div class="product-price" style="color:#757575"><span class="currency">$</span>9.500.000</div>
          <div class="product-meta">
            <div class="product-meta-item"><i class="fas fa-map-marker-alt"></i> Barranquilla, Centro</div>
            <div class="product-meta-item"><i class="fas fa-pause-circle" style="color:#757575"></i> Pausado</div>
          </div>
        </div>
        <div class="product-footer">
          <button class="prod-btn prod-btn-edit" onclick="showToast('Reactivando producto...', 'green')" style="background:#e8f5e9;color:var(--green-deep)"><i class="fas fa-play"></i> Reactivar</button>
          <button class="prod-btn prod-btn-delete" onclick="showToast('Producto eliminado', 'red')" title="Eliminar"><i class="fas fa-trash"></i></button>
        </div>
      </div>

      <!-- Card 6 -->
      <div class="product-card" data-status="active" data-title="Laptop MacBook Pro M3" data-price="8900000">
        <div class="product-img-wrap" style="background: linear-gradient(135deg,#e8eaf6,#c5cae9)">
          <div class="product-img-placeholder">
            <i class="fas fa-laptop"></i>
            <span>Sin imagen</span>
          </div>
          <span class="product-status-badge active">● Activo</span>
          <button class="product-fav-btn"><i class="fas fa-heart"></i></button>
          <div class="product-views"><i class="fas fa-eye"></i> 1.2K</div>
        </div>
        <div class="product-body">
          <div class="product-category">Computadores</div>
          <div class="product-title">MacBook Pro M3 14" 16GB RAM 512GB SSD Space Gray</div>
          <div class="product-price"><span class="currency">$</span>8.900.000</div>
          <div class="product-meta">
            <div class="product-meta-item"><i class="fas fa-map-marker-alt"></i> Bogotá, Suba</div>
            <div class="product-meta-item"><i class="fas fa-clock"></i> Hace 3 días</div>
          </div>
        </div>
        <div class="product-footer">
          <button class="prod-btn prod-btn-edit" onclick="showToast('Editando: MacBook Pro M3', 'green')"><i class="fas fa-pen"></i> Editar</button>
          <button class="prod-btn prod-btn-delete" onclick="showToast('Producto eliminado', 'red')" title="Eliminar"><i class="fas fa-trash"></i></button>
        </div>
      </div>

    </div>

    <!-- Empty State (hidden by default) -->
    <div class="empty-state" id="emptyState">
      <div class="empty-illustration">📦</div>
      <div class="empty-title">No tienes productos aún</div>
      <div class="empty-sub">¡Empieza a vender hoy! Publica tu primer producto y llega a miles de compradores locales.</div>
      <button class="publish-btn" style="margin:0 auto" onclick="showToast('Redirigiendo a publicar producto...', 'green')">
        <i class="fas fa-plus"></i> Publicar primer producto
      </button>
    </div>

  </main>
</div>

<!-- Toast notification -->
<div class="toast" id="toast">
  <i class="fas fa-check-circle"></i>
  <span id="toastMsg">Acción realizada</span>
</div>

<script>
  // ── Filter & search
  function filterProducts() {
    const query  = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const cards  = document.querySelectorAll('.product-card');
    let visible  = 0;

    cards.forEach(card => {
      const title   = card.dataset.title.toLowerCase();
      const cStatus = card.dataset.status;
      const matchQ  = title.includes(query);
      const matchS  = status === 'all' || cStatus === status;

      if (matchQ && matchS) {
        card.style.display = '';
        visible++;
      } else {
        card.style.display = 'none';
      }
    });

    document.getElementById('productCount').textContent = visible + ' producto' + (visible !== 1 ? 's' : '');
    document.getElementById('emptyState').classList.toggle('show', visible === 0);
    document.getElementById('productsGrid').style.display = visible === 0 ? 'none' : '';
  }

  // ── Tag filter
  function setTagFilter(status, el) {
    document.querySelectorAll('.tag').forEach(t => {
      t.style.opacity = '.55';
    });
    el.style.opacity = '1';
    document.getElementById('statusFilter').value = status;
    filterProducts();
    // Reset opacity
    setTimeout(() => document.querySelectorAll('.tag').forEach(t => t.style.opacity = ''), 10);
    el.style.fontWeight = '700';
  }

  // ── View toggle
  function setView(type, btn) {
    document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const grid = document.getElementById('productsGrid');
    if (type === 'list') {
      grid.style.gridTemplateColumns = '1fr';
    } else {
      grid.style.gridTemplateColumns = '';
    }
  }

  // ── Toast
  let toastTimer;
  function showToast(msg, type = 'green') {
    const toast = document.getElementById('toast');
    const icon  = toast.querySelector('i');
    document.getElementById('toastMsg').textContent = msg;
    icon.className = type === 'green' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
    toast.className = 'toast show ' + type;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 3000);
  }

  // ── Stat bar animation on load
  window.addEventListener('load', () => {
    document.querySelectorAll('.stat-bar-fill').forEach(bar => {
      const w = bar.style.width;
      bar.style.width = '0';
      setTimeout(() => {
        bar.style.transition = 'width .8s cubic-bezier(.4,0,.2,1)';
        bar.style.width = w;
      }, 300);
    });
  });
</script>
</body>
</html>
<?php
/**
 * Gestión de Administradores — ComercioLocal Admin
 * Estilo consistente con dashboard / gestion_usuarios / gestion_productos.
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

/* API base path (same-origin) */
$API = '../../api/admin/admins.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Administradores — ComercioLocal Admin</title>

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
    .page-head-actions{display:flex;gap:10px;align-items:center;}
    .btn-primary{background:var(--primary);color:var(--white);border:0;padding:12px 20px;border-radius:11px;font-weight:600;font-size:.88rem;display:inline-flex;align-items:center;gap:8px;cursor:pointer;transition:.15s;box-shadow:0 1px 2px rgba(0,0,0,.05);}
    .btn-primary:hover{background:var(--primary-2);transform:translateY(-1px);}
    .btn-ghost{background:var(--white);color:var(--ink2);border:1px solid var(--bord);padding:11px 16px;border-radius:11px;font-weight:600;font-size:.84rem;display:inline-flex;align-items:center;gap:8px;cursor:pointer;transition:.15s;}
    .btn-ghost:hover{border-color:var(--primary);color:var(--primary);}

    /* ══ LAYOUT 2 COL ══ */
    .layout{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:20px;align-items:start;}

    /* ══ CARD ══ */
    .card{background:var(--white);border-radius:16px;box-shadow:var(--shadow);overflow:hidden;}

    /* ══ FILTROS ══ */
    .filters{padding:18px 20px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;border-bottom:1px solid var(--bord-soft);}
    .filters .search{position:relative;flex:1;min-width:240px;}
    .filters .search i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--ink4);}
    .filters .search input{width:100%;padding:10px 12px 10px 36px;border:1px solid var(--bord);border-radius:10px;background:var(--bg);font-size:.88rem;font-family:inherit;outline:none;transition:.15s;}
    .filters .search input:focus{background:var(--white);border-color:var(--primary);box-shadow:0 0 0 3px rgba(11,46,23,.07);}
    .filters select,.filters input[type="date"]{padding:10px 34px 10px 12px;border:1px solid var(--bord);border-radius:10px;background:var(--white) url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%2364748B' viewBox='0 0 16 16'%3e%3cpath d='M3.204 5h9.592L8 10.481 3.204 5zm-.753.659 4.796 5.48a1 1 0 0 0 1.506 0l4.796-5.48c.566-.647.106-1.659-.753-1.659H3.204a1 1 0 0 0-.753 1.659z'/%3e%3c/svg%3e") no-repeat right 12px center;appearance:none;font-size:.84rem;font-weight:500;color:var(--ink2);cursor:pointer;font-family:inherit;min-width:140px;}
    .filters input[type="date"]{padding-right:12px;background-image:none;min-width:150px;}
    .filters select:focus,.filters input[type="date"]:focus{outline:none;border-color:var(--primary);}
    .filters .count{margin-left:auto;font-size:.82rem;color:var(--ink3);}
    .filters .count strong{color:var(--primary);font-weight:700;}

    /* ══ TABLA ══ */
    .table-wrap{position:relative;min-height:280px;overflow-x:auto;}
    .u-table{width:100%;border-collapse:collapse;font-size:.86rem;}
    .u-table thead th{text-align:left;font-weight:600;color:var(--ink3);font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;padding:12px 18px;background:var(--bg);border-bottom:1px solid var(--bord);white-space:nowrap;}
    .u-table thead th.sortable{cursor:pointer;user-select:none;}
    .u-table thead th.sortable:hover{color:var(--primary);}
    .u-table thead th.sortable .sort-ico{font-size:.6rem;margin-left:4px;opacity:.35;transition:.15s;}
    .u-table thead th.sort-asc .sort-ico::before{content:"\25B2";opacity:1;}
    .u-table thead th.sort-desc .sort-ico::before{content:"\25BC";opacity:1;}
    .u-table tbody td{padding:14px 18px;border-bottom:1px solid var(--bord-soft);vertical-align:middle;}
    .u-table tbody tr:hover{background:var(--bg);}
    .u-table tbody tr:last-child td{border-bottom:none;}
    .u-table .num{text-align:right;font-variant-numeric:tabular-nums;}

    /* ══ Avatar + nombre ══ */
    .cell-user{display:flex;align-items:center;gap:10px;min-width:200px;cursor:pointer;}
    .av{width:38px;height:38px;border-radius:50%;background:var(--primary);color:var(--accent);font-weight:700;display:flex;align-items:center;justify-content:center;font-size:.85rem;overflow:hidden;flex-shrink:0;position:relative;}
    .av.super{background:linear-gradient(135deg,#F5A81C,#FFC154);color:var(--primary);}
    .av img{width:100%;height:100%;object-fit:cover;}
    .av .dot{position:absolute;bottom:-1px;right:-1px;width:10px;height:10px;border-radius:50%;border:2px solid var(--white);}
    .av .dot.on{background:#22C55E;}
    .av .dot.off{background:#CBD5E1;}
    .cell-user strong{color:var(--ink);font-weight:600;font-size:.88rem;display:block;line-height:1.25;}
    .cell-user small{color:var(--ink4);font-size:.74rem;word-break:break-all;}
    .cell-user .me-tag{color:var(--primary);font-size:.7rem;font-weight:700;margin-left:4px;}
    .cell-date{color:var(--ink3);font-size:.82rem;white-space:nowrap;}
    .cell-date .d{display:block;color:var(--ink2);font-weight:500;}
    .cell-date .t{display:block;font-size:.7rem;color:var(--ink4);}

    /* ══ Badges ══ */
    .badge{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:999px;font-size:.7rem;font-weight:600;white-space:nowrap;}
    .b-super{background:linear-gradient(135deg,#F5A81C,#FFC154);color:var(--primary);}
    .b-admin{background:var(--green-soft);color:var(--primary);}
    .b-active{background:var(--green-soft);color:var(--primary);}
    .b-inactive{background:#F1F5F9;color:var(--ink3);}
    .b-blocked{background:var(--red-soft);color:var(--red);}

    /* ══ Acciones ══ */
    .actions-cell{display:flex;gap:6px;justify-content:flex-end;}
    .ico-btn{width:32px;height:32px;border-radius:8px;border:1px solid var(--bord);background:var(--white);color:var(--ink2);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:.15s;font-size:.88rem;}
    .ico-btn:hover{border-color:var(--primary);color:var(--primary);}
    .ico-btn.danger{color:var(--red);border-color:#FCA5A5;}
    .ico-btn.danger:hover{background:var(--red-soft);color:var(--red);}
    .ico-btn.ok{color:var(--primary);}
    .ico-btn.ok:hover{background:var(--green-soft);}
    .ico-btn:disabled{opacity:.35;cursor:not-allowed;border-color:var(--bord);}
    .ico-btn:disabled:hover{background:var(--white);color:var(--ink2);}

    /* ══ Paginación ══ */
    .pagination{display:flex;justify-content:space-between;align-items:center;padding:14px 20px;border-top:1px solid var(--bord-soft);flex-wrap:wrap;gap:10px;}
    .page-info{font-size:.82rem;color:var(--ink3);}
    .page-info strong{color:var(--primary);}
    .page-btns{display:flex;gap:4px;}
    .pg-btn{min-width:34px;height:34px;padding:0 10px;border:1px solid var(--bord);background:var(--white);border-radius:8px;font-size:.82rem;font-weight:600;color:var(--ink2);cursor:pointer;transition:.15s;display:inline-flex;align-items:center;justify-content:center;}
    .pg-btn:hover:not(:disabled){border-color:var(--primary);color:var(--primary);}
    .pg-btn.active{background:var(--primary);border-color:var(--primary);color:var(--white);}
    .pg-btn:disabled{opacity:.4;cursor:not-allowed;}
    .per-page{display:flex;align-items:center;gap:8px;font-size:.8rem;color:var(--ink3);}
    .per-page select{padding:6px 28px 6px 10px;border:1px solid var(--bord);border-radius:8px;background:var(--white) url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' fill='%2364748B' viewBox='0 0 16 16'%3e%3cpath d='M3.204 5h9.592L8 10.481 3.204 5z'/%3e%3c/svg%3e") no-repeat right 10px center;appearance:none;font-size:.8rem;font-weight:600;color:var(--ink2);cursor:pointer;font-family:inherit;}

    /* ══ Loading ══ */
    .loading{position:absolute;inset:0;background:rgba(255,255,255,.7);display:none;align-items:center;justify-content:center;z-index:10;}
    .loading.active{display:flex;}
    .spinner{width:36px;height:36px;border:3px solid var(--bord);border-top-color:var(--primary);border-radius:50%;animation:spin .7s linear infinite;}
    @keyframes spin{to{transform:rotate(360deg);}}

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
    .stat-mini--total    .stat-mini__icon{background:var(--green-soft);color:var(--primary);}
    .stat-mini--super    .stat-mini__icon{background:var(--yellow-soft);color:#B45309;}
    .stat-mini--active   .stat-mini__icon{background:var(--blue-soft);color:#1E40AF;}
    .stat-mini--inactive .stat-mini__icon{background:var(--red-soft);color:var(--red);}

    /* Stat destacada */
    .stat-feat{background:var(--primary);color:var(--white);border-radius:14px;padding:20px;position:relative;overflow:hidden;box-shadow:var(--shadow);}
    .stat-feat::after{content:"";position:absolute;top:-30px;right:-30px;width:110px;height:110px;border-radius:50%;background:rgba(245,168,28,.18);}
    .stat-feat__icon{width:38px;height:38px;border-radius:10px;background:rgba(245,168,28,.2);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:1rem;margin-bottom:12px;position:relative;z-index:1;}
    .stat-feat__num{font-family:'Syne',sans-serif;font-size:1.55rem;font-weight:800;color:var(--accent);line-height:1;margin-bottom:4px;position:relative;z-index:1;}
    .stat-feat__lbl{font-size:.78rem;color:rgba(255,255,255,.75);font-weight:500;position:relative;z-index:1;}
    .stat-feat__meta{margin-top:14px;padding-top:12px;border-top:1px solid rgba(255,255,255,.14);font-size:.72rem;color:rgba(255,255,255,.7);display:flex;justify-content:space-between;position:relative;z-index:1;}
    .stat-feat__meta strong{color:var(--white);font-weight:600;}

    /* Tip / info */
    .tip{background:var(--white);border-radius:14px;padding:18px;box-shadow:var(--shadow);display:flex;gap:12px;}
    .tip__ico{width:38px;height:38px;border-radius:10px;background:var(--green-soft);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:1.05rem;flex-shrink:0;}
    .tip h4{font-size:.88rem;font-weight:700;margin-bottom:4px;}
    .tip p{margin:0;font-size:.76rem;color:var(--ink3);line-height:1.5;}

    /* ══ MODAL base ══ */
    .modal-bg{position:fixed;inset:0;background:rgba(11,46,23,.55);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center;z-index:100;padding:16px;}
    .modal-bg.open{display:flex;}
    .modal{background:var(--white);border-radius:18px;width:100%;max-width:520px;box-shadow:var(--shadow-lg);overflow:hidden;animation:modalIn .22s ease-out;max-height:92vh;display:flex;flex-direction:column;}
    @keyframes modalIn{from{opacity:0;transform:translateY(12px) scale(.97);}to{opacity:1;transform:none;}}
    .modal-head{padding:22px 24px 6px;display:flex;gap:14px;align-items:flex-start;}
    .modal-ico{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;}
    .modal-ico.green{background:var(--green-soft);color:var(--primary);}
    .modal-ico.yellow{background:var(--yellow-soft);color:#B45309;}
    .modal-ico.red{background:var(--red-soft);color:var(--red);}
    .modal-ico.blue{background:var(--blue-soft);color:#1E40AF;}
    .modal-head h2{font-size:1.15rem;font-weight:700;margin-bottom:4px;}
    .modal-head p{margin:0;font-size:.85rem;color:var(--ink3);}
    .modal-close{margin-left:auto;background:transparent;border:0;color:var(--ink3);cursor:pointer;padding:6px;border-radius:8px;font-size:1rem;}
    .modal-close:hover{background:var(--bg);color:var(--ink);}
    .modal-body{padding:16px 24px 4px;overflow-y:auto;}
    .modal-actions{padding:18px 24px 22px;display:flex;gap:10px;justify-content:flex-end;border-top:1px solid var(--bord-soft);margin-top:10px;background:var(--white);}
    .btn-secondary{background:var(--bg);color:var(--ink2);border:1px solid var(--bord);padding:10px 16px;border-radius:10px;font-weight:600;font-size:.85rem;cursor:pointer;transition:.15s;display:inline-flex;align-items:center;gap:6px;}
    .btn-secondary:hover{background:var(--bord-soft);}
    .btn-modal-primary{background:var(--primary);color:var(--white);border:0;padding:10px 18px;border-radius:10px;font-weight:600;font-size:.85rem;cursor:pointer;transition:.15s;display:inline-flex;align-items:center;gap:6px;}
    .btn-modal-primary:hover{background:var(--primary-2);}
    .btn-modal-primary:disabled{opacity:.55;cursor:not-allowed;}
    .btn-danger{background:var(--red);color:var(--white);border:0;padding:10px 18px;border-radius:10px;font-weight:600;font-size:.85rem;cursor:pointer;transition:.15s;display:inline-flex;align-items:center;gap:6px;}
    .btn-danger:hover{background:#C62828;}

    /* ══ Form ══ */
    .field{margin-bottom:14px;}
    .field label{display:block;font-size:.78rem;font-weight:600;color:var(--ink2);margin-bottom:6px;}
    .field label .req{color:var(--red);}
    .field input{width:100%;padding:10px 12px;border:1px solid var(--bord);border-radius:10px;font-size:.88rem;font-family:inherit;outline:none;transition:.15s;background:var(--bg);}
    .field input:focus{background:var(--white);border-color:var(--primary);box-shadow:0 0 0 3px rgba(11,46,23,.07);}
    .field input.error{border-color:var(--red);background:#FFF7F7;}
    .field .hint{display:block;margin-top:5px;font-size:.72rem;color:var(--ink4);}
    .field .err{display:none;margin-top:5px;font-size:.74rem;color:var(--red);font-weight:600;}
    .field .err.show{display:block;}

    .role-cards{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:4px;}
    .role-card{border:1.5px solid var(--bord);border-radius:12px;padding:12px;cursor:pointer;transition:.15s;display:flex;gap:10px;align-items:flex-start;background:var(--white);}
    .role-card:hover{border-color:var(--primary);background:var(--bg);}
    .role-card.sel{border-color:var(--primary);background:var(--green-soft);}
    .role-card input{display:none;}
    .role-card .rc-ico{width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;}
    .role-card.admin .rc-ico{background:var(--green-soft);color:var(--primary);}
    .role-card.super .rc-ico{background:linear-gradient(135deg,#F5A81C,#FFC154);color:var(--primary);}
    .role-card strong{display:block;font-size:.86rem;color:var(--ink);line-height:1.2;margin-bottom:2px;}
    .role-card span{font-size:.72rem;color:var(--ink3);line-height:1.3;display:block;}

    /* ══ Detail modal ══ */
    .modal.wide{max-width:580px;}
    .detail-profile{display:flex;flex-direction:column;align-items:center;gap:8px;padding:6px 0 18px;border-bottom:1px solid var(--bord-soft);margin-bottom:16px;}
    .detail-av{width:72px;height:72px;border-radius:50%;background:var(--primary);color:var(--accent);font-weight:800;display:flex;align-items:center;justify-content:center;font-size:1.6rem;font-family:'Syne',sans-serif;}
    .detail-av.super{background:linear-gradient(135deg,#F5A81C,#FFC154);color:var(--primary);}
    .detail-name{font-family:'Syne',sans-serif;font-size:1.15rem;font-weight:700;color:var(--primary);}
    .detail-email{font-size:.82rem;color:var(--ink3);word-break:break-all;text-align:center;}
    .detail-badges{display:flex;gap:6px;flex-wrap:wrap;justify-content:center;margin-top:4px;}
    .detail-section{margin-bottom:14px;}
    .detail-section h4{font-family:'DM Sans',sans-serif;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--ink3);margin-bottom:8px;}
    .detail-row{display:flex;justify-content:space-between;padding:8px 10px;border-radius:8px;font-size:.84rem;gap:12px;}
    .detail-row:nth-child(even){background:var(--bg);}
    .detail-row .lbl{color:var(--ink3);font-weight:500;flex-shrink:0;}
    .detail-row .val{color:var(--ink);font-weight:600;text-align:right;word-break:break-word;}
    .act-item{display:flex;gap:10px;padding:8px 0;border-bottom:1px solid var(--bord-soft);font-size:.82rem;}
    .act-item:last-child{border-bottom:none;}
    .act-dot{width:8px;height:8px;border-radius:50%;background:var(--accent);margin-top:5px;flex-shrink:0;}
    .act-item .act-txt{color:var(--ink);font-weight:500;}
    .act-item .act-time{color:var(--ink4);font-size:.72rem;margin-top:2px;}

    /* ══ Confirm modal ══ */
    .confirm-target{background:var(--bg);border:1px solid var(--bord-soft);border-radius:10px;padding:10px 14px;display:flex;align-items:center;gap:8px;margin-top:10px;font-size:.86rem;font-weight:600;color:var(--ink);}
    .modal-warn{background:var(--red-soft);color:#991B1B;border:1px solid #FCA5A5;border-radius:10px;padding:10px 12px;display:flex;gap:8px;font-size:.78rem;margin-top:10px;}
    .modal-warn i{color:var(--red);flex-shrink:0;margin-top:1px;}

    /* ══ Toast ══ */
    .toast-area{position:fixed;bottom:24px;right:24px;display:flex;flex-direction:column;gap:10px;z-index:200;}
    .toast{background:var(--primary);color:var(--white);padding:13px 18px;border-radius:12px;box-shadow:var(--shadow-lg);font-size:.86rem;font-weight:600;display:flex;align-items:center;gap:10px;min-width:260px;max-width:360px;animation:toastIn .25s;}
    .toast.err{background:var(--red);}
    .toast.warn{background:var(--accent);color:var(--primary);}
    @keyframes toastIn{from{opacity:0;transform:translateX(20px);}to{opacity:1;transform:none;}}
    .toast strong{display:block;font-weight:700;}
    .toast span{display:block;font-weight:500;opacity:.9;font-size:.78rem;}
    .toast button{background:transparent;border:0;color:inherit;opacity:.7;cursor:pointer;margin-left:auto;}

    /* ══ Responsive ══ */
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
      .role-cards{grid-template-columns:1fr;}
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
      <a class="tb-link active" href="./gestion_admins.php"><i class="bi bi-shield-fill-check"></i> Administradores</a>
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
        <h1>Administradores</h1>
        <p>Gestiona cuentas, roles y estados de los administradores de la plataforma.</p>
      </div>
      <div class="page-head-actions">
        <button class="btn-ghost" onclick="loadStats(); loadTable();" title="Actualizar datos">
          <i class="bi bi-arrow-clockwise"></i> Actualizar
        </button>
        <button class="btn-primary" onclick="openCreateModal()">
          <i class="bi bi-plus-lg"></i> Crear administrador
        </button>
      </div>
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
            <select id="fRole">
              <option value="">Rol: Todos</option>
              <option value="admin">Admin</option>
              <option value="super_admin">Super Admin</option>
            </select>
            <select id="fStatus">
              <option value="">Estado: Todos</option>
              <option value="active">Activo</option>
              <option value="inactive">Inactivo</option>
              <option value="blocked">Bloqueado</option>
            </select>
            <input type="date" id="fDateFrom" title="Registrado desde">
            <input type="date" id="fDateTo" title="Registrado hasta">
            <div class="count" id="countInfo">Cargando…</div>
          </div>

          <!-- Tabla -->
          <div class="table-wrap">
            <div class="loading active" id="loading"><div class="spinner"></div></div>
            <table class="u-table" id="adminsTable">
              <thead>
                <tr>
                  <th style="width:50px;">#</th>
                  <th class="sortable" data-col="full_name" onclick="sortBy('full_name')">
                    Administrador <span class="sort-ico"></span>
                  </th>
                  <th class="sortable" data-col="role" onclick="sortBy('role')">
                    Rol <span class="sort-ico"></span>
                  </th>
                  <th class="sortable" data-col="status" onclick="sortBy('status')">
                    Estado <span class="sort-ico"></span>
                  </th>
                  <th class="sortable" data-col="created_at" onclick="sortBy('created_at')">
                    Creado <span class="sort-ico"></span>
                  </th>
                  <th class="sortable" data-col="last_login" onclick="sortBy('last_login')">
                    Último acceso <span class="sort-ico"></span>
                  </th>
                  <th style="text-align:right;">Acciones</th>
                </tr>
              </thead>
              <tbody id="tbody"></tbody>
            </table>
          </div>

          <!-- Paginación -->
          <div class="pagination">
            <div class="per-page">
              <span>Mostrar</span>
              <select id="perPage" onchange="changePerPage(this.value)">
                <option value="10">10</option>
                <option value="20" selected>20</option>
                <option value="50">50</option>
              </select>
              <span>por página</span>
            </div>
            <div class="page-info" id="pageInfo"></div>
            <div class="page-btns" id="pageBtns"></div>
          </div>

        </div>
      </section>

      <!-- ══ SIDEBAR ══ -->
      <aside class="sbar">
        <div class="sbar-title">Estadísticas</div>

        <div class="stat-mini stat-mini--total">
          <div class="stat-mini__icon"><i class="bi bi-shield-fill-check"></i></div>
          <div class="stat-mini__num" id="statTotal">—</div>
          <div class="stat-mini__lbl">Total administradores</div>
        </div>

        <div class="stat-feat">
          <div class="stat-feat__icon"><i class="bi bi-star-fill"></i></div>
          <div class="stat-feat__num" id="statSuper">—</div>
          <div class="stat-feat__lbl">Super Admins</div>
          <div class="stat-feat__meta">
            <span>Admins estándar</span>
            <strong id="statAdmins">—</strong>
          </div>
        </div>

        <div class="stat-mini stat-mini--active">
          <div class="stat-mini__icon"><i class="bi bi-check-circle-fill"></i></div>
          <div class="stat-mini__num" id="statActive">—</div>
          <div class="stat-mini__lbl">Cuentas activas</div>
        </div>

        <div class="stat-mini stat-mini--inactive">
          <div class="stat-mini__icon"><i class="bi bi-dash-circle-fill"></i></div>
          <div class="stat-mini__num" id="statInactive">—</div>
          <div class="stat-mini__lbl">Cuentas inactivas</div>
        </div>

        <div class="tip">
          <div class="tip__ico"><i class="bi bi-shield-lock-fill"></i></div>
          <div>
            <h4>Gestión segura</h4>
            <p>Todas las acciones sobre administradores quedan registradas en el log de actividad.</p>
          </div>
        </div>
      </aside>

    </div>
  </div>

  <!-- ══ MODAL: CREAR / EDITAR ADMIN ══ -->
  <div class="modal-bg" id="mAdmin">
    <div class="modal">
      <div class="modal-head">
        <div class="modal-ico green" id="mAdminIco"><i class="bi bi-person-plus-fill"></i></div>
        <div>
          <h2 id="mAdminTitle">Crear administrador</h2>
          <p id="mAdminSub">Completa los datos del nuevo administrador.</p>
        </div>
        <button class="modal-close" onclick="closeModal('mAdmin')"><i class="bi bi-x-lg"></i></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="fAdminId" value="">

        <div class="field">
          <label for="fName">Nombre completo <span class="req">*</span></label>
          <input type="text" id="fName" placeholder="Ej. María García López" autocomplete="off">
          <span class="err" id="eName"></span>
        </div>

        <div class="field">
          <label for="fEmail">Correo electrónico <span class="req">*</span></label>
          <input type="email" id="fEmail" placeholder="correo@ejemplo.com" autocomplete="off">
          <span class="err" id="eEmail"></span>
        </div>

        <div class="field">
          <label for="fPass">Contraseña <span class="req" id="fPassReq">*</span></label>
          <input type="text" id="fPass" placeholder="Contraseña" autocomplete="new-password">
          <span class="hint" id="fPassHint">Sin límite mínimo de caracteres.</span>
          <span class="err" id="ePass"></span>
        </div>

        <div class="field">
          <label>Rol del administrador <span class="req">*</span></label>
          <div class="role-cards">
            <label class="role-card admin sel" id="rcAdmin" onclick="pickRole('admin')">
              <input type="radio" name="adminRole" value="admin" checked>
              <div class="rc-ico"><i class="bi bi-shield-check"></i></div>
              <div>
                <strong>Admin</strong>
                <span>Acceso al panel con permisos estándar.</span>
              </div>
            </label>
            <label class="role-card super" id="rcSuper" onclick="pickRole('super_admin')">
              <input type="radio" name="adminRole" value="super_admin">
              <div class="rc-ico"><i class="bi bi-star-fill"></i></div>
              <div>
                <strong>Super Admin</strong>
                <span>Control total, incluye gestión de admins.</span>
              </div>
            </label>
          </div>
        </div>
      </div>
      <div class="modal-actions">
        <button class="btn-secondary" onclick="closeModal('mAdmin')">
          <i class="bi bi-x"></i> Cancelar
        </button>
        <button class="btn-modal-primary" id="mAdminSubmit" onclick="submitAdminForm()">
          <i class="bi bi-check-lg"></i> <span id="mAdminBtnLbl">Crear administrador</span>
        </button>
      </div>
    </div>
  </div>

  <!-- ══ MODAL: DETALLE ══ -->
  <div class="modal-bg" id="mDetail">
    <div class="modal wide">
      <div class="modal-head">
        <div class="modal-ico blue"><i class="bi bi-person-badge"></i></div>
        <div>
          <h2>Perfil del administrador</h2>
          <p>Información de la cuenta y actividad reciente.</p>
        </div>
        <button class="modal-close" onclick="closeModal('mDetail')"><i class="bi bi-x-lg"></i></button>
      </div>
      <div class="modal-body" id="detailBody">
        <div style="padding:40px 0;text-align:center;">
          <div class="spinner" style="margin:0 auto;"></div>
          <p style="margin-top:16px;font-size:.85rem;color:var(--ink3);">Cargando perfil…</p>
        </div>
      </div>
      <div class="modal-actions" id="detailFooter"></div>
    </div>
  </div>

  <!-- ══ MODAL: CONFIRMAR CAMBIO DE ESTADO ══ -->
  <div class="modal-bg" id="mConfirm">
    <div class="modal" style="max-width:440px;">
      <div class="modal-head">
        <div class="modal-ico red" id="mConfirmIco"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <div>
          <h2 id="mConfirmTitle">¿Confirmar acción?</h2>
          <p id="mConfirmSub">Esta acción modificará el estado del administrador.</p>
        </div>
        <button class="modal-close" onclick="closeModal('mConfirm')"><i class="bi bi-x-lg"></i></button>
      </div>
      <div class="modal-body">
        <p id="mConfirmMsg" style="margin:0;font-size:.88rem;color:var(--ink2);">¿Estás seguro de continuar?</p>
        <div class="confirm-target" id="mConfirmTarget"></div>
      </div>
      <div class="modal-actions">
        <button class="btn-secondary" onclick="closeModal('mConfirm')">
          <i class="bi bi-x"></i> Cancelar
        </button>
        <button class="btn-modal-primary" id="mConfirmOk" onclick="execConfirmedAction()">
          <i class="bi bi-check-lg"></i> <span id="mConfirmOkLbl">Confirmar</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Toast area -->
  <div class="toast-area" id="toastArea"></div>

  <script>
    function toggleTbMenu(e){e.stopPropagation();e.currentTarget.closest('.tb-user-wrap').classList.toggle('open');}
    document.addEventListener('click',e=>{if(!e.target.closest('.tb-user-wrap'))document.querySelectorAll('.tb-user-wrap.open').forEach(el=>el.classList.remove('open'));});
  </script>

  <!-- ══ SCRIPTS ══ -->
  <script>
    const API            = <?= json_encode($API) ?>;
    const IS_SUPER_ADMIN = <?= json_encode($isSuperAdmin) ?>;
    const SESSION_ID     = <?= (int)($user['id'] ?? 0) ?>;

    let state = {
      page: 1, limit: 20,
      sort: 'created_at', dir: 'DESC',
      search: '', role: '', status: '',
      date_from: '', date_to: '',
    };
    let pendingAction = null;
    let searchTimer   = null;
    let editMode      = false;
    let selectedRole  = 'admin';

    /* ══ BOOT ══ */
    document.addEventListener('DOMContentLoaded', () => {
      loadStats();
      loadTable();

      /* Debounced search */
      document.getElementById('fSearch').addEventListener('input', e => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
          state.search = e.target.value.trim();
          state.page   = 1;
          loadTable();
        }, 380);
      });

      /* Filters */
      ['fRole','fStatus','fDateFrom','fDateTo'].forEach(id => {
        document.getElementById(id).addEventListener('change', applyFilters);
      });

      /* Close modals on backdrop click */
      ['mAdmin','mDetail','mConfirm'].forEach(id => {
        document.getElementById(id).addEventListener('click', e => {
          if (e.target.id === id) closeModal(id);
        });
      });

      /* ESC key closes all modals */
      document.addEventListener('keydown', e => {
        if (e.key === 'Escape') ['mAdmin','mDetail','mConfirm'].forEach(closeModal);
      });
    });

    /* ══ LOAD STATS ══ */
    async function loadStats() {
      try {
        const res  = await fetch(`${API}?action=stats`);
        const json = await res.json();
        if (!json.ok) return;
        const d = json.data;
        setText('statTotal',    fmt(d.total));
        setText('statSuper',    fmt(d.super_admins));
        setText('statAdmins',   fmt(d.admins));
        setText('statActive',   fmt(d.active));
        setText('statInactive', fmt(d.inactive));
      } catch(e) { console.warn('Stats error', e); }
    }

    /* ══ LOAD TABLE ══ */
    async function loadTable() {
      showLoading(true);
      const params = new URLSearchParams({
        action:'list', page:state.page, limit:state.limit,
        sort:state.sort, dir:state.dir,
        search:state.search, role:state.role, status:state.status,
        date_from:state.date_from, date_to:state.date_to,
      });

      try {
        const res  = await fetch(`${API}?${params}`);
        const json = await res.json();
        if (!json.ok) { showLoading(false); toast('err','Error al cargar', json.error); return; }
        renderTable(json.data);
        renderPagination(json.pagination);
        updateSortHeaders();
        const t = json.pagination.total;
        document.getElementById('countInfo').innerHTML =
          `<strong>${fmt(t)}</strong> resultado${t !== 1 ? 's' : ''}`;
      } catch(e) {
        toast('err','Error de red','No se pudo conectar con el servidor');
      }
      showLoading(false);
    }

    /* ══ RENDER TABLE ══ */
    function renderTable(rows) {
      const tbody = document.getElementById('tbody');
      if (!rows || rows.length === 0) {
        tbody.innerHTML = `
          <tr><td colspan="7">
            <div class="empty">
              <i class="bi bi-shield-fill-exclamation"></i>
              <strong>No se encontraron administradores</strong>
              <div style="font-size:.82rem;margin-top:4px;">
                ${state.search || state.role || state.status
                  ? 'No hay resultados con los filtros actuales.'
                  : 'Aún no hay administradores registrados.'}
              </div>
              <button class="btn-primary" style="margin-top:14px;" onclick="openCreateModal()">
                <i class="bi bi-plus-lg"></i> Crear administrador
              </button>
            </div>
          </td></tr>`;
        return;
      }

      tbody.innerHTML = rows.map(a => {
        const isMe    = (a.id === SESSION_ID);
        const isSuper = (a.role === 'super_admin');
        const avCls   = isSuper ? 'av super' : 'av';
        const dotCls  = a.status === 'active' ? 'on' : 'off';

        const avatar = a.profile_photo
          ? `<div class="${avCls}"><img src="${esc(a.profile_photo)}" alt="">
               <span class="dot ${dotCls}"></span></div>`
          : `<div class="${avCls}">${esc(a.initials)}<span class="dot ${dotCls}"></span></div>`;

        const roleBadge = isSuper
          ? `<span class="badge b-super"><i class="bi bi-star-fill"></i> Super Admin</span>`
          : `<span class="badge b-admin"><i class="bi bi-shield-check"></i> Admin</span>`;

        const statusBadge = {
          active:   `<span class="badge b-active"><i class="bi bi-check-circle-fill"></i> Activo</span>`,
          inactive: `<span class="badge b-inactive"><i class="bi bi-dash-circle-fill"></i> Inactivo</span>`,
          blocked:  `<span class="badge b-blocked"><i class="bi bi-slash-circle-fill"></i> Bloqueado</span>`,
        }[a.status] || `<span class="badge b-inactive">${esc(a.status)}</span>`;

        const canEdit   = IS_SUPER_ADMIN || !isSuper;
        const canToggle = (IS_SUPER_ADMIN || !isSuper) && !isMe;
        const canDelete = (IS_SUPER_ADMIN || !isSuper) && !isMe;
        const toggleBtn = a.status === 'active'
          ? `<button class="ico-btn danger" title="Desactivar" ${canToggle?'':'disabled'}
               onclick="confirmDeactivate(${a.id}, '${escAttr(a.full_name)}')">
               <i class="bi bi-dash-circle"></i></button>`
          : `<button class="ico-btn ok" title="Activar" ${canToggle?'':'disabled'}
               onclick="confirmActivate(${a.id}, '${escAttr(a.full_name)}')">
               <i class="bi bi-check-circle"></i></button>`;
        const deleteBtn = `<button class="ico-btn danger" title="Eliminar" ${canDelete?'':'disabled'}
               onclick="confirmDelete(${a.id}, '${escAttr(a.full_name)}')">
               <i class="bi bi-trash3"></i></button>`;

        return `
          <tr>
            <td style="color:var(--ink4);font-weight:600;font-size:.78rem;">#${a.id}</td>
            <td>
              <div class="cell-user" onclick="openDetail(${a.id})">
                ${avatar}
                <div>
                  <strong>${esc(a.full_name)}${isMe?'<span class="me-tag">(tú)</span>':''}</strong>
                  <small>${esc(a.email)}</small>
                </div>
              </div>
            </td>
            <td>${roleBadge}</td>
            <td>${statusBadge}</td>
            <td>${fmtDateCell(a.created_at)}</td>
            <td>${a.last_login ? fmtDateCell(a.last_login) : '<span class="cell-date" style="color:var(--ink4);font-style:italic;">Nunca</span>'}</td>
            <td>
              <div class="actions-cell">
                <button class="ico-btn" title="Ver detalles" onclick="openDetail(${a.id})">
                  <i class="bi bi-eye"></i>
                </button>
                <button class="ico-btn" title="Editar" ${canEdit?'':'disabled'} onclick="openEditModal(${a.id})">
                  <i class="bi bi-pencil"></i>
                </button>
                ${toggleBtn}
                ${deleteBtn}
              </div>
            </td>
          </tr>`;
      }).join('');
    }

    /* ══ PAGINACIÓN ══ */
    function renderPagination(pg) {
      const { page, limit, total, total_pages } = pg;
      const from = total === 0 ? 0 : (page - 1) * limit + 1;
      const to   = Math.min(page * limit, total);

      document.getElementById('pageInfo').innerHTML =
        total > 0 ? `Mostrando <strong>${from}–${to}</strong> de <strong>${fmt(total)}</strong>` : '';

      const btns = document.getElementById('pageBtns');
      if (total_pages <= 1) { btns.innerHTML = ''; return; }

      let html = `<button class="pg-btn" onclick="goPage(${page-1})" ${page<=1?'disabled':''}><i class="bi bi-chevron-left"></i></button>`;
      const range = pageRange(page, total_pages);
      let prev = null;
      for (const p of range) {
        if (prev !== null && p - prev > 1) html += `<button class="pg-btn" disabled>…</button>`;
        html += `<button class="pg-btn ${p===page?'active':''}" onclick="goPage(${p})">${p}</button>`;
        prev = p;
      }
      html += `<button class="pg-btn" onclick="goPage(${page+1})" ${page>=total_pages?'disabled':''}><i class="bi bi-chevron-right"></i></button>`;
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

    /* ══ SORT & FILTERS ══ */
    function sortBy(col) {
      if (state.sort === col) state.dir = state.dir === 'ASC' ? 'DESC' : 'ASC';
      else { state.sort = col; state.dir = 'DESC'; }
      state.page = 1;
      loadTable();
    }
    function updateSortHeaders() {
      document.querySelectorAll('.u-table th.sortable').forEach(th => {
        th.classList.remove('sort-asc','sort-desc');
        if (th.dataset.col === state.sort) th.classList.add(state.dir === 'ASC' ? 'sort-asc' : 'sort-desc');
      });
    }
    function applyFilters() {
      state.role      = document.getElementById('fRole').value;
      state.status    = document.getElementById('fStatus').value;
      state.date_from = document.getElementById('fDateFrom').value;
      state.date_to   = document.getElementById('fDateTo').value;
      state.page      = 1;
      loadTable();
    }

    /* ══ CREAR / EDITAR MODAL ══ */
    function openCreateModal() {
      editMode = false;
      document.getElementById('fAdminId').value = '';
      document.getElementById('fName').value    = '';
      document.getElementById('fEmail').value   = '';
      document.getElementById('fPass').value    = '';
      document.getElementById('fPassReq').textContent  = '*';
      document.getElementById('fPassHint').textContent = 'Sin límite mínimo de caracteres.';

      /* Solo el Super Admin puede asignar rol Super Admin */
      const rcSuper = document.getElementById('rcSuper');
      if (rcSuper) rcSuper.style.display = IS_SUPER_ADMIN ? '' : 'none';
      pickRole('admin');

      document.getElementById('mAdminIco').className    = 'modal-ico green';
      document.getElementById('mAdminIco').innerHTML    = '<i class="bi bi-person-plus-fill"></i>';
      document.getElementById('mAdminTitle').textContent = 'Crear administrador';
      document.getElementById('mAdminSub').textContent   = 'Completa los datos del nuevo administrador.';
      document.getElementById('mAdminBtnLbl').textContent = 'Crear administrador';

      clearFormErrors();
      openModal('mAdmin');
      setTimeout(() => document.getElementById('fName').focus(), 100);
    }

    async function openEditModal(id) {
      editMode = true;
      openModal('mAdmin');
      document.getElementById('mAdminTitle').textContent  = 'Cargando…';
      document.getElementById('mAdminSub').textContent    = '';
      clearFormErrors();

      try {
        const res  = await fetch(`${API}?action=detail&id=${id}`);
        const json = await res.json();
        if (!json.ok) { closeModal('mAdmin'); toast('err','Error', json.error); return; }
        const a = json.data;

        if (!IS_SUPER_ADMIN && a.role === 'super_admin') {
          closeModal('mAdmin');
          toast('warn','Sin permisos','Solo un Super Admin puede editar a otro Super Admin.');
          return;
        }

        document.getElementById('fAdminId').value = a.id;
        document.getElementById('fName').value    = a.full_name;
        document.getElementById('fEmail').value   = a.email;
        document.getElementById('fPass').value    = '';
        document.getElementById('fPassReq').textContent  = '';
        document.getElementById('fPassHint').textContent = 'Deja en blanco para no cambiarla.';

        const rcSuper = document.getElementById('rcSuper');
        if (rcSuper) rcSuper.style.display = IS_SUPER_ADMIN ? '' : 'none';
        pickRole(a.role);

        document.getElementById('mAdminIco').className    = 'modal-ico yellow';
        document.getElementById('mAdminIco').innerHTML    = '<i class="bi bi-pencil-square"></i>';
        document.getElementById('mAdminTitle').textContent = 'Editar administrador';
        document.getElementById('mAdminSub').textContent   = `Modificando la cuenta de ${a.full_name}.`;
        document.getElementById('mAdminBtnLbl').textContent = 'Guardar cambios';
      } catch(e) {
        closeModal('mAdmin');
        toast('err','Error de red','No se pudo cargar el administrador.');
      }
    }

    function pickRole(role) {
      selectedRole = role;
      document.querySelectorAll('.role-card').forEach(c => c.classList.remove('sel'));
      const el = role === 'super_admin' ? document.getElementById('rcSuper') : document.getElementById('rcAdmin');
      if (el) { el.classList.add('sel'); el.querySelector('input').checked = true; }
    }

    async function submitAdminForm() {
      const id    = document.getElementById('fAdminId').value;
      const name  = document.getElementById('fName').value.trim();
      const email = document.getElementById('fEmail').value.trim();
      const pass  = document.getElementById('fPass').value;
      const role  = selectedRole;
      const isEdit = editMode && id !== '';

      clearFormErrors();
      let valid = true;
      if (name.length < 2)                                 { showFieldError('eName','fName','Mínimo 2 caracteres'); valid = false; }
      if (!isValidEmail(email))                            { showFieldError('eEmail','fEmail','Correo electrónico inválido'); valid = false; }
      if (!isEdit && pass.length === 0)                    { showFieldError('ePass','fPass','La contraseña es obligatoria'); valid = false; }
      if (!valid) return;

      const btn = document.getElementById('mAdminSubmit');
      btn.disabled = true;
      btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Procesando…';

      const action = isEdit ? 'update' : 'create';
      const payload = { action, name, email, role };
      if (pass)   payload.password = pass;
      if (isEdit) payload.id = parseInt(id);

      try {
        const res  = await fetch(API, {
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify(payload),
        });
        const json = await res.json();
        if (json.ok) {
          toast('ok', isEdit ? 'Administrador actualizado' : 'Administrador creado', json.message || '');
          closeModal('mAdmin');
          loadStats(); loadTable();
        } else {
          toast('err','No se pudo guardar', json.error);
        }
      } catch(e) {
        toast('err','Error de red','No se pudo conectar con el servidor.');
      }

      btn.disabled = false;
      btn.innerHTML = `<i class="bi bi-check-lg"></i> <span id="mAdminBtnLbl">${isEdit ? 'Guardar cambios' : 'Crear administrador'}</span>`;
    }

    /* ══ DETAIL MODAL ══ */
    async function openDetail(id) {
      openModal('mDetail');
      document.getElementById('detailBody').innerHTML = `
        <div style="padding:40px 0;text-align:center;">
          <div class="spinner" style="margin:0 auto;"></div>
          <p style="margin-top:16px;font-size:.85rem;color:var(--ink3);">Cargando perfil…</p>
        </div>`;
      document.getElementById('detailFooter').innerHTML = '';

      try {
        const res  = await fetch(`${API}?action=detail&id=${id}`);
        const json = await res.json();
        if (!json.ok) {
          document.getElementById('detailBody').innerHTML = `<p style="color:var(--red);padding:20px;">${esc(json.error)}</p>`;
          return;
        }
        renderDetail(json.data);
      } catch(e) {
        document.getElementById('detailBody').innerHTML = `<p style="color:var(--red);padding:20px;">Error al cargar el perfil.</p>`;
      }
    }

    function renderDetail(a) {
      const isSuper = a.role === 'super_admin';
      const isMe    = a.id === SESSION_ID;
      const avCls   = isSuper ? 'detail-av super' : 'detail-av';

      const roleBadge = isSuper
        ? `<span class="badge b-super"><i class="bi bi-star-fill"></i> Super Admin</span>`
        : `<span class="badge b-admin"><i class="bi bi-shield-check"></i> Admin</span>`;
      const statusBadge = {
        active:   `<span class="badge b-active"><i class="bi bi-check-circle-fill"></i> Activo</span>`,
        inactive: `<span class="badge b-inactive"><i class="bi bi-dash-circle-fill"></i> Inactivo</span>`,
        blocked:  `<span class="badge b-blocked"><i class="bi bi-slash-circle-fill"></i> Bloqueado</span>`,
      }[a.status] || '';

      const profile = `
        <div class="detail-profile">
          <div class="${avCls}">${esc(a.initials)}</div>
          <div class="detail-name">${esc(a.full_name)}${isMe?' <span style="color:var(--primary);font-size:.82rem;">(tú)</span>':''}</div>
          <div class="detail-email">${esc(a.email)}</div>
          <div class="detail-badges">${roleBadge}${statusBadge}</div>
        </div>`;

      const info = `
        <div class="detail-section">
          <h4>Información de la cuenta</h4>
          <div class="detail-row"><span class="lbl">ID</span><span class="val">#${a.id}</span></div>
          <div class="detail-row"><span class="lbl">Nombre</span><span class="val">${esc(a.full_name)}</span></div>
          <div class="detail-row"><span class="lbl">Correo</span><span class="val">${esc(a.email)}</span></div>
          <div class="detail-row"><span class="lbl">Rol</span><span class="val">${isSuper ? 'Super Admin' : 'Admin'}</span></div>
          <div class="detail-row"><span class="lbl">Estado</span><span class="val">${a.status === 'active' ? 'Activo' : a.status === 'inactive' ? 'Inactivo' : 'Bloqueado'}</span></div>
          ${a.phone ? `<div class="detail-row"><span class="lbl">Teléfono</span><span class="val">${esc(a.phone)}</span></div>` : ''}
          ${a.city  ? `<div class="detail-row"><span class="lbl">Ciudad</span><span class="val">${esc(a.city)}</span></div>` : ''}
        </div>`;

      const dates = `
        <div class="detail-section">
          <h4>Actividad</h4>
          <div class="detail-row"><span class="lbl">Cuenta creada</span><span class="val">${fmtFull(a.created_at)}</span></div>
          <div class="detail-row"><span class="lbl">Último acceso</span><span class="val">${a.last_login ? fmtFull(a.last_login) : '<em style="color:var(--ink4);font-weight:400;">Nunca</em>'}</span></div>
          ${a.updated_at ? `<div class="detail-row"><span class="lbl">Actualizado</span><span class="val">${fmtFull(a.updated_at)}</span></div>` : ''}
        </div>`;

      let actSection = '';
      if (a.activity && a.activity.length > 0) {
        actSection = `
          <div class="detail-section">
            <h4>Actividad reciente</h4>
            ${a.activity.map(x => `
              <div class="act-item">
                <div class="act-dot"></div>
                <div>
                  <div class="act-txt">${esc(x.action)}</div>
                  <div class="act-time">${fmtFull(x.created_at)}</div>
                </div>
              </div>`).join('')}
          </div>`;
      }

      document.getElementById('detailBody').innerHTML = profile + info + dates + actSection;

      /* Footer actions */
      const canEdit   = IS_SUPER_ADMIN || !isSuper;
      const canToggle = (IS_SUPER_ADMIN || !isSuper) && !isMe;
      const canDelete = (IS_SUPER_ADMIN || !isSuper) && !isMe;
      const toggleBtn = a.status === 'active'
        ? `<button class="btn-secondary" ${canToggle?'':'disabled'} onclick="confirmDeactivate(${a.id}, '${escAttr(a.full_name)}'); closeModal('mDetail');">
             <i class="bi bi-dash-circle"></i> Desactivar</button>`
        : `<button class="btn-modal-primary" ${canToggle?'':'disabled'} onclick="confirmActivate(${a.id}, '${escAttr(a.full_name)}'); closeModal('mDetail');">
             <i class="bi bi-check-circle"></i> Activar</button>`;

      document.getElementById('detailFooter').innerHTML = `
        <button class="btn-secondary" onclick="closeModal('mDetail')">
          <i class="bi bi-x"></i> Cerrar
        </button>
        <button class="btn-secondary" ${canEdit?'':'disabled'} onclick="closeModal('mDetail'); openEditModal(${a.id});">
          <i class="bi bi-pencil"></i> Editar
        </button>
        ${toggleBtn}
        <button class="btn-danger" ${canDelete?'':'disabled'} onclick="confirmDelete(${a.id}, '${escAttr(a.full_name)}'); closeModal('mDetail');">
          <i class="bi bi-trash3"></i> Eliminar
        </button>`;
    }

    /* ══ CONFIRMAR ESTADO ══ */
    function confirmActivate(id, name) {
      pendingAction = { action:'activate', id, name };
      document.getElementById('mConfirmIco').className   = 'modal-ico green';
      document.getElementById('mConfirmIco').innerHTML   = '<i class="bi bi-check-circle-fill"></i>';
      document.getElementById('mConfirmTitle').textContent = 'Activar administrador';
      document.getElementById('mConfirmSub').textContent   = 'Esta acción reactivará el acceso al panel.';
      document.getElementById('mConfirmMsg').textContent   = '¿Confirmas que deseas activar la cuenta de:';
      document.getElementById('mConfirmTarget').innerHTML  = `<i class="bi bi-person-fill" style="color:var(--primary);"></i> ${esc(name)}`;
      document.getElementById('mConfirmOk').className      = 'btn-modal-primary';
      document.getElementById('mConfirmOkLbl').textContent = 'Activar';
      openModal('mConfirm');
    }
    function confirmDeactivate(id, name) {
      pendingAction = { action:'deactivate', id, name };
      document.getElementById('mConfirmIco').className   = 'modal-ico red';
      document.getElementById('mConfirmIco').innerHTML   = '<i class="bi bi-dash-circle-fill"></i>';
      document.getElementById('mConfirmTitle').textContent = 'Desactivar administrador';
      document.getElementById('mConfirmSub').textContent   = 'El administrador perderá acceso al panel.';
      document.getElementById('mConfirmMsg').textContent   = '¿Confirmas que deseas desactivar la cuenta de:';
      document.getElementById('mConfirmTarget').innerHTML  = `<i class="bi bi-person-fill" style="color:var(--red);"></i> ${esc(name)}`;
      document.getElementById('mConfirmOk').className      = 'btn-danger';
      document.getElementById('mConfirmOkLbl').textContent = 'Desactivar';
      openModal('mConfirm');
    }
    function confirmDelete(id, name) {
      pendingAction = { action:'delete', id, name };
      document.getElementById('mConfirmIco').className   = 'modal-ico red';
      document.getElementById('mConfirmIco').innerHTML   = '<i class="bi bi-trash3-fill"></i>';
      document.getElementById('mConfirmTitle').textContent = 'Eliminar administrador';
      document.getElementById('mConfirmSub').textContent   = 'Esta acción es irreversible: el administrador será removido del panel.';
      document.getElementById('mConfirmMsg').textContent   = '¿Confirmas que deseas eliminar la cuenta de:';
      document.getElementById('mConfirmTarget').innerHTML  = `<i class="bi bi-person-fill-x" style="color:var(--red);"></i> ${esc(name)}`;
      document.getElementById('mConfirmOk').className      = 'btn-danger';
      document.getElementById('mConfirmOkLbl').textContent = 'Eliminar';
      openModal('mConfirm');
    }
    async function execConfirmedAction() {
      if (!pendingAction) return;
      const { action, id } = pendingAction;
      const btn = document.getElementById('mConfirmOk');
      btn.disabled = true;
      try {
        const res  = await fetch(API, {
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify({ action, id }),
        });
        const json = await res.json();
        if (json.ok) {
          const titles = { activate:'Administrador activado', deactivate:'Administrador desactivado', delete:'Administrador eliminado' };
          toast('ok', titles[action] || 'Listo', json.message || '');
          closeModal('mConfirm');
          loadStats(); loadTable();
        } else {
          toast('err','Error', json.error);
        }
      } catch(e) {
        toast('err','Error de red','No se pudo conectar con el servidor.');
      }
      btn.disabled = false;
      pendingAction = null;
    }

    /* ══ MODAL HELPERS ══ */
    function openModal(id)  { document.getElementById(id).classList.add('open'); }
    function closeModal(id) { document.getElementById(id).classList.remove('open'); }

    /* ══ TOAST ══ */
    function toast(type, title, msg) {
      const cls = type === 'err' ? 'err' : (type === 'warn' ? 'warn' : '');
      const ico = type === 'err' ? 'bi-x-circle-fill' : (type === 'warn' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill');
      const el  = document.createElement('div');
      el.className = `toast ${cls}`;
      el.innerHTML = `<i class="bi ${ico}"></i><div><strong>${esc(title)}</strong>${msg?`<span>${esc(msg)}</span>`:''}</div>
        <button onclick="this.parentElement.remove()"><i class="bi bi-x-lg"></i></button>`;
      document.getElementById('toastArea').appendChild(el);
      setTimeout(() => el.remove(), 4500);
    }

    /* ══ HELPERS ══ */
    function showLoading(on) { document.getElementById('loading').classList.toggle('active', on); }
    function setText(id, v)  { const el = document.getElementById(id); if (el) el.textContent = v; }
    function fmt(n)          { return n == null ? '—' : Number(n).toLocaleString('es-CO'); }
    function fmtDateCell(dt) {
      if (!dt) return '<span class="cell-date">—</span>';
      const d = new Date(dt);
      if (isNaN(d)) return '<span class="cell-date">—</span>';
      const date = d.toLocaleDateString('es-CO', { day:'2-digit', month:'short', year:'numeric' });
      const time = d.toLocaleTimeString('es-CO', { hour:'2-digit', minute:'2-digit' });
      return `<div class="cell-date"><span class="d">${date}</span><span class="t">${time}</span></div>`;
    }
    function fmtFull(dt) {
      if (!dt) return '—';
      const d = new Date(dt);
      if (isNaN(d)) return '—';
      return d.toLocaleString('es-CO', { day:'2-digit', month:'long', year:'numeric', hour:'2-digit', minute:'2-digit' });
    }
    function esc(s) {
      if (s == null) return '';
      return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }
    function escAttr(s) {
      if (s == null) return '';
      return String(s).replace(/'/g,"\\'").replace(/"/g,'&quot;');
    }
    function isValidEmail(e) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e); }

    function showFieldError(errId, inputId, msg) {
      const err = document.getElementById(errId);
      const inp = document.getElementById(inputId);
      if (err) { err.textContent = msg; err.classList.add('show'); }
      if (inp) inp.classList.add('error');
    }
    function clearFormErrors() {
      ['eName','eEmail','ePass'].forEach(id => {
        const el = document.getElementById(id);
        if (el) { el.textContent = ''; el.classList.remove('show'); }
      });
      ['fName','fEmail','fPass'].forEach(id => {
        document.getElementById(id)?.classList.remove('error');
      });
    }
  </script>
</body>
</html>

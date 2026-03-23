<?php
require_once("../config/conexion.php");
session_start();

/* ── LOGIC PRESERVED ── */
if (!isset($_SESSION['user'])) {
    header("Location: ./auth/login.php");
    exit();
}

// Procesar guardado del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    $user_id = $_SESSION['user']['id'];

    // Verificar que el producto pertenece al usuario logueado
    $verify_sql = "SELECT user_id FROM products WHERE id = $product_id";
    $verify_result = mysqli_query($conn, $verify_sql);
    $verify_row = mysqli_fetch_assoc($verify_result);

    if (!$verify_row || $verify_row['user_id'] != $user_id) {
        die("No tienes permiso para editar este producto");
    }

    // Recolectar datos del formulario
    $nombre = htmlspecialchars($_POST['nombre'] ?? '');
    $descripcion = htmlspecialchars($_POST['descripcion'] ?? '');
    $precio = floatval($_POST['precio'] ?? 0);
    $condicion = htmlspecialchars($_POST['condicion'] ?? 'usado');
    $categoria = intval($_POST['categoria'] ?? 0);
    $latitude = htmlspecialchars($_POST['latitude'] ?? '');
    $longitude = htmlspecialchars($_POST['longitude'] ?? '');

    // Actualizar producto
    $update_sql = "UPDATE products SET title='$nombre', description='$descripcion', price=$precio, condition_type='$condicion', category_id=$categoria, latitude='$latitude', longitude='$longitude' WHERE id=$product_id";

    if (mysqli_query($conn, $update_sql)) {
        // Redirigir al dashboard
        header("Location: ./dashboard.php");
        exit();
    } else {
        die("Error al actualizar: " . mysqli_error($conn));
    }
}

// GET: Mostrar formulario
if (!isset($_GET['id'])) {
    echo "Producto no encontrado";
    exit();
}

$id = $_GET['id'];

$sql     = "SELECT * FROM products WHERE id = '$id'";
$result  = mysqli_query($conn, $sql);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    echo "Producto no existe";
    exit();
}

/* Fetch existing images */
$img_sql    = "SELECT * FROM product_images WHERE product_id = '$id' ORDER BY id ASC";
$img_result = mysqli_query($conn, $img_sql);
$existingImages = [];
while ($img = mysqli_fetch_assoc($img_result)) $existingImages[] = $img;

/* Categories */
$cat_sql    = "SELECT * FROM categories ORDER BY name ASC";
$cat_result = mysqli_query($conn, $cat_sql);

/* Session user */
$isLoggedIn  = isset($_SESSION['user']);
$user        = $isLoggedIn ? $_SESSION['user'] : null;
$userInitial = $isLoggedIn ? strtoupper(mb_substr($user['full_name'], 0, 1)) : '';
$userName    = $isLoggedIn ? explode(' ', $user['full_name'])[0] : '';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar producto – ComercioLocal</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
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

            --r-danger: #dc2626;
            --bg-danger: #fef2f2;
            --border-danger: #fecaca;

            --ink: #0d1f13;
            --ink2: #2d4035;
            --ink3: #5a7065;
            --bg: #f0f7f2;
            --card: #ffffff;
            --border: #d4e8da;
            --r: 16px;
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
            display: flex;
            flex-direction: column;
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

        /* ══ TOPBAR ══ */
        .topbar {
            height: 66px;
            background: var(--g900);
            border-bottom: 3px solid var(--y400);
            display: flex;
            align-items: center;
            padding: 0 24px;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 200;
            box-shadow: 0 4px 24px rgba(0, 0, 0, .3);
            flex-shrink: 0;
        }

        .tb-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.25rem;
            color: #fff;
            white-space: nowrap;
            flex-shrink: 0;
            text-decoration: none;
        }

        .tb-logo-box {
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

        .tb-logo em {
            color: var(--y400);
            font-style: normal;
        }

        .tb-search {
            flex: 1;
            max-width: 380px;
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, .1);
            border: 1.5px solid rgba(255, 255, 255, .15);
            border-radius: 50px;
            overflow: hidden;
        }

        .tb-search input {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            color: #fff;
            font-family: 'DM Sans', sans-serif;
            font-size: .87rem;
            padding: 9px 16px;
        }

        .tb-search input::placeholder {
            color: rgba(255, 255, 255, .4);
        }

        .tb-search button {
            background: none;
            border: none;
            cursor: pointer;
            color: rgba(255, 255, 255, .5);
            padding: 9px 14px;
            font-size: .95rem;
            transition: color .2s;
        }

        .tb-search button:hover {
            color: var(--y300);
        }

        .tb-spacer {
            flex: 1;
        }

        .tb-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .tb-icon-btn {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: rgba(255, 255, 255, .08);
            border: 1.5px solid rgba(255, 255, 255, .12);
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, .7);
            font-size: 1.05rem;
            cursor: pointer;
            transition: all .2s;
            position: relative;
        }

        .tb-icon-btn:hover {
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
            animation: pulse-notif 2s infinite;
        }

        @keyframes pulse-notif {

            0%,
            100% {
                transform: scale(1);
                opacity: 1;
            }

            50% {
                transform: scale(1.3);
                opacity: .7;
            }
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

        .uc-greeting {
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

        /* ══ PAGE SHELL ══ */
        .page-shell {
            display: flex;
            flex: 1;
            min-height: 0;
        }

        /* ══ SIDEBAR ══ */
        .sidebar {
            width: 228px;
            flex-shrink: 0;
            background: var(--g900);
            display: flex;
            flex-direction: column;
            padding: 24px 0 40px;
            position: sticky;
            top: 66px;
            height: calc(100vh - 66px);
            overflow-y: auto;
        }

        .sb-section-label {
            font-size: .67rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: rgba(255, 255, 255, .28);
            padding: 16px 20px 8px;
        }

        .sb-link {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 10px 20px;
            color: rgba(255, 255, 255, .55);
            font-size: .86rem;
            border-left: 3px solid transparent;
            transition: all .2s;
            cursor: pointer;
            text-decoration: none;
            position: relative;
        }

        .sb-link i {
            font-size: .98rem;
            width: 18px;
            text-align: center;
        }

        .sb-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, .05);
        }

        .sb-link.active {
            color: var(--y300);
            background: rgba(245, 168, 28, .08);
            border-left-color: var(--y400);
        }

        .sb-badge {
            margin-left: auto;
            background: var(--g500);
            color: #fff;
            font-size: .65rem;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 20px;
        }

        .sb-badge.yellow {
            background: var(--y400);
            color: var(--g900);
        }

        .sb-divider {
            height: 1px;
            background: rgba(255, 255, 255, .07);
            margin: 10px 16px;
        }

        .sb-promo {
            margin: 18px 14px 0;
            background: linear-gradient(135deg, var(--g700), var(--g800));
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: 14px;
            padding: 16px;
        }

        .sb-promo-icon {
            font-size: 1.5rem;
            margin-bottom: 7px;
        }

        .sb-promo p {
            font-size: .76rem;
            color: rgba(255, 255, 255, .55);
            line-height: 1.5;
            margin-bottom: 12px;
        }

        .sb-promo a {
            display: block;
            text-align: center;
            background: var(--y400);
            color: var(--g900);
            padding: 7px;
            border-radius: 8px;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: .76rem;
            transition: background .2s;
        }

        .sb-promo a:hover {
            background: var(--y300);
        }

        /* ══ MAIN ══ */
        .main-content {
            flex: 1;
            padding: 28px 32px 80px;
            overflow-y: auto;
            min-width: 0;
        }

        /* Page header */
        .pg-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 28px;
            flex-wrap: wrap;
            gap: 14px;
        }

        .pg-breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: .75rem;
            color: var(--ink3);
            margin-bottom: 6px;
        }

        .pg-breadcrumb a {
            color: var(--g500);
        }

        .pg-breadcrumb a:hover {
            text-decoration: underline;
        }

        .pg-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--ink);
            line-height: 1.2;
        }

        .pg-title span {
            color: var(--g500);
        }

        .pg-subtitle {
            font-size: .87rem;
            color: var(--ink3);
            margin-top: 4px;
        }

        .pg-id-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--g100);
            color: var(--g700);
            font-size: .73rem;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
            margin-top: 6px;
        }

        /* Action header row */
        .action-header {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-save {
            display: flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--g500), var(--g400));
            border: none;
            color: #fff;
            border-radius: 12px;
            padding: 12px 22px;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: .92rem;
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(37, 136, 63, .35);
            transition: all .25s;
        }

        .btn-save:hover {
            background: linear-gradient(135deg, var(--g600), var(--g500));
            box-shadow: 0 6px 22px rgba(37, 136, 63, .45);
            transform: translateY(-2px);
        }

        .btn-cancel {
            display: flex;
            align-items: center;
            gap: 7px;
            background: var(--card);
            border: 1.5px solid var(--border);
            color: var(--ink2);
            border-radius: 12px;
            padding: 11px 20px;
            font-family: 'DM Sans', sans-serif;
            font-size: .88rem;
            font-weight: 500;
            cursor: pointer;
            transition: all .2s;
            text-decoration: none;
        }

        .btn-cancel:hover {
            border-color: var(--g300);
            color: var(--g600);
        }

        .btn-delete {
            display: flex;
            align-items: center;
            gap: 7px;
            background: var(--bg-danger);
            border: 1.5px solid var(--border-danger);
            color: var(--r-danger);
            border-radius: 12px;
            padding: 11px 20px;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: .88rem;
            cursor: pointer;
            transition: all .2s;
            margin-left: auto;
        }

        .btn-delete:hover {
            background: var(--r-danger);
            border-color: var(--r-danger);
            color: #fff;
        }

        /* ══ SUCCESS TOAST ══ */
        .success-toast {
            display: none;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, var(--g50), #f0fbf4);
            border: 1.5px solid var(--g300);
            border-left: 4px solid var(--g500);
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 22px;
            animation: slideInDown .4s both;
        }

        .success-toast.show {
            display: flex;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .toast-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--g100);
            color: var(--g600);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .toast-text strong {
            font-size: .9rem;
            color: var(--g700);
            font-family: 'Syne', sans-serif;
            display: block;
        }

        .toast-text span {
            font-size: .78rem;
            color: var(--ink3);
        }

        .toast-close {
            margin-left: auto;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--ink3);
            font-size: 1rem;
            transition: color .2s;
        }

        .toast-close:hover {
            color: var(--r-danger);
        }

        /* ══ TWO-COL LAYOUT ══ */
        .edit-layout {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 22px;
            align-items: start;
        }

        /* ══ CARDS ══ */
        .card {
            background: var(--card);
            border-radius: var(--r);
            border: 1.5px solid var(--border);
            box-shadow: var(--sh);
            overflow: hidden;
            margin-bottom: 20px;
            transition: box-shadow .2s;
        }

        .card:hover {
            box-shadow: var(--sh-lg);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 18px 24px;
            border-bottom: 1.5px solid var(--border);
            background: linear-gradient(to right, var(--g50), transparent);
        }

        .card-header-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .icon-green {
            background: var(--g100);
            color: var(--g700);
        }

        .icon-yellow {
            background: var(--y100);
            color: var(--y600);
        }

        .icon-blue {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .icon-purple {
            background: #ede9fe;
            color: #6d28d9;
        }

        .icon-red {
            background: #fee2e2;
            color: #b91c1c;
        }

        .card-header-text h3 {
            font-size: .97rem;
            font-weight: 700;
            color: var(--ink);
        }

        .card-header-text p {
            font-size: .76rem;
            color: var(--ink3);
            margin-top: 1px;
        }

        .card-step-badge {
            margin-left: auto;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: var(--g500);
            color: #fff;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: .78rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-body {
            padding: 22px 24px;
        }

        /* ══ FORM FIELDS ══ */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 18px;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        .form-label {
            font-size: .8rem;
            font-weight: 600;
            color: var(--ink2);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-label i {
            color: var(--g500);
        }

        .required {
            color: #dc2626;
            font-size: .72rem;
        }

        .form-control {
            width: 100%;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            padding: 11px 14px;
            font-family: 'DM Sans', sans-serif;
            font-size: .9rem;
            color: var(--ink);
            background: #fff;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }

        .form-control:focus {
            border-color: var(--g400);
            box-shadow: 0 0 0 3px rgba(52, 179, 87, .12);
        }

        .form-control::placeholder {
            color: #a8bfb0;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 110px;
            line-height: 1.55;
        }

        select.form-control {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='%235a7065' d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 12px;
            padding-right: 36px;
            appearance: none;
        }

        .price-wrapper {
            position: relative;
        }

        .price-prefix {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: 700;
            color: var(--g500);
            font-size: .95rem;
            pointer-events: none;
        }

        .price-wrapper .form-control {
            padding-left: 28px;
        }

        /* condition toggle */
        .condition-toggle {
            display: flex;
            gap: 10px;
        }

        .cond-opt {
            flex: 1;
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 12px;
            cursor: pointer;
            text-align: center;
            transition: all .2s;
        }

        .cond-opt:hover {
            border-color: var(--g300);
            background: var(--g50);
        }

        .cond-opt.active {
            border-color: var(--g500);
            background: var(--g50);
        }

        .cond-opt i {
            display: block;
            font-size: 1.3rem;
            margin-bottom: 4px;
            color: var(--g500);
        }

        .cond-opt span {
            font-size: .83rem;
            font-weight: 600;
            color: var(--ink2);
        }

        .cond-opt small {
            display: block;
            font-size: .71rem;
            color: var(--ink3);
            margin-top: 2px;
        }

        /* ══ EXISTING IMAGES ══ */
        .existing-images-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 18px;
        }

        .existing-img-card {
            border-radius: 12px;
            overflow: hidden;
            border: 1.5px solid var(--border);
            position: relative;
            aspect-ratio: 1;
            background: var(--g50);
        }

        .existing-img-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform .3s;
        }

        .existing-img-card:hover img {
            transform: scale(1.06);
        }

        .existing-img-card .main-tag {
            position: absolute;
            bottom: 6px;
            left: 6px;
            background: var(--g500);
            color: #fff;
            font-size: .6rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 20px;
        }

        .existing-img-card .del-img-btn {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: rgba(220, 38, 38, .9);
            border: none;
            color: #fff;
            font-size: .75rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all .2s;
            backdrop-filter: blur(4px);
        }

        .existing-img-card .del-img-btn:hover {
            background: #b91c1c;
            transform: scale(1.1);
        }

        .existing-img-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--g200);
        }

        /* drop zone */
        .drop-zone {
            border: 2.5px dashed var(--g300);
            border-radius: 14px;
            padding: 32px 20px;
            text-align: center;
            background: var(--g50);
            cursor: pointer;
            transition: all .2s;
            position: relative;
        }

        .drop-zone:hover,
        .drop-zone.dragover {
            border-color: var(--g500);
            background: var(--g100);
        }

        .drop-zone input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .drop-icon {
            width: 56px;
            height: 56px;
            background: var(--g100);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--g600);
            margin: 0 auto 12px;
        }

        .drop-title {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: .95rem;
            color: var(--ink);
            margin-bottom: 4px;
        }

        .drop-sub {
            font-size: .78rem;
            color: var(--ink3);
            margin-bottom: 12px;
        }

        .drop-formats {
            display: flex;
            justify-content: center;
            gap: 7px;
            flex-wrap: wrap;
        }

        .fmt-tag {
            background: var(--g100);
            color: var(--g700);
            font-size: .68rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 20px;
        }

        .image-preview-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 14px;
        }

        .img-thumb {
            aspect-ratio: 1;
            border-radius: 10px;
            background: var(--g50);
            border: 1.5px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: var(--ink3);
            overflow: hidden;
            position: relative;
        }

        .img-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .img-thumb .main-badge {
            position: absolute;
            bottom: 4px;
            left: 4px;
            background: var(--g500);
            color: #fff;
            font-size: .6rem;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 20px;
        }

        /* ══ MAP ══ */
        #map {
            height: 300px;
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
            border: 1.5px solid var(--border);
            margin-top: 10px;
        }

        .map-action-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 14px;
        }

        .btn-map-loc {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--g500);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: .87rem;
            cursor: pointer;
            transition: all .2s;
        }

        .btn-map-loc:hover {
            background: var(--g600);
            transform: translateY(-1px);
        }

        #est {
            font-size: .82rem;
            color: var(--ink3);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* ══ RIGHT COLUMN ══ */

        /* Preview card */
        .preview-card {
            border-radius: var(--r);
            border: 1.5px solid var(--border);
            background: var(--card);
            box-shadow: var(--sh);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .preview-header {
            background: linear-gradient(135deg, var(--g800), var(--g700));
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #fff;
        }

        .preview-header h4 {
            font-size: .9rem;
            font-weight: 700;
        }

        .preview-header .live-badge {
            margin-left: auto;
            font-size: .68rem;
            color: var(--y300);
            background: rgba(245, 168, 28, .15);
            border: 1px solid rgba(245, 168, 28, .25);
            padding: 2px 8px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .live-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--g400);
            animation: pulse-notif 2s infinite;
        }

        .preview-img-slot {
            height: 158px;
            background: linear-gradient(135deg, var(--g50), var(--g100));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--g300);
            position: relative;
            overflow: hidden;
        }

        .preview-img-slot img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .preview-cond-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: var(--g500);
            color: #fff;
            font-size: .66rem;
            font-weight: 700;
            padding: 3px 9px;
            border-radius: 20px;
            text-transform: uppercase;
        }

        .preview-body {
            padding: 14px 16px;
        }

        .preview-price {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.3rem;
            color: var(--g600);
            margin-bottom: 4px;
        }

        .preview-title {
            font-size: .88rem;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 8px;
            min-height: 20px;
        }

        .preview-meta {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .preview-meta-row {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: .74rem;
            color: var(--ink3);
        }

        .preview-meta-row i {
            color: var(--g400);
        }

        .preview-footer {
            padding: 12px 16px;
            border-top: 1.5px solid var(--border);
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--g50);
        }

        .preview-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--g100);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .68rem;
            font-weight: 700;
            color: var(--g700);
        }

        .preview-seller {
            font-size: .77rem;
            color: var(--ink2);
            font-weight: 500;
        }

        .preview-seller-rating {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 3px;
            font-size: .7rem;
            color: var(--y400);
        }

        /* Status card */
        .status-card {
            background: var(--card);
            border-radius: var(--r);
            border: 1.5px solid var(--border);
            box-shadow: var(--sh);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .sc-header {
            background: linear-gradient(to right, var(--y50), #fffdf0);
            border-bottom: 1.5px solid var(--y200);
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sc-header h4 {
            font-size: .9rem;
            font-weight: 700;
            color: var(--ink);
        }

        .sc-header i {
            color: var(--y500);
        }

        .sc-body {
            padding: 16px 18px;
        }

        .sc-stat {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--border);
        }

        .sc-stat:last-child {
            border-bottom: none;
        }

        .sc-stat-label {
            font-size: .8rem;
            color: var(--ink3);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .sc-stat-label i {
            color: var(--g400);
            font-size: .85rem;
        }

        .sc-stat-val {
            font-size: .82rem;
            font-weight: 600;
            color: var(--ink);
        }

        .sc-status-active {
            color: var(--g600);
            background: var(--g100);
            padding: 2px 8px;
            border-radius: 20px;
            font-size: .72rem;
            font-weight: 700;
        }

        .sc-status-inactive {
            color: #dc2626;
            background: #fee2e2;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: .72rem;
            font-weight: 700;
        }

        /* Danger zone */
        .danger-zone {
            background: var(--card);
            border-radius: var(--r);
            border: 1.5px solid var(--border-danger);
            box-shadow: var(--sh);
            overflow: hidden;
        }

        .dz-header {
            background: linear-gradient(to right, #fef2f2, #fff);
            border-bottom: 1.5px solid var(--border-danger);
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dz-header h4 {
            font-size: .88rem;
            font-weight: 700;
            color: var(--r-danger);
        }

        .dz-header i {
            color: var(--r-danger);
        }

        .dz-body {
            padding: 16px 18px;
            display: flex;
            flex-direction: column;
            gap: 9px;
        }

        .dz-text {
            font-size: .78rem;
            color: var(--ink3);
            line-height: 1.5;
        }

        /* checklist */
        .checklist-card {
            background: var(--card);
            border-radius: var(--r);
            border: 1.5px solid var(--border);
            box-shadow: var(--sh);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .cl-header {
            background: linear-gradient(to right, var(--g100), var(--g50));
            border-bottom: 1.5px solid var(--border);
            padding: 13px 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cl-header h4 {
            font-size: .88rem;
            font-weight: 700;
            color: var(--ink);
        }

        .cl-body {
            padding: 14px 18px;
        }

        .check-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 7px 0;
            border-bottom: 1px solid var(--border);
            font-size: .82rem;
            color: var(--ink2);
        }

        .check-item:last-child {
            border-bottom: none;
        }

        .check-item i {
            font-size: .9rem;
            color: #ccc;
        }

        .check-item.done i {
            color: var(--g500);
        }

        /* ══ DELETE MODAL ══ */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .55);
            z-index: 500;
            display: none;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal-box {
            background: #fff;
            border-radius: 20px;
            padding: 32px;
            max-width: 420px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .2);
            animation: modalIn .3s cubic-bezier(.22, .9, .36, 1) both;
        }

        @keyframes modalIn {
            from {
                opacity: 0;
                transform: scale(.93);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .modal-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #fee2e2;
            color: #dc2626;
            font-size: 1.6rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .modal-title {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.1rem;
            color: var(--ink);
            text-align: center;
            margin-bottom: 8px;
        }

        .modal-text {
            font-size: .85rem;
            color: var(--ink3);
            text-align: center;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .modal-product-name {
            font-weight: 700;
            color: var(--ink);
        }

        .modal-btns {
            display: flex;
            gap: 10px;
        }

        .modal-btn-cancel {
            flex: 1;
            background: var(--card);
            border: 1.5px solid var(--border);
            color: var(--ink2);
            border-radius: 12px;
            padding: 12px;
            font-family: 'DM Sans', sans-serif;
            font-size: .9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all .2s;
        }

        .modal-btn-cancel:hover {
            border-color: var(--g300);
            color: var(--g600);
        }

        .modal-btn-delete {
            flex: 1;
            background: #dc2626;
            border: none;
            color: #fff;
            border-radius: 12px;
            padding: 12px;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: .9rem;
            cursor: pointer;
            transition: all .2s;
        }

        .modal-btn-delete:hover {
            background: #b91c1c;
        }

        /* ══ ANIMATIONS ══ */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            animation: fadeUp .4s both;
        }

        .card:nth-child(1) {
            animation-delay: .04s;
        }

        .card:nth-child(2) {
            animation-delay: .09s;
        }

        .card:nth-child(3) {
            animation-delay: .14s;
        }

        input[type="hidden"] {
            display: none !important;
        }

        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--g200);
            border-radius: 6px;
        }

        @media(max-width:1100px) {
            .edit-layout {
                grid-template-columns: 1fr;
            }
        }

        @media(max-width:800px) {
            .sidebar {
                display: none;
            }

            .main-content {
                padding: 16px 16px 60px;
            }
        }
    </style>
</head>

<body>

    <!-- ══ TOPBAR ══ -->
    <header class="topbar">
        <a href="./inde.php" class="tb-logo">
            <div class="tb-logo-box"><i class="bi bi-shop-window"></i></div>
            Comercio<em>Local</em>
        </a>

        <div class="tb-search">
            <input type="text" placeholder="Buscar productos, anuncios...">
            <button><i class="bi bi-search"></i></button>
        </div>

        <div class="tb-spacer"></div>

        <div class="tb-actions">
            <div class="tb-icon-btn"><i class="bi bi-bell"></i>
                <div class="notif-dot"></div>
            </div>
            <div class="tb-icon-btn"><i class="bi bi-chat-dots"></i></div>
            <div class="tb-icon-btn"><i class="bi bi-question-circle"></i></div>

            <?php if ($isLoggedIn): ?>
                <div class="user-menu-wrap" id="userMenuWrap">
                    <div class="user-chip" id="userChip">
                        <div style="position:relative;">
                            <div class="user-avatar"><?php echo $userInitial; ?></div>
                            <div class="online-dot"></div>
                        </div>
                        <div>
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
                            <a class="ud-lnk" href="./dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                            <a class="ud-lnk" href="./crear.php"><i class="bi bi-plus-square"></i> Publicar producto</a>
                            <a class="ud-lnk" href="./allProduct.php"><i class="bi bi-box-seam"></i> Mis anuncios</a>
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

        <!-- ══ SIDEBAR ══ -->
        <aside class="sidebar">
            <div class="sb-section-label">Principal</div>
            <a class="sb-link" href="./dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a class="sb-link" href="./crear.php"><i class="bi bi-plus-square-fill"></i> Publicar producto</a>
            <a class="sb-link active" href="./allProduct.php">
                <i class="bi bi-box-seam"></i> Mis productos
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

        <!-- ══ MAIN ══ -->
        <main class="main-content">

            <!-- Page header -->
            <div class="pg-header">
                <div>
                    <div class="pg-breadcrumb">
                        <a href="./dashboard.php">Dashboard</a>
                        <i class="bi bi-chevron-right" style="font-size:.65rem;"></i>
                        <a href="./allProduct.php">Mis productos</a>
                        <i class="bi bi-chevron-right" style="font-size:.65rem;"></i>
                        <span>Editar</span>
                    </div>
                    <h1 class="pg-title">Editar <span>producto</span></h1>
                    <p class="pg-subtitle">Actualiza la información de tu anuncio y guarda los cambios.</p>
                    <div class="pg-id-badge"><i class="bi bi-hash"></i> ID <?php echo htmlspecialchars($id); ?></div>
                </div>
                <div class="action-header">
                    <button class="btn-save" form="editForm" type="submit">
                        <i class="bi bi-floppy-fill"></i> Guardar cambios
                    </button>
                    <a class="btn-cancel" href="./allProduct.php">
                        <i class="bi bi-x-lg"></i> Cancelar
                    </a>
                    <button class="btn-delete" type="button" onclick="document.getElementById('deleteModal').classList.add('show')">
                        <i class="bi bi-trash-fill"></i> Eliminar
                    </button>
                </div>
            </div>

            <!-- Success toast (shown after save) -->
            <div class="success-toast" id="successToast">
                <div class="toast-icon"><i class="bi bi-check-circle-fill"></i></div>
                <div class="toast-text">
                    <strong>¡Producto actualizado!</strong>
                    <span>Los cambios se guardaron correctamente.</span>
                </div>
                <button class="toast-close" onclick="document.getElementById('successToast').classList.remove('show')">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <!-- ══ FORM — LOGIC PRESERVED ══ -->
            <form id="editForm" action="./edit_product.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($id); ?>">
                <input type="hidden" name="latitude" id="latitude" value="<?php echo htmlspecialchars($product['latitude'] ?? ''); ?>">
                <input type="hidden" name="longitude" id="longitude" value="<?php echo htmlspecialchars($product['longitude'] ?? ''); ?>">

                <div class="edit-layout">

                    <!-- ── LEFT COLUMN ── -->
                    <div class="left-col">

                        <!-- Step 1: Images -->
                        <div class="card">
                            <div class="card-header">
                                <div class="card-header-icon icon-purple"><i class="bi bi-images"></i></div>
                                <div class="card-header-text">
                                    <h3>Imágenes del producto</h3>
                                    <p>Gestiona las fotos actuales o agrega nuevas.</p>
                                </div>
                                <div class="card-step-badge">1</div>
                            </div>
                            <div class="card-body">

                                <!-- Existing images — LOGIC PRESERVED -->
                                <?php if (!empty($existingImages)): ?>
                                    <div style="font-size:.78rem;font-weight:600;color:var(--ink2);margin-bottom:10px;display:flex;align-items:center;gap:6px;">
                                        <i class="bi bi-images" style="color:var(--g500);"></i>
                                        Fotos actuales (<?php echo count($existingImages); ?>)
                                    </div>
                                    <div class="existing-images-grid">
                                        <?php foreach ($existingImages as $idx => $img): ?>
                                            <div class="existing-img-card">
                                                <img src="./productos/uploads/<?php echo htmlspecialchars($img['image_url']); ?>"
                                                    alt="Imagen <?php echo $idx + 1; ?>">
                                                <?php if ($idx === 0): ?>
                                                    <span class="main-tag">Principal</span>
                                                <?php endif; ?>
                                                <button type="button" class="del-img-btn"
                                                    onclick="removeExistingImg(this, <?php echo $img['id']; ?>)"
                                                    title="Eliminar imagen">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                                <!-- Hidden input to track deletions -->
                                                <input type="hidden" name="keep_images[]" value="<?php echo $img['id']; ?>" id="keep_img_<?php echo $img['id']; ?>">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div style="height:1px;background:var(--border);margin:16px 0;"></div>
                                <?php endif; ?>

                                <!-- Upload new images -->
                                <div style="font-size:.78rem;font-weight:600;color:var(--ink2);margin-bottom:10px;display:flex;align-items:center;gap:6px;">
                                    <i class="bi bi-plus-circle" style="color:var(--g500);"></i>
                                    Agregar nuevas fotos
                                </div>
                                <div class="drop-zone" id="dropZone">
                                    <input type="file" id="fotos" name="fotos[]" accept="image/*" multiple>
                                    <div class="drop-icon"><i class="bi bi-cloud-upload"></i></div>
                                    <div class="drop-title">Arrastra nuevas fotos aquí</div>
                                    <div class="drop-sub">o haz clic para seleccionar desde tu dispositivo</div>
                                    <div class="drop-formats">
                                        <span class="fmt-tag">JPG</span>
                                        <span class="fmt-tag">PNG</span>
                                        <span class="fmt-tag">WEBP</span>
                                        <span class="fmt-tag">Máx. 5MB</span>
                                    </div>
                                </div>
                                <div class="image-preview-grid" id="imgPreviewGrid" style="display:none;"></div>
                            </div>
                        </div>

                        <!-- Step 2: Product info -->
                        <div class="card">
                            <div class="card-header">
                                <div class="card-header-icon icon-green"><i class="bi bi-pencil-square"></i></div>
                                <div class="card-header-text">
                                    <h3>Información del producto</h3>
                                    <p>Edita los detalles del anuncio.</p>
                                </div>
                                <div class="card-step-badge">2</div>
                            </div>
                            <div class="card-body">

                                <div class="form-group">
                                    <label class="form-label" for="nombre">
                                        <i class="bi bi-type"></i> Título del anuncio <span class="required">*</span>
                                    </label>
                                    <!-- LOGIC PRESERVED: name="nombre", prefilled -->
                                    <input type="text" id="nombre" name="nombre" class="form-control"
                                        value="<?php echo htmlspecialchars($product['title'] ?? ''); ?>"
                                        placeholder="Ej: iPhone 13 128GB Negro – Perfecto estado" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="descripcion">
                                        <i class="bi bi-card-text"></i> Descripción
                                    </label>
                                    <!-- LOGIC PRESERVED: name="descripcion", prefilled -->
                                    <textarea id="descripcion" name="descripcion" class="form-control"
                                        placeholder="Describe el estado, características, accesorios incluidos..."><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label" for="precio">
                                            <i class="bi bi-currency-dollar"></i> Precio <span class="required">*</span>
                                        </label>
                                        <div class="price-wrapper">
                                            <span class="price-prefix">$</span>
                                            <!-- LOGIC PRESERVED: name="precio", prefilled -->
                                            <input type="number" id="precio" name="precio" class="form-control"
                                                value="<?php echo htmlspecialchars($product['price'] ?? ''); ?>"
                                                placeholder="0" required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="bi bi-tag"></i> Estado del anuncio <span class="required">*</span>
                                        </label>
                                        <!-- LOGIC PRESERVED: name="estado" -->
                                        <select name="estado" class="form-control" required>
                                            <option value="">Seleccionar estado</option>
                                            <option value="1" <?php echo ($product['status'] == 1 || $product['status'] === 'disponible') ? 'selected' : ''; ?>>✅ Activo</option>
                                            <option value="0" <?php echo ($product['status'] == 0) ? 'selected' : ''; ?>>⏸ Inactivo</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label" for="categoria">
                                            <i class="bi bi-grid"></i> Categoría <span class="required">*</span>
                                        </label>
                                        <!-- LOGIC PRESERVED: category query -->
                                        <select name="categoria" id="categoria" class="form-control" required>
                                            <option value="">Seleccionar categoría</option>
                                            <?php while ($cat = mysqli_fetch_assoc($cat_result)): ?>
                                                <option value="<?php echo $cat['id']; ?>"
                                                    <?php echo ($product['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="bi bi-award"></i> Condición
                                        </label>
                                        <?php
                                        $cond = strtolower($product['condition_type'] ?? 'usado');
                                        ?>
                                        <div class="condition-toggle">
                                            <div class="cond-opt <?php echo ($cond === 'nuevo') ? 'active' : ''; ?>"
                                                onclick="selectCondition(this,'nuevo')">
                                                <i class="bi bi-star-fill"></i>
                                                <span>Nuevo</span>
                                                <small>Sin uso, en caja</small>
                                            </div>
                                            <div class="cond-opt <?php echo ($cond !== 'nuevo') ? 'active' : ''; ?>"
                                                onclick="selectCondition(this,'usado')">
                                                <i class="bi bi-recycle"></i>
                                                <span>Usado</span>
                                                <small>Buen estado</small>
                                            </div>
                                        </div>
                                        <input type="hidden" name="condicion" id="condicionHidden" value="<?php echo htmlspecialchars($product['condition_type'] ?? 'usado'); ?>">
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Step 3: Location -->
                        <div class="card">
                            <div class="card-header">
                                <div class="card-header-icon icon-blue"><i class="bi bi-geo-alt-fill"></i></div>
                                <div class="card-header-text">
                                    <h3>Ubicación del producto</h3>
                                    <p>Actualiza dónde está disponible para el comprador.</p>
                                </div>
                                <div class="card-step-badge">3</div>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label"><i class="bi bi-signpost-split"></i> Sector / Barrio</label>
                                    <input type="text" class="form-control"
                                        name="sector"
                                        value="<?php echo htmlspecialchars($product['location'] ?? ''); ?>"
                                        placeholder="Ej: Chapinero, Bogotá">
                                </div>

                                <!-- LOGIC PRESERVED: map with id="map" -->
                                <div id="map"></div>

                                <div class="map-action-row">
                                    <!-- LOGIC PRESERVED: id="btnUbicacion" -->
                                    <button type="button" id="btnUbicacion" class="btn-map-loc">
                                        <i class="bi bi-crosshair"></i>
                                        Actualizar mi ubicación
                                    </button>
                                    <!-- LOGIC PRESERVED: id="est" -->
                                    <p id="est"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Bottom action bar (repeated for convenience) -->
                        <div style="display:flex;gap:10px;padding-bottom:8px;flex-wrap:wrap;">
                            <button type="submit" class="btn-save">
                                <i class="bi bi-floppy-fill"></i> Guardar cambios
                            </button>
                            <a class="btn-cancel" href="./allProduct.php">
                                <i class="bi bi-x-lg"></i> Cancelar
                            </a>
                        </div>

                    </div><!-- /left-col -->

                    <!-- ── RIGHT COLUMN ── -->
                    <div class="right-col">

                        <!-- Live preview -->
                        <div class="preview-card">
                            <div class="preview-header">
                                <i class="bi bi-eye-fill"></i>
                                <h4>Vista previa</h4>
                                <div class="live-badge">
                                    <div class="live-dot"></div> En vivo
                                </div>
                            </div>
                            <div class="preview-img-slot" id="previewImgSlot">
                                <?php if (!empty($existingImages)): ?>
                                    <img id="previewMainImg" src="./productos/uploads/<?php echo htmlspecialchars($existingImages[0]['image_url']); ?>" alt="">
                                <?php else: ?>
                                    <i class="bi bi-image" id="previewImgIcon"></i>
                                <?php endif; ?>
                                <div class="preview-cond-badge" id="previewCond">
                                    <?php echo ucfirst($product['condition_type'] ?? 'Usado'); ?>
                                </div>
                            </div>
                            <div class="preview-body">
                                <div class="preview-price" id="previewPrice">
                                    $<?php echo number_format($product['price'] ?? 0, 0, ',', '.'); ?>
                                </div>
                                <div class="preview-title" id="previewTitle">
                                    <?php echo htmlspecialchars($product['title'] ?? 'Título del producto'); ?>
                                </div>
                                <div class="preview-meta">
                                    <div class="preview-meta-row"><i class="bi bi-geo-alt-fill"></i> Bogotá, Colombia</div>
                                    <div class="preview-meta-row"><i class="bi bi-clock-fill"></i> Recientemente</div>
                                </div>
                            </div>
                            <div class="preview-footer">
                                <div class="preview-avatar"><?php echo $userInitial; ?></div>
                                <span class="preview-seller"><?php echo htmlspecialchars($userName); ?></span>
                                <div class="preview-seller-rating">
                                    <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Checklist -->
                        <div class="checklist-card">
                            <div class="cl-header">
                                <i class="bi bi-check2-circle" style="color:var(--g500);font-size:1.05rem;"></i>
                                <h4>Checklist del anuncio</h4>
                            </div>
                            <div class="cl-body">
                                <div class="check-item <?php echo !empty($product['title']) ? 'done' : ''; ?>" id="chk-titulo">
                                    <i class="bi <?php echo !empty($product['title']) ? 'bi-check-circle-fill' : 'bi-circle'; ?>"></i>
                                    Título del producto
                                </div>
                                <div class="check-item <?php echo !empty($product['price']) ? 'done' : ''; ?>" id="chk-precio">
                                    <i class="bi <?php echo !empty($product['price']) ? 'bi-check-circle-fill' : 'bi-circle'; ?>"></i>
                                    Precio definido
                                </div>
                                <div class="check-item <?php echo !empty($product['description']) ? 'done' : ''; ?>" id="chk-descripcion">
                                    <i class="bi <?php echo !empty($product['description']) ? 'bi-check-circle-fill' : 'bi-circle'; ?>"></i>
                                    Descripción completa
                                </div>
                                <div class="check-item <?php echo !empty($product['category_id']) ? 'done' : ''; ?>" id="chk-categoria">
                                    <i class="bi <?php echo !empty($product['category_id']) ? 'bi-check-circle-fill' : 'bi-circle'; ?>"></i>
                                    Categoría seleccionada
                                </div>
                                <div class="check-item <?php echo !empty($existingImages) ? 'done' : ''; ?>" id="chk-fotos">
                                    <i class="bi <?php echo !empty($existingImages) ? 'bi-check-circle-fill' : 'bi-circle'; ?>"></i>
                                    Fotos del producto
                                </div>
                                <div class="check-item <?php echo (!empty($product['latitude']) && !empty($product['longitude'])) ? 'done' : ''; ?>" id="chk-ubicacion">
                                    <i class="bi <?php echo (!empty($product['latitude']) && !empty($product['longitude'])) ? 'bi-check-circle-fill' : 'bi-circle'; ?>"></i>
                                    Ubicación en el mapa
                                </div>
                            </div>
                        </div>

                        <!-- Product stats -->
                        <div class="status-card">
                            <div class="sc-header">
                                <i class="bi bi-bar-chart-line-fill"></i>
                                <h4>Estado del anuncio</h4>
                            </div>
                            <div class="sc-body">
                                <div class="sc-stat">
                                    <span class="sc-stat-label"><i class="bi bi-circle-fill"></i> Estado actual</span>
                                    <?php if ($product['status'] == 1 || $product['status'] === 'disponible'): ?>
                                        <span class="sc-status-active">● Activo</span>
                                    <?php else: ?>
                                        <span class="sc-status-inactive">○ Inactivo</span>
                                    <?php endif; ?>
                                </div>
                                <div class="sc-stat">
                                    <span class="sc-stat-label"><i class="bi bi-eye-fill"></i> Vistas</span>
                                    <span class="sc-stat-val">142</span>
                                </div>
                                <div class="sc-stat">
                                    <span class="sc-stat-label"><i class="bi bi-heart-fill"></i> Favoritos</span>
                                    <span class="sc-stat-val">8</span>
                                </div>
                                <div class="sc-stat">
                                    <span class="sc-stat-label"><i class="bi bi-images"></i> Fotos</span>
                                    <span class="sc-stat-val"><?php echo count($existingImages); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Danger zone -->
                        <div class="danger-zone">
                            <div class="dz-header">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <h4>Zona de peligro</h4>
                            </div>
                            <div class="dz-body">
                                <p class="dz-text">Al eliminar este producto se borrarán todas sus imágenes, mensajes e historial de vistas. Esta acción no se puede deshacer.</p>
                                <button type="button" class="btn-delete" style="width:100%;justify-content:center;"
                                    onclick="document.getElementById('deleteModal').classList.add('show')">
                                    <i class="bi bi-trash-fill"></i> Eliminar producto permanentemente
                                </button>
                            </div>
                        </div>

                    </div><!-- /right-col -->
                </div><!-- /edit-layout -->
            </form>

        </main>
    </div><!-- /page-shell -->

    <!-- ══ DELETE MODAL ══ -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-box">
            <div class="modal-icon"><i class="bi bi-trash-fill"></i></div>
            <div class="modal-title">¿Eliminar este producto?</div>
            <div class="modal-text">
                Estás a punto de eliminar permanentemente
                <span class="modal-product-name">"<?php echo htmlspecialchars($product['title'] ?? 'este producto'); ?>"</span>.
                Esta acción no se puede deshacer.
            </div>
            <div class="modal-btns">
                <button class="modal-btn-cancel" onclick="document.getElementById('deleteModal').classList.remove('show')">
                    Cancelar
                </button>
                <a href="./delete_product.php?id=<?php echo htmlspecialchars($id); ?>" class="modal-btn-delete"
                    style="display:flex;align-items:center;justify-content:center;gap:7px;">
                    <i class="bi bi-trash-fill"></i> Sí, eliminar
                </a>
            </div>
        </div>
    </div>

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
                if (e.key === 'Escape') {
                    wrap.classList.remove('open');
                    document.getElementById('deleteModal').classList.remove('show');
                }
            });
        }

        /* ── Close modal on overlay click ── */
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('show');
        });

        /* ── LOGIC PRESERVED: geolocation + Leaflet ── */
        const estado = document.getElementById("est");
        const btn = document.getElementById("btnUbicacion");
        let map;

        <?php if (!empty($product['latitude']) && !empty($product['longitude'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof L !== 'undefined') {
                    const lat = <?php echo floatval($product['latitude']); ?>;
                    const lon = <?php echo floatval($product['longitude']); ?>;
                    map = L.map('map').setView([lat, lon], 15);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap'
                    }).addTo(map);
                    L.marker([lat, lon]).addTo(map).bindPopup("📍 Ubicación actual").openPopup();
                }
            });
        <?php else: ?>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof L !== 'undefined') {
                    map = L.map('map').setView([4.711, -74.0721], 11);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap'
                    }).addTo(map);
                }
            });
        <?php endif; ?>

        btn.addEventListener("click", () => {
            if (!navigator.geolocation) {
                estado.textContent = "Geolocalización no soportada.";
                return;
            }
            estado.innerHTML = '<i class="bi bi-hourglass-split"></i> Obteniendo ubicación...';
            navigator.geolocation.getCurrentPosition((pos) => {
                const lat = pos.coords.latitude;
                const lon = pos.coords.longitude;
                document.getElementById("latitude").value = lat;
                document.getElementById("longitude").value = lon;
                estado.innerHTML = `<i class="bi bi-geo-alt-fill" style="color:var(--g500);"></i> Lat ${lat.toFixed(5)}, Lon ${lon.toFixed(5)}`;
                if (map) {
                    map.remove();
                }
                map = L.map('map').setView([lat, lon], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);
                L.marker([lat, lon]).addTo(map).bindPopup("📍 Estás aquí").openPopup();
                markDone('chk-ubicacion');
            }, (err) => {
                estado.innerHTML = `<i class="bi bi-exclamation-circle" style="color:#dc2626;"></i> ${err.message}`;
            });
        });

        /* ── CONDITION TOGGLE ── */
        function selectCondition(el, val) {
            document.querySelectorAll('.cond-opt').forEach(o => o.classList.remove('active'));
            el.classList.add('active');
            document.getElementById('condicionHidden').value = val;
            const badge = document.getElementById('previewCond');
            badge.textContent = val === 'nuevo' ? 'Nuevo' : 'Usado';
            badge.style.background = val === 'nuevo' ? 'var(--g500)' : 'var(--y400)';
            badge.style.color = val === 'nuevo' ? '#fff' : 'var(--g900)';
        }

        /* ── LIVE PREVIEW ── */
        const titleInput = document.getElementById('nombre');
        const precioInput = document.getElementById('precio');

        titleInput.addEventListener('input', () => {
            document.getElementById('previewTitle').textContent = titleInput.value || 'Título del producto';
            toggle('chk-titulo', !!titleInput.value.trim());
        });

        precioInput.addEventListener('input', () => {
            const v = parseInt(precioInput.value) || 0;
            document.getElementById('previewPrice').textContent = v ? `$${v.toLocaleString('es-CO')}` : '$0';
            toggle('chk-precio', v > 0);
        });

        document.getElementById('descripcion').addEventListener('input', function() {
            toggle('chk-descripcion', this.value.length > 10);
        });

        document.getElementById('categoria').addEventListener('change', function() {
            toggle('chk-categoria', !!this.value);
        });

        /* ── REMOVE EXISTING IMAGE ── */
        function removeExistingImg(btn, imgId) {
            const card = btn.closest('.existing-img-card');
            card.style.opacity = '0';
            card.style.transform = 'scale(.85)';
            card.style.transition = 'all .25s';
            setTimeout(() => card.remove(), 250);
            const hidden = document.getElementById('keep_img_' + imgId);
            if (hidden) hidden.remove();
            // if no more existing images, uncheck checklist
            const remaining = document.querySelectorAll('.existing-img-card').length - 1;
            if (remaining === 0 && document.querySelectorAll('.img-thumb img').length === 0) {
                toggle('chk-fotos', false);
            }
        }

        /* ── DRAG & DROP NEW IMAGES ── */
        const dropZone = document.getElementById('dropZone');
        const fotosInput = document.getElementById('fotos');
        const gridEl = document.getElementById('imgPreviewGrid');

        dropZone.addEventListener('dragover', e => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });
        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        });
        fotosInput.addEventListener('change', () => handleFiles(fotosInput.files));

        function handleFiles(files) {
            if (!files.length) return;
            gridEl.style.display = 'grid';
            gridEl.innerHTML = '';
            Array.from(files).slice(0, 8).forEach((f, i) => {
                const reader = new FileReader();
                reader.onload = ev => {
                    const div = document.createElement('div');
                    div.className = 'img-thumb';
                    div.innerHTML = `<img src="${ev.target.result}" alt="">` + (i === 0 ? '<div class="main-badge">Nueva</div>' : '');
                    gridEl.appendChild(div);
                    if (i === 0) {
                        const slot = document.getElementById('previewImgSlot');
                        const existing = slot.querySelector('img');
                        if (existing) existing.src = ev.target.result;
                        else {
                            slot.innerHTML = `<img id="previewMainImg" src="${ev.target.result}" alt=""><div class="preview-cond-badge" id="previewCond">${document.getElementById('condicionHidden').value}</div>`;
                        }
                    }
                };
                reader.readAsDataURL(f);
            });
            markDone('chk-fotos');
        }

        /* ── CHECKLIST HELPERS ── */
        function markDone(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.add('done');
            el.querySelector('i').className = 'bi bi-check-circle-fill';
        }

        function toggle(id, done) {
            const el = document.getElementById(id);
            if (!el) return;
            if (done) {
                el.classList.add('done');
                el.querySelector('i').className = 'bi bi-check-circle-fill';
            } else {
                el.classList.remove('done');
                el.querySelector('i').className = 'bi bi-circle';
            }
        }

        /* ── SHOW SUCCESS TOAST after form submit (demo) ── */
        document.getElementById('editForm').addEventListener('submit', function() {
            // In a real scenario this shows after redirect; here we show optimistically
            setTimeout(() => document.getElementById('successToast').classList.add('show'), 600);
        });
    </script>

</body>

</html>
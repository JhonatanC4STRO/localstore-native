<?php
/**
 * Admin Users API — ComercioLocal
 * All user management actions go through this endpoint.
 * Returns JSON for all requests.
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/conexion.php';

/* ── Auth guard ── */
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso denegado']);
    exit();
}

/* ════════════════════════════════════
   SCHEMA BOOTSTRAP
   Add missing columns to users table
════════════════════════════════════ */
function ensure_columns(mysqli $db): void
{
    $needed = [
        'phone'         => "VARCHAR(30) DEFAULT NULL",
        'city'          => "VARCHAR(100) DEFAULT NULL",
        'address'       => "VARCHAR(255) DEFAULT NULL",
        'bio'           => "TEXT DEFAULT NULL",
        'profile_photo' => "VARCHAR(255) DEFAULT NULL",
        'status'        => "ENUM('active','blocked','suspended') NOT NULL DEFAULT 'active'",
        'role'          => "ENUM('user','seller','admin') NOT NULL DEFAULT 'user'",
        'created_at'    => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        'updated_at'    => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
        'deleted_at'    => "TIMESTAMP DEFAULT NULL",
    ];

    $existing = [];
    $res = mysqli_query($db, "SHOW COLUMNS FROM users");
    while ($r = mysqli_fetch_assoc($res)) {
        $existing[] = strtolower($r['Field']);
    }

    foreach ($needed as $col => $def) {
        if (!in_array(strtolower($col), $existing, true)) {
            mysqli_query($db, "ALTER TABLE users ADD COLUMN `$col` $def");
        }
    }

    /* Migrate type_user → role if role column was just added and type_user exists */
    if (in_array('type_user', $existing) && in_array('role', $existing)) {
        mysqli_query($db, "
            UPDATE users
            SET role = CASE
                WHEN type_user IN ('seller','vendedor') THEN 'seller'
                WHEN type_user IN ('admin') THEN 'admin'
                ELSE 'user'
            END
            WHERE role = 'user' AND type_user IS NOT NULL
        ");
    }
}

ensure_columns($conn);

/* ════════════════════════════════════
   ROUTE DISPATCHER
════════════════════════════════════ */
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($method) {
    case 'GET':
        switch ($action) {
            case 'list':   route_list($conn);   break;
            case 'detail': route_detail($conn); break;
            case 'stats':  route_stats($conn);  break;
            default:       json_error('Acción no válida'); break;
        }
        break;

    case 'POST':
        switch ($action) {
            case 'block':       route_set_status($conn, 'blocked');    break;
            case 'activate':    route_set_status($conn, 'active');     break;
            case 'suspend':     route_set_status($conn, 'suspended');  break;
            case 'delete':      route_delete($conn);                   break;
            case 'change_role': route_change_role($conn);              break;
            default:            json_error('Acción no válida');         break;
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
}

/* ════════════════════════════════════
   ROUTE: LIST USERS  (paginated, filtered, sorted)
════════════════════════════════════ */
function route_list(mysqli $db): void
{
    /* Inputs */
    $page     = max(1, (int)($_GET['page'] ?? 1));
    $limit    = min(100, max(5, (int)($_GET['limit'] ?? 25)));
    $offset   = ($page - 1) * $limit;

    $search   = trim($_GET['search'] ?? '');
    $role     = trim($_GET['role'] ?? '');
    $status   = trim($_GET['status'] ?? '');
    $verified = $_GET['verified'] ?? '';
    $date_from= trim($_GET['date_from'] ?? '');
    $date_to  = trim($_GET['date_to'] ?? '');

    $sort_col = $_GET['sort'] ?? 'created_at';
    $sort_dir = strtoupper($_GET['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

    $allowed_sorts = ['id', 'full_name', 'email', 'role', 'status', 'created_at', 'city'];
    if (!in_array($sort_col, $allowed_sorts, true)) $sort_col = 'created_at';

    /* Build WHERE */
    $where   = ["u.deleted_at IS NULL"];
    $params  = [];
    $types   = '';

    if ($search !== '') {
        $like = "%{$search}%";
        $where[] = "(u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
        $params  = array_merge($params, [$like, $like, $like]);
        $types  .= 'sss';
    }

    $allowed_roles = ['user','seller','admin'];
    if ($role !== '' && in_array($role, $allowed_roles, true)) {
        $where[] = "u.role = ?";
        $params[] = $role; $types .= 's';
    }

    $allowed_statuses = ['active','blocked','suspended'];
    if ($status !== '' && in_array($status, $allowed_statuses, true)) {
        $where[] = "u.status = ?";
        $params[] = $status; $types .= 's';
    }

    if ($verified === '1') {
        $where[] = "u.verified_at IS NOT NULL";
    } elseif ($verified === '0') {
        $where[] = "u.verified_at IS NULL";
    }

    if ($date_from !== '' && strtotime($date_from)) {
        $where[] = "DATE(u.created_at) >= ?";
        $params[] = $date_from; $types .= 's';
    }
    if ($date_to !== '' && strtotime($date_to)) {
        $where[] = "DATE(u.created_at) <= ?";
        $params[] = $date_to; $types .= 's';
    }

    $where_sql = implode(' AND ', $where);

    /* Check if verified_at column exists */
    $has_verified = mysqli_num_rows(mysqli_query($db, "SHOW COLUMNS FROM users LIKE 'verified_at'")) > 0;
    $verified_sel = $has_verified ? "u.verified_at IS NOT NULL AS is_verified, u.verified_at," : "0 AS is_verified, NULL AS verified_at,";

    /* Count total matching */
    $stmt_count = mysqli_prepare($db,
        "SELECT COUNT(*) AS total FROM users u WHERE $where_sql");
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt_count, $types, ...$params);
    }
    mysqli_stmt_execute($stmt_count);
    $total_rows = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_count))['total'];

    /* Fetch page */
    $sql = "
        SELECT
            u.id, u.full_name, u.email, u.phone, u.city,
            u.role, u.status, u.created_at, u.updated_at,
            u.profile_photo, u.bio,
            $verified_sel
            (SELECT COUNT(*) FROM products p WHERE p.user_id = u.id) AS product_count,
            (SELECT COUNT(*) FROM products p WHERE p.user_id = u.id AND p.status = 'disponible') AS active_products
        FROM users u
        WHERE $where_sql
        ORDER BY u.`$sort_col` $sort_dir
        LIMIT ? OFFSET ?
    ";

    $stmt = mysqli_prepare($db, $sql);
    $all_params = array_merge($params, [$limit, $offset]);
    $all_types  = $types . 'ii';
    mysqli_stmt_bind_param($stmt, $all_types, ...$all_params);
    mysqli_stmt_execute($stmt);
    $res  = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($r = mysqli_fetch_assoc($res)) {
        $rows[] = sanitize_user_row($r);
    }

    echo json_encode([
        'ok'         => true,
        'data'       => $rows,
        'pagination' => [
            'page'        => $page,
            'limit'       => $limit,
            'total'       => $total_rows,
            'total_pages' => (int)ceil($total_rows / $limit),
        ],
    ]);
}

/* ════════════════════════════════════
   ROUTE: USER DETAIL
════════════════════════════════════ */
function route_detail(mysqli $db): void
{
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) { json_error('ID inválido'); return; }

    $has_verified = mysqli_num_rows(mysqli_query($db, "SHOW COLUMNS FROM users LIKE 'verified_at'")) > 0;
    $verified_sel = $has_verified ? "u.verified_at IS NOT NULL AS is_verified, u.verified_at," : "0 AS is_verified, NULL AS verified_at,";

    $stmt = mysqli_prepare($db, "
        SELECT u.*,
            $verified_sel
            (SELECT COUNT(*) FROM products p WHERE p.user_id = u.id) AS product_count,
            (SELECT COUNT(*) FROM products p WHERE p.user_id = u.id AND p.status = 'disponible') AS active_products
        FROM users u
        WHERE u.id = ? AND u.deleted_at IS NULL
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$row) { json_error('Usuario no encontrado'); return; }

    /* Recent products */
    $sp = mysqli_prepare($db, "
        SELECT p.id, p.title, p.price, p.status, p.created_at,
               (SELECT pi.image_url FROM product_images pi WHERE pi.product_id=p.id ORDER BY pi.id LIMIT 1) AS img
        FROM products p
        WHERE p.user_id = ?
        ORDER BY p.created_at DESC
        LIMIT 6
    ");
    mysqli_stmt_bind_param($sp, 'i', $id);
    mysqli_stmt_execute($sp);
    $prods = [];
    $rp = mysqli_stmt_get_result($sp);
    while ($p = mysqli_fetch_assoc($rp)) $prods[] = $p;

    $user = sanitize_user_row($row);
    unset($user['password']);
    $user['products'] = $prods;

    echo json_encode(['ok' => true, 'data' => $user]);
}

/* ════════════════════════════════════
   ROUTE: STATS
════════════════════════════════════ */
function route_stats(mysqli $db): void
{
    $res = mysqli_query($db, "
        SELECT
            COUNT(*) AS total,
            SUM(status='active')    AS active,
            SUM(status='blocked')   AS blocked,
            SUM(status='suspended') AS suspended,
            SUM(role='seller')      AS sellers,
            SUM(role='admin')       AS admins,
            SUM(role='user')        AS buyers
        FROM users
        WHERE deleted_at IS NULL
    ");
    $row = mysqli_fetch_assoc($res);
    echo json_encode(['ok' => true, 'data' => array_map('intval', $row)]);
}

/* ════════════════════════════════════
   ROUTE: SET STATUS
════════════════════════════════════ */
function route_set_status(mysqli $db, string $new_status): void
{
    $id = (int)(json_decode(file_get_contents('php://input'), true)['id'] ?? $_POST['id'] ?? 0);
    if ($id <= 0) { json_error('ID inválido'); return; }
    if ($id === (int)($_SESSION['user']['id'] ?? -1)) {
        json_error('No puedes modificar tu propia cuenta'); return;
    }

    $stmt = mysqli_prepare($db, "UPDATE users SET status=?, updated_at=NOW() WHERE id=? AND deleted_at IS NULL");
    mysqli_stmt_bind_param($stmt, 'si', $new_status, $id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        log_action($db, "Usuario #$id → estado: $new_status");
        echo json_encode(['ok' => true, 'message' => 'Estado actualizado', 'new_status' => $new_status]);
    } else {
        json_error('No se pudo actualizar el estado');
    }
}

/* ════════════════════════════════════
   ROUTE: DELETE (soft)
════════════════════════════════════ */
function route_delete(mysqli $db): void
{
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = (int)($body['id'] ?? $_POST['id'] ?? 0);
    if ($id <= 0) { json_error('ID inválido'); return; }
    if ($id === (int)($_SESSION['user']['id'] ?? -1)) {
        json_error('No puedes eliminar tu propia cuenta'); return;
    }

    $stmt = mysqli_prepare($db, "UPDATE users SET deleted_at=NOW(), status='blocked' WHERE id=? AND deleted_at IS NULL");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        log_action($db, "Usuario #$id eliminado (soft delete)");
        echo json_encode(['ok' => true, 'message' => 'Usuario eliminado']);
    } else {
        json_error('No se encontró el usuario');
    }
}

/* ════════════════════════════════════
   ROUTE: CHANGE ROLE
════════════════════════════════════ */
function route_change_role(mysqli $db): void
{
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = (int)($body['id'] ?? 0);
    $role = trim($body['role'] ?? '');
    $allowed = ['user','seller','admin'];

    if ($id <= 0)                       { json_error('ID inválido');   return; }
    if (!in_array($role, $allowed,true)) { json_error('Rol inválido'); return; }
    if ($id === (int)($_SESSION['user']['id'] ?? -1)) {
        json_error('No puedes cambiar tu propio rol'); return;
    }

    $stmt = mysqli_prepare($db, "UPDATE users SET role=?, updated_at=NOW() WHERE id=? AND deleted_at IS NULL");
    mysqli_stmt_bind_param($stmt, 'si', $role, $id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        log_action($db, "Usuario #$id → rol: $role");
        echo json_encode(['ok' => true, 'message' => 'Rol actualizado', 'new_role' => $role]);
    } else {
        json_error('No se pudo actualizar el rol');
    }
}

/* ════════════════════════════════════
   HELPERS
════════════════════════════════════ */
function sanitize_user_row(array $r): array
{
    unset($r['password']);
    $r['id']             = (int)$r['id'];
    $r['product_count']  = (int)($r['product_count'] ?? 0);
    $r['active_products']= (int)($r['active_products'] ?? 0);
    $r['is_verified']    = (bool)($r['is_verified'] ?? false);
    $r['status']         = $r['status']  ?? 'active';
    $r['role']           = $r['role']    ?? 'user';
    $r['initials']       = initials($r['full_name'] ?? '');
    return $r;
}

function initials(string $name): string
{
    if (empty($name)) return 'U';
    $parts = array_filter(explode(' ', trim($name)));
    $out   = '';
    foreach ($parts as $p) $out .= strtoupper($p[0]);
    return substr($out, 0, 2);
}

function log_action(mysqli $db, string $msg): void
{
    /* Silently fail if activity_log table doesn't exist */
    $admin_id = (int)($_SESSION['user']['id'] ?? 0);
    @mysqli_query($db, "INSERT INTO activity_log (admin_id, action, created_at) VALUES ($admin_id, '" . mysqli_real_escape_string($db, $msg) . "', NOW())");
}

function json_error(string $msg, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit();
}

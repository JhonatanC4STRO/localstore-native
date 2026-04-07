<?php
/**
 * Admin Admins API — ComercioLocal
 * Manages users with role = 'admin' | 'super_admin'.
 * Returns JSON for every request.
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/conexion.php';

/* ── Auth guard ── */
$sessionRole  = $_SESSION['user']['role'] ?? '';
$sessionId    = (int)($_SESSION['user']['id'] ?? -1);
$isSuperAdmin = ($sessionRole === 'super_admin');
$isAdmin      = ($sessionRole === 'admin' || $isSuperAdmin);

if (!isset($_SESSION['user']) || !$isAdmin) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso denegado']);
    exit();
}

/* ════════════════════════════════════
   SCHEMA BOOTSTRAP
════════════════════════════════════ */
function ensure_admin_schema(mysqli $db): void
{
    /* Add last_login column if missing */
    $cols = [];
    $r = mysqli_query($db, "SHOW COLUMNS FROM users");
    while ($c = mysqli_fetch_assoc($r)) $cols[] = strtolower($c['Field']);

    if (!in_array('last_login', $cols)) {
        mysqli_query($db, "ALTER TABLE users ADD COLUMN `last_login` TIMESTAMP DEFAULT NULL");
    }
    if (!in_array('deleted_at', $cols)) {
        mysqli_query($db, "ALTER TABLE users ADD COLUMN `deleted_at` TIMESTAMP DEFAULT NULL");
    }

    /* Widen role ENUM to include super_admin */
    $cr = mysqli_query($db, "SHOW COLUMNS FROM users LIKE 'role'");
    if ($cr && $row = mysqli_fetch_assoc($cr)) {
        if (strpos($row['Type'], 'super_admin') === false) {
            @mysqli_query($db, "ALTER TABLE users MODIFY COLUMN `role`
                ENUM('user','seller','admin','super_admin') NOT NULL DEFAULT 'user'");
        }
    }

    /* Widen status ENUM to include inactive */
    $cs = mysqli_query($db, "SHOW COLUMNS FROM users LIKE 'status'");
    if ($cs && $rowS = mysqli_fetch_assoc($cs)) {
        if (strpos($rowS['Type'], 'inactive') === false) {
            @mysqli_query($db, "ALTER TABLE users MODIFY COLUMN `status`
                ENUM('active','inactive','blocked','suspended') NOT NULL DEFAULT 'active'");
        }
    }
}

ensure_admin_schema($conn);

/* ════════════════════════════════════
   ROUTE DISPATCHER
════════════════════════════════════ */
$method = $_SERVER['REQUEST_METHOD'];
$body   = [];
if ($method === 'POST') {
    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true) ?? [];
}
$action = trim($_GET['action'] ?? ($body['action'] ?? ''));

switch ($method) {
    case 'GET':
        switch ($action) {
            case 'list':   route_list($conn);   break;
            case 'detail': route_detail($conn); break;
            case 'stats':  route_stats($conn);  break;
            default:       json_error('Acción GET no válida'); break;
        }
        break;

    case 'POST':
        switch ($action) {
            case 'create':     route_create($conn, $body);              break;
            case 'update':     route_update($conn, $body);              break;
            case 'activate':   route_set_status($conn, $body, 'active');   break;
            case 'deactivate': route_set_status($conn, $body, 'inactive'); break;
            default:           json_error('Acción POST no válida');         break;
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
}

/* ════════════════════════════════════
   GET — LIST
════════════════════════════════════ */
function route_list(mysqli $db): void
{
    $page      = max(1, (int)($_GET['page']   ?? 1));
    $limit     = min(100, max(5, (int)($_GET['limit'] ?? 20)));
    $offset    = ($page - 1) * $limit;
    $search    = trim($_GET['search']    ?? '');
    $role      = trim($_GET['role']      ?? '');
    $status    = trim($_GET['status']    ?? '');
    $date_from = trim($_GET['date_from'] ?? '');
    $date_to   = trim($_GET['date_to']   ?? '');
    $sort_col  = $_GET['sort'] ?? 'created_at';
    $sort_dir  = strtoupper($_GET['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

    $allowed_sorts = ['id','full_name','email','role','status','created_at','last_login'];
    if (!in_array($sort_col, $allowed_sorts, true)) $sort_col = 'created_at';

    $where  = ["u.role IN ('admin','super_admin')", "u.deleted_at IS NULL"];
    $params = [];
    $types  = '';

    if ($search !== '') {
        $like    = "%{$search}%";
        $where[] = "(u.full_name LIKE ? OR u.email LIKE ?)";
        $params  = array_merge($params, [$like, $like]);
        $types  .= 'ss';
    }
    if ($role !== '' && in_array($role, ['admin','super_admin'], true)) {
        $where[] = "u.role = ?"; $params[] = $role; $types .= 's';
    }
    $allowed_statuses = ['active','inactive','blocked'];
    if ($status !== '' && in_array($status, $allowed_statuses, true)) {
        $where[] = "u.status = ?"; $params[] = $status; $types .= 's';
    }
    if ($date_from !== '' && strtotime($date_from)) {
        $where[] = "DATE(u.created_at) >= ?"; $params[] = $date_from; $types .= 's';
    }
    if ($date_to !== '' && strtotime($date_to)) {
        $where[] = "DATE(u.created_at) <= ?"; $params[] = $date_to; $types .= 's';
    }

    $w = implode(' AND ', $where);

    /* Count */
    $sc = mysqli_prepare($db, "SELECT COUNT(*) AS total FROM users u WHERE $w");
    if (!empty($params)) mysqli_stmt_bind_param($sc, $types, ...$params);
    mysqli_stmt_execute($sc);
    $total = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($sc))['total'];

    /* Rows */
    $sql = "SELECT u.id, u.full_name, u.email, u.role, u.status,
                   u.created_at, u.last_login, u.profile_photo
            FROM users u
            WHERE $w
            ORDER BY u.`$sort_col` $sort_dir
            LIMIT ? OFFSET ?";

    $stmt = mysqli_prepare($db, $sql);
    $ap   = array_merge($params, [$limit, $offset]);
    $at   = $types . 'ii';
    mysqli_stmt_bind_param($stmt, $at, ...$ap);
    mysqli_stmt_execute($stmt);
    $res  = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($r = mysqli_fetch_assoc($res)) $rows[] = clean_row($r);

    echo json_encode([
        'ok'   => true,
        'data' => $rows,
        'pagination' => [
            'page'        => $page,
            'limit'       => $limit,
            'total'       => $total,
            'total_pages' => (int)ceil($total / max(1, $limit)),
        ],
    ]);
}

/* ════════════════════════════════════
   GET — DETAIL
════════════════════════════════════ */
function route_detail(mysqli $db): void
{
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) { json_error('ID inválido'); return; }

    $stmt = mysqli_prepare($db, "
        SELECT id, full_name, email, role, status,
               created_at, last_login, updated_at, profile_photo, phone, city, bio
        FROM users
        WHERE id = ? AND role IN ('admin','super_admin') AND deleted_at IS NULL
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$row) { json_error('Administrador no encontrado'); return; }

    /* Recent activity log (silently skip if table missing) */
    $activity = [];
    $chk = @mysqli_query($db, "SHOW TABLES LIKE 'activity_log'");
    if ($chk && mysqli_num_rows($chk) > 0) {
        $sa = mysqli_prepare($db,
            "SELECT action, created_at FROM activity_log WHERE admin_id = ? ORDER BY created_at DESC LIMIT 8");
        mysqli_stmt_bind_param($sa, 'i', $id);
        mysqli_stmt_execute($sa);
        $ra = mysqli_stmt_get_result($sa);
        while ($a = mysqli_fetch_assoc($ra)) $activity[] = $a;
    }

    $data             = clean_row($row);
    $data['activity'] = $activity;
    echo json_encode(['ok' => true, 'data' => $data]);
}

/* ════════════════════════════════════
   GET — STATS
════════════════════════════════════ */
function route_stats(mysqli $db): void
{
    $res = mysqli_query($db, "
        SELECT
            COUNT(*)                       AS total,
            SUM(role = 'admin')            AS admins,
            SUM(role = 'super_admin')      AS super_admins,
            SUM(status = 'active')         AS active,
            SUM(status = 'inactive')       AS inactive,
            SUM(status = 'blocked')        AS blocked
        FROM users
        WHERE role IN ('admin','super_admin') AND deleted_at IS NULL
    ");
    $row = mysqli_fetch_assoc($res);
    echo json_encode(['ok' => true, 'data' => array_map('intval', $row)]);
}

/* ════════════════════════════════════
   POST — CREATE  (super_admin only)
════════════════════════════════════ */
function route_create(mysqli $db, array $body): void
{
    global $isSuperAdmin;
    if (!$isSuperAdmin) { json_error('Solo un Super Admin puede crear administradores', 403); return; }

    $name     = trim($body['name']     ?? '');
    $email    = strtolower(trim($body['email']    ?? ''));
    $password = $body['password'] ?? '';
    $role     = trim($body['role']     ?? 'admin');

    $errors = [];
    if (mb_strlen($name) < 2)                              $errors[] = 'El nombre debe tener al menos 2 caracteres';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))         $errors[] = 'Correo electrónico inválido';
    if (strlen($password) < 8)                             $errors[] = 'La contraseña debe tener al menos 8 caracteres';
    if (!in_array($role, ['admin','super_admin'], true))    $errors[] = 'Rol inválido';
    if (!empty($errors)) { json_error(implode('. ', $errors)); return; }

    /* Duplicate email */
    $chk = mysqli_prepare($db, "SELECT id FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1");
    mysqli_stmt_bind_param($chk, 's', $email);
    mysqli_stmt_execute($chk);
    if (mysqli_fetch_assoc(mysqli_stmt_get_result($chk))) {
        json_error('Ya existe una cuenta con ese correo electrónico'); return;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $ins  = mysqli_prepare($db,
        "INSERT INTO users (full_name, email, password, role, status, created_at)
         VALUES (?, ?, ?, ?, 'active', NOW())");
    mysqli_stmt_bind_param($ins, 'ssss', $name, $email, $hash, $role);
    mysqli_stmt_execute($ins);

    if (mysqli_stmt_affected_rows($ins) > 0) {
        $new_id = mysqli_insert_id($db);
        log_action($db, "Admin creado: {$email} (#{$new_id}), rol: {$role}");
        echo json_encode(['ok' => true, 'message' => 'Administrador creado exitosamente', 'id' => $new_id]);
    } else {
        json_error('No se pudo crear el administrador');
    }
}

/* ════════════════════════════════════
   POST — UPDATE  (super_admin only)
════════════════════════════════════ */
function route_update(mysqli $db, array $body): void
{
    global $isSuperAdmin, $sessionId;
    if (!$isSuperAdmin) { json_error('Solo un Super Admin puede editar administradores', 403); return; }

    $id    = (int)($body['id']   ?? 0);
    $name  = trim($body['name']  ?? '');
    $email = strtolower(trim($body['email'] ?? ''));
    $role  = trim($body['role']  ?? '');
    $pass  = $body['password']   ?? '';

    if ($id <= 0)                                          { json_error('ID inválido'); return; }
    if (mb_strlen($name) < 2)                              { json_error('Nombre inválido (mínimo 2 caracteres)'); return; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))         { json_error('Correo electrónico inválido'); return; }
    if (!in_array($role, ['admin','super_admin'], true))    { json_error('Rol inválido'); return; }

    /* Self-demotion guard */
    if ($id === $sessionId && $role !== 'super_admin') {
        json_error('No puedes reducir tu propio rol'); return;
    }

    /* Duplicate email (other user) */
    $chk = mysqli_prepare($db, "SELECT id FROM users WHERE email=? AND id!=? AND deleted_at IS NULL LIMIT 1");
    mysqli_stmt_bind_param($chk, 'si', $email, $id);
    mysqli_stmt_execute($chk);
    if (mysqli_fetch_assoc(mysqli_stmt_get_result($chk))) {
        json_error('Ese correo ya está en uso'); return;
    }

    if ($pass !== '') {
        if (strlen($pass) < 8) { json_error('La contraseña debe tener al menos 8 caracteres'); return; }
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $upd  = mysqli_prepare($db,
            "UPDATE users SET full_name=?, email=?, password=?, role=?, updated_at=NOW()
             WHERE id=? AND deleted_at IS NULL");
        mysqli_stmt_bind_param($upd, 'ssssi', $name, $email, $hash, $role, $id);
    } else {
        $upd = mysqli_prepare($db,
            "UPDATE users SET full_name=?, email=?, role=?, updated_at=NOW()
             WHERE id=? AND deleted_at IS NULL");
        mysqli_stmt_bind_param($upd, 'sssi', $name, $email, $role, $id);
    }

    mysqli_stmt_execute($upd);
    log_action($db, "Admin #{$id} actualizado");
    echo json_encode(['ok' => true, 'message' => 'Administrador actualizado correctamente']);
}

/* ════════════════════════════════════
   POST — ACTIVATE / DEACTIVATE
════════════════════════════════════ */
function route_set_status(mysqli $db, array $body, string $new_status): void
{
    global $sessionId;
    $id = (int)($body['id'] ?? 0);
    if ($id <= 0)           { json_error('ID inválido'); return; }
    if ($id === $sessionId) { json_error('No puedes modificar tu propia cuenta'); return; }

    $stmt = mysqli_prepare($db,
        "UPDATE users SET status=?, updated_at=NOW()
         WHERE id=? AND role IN ('admin','super_admin') AND deleted_at IS NULL");
    mysqli_stmt_bind_param($stmt, 'si', $new_status, $id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        $label = $new_status === 'active' ? 'activado' : 'desactivado';
        log_action($db, "Admin #{$id} {$label}");
        echo json_encode(['ok' => true, 'message' => "Administrador {$label}", 'new_status' => $new_status]);
    } else {
        json_error('No se pudo actualizar el estado');
    }
}

/* ════════════════════════════════════
   HELPERS
════════════════════════════════════ */
function clean_row(array $r): array
{
    unset($r['password']);
    $r['id']       = (int)$r['id'];
    $r['status']   = $r['status'] ?? 'active';
    $r['role']     = $r['role']   ?? 'admin';
    $name          = $r['full_name'] ?? '';
    $parts         = array_filter(explode(' ', trim($name)));
    $initials      = '';
    foreach ($parts as $p) $initials .= strtoupper($p[0]);
    $r['initials'] = substr($initials ?: 'A', 0, 2);
    return $r;
}

function log_action(mysqli $db, string $msg): void
{
    $admin_id = (int)($_SESSION['user']['id'] ?? 0);
    @mysqli_query($db,
        "INSERT INTO activity_log (admin_id, action, created_at)
         VALUES ($admin_id, '" . mysqli_real_escape_string($db, $msg) . "', NOW())");
}

function json_error(string $msg, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit();
}

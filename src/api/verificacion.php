<?php
/**
 * Verification API — ComercioLocal
 * User: submit / cancel own request
 * Admin: list, approve, reject
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/conexion.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit();
}

$sessionId   = (int)($_SESSION['user']['id'] ?? 0);
$sessionRole = $_SESSION['user']['role'] ?? '';
$isAdmin     = in_array($sessionRole, ['admin', 'super_admin'], true);

/* ══════════════════════════════════════
   SCHEMA BOOTSTRAP
══════════════════════════════════════ */
function ensure_verif_schema(mysqli $db): void
{
    mysqli_query($db, "
        CREATE TABLE IF NOT EXISTS seller_verifications (
            id               INT AUTO_INCREMENT PRIMARY KEY,
            user_id          INT NOT NULL,
            store_name       VARCHAR(255) NOT NULL,
            category         VARCHAR(100) DEFAULT NULL,
            city             VARCHAR(100) DEFAULT NULL,
            phone            VARCHAR(30)  DEFAULT NULL,
            document_type    VARCHAR(100) DEFAULT NULL,
            document_path    VARCHAR(255) DEFAULT NULL,
            description      TEXT         DEFAULT NULL,
            status           ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            rejection_reason TEXT         DEFAULT NULL,
            reviewed_by      INT          DEFAULT NULL,
            reviewed_at      TIMESTAMP    DEFAULT NULL,
            created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            updated_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user   (user_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

ensure_verif_schema($conn);

/* ══════════════════════════════════════
   ROUTE DISPATCHER
══════════════════════════════════════ */
$method = $_SERVER['REQUEST_METHOD'];
$action = trim($_GET['action'] ?? '');
$body   = [];
if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $action ?: trim($body['action'] ?? '');
}

switch ($method) {
    case 'GET':
        switch ($action) {
            case 'list':      route_list($conn);      break;
            case 'stats':     route_stats($conn);     break;
            case 'my_status': route_my_status($conn); break;
            default: json_err('Acción no válida'); break;
        }
        break;

    case 'POST':
        switch ($action) {
            case 'approve': route_approve($conn, $body); break;
            case 'reject':  route_reject($conn, $body);  break;
            case 'cancel':  route_cancel($conn);         break;
            default: json_err('Acción no válida'); break;
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
}

/* ══════════════════════════════════════
   GET — LIST  (admin only)
══════════════════════════════════════ */
function route_list(mysqli $db): void
{
    global $isAdmin;
    if (!$isAdmin) { json_err('Acceso denegado', 403); return; }

    $status = trim($_GET['status'] ?? '');
    $search = trim($_GET['search'] ?? '');
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = min(50, max(5, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    $where  = ['1=1'];
    $params = [];
    $types  = '';

    $allowed = ['pending','approved','rejected'];
    if ($status !== '' && in_array($status, $allowed, true)) {
        $where[] = 'sv.status = ?'; $params[] = $status; $types .= 's';
    }
    if ($search !== '') {
        $like    = "%{$search}%";
        $where[] = '(sv.store_name LIKE ? OR u.email LIKE ? OR u.full_name LIKE ? OR sv.city LIKE ?)';
        $params  = array_merge($params, [$like, $like, $like, $like]);
        $types  .= 'ssss';
    }

    $w = implode(' AND ', $where);

    /* Count */
    $sc = mysqli_prepare($db, "SELECT COUNT(*) AS c FROM seller_verifications sv LEFT JOIN users u ON u.id=sv.user_id WHERE $w");
    if (!empty($params)) mysqli_stmt_bind_param($sc, $types, ...$params);
    mysqli_stmt_execute($sc);
    $total = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($sc))['c'];

    /* Rows */
    $stmt = mysqli_prepare($db, "
        SELECT sv.id, sv.user_id, sv.store_name, sv.category, sv.city, sv.phone,
               sv.document_type, sv.document_path, sv.description,
               sv.status, sv.rejection_reason,
               sv.reviewed_by, sv.reviewed_at, sv.created_at,
               u.full_name, u.email,
               ra.full_name AS reviewer_name
        FROM seller_verifications sv
        LEFT JOIN users u  ON u.id  = sv.user_id
        LEFT JOIN users ra ON ra.id = sv.reviewed_by
        WHERE $w
        ORDER BY FIELD(sv.status,'pending','rejected','approved'), sv.created_at ASC
        LIMIT ? OFFSET ?
    ");
    $ap = array_merge($params, [$limit, $offset]);
    $at = $types . 'ii';
    mysqli_stmt_bind_param($stmt, $at, ...$ap);
    mysqli_stmt_execute($stmt);

    $rows = [];
    $res  = mysqli_stmt_get_result($stmt);
    while ($r = mysqli_fetch_assoc($res)) {
        $r['id']          = (int)$r['id'];
        $r['user_id']     = (int)$r['user_id'];
        $r['reviewed_by'] = (int)$r['reviewed_by'];
        $rows[]           = $r;
    }

    echo json_encode([
        'ok'   => true,
        'data' => $rows,
        'pagination' => ['page' => $page, 'limit' => $limit, 'total' => $total,
                         'total_pages' => (int)ceil($total / max(1,$limit))],
    ]);
}

/* ══════════════════════════════════════
   GET — STATS  (admin)
══════════════════════════════════════ */
function route_stats(mysqli $db): void
{
    global $isAdmin;
    if (!$isAdmin) { json_err('Acceso denegado', 403); return; }

    $r = mysqli_query($db, "
        SELECT COUNT(*) AS total,
               SUM(status='pending')  AS pending,
               SUM(status='approved') AS approved,
               SUM(status='rejected') AS rejected
        FROM seller_verifications
    ");
    $row = mysqli_fetch_assoc($r);
    echo json_encode(['ok' => true, 'data' => array_map('intval', $row)]);
}

/* ══════════════════════════════════════
   GET — MY STATUS  (user)
══════════════════════════════════════ */
function route_my_status(mysqli $db): void
{
    global $sessionId;
    $stmt = mysqli_prepare($db, "
        SELECT id, store_name, category, city, status, rejection_reason, created_at
        FROM seller_verifications
        WHERE user_id = ?
        ORDER BY created_at DESC LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, 'i', $sessionId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    echo json_encode(['ok' => true, 'data' => $row ?: null]);
}

/* ══════════════════════════════════════
   POST — APPROVE  (admin)
══════════════════════════════════════ */
function route_approve(mysqli $db, array $body): void
{
    global $isAdmin, $sessionId;
    if (!$isAdmin) { json_err('Acceso denegado', 403); return; }

    $id = (int)($body['id'] ?? 0);
    if ($id <= 0) { json_err('ID inválido'); return; }

    $stmt = mysqli_prepare($db, "
        UPDATE seller_verifications
        SET status='approved', rejection_reason=NULL,
            reviewed_by=?, reviewed_at=NOW(), updated_at=NOW()
        WHERE id=? AND status='pending'
    ");
    mysqli_stmt_bind_param($stmt, 'ii', $sessionId, $id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        /* Promote user role to seller if currently 'user' */
        $sv = mysqli_prepare($db, "SELECT user_id FROM seller_verifications WHERE id=?");
        mysqli_stmt_bind_param($sv, 'i', $id);
        mysqli_stmt_execute($sv);
        $uid = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($sv))['user_id'] ?? 0);
        if ($uid > 0) {
            mysqli_query($db, "UPDATE users SET role='seller' WHERE id=$uid AND role='user'");
        }
        log_verif($db, "Verificación #{$id} aprobada");
        echo json_encode(['ok' => true, 'message' => 'Solicitud aprobada. El vendedor ha sido verificado.']);
    } else {
        json_err('No se pudo aprobar la solicitud (ya no está pendiente)');
    }
}

/* ══════════════════════════════════════
   POST — REJECT  (admin)
══════════════════════════════════════ */
function route_reject(mysqli $db, array $body): void
{
    global $isAdmin, $sessionId;
    if (!$isAdmin) { json_err('Acceso denegado', 403); return; }

    $id     = (int)($body['id'] ?? 0);
    $reason = trim($body['reason'] ?? '');

    if ($id <= 0)        { json_err('ID inválido'); return; }
    if (empty($reason))  { json_err('Debes indicar el motivo del rechazo'); return; }

    $stmt = mysqli_prepare($db, "
        UPDATE seller_verifications
        SET status='rejected', rejection_reason=?,
            reviewed_by=?, reviewed_at=NOW(), updated_at=NOW()
        WHERE id=? AND status='pending'
    ");
    mysqli_stmt_bind_param($stmt, 'sii', $reason, $sessionId, $id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        log_verif($db, "Verificación #{$id} rechazada: {$reason}");
        echo json_encode(['ok' => true, 'message' => 'Solicitud rechazada.']);
    } else {
        json_err('No se pudo rechazar la solicitud');
    }
}

/* ══════════════════════════════════════
   POST — CANCEL  (user: own request)
══════════════════════════════════════ */
function route_cancel(mysqli $db): void
{
    global $sessionId;
    $stmt = mysqli_prepare($db, "
        DELETE FROM seller_verifications
        WHERE user_id=? AND status='pending'
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, 'i', $sessionId);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        echo json_encode(['ok' => true, 'message' => 'Solicitud cancelada']);
    } else {
        json_err('No hay solicitud pendiente para cancelar');
    }
}

/* ══════════════════════════════════════
   HELPERS
══════════════════════════════════════ */
function log_verif(mysqli $db, string $msg): void
{
    $admin_id = (int)($_SESSION['user']['id'] ?? 0);
    @mysqli_query($db, "INSERT INTO activity_log (admin_id, action, created_at)
        VALUES ($admin_id, '" . mysqli_real_escape_string($db, $msg) . "', NOW())");
}

function json_err(string $msg, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit();
}

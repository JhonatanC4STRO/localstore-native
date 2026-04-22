<?php
/**
 * Admin Reports API — ComercioLocal
 * List, resolve and dismiss product/user reports.
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../config/role_sync.php';

$sessionRole = $_SESSION['user']['role'] ?? '';
if (!isset($_SESSION['user']) || !in_array($sessionRole, ['admin', 'super_admin'], true)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso denegado']);
    exit();
}

function tbl_exists(mysqli $db, string $t): bool
{
    $r = @mysqli_query($db, "SHOW TABLES LIKE '" . mysqli_real_escape_string($db, $t) . "'");
    return ($r && mysqli_num_rows($r) > 0);
}

function json_err(string $msg, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        if     ($action === 'list')  route_list($conn);
        elseif ($action === 'stats') route_stats($conn);
        else   json_err('Acción no válida');
        break;

    case 'POST':
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        if     ($action === 'resolve')        route_update($conn, $body, 'resolved');
        elseif ($action === 'dismiss')        route_update($conn, $body, 'dismissed');
        elseif ($action === 'delete_product') route_delete_product($conn, $body);
        else   json_err('Acción no válida');
        break;

    default:
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
}

/* ══════════════════════════════════════
   LIST  ?type=product|user&status=pending|resolved|dismissed|all
══════════════════════════════════════ */
function route_list(mysqli $db): void
{
    $type   = $_GET['type']   ?? 'product';
    $status = $_GET['status'] ?? 'pending';
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = min(50, max(5, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    if (!in_array($type, ['product', 'user'], true)) json_err('Tipo inválido');
    $allowed_status = ['pending', 'resolved', 'dismissed', 'all'];
    if (!in_array($status, $allowed_status, true))   json_err('Estado inválido');

    $table = $type === 'product' ? 'product_reports' : 'user_reports';
    if (!tbl_exists($db, $table)) {
        echo json_encode(['ok' => true, 'data' => [], 'pagination' => ['page' => 1, 'limit' => $limit, 'total' => 0, 'total_pages' => 0]]);
        return;
    }

    $where = [];
    $params = []; $types = '';
    if ($status !== 'all') {
        $where[] = 'r.status = ?';
        $params[] = $status; $types .= 's';
    }
    $w = empty($where) ? '1=1' : implode(' AND ', $where);

    /* Count */
    $sc = mysqli_prepare($db, "SELECT COUNT(*) AS c FROM `$table` r WHERE $w");
    if (!empty($params)) mysqli_stmt_bind_param($sc, $types, ...$params);
    mysqli_stmt_execute($sc);
    $total = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($sc))['c'];

    /* Rows */
    if ($type === 'product') {
        $sql = "
            SELECT r.id, r.product_id, r.reporter_id, r.reason, r.description,
                   r.evidence_path, r.status, r.admin_notes, r.reviewed_by, r.reviewed_at, r.created_at,
                   p.title AS target_title, p.user_id AS target_owner_id,
                   (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.id LIMIT 1) AS target_img,
                   ru.full_name AS reporter_name, ru.email AS reporter_email
            FROM product_reports r
            LEFT JOIN products p ON p.id  = r.product_id
            LEFT JOIN users  ru  ON ru.id = r.reporter_id
            WHERE $w
            ORDER BY FIELD(r.status,'pending','resolved','dismissed'), r.created_at DESC
            LIMIT ? OFFSET ?
        ";
    } else {
        $sql = "
            SELECT r.id, r.reported_user_id, r.reporter_id, r.reason, r.description,
                   r.evidence_path, r.status, r.admin_notes, r.reviewed_by, r.reviewed_at, r.created_at,
                   tu.full_name AS target_name, tu.email AS target_email, tu.profile_photo AS target_photo, tu.status AS target_status,
                   ru.full_name AS reporter_name, ru.email AS reporter_email
            FROM user_reports r
            LEFT JOIN users tu ON tu.id = r.reported_user_id
            LEFT JOIN users ru ON ru.id = r.reporter_id
            WHERE $w
            ORDER BY FIELD(r.status,'pending','resolved','dismissed'), r.created_at DESC
            LIMIT ? OFFSET ?
        ";
    }

    $stmt = mysqli_prepare($db, $sql);
    $ap = array_merge($params, [$limit, $offset]);
    $at = $types . 'ii';
    mysqli_stmt_bind_param($stmt, $at, ...$ap);
    mysqli_stmt_execute($stmt);

    $rows = [];
    $res  = mysqli_stmt_get_result($stmt);
    while ($r = mysqli_fetch_assoc($res)) {
        $r['id'] = (int)$r['id'];
        $rows[] = $r;
    }

    echo json_encode([
        'ok'         => true,
        'data'       => $rows,
        'pagination' => [
            'page'        => $page,
            'limit'       => $limit,
            'total'       => $total,
            'total_pages' => (int)ceil($total / max(1, $limit)),
        ],
    ]);
}

/* ══════════════════════════════════════
   STATS  — counts per type/status
══════════════════════════════════════ */
function route_stats(mysqli $db): void
{
    $out = [
        'product' => ['pending' => 0, 'resolved' => 0, 'dismissed' => 0, 'total' => 0],
        'user'    => ['pending' => 0, 'resolved' => 0, 'dismissed' => 0, 'total' => 0],
    ];

    foreach (['product' => 'product_reports', 'user' => 'user_reports'] as $key => $t) {
        if (!tbl_exists($db, $t)) continue;
        $r = mysqli_query($db, "
            SELECT COUNT(*) AS total,
                   SUM(status='pending')   AS pending,
                   SUM(status='resolved')  AS resolved,
                   SUM(status='dismissed') AS dismissed
            FROM `$t`
        ");
        if ($r) {
            $row = mysqli_fetch_assoc($r);
            $out[$key] = [
                'total'     => (int)$row['total'],
                'pending'   => (int)$row['pending'],
                'resolved'  => (int)$row['resolved'],
                'dismissed' => (int)$row['dismissed'],
            ];
        }
    }

    echo json_encode(['ok' => true, 'data' => $out]);
}

/* ══════════════════════════════════════
   UPDATE STATUS (resolve / dismiss)
══════════════════════════════════════ */
function route_update(mysqli $db, array $body, string $new_status): void
{
    $type  = $body['type']  ?? '';
    $id    = (int)($body['id'] ?? 0);
    $notes = trim($body['notes'] ?? '');
    $admin_id = (int)($_SESSION['user']['id'] ?? 0);

    if (!in_array($type, ['product', 'user'], true)) json_err('Tipo inválido');
    if ($id <= 0) json_err('ID inválido');

    $table = $type === 'product' ? 'product_reports' : 'user_reports';
    if (!tbl_exists($db, $table)) json_err('Tabla no encontrada', 404);

    $stmt = mysqli_prepare($db, "
        UPDATE `$table`
        SET status = ?, admin_notes = ?, reviewed_by = ?, reviewed_at = NOW()
        WHERE id = ? AND status = 'pending'
    ");
    mysqli_stmt_bind_param($stmt, 'ssii', $new_status, $notes, $admin_id, $id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        echo json_encode(['ok' => true, 'message' => $new_status === 'resolved' ? 'Reporte resuelto' : 'Reporte descartado']);
    } else {
        json_err('No se pudo actualizar (ya no está pendiente)');
    }
}

/* ══════════════════════════════════════
   DELETE PRODUCT (from a product report)
══════════════════════════════════════ */
function route_delete_product(mysqli $db, array $body): void
{
    $id       = (int)($body['id'] ?? 0);
    $notes    = trim($body['notes'] ?? '');
    $admin_id = (int)($_SESSION['user']['id'] ?? 0);

    if ($id <= 0) json_err('ID de reporte inválido');

    /* Obtener el reporte */
    $stR = mysqli_prepare($db, "SELECT product_id, status FROM product_reports WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stR, 'i', $id);
    mysqli_stmt_execute($stR);
    $rep = mysqli_fetch_assoc(mysqli_stmt_get_result($stR));
    if (!$rep) json_err('Reporte no encontrado', 404);
    if ($rep['status'] !== 'pending') json_err('El reporte ya fue procesado');

    $product_id = (int)$rep['product_id'];

    /* Obtener dueño del producto para sincronizar rol luego */
    $stP = mysqli_prepare($db, "SELECT user_id FROM products WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stP, 'i', $product_id);
    mysqli_stmt_execute($stP);
    $prod = mysqli_fetch_assoc(mysqli_stmt_get_result($stP));

    if ($prod) {
        $owner_id = (int)$prod['user_id'];

        /* Borrar imágenes físicas */
        $imgRes = mysqli_query($db, "SELECT image_url FROM product_images WHERE product_id = $product_id");
        if ($imgRes) {
            $imgDir = __DIR__ . '/../../../public/uploads/products/';
            while ($img = mysqli_fetch_assoc($imgRes)) {
                $path = $imgDir . $img['image_url'];
                if (!empty($img['image_url']) && file_exists($path)) @unlink($path);
            }
        }

        mysqli_query($db, "DELETE FROM product_images WHERE product_id = $product_id");
        mysqli_query($db, "DELETE FROM products WHERE id = $product_id");

        /* Si el dueño se queda sin productos, regresa a 'user' */
        if ($owner_id > 0) sync_user_role($db, $owner_id);
    }

    /* Marcar TODOS los reportes pendientes del mismo producto como resueltos */
    $finalNote = $notes !== '' ? $notes : 'Producto eliminado por el administrador';
    $upd = mysqli_prepare($db, "
        UPDATE product_reports
        SET status = 'resolved', admin_notes = ?, reviewed_by = ?, reviewed_at = NOW()
        WHERE product_id = ? AND status = 'pending'
    ");
    mysqli_stmt_bind_param($upd, 'sii', $finalNote, $admin_id, $product_id);
    mysqli_stmt_execute($upd);

    echo json_encode([
        'ok'      => true,
        'message' => $prod ? 'Producto eliminado y reporte resuelto' : 'El producto ya no existía; reporte cerrado',
    ]);
}

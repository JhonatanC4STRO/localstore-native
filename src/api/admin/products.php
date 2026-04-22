<?php
/**
 * Admin Products API — ComercioLocal
 * Handles all product management actions for the admin panel.
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/conexion.php';

/* ── Auth guard ── */
$sessionRole = $_SESSION['user']['role'] ?? '';
if (!isset($_SESSION['user']) || ($sessionRole !== 'admin' && $sessionRole !== 'super_admin')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso denegado']);
    exit();
}

/* ════════════════════════════════
   SCHEMA BOOTSTRAP
════════════════════════════════ */
function ensure_columns(mysqli $db): void
{
    $res = mysqli_query($db, "SHOW COLUMNS FROM products");
    $existing = [];
    while ($r = mysqli_fetch_assoc($res)) $existing[] = strtolower($r['Field']);

    $needed = [
        'admin_status' => "ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active'",
        'updated_at'   => "TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP",
    ];
    foreach ($needed as $col => $def) {
        if (!in_array($col, $existing, true)) {
            mysqli_query($db, "ALTER TABLE products ADD COLUMN `$col` $def");
        }
    }
}
ensure_columns($conn);

/* ════════════════════════════════
   ROUTER
════════════════════════════════ */
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        match ($action) {
            'list'       => route_list($conn),
            'detail'     => route_detail($conn),
            'stats'      => route_stats($conn),
            'categories' => route_categories($conn),
            default      => json_error('Acción no válida'),
        };
        break;
    case 'POST':
        $action = $action ?: (json_decode(file_get_contents('php://input'), true)['action'] ?? '');
        match ($action) {
            'set_status' => route_set_status($conn),
            default      => json_error('Acción no válida'),
        };
        break;
    default:
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
}

/* ════════════════════════════════
   LIST (paginated + filtered)
════════════════════════════════ */
function route_list(mysqli $db): void
{
    $page      = max(1, (int)($_GET['page']  ?? 1));
    $limit     = min(100, max(5, (int)($_GET['limit'] ?? 25)));
    $offset    = ($page - 1) * $limit;

    $search       = trim($_GET['search']       ?? '');
    $category_id  = (int)($_GET['category_id'] ?? 0);
    $admin_status = trim($_GET['admin_status'] ?? '');
    $city         = trim($_GET['city']         ?? '');
    $date_from    = trim($_GET['date_from']    ?? '');
    $date_to      = trim($_GET['date_to']      ?? '');

    $sort_map = [
        'id'           => 'p.id',
        'title'        => 'p.title',
        'price'        => 'p.price',
        'admin_status' => 'p.admin_status',
        'created_at'   => 'p.created_at',
        'seller'       => 'u.full_name',
        'category'     => 'c.name',
    ];
    $sort_col  = $_GET['sort'] ?? 'created_at';
    $sort_expr = $sort_map[$sort_col] ?? 'p.created_at';
    $sort_dir  = strtoupper($_GET['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

    $where  = ['1=1'];
    $params = [];
    $types  = '';

    if ($search !== '') {
        $like    = "%{$search}%";
        $where[] = '(p.title LIKE ? OR u.full_name LIKE ?)';
        $params  = array_merge($params, [$like, $like]);
        $types  .= 'ss';
    }
    if ($category_id > 0) {
        $where[]  = 'p.category_id = ?';
        $params[] = $category_id;
        $types   .= 'i';
    }
    if (in_array($admin_status, ['active','inactive','deleted'], true)) {
        $where[]  = 'p.admin_status = ?';
        $params[] = $admin_status;
        $types   .= 's';
    }
    if ($city !== '') {
        $like    = "%{$city}%";
        $where[] = 'u.city LIKE ?';
        $params[] = $like;
        $types  .= 's';
    }
    if ($date_from !== '' && strtotime($date_from)) {
        $where[]  = 'DATE(p.created_at) >= ?';
        $params[] = $date_from;
        $types   .= 's';
    }
    if ($date_to !== '' && strtotime($date_to)) {
        $where[]  = 'DATE(p.created_at) <= ?';
        $params[] = $date_to;
        $types   .= 's';
    }

    $where_sql = implode(' AND ', $where);
    $join      = 'LEFT JOIN users u ON p.user_id = u.id LEFT JOIN categories c ON p.category_id = c.id';

    /* Count */
    $sc = mysqli_prepare($db, "SELECT COUNT(*) AS t FROM products p $join WHERE $where_sql");
    if (!empty($params)) mysqli_stmt_bind_param($sc, $types, ...$params);
    mysqli_stmt_execute($sc);
    $total = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($sc))['t'];

    /* Rows */
    $sql = "
        SELECT
            p.id, p.title, p.price, p.status AS product_status,
            p.condition_type, p.admin_status, p.created_at, p.updated_at,
            p.user_id, p.category_id,
            u.full_name AS seller_name, u.city, u.email AS seller_email,
            c.name AS category_name,
            (SELECT pi.image_url FROM product_images pi
             WHERE pi.product_id = p.id ORDER BY pi.id ASC LIMIT 1) AS thumb
        FROM products p $join
        WHERE $where_sql
        ORDER BY $sort_expr $sort_dir
        LIMIT ? OFFSET ?
    ";
    $stmt = mysqli_prepare($db, $sql);
    $ap   = array_merge($params, [$limit, $offset]);
    $at   = $types . 'ii';
    mysqli_stmt_bind_param($stmt, $at, ...$ap);
    mysqli_stmt_execute($stmt);
    $rows = [];
    $res  = mysqli_stmt_get_result($stmt);
    while ($r = mysqli_fetch_assoc($res)) {
        $r['id']    = (int)$r['id'];
        $r['price'] = (float)$r['price'];
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

/* ════════════════════════════════
   DETAIL
════════════════════════════════ */
function route_detail(mysqli $db): void
{
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) { json_error('ID inválido'); return; }

    $stmt = mysqli_prepare($db, "
        SELECT p.*, p.status AS product_status,
               u.full_name AS seller_name, u.city, u.email AS seller_email, u.phone AS seller_phone,
               c.name AS category_name
        FROM products p
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ? LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$row) { json_error('Producto no encontrado'); return; }

    $si = mysqli_prepare($db, "SELECT id, image_url FROM product_images WHERE product_id = ? ORDER BY id ASC");
    mysqli_stmt_bind_param($si, 'i', $id);
    mysqli_stmt_execute($si);
    $imgs = [];
    $ri = mysqli_stmt_get_result($si);
    while ($img = mysqli_fetch_assoc($ri)) $imgs[] = $img;

    $row['id']     = (int)$row['id'];
    $row['price']  = (float)$row['price'];
    $row['images'] = $imgs;

    echo json_encode(['ok' => true, 'data' => $row]);
}

/* ════════════════════════════════
   STATS
════════════════════════════════ */
function route_stats(mysqli $db): void
{
    $row = mysqli_fetch_assoc(mysqli_query($db, "
        SELECT
            COUNT(*)                     AS total,
            SUM(admin_status='active')   AS active,
            SUM(admin_status='inactive') AS inactive,
            SUM(admin_status='deleted')  AS deleted,
            SUM(status='disponible')     AS disponible,
            SUM(status='vendido')        AS vendido
        FROM products
    "));

    $res = mysqli_query($db, "
        SELECT c.name, COUNT(*) AS cnt
        FROM products p LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.admin_status != 'deleted'
        GROUP BY p.category_id ORDER BY cnt DESC LIMIT 5
    ");
    $top = [];
    while ($r = mysqli_fetch_assoc($res)) $top[] = $r;

    echo json_encode(['ok' => true, 'data' => [
        'stats'          => array_map('intval', $row),
        'top_categories' => $top,
    ]]);
}

/* ════════════════════════════════
   CATEGORIES
════════════════════════════════ */
function route_categories(mysqli $db): void
{
    $res  = mysqli_query($db, "SELECT id, name FROM categories ORDER BY name ASC");
    $cats = [];
    while ($r = mysqli_fetch_assoc($res)) $cats[] = $r;
    echo json_encode(['ok' => true, 'data' => $cats]);
}

/* ════════════════════════════════
   SET STATUS
════════════════════════════════ */
function route_set_status(mysqli $db): void
{
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $id     = (int)($body['id']     ?? 0);
    $status = trim($body['status']  ?? '');

    if ($id <= 0)                                      { json_error('ID inválido');     return; }
    if (!in_array($status, ['active','inactive','deleted'], true)) { json_error('Estado inválido'); return; }

    $stmt = mysqli_prepare($db, "UPDATE products SET admin_status = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'si', $status, $id);
    mysqli_stmt_execute($stmt);

    log_action($db, "Producto #$id → admin_status: $status");
    echo json_encode(['ok' => true, 'new_status' => $status]);
}

/* ════════════════════════════════
   HELPERS
════════════════════════════════ */
function log_action(mysqli $db, string $msg): void
{
    $aid = (int)($_SESSION['user']['id'] ?? 0);
    @mysqli_query($db, "INSERT INTO activity_log (admin_id, action, created_at)
        VALUES ($aid, '" . mysqli_real_escape_string($db, $msg) . "', NOW())");
}

function json_error(string $msg, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit();
}





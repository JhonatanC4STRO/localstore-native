<?php
/**
 * API – Product Promotions
 *
 * GET  ?product_id=X  → return promotions for a product
 * POST (JSON)         → create a new promotion record
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../config/conexion.php';
session_start();

// ── Helpers ────────────────────────────────────────────────
function ok(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}
function err(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}
function auth(): int {
    if (!isset($_SESSION['user']['id'])) err('No autenticado', 401);
    return (int) $_SESSION['user']['id'];
}

// ── Plan config (server-side source of truth) ──────────────
function plan(string $type): array {
    $p = [
        'basic'       => ['name' => 'Plan Básico',      'price' => 3000.0,  'duration' => 3, 'priority' => 1],
        'recommended' => ['name' => 'Plan Recomendado', 'price' => 5000.0,  'duration' => 7, 'priority' => 2],
        'premium'     => ['name' => 'Plan Premium',     'price' => 10000.0, 'duration' => 7, 'priority' => 3,
                          'max_slots_per_category' => 5],
    ];
    return $p[$type] ?? [];
}

// Auto-expire
function autoExpire(mysqli $conn): void {
    mysqli_query($conn,
        "UPDATE product_promotions SET status='expired'
         WHERE status='active' AND end_date < NOW()");
}

autoExpire($conn);
$method = $_SERVER['REQUEST_METHOD'];

// ── GET ─────────────────────────────────────────────────────
if ($method === 'GET') {
    auth();
    $pid = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
    if ($pid < 1) err('product_id requerido');

    $st = mysqli_prepare($conn,
        "SELECT * FROM product_promotions WHERE product_id=? ORDER BY created_at DESC LIMIT 10");
    mysqli_stmt_bind_param($st, 'i', $pid);
    mysqli_stmt_execute($st);
    $rows = [];
    $res  = mysqli_stmt_get_result($st);
    while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
    ok(['promotions' => $rows]);
}

// ── POST ────────────────────────────────────────────────────
if ($method === 'POST') {
    $uid  = auth();
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) err('JSON inválido');

    $pid  = isset($body['product_id']) ? (int)$body['product_id'] : 0;
    $type = trim($body['plan_type'] ?? '');
    if ($pid < 1) err('product_id requerido');
    if (!in_array($type, ['basic','recommended','premium'])) err('plan_type inválido');

    // Ownership check
    $st = mysqli_prepare($conn,
        "SELECT id, category_id FROM products WHERE id=? AND user_id=?");
    mysqli_stmt_bind_param($st, 'ii', $pid, $uid);
    mysqli_stmt_execute($st);
    $product = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    if (!$product) err('Producto no encontrado o sin permisos', 403);

    // One active promo per product
    $st2 = mysqli_prepare($conn,
        "SELECT id FROM product_promotions WHERE product_id=? AND status='active'");
    mysqli_stmt_bind_param($st2, 'i', $pid);
    mysqli_stmt_execute($st2);
    if (mysqli_num_rows(mysqli_stmt_get_result($st2)) > 0)
        err('Este producto ya tiene una promoción activa', 409);

    // Premium slot limit
    if ($type === 'premium') {
        $cfg = plan('premium');
        $cat = (int)$product['category_id'];
        $st3 = mysqli_prepare($conn,
            "SELECT COUNT(*) as cnt FROM product_promotions pp
             JOIN products p ON p.id=pp.product_id
             WHERE pp.status='active' AND pp.plan_type='premium' AND p.category_id=?");
        mysqli_stmt_bind_param($st3, 'i', $cat);
        mysqli_stmt_execute($st3);
        $row3 = mysqli_fetch_assoc(mysqli_stmt_get_result($st3));
        if ((int)$row3['cnt'] >= $cfg['max_slots_per_category'])
            err('Cupos Premium agotados para esta categoría', 409);
    }

    $cfg   = plan($type);
    $start = date('Y-m-d H:i:s');
    $end   = date('Y-m-d H:i:s', strtotime("+{$cfg['duration']} days"));

    $st4 = mysqli_prepare($conn,
        "INSERT INTO product_promotions (product_id,plan_type,price,start_date,end_date,status)
         VALUES (?,?,?,?,?,'active')");
    mysqli_stmt_bind_param($st4, 'issss', $pid, $type, $cfg['price'], $start, $end);
    if (!mysqli_stmt_execute($st4)) err('Error DB: ' . mysqli_error($conn), 500);

    $promo_id = mysqli_insert_id($conn);

    // Update product priority
    $pri = $cfg['priority'];
    mysqli_query($conn, "UPDATE products SET promo_priority=$pri WHERE id=$pid");

    ok([
        'promotion_id'  => $promo_id,
        'plan_type'     => $type,
        'plan_name'     => $cfg['name'],
        'price'         => $cfg['price'],
        'duration_days' => $cfg['duration'],
        'start_date'    => $start,
        'end_date'      => $end,
        'status'        => 'active',
    ], 201);
}

err('Método no permitido', 405);

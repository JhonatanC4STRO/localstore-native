<?php
/**
 * API – Simulated Payment Gateway
 *
 * POST /api/payments_simulate.php
 * Body: { product_id, plan_type, method: 'nequi'|'card'|'cash' }
 *
 * Simulates a payment, then creates the promotion record on success.
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../config/conexion.php';
session_start();

function ok(array $d, int $c = 200): void {
    http_response_code($c);
    echo json_encode(['success' => true, 'data' => $d]);
    exit;
}
function err(string $m, int $c = 400): void {
    http_response_code($c);
    echo json_encode(['success' => false, 'message' => $m]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') err('Método no permitido', 405);
if (!isset($_SESSION['user']['id']))        err('No autenticado',      401);

$uid  = (int) $_SESSION['user']['id'];
$body = json_decode(file_get_contents('php://input'), true);
if (!$body) err('JSON inválido');

$pid    = isset($body['product_id']) ? (int)$body['product_id']  : 0;
$type   = trim($body['plan_type']   ?? '');
$method = trim($body['method']      ?? '');

if ($pid < 1)                                                   err('product_id requerido');
if (!in_array($type, ['basic','recommended','premium']))        err('plan_type inválido');
if (!in_array($method, ['nequi','card','cash']))                err('Método de pago inválido');

// Plan config
$plans = [
    'basic'       => ['name' => 'Plan Básico',      'price' => 3000.0,  'duration' => 3, 'priority' => 1],
    'recommended' => ['name' => 'Plan Recomendado', 'price' => 5000.0,  'duration' => 7, 'priority' => 2],
    'premium'     => ['name' => 'Plan Premium',     'price' => 10000.0, 'duration' => 7, 'priority' => 3,
                      'max_slots_per_category' => 5],
];
$plan = $plans[$type];

// Auto-expire old promos
mysqli_query($conn,
    "UPDATE product_promotions SET status='expired' WHERE status='active' AND end_date < NOW()");

// Verify ownership
$st = mysqli_prepare($conn,
    "SELECT id, category_id, title FROM products WHERE id=? AND user_id=?");
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

// Premium slots
if ($type === 'premium') {
    $cat = (int)$product['category_id'];
    $st3 = mysqli_prepare($conn,
        "SELECT COUNT(*) as cnt FROM product_promotions pp
         JOIN products p ON p.id=pp.product_id
         WHERE pp.status='active' AND pp.plan_type='premium' AND p.category_id=?");
    mysqli_stmt_bind_param($st3, 'i', $cat);
    mysqli_stmt_execute($st3);
    $r3 = mysqli_fetch_assoc(mysqli_stmt_get_result($st3));
    if ((int)$r3['cnt'] >= $plan['max_slots_per_category'])
        err('Cupos Premium agotados para esta categoría. Elige otro plan.', 409);
}

// ── Simulate payment processing ───────────────────────────
// Simulate 1-2s processing delay (client handles the visual)
// 95% success rate – set to always true for demo
$payment_ok = true;

if (!$payment_ok) {
    err('El pago fue rechazado. Verifica tus datos e intenta de nuevo.', 402);
}

// Generate transaction ID
$txn_id = 'TXN-' . strtoupper(date('Ymd')) . '-'
        . strtoupper(substr(md5(uniqid('', true)), 0, 8));

// Dates
$start = date('Y-m-d H:i:s');
$end   = date('Y-m-d H:i:s', strtotime("+{$plan['duration']} days"));

// Create promotion record
$st4 = mysqli_prepare($conn,
    "INSERT INTO product_promotions (product_id,plan_type,price,start_date,end_date,status)
     VALUES (?,?,?,?,?,'active')");
mysqli_stmt_bind_param($st4, 'issss', $pid, $type, $plan['price'], $start, $end);
if (!mysqli_stmt_execute($st4))
    err('Error al registrar la promoción: ' . mysqli_error($conn), 500);

$promo_id = mysqli_insert_id($conn);

// Update product priority for ordering
$pri = $plan['priority'];
mysqli_query($conn, "UPDATE products SET promo_priority=$pri WHERE id=$pid");

ok([
    'transaction_id' => $txn_id,
    'promotion_id'   => $promo_id,
    'product_id'     => $pid,
    'product_title'  => $product['title'],
    'plan_type'      => $type,
    'plan_name'      => $plan['name'],
    'price'          => $plan['price'],
    'payment_method' => $method,
    'duration_days'  => $plan['duration'],
    'start_date'     => $start,
    'end_date'       => $end,
    'status'         => 'active',
    'paid_at'        => date('Y-m-d H:i:s'),
], 201);





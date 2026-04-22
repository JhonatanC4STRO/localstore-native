<?php
/**
 * mark_sold.php — marca un producto como vendido.
 * Solo el dueño del producto puede hacerlo.
 */

session_start();
include(__DIR__ . '/../../config/conexion.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

$user_id    = (int) $_SESSION['user']['id'];
$product_id = isset($input['product_id']) ? (int) $input['product_id'] : 0;
$action     = isset($input['action']) ? strtolower(trim($input['action'])) : 'mark';
if (!in_array($action, ['mark', 'undo'], true)) $action = 'mark';

if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'ID de producto inválido']);
    exit();
}

/* Verificar que el usuario es el dueño */
$stmt = mysqli_prepare($conn, "SELECT user_id, status FROM products WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $product_id);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$row) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Producto no encontrado']);
    exit();
}

if ((int) $row['user_id'] !== $user_id) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'No eres el dueño de este producto']);
    exit();
}

$newStatus = ($action === 'undo') ? 'disponible' : 'vendido';

if ($row['status'] === $newStatus) {
    echo json_encode(['ok' => true, 'already' => true, 'status' => $newStatus]);
    exit();
}

$upd = mysqli_prepare($conn, "UPDATE products SET status = ? WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($upd, 'sii', $newStatus, $product_id, $user_id);

if (!mysqli_stmt_execute($upd)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo actualizar el producto']);
    exit();
}

echo json_encode(['ok' => true, 'status' => $newStatus]);
exit();

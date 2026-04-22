<?php
/* ══════════════════════════════════════
   toggle.php
   Agrega o quita un producto de favoritos.
   POST { product_id: int }
   Responde { ok: true, favorited: bool }
══════════════════════════════════════ */

session_start();
include(__DIR__ . '/../../config/conexion.php');

header('Content-Type: application/json; charset=utf-8');

// 1. Auth
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit();
}

// 2. Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

// 3. Input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

$user_id    = (int) $_SESSION['user']['id'];
$product_id = (int) ($input['product_id'] ?? 0);

if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'product_id inválido']);
    exit();
}

// Asegurar que la tabla existe
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `favorites` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_product` (`user_id`, `product_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// 4. Verificar si ya existe
$stmt = mysqli_prepare($conn, "SELECT id FROM favorites WHERE user_id = ? AND product_id = ?");
mysqli_stmt_bind_param($stmt, 'ii', $user_id, $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    // Ya existe -> quitar de favoritos
    $del = mysqli_prepare($conn, "DELETE FROM favorites WHERE user_id = ? AND product_id = ?");
    mysqli_stmt_bind_param($del, 'ii', $user_id, $product_id);
    mysqli_stmt_execute($del);
    echo json_encode(['ok' => true, 'favorited' => false]);
} else {
    // No existe -> agregar a favoritos
    $ins = mysqli_prepare($conn, "INSERT INTO favorites (user_id, product_id) VALUES (?, ?)");
    mysqli_stmt_bind_param($ins, 'ii', $user_id, $product_id);
    mysqli_stmt_execute($ins);
    echo json_encode(['ok' => true, 'favorited' => true]);
}

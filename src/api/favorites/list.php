<?php
/* ══════════════════════════════════════
   list.php
   Devuelve los IDs de productos favoritos
   del usuario actual.
   GET -> { ok: true, product_ids: [1,2,3] }
══════════════════════════════════════ */

session_start();
include(__DIR__ . '/../../config/conexion.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit();
}

$user_id = (int) $_SESSION['user']['id'];

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

$stmt = mysqli_prepare($conn, "SELECT product_id FROM favorites WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$ids = [];
while ($row = mysqli_fetch_assoc($result)) {
    $ids[] = (int) $row['product_id'];
}

echo json_encode(['ok' => true, 'product_ids' => $ids]);

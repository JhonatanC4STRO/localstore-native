<?php
/* ═══��══════════════════════════════════
   get_unread_count.php
   Devuelve el total de mensajes no leídos
   para el usuario actual.
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

$sql = "SELECT COUNT(*) AS total
        FROM messages m
        JOIN conversations c ON m.conversation_id = c.id
        WHERE (c.buyer_id = $user_id OR c.seller_id = $user_id)
          AND m.sender_id != $user_id
          AND m.is_read = 0
          AND NOT (c.buyer_id  = $user_id AND c.hidden_by_buyer  = 1)
          AND NOT (c.seller_id = $user_id AND c.hidden_by_seller = 1)";

$res = mysqli_query($conn, $sql);
$total = 0;
if ($res) {
    $total = (int) mysqli_fetch_assoc($res)['total'];
}

echo json_encode(['unread' => $total]);
exit();

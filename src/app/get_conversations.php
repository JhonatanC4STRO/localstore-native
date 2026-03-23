<?php
/* ══════════════════════════════════════
   get_conversations.php
   Devuelve la lista de conversaciones
   visibles para el usuario actual.
   Usado por el polling del sidebar para
   detectar chats nuevos o reactivados.
══════════════════════════════════════ */

session_start();
include(__DIR__ . '/../config/conexion.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit();
}

$user_id = (int) $_SESSION['user']['id'];

$sql = "SELECT
            c.id,
            c.buyer_id,
            c.seller_id,
            p.title  AS product_name,
            p.price  AS product_price,
            p.id     AS product_id,
            u.full_name AS other_user,
            u.id        AS other_user_id,
            (SELECT m2.message
             FROM messages m2
             WHERE m2.conversation_id = c.id
             ORDER BY m2.id DESC LIMIT 1) AS last_message,
            (SELECT m3.created_at
             FROM messages m3
             WHERE m3.conversation_id = c.id
             ORDER BY m3.id DESC LIMIT 1) AS last_message_at,
            (SELECT COUNT(*)
             FROM messages m4
             WHERE m4.conversation_id = c.id
               AND m4.sender_id != $user_id
               AND m4.is_read = 0) AS unread_count
        FROM conversations c
        JOIN products p ON c.product_id = p.id
        JOIN users u ON (
            (c.buyer_id  = $user_id AND u.id = c.seller_id)
            OR
            (c.seller_id = $user_id AND u.id = c.buyer_id)
        )
        WHERE (c.buyer_id = $user_id OR c.seller_id = $user_id)
          AND NOT (c.buyer_id  = $user_id AND c.hidden_by_buyer  = 1)
          AND NOT (c.seller_id = $user_id AND c.hidden_by_seller = 1)
        ORDER BY last_message_at DESC, c.id DESC";

$res  = mysqli_query($conn, $sql);
$data = [];

if ($res && mysqli_num_rows($res) > 0) {
    while ($row = mysqli_fetch_assoc($res)) {
        $data[] = [
            'id'              => (int) $row['id'],
            'other_user'      => $row['other_user'],
            'other_user_id'   => (int) $row['other_user_id'],
            'product_name'    => $row['product_name'],
            'product_price'   => $row['product_price'],
            'product_id'      => (int) $row['product_id'],
            'last_message'    => $row['last_message']    ?? '',
            'last_message_at' => $row['last_message_at'] ?? '',
            'unread_count'    => (int) ($row['unread_count'] ?? 0),
            'initial'         => strtoupper(mb_substr($row['other_user'], 0, 1)),
            'time_label'      => $row['last_message_at']
                ? date('H:i', strtotime($row['last_message_at']))
                : '',
        ];
    }
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
exit();

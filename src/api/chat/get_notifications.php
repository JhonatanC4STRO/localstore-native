<?php
/* ══════════════════════════════════════
   get_notifications.php
   Devuelve los mensajes no leídos más
   recientes como notificaciones.
══════════════���═══════════════════════ */

session_start();
include(__DIR__ . '/../../config/conexion.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit();
}

$user_id = (int) $_SESSION['user']['id'];
$limit   = isset($_GET['limit']) && is_numeric($_GET['limit']) ? min((int)$_GET['limit'], 20) : 10;

// Obtener el último mensaje no leído de cada conversación
$sql = "SELECT
            m.id AS message_id,
            m.message,
            m.created_at,
            m.conversation_id,
            u.full_name AS sender_name,
            u.id AS sender_id,
            u.profile_photo AS sender_photo,
            p.title AS product_name,
            sub.unread_count
        FROM messages m
        JOIN conversations c ON m.conversation_id = c.id
        JOIN users u ON m.sender_id = u.id
        LEFT JOIN products p ON c.product_id = p.id
        JOIN (
            SELECT m2.conversation_id,
                   MAX(m2.id) AS last_msg_id,
                   COUNT(*) AS unread_count
            FROM messages m2
            JOIN conversations c2 ON m2.conversation_id = c2.id
            WHERE (c2.buyer_id = $user_id OR c2.seller_id = $user_id)
              AND m2.sender_id != $user_id
              AND m2.is_read = 0
              AND NOT (c2.buyer_id  = $user_id AND c2.hidden_by_buyer  = 1)
              AND NOT (c2.seller_id = $user_id AND c2.hidden_by_seller = 1)
            GROUP BY m2.conversation_id
        ) sub ON m.id = sub.last_msg_id
        ORDER BY m.created_at DESC
        LIMIT $limit";

$res  = mysqli_query($conn, $sql);
$data = [];

if ($res && mysqli_num_rows($res) > 0) {
    while ($row = mysqli_fetch_assoc($res)) {
        $diff = time() - strtotime($row['created_at']);
        if ($diff < 60)       $ago = 'Ahora';
        elseif ($diff < 3600) $ago = floor($diff / 60) . ' min';
        elseif ($diff < 86400) $ago = floor($diff / 3600) . ' h';
        else                   $ago = floor($diff / 86400) . ' d';

        $data[] = [
            'message_id'      => (int) $row['message_id'],
            'conversation_id' => (int) $row['conversation_id'],
            'sender_id'       => (int) $row['sender_id'],
            'sender_name'     => $row['sender_name'],
            'sender_photo'    => $row['sender_photo'] ?? '',
            'sender_initial'  => strtoupper(mb_substr($row['sender_name'], 0, 1)),
            'message'         => mb_strlen($row['message']) > 80
                                    ? mb_substr($row['message'], 0, 80) . '…'
                                    : $row['message'],
            'product_name'    => $row['product_name'] ?? '',
            'unread_count'    => (int) $row['unread_count'],
            'time_ago'        => $ago,
            'created_at'      => $row['created_at'],
        ];
    }
}

// Total global de no leídos
$sql_total = "SELECT COUNT(*) AS total
              FROM messages m
              JOIN conversations c ON m.conversation_id = c.id
              WHERE (c.buyer_id = $user_id OR c.seller_id = $user_id)
                AND m.sender_id != $user_id
                AND m.is_read = 0
                AND NOT (c.buyer_id  = $user_id AND c.hidden_by_buyer  = 1)
                AND NOT (c.seller_id = $user_id AND c.hidden_by_seller = 1)";
$res_total = mysqli_query($conn, $sql_total);
$total_unread = $res_total ? (int) mysqli_fetch_assoc($res_total)['total'] : 0;

echo json_encode([
    'total_unread'  => $total_unread,
    'notifications' => $data,
], JSON_UNESCAPED_UNICODE);
exit();

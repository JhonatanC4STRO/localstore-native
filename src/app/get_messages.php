<?php
/* ══════════════════════════════════════
   get_messages.php
   Devuelve mensajes de una conversación.
   Respeta el soft-delete por usuario:
   si este usuario eliminó el chat,
   no ve los mensajes anteriores a ese momento.
══════════════════════════════════════ */

session_start();
include(__DIR__ . '/../config/conexion.php');

header('Content-Type: application/json; charset=utf-8');

// ── 1. Autenticación ──────────────────
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit();
}

// ── 2. Validar conversation_id ────────
if (!isset($_GET['conversation_id']) || !is_numeric($_GET['conversation_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de conversación inválido']);
    exit();
}

$user_id         = (int) $_SESSION['user']['id'];
$conversation_id = (int) $_GET['conversation_id'];

// ── 3. Verificar pertenencia + leer flags de soft-delete ──
$sql_auth = "SELECT id, buyer_id, seller_id,
                    hidden_by_buyer, hidden_by_seller
             FROM conversations
             WHERE id = $conversation_id
               AND (buyer_id = $user_id OR seller_id = $user_id)
             LIMIT 1";

$res_auth = mysqli_query($conn, $sql_auth);

if (!$res_auth || mysqli_num_rows($res_auth) === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit();
}

$conv = mysqli_fetch_assoc($res_auth);

// ── 4. Si este usuario eliminó el chat → lista vacía ──
// (el chat no debería aparecer en la sidebar, pero por seguridad)
$isBuyer = ((int) $conv['buyer_id'] === $user_id);
if ($isBuyer && $conv['hidden_by_buyer']) {
    echo json_encode([]);
    exit();
}
if (!$isBuyer && $conv['hidden_by_seller']) {
    echo json_encode([]);
    exit();
}

// ── 5. Paginación incremental opcional ──
$after_id  = isset($_GET['after_id']) && is_numeric($_GET['after_id'])
             ? (int) $_GET['after_id'] : 0;
$limit     = 50;
$extra_sql = $after_id > 0 ? "AND m.id > $after_id" : "";

// ── 6. Obtener mensajes ───────────────
$sql = "SELECT
            m.id,
            m.conversation_id,
            m.sender_id,
            m.message,
            m.created_at,
            u.full_name AS sender_name
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.conversation_id = $conversation_id
          $extra_sql
        ORDER BY m.created_at ASC
        LIMIT $limit";

$res  = mysqli_query($conn, $sql);
$data = [];

if ($res && mysqli_num_rows($res) > 0) {
    while ($row = mysqli_fetch_assoc($res)) {
        $data[] = [
            'id'              => (int)  $row['id'],
            'conversation_id' => (int)  $row['conversation_id'],
            'sender_id'       => (int)  $row['sender_id'],
            'sender_name'     => $row['sender_name'],
            'message'         => $row['message'],
            'created_at'      => $row['created_at'],
            'is_me'           => ((int) $row['sender_id'] === $user_id),
            'time_label'      => date('H:i', strtotime($row['created_at'])),
        ];
    }
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
exit();
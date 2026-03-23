<?php
/* ══════════════════════════════════════
   eliminar_chat.php
   Soft-delete: marca la conversación como
   eliminada SOLO para el usuario actual.
   El otro participante sigue viéndola.
   (igual que WhatsApp)
══════════════════════════════════════ */

session_start();
include(__DIR__ . '/../config/conexion.php');

header('Content-Type: application/json; charset=utf-8');

// ── 1. Autenticación ──────────────────
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit();
}

// ── 2. Solo POST ──────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit();
}

// ── 3. Leer body ──────────────────────
$input           = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

$user_id         = (int) $_SESSION['user']['id'];
$conversation_id = isset($input['conversation_id']) ? (int) $input['conversation_id'] : 0;

if ($conversation_id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'ID de conversación inválido']);
    exit();
}

// ── 4. Verificar pertenencia y rol ────
$sql_auth = "SELECT id, buyer_id, seller_id
             FROM conversations
             WHERE id = $conversation_id
               AND (buyer_id = $user_id OR seller_id = $user_id)
             LIMIT 1";

$res_auth = mysqli_query($conn, $sql_auth);

if (!$res_auth || mysqli_num_rows($res_auth) === 0) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'No tienes permiso para eliminar este chat']);
    exit();
}

$conv = mysqli_fetch_assoc($res_auth);

// ── 5. Determinar qué columna marcar ──
if ((int) $conv['buyer_id'] === $user_id) {
    $campo = 'deleted_by_buyer';
} else {
    $campo = 'deleted_by_seller';
}

// ── 6. Soft-delete solo para este usuario ──
$ok = mysqli_query($conn, "UPDATE conversations SET $campo = 1 WHERE id = $conversation_id");

if (!$ok) {
    error_log("Error soft-delete: " . mysqli_error($conn));
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error al eliminar la conversación']);
    exit();
}

// ── 7. Si AMBOS la eliminaron → borrar definitivamente ──
$res_both = mysqli_query($conn, "SELECT id FROM conversations
                                  WHERE id = $conversation_id
                                    AND deleted_by_buyer  = 1
                                    AND deleted_by_seller = 1 LIMIT 1");

if ($res_both && mysqli_num_rows($res_both) > 0) {
    mysqli_query($conn, "DELETE FROM messages      WHERE conversation_id = $conversation_id");
    mysqli_query($conn, "DELETE FROM conversations WHERE id = $conversation_id");
}

echo json_encode([
    'ok'              => true,
    'conversation_id' => $conversation_id,
    'message'         => 'Conversación eliminada de tu lista',
]);
exit();
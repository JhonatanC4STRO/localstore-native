<?php
/* ══════════════════════════════════════
   eliminar_chat.php
   Soft-delete: marca la conversación como
   eliminada SOLO para el usuario actual.
   El otro participante sigue viéndola.
   (igual que WhatsApp)
══════════════════════════════════════ */

session_start();
require_once __DIR__ . '/../../config/conexion.php';

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

// ── 4. Verificar pertenencia y rol usando consulta preparada ────
$stmt_auth = mysqli_prepare($conn, "
    SELECT id, buyer_id, seller_id
    FROM conversations
    WHERE id = ?
      AND (buyer_id = ? OR seller_id = ?)
    LIMIT 1
");

if (!$stmt_auth) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error de servidor al validar acceso']);
    exit();
}

mysqli_stmt_bind_param($stmt_auth, "iii", $conversation_id, $user_id, $user_id);
mysqli_stmt_execute($stmt_auth);
$res_auth = mysqli_stmt_get_result($stmt_auth);

if (!$res_auth || mysqli_num_rows($res_auth) === 0) {
    mysqli_stmt_close($stmt_auth);
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'No tienes permiso para eliminar este chat']);
    exit();
}

$conv = mysqli_fetch_assoc($res_auth);
mysqli_stmt_close($stmt_auth);

// ── 5. Determinar qué columna marcar ──
if ((int) $conv['buyer_id'] === $user_id) {
    $campo = 'hidden_by_buyer';
} else {
    $campo = 'hidden_by_seller';
}

// ── 6. Soft-delete solo para este usuario usando consulta preparada ──
// Nota: $campo es seguro al ser validado por el servidor, pero parametrizamos el ID.
$stmt_upd = mysqli_prepare($conn, "UPDATE conversations SET $campo = 1 WHERE id = ?");
$ok = false;
if ($stmt_upd) {
    mysqli_stmt_bind_param($stmt_upd, "i", $conversation_id);
    $ok = mysqli_stmt_execute($stmt_upd);
    mysqli_stmt_close($stmt_upd);
}

if (!$ok) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error al eliminar la conversación']);
    exit();
}

// ── 7. Si AMBOS la eliminaron → borrar definitivamente usando consultas preparadas ──
$stmt_both = mysqli_prepare($conn, "
    SELECT id FROM conversations
    WHERE id = ?
      AND hidden_by_buyer  = 1
      AND hidden_by_seller = 1
    LIMIT 1
");

if ($stmt_both) {
    mysqli_stmt_bind_param($stmt_both, "i", $conversation_id);
    mysqli_stmt_execute($stmt_both);
    $res_both = mysqli_stmt_get_result($stmt_both);
    
    if ($res_both && mysqli_num_rows($res_both) > 0) {
        $stmt_del_msg = mysqli_prepare($conn, "DELETE FROM messages WHERE conversation_id = ?");
        if ($stmt_del_msg) {
            mysqli_stmt_bind_param($stmt_del_msg, "i", $conversation_id);
            mysqli_stmt_execute($stmt_del_msg);
            mysqli_stmt_close($stmt_del_msg);
        }
        
        $stmt_del_conv = mysqli_prepare($conn, "DELETE FROM conversations WHERE id = ?");
        if ($stmt_del_conv) {
            mysqli_stmt_bind_param($stmt_del_conv, "i", $conversation_id);
            mysqli_stmt_execute($stmt_del_conv);
            mysqli_stmt_close($stmt_del_conv);
        }
    }
    mysqli_stmt_close($stmt_both);
}

echo json_encode([
    'ok'              => true,
    'conversation_id' => $conversation_id,
    'message'         => 'Conversación eliminada de tu lista',
]);
exit();




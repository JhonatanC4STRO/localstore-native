<?php
/* ══════════════════════════════════════
   save_message.php
   Guarda un mensaje enviado desde el chat
   (llamado por el servidor WebSocket o
   directamente por fetch como fallback).
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

// ── 2. Solo POST ──────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

// ── 3. Leer body JSON o POST normal ──
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

$sender_id       = (int) $_SESSION['user']['id'];
$conversation_id = isset($input['conversation_id']) ? (int) $input['conversation_id'] : 0;
$message         = isset($input['message'])         ? trim($input['message'])          : '';

// ── 4. Validaciones ───────────────────
if ($conversation_id <= 0 || $message === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos']);
    exit();
}

if (mb_strlen($message) > 2000) {
    http_response_code(400);
    echo json_encode(['error' => 'Mensaje demasiado largo (máx. 2000 caracteres)']);
    exit();
}

// ── 5. Verificar que el usuario pertenece a la conversación ──
$sql_auth = "SELECT id FROM conversations
             WHERE id = $conversation_id
               AND (buyer_id = $sender_id OR seller_id = $sender_id)
             LIMIT 1";
$res_auth = mysqli_query($conn, $sql_auth);

if (!$res_auth || mysqli_num_rows($res_auth) === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit();
}

// ── 6. Guardar mensaje ────────────────
$msg_escaped = mysqli_real_escape_string($conn, $message);
$sql_insert  = "INSERT INTO messages (conversation_id, sender_id, message, created_at)
                VALUES ($conversation_id, $sender_id, '$msg_escaped', NOW())";

$ok = mysqli_query($conn, $sql_insert);

if (!$ok) {
    error_log("Error al guardar mensaje: " . mysqli_error($conn));
    http_response_code(500);
    echo json_encode(['error' => 'Error al guardar el mensaje']);
    exit();
}

$message_id = (int) mysqli_insert_id($conn);

// ── 7. Responder con el mensaje guardado ──
echo json_encode([
    'ok'              => true,
    'id'              => $message_id,
    'conversation_id' => $conversation_id,
    'sender_id'       => $sender_id,
    'message'         => $message,
    'created_at'      => date('Y-m-d H:i:s'),
    'time_label'      => date('H:i'),
    'is_me'           => true,
]);
exit();
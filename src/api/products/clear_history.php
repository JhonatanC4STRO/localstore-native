<?php
/* ══════════════════════════════════════════════════════════════
   clear_history.php
   POST → borra todo el historial de búsquedas del usuario logueado.
══════════════════════════════════════════════════════════════ */

include(__DIR__ . '/../../config/conexion.php');
session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$uid = (int)$_SESSION['user']['id'];
$ok  = mysqli_query($conn, "DELETE FROM search_history WHERE user_id = $uid");

echo json_encode(['success' => (bool)$ok]);

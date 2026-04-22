<?php
/**
 * Reports API — ComercioLocal
 * Users submit reports against products or other users. Evidence upload supported.
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/conexion.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Debes iniciar sesión para reportar']);
    exit();
}

$reporterId = (int)($_SESSION['user']['id'] ?? 0);
if ($reporterId <= 0) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Sesión inválida']);
    exit();
}

/* ══════════════════════════════════════
   SCHEMA BOOTSTRAP
══════════════════════════════════════ */
function ensure_reports_schema(mysqli $db): void
{
    mysqli_query($db, "
        CREATE TABLE IF NOT EXISTS product_reports (
            id             INT AUTO_INCREMENT PRIMARY KEY,
            product_id     INT NOT NULL,
            reporter_id    INT NOT NULL,
            reason         VARCHAR(60)  NOT NULL,
            description    TEXT         NOT NULL,
            evidence_path  VARCHAR(255) DEFAULT NULL,
            status         ENUM('pending','resolved','dismissed') NOT NULL DEFAULT 'pending',
            admin_notes    TEXT         DEFAULT NULL,
            reviewed_by    INT          DEFAULT NULL,
            reviewed_at    TIMESTAMP    DEFAULT NULL,
            created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_product  (product_id),
            INDEX idx_reporter (reporter_id),
            INDEX idx_status   (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    mysqli_query($db, "
        CREATE TABLE IF NOT EXISTS user_reports (
            id                INT AUTO_INCREMENT PRIMARY KEY,
            reported_user_id  INT NOT NULL,
            reporter_id       INT NOT NULL,
            reason            VARCHAR(60)  NOT NULL,
            description       TEXT         NOT NULL,
            evidence_path     VARCHAR(255) DEFAULT NULL,
            status            ENUM('pending','resolved','dismissed') NOT NULL DEFAULT 'pending',
            admin_notes       TEXT         DEFAULT NULL,
            reviewed_by       INT          DEFAULT NULL,
            reviewed_at       TIMESTAMP    DEFAULT NULL,
            created_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_reported (reported_user_id),
            INDEX idx_reporter (reporter_id),
            INDEX idx_status   (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}
ensure_reports_schema($conn);

/* ══════════════════════════════════════
   HELPERS
══════════════════════════════════════ */
function json_err(string $msg, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit();
}

/* ══════════════════════════════════════
   ROUTE
══════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit();
}

$type        = trim($_POST['type']        ?? '');
$reason      = trim($_POST['reason']      ?? '');
$description = trim($_POST['description'] ?? '');
$targetId    = (int)($_POST['target_id']  ?? 0);

$allowed_types   = ['product', 'user'];
$allowed_reasons = ['spam', 'fraude', 'contenido_ofensivo', 'producto_prohibido', 'suplantacion', 'precio_enganoso', 'otro'];

if (!in_array($type, $allowed_types, true))     json_err('Tipo de reporte inválido');
if (!in_array($reason, $allowed_reasons, true)) json_err('Motivo no válido');
if ($targetId <= 0)                             json_err('ID de destino inválido');
if (mb_strlen($description) < 20)               json_err('La descripción debe tener al menos 20 caracteres');
if (mb_strlen($description) > 1000)             json_err('La descripción supera el límite (1000 caracteres)');

if ($type === 'user' && $targetId === $reporterId) {
    json_err('No puedes reportar tu propia cuenta');
}

/* Validar existencia del destino + no reportar lo propio */
if ($type === 'product') {
    $chk = mysqli_prepare($conn, "SELECT user_id FROM products WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($chk, 'i', $targetId);
    mysqli_stmt_execute($chk);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($chk));
    if (!$row)                                  json_err('Producto no encontrado', 404);
    if ((int)$row['user_id'] === $reporterId)   json_err('No puedes reportar tu propio producto');
} else {
    $chk = mysqli_prepare($conn, "SELECT id FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1");
    mysqli_stmt_bind_param($chk, 'i', $targetId);
    mysqli_stmt_execute($chk);
    if (mysqli_num_rows(mysqli_stmt_get_result($chk)) === 0) {
        json_err('Usuario no encontrado', 404);
    }
}

/* Evitar duplicados pendientes del mismo reportador */
$table     = $type === 'product' ? 'product_reports' : 'user_reports';
$targetCol = $type === 'product' ? 'product_id'      : 'reported_user_id';

$dup = mysqli_prepare($conn, "SELECT id FROM $table WHERE $targetCol = ? AND reporter_id = ? AND status = 'pending' LIMIT 1");
mysqli_stmt_bind_param($dup, 'ii', $targetId, $reporterId);
mysqli_stmt_execute($dup);
if (mysqli_num_rows(mysqli_stmt_get_result($dup)) > 0) {
    json_err('Ya tienes un reporte pendiente para este elemento');
}

/* Evidencia (opcional) */
$evidence_path = null;
if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['evidence']['error'] !== UPLOAD_ERR_OK) {
        json_err('Error al subir la evidencia');
    }
    $ext = strtolower(pathinfo($_FILES['evidence']['name'], PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowed_ext, true)) json_err('Formato de evidencia no permitido (jpg, png, webp)');
    if ($_FILES['evidence']['size'] > 5 * 1024 * 1024) json_err('La evidencia supera el límite de 5 MB');

    $uploadDir = __DIR__ . '/../../public/uploads/reportes/';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

    $filename = 'rep_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target   = $uploadDir . $filename;
    if (!move_uploaded_file($_FILES['evidence']['tmp_name'], $target)) {
        json_err('No se pudo guardar la evidencia');
    }
    $evidence_path = $filename;
}

/* Insertar reporte */
$sql  = "INSERT INTO $table ($targetCol, reporter_id, reason, description, evidence_path)
         VALUES (?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'iisss', $targetId, $reporterId, $reason, $description, $evidence_path);
if (!mysqli_stmt_execute($stmt)) {
    json_err('Error al registrar el reporte');
}

echo json_encode([
    'ok'      => true,
    'message' => 'Reporte enviado. Un administrador lo revisará pronto.',
    'id'      => mysqli_insert_id($conn),
]);

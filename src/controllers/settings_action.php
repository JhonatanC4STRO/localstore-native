<?php
require_once(__DIR__ . '/../config/conexion.php');
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$user_id = (int) $_SESSION['user']['id'];

// Asegurar tabla
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS user_settings (
    user_id INT PRIMARY KEY,
    notif_email_messages TINYINT(1) NOT NULL DEFAULT 1,
    notif_email_reviews  TINYINT(1) NOT NULL DEFAULT 1,
    notif_email_sales    TINYINT(1) NOT NULL DEFAULT 1,
    notif_email_promos   TINYINT(1) NOT NULL DEFAULT 1,
    notif_browser        TINYINT(1) NOT NULL DEFAULT 1,
    privacy_show_phone   TINYINT(1) NOT NULL DEFAULT 1,
    privacy_show_email   TINYINT(1) NOT NULL DEFAULT 0,
    privacy_who_can_message ENUM('all','verified','nobody') NOT NULL DEFAULT 'all',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$flag = function($key) {
    return isset($_POST[$key]) && ($_POST[$key] === '1' || $_POST[$key] === 'true' || $_POST[$key] === 'on') ? 1 : 0;
};

$notif_email_messages = $flag('notif_email_messages');
$notif_email_reviews  = $flag('notif_email_reviews');
$notif_email_sales    = $flag('notif_email_sales');
$notif_email_promos   = $flag('notif_email_promos');
$notif_browser        = $flag('notif_browser');
$privacy_show_phone   = $flag('privacy_show_phone');
$privacy_show_email   = $flag('privacy_show_email');

$who = $_POST['privacy_who_can_message'] ?? 'all';
if (!in_array($who, ['all', 'verified', 'nobody'], true)) $who = 'all';
$who_esc = mysqli_real_escape_string($conn, $who);

$sql = "INSERT INTO user_settings
    (user_id, notif_email_messages, notif_email_reviews, notif_email_sales, notif_email_promos,
     notif_browser, privacy_show_phone, privacy_show_email, privacy_who_can_message)
    VALUES
    ($user_id, $notif_email_messages, $notif_email_reviews, $notif_email_sales, $notif_email_promos,
     $notif_browser, $privacy_show_phone, $privacy_show_email, '$who_esc')
    ON DUPLICATE KEY UPDATE
        notif_email_messages = $notif_email_messages,
        notif_email_reviews  = $notif_email_reviews,
        notif_email_sales    = $notif_email_sales,
        notif_email_promos   = $notif_email_promos,
        notif_browser        = $notif_browser,
        privacy_show_phone   = $privacy_show_phone,
        privacy_show_email   = $privacy_show_email,
        privacy_who_can_message = '$who_esc'";

if (mysqli_query($conn, $sql)) {
    echo json_encode(['success' => true, 'message' => 'Preferencias guardadas correctamente.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . mysqli_error($conn)]);
}

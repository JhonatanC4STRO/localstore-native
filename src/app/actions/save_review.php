<?php
include_once("../../config/conexion.php");
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para dejar una reseña.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$product_id = intval($_POST['product_id'] ?? 0);
$rating     = intval($_POST['rating'] ?? 0);
$comment    = trim($_POST['comment'] ?? '');
$user_id    = intval($_SESSION['user']['id']);

if ($product_id <= 0 || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

// Verificar que el producto existe
$check_product = mysqli_query($conn, "SELECT id, user_id FROM products WHERE id = $product_id AND status = 1");
if (!$check_product || mysqli_num_rows($check_product) === 0) {
    echo json_encode(['success' => false, 'message' => 'Producto no encontrado.']);
    exit;
}

$product_row = mysqli_fetch_assoc($check_product);

// El vendedor no puede reseñar su propio producto
if (intval($product_row['user_id']) === $user_id) {
    echo json_encode(['success' => false, 'message' => 'No puedes reseñar tu propio producto.']);
    exit;
}

// Verificar si ya dejó una reseña
$check_dup = mysqli_query($conn, "SELECT id FROM reviews WHERE product_id = $product_id AND user_id = $user_id");
if ($check_dup && mysqli_num_rows($check_dup) > 0) {
    echo json_encode(['success' => false, 'message' => 'Ya dejaste una reseña para este producto.']);
    exit;
}

$comment_escaped = mysqli_real_escape_string($conn, $comment);

$sql = "INSERT INTO reviews (product_id, user_id, rating, comment)
        VALUES ($product_id, $user_id, $rating, '$comment_escaped')";

if (mysqli_query($conn, $sql)) {
    $user_name = htmlspecialchars($_SESSION['user']['full_name']);
    $initial   = strtoupper(mb_substr($user_name, 0, 1));
    echo json_encode([
        'success'   => true,
        'message'   => 'Reseña publicada.',
        'review'    => [
            'rating'    => $rating,
            'comment'   => htmlspecialchars($comment),
            'user_name' => $user_name,
            'initial'   => $initial,
            'date'      => date('d/m/Y'),
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al guardar la reseña.']);
}

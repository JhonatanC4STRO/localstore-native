<?php
session_start();
include('../config/conexion.php');
include('../config/role_sync.php');

if (!isset($_SESSION['user'])) {
    header('Location: ./auth/login.php');
    exit();
}

$user_id    = (int)$_SESSION['user']['id'];
$product_id = (int)($_GET['id'] ?? 0);

if ($product_id <= 0) {
    header('Location: ./misProductos.php');
    exit();
}

/* Verificar que el producto pertenece al usuario (o es admin) */
$is_admin = ($_SESSION['user']['role'] ?? '') === 'admin';
$check    = mysqli_query($conn, "SELECT user_id FROM products WHERE id = $product_id LIMIT 1");

if (!$check || mysqli_num_rows($check) === 0) {
    header('Location: ./misProductos.php');
    exit();
}

$owner_id = (int)mysqli_fetch_assoc($check)['user_id'];

if (!$is_admin && $owner_id !== $user_id) {
    header('Location: ./misProductos.php');
    exit();
}

/* Eliminar imágenes y producto */
$imgs = mysqli_query($conn, "SELECT image_url FROM product_images WHERE product_id = $product_id");
while ($img = mysqli_fetch_assoc($imgs)) {
    $path = __DIR__ . '/productos/uploads/' . $img['image_url'];
    if (file_exists($path)) @unlink($path);
}

mysqli_query($conn, "DELETE FROM product_images WHERE product_id = $product_id");
mysqli_query($conn, "DELETE FROM products WHERE id = $product_id");

/* Sincronizar rol: si ya no tiene productos, vuelve a 'user' */
sync_user_role($conn, $owner_id);

/* Refrescar sesión solo si el dueño es quien borra */
if ($owner_id === $user_id) {
    $refreshed = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id LIMIT 1");
    if ($refreshed) $_SESSION['user'] = mysqli_fetch_assoc($refreshed);
}

header('Location: ./misProductos.php');
exit();

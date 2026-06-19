<?php
session_start();
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/role_sync.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../views/auth/login.php');
    exit();
}

$user_id    = (int)$_SESSION['user']['id'];
$product_id = (int)($_GET['id'] ?? 0);

if ($product_id <= 0) {
    header('Location: ../views/products/mis_productos.php');
    exit();
}

/* Verificar que el producto pertenece al usuario (o es admin) */
$is_admin = ($_SESSION['user']['role'] ?? '') === 'admin';

$check_stmt = mysqli_prepare($conn, "SELECT user_id FROM products WHERE id = ? LIMIT 1");
if (!$check_stmt) {
    header('Location: ../views/products/mis_productos.php');
    exit();
}
mysqli_stmt_bind_param($check_stmt, "i", $product_id);
mysqli_stmt_execute($check_stmt);
$check_res = mysqli_stmt_get_result($check_stmt);

if (!$check_res || mysqli_num_rows($check_res) === 0) {
    mysqli_stmt_close($check_stmt);
    header('Location: ../views/products/mis_productos.php');
    exit();
}

$owner_id = (int)mysqli_fetch_assoc($check_res)['user_id'];
mysqli_stmt_close($check_stmt);

if (!$is_admin && $owner_id !== $user_id) {
    header('Location: ../views/products/mis_productos.php');
    exit();
}

/* Eliminar imágenes y producto */
$imgs_stmt = mysqli_prepare($conn, "SELECT image_url FROM product_images WHERE product_id = ?");
if ($imgs_stmt) {
    mysqli_stmt_bind_param($imgs_stmt, "i", $product_id);
    mysqli_stmt_execute($imgs_stmt);
    $imgs_res = mysqli_stmt_get_result($imgs_stmt);
    while ($img = mysqli_fetch_assoc($imgs_res)) {
        $path = __DIR__ . '/../app/productos/uploads/' . $img['image_url'];
        if (file_exists($path)) @unlink($path);
    }
    mysqli_stmt_close($imgs_stmt);
}

$del_imgs_stmt = mysqli_prepare($conn, "DELETE FROM product_images WHERE product_id = ?");
if ($del_imgs_stmt) {
    mysqli_stmt_bind_param($del_imgs_stmt, "i", $product_id);
    mysqli_stmt_execute($del_imgs_stmt);
    mysqli_stmt_close($del_imgs_stmt);
}

$del_prod_stmt = mysqli_prepare($conn, "DELETE FROM products WHERE id = ?");
if ($del_prod_stmt) {
    mysqli_stmt_bind_param($del_prod_stmt, "i", $product_id);
    mysqli_stmt_execute($del_prod_stmt);
    mysqli_stmt_close($del_prod_stmt);
}

/* Sincronizar rol: si ya no tiene productos, vuelve a 'user' */
sync_user_role($conn, $owner_id);

/* Refrescar sesión solo si el dueño es quien borra */
if ($owner_id === $user_id) {
    $ref_stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ? LIMIT 1");
    if ($ref_stmt) {
        mysqli_stmt_bind_param($ref_stmt, "i", $user_id);
        mysqli_stmt_execute($ref_stmt);
        $ref_res = mysqli_stmt_get_result($ref_stmt);
        if ($ref_res) {
            $_SESSION['user'] = mysqli_fetch_assoc($ref_res);
        }
        mysqli_stmt_close($ref_stmt);
    }
}

header('Location: ../views/products/mis_productos.php');
exit();






<?php
/* ══════════════════════════════════════
   crear_conversacion.php
   Crea o recupera una conversación.
   Si el destinatario la tenía oculta,
   la vuelve a mostrar automáticamente
   al recibir el nuevo mensaje.
══════════════════════════════════════ */

session_start();
include(__DIR__ . '/../config/conexion.php');

if (!isset($_SESSION['user'])) {
    header("Location: ./auth/login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

if (!isset($_GET['product_id']) || !is_numeric($_GET['product_id'])) {
    header("Location: ./inde.php?error=producto_invalido");
    exit();
}

$buyer_id   = (int) $_SESSION['user']['id'];
$product_id = (int) $_GET['product_id'];

// Obtener producto y vendedor
$res_product = mysqli_query($conn,
    "SELECT p.id, p.title, p.price, p.user_id AS seller_id, u.full_name AS seller_name
     FROM products p
     JOIN users u ON p.user_id = u.id
     WHERE p.id = $product_id LIMIT 1");

if (!$res_product || mysqli_num_rows($res_product) === 0) {
    header("Location: ./inde.php?error=producto_no_encontrado");
    exit();
}

$product   = mysqli_fetch_assoc($res_product);
$seller_id = (int) $product['seller_id'];

if ($buyer_id === $seller_id) {
    header("Location: ./actions/detalleProducto.php?id=$product_id&msg=own_product");
    exit();
}

// Buscar conversación existente (activa u oculta)
$res_check = mysqli_query($conn,
    "SELECT id, hidden_by_buyer, hidden_by_seller
     FROM conversations
     WHERE product_id = $product_id
       AND buyer_id   = $buyer_id
       AND seller_id  = $seller_id
     LIMIT 1");

if ($res_check && mysqli_num_rows($res_check) > 0) {

    $conv            = mysqli_fetch_assoc($res_check);
    $conversation_id = (int) $conv['id'];

    // ── Desocultar para AMBOS si era necesario ──
    // El comprador inicia de nuevo → se desoculta para él
    // El vendedor recibe mensaje   → se desoculta para él
    $resets = [];
    if ($conv['hidden_by_buyer']  == 1) $resets[] = 'hidden_by_buyer  = 0';
    if ($conv['hidden_by_seller'] == 1) $resets[] = 'hidden_by_seller = 0';

    if (!empty($resets)) {
        mysqli_query($conn,
            "UPDATE conversations SET " . implode(', ', $resets) .
            " WHERE id = $conversation_id");
    }

} else {

    // Crear conversación nueva
    $ok = mysqli_query($conn,
        "INSERT INTO conversations
             (product_id, buyer_id, seller_id, hidden_by_buyer, hidden_by_seller, created_at)
         VALUES
             ($product_id, $buyer_id, $seller_id, 0, 0, NOW())");

    if (!$ok) {
        error_log("Error creando conversación: " . mysqli_error($conn));
        header("Location: ./actions/detalleProducto.php?id=$product_id&error=chat_error");
        exit();
    }

    $conversation_id = (int) mysqli_insert_id($conn);

    // Mensaje de bienvenida automático
    $welcome = mysqli_real_escape_string($conn,
        "Hola! Me interesa tu producto: {$product['title']}.");
    mysqli_query($conn,
        "INSERT INTO messages (conversation_id, sender_id, message, created_at)
         VALUES ($conversation_id, $buyer_id, '$welcome', NOW())");
}

header("Location: ./chat.php?conversation_id=$conversation_id");
exit();
<?php
/* ══════════════════════════════════════
   crear_conversacion_general.php
   Crea o recupera una conversación con un vendedor.
   Puede o no estar vinculada a un producto.
══════════════════════════════════════ */

session_start();
include(__DIR__ . '/../config/conexion.php');

if (!isset($_SESSION['user'])) {
    header("Location: ./auth/login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$buyer_id = (int) $_SESSION['user']['id'];
$seller_id = isset($_GET['seller_id']) ? (int) $_GET['seller_id'] : 0;
$product_id = isset($_GET['product_id']) ? (int) $_GET['product_id'] : null;

if ($seller_id <= 0 && $product_id > 0) {
    // Intentar obtener seller_id del producto si no se pasó
    $res_p = mysqli_query($conn, "SELECT user_id FROM products WHERE id = $product_id LIMIT 1");
    if ($res_p && mysqli_num_rows($res_p) > 0) {
        $seller_id = (int) mysqli_fetch_assoc($res_p)['user_id'];
    }
}

if ($seller_id <= 0) {
    header("Location: ./inde.php?error=vendedor_no_encontrado");
    exit();
}

if ($buyer_id === $seller_id) {
    if ($product_id) {
        header("Location: ./actions/detalleProducto.php?id=$product_id&msg=own_product");
    } else {
        header("Location: ./perfil.php");
    }
    exit();
}

// Buscar conversación existente
// Si hay product_id, buscamos una vinculada a ese producto.
// Si no hay product_id, buscamos una "general" (product_id IS NULL) entre estos dos usuarios.
$check_sql = "SELECT id, hidden_by_buyer, hidden_by_seller FROM conversations 
              WHERE buyer_id = $buyer_id AND seller_id = $seller_id ";

if ($product_id) {
    $check_sql .= " AND product_id = $product_id ";
} else {
    $check_sql .= " AND product_id IS NULL ";
}
$check_sql .= " LIMIT 1";

$res_check = mysqli_query($conn, $check_sql);

if ($res_check && mysqli_num_rows($res_check) > 0) {
    $conv = mysqli_fetch_assoc($res_check);
    $conversation_id = (int) $conv['id'];

    // Desocultar si estaba oculta
    $resets = [];
    if ($conv['hidden_by_buyer'] == 1) $resets[] = "hidden_by_buyer = 0";
    if ($conv['hidden_by_seller'] == 1) $resets[] = "hidden_by_seller = 0";

    if (!empty($resets)) {
        mysqli_query($conn, "UPDATE conversations SET " . implode(', ', $resets) . " WHERE id = $conversation_id");
    }
} else {
    // Crear nueva conversación
    $p_val = $product_id ? $product_id : "NULL";
    $ins_sql = "INSERT INTO conversations (product_id, buyer_id, seller_id, created_at) 
                VALUES ($p_val, $buyer_id, $seller_id, NOW())";
    
    if (mysqli_query($conn, $ins_sql)) {
        $conversation_id = mysqli_insert_id($conn);
        
        // Mensaje inicial opcional
        $msg_text = "¡Hola! Me gustaría contactarte.";
        if ($product_id) {
            $res_p_info = mysqli_query($conn, "SELECT title FROM products WHERE id = $product_id");
            if ($res_p_info && $row_p = mysqli_fetch_assoc($res_p_info)) {
                $msg_text = "¡Hola! Me interesa tu producto: " . mysqli_real_escape_string($conn, $row_p['title']) . ".";
            }
        }
        
        $msg_text_esc = mysqli_real_escape_string($conn, $msg_text);
        mysqli_query($conn, "INSERT INTO messages (conversation_id, sender_id, message, created_at) 
                            VALUES ($conversation_id, $buyer_id, '$msg_text_esc', NOW())");
    } else {
        header("Location: ./inde.php?error=chat_error");
        exit();
    }
}

header("Location: ./chat.php?conversation_id=$conversation_id");
exit();
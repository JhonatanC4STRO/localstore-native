<?php
/**
 * role_sync.php — ComercioLocal
 *
 * Reglas de rol:
 *   type_user = 'tienda'               → seller  (siempre)
 *   type_user = 'usuario' + productos  → seller
 *   type_user = 'usuario' + sin prods  → user
 *   role = 'admin'                     → nunca se toca
 */

function sync_user_role(mysqli $conn, int $user_id): void
{
    if ($user_id <= 0) return;

    /* No tocar admins */
    $chk = mysqli_query($conn, "SELECT role FROM users WHERE id = $user_id LIMIT 1");
    if (!$chk || mysqli_num_rows($chk) === 0) return;
    $current = mysqli_fetch_assoc($chk)['role'] ?? '';
    if ($current === 'admin') return;

    mysqli_query($conn, "
        UPDATE users
        SET role = CASE
            WHEN type_user = 'tienda' THEN 'seller'
            WHEN (SELECT COUNT(*) FROM products WHERE user_id = $user_id) > 0 THEN 'seller'
            ELSE 'user'
        END
        WHERE id = $user_id
    ");
}

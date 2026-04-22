<?php
/**
 * role_sync.php — ComercioLocal
 *
 * Reglas de rol:
 *   role = 'seller'                 → se conserva (ya es vendedor)
 *   role = 'user' + productos       → promover a seller
 *   role = 'user' + sin productos   → permanece user
 *   role = 'admin' o 'super_admin'  → nunca se toca
 */

function sync_user_role(mysqli $conn, int $user_id): void
{
    if ($user_id <= 0) return;

    /* No tocar admins, super admins ni sellers ya establecidos */
    $chk = mysqli_query($conn, "SELECT role FROM users WHERE id = $user_id LIMIT 1");
    if (!$chk || mysqli_num_rows($chk) === 0) return;
    $current = mysqli_fetch_assoc($chk)['role'] ?? '';
    if (in_array($current, ['admin', 'super_admin', 'seller'], true)) return;

    mysqli_query($conn, "
        UPDATE users
        SET role = CASE
            WHEN (SELECT COUNT(*) FROM products WHERE user_id = $user_id) > 0 THEN 'seller'
            ELSE 'user'
        END
        WHERE id = $user_id
    ");
}





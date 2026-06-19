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

    /* No tocar admins, super admins ni sellers ya establecidos usando consulta preparada */
    $chk_stmt = mysqli_prepare($conn, "SELECT role FROM users WHERE id = ? LIMIT 1");
    if (!$chk_stmt) return;
    mysqli_stmt_bind_param($chk_stmt, "i", $user_id);
    mysqli_stmt_execute($chk_stmt);
    $chk_res = mysqli_stmt_get_result($chk_stmt);
    if (!$chk_res || mysqli_num_rows($chk_res) === 0) {
        mysqli_stmt_close($chk_stmt);
        return;
    }
    $current = mysqli_fetch_assoc($chk_res)['role'] ?? '';
    mysqli_stmt_close($chk_stmt);

    if (in_array($current, ['admin', 'super_admin', 'seller'], true)) return;

    /* Promoción a seller si tiene productos usando consulta preparada */
    $upd_stmt = mysqli_prepare($conn, "
        UPDATE users
        SET role = CASE
            WHEN (SELECT COUNT(*) FROM products WHERE user_id = ?) > 0 THEN 'seller'
            ELSE 'user'
        END
        WHERE id = ?
    ");
    if ($upd_stmt) {
        mysqli_stmt_bind_param($upd_stmt, "ii", $user_id, $user_id);
        mysqli_stmt_execute($upd_stmt);
        mysqli_stmt_close($upd_stmt);
    }
}





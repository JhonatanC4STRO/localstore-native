<?php
/**
 * Helper para leer preferencias de privacidad/notificaciones de un usuario.
 * Devuelve siempre un array con valores por defecto aunque la fila no exista.
 */

if (!function_exists('get_user_settings')) {
    function get_user_settings(mysqli $conn, int $user_id): array {
        $defaults = [
            'notif_email_messages'    => 1,
            'notif_email_reviews'     => 1,
            'notif_email_sales'       => 1,
            'notif_email_promos'      => 1,
            'notif_browser'           => 1,
            'privacy_show_phone'      => 1,
            'privacy_show_email'      => 0,
            'privacy_who_can_message' => 'all',
        ];
        if ($user_id <= 0) return $defaults;

        $q = mysqli_query($conn, "SELECT * FROM user_settings WHERE user_id = $user_id LIMIT 1");
        if ($q && mysqli_num_rows($q) > 0) {
            $row = mysqli_fetch_assoc($q);
            foreach ($defaults as $k => $v) {
                if (isset($row[$k])) $defaults[$k] = $row[$k];
            }
        }
        return $defaults;
    }
}

if (!function_exists('user_is_verified')) {
    function user_is_verified(mysqli $conn, int $user_id): bool {
        if ($user_id <= 0) return false;
        $q = mysqli_query($conn,
            "SELECT id FROM seller_verifications
             WHERE user_id = $user_id AND status = 'approved' LIMIT 1");
        return $q && mysqli_num_rows($q) > 0;
    }
}

/**
 * ¿Puede $buyer_id iniciar chat con $seller_id según las prefs del vendedor?
 * Devuelve ['ok' => bool, 'reason' => string].
 */
if (!function_exists('can_message_seller')) {
    function can_message_seller(mysqli $conn, int $buyer_id, int $seller_id): array {
        $s = get_user_settings($conn, $seller_id);
        $mode = $s['privacy_who_can_message'];

        if ($mode === 'nobody') {
            return ['ok' => false, 'reason' => 'Este vendedor tiene el chat cerrado.'];
        }
        if ($mode === 'verified' && !user_is_verified($conn, $buyer_id)) {
            return ['ok' => false, 'reason' => 'Este vendedor solo acepta mensajes de usuarios verificados.'];
        }
        return ['ok' => true, 'reason' => ''];
    }
}

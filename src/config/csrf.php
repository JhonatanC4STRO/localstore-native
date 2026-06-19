<?php
/* ══════════════════════════════════════════════════════════════
   csrf.php — Helper central para protección contra ataques CSRF
   ══════════════════════════════════════════════════════════════ */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Genera un token CSRF si no existe en la sesión y lo retorna.
 */
function get_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Imprime el input oculto con el token CSRF para formularios.
 */
function insert_csrf_input(): void
{
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verifica si el token CSRF suministrado es válido.
 */
function verify_csrf_token(?string $token): bool
{
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

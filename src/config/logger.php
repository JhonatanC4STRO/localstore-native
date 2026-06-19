<?php
/* ══════════════════════════════════════════════════════════════
   logger.php — Helper central para el registro de errores seguro
   ══════════════════════════════════════════════════════════════ */

/**
 * Registra un mensaje de error detallado de forma interna y privada.
 */
function log_error(string $message, string $category = 'GENERAL'): void
{
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . '/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
    $formatted_message = sprintf("[%s] [%s] [IP: %s] %s\n", $timestamp, strtoupper($category), $ip, $message);
    
    @file_put_contents($log_file, $formatted_message, FILE_APPEND);
}

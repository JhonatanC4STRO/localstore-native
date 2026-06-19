<?php
$mysql_url = getenv('MYSQL_URL') ?: getenv('DATABASE_URL') ?: null;

if ($mysql_url) {
    $p    = parse_url(trim($mysql_url));
    $host = $p['host'];
    $user = $p['user'];
    $pass = $p['pass'] ?? '';
    $db   = ltrim($p['path'], '/');
    $port = (int)($p['port'] ?? 3306);
} else {
    $host = trim(getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: 'localhost');
    $user = trim(getenv('DB_USER') ?: getenv('MYSQLUSER') ?: 'root');
    $pass = trim(getenv('DB_PASS') ?: getenv('MYSQLPASSWORD') ?: '');
    $db   = trim(getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: 'tiendalocal');
    $port = (int)trim(getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: '3306');
}

require_once __DIR__ . '/logger.php';

try {
    mysqli_report(MYSQLI_REPORT_OFF); // Evita excepciones nativas automáticas ruidosas que revelan rutas
    $conn = @mysqli_connect($host, $user, $pass, $db, $port);
    if (!$conn) {
        throw new Exception(mysqli_connect_error());
    }
} catch (Throwable $e) {
    log_error("Fallo de conexión a la base de datos: " . $e->getMessage(), 'DATABASE');
    http_response_code(500);
    die("<div style='font-family:\"Outfit\",sans-serif; text-align:center; padding:50px; color:#334155;'>
            <h2 style='color:#ef4444;'>Error Interno de Servidor</h2>
            <p>Lo sentimos, ha ocurrido un problema técnico en la plataforma. Por favor, inténtelo de nuevo más tarde.</p>
         </div>");
}

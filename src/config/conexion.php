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

$conn = mysqli_connect($host, $user, $pass, $db, $port);

if (!$conn) {
    die('Error de conexión: ' . mysqli_connect_error());
}

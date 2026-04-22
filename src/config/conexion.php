<?php
$mysql_url = getenv('MYSQL_URL') ?: getenv('DATABASE_URL') ?: null;

if ($mysql_url) {
    $p    = parse_url($mysql_url);
    $host = $p['host'];
    $user = $p['user'];
    $pass = $p['pass'] ?? '';
    $db   = ltrim($p['path'], '/');
    $port = (int)($p['port'] ?? 3306);
} else {
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $db   = 'tiendalocal';
    $port = 3306;
}

$conn = mysqli_connect($host, $user, $pass, $db, $port);

if (!$conn) {
    die('Error de conexión: ' . mysqli_connect_error());
}

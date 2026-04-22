<?php
// Railway MySQL plugin usa MYSQLHOST, MYSQLUSER, etc.
// Fallback a DB_* para otros entornos y local
$host = getenv('MYSQLHOST')     ?: getenv('DB_HOST') ?: 'localhost';
$user = getenv('MYSQLUSER')     ?: getenv('DB_USER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '';
$db   = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'tiendalocal';
$port = (int)(getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: 3306);

$conn = mysqli_connect($host, $user, $pass, $db, $port);

if (!$conn) {
    die('Error de conexión: ' . mysqli_connect_error());
}

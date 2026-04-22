<?php
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db   = getenv('DB_NAME') ?: 'tiendalocal';

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die(json_encode(['error' => 'DB connection failed: ' . mysqli_connect_error()]));
}

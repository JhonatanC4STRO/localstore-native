<?php
// BORRAR ESTE ARCHIVO DESPUES DE DIAGNOSTICAR
$vars = array_filter(
    array_merge(getenv(), $_ENV, $_SERVER),
    fn($k) => str_contains(strtoupper($k), 'MYSQL') || str_contains(strtoupper($k), 'DB_') || str_contains(strtoupper($k), 'DATABASE'),
    ARRAY_FILTER_USE_KEY
);

echo '<pre>';
foreach ($vars as $k => $v) {
    echo htmlspecialchars($k) . ' = ' . htmlspecialchars($v) . "\n";
}
if (empty($vars)) {
    echo "No se encontraron variables de base de datos.\n\nTodas las variables disponibles:\n";
    foreach (array_merge(getenv(), $_ENV) as $k => $v) {
        echo htmlspecialchars($k) . "\n";
    }
}
echo '</pre>';

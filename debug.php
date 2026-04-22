<?php
// BORRAR ESTE ARCHIVO DESPUES DE DIAGNOSTICAR

echo '<h2>Variables DB</h2><pre>';
$vars = array_filter(
    getenv(),
    fn($k) => str_contains(strtoupper($k), 'MYSQL') || str_contains(strtoupper($k), 'DB_'),
    ARRAY_FILTER_USE_KEY
);
foreach ($vars as $k => $v) {
    echo htmlspecialchars($k) . " = " . htmlspecialchars($v) . "\n";
}
echo '</pre>';

echo '<h2>Archivos en public/uploads/products/</h2><pre>';
$dir = __DIR__ . '/public/uploads/products';
if (is_dir($dir)) {
    $files = scandir($dir);
    echo "Total: " . (count($files) - 2) . " archivos\n\n";
    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..') {
            $size = filesize("$dir/$f");
            echo "$f ($size bytes)\n";
        }
    }
} else {
    echo "Directorio NO existe: $dir";
}
echo '</pre>';

echo '<h2>Ruta absoluta del script</h2><pre>' . __DIR__ . '</pre>';

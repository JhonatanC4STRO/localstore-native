<?php
echo '<h2>Contenido de /var/www/html</h2><pre>';
$files = scandir('/var/www/html');
foreach ($files as $f) {
    if ($f !== '.' && $f !== '..') {
        $type = is_dir("/var/www/html/$f") ? 'DIR ' : 'FILE';
        echo "$type $f\n";
    }
}
echo '</pre>';

echo '<h2>Contenido de /var/www/html/public</h2><pre>';
$dir = '/var/www/html/public';
if (is_dir($dir)) {
    foreach (scandir($dir) as $f) {
        if ($f !== '.' && $f !== '..') {
            $type = is_dir("$dir/$f") ? 'DIR ' : 'FILE';
            echo "$type $f\n";
        }
    }
} else {
    echo "NO EXISTE\n";
}
echo '</pre>';

echo '<h2>Contenido de /var/www/html/public/uploads</h2><pre>';
$dir = '/var/www/html/public/uploads';
if (is_dir($dir)) {
    foreach (scandir($dir) as $f) {
        if ($f !== '.' && $f !== '..') {
            $type = is_dir("$dir/$f") ? 'DIR ' : 'FILE';
            echo "$type $f\n";
        }
    }
} else {
    echo "NO EXISTE\n";
}
echo '</pre>';

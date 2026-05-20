<?php
function folderSize($dir) {
    $size = 0;

    if (!is_dir($dir)) {
        return 0;
    }

    $items = @scandir($dir);
    if ($items === false) {
        return 0;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_file($path)) {
            $size += filesize($path);
        } elseif (is_dir($path)) {
            $size += folderSize($path);
        }
    }

    return $size;
}

function formatSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;

    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }

    return round($bytes, 2) . ' ' . $units[$i];
}

$base = __DIR__;
$folders = scandir($base);

echo "<h2>Uso de espacio en: " . htmlspecialchars($base) . "</h2>";
echo "<table border='1' cellpadding='8' cellspacing='0'>";
echo "<tr><th>Carpeta / Archivo</th><th>Tamaño</th></tr>";

$results = [];

foreach ($folders as $folder) {
    if ($folder === '.' || $folder === '..') {
        continue;
    }

    $path = $base . DIRECTORY_SEPARATOR . $folder;

    if (is_dir($path)) {
        $results[$folder] = folderSize($path);
    } elseif (is_file($path)) {
        $results[$folder] = filesize($path);
    }
}

arsort($results);

foreach ($results as $name => $size) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($name) . "</td>";
    echo "<td>" . formatSize($size) . "</td>";
    echo "</tr>";
}

echo "</table>";
<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);
set_time_limit(0);

$secret = 'madrural2026';

if (($_GET['key'] ?? '') !== $secret) {
    http_response_code(403);
    exit('Acceso denegado');
}

$allowedFolders = ['2', '5', 'tmp'];
$folder = $_GET['folder'] ?? '5';

if (!in_array($folder, $allowedFolders, true)) {
    exit('Carpeta no permitida');
}

$dir = __DIR__ . '/wp-content/uploads/ninja-forms/' . $folder;

if (!is_dir($dir)) {
    exit('No existe la carpeta: ' . htmlspecialchars($dir));
}

function formatSize(int|float $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;

    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }

    return round($bytes, 2) . ' ' . $units[$i];
}

function psQuote(string $value): string
{
    return "'" . str_replace("'", "''", $value) . "'";
}

$host = $_SERVER['HTTP_HOST'];
$baseUrl = 'https://' . $host . '/wp-content/uploads/ninja-forms/' . rawurlencode($folder) . '/';

$files = [];

foreach (new DirectoryIterator($dir) as $item) {
    if ($item->isDot() || !$item->isFile()) {
        continue;
    }

    $name = $item->getFilename();

    $files[] = [
        'name' => $name,
        'size' => $item->getSize(),
        'url' => $baseUrl . rawurlencode($name),
        'modified' => date('Y-m-d H:i:s', $item->getMTime()),
    ];
}

usort($files, fn($a, $b) => $b['size'] <=> $a['size']);

$totalSize = array_sum(array_column($files, 'size'));

if (($_GET['format'] ?? '') === 'ps1') {
    header('Content-Type: text/plain; charset=UTF-8');
    header('Content-Disposition: attachment; filename="download-ninja-forms-' . $folder . '.ps1"');

    echo '$ErrorActionPreference = "Stop"' . PHP_EOL;
    echo '$target = Join-Path (Get-Location) ' . psQuote('ninja-forms-' . $folder) . PHP_EOL;
    echo 'New-Item -ItemType Directory -Force -Path $target | Out-Null' . PHP_EOL . PHP_EOL;

    echo '$files = @(' . PHP_EOL;

    foreach ($files as $file) {
        echo '    @{ Name = ' . psQuote($file['name']) . '; Url = ' . psQuote($file['url']) . '; Size = [int64]' . $file['size'] . ' }' . PHP_EOL;
    }

    echo ')' . PHP_EOL . PHP_EOL;

    echo 'foreach ($f in $files) {' . PHP_EOL;
    echo '    $out = Join-Path $target $f.Name' . PHP_EOL;
    echo '    Write-Host "Descargando: $($f.Name)"' . PHP_EOL;
    echo '    if ((Test-Path $out) -and ((Get-Item $out).Length -eq $f.Size)) {' . PHP_EOL;
    echo '        Write-Host "Ya descargado correctamente: $($f.Name)"' . PHP_EOL;
    echo '        continue' . PHP_EOL;
    echo '    }' . PHP_EOL;
    echo '    curl.exe -L --fail --retry 10 --retry-delay 10 --continue-at - --output $out $f.Url' . PHP_EOL;
    echo '    if (!(Test-Path $out)) {' . PHP_EOL;
    echo '        throw "No se ha descargado: $($f.Name)"' . PHP_EOL;
    echo '    }' . PHP_EOL;
    echo '    $localSize = (Get-Item $out).Length' . PHP_EOL;
    echo '    if ($localSize -ne $f.Size) {' . PHP_EOL;
    echo '        throw "Tamaño incorrecto en $($f.Name). Esperado: $($f.Size). Local: $localSize"' . PHP_EOL;
    echo '    }' . PHP_EOL;
    echo '}' . PHP_EOL . PHP_EOL;

    echo '$downloaded = Get-ChildItem $target -File' . PHP_EOL;
    echo '$total = ($downloaded | Measure-Object -Property Length -Sum).Sum' . PHP_EOL;
    echo 'Write-Host "Descarga finalizada."' . PHP_EOL;
    echo 'Write-Host "Archivos descargados: $($downloaded.Count)"' . PHP_EOL;
    echo 'Write-Host "Tamaño total GB: $([Math]::Round($total / 1GB, 2))"' . PHP_EOL;

    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Descarga Ninja Forms</title>
</head>
<body>
    <h1>Descarga Ninja Forms <?= htmlspecialchars($folder) ?></h1>

    <p><strong>Carpeta:</strong> <?= htmlspecialchars($dir) ?></p>
    <p><strong>Archivos:</strong> <?= count($files) ?></p>
    <p><strong>Tamaño total:</strong> <?= htmlspecialchars(formatSize($totalSize)) ?></p>

    <p>
        <a href="?key=<?= urlencode($secret) ?>&folder=<?= urlencode($folder) ?>&format=ps1">
            Descargar script PowerShell para esta carpeta
        </a>
    </p>

    <ol>
        <?php foreach ($files as $file): ?>
            <li>
                <?= htmlspecialchars($file['name']) ?>
                —
                <strong><?= htmlspecialchars(formatSize($file['size'])) ?></strong>
            </li>
        <?php endforeach; ?>
    </ol>
</body>
</html>
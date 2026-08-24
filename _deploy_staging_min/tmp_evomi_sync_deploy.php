<?php
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
if (($_GET['key'] ?? '') !== 'evomi-nav-sync-2026') {
    http_response_code(403);
    echo "forbidden\n";
    exit;
}

$docRoot = __DIR__;
$laravel = dirname($docRoot) . '/laravel';
$codeSrc = $docRoot . '/code-sync';
$buildSrc = $docRoot . '/build-sync';

if (! is_dir($laravel)) {
    echo "missing laravel dir: $laravel\n";
    exit(1);
}

function copyTree(string $src, string $dstRoot, string $rel = ''): int
{
    $count = 0;
    $path = $src . ($rel !== '' ? '/' . $rel : '');
    if (! is_dir($path)) {
        return 0;
    }
    foreach (scandir($path) ?: [] as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }
        $childRel = $rel !== '' ? $rel . '/' . $name : $name;
        $from = $src . '/' . $childRel;
        $to = $dstRoot . '/' . $childRel;
        if (is_dir($from)) {
            if (! is_dir($to)) {
                mkdir($to, 0755, true);
            }
            $count += copyTree($src, $dstRoot, $childRel);
            continue;
        }
        if (! is_dir(dirname($to))) {
            mkdir(dirname($to), 0755, true);
        }
        if (copy($from, $to)) {
            echo "OK $childRel\n";
            $count++;
        } else {
            echo "FAIL $childRel\n";
        }
    }

    return $count;
}

$total = 0;
if (is_dir($codeSrc)) {
    echo "=== code-sync ===\n";
    $total += copyTree($codeSrc, $laravel);
} else {
    echo "missing code-sync\n";
}

if (is_dir($buildSrc)) {
    echo "=== build-sync -> laravel/public/build ===\n";
    $total += copyTree($buildSrc, $laravel . '/public/build');
    echo "=== build-sync -> docroot/build ===\n";
    $total += copyTree($buildSrc, $docRoot . '/build');
}

require $laravel . '/vendor/autoload.php';
$app = require $laravel . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (['view:clear', 'route:clear', 'config:clear'] as $cmd) {
    try {
        Illuminate\Support\Facades\Artisan::call($cmd);
        echo "$cmd ok\n";
    } catch (Throwable $e) {
        echo "$cmd error: " . $e->getMessage() . "\n";
    }
}

$views = glob($laravel . '/storage/framework/views/*.php') ?: [];
foreach ($views as $file) {
    @unlink($file);
}
echo 'cleared_views=' . count($views) . "\n";
echo "copied_files=$total\n";
echo "done\n";

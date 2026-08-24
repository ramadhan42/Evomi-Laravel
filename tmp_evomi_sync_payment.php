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
$srcRoot = $docRoot . '/code-sync';
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
        if (! copy($from, $to)) {
            echo "FAIL $childRel\n";
            continue;
        }
        echo "OK $childRel\n";
        $count++;
    }

    return $count;
}

$copied = 0;
if (is_dir($srcRoot)) {
    echo "=== code-sync ===\n";
    $copied += copyTree($srcRoot, $laravel);
} else {
    echo "missing code-sync\n";
}

if (is_dir($buildSrc)) {
    echo "=== build-sync ===\n";
    $buildDst = $laravel . '/public/build';
    $copied += copyTree($buildSrc, $buildDst);
} else {
    echo "missing build-sync\n";
}

require $laravel . '/vendor/autoload.php';
$app = require $laravel . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== migrate ===\n";
try {
    Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    echo Illuminate\Support\Facades\Artisan::output();
} catch (Throwable $e) {
    echo 'migrate error: ' . $e->getMessage() . "\n";
}

foreach (['view:clear', 'route:clear', 'config:clear'] as $cmd) {
    try {
        Illuminate\Support\Facades\Artisan::call($cmd);
        echo "$cmd ok\n";
    } catch (Throwable $e) {
        echo "$cmd error: " . $e->getMessage() . "\n";
    }
}

echo "copied_files=$copied\n";
echo "done\n";

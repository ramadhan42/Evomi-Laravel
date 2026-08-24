<?php
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
if (($_GET['key'] ?? '') !== 'evomi-nav-sync-2026') {
    http_response_code(403);
    echo "forbidden\n";
    exit;
}

$docRoot = __DIR__;
$laravel = dirname($docRoot).'/laravel';
if (! is_dir($laravel)) {
    $laravel = $docRoot.'/laravel';
}
echo "laravel=$laravel\n";

require $laravel.'/vendor/autoload.php';
$app = require $laravel.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (['view:clear', 'route:clear', 'config:clear', 'cache:clear'] as $cmd) {
    try {
        Illuminate\Support\Facades\Artisan::call($cmd);
        echo "$cmd ok\n";
    } catch (Throwable $e) {
        echo "$cmd error: ".$e->getMessage()."\n";
    }
}

try {
    Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    echo "migrate ok\n";
    echo trim(Illuminate\Support\Facades\Artisan::output())."\n";
} catch (Throwable $e) {
    echo 'migrate error: '.$e->getMessage()."\n";
}

echo "done\n";

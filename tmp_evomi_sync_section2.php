<?php
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
if (($_GET['key'] ?? '') !== 'evomi-nav-sync-2026') { http_response_code(403); echo "forbidden\n"; exit; }

$docRoot = __DIR__;
$laravel = dirname($docRoot) . '/laravel';
$srcFile = $docRoot . '/code-sync/resources/views/beranda/second.blade.php';
$dstFile = $laravel . '/resources/views/beranda/second.blade.php';

function smd($p) { return is_file($p) ? substr(md5_file($p),0,12) : 'MISSING'; }

echo "docRoot=$docRoot\n";
echo "laravel=$laravel\n";
echo "srcFileExists=" . (is_file($srcFile) ? 'yes' : 'no') . " md5=" . smd($srcFile) . "\n";
echo "dstFileExists=" . (is_file($dstFile) ? 'yes' : 'no') . " md5=" . smd($dstFile) . "\n";

if (!is_file($srcFile)) {
  echo "source missing\n";
  exit;
}

if (!is_dir(dirname($dstFile))) {
  mkdir(dirname($dstFile), 0755, true);
}

$ok = copy($srcFile, $dstFile);

echo "copy_ok=" . ($ok ? 'yes' : 'no') . "\n";
echo "dstAfterExists=" . (is_file($dstFile) ? 'yes' : 'no') . " md5=" . smd($dstFile) . "\n";

// Clear Laravel caches (best-effort). Skip if the laravel path differs on the server.
$autoload = $laravel . '/vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
    $app = require $laravel . '/bootstrap/app.php';
    $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
    try { Illuminate\\Support\\Facades\\Artisan::call('view:clear'); } catch (Throwable $e) {}
    try { Illuminate\\Support\\Facades\\Artisan::call('route:clear'); } catch (Throwable $e) {}
    try { Illuminate\\Support\\Facades\\Artisan::call('config:clear'); } catch (Throwable $e) {}
} else {
    echo "skip_cache_clear=autoload_missing\n";
}

echo "done\n";
?>

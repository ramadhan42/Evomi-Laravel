<?php
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
if (($_GET['key'] ?? '') !== 'evomi-nav-sync-2026') { http_response_code(403); echo "forbidden\n"; exit; }
$docRoot = __DIR__;
$laravel = dirname($docRoot) . '/laravel';
$srcFile = $docRoot . '/code-sync/resources/views/beranda/second.blade.php';
$dstFile = $laravel . '/resources/views/beranda/second.blade.php';

echo "docRoot=$docRoot\n";
echo "laravel=$laravel\n";
echo "srcExists=" . (is_file($srcFile) ? 'yes' : 'no') . "\n";
echo "dstExists=" . (is_file($dstFile) ? 'yes' : 'no') . "\n";

if (!is_file($srcFile)) {
  echo "source missing\n";
  exit;
}

if (!is_dir(dirname($dstFile))) {
  @mkdir(dirname($dstFile), 0755, true);
}

$ok = @copy($srcFile, $dstFile);
echo "copy_ok=" . ($ok ? 'yes' : 'no') . "\n";
echo "dstAfterExists=" . (is_file($dstFile) ? 'yes' : 'no') . "\n";

// Clear view cache (best-effort). If laravel path works, this should remove stale compiled views.
$autoload = $laravel . '/vendor/autoload.php';
if (is_file($autoload)) {
  try {
    @require $autoload;
    $app = @require $laravel . '/bootstrap/app.php';
    if ($app) {
      try { $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); } catch (Throwable $e) {}
    }
    try { Illuminate\\Support\\Facades\\Artisan::call('view:clear'); } catch (Throwable $e) {}
    try { Illuminate\\Support\\Facades\\Artisan::call('route:clear'); } catch (Throwable $e) {}
    try { Illuminate\\Support\\Facades\\Artisan::call('config:clear'); } catch (Throwable $e) {}
    echo "cache_cleared=yes\n";
  } catch (Throwable $e) {
    echo "cache_cleared=no\n";
  }
} else {
  echo "cache_cleared=autoload_missing\n";
}

echo "done\n";
?>

<?php
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
if (($_GET['key'] ?? '') !== 'evomi-nav-sync-2026') { http_response_code(403); echo "forbidden\n"; exit; }
$docRoot = __DIR__;
$laravel = dirname($docRoot) . '/laravel';
$viewsDir = $laravel . '/storage/framework/views';

echo "laravel=$laravel\n";
echo "viewsDir=$viewsDir\n";

function delTree($dir, &$count=0) {
  if (!is_dir($dir)) return;
  foreach (scandir($dir) ?: [] as $name) {
    if ($name === '.' || $name === '..') continue;
    $p = $dir . '/' . $name;
    if (is_dir($p)) {
      delTree($p, $count);
      @rmdir($p);
    } else {
      if (@unlink($p)) { $count++; }
    }
  }
}

$count=0;
delTree($viewsDir, $count);

echo "deleted_files=$count\n";
echo "done\n";
?>

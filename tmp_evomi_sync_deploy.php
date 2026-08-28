<?php
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
$docRoot = __DIR__;
$laravel = dirname($docRoot) . '/laravel';

/*
 * Kunci dibaca langsung dari laravel/.env, bukan lewat config().
 *
 * Pemeriksaan sengaja berjalan sebelum Laravel di-boot supaya permintaan tanpa
 * kunci tidak sempat menyentuh framework maupun database. Kuncinya juga TIDAK
 * boleh ditulis di berkas ini: repo bersifat publik, jadi apa pun yang
 * ter-commit di sini otomatis bisa dibaca siapa saja.
 */
function evomiEnvValue(string $envFile, string $name): string
{
    if (! is_readable($envFile)) {
        return '';
    }

    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);

        if ($line === '' || $line[0] === '#' || ! str_starts_with($line, $name . '=')) {
            continue;
        }

        $value = trim(substr($line, strlen($name) + 1));

        // Buang tanda kutip pembungkus bila ada
        if (strlen($value) >= 2
            && ($value[0] === '"' || $value[0] === "'")
            && $value[strlen($value) - 1] === $value[0]) {
            $value = substr($value, 1, -1);
        }

        return $value;
    }

    return '';
}

$expectedKey = evomiEnvValue($laravel . '/.env', 'EVOMI_SYNC_KEY');

if ($expectedKey === '') {
    // Fail closed: tanpa kunci terkonfigurasi, endpoint ini mati total.
    http_response_code(503);
    echo "EVOMI_SYNC_KEY belum diset di laravel/.env\n";
    exit;
}

if (! hash_equals($expectedKey, (string) ($_GET['key'] ?? ''))) {
    http_response_code(403);
    echo "forbidden\n";
    exit;
}

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
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (['view:clear', 'route:clear', 'config:clear', 'cache:clear'] as $cmd) {
    try {
        Illuminate\Support\Facades\Artisan::call($cmd);
        echo "$cmd ok\n";
    } catch (Throwable $e) {
        echo "$cmd error: " . $e->getMessage() . "\n";
    }
}

try {
    Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    echo "migrate ok\n";
    echo trim(Illuminate\Support\Facades\Artisan::output()) . "\n";
} catch (Throwable $e) {
    echo "migrate error: " . $e->getMessage() . "\n";
}

$views = glob($laravel . '/storage/framework/views/*.php') ?: [];
foreach ($views as $file) {
    @unlink($file);
}
echo 'cleared_views=' . count($views) . "\n";

/*
 * Bangun cache SETELAH kode tersalin, migrasi jalan, dan view lama dibuang.
 *
 * config:cache aman sejak URL frontend/aset dipindah ke config/evomi.php.
 * Jangan kembalikan env() ke controller atau mailable: begitu config di-cache
 * Laravel berhenti memuat .env, env() jadi null, dan tautan email diam-diam
 * jatuh ke localhost.
 */
$cacheOk = true;
foreach (['config:cache', 'route:cache', 'view:cache'] as $cmd) {
    try {
        Illuminate\Support\Facades\Artisan::call($cmd);
        echo $cmd . ' ok' . PHP_EOL;
    } catch (Throwable $e) {
        $cacheOk = false;
        echo $cmd . ' error: ' . $e->getMessage() . PHP_EOL;
        break;
    }
}

/*
 * Jaring pengaman: deploy ini berjalan lewat HTTP tanpa akses SSH. Kalau satu
 * langkah cache gagal, situs bisa terkunci pada konfigurasi setengah jadi dan
 * sulit dipulihkan. Buang cache-nya supaya Laravel kembali membaca .env apa
 * adanya - lebih lambat, tapi hidup.
 */
if (! $cacheOk) {
    foreach (['config:clear', 'route:clear', 'view:clear'] as $cmd) {
        try {
            Illuminate\Support\Facades\Artisan::call($cmd);
            echo 'rollback ' . $cmd . ' ok' . PHP_EOL;
        } catch (Throwable $e) {
            echo 'rollback ' . $cmd . ' error: ' . $e->getMessage() . PHP_EOL;
        }
    }
}

echo 'cache_built=' . ($cacheOk ? 'yes' : 'no (rolled back)') . PHP_EOL;

echo "copied_files=$total\n";
echo "done\n";

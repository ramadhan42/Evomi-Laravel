<?php

/**
 * Deploy Evomi langsung dari GitHub.
 *
 * Taruh SATU berkas ini di ~/public_html/, lalu buka:
 *   https://evomi.shop/evomi-deploy.php?key=<EVOMI_SYNC_KEY>
 *
 * Script mengunduh rilis dari repo, menyalin kode, gambar, dan aset build ke
 * tempatnya, menjalankan migrasi, lalu membangun cache. Tidak ada berkas yang
 * perlu diunggah manual selain berkas ini sendiri.
 *
 * Hapus berkas ini dari server setelah dipakai.
 */

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
@set_time_limit(600);
@ini_set('memory_limit', '512M');

$docRoot = __DIR__;
$laravel = dirname($docRoot) . '/laravel';

/*
 * Satu berkas ini melayani lebih dari satu domain di akun yang sama.
 *
 * Tanpa ?site=, ia men-deploy domain tempat ia diunggah. Dengan ?site=<domain>,
 * ia men-deploy domain tetangga di ~/domains/<domain>. Itu dipakai untuk
 * evomi.id, yang tidak punya akun FTP sendiri - akun FTP yang ada terkunci di
 * docroot evomi.shop, jadi berkas ini tidak bisa diunggah langsung ke sana.
 *
 * Keduanya milik satu pengguna Unix yang sama, jadi tidak ada batas hak akses
 * yang dilewati. Kuncinya tetap diperiksa setelah ini, dan dibaca dari .env
 * milik domain yang dituju.
 */
$site = trim((string) ($_GET['site'] ?? ''));

if ($site !== '') {
    // Hanya nama domain sederhana - menutup upaya menaiki direktori.
    if (! preg_match('/^[a-z0-9][a-z0-9.-]*$/i', $site) || str_contains($site, '..')) {
        fail('nama site tidak valid');
    }

    $base = dirname(dirname($docRoot)) . '/' . $site;
    $docRoot = $base . '/public_html';
    $laravel = $base . '/laravel';

    if (! is_dir($docRoot) || ! is_dir($laravel)) {
        fail("site tidak ditemukan di akun ini: $site");
    }
}

function out(string $line): void
{
    echo $line, "\n";
    @ob_flush();
    @flush();
}

function fail(string $line, int $status = 500): never
{
    http_response_code($status);
    out('GAGAL: ' . $line);
    exit(1);
}

/* ------------------------------------------------------------------ *
 * 1. Autentikasi
 *
 * Kunci dibaca langsung dari .env supaya pemeriksaan selesai sebelum
 * Laravel di-boot, dan tetap benar walaupun config sudah di-cache.
 * ------------------------------------------------------------------ */

function envValue(string $envFile, string $name, string $default = ''): string
{
    if (! is_readable($envFile)) {
        return $default;
    }

    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);

        if ($line === '' || $line[0] === '#' || ! str_starts_with($line, $name . '=')) {
            continue;
        }

        $value = trim(substr($line, strlen($name) + 1));

        if (strlen($value) >= 2
            && ($value[0] === '"' || $value[0] === "'")
            && $value[strlen($value) - 1] === $value[0]) {
            $value = substr($value, 1, -1);
        }

        return $value;
    }

    return $default;
}

if (! is_dir($laravel)) {
    fail("folder laravel tidak ditemukan: $laravel");
}

$envFile = $laravel . '/.env';
$expectedKey = envValue($envFile, 'EVOMI_SYNC_KEY');

if ($expectedKey === '') {
    fail('EVOMI_SYNC_KEY belum diset di laravel/.env', 503);
}

if (! hash_equals($expectedKey, (string) ($_GET['key'] ?? ''))) {
    http_response_code(403);
    out('forbidden');
    exit;
}

/* ------------------------------------------------------------------ *
 * 2. Unduh rilis dari GitHub
 * ------------------------------------------------------------------ */

$repo = envValue($envFile, 'EVOMI_DEPLOY_REPO', 'ramadhan42/Evomi-Laravel');
$ref = envValue($envFile, 'EVOMI_DEPLOY_REF', 'perf/webp-and-bundle-split');
$zipUrl = "https://codeload.github.com/$repo/zip/refs/heads/$ref";

out("repo   : $repo");
out("ref    : $ref");
out('mengunduh rilis...');

$work = $laravel . '/storage/app/evomi-deploy';

// Sisa jalanan sebelumnya dibersihkan supaya tidak tercampur.
if (is_dir($work)) {
    rrmdir($work);
}

if (! @mkdir($work, 0755, true) && ! is_dir($work)) {
    fail("tidak bisa membuat folder kerja: $work");
}

$zipPath = $work . '/release.zip';
$bytes = download($zipUrl, $zipPath);

if ($bytes <= 0) {
    fail('unduhan kosong. Pastikan repo publik dan server mengizinkan koneksi keluar ke github.com');
}

out(sprintf('terunduh: %.1f MB', $bytes / 1048576));

/* ------------------------------------------------------------------ *
 * 3. Ekstrak
 * ------------------------------------------------------------------ */

if (! class_exists('ZipArchive')) {
    fail('ekstensi PHP zip tidak tersedia di server ini');
}

$zip = new ZipArchive();

if ($zip->open($zipPath) !== true) {
    fail('gagal membuka arsip yang diunduh');
}

$rootEntry = $zip->getNameIndex(0);
$zip->extractTo($work);
$zip->close();
@unlink($zipPath);

$src = rtrim($work . '/' . $rootEntry, '/');

if (! is_dir($src)) {
    fail("hasil ekstrak tidak ditemukan: $src");
}

out('diekstrak: ' . basename($src));

/* ------------------------------------------------------------------ *
 * 4. Salin ke tempatnya
 *
 * Hanya subpath yang didaftarkan di bawah yang disentuh. Script tidak
 * pernah menghapus folder tujuan, jadi berkas lama tetap ada sebagai
 * jaring pengaman sampai dihapus manual.
 * ------------------------------------------------------------------ */

$targets = [
    // [ sumber di repo, tujuan ]
    ['app', $laravel . '/app'],
    ['bootstrap/app.php', $laravel . '/bootstrap/app.php'],
    ['config', $laravel . '/config'],
    ['database', $laravel . '/database'],
    ['routes', $laravel . '/routes'],
    ['resources', $laravel . '/resources'],

    // Aset build: dibutuhkan Laravel (manifest) dan docroot (URL publik)
    ['public/build', $laravel . '/public/build'],
    ['public/build', $docRoot . '/build'],

    // Gambar: docroot yang melayani /src/images
    ['public/src', $laravel . '/public/src'],
    ['public/src', $docRoot . '/src'],

    // Video layar loading: docroot yang melayani /videos/loading-screen.mp4
    ['public/videos', $laravel . '/public/videos'],
    ['public/videos', $docRoot . '/videos'],

    // Media unggahan yang ikut repo
    ['storage/app/public', $laravel . '/storage/app/public'],
];

$total = 0;

foreach ($targets as [$rel, $dst]) {
    $from = $src . '/' . $rel;

    if (! file_exists($from)) {
        out("lewati (tidak ada di repo): $rel");
        continue;
    }

    $n = copyPath($from, $dst);
    $total += $n;
    out(sprintf('%-22s -> %-28s %5d berkas', $rel, shorten($dst, $laravel, $docRoot), $n));
}

out("total berkas disalin: $total");

rrmdir($work);

/* ------------------------------------------------------------------ *
 * 5. Migrasi + cache
 * ------------------------------------------------------------------ */

require $laravel . '/vendor/autoload.php';
$app = require $laravel . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (['view:clear', 'route:clear', 'config:clear', 'cache:clear'] as $cmd) {
    artisan($cmd);
}

foreach (glob($laravel . '/storage/framework/views/*.php') ?: [] as $file) {
    @unlink($file);
}

// Unggahan di storage/app/public tidak ikut repo, jadi tiap server mengonversi
// berkasnya sendiri. Wajib sebelum migrate: migrasi hanya mengubah path di
// database bila berkas .webp-nya sudah benar-benar ada di disk.
out('--- konversi gambar unggahan ---');

try {
    Illuminate\Support\Facades\Artisan::call('evomi:images-to-webp');
    out(trim(Illuminate\Support\Facades\Artisan::output()));
} catch (Throwable $e) {
    out('konversi error: ' . $e->getMessage());
}

out('--- migrate ---');

try {
    Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    out(trim(Illuminate\Support\Facades\Artisan::output()));
} catch (Throwable $e) {
    out('migrate error: ' . $e->getMessage());
}

out('--- cache ---');

$cacheOk = true;

foreach (['config:cache', 'route:cache', 'view:cache'] as $cmd) {
    if (! artisan($cmd)) {
        $cacheOk = false;
        break;
    }
}

// Deploy ini berjalan lewat HTTP tanpa akses SSH. Kalau satu langkah cache
// gagal, situs bisa terkunci pada konfigurasi setengah jadi dan sulit
// dipulihkan. Buang cache-nya supaya Laravel kembali membaca .env apa adanya.
if (! $cacheOk) {
    foreach (['config:clear', 'route:clear', 'view:clear'] as $cmd) {
        artisan($cmd);
    }
}

out('cache_built=' . ($cacheOk ? 'yes' : 'no (rolled back)'));
out('');
out('SELESAI. Hapus berkas ini dari public_html sekarang.');

/* ------------------------------------------------------------------ *
 * Helper
 * ------------------------------------------------------------------ */

function artisan(string $cmd): bool
{
    try {
        Illuminate\Support\Facades\Artisan::call($cmd);
        out("$cmd ok");

        return true;
    } catch (Throwable $e) {
        out("$cmd error: " . $e->getMessage());

        return false;
    }
}

function download(string $url, string $dest): int
{
    if (function_exists('curl_init')) {
        $fh = fopen($dest, 'wb');

        if ($fh === false) {
            fail("tidak bisa menulis: $dest");
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fh,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_USERAGENT => 'evomi-deploy',
        ]);
        $ok = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        fclose($fh);

        if (! $ok || $code !== 200) {
            fail("unduh gagal (HTTP $code) $err");
        }

        return (int) filesize($dest);
    }

    $data = @file_get_contents($url);

    if ($data === false) {
        fail('curl maupun allow_url_fopen tidak tersedia untuk mengunduh rilis');
    }

    file_put_contents($dest, $data);

    return strlen($data);
}

function copyPath(string $from, string $dst): int
{
    if (is_file($from)) {
        @mkdir(dirname($dst), 0755, true);

        return @copy($from, $dst) ? 1 : 0;
    }

    if (! is_dir($dst) && ! @mkdir($dst, 0755, true) && ! is_dir($dst)) {
        return 0;
    }

    $count = 0;

    foreach (scandir($from) ?: [] as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }

        $count += copyPath($from . '/' . $name, $dst . '/' . $name);
    }

    return $count;
}

function rrmdir(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }

    foreach (scandir($dir) ?: [] as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }

        $path = $dir . '/' . $name;
        is_dir($path) ? rrmdir($path) : @unlink($path);
    }

    @rmdir($dir);
}

function shorten(string $path, string $laravel, string $docRoot): string
{
    if (str_starts_with($path, $laravel)) {
        return 'laravel' . substr($path, strlen($laravel));
    }

    if (str_starts_with($path, $docRoot)) {
        return 'public_html' . substr($path, strlen($docRoot));
    }

    return $path;
}

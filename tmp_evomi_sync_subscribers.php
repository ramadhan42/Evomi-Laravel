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
require $laravel . '/vendor/autoload.php';
$app = require $laravel . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function copyFile(string $src, string $dst): bool
{
    if (! is_file($src)) {
        echo "missing $src\n";

        return false;
    }
    if (! is_dir(dirname($dst))) {
        mkdir(dirname($dst), 0755, true);
    }
    if (! copy($src, $dst)) {
        echo "copy fail $dst\n";

        return false;
    }
    echo 'copied '.basename($dst).' sha='.sha1_file($dst)."\n";

    return true;
}

copyFile($docRoot.'/view-sync/subscribers.blade.php', resource_path('views/dashboard/subscribers.blade.php'));
copyFile($docRoot.'/view-sync/NewsletterController.php', $laravel.'/app/Http/Controllers/Api/NewsletterController.php');

$buildSrc = $docRoot.'/view-sync/build';
$targets = [public_path('build'), $docRoot.'/build'];
foreach ($targets as $dstRoot) {
    if (! is_dir($dstRoot)) {
        mkdir($dstRoot, 0755, true);
    }
    copyFile($buildSrc.'/manifest.json', $dstRoot.'/manifest.json');
    if (! is_dir($dstRoot.'/assets')) {
        mkdir($dstRoot.'/assets', 0755, true);
    }
    if (is_dir($buildSrc.'/assets')) {
        foreach (scandir($buildSrc.'/assets') as $f) {
            if ($f === '.' || $f === '..') {
                continue;
            }
            copyFile($buildSrc.'/assets/'.$f, $dstRoot.'/assets/'.$f);
        }
    }
}

$kernel->call('view:clear');
$ctrl = new App\Http\Controllers\Api\NewsletterController();
$payload = $ctrl->index()->getData(true);
echo 'subscribers_count='.count($payload['data'] ?? [])."\n";
echo 'success_key='.(! empty($payload['success']) ? 'yes' : 'no')."\n";
$blade = file_get_contents(resource_path('views/dashboard/subscribers.blade.php'));
echo 'blade_uses_x_show_empty='.(str_contains($blade, 'x-show="pagedItems().length === 0"') ? 'yes' : 'no')."\n";
$manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
echo 'public_js='.($manifest['resources/js/app.js']['file'] ?? '-')."\n";
echo "ok\n";

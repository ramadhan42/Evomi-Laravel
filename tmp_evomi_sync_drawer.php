<?php
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
if ((['key'] ?? '') !== 'evomi-nav-sync-2026') {
    http_response_code(403);
    echo "forbidden\n";
    exit;
}

 = __DIR__;
 = dirname() . '/laravel';
 =  . '/code-sync';
 =  . '/build-sync';

if (! is_dir()) {
    echo "missing laravel dir: \n";
    exit(1);
}

function copyTree(string , string , string  = ''): int
{
     = 0;
    D:\Documents\Rama\Folder Latihan\Evomi-Laravel\tmp_evomi_sync_drawer.php =  . ( !== '' ? '/' .  : '');
    if (! is_dir(D:\Documents\Rama\Folder Latihan\Evomi-Laravel\tmp_evomi_sync_drawer.php)) {
        return 0;
    }
    foreach (scandir(D:\Documents\Rama\Folder Latihan\Evomi-Laravel\tmp_evomi_sync_drawer.php) ?: [] as ) {
        if ( === '.' ||  === '..') {
            continue;
        }
         =  !== '' ?  . '/' .  : ;
         =  . '/' . ;
         =  . '/' . ;
        if (is_dir()) {
            if (! is_dir()) {
                mkdir(, 0755, true);
            }
             += copyTree(, , );
            continue;
        }
        if (! is_dir(dirname())) {
            mkdir(dirname(), 0755, true);
        }
        if (! copy(, )) {
            echo "FAIL \n";
            continue;
        }
        echo "OK \n";
        ++;
    }

    return ;
}

 = 0;
if (is_dir()) {
    echo "=== code-sync ===\n";
     += copyTree(, );
} else {
    echo "missing code-sync\n";
}

if (is_dir()) {
    echo "=== build-sync ===\n";
     =  . '/public/build';
     += copyTree(, );
} else {
    echo "missing build-sync\n";
}

require  . '/vendor/autoload.php';
 = require  . '/bootstrap/app.php';
 = ->make(Illuminate\\Contracts\\Console\\Kernel::class);
->bootstrap();

foreach (['view:clear', 'route:clear', 'config:clear'] as ) {
    try {
        Illuminate\\Support\\Facades\\Artisan::call();
        echo " ok\n";
    } catch (Throwable ) {
        echo " error: " . ->getMessage() . "\n";
    }
}

echo "copied_files=\n";
echo "done\n";
?>

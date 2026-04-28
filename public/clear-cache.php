<?php
// Clear all Laravel caches
$base = dirname(__DIR__);
require $base . '/vendor/autoload.php';
$app = require $base . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->call('optimize:clear');
echo "<pre>✅ Cache cleared!\n";
echo $kernel->output();
echo "\nPage: <a href='/admin/dashboard'>/admin/dashboard</a></pre>";

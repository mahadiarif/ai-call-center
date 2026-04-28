<?php
// One-time migration runner — DELETE this file after use!
// Visit: http://ai-call-center.test/run-migration.php

define('LARAVEL_START', microtime(true));
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "<pre style='font-family:monospace;padding:20px;background:#1e1e2e;color:#cdd6f4;'>";
echo "=== Running Migrations ===\n\n";

try {
    $artisan = $app->make('Illuminate\Contracts\Console\Kernel');

    // Run pending migrations
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    echo \Illuminate\Support\Facades\Artisan::output();
    echo "\n✅ Migration complete!\n\n";

    // Create storage link
    try {
        \Illuminate\Support\Facades\Artisan::call('storage:link');
        echo \Illuminate\Support\Facades\Artisan::output();
        echo "✅ Storage link created!\n\n";
    } catch (\Exception $e) {
        echo "⚠️ Storage link: " . $e->getMessage() . "\n";
    }

    echo "\n🎉 Done! Now DELETE this file: public/run-migration.php\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
echo "</pre>";

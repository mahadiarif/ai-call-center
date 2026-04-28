<?php
// Debug logo - DELETE after use
// Visit: http://ai-call-center.test/debug-logo.php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$profile = \App\Models\CompanyProfile::where('is_active', true)->first();

echo "<pre style='background:#1e1e2e;color:#cdd6f4;padding:20px;font-family:monospace'>";
if (!$profile) { echo "❌ No active company profile found!\n"; exit; }

$logo = $profile->company_logo;
echo "DB company_logo value: " . ($logo ?: '(empty)') . "\n\n";

if ($logo) {
    $storagePath = storage_path('app/public/' . $logo);
    echo "File expected at: {$storagePath}\n";
    echo "File exists: " . (file_exists($storagePath) ? '✅ YES' : '❌ NO') . "\n\n";
    
    $url1 = asset('storage/' . $logo);
    $url2 = \Illuminate\Support\Facades\Storage::disk('public')->url($logo);
    echo "URL (asset): {$url1}\n";
    echo "URL (Storage): {$url2}\n\n";
    
    // List files in company-logos dir
    $dir = storage_path('app/public/company-logos');
    echo "Files in company-logos dir:\n";
    if (is_dir($dir)) {
        foreach (scandir($dir) as $f) {
            if ($f !== '.' && $f !== '..') echo "  - {$f}\n";
        }
    } else {
        echo "  ❌ Directory does not exist!\n";
    }
    
    echo "\n<img src='{$url2}' style='max-height:80px;border:2px solid lime'> ← Logo preview\n";
}
echo "</pre>";

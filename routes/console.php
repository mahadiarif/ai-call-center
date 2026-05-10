<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ══════════════════════════════════════════════════════════════
// 🔄 Client API Auto-Sync — every hour (configurable per integration)
// ══════════════════════════════════════════════════════════════
Schedule::command('client:sync')->hourly()->withoutOverlapping();

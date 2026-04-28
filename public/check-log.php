<?php
// Check recent Laravel errors
$logFile = dirname(__DIR__) . '/storage/logs/laravel.log';
if (!file_exists($logFile)) {
    echo "Log file not found!";
    exit;
}
// Get last 100 lines
$lines = file($logFile);
$last100 = array_slice($lines, -100);
echo "<pre style='font-size:11px;background:#111;color:#0f0;padding:10px;'>";
foreach ($last100 as $line) {
    $line = htmlspecialchars($line);
    if (strpos($line, 'ERROR') !== false || strpos($line, 'Error') !== false) {
        echo "<span style='color:red;font-weight:bold;'>" . $line . "</span>";
    } elseif (strpos($line, 'rejected') !== false || strpos($line, 'skipped') !== false) {
        echo "<span style='color:yellow;'>" . $line . "</span>";
    } else {
        echo $line;
    }
}
echo "</pre>";

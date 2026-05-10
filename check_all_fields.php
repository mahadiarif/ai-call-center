<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=ai_call_center;charset=utf8mb4', 'root', '');
$pdo->exec("SET NAMES utf8mb4");
$row = $pdo->query("SELECT required_fields FROM ivr_services WHERE id=4")->fetch(PDO::FETCH_ASSOC);
$fields = json_decode($row['required_fields'], true);
echo "=== ALL IVR FIELDS ===\n";
foreach ($fields as $i => $f) {
    preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)/', trim($f['field_name'] ?? ''), $m);
    $key = $m[1] ?? '?';
    $retries = $f['max_retries'] ?? '?';
    $mandatory = $f['is_mandatory'] ? 'YES' : 'NO';
    $blocking = $f['is_blocking'] ? 'YES' : 'NO';
    $instr = mb_substr($f['ai_instruction'] ?? 'EMPTY', 0, 60);
    echo "[$i] $key | retries=$retries | mandatory=$mandatory | blocking=$blocking\n";
    echo "    ai_instruction: $instr\n";
}

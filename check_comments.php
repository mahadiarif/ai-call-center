<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=ai_call_center;charset=utf8mb4', 'root', '');
$pdo->exec("SET NAMES utf8mb4");
$row = $pdo->query("SELECT required_fields FROM ivr_services WHERE id=4")->fetch(PDO::FETCH_ASSOC);
$fields = json_decode($row['required_fields'], true);
foreach ($fields as $f) {
    preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)/', trim($f['field_name'] ?? ''), $m);
    $key = $m[1] ?? '';
    if (strtolower($key) === 'comments') {
        echo "field_name: " . $f['field_name'] . "\n";
        echo "max_retries: " . $f['max_retries'] . "\n";
        echo "ai_instruction: " . $f['ai_instruction'] . "\n";
        echo "is_mandatory: " . ($f['is_mandatory'] ? 'true' : 'false') . "\n";
        echo "is_blocking: " . ($f['is_blocking'] ? 'true' : 'false') . "\n";
    }
}

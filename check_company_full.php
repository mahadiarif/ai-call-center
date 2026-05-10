<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=ai_call_center;charset=utf8mb4', 'root', '');
$pdo->exec("SET NAMES utf8mb4");
$row = $pdo->query("SELECT * FROM company_profiles WHERE is_active=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
foreach ($row as $k => $v) {
    if (in_array($k, ['id','created_at','updated_at','is_active'])) continue;
    echo "=== $k ===\n$v\n\n";
}

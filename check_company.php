<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=ai_call_center;charset=utf8mb4', 'root', '');
$pdo->exec("SET NAMES utf8mb4");
$row = $pdo->query("SELECT id, company_name, global_persona, greeting_behavior, ai_tone_guidelines, ai_special_knowledge, ai_forbidden_topics FROM company_profiles WHERE is_active=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
foreach ($row as $k => $v) {
    echo "$k: " . mb_substr((string)$v, 0, 200) . "\n---\n";
}

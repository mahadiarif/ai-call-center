<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=ai_call_center;charset=utf8mb4', 'root', '');
$pdo->exec("SET NAMES utf8mb4");
$row = $pdo->query("SELECT ai_name, greeting_message, voice_gender FROM ivr_services WHERE id=4")->fetch(PDO::FETCH_ASSOC);
foreach ($row as $k => $v) {
    echo "$k: $v\n";
}

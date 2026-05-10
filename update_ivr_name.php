<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=ai_call_center;charset=utf8mb4', 'root', '');
$pdo->exec("SET NAMES utf8mb4");

$stmt = $pdo->prepare("UPDATE ivr_services SET 
    ai_name = 'রাফি',
    greeting_message = 'আসসালামু আলাইকুম, ওয়ালটন কাস্টমার কেয়ার থেকে বলছি। কীভাবে আপনাকে সাহায্য করতে পারি?'
    WHERE id = 4");
$ok = $stmt->execute();
echo $ok ? "✅ IVR updated!\n" : "❌ Failed!\n";

$r = $pdo->query("SELECT ai_name, greeting_message, voice_gender FROM ivr_services WHERE id=4")->fetch(PDO::FETCH_ASSOC);
echo "ai_name: " . $r['ai_name'] . "\n";
echo "greeting: " . $r['greeting_message'] . "\n";
echo "voice: " . $r['voice_gender'] . "\n";

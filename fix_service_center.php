<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=ai_call_center;charset=utf8mb4', 'root', '');
$pdo->exec("SET NAMES utf8mb4");

$row = $pdo->query("SELECT required_fields FROM ivr_services WHERE id=4")->fetch(PDO::FETCH_ASSOC);
$fields = json_decode($row['required_fields'], true);

// Find service_center index
$idx = null;
foreach ($fields as $i => $f) {
    $key = $f['field_name'] ?? '';
    preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)/', trim($key), $m);
    if (($m[1] ?? '') === 'service_center') { $idx = $i; break; }
}

if ($idx === null) { echo "service_center field not found!\n"; exit(1); }

// Fix the field
$fields[$idx]['ai_instruction']    = 'Sir, আপনার নিকটতম কোন Walton সার্ভিস সেন্টার থেকে সার্ভিস নিতে চান? যেমন: মিরপুর, গুলশান, চট্টগ্রাম।';
$fields[$idx]['indirect_question'] = 'Sir, এলাকার নাম বললেই হবে — আমরা নিকটতম সেন্টার থেকে যোগাযোগ করব।';
$fields[$idx]['convincing_logic']  = 'আপনার এলাকা জানলে দ্রুত সার্ভিস পৌঁছে দেওয়া সম্ভব।';
$fields[$idx]['is_blocking']       = false;  // না বললে skip করবে
$fields[$idx]['is_mandatory']      = false;  // optional
$fields[$idx]['max_retries']       = 1;      // শুধু একবার জিজ্ঞেস

$pdo->prepare("UPDATE ivr_services SET required_fields=? WHERE id=4")
    ->execute([json_encode($fields, JSON_UNESCAPED_UNICODE)]);

echo "✅ service_center field fixed!\n";
echo "  ai_instruction: " . $fields[$idx]['ai_instruction'] . "\n";
echo "  is_mandatory: false, is_blocking: false\n";

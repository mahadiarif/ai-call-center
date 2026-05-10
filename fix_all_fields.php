<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=ai_call_center;charset=utf8mb4', 'root', '');
$pdo->exec("SET NAMES utf8mb4");

$row = $pdo->query("SELECT required_fields FROM ivr_services WHERE id=4")->fetch(PDO::FETCH_ASSOC);
$fields = json_decode($row['required_fields'], true);

foreach ($fields as $i => &$f) {
    preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)/', trim($f['field_name'] ?? ''), $m);
    $key = strtolower($m[1] ?? '');

    // FIX 1: district — developer text ছিল ai_instruction এ, proper question দাও
    if ($key === 'district') {
        $f['ai_instruction']    = 'Sir, আপনি কোন জেলায় থাকেন?';
        $f['indirect_question'] = 'Sir, আপনার এলাকার নামটা বলুন, আমরা জেলা বের করব।';
        $f['is_blocking']       = false;   // optional, skip করলেই হয়
        $f['is_mandatory']      = false;
        echo "[✅] district: ai_instruction fixed, blocking=false\n";
    }

    // FIX 2: alt_mobile_number — blocking হওয়া উচিত না, optional
    if ($key === 'alt_mobile_number') {
        $f['is_blocking']  = false;
        $f['is_mandatory'] = false;
        $f['max_retries']  = 1;
        echo "[✅] alt_mobile_number: blocking=false, mandatory=false\n";
    }

    // FIX 3: service_center — correct question
    if ($key === 'service_center') {
        $f['ai_instruction']    = 'Sir, আপনার নিকটতম কোন Walton সার্ভিস সেন্টার থেকে সার্ভিস নিতে চান? যেমন: মিরপুর, গুলশান, চট্টগ্রাম।';
        $f['indirect_question'] = 'Sir, এলাকার নাম বললেই হবে — আমরা নিকটতম সেন্টার থেকে যোগাযোগ করব।';
        $f['convincing_logic']  = null;
        $f['is_blocking']       = false;
        $f['is_mandatory']      = false;
        echo "[✅] service_center: correct question, blocking=false\n";
    }

    // FIX 4: Comments — AI generate করবে, কাস্টমারকে জিজ্ঞেস করার দরকার নেই
    if ($key === 'comments') {
        $f['ai_instruction'] = 'অবশ্যই ভালো একটা মতামত লিখবে তুমি।';
        $f['is_blocking']    = false;
        $f['is_mandatory']   = false;
        $f['max_retries']    = 0;  // AI generates, don't ask customer
        echo "[✅] comments: max_retries=0 (AI auto-generate), blocking=false\n";
    }
}
unset($f);

$json = json_encode($fields, JSON_UNESCAPED_UNICODE);
$stmt = $pdo->prepare("UPDATE ivr_services SET required_fields=? WHERE id=4");
$ok = $stmt->execute([$json]);
echo $ok ? "\n✅ DB updated successfully!\n" : "\n❌ DB UPDATE FAILED!\n";

// Verify
echo "\n=== VERIFICATION ===\n";
$row2 = $pdo->query("SELECT required_fields FROM ivr_services WHERE id=4")->fetch(PDO::FETCH_ASSOC);
$fields2 = json_decode($row2['required_fields'], true);
foreach ($fields2 as $i => $f) {
    preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)/', trim($f['field_name'] ?? ''), $m);
    $key = strtolower($m[1] ?? '?');
    $instr = mb_substr($f['ai_instruction'] ?? 'EMPTY', 0, 55);
    $b = $f['is_blocking'] ? 'BLOCK' : 'skip';
    $mand = $f['is_mandatory'] ? 'MUST' : 'opt';
    $r = $f['max_retries'] ?? '?';
    echo "[$i] $key | $b | $mand | retry=$r | $instr\n";
}

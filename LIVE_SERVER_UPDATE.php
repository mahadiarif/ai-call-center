<?php
// ==========================================
// LIVE SERVER DB UPDATE SCRIPT
// Run this ONCE on live server via browser or SSH
// ==========================================

$pdo = new PDO('mysql:host=127.0.0.1;dbname=ai_call_center;charset=utf8mb4', 'root', '');
// ⚠️ Live server-এ password থাকলে: new PDO(..., 'root', 'YOUR_PASSWORD')
$pdo->exec("SET NAMES utf8mb4");

$row = $pdo->query("SELECT required_fields FROM ivr_services WHERE id=4")->fetch(PDO::FETCH_ASSOC);
$fields = json_decode($row['required_fields'], true);

foreach ($fields as $i => &$f) {
    preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)/', trim($f['field_name'] ?? ''), $m);
    $key = strtolower($m[1] ?? '');

    if ($key === 'district') {
        $f['ai_instruction']    = 'Sir, আপনি কোন জেলায় থাকেন?';
        $f['indirect_question'] = 'Sir, আপনার এলাকার নামটা বলুন, আমরা জেলা বের করব।';
        $f['is_blocking']       = false;
        $f['is_mandatory']      = false;
        echo "[✅] district fixed\n";
    }
    if ($key === 'alt_mobile_number') {
        $f['is_blocking']  = false;
        $f['is_mandatory'] = false;
        $f['max_retries']  = 1;
        echo "[✅] alt_mobile_number: optional\n";
    }
    if ($key === 'service_center') {
        $f['ai_instruction']    = 'Sir, আপনার নিকটতম কোন Walton সার্ভিস সেন্টার থেকে সার্ভিস নিতে চান? যেমন: মিরপুর, গুলশান, চট্টগ্রাম।';
        $f['indirect_question'] = 'Sir, এলাকার নাম বললেই হবে — আমরা নিকটতম সেন্টার থেকে যোগাযোগ করব।';
        $f['convincing_logic']  = null;
        $f['is_blocking']       = false;
        $f['is_mandatory']      = false;
        echo "[✅] service_center: correct question\n";
    }
    if ($key === 'comments') {
        $f['is_blocking']  = false;
        $f['is_mandatory'] = false;
        $f['max_retries']  = 0;
        echo "[✅] comments: AI auto-generate\n";
    }
}
unset($f);

$pdo->prepare("UPDATE ivr_services SET required_fields=? WHERE id=4")
    ->execute([json_encode($fields, JSON_UNESCAPED_UNICODE)]);

// Company Profile update
$pdo->prepare("UPDATE company_profiles SET
    global_persona = ?,
    ai_tone_guidelines = ?,
    ai_special_knowledge = ?,
    ai_forbidden_topics = ?
    WHERE is_active = 1")->execute([
    "তুমি ওয়ালটন বাংলাদেশের একজন অভিজ্ঞ ও বিনয়ী কাস্টমার কেয়ার প্রতিনিধি।\n\nতোমার কাজ:\n- কাস্টমারের সমস্যা মনোযোগ দিয়ে শোনা\n- সার্ভিস রিকোয়েস্টের জন্য প্রয়োজনীয় তথ্য সংগ্রহ করা\n- কাস্টমারকে নিশ্চিত করা যে তার সমস্যার সমাধান হবে\n\nপ্রতিটি উত্তর ২-৩ বাক্যের বেশি হবে না।",
    "ভাষা: ১০০% বাংলাদেশী বাংলা — 'স্যার', 'ম্যাম', 'জ্বী', 'আচ্ছা', 'ধন্যবাদ'\nনিষেধ: 'দাদা', 'দিদি', 'জল' — কখনো বলবে না\n\nকথার ধরন:\n- উষ্ণ ও আন্তরিক: \"জ্বী স্যার, আমি বুঝতে পারছি।\"\n- সংক্ষিপ্ত confirm: \"আচ্ছা স্যার, নোট করলাম।\"\n- সমাপ্তি: \"ধন্যবাদ স্যার, আমাদের টিম শীঘ্রই যোগাযোগ করবে।\"",
    "কলের শুরুতে IVR greeting message হুবহু বলবে।\n\nতথ্য সংগ্রহের কৌশল:\n- একটা একটা করে জিজ্ঞেস করবে\n- কাস্টমার উত্তর দিলে: \"জ্বী স্যার, নোট করলাম।\" তারপর পরেরটা\n- কাস্টমার না দিলে: একবার চেষ্টা করো, তারপর skip\n\nসব field শেষে: \"আপনার সার্ভিস রিকোয়েস্ট সিস্টেমে সেভ হয়েছে। আমাদের টিম শীঘ্রই যোগাযোগ করবে। ধন্যবাদ স্যার।\"",
    "- নিজে থেকে repair পরামর্শ দেবে না\n- রাজনীতি, ধর্ম, অন্য ব্র্যান্ড নিয়ে কথা বলবে না\n- নিজেকে AI বলে পরিচয় দেবে না\n- হিন্দি, আরবি ভাষায় কথা বলবে না",
]);

echo "\n✅ ALL DB UPDATES DONE!\n";
echo "Now delete this file from server for security.\n";

<?php
$file = __DIR__ . '/app/Http/Controllers/AIFormController.php';
$content = file_get_contents($file);

// Find the line with 'alt_mobile_number must be DIFFERENT'
$searchStr = 'alt_mobile_number must be DIFFERENT from primary mobile / caller number';
$pos = strpos($content, $searchStr);
if ($pos === false) {
    echo "NOT FOUND\n";
    exit(1);
}

// Find the start of that full line (go back to find \n)
$lineStart = strrpos(substr($content, 0, $pos), "\n") + 1;
echo "Found at pos: $pos\n";
echo "Line start: $lineStart\n";
echo "Line content: " . substr($content, $lineStart, 120) . "\n\n";

// New code to insert BEFORE that line
$newCode = <<<'PHP'
                // Recovery: agent "ধন্যবাদ X স্যার" pattern থেকে customer_name recover করো
                // Customer "assan" বললে AI "আহসান" extract করে কিন্তু anti-hallucination remove করে
                // Agent "ধন্যবাদ আহসান স্যার" বললে সেটাই verified name
                if (empty($data['customer_name'])) {
                    $nameSkipWords = ['আপনার','আপনি','তার','এই','সেই','একটু','আমাদের','তোমার','আমার','সাথে'];
                    foreach (explode("\n", $transcript) as $tl) {
                        if (mb_strpos($tl, 'এজেন্ট:') === false) continue;
                        if (preg_match('/(?:ধন্যবাদ|জ্বী|আচ্ছা)\s+([\p{L}]+(?:\s+[\p{L}]+)?)\s+(?:স্যার|ম্যাম|ভাই|আপু)/u', $tl, $m)) {
                            $candidate = trim($m[1]);
                            if (!in_array(mb_strtolower($candidate), $nameSkipWords) && mb_strlen($candidate) >= 2) {
                                $data['customer_name'] = $candidate;
                                break;
                            }
                        }
                    }
                }

                // Recovery: product_name empty হলে transcript keyword থেকে বের করো
                if (empty($data['product_name'])) {
                    $productMap = [
                        'ফ্রিজ'=>'ফ্রিজ','রেফ্রিজারেটর'=>'ফ্রিজ','freeze'=>'ফ্রিজ','fridge'=>'ফ্রিজ',
                        'refrigerator'=>'ফ্রিজ','naasht'=>'ফ্রিজ',
                        'এসি'=>'এসি','air conditioner'=>'এসি',
                        'টিভি'=>'টিভি','television'=>'টিভি',
                        'ওয়াশিং মেশিন'=>'ওয়াশিং মেশিন','washing machine'=>'ওয়াশিং মেশিন',
                        'ওভেন'=>'ওভেন','oven'=>'ওভেন','microwave'=>'মাইক্রোওয়েভ',
                        'ফ্যান'=>'ফ্যান','fan'=>'ফ্যান',
                        'রাইস কুকার'=>'রাইস কুকার','rice cooker'=>'রাইস কুকার',
                        'ব্লেন্ডার'=>'ব্লেন্ডার','blender'=>'ব্লেন্ডার',
                        'আয়রন'=>'আয়রন','iron'=>'আয়রন',
                        'মোটর'=>'মোটর','motor'=>'মোটর',
                    ];
                    $tLower = mb_strtolower($transcript);
                    foreach ($productMap as $keyword => $productName) {
                        if (mb_strpos($tLower, $keyword) !== false) {
                            $data['product_name'] = $productName;
                            break;
                        }
                    }
                }

PHP;

// Insert before the found line
$newContent = substr($content, 0, $lineStart) . $newCode . substr($content, $lineStart);
file_put_contents($file, $newContent);
echo "SUCCESS: " . strlen($newCode) . " bytes inserted at line start $lineStart\n";

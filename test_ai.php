<?php
require_once __DIR__ . '/lib/helpers.php';

header('Content-Type: text/plain');

$apiKey = GEMINI_API_KEY;
$service = "Web Development";
$desc = "Create a modern portfolio website for a design agency.";

$prompt = "You are a professional IT consultant and digital agency estimator.
Analyze the market pricing and structure for the following project request:
Service: {$service}
Project Description: \"{$desc}\"

Tasks:
1. Determine a realistic average total Market Price in Indian Rupees (INR) for this project (e.g. 60000). Keep it reasonable based on the description scope.
2. Calculate a recommended Adloaf Price which is 20% to 40% cheaper (e.g. 38000).
3. Create an itemized market cost breakdown of 3 to 4 major tasks (e.g. UI/UX Wireframing, Copywriting, Core Development).
4. Provide a brief analysis (50-80 words) summarizing why standard agencies charge this high, and how Adloaf operates as a visual bakery offering massive cost savings without sacrificing quality.

You MUST respond ONLY with a valid JSON object. Do not wrap it in markdown block tags. The JSON structure must be:
{
  \"market_price\": 60000,
  \"adloaf_price\": 38000,
  \"breakdown\": [
     { \"item\": \"Wireframing & UX\", \"price\": 12000 },
     { \"item\": \"UI Design & Visuals\", \"price\": 18000 },
     { \"item\": \"Frontend Development\", \"price\": 30000 }
  ],
  \"analysis\": \"Analysis text goes here...\"
}";

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}";
$payload = json_encode([
    'contents' => [['parts' => [['text' => $prompt]]]],
    'generationConfig' => [
        'temperature' => 0.2, 
        'maxOutputTokens' => 1000,
        'responseMimeType' => 'application/json',
        'thinkingConfig' => [
            'thinkingBudget' => 0
        ]
    ]
]);

$ctx = stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\nContent-Length: " . strlen($payload),
        'content' => $payload,
        'timeout' => 15,
        'ignore_errors' => true
    ]
]);

$res = file_get_contents($url, false, $ctx);
if ($res) {
    $json = json_decode($res, true);
    echo "=== API Metadata ===\n";
    echo "Model Version: " . ($json['modelVersion'] ?? 'unknown') . "\n";
    if (isset($json['candidates'][0])) {
        $cand = $json['candidates'][0];
        echo "Finish Reason: " . ($cand['finishReason'] ?? 'none') . "\n";
        echo "Text Content Length: " . (isset($cand['content']['parts'][0]['text']) ? strlen($cand['content']['parts'][0]['text']) : 0) . " chars\n";
    }
    if (isset($json['usageMetadata'])) {
        echo "Usage Metadata:\n";
        print_r($json['usageMetadata']);
    }
    echo "\n=== Raw Extracted Text ===\n";
    echo ($json['candidates'][0]['content']['parts'][0]['text'] ?? 'NONE') . "\n";
} else {
    echo "Request failed.\n";
}
?>

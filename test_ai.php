<?php
require_once __DIR__ . '/lib/helpers.php';

header('Content-Type: text/plain');

echo "=== Testing Gemini API Connection ===\n\n";

$apiKey = GEMINI_API_KEY;
echo "API Key Length: " . strlen($apiKey) . "\n";
echo "API Key Prefix: " . substr($apiKey, 0, 8) . "...\n\n";

// Test 1: Concept Generator
echo "--- Test 1: Concept Generator ---\n";
$service = "Web Development";
$deadline = "2026-06-25";
$desc = "Create a modern portfolio website for a design agency.";
$name = "Adnan Vellicheri";
$business = "adLoaf";

$t1_start = microtime(true);
$concept = ai_generate_detailed_concept($service, $deadline, $desc, $name, $business, []);
$t1_end = microtime(true);
echo "Time Taken: " . round($t1_end - $t1_start, 2) . "s\n";
echo "Result:\n";
if ($concept) {
    echo $concept . "\n";
} else {
    echo "FAILED (Returned null)\n";
}
echo "\n";

// Test 2: Market Analysis
echo "--- Test 2: Market Analysis ---\n";
$t2_start = microtime(true);

// We will replicate the ai_market_analysis body here with error reporting enabled
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
        'maxOutputTokens' => 500,
        'responseMimeType' => 'application/json'
    ]
]);

$ctx = stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\nContent-Length: " . strlen($payload),
        'content' => $payload,
        'timeout' => 15,
        'ignore_errors' => true // See full HTTP response even on error
    ]
]);

$res = file_get_contents($url, false, $ctx);
$t2_end = microtime(true);
echo "Time Taken: " . round($t2_end - $t2_start, 2) . "s\n";
echo "HTTP Response Headers:\n";
print_r($http_response_header);
echo "\nResponse Body:\n";
if ($res) {
    echo $res . "\n";
    $json = json_decode($res, true);
    if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
        $text = $json['candidates'][0]['content']['parts'][0]['text'];
        echo "Extracted Text:\n" . $text . "\n\n";
        $parsed = json_decode($text, true);
        if ($parsed) {
            echo "Successfully Parsed JSON!\n";
            print_r($parsed);
        } else {
            echo "JSON Parsing of extracted text FAILED. Last error: " . json_last_error_msg() . "\n";
        }
    } else {
        echo "No text candidate found in the response.\n";
    }
} else {
    echo "FAILED to fetch response body (file_get_contents returned false).\n";
}
?>

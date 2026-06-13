<?php
/**
 * Shared helper functions for Adloaf
 */

require_once __DIR__ . '/../config.php';

// ─── Auth Helpers ──────────────────────────────────────────────────────────────
function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

function current_user() {
    if (!is_logged_in()) return null;
    global $pdo;
    static $u = null;
    if ($u) return $u;
    $s = $pdo->prepare("SELECT * FROM users_public WHERE id = ?");
    $s->execute([$_SESSION['user_id']]);
    $u = $s->fetch();
    return $u;
}

function require_login($redirect = 'bake.php') {
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: auth/login.php?next=" . urlencode($redirect));
        exit;
    }
}

// ─── Rate Limit ───────────────────────────────────────────────────────────────
function check_rate_limit($ip, $limit = 5, $window = 900) {
    global $pdo;
    // Clean old entries
    $pdo->prepare("DELETE FROM login_attempts WHERE attempted_at < NOW() - INTERVAL ? SECOND")
        ->execute([$window]);
    $count = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > NOW() - INTERVAL ? SECOND");
    $count->execute([$ip, $window]);
    return $count->fetchColumn() < $limit;
}

function record_attempt($ip) {
    global $pdo;
    $pdo->prepare("INSERT INTO login_attempts (ip_address) VALUES (?)")->execute([$ip]);
}

// ─── Visitor Tracking ─────────────────────────────────────────────────────────
function track_visitor($page = '/') {
    global $pdo;
    $ip  = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ip  = explode(',', $ip)[0];
    // Anonymize last octet for GDPR
    $anonIp = preg_replace('/\.\d+$/', '.0', $ip);
    $ua  = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
    $ref = substr($_SERVER['HTTP_REFERER'] ?? '', 0, 500);

    // Check if same IP visited in last 30 minutes (don't double-count)
    $recent = $pdo->prepare("SELECT id FROM visitors WHERE ip_address=? AND visited_at > NOW() - INTERVAL 30 MINUTE LIMIT 1");
    $recent->execute([$anonIp]);
    if ($recent->fetch()) return;

    // Async geo lookup via PHP (fire-and-forget using non-blocking socket)
    $geo = [];
    try {
        $ctx = stream_context_create(['http' => ['timeout' => 1]]);
        $json = @file_get_contents(GEO_API . $ip, false, $ctx);
        if ($json) $geo = json_decode($json, true);
    } catch (Exception $e) {}

    $stmt = $pdo->prepare("INSERT INTO visitors 
        (ip_address, country, country_code, city, timezone, isp, user_agent, page_visited, referrer)
        VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $anonIp,
        $geo['country'] ?? null,
        $geo['countryCode'] ?? null,
        $geo['city'] ?? null,
        $geo['timezone'] ?? null,
        $geo['isp'] ?? null,
        $ua,
        $page,
        $ref
    ]);
}

// ─── Currency ─────────────────────────────────────────────────────────────────
function get_exchange_rates() {
    $cacheFile = __DIR__ . '/../assets/data/exchange_cache.json';
    // Cache for 6 hours
    if (file_exists($cacheFile) && filemtime($cacheFile) > time() - 21600) {
        return json_decode(file_get_contents($cacheFile), true);
    }
    try {
        $ctx = stream_context_create(['http' => ['timeout' => 3]]);
        $json = @file_get_contents(EXCHANGE_API, false, $ctx);
        if ($json) {
            $data = json_decode($json, true);
            if (!empty($data['rates'])) {
                @file_put_contents($cacheFile, $json);
                return $data['rates'];
            }
        }
    } catch (Exception $e) {}
    // Fallback static rates
    return ['USD'=>0.012,'EUR'=>0.011,'GBP'=>0.0094,'AED'=>0.044,'INR'=>1];
}

function convert_from_inr($amountINR, $targetCurrency, $rates = null) {
    if ($targetCurrency === 'INR') return $amountINR;
    if (!$rates) $rates = get_exchange_rates();
    $rate = $rates[$targetCurrency] ?? 1;
    return round($amountINR * $rate, 2);
}

function currency_symbol($code) {
    $symbols = [
        'INR'=>'₹','USD'=>'$','EUR'=>'€','GBP'=>'£','AED'=>'د.إ',
        'SAR'=>'﷼','MYR'=>'RM','SGD'=>'S$','AUD'=>'A$','CAD'=>'C$',
        'JPY'=>'¥','CNY'=>'¥','KWD'=>'KD','QAR'=>'QR','BHD'=>'BD',
    ];
    return $symbols[$code] ?? $code;
}

// ─── File Upload ───────────────────────────────────────────────────────────────
function handle_upload($fileKey, $subDir = '') {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $allowedTypes = ['image/jpeg','image/png','image/gif','image/webp','image/svg+xml'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($_FILES[$fileKey]['tmp_name']);
    if (!in_array($mime, $allowedTypes)) {
        throw new Exception("Invalid file type.");
    }
    $ext  = pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION);
    $name = bin2hex(random_bytes(16)) . '.' . strtolower($ext);
    $dir  = UPLOAD_DIR . $subDir;
    if (!file_exists($dir)) mkdir($dir, 0755, true);
    move_uploaded_file($_FILES[$fileKey]['tmp_name'], $dir . $name);
    return UPLOAD_URL . $subDir . $name;
}

// ─── AI Description Generator ─────────────────────────────────────────────────
function ai_generate_description($service, $deadline, $userInput, $userName = '', $business = '') {
    $prompt = "You are a professional creative project description writer for a design agency called Adloaf. 
A client named '{$userName}' from business '{$business}' wants a '{$service}' project.
Their deadline is {$deadline}.
They described their project as: \"{$userInput}\"

Write a clear, professional, detailed project brief (150-200 words) that includes:
- What they need
- Their target audience
- Key deliverables
- Tone and style preferences based on their description
- Any special requirements

Write in first person as the client. Be specific and professional.";

    try {
        $apiKey = GEMINI_API_KEY;
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}";
        $payload = json_encode([
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 400]
        ]);
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\nContent-Length: " . strlen($payload),
                'content' => $payload,
                'timeout' => 10,
            ]
        ]);
        $res = @file_get_contents($url, false, $ctx);
        if ($res) {
            $data = json_decode($res, true);
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        }
    } catch (Exception $e) {}
    return null;
}

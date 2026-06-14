<?php
// ─── Database ─────────────────────────────────────────────────────────────────
$host = 'localhost';
$db   = 'u806388046_adloafDB';
$user = 'u806388046_adloaf_DB';
$pass = 'Adnan@adloaf2027#';

// ─── Email / SMTP ─────────────────────────────────────────────────────────────
define('SMTP_HOST',     'smtp.hostinger.com');
define('SMTP_PORT',     587);
define('SMTP_USER',     'noreply@adloaf.com');
define('SMTP_PASS',     'Adloaf@14062026');
define('SMTP_FROM_NAME','Adloaf Creative');

// ─── APIs ─────────────────────────────────────────────────────────────────────
define('EXCHANGE_API',   'https://api.exchangerate-api.com/v4/latest/INR');
define('GEO_API',        'http://ip-api.com/json/');

// ─── App Settings ─────────────────────────────────────────────────────────────
define('SITE_URL',      'https://adloaf.com');
define('UPLOAD_DIR',    __DIR__ . '/assets/uploads/');
define('UPLOAD_URL',    'assets/uploads/');
define('ADMIN_WA',      '916282563209');

// ─── Session ──────────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure',   isset($_SERVER['HTTPS']) ? 1 : 0);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// ─── Database Connection ──────────────────────────────────────────────────────
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,   false);
    
    // Silent DB Migrations for new columns
    try {
        $pdo->exec("ALTER TABLE bake_requests ADD COLUMN uploaded_files TEXT NULL");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE bake_requests ADD COLUMN total_cost DECIMAL(10,2) DEFAULT 0.00");
    } catch (PDOException $e) {}
    try {
        $sVal = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'gemini_api_key'")->fetchColumn();
        if (!$sVal || strpos($sVal, 'AIzaSy') === 0) {
            $dec = base64_decode('QVEuQWI4Uk42SkN0X1UxQW1QY2h4NTgxQjNsUC1jVHBtWm5xSnQxamlVZWFHZWtWNkpTZWc=');
            $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('gemini_api_key', ?) ON DUPLICATE KEY UPDATE setting_value = ?")
                ->execute([$dec, $dec]);
        }
    } catch (PDOException $e) {}
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ─── Helper: fetch site setting ───────────────────────────────────────────────
function site_setting($key, $default = '') {
    global $pdo;
    static $cache = [];
    if (!isset($cache[$key])) {
        $s = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
        $s->execute([$key]);
        $cache[$key] = $s->fetchColumn() ?: $default;
    }
    return $cache[$key];
}

// ─── CSRF Helper ──────────────────────────────────────────────────────────────
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify() {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

define('GEMINI_API_KEY', site_setting('gemini_api_key', base64_decode('QVEuQWI4Uk42SkN0X1UxQW1QY2h4NTgxQjNsUC1jVHBtWm5xSnQxamlVZWFHZWtWNkpTZWc=')));
?>

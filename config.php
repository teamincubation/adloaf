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
define('GEMINI_API_KEY', 'AIzaSyAQ.Ab8RN6JCt_U1AmPchx581B3lP-cTpmZnqJt1jiUeaGekV6JSeg');
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
?>

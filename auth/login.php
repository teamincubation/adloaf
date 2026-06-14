<?php
require_once __DIR__ . '/../lib/helpers.php';

$error = '';
$ip    = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) die("Invalid request.");

    if (!check_rate_limit($ip)) {
        $error = "Too many login attempts. Please wait 15 minutes and try again.";
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass  = $_POST['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM users_public WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($pass, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];

            $next = $_POST['next'] ?? $_SESSION['redirect_after_login'] ?? '../bake.php';
            unset($_SESSION['redirect_after_login']);
            if (!empty($next) && strpos($next, 'http') === false && strpos($next, '/') === false && strpos($next, '..') === false) {
                $next = '../' . $next;
            }
            header("Location: $next");
            exit;
        } else {
            record_attempt($ip);
            $error = "Invalid email or password.";
        }
    }
}

$next = $_GET['next'] ?? '../bake.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In | Adloaf</title>
  <link rel="stylesheet" href="../style.css?v=<?php echo filemtime('../style.css'); ?>">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Favicon -->
  <link class="fav-icon" rel="icon" type="image/svg+xml" href="../adloaf_logo.svg">
</head>
<body>
  <div class="auth-split-layout">
    <div class="auth-sidebar-pane">
      <a href="../index.php" class="auth-sidebar-brand" aria-label="adloaf Home">
        <div class="logo-icon-wrap">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 15C3 13 4.5 10.5 7 10.5C9.5 10.5 10 12 12 12C14 12 14.5 10.5 17 10.5C19.5 10.5 21 13 21 15C21 18.5 18.5 20 12 20C5.5 20 3 18.5 3 15Z"/>
            <path d="M7 10.5C7 8 9 6.5 12 6.5C15 6.5 17 8 17 10.5" stroke-dasharray="1 1"/>
            <path d="M12 2V4M8 3.5l1.5 1.5M16 3.5L14.5 5" stroke="currentColor" stroke-width="2"/>
          </svg>
        </div>
        <span class="logo-text">adloaf<span class="logo-dot" style="color:var(--accent-orange);">.</span></span>
      </a>
      
      <div class="auth-sidebar-content">
        <h2 class="auth-sidebar-title">Kneading <span>Creativity</span> Into Every Brand.</h2>
        <p class="auth-sidebar-desc">Welcome back to the bakery! Sign in to review your recipes, track your ongoing loaf projects, and submit new design ingredients.</p>
      </div>
      
      <div class="auth-sidebar-footer">
        &copy; 2026 adloaf Creative. Freshly baked design assets.
      </div>
    </div>
 
    <div class="auth-form-pane">
      <div class="auth-form-inner">
        <h1 class="auth-title" style="margin-bottom: 0.5rem;">Welcome Back</h1>
        <p class="auth-subtitle" style="margin-bottom: 2rem;">Sign in to manage your bake requests.</p>
 
        <?php if ($error): ?>
          <div class="form-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
 
        <form method="POST" action="">
          <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
          <input type="hidden" name="next" value="<?php echo htmlspecialchars($next); ?>">
 
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-input" placeholder="you@example.com" required autofocus style="height: 52px;">
          </div>
 
          <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-input" placeholder="Your password" required style="height: 52px;">
          </div>
          
          <div class="forgot-link" style="text-align: right; margin-top: -0.25rem; margin-bottom: 1.5rem;">
            <a href="forgot_password.php" style="color: var(--text-secondary); font-size: 0.88rem; font-weight: 600;">Forgot password?</a>
          </div>
 
          <button type="submit" class="btn btn-primary" style="width:100%; padding:0.95rem; font-size: 1rem;">Sign In</button>
          
          <div style="text-align: center; margin: 1.5rem 0; position: relative;">
            <hr style="border: 0; border-top: 1px solid var(--border-medium); margin: 0;">
            <span style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: var(--bg-primary); padding: 0 0.75rem; font-size: 0.85rem; color: var(--text-secondary); font-weight: 600;">or</span>
          </div>
 
          <?php
          // Check if Google Client ID is configured
          $googleClientId = site_setting('google_client_id');
          if ($googleClientId) {
              $siteUrlHost = parse_url(SITE_URL, PHP_URL_HOST);
              $currentHost = $_SERVER['HTTP_HOST'];
              $cleanSiteHost = preg_replace('/^www\./i', '', $siteUrlHost);
              $cleanCurrentHost = preg_replace('/^www\./i', '', $currentHost);
              
              if ($cleanSiteHost === $cleanCurrentHost) {
                  $redirectUri = SITE_URL . '/auth/google-callback.php';
              } else {
                  $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
                  $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
                  if (strpos($dir, '/auth') === false) {
                      $dir = rtrim($dir, '/') . '/auth';
                  }
                  $redirectUri = $scheme . '://' . $currentHost . $dir . '/google-callback.php';
              }
              
              $googleUrl = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
                  'response_type' => 'code',
                  'client_id'     => $googleClientId,
                  'redirect_uri'  => $redirectUri,
                  'scope'         => 'openid email profile',
                  'state'         => $next
              ]);
          } else {
              // Simulated Fallback login link
              $googleUrl = "google-callback.php?mock=1&state=" . urlencode($next);
          }
          ?>
          <a href="<?php echo htmlspecialchars($googleUrl); ?>" class="btn btn-secondary" style="width:100%; padding:0.95rem; font-size: 1rem; display: flex; align-items: center; justify-content: center; gap: 10px; text-decoration: none; border-color: var(--border-medium); background: #fff;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="color: #4285F4; margin-right: 6px;">
              <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
              <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
              <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/>
              <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            </svg>
            Continue with Google
          </a>
        </form>

        <div class="auth-footer" style="margin-top: 2rem;">
          Don't have an account? <a href="signup.php?next=<?php echo urlencode($next); ?>" style="font-weight: 700;">Sign up</a>
        </div>
      </div>
    </div>
  </div>
</body>
</html>

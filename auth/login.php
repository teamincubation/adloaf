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
  <link rel="stylesheet" href="../style.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
  <div class="auth-split-layout">
    <div class="auth-sidebar-pane">
      <a href="../index.php" class="auth-sidebar-brand" aria-label="Adloaf Home">
        <div class="logo-icon-wrap">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 15C3 13 4.5 10.5 7 10.5C9.5 10.5 10 12 12 12C14 12 14.5 10.5 17 10.5C19.5 10.5 21 13 21 15C21 18.5 18.5 20 12 20C5.5 20 3 18.5 3 15Z"/>
            <path d="M7 10.5C7 8 9 6.5 12 6.5C15 6.5 17 8 17 10.5" stroke-dasharray="1 1"/>
            <path d="M12 2V4M8 3.5l1.5 1.5M16 3.5L14.5 5" stroke="currentColor" stroke-width="2"/>
          </svg>
        </div>
        <span class="logo-text">Adloaf<span class="logo-dot" style="color:var(--accent-orange);">.</span></span>
      </a>
      
      <div class="auth-sidebar-content">
        <h2 class="auth-sidebar-title">Kneading <span>Creativity</span> Into Every Brand.</h2>
        <p class="auth-sidebar-desc">Welcome back to the bakery! Sign in to review your recipes, track your ongoing loaf projects, and submit new design ingredients.</p>
      </div>
      
      <div class="auth-sidebar-footer">
        &copy; 2026 Adloaf Creative. Freshly baked design assets.
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
        </form>

        <div class="auth-footer" style="margin-top: 2rem;">
          Don't have an account? <a href="signup.php?next=<?php echo urlencode($next); ?>" style="font-weight: 700;">Sign up</a>
        </div>
      </div>
    </div>
  </div>
</body>
</html>

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
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:var(--bg-primary); }
    .auth-card { background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:20px; padding:2.5rem; width:100%; max-width:420px; }
    .auth-logo { text-align:center; margin-bottom:2rem; }
    .auth-logo a { font-size:2rem; font-weight:800; color:var(--text-primary); text-decoration:none; }
    .auth-logo span { color:var(--primary-color); }
    .auth-title { font-size:1.5rem; font-weight:700; color:var(--text-primary); margin-bottom:0.25rem; }
    .auth-subtitle { color:var(--text-secondary); margin-bottom:1.5rem; font-size:0.9rem; }
    .auth-footer { text-align:center; margin-top:1rem; color:var(--text-secondary); font-size:0.9rem; }
    .auth-footer a { color:var(--primary-color); text-decoration:none; }
    .form-error { background:rgba(239,68,68,0.1); color:#ef4444; padding:0.75rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:0.9rem; }
    .forgot-link { text-align:right; margin-top:-0.5rem; margin-bottom:1rem; }
    .forgot-link a { color:var(--text-secondary); font-size:0.85rem; text-decoration:none; }
    .forgot-link a:hover { color:var(--primary-color); }
  </style>
</head>
<body>
  <div class="auth-card">
    <div class="auth-logo"><a href="../index.php">Adloaf<span>.</span></a></div>
    <h1 class="auth-title">Welcome back</h1>
    <p class="auth-subtitle">Sign in to manage your bake requests.</p>

    <?php if ($error): ?>
      <div class="form-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
      <input type="hidden" name="next" value="<?php echo htmlspecialchars($next); ?>">

      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-input" placeholder="you@example.com" required autofocus>
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-input" placeholder="Your password" required>
      </div>
      <div class="forgot-link">
        <a href="forgot_password.php">Forgot password?</a>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;padding:0.85rem;">Sign In</button>
    </form>

    <div class="auth-footer">
      Don't have an account? <a href="signup.php?next=<?php echo urlencode($next); ?>">Sign up</a>
    </div>
  </div>
</body>
</html>

<?php
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/Mailer.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) die("Invalid request.");
    $email = strtolower(trim($_POST['email'] ?? ''));

    $stmt = $pdo->prepare("SELECT * FROM users_public WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Always show success to prevent email enumeration
    $success = "If an account exists with that email, a reset link has been sent.";

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $exp   = date('Y-m-d H:i:s', time() + 3600); // 1 hour

        // Remove any existing tokens for this email
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
        $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?,?,?)")
            ->execute([$email, $token, $exp]);

        $link   = SITE_URL . "/auth/reset_password.php?token={$token}&email=" . urlencode($email);
        $mailer = new Mailer();
        $mailer->sendPasswordReset($email, $user['full_name'], $link);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password | Adloaf</title>
  <link rel="stylesheet" href="../style.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:var(--bg-primary); }
    .auth-card { background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:20px; padding:2.5rem; width:100%; max-width:420px; }
    .auth-logo { text-align:center; margin-bottom:2rem; }
    .auth-logo a { font-size:2rem; font-weight:800; color:var(--text-primary); text-decoration:none; }
    .auth-logo span { color:var(--primary-color); }
    .auth-footer { text-align:center; margin-top:1rem; color:var(--text-secondary); font-size:0.9rem; }
    .auth-footer a { color:var(--primary-color); text-decoration:none; }
    .form-success { background:rgba(16,185,129,0.1); color:#10b981; padding:0.75rem 1rem; border-radius:8px; margin-bottom:1rem; }
  </style>
</head>
<body>
  <div class="auth-card">
    <div class="auth-logo"><a href="../index.php">Adloaf<span>.</span></a></div>
    <h1 style="color:var(--text-primary);font-size:1.5rem;margin-bottom:0.25rem;">Forgot Password?</h1>
    <p style="color:var(--text-secondary);margin-bottom:1.5rem;font-size:0.9rem;">Enter your email and we'll send a reset link.</p>

    <?php if ($success): ?>
      <div class="form-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-input" placeholder="you@example.com" required autofocus>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;padding:0.85rem;">Send Reset Link</button>
    </form>
    <?php endif; ?>

    <div class="auth-footer">
      <a href="login.php">← Back to Login</a>
    </div>
  </div>
</body>
</html>

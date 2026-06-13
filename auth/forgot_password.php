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
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
  <div class="auth-center-layout">
    <div class="auth-center-card">
      <div style="text-align: center; margin-bottom: 2rem;">
        <a href="../index.php" class="logo" style="justify-content: center; display: inline-flex;">
          <div class="logo-icon-wrap">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 15C3 13 4.5 10.5 7 10.5C9.5 10.5 10 12 12 12C14 12 14.5 10.5 17 10.5C19.5 10.5 21 13 21 15C21 18.5 18.5 20 12 20C5.5 20 3 18.5 3 15Z"/>
              <path d="M7 10.5C7 8 9 6.5 12 6.5C15 6.5 17 8 17 10.5" stroke-dasharray="1 1"/>
              <path d="M12 2V4M8 3.5l1.5 1.5M16 3.5L14.5 5" stroke="currentColor" stroke-width="2"/>
            </svg>
          </div>
          <span class="logo-text">Adloaf<span class="logo-dot" style="color:var(--accent-orange);">.</span></span>
        </a>
      </div>

      <h1 class="auth-title" style="margin-bottom: 0.5rem; text-align: center; font-size: 1.6rem;">Forgot Password?</h1>
      <p class="auth-subtitle" style="margin-bottom: 2rem; text-align: center;">Enter your email and we'll send a reset link.</p>

      <?php if ($success): ?>
        <div class="success-toast" style="margin-bottom: 1.5rem; text-align: center;"><?php echo htmlspecialchars($success); ?></div>
      <?php endif; ?>

      <?php if (!$success): ?>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <div class="form-group" style="margin-bottom: 1.5rem;">
          <label class="form-label">Email Address</label>
          <input type="email" name="email" class="form-input" placeholder="you@example.com" required autofocus style="height: 52px;">
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%; padding:0.95rem; font-size: 1rem;">Send Reset Link</button>
      </form>
      <?php endif; ?>

      <div class="auth-footer" style="margin-top: 2rem; border-top: 1px solid var(--border-medium); padding-top: 1.25rem;">
        <a href="login.php" style="font-weight: 700;">← Back to Login</a>
      </div>
    </div>
  </div>
</body>
</html>

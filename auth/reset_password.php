<?php
require_once __DIR__ . '/../lib/helpers.php';

$error   = '';
$success = '';
$token   = $_GET['token'] ?? '';
$email   = $_GET['email'] ?? '';
$valid   = false;

if ($token && $email) {
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email=? AND token=? AND used=0 AND expires_at > NOW()");
    $stmt->execute([$email, $token]);
    $reset = $stmt->fetch();
    $valid = (bool)$reset;
}

if ($valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) die("Invalid request.");
    $pass  = $_POST['password']         ?? '';
    $conf  = $_POST['confirm_password'] ?? '';
    if (strlen($pass) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($pass !== $conf) {
        $error = "Passwords do not match.";
    } else {
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users_public SET password_hash=? WHERE email=?")->execute([$hash, $email]);
        $pdo->prepare("UPDATE password_resets SET used=1 WHERE token=?")->execute([$token]);
        $success = "Password reset successfully! You can now log in.";
        $valid = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password | Adloaf</title>
  <link rel="stylesheet" href="../style.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="../adloaf_logo.svg">
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
          <span class="logo-text">adloaf<span class="logo-dot" style="color:var(--accent-orange);">.</span></span>
        </a>
      </div>

      <?php if (!$valid && !$success): ?>
        <div class="form-error" style="text-align: center;">This reset link is invalid or has expired. <br><br><a href="forgot_password.php" style="color:#EA580C; font-weight:700;">Request a new link</a></div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="success-toast" style="margin-bottom: 1.5rem; text-align: center;"><?php echo htmlspecialchars($success); ?></div>
        <a href="login.php" class="btn btn-primary" style="display:block; text-align:center; padding:0.95rem; font-size: 1rem;">Go to Login</a>
      <?php endif; ?>

      <?php if ($valid): ?>
        <h1 class="auth-title" style="margin-bottom: 0.5rem; text-align: center; font-size: 1.6rem;">Set New Password</h1>
        <p class="auth-subtitle" style="margin-bottom: 2rem; text-align: center;">Choose a strong password for your account.</p>

        <?php if ($error): ?>
          <div class="form-error" style="margin-bottom: 1.5rem;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
          <div class="form-group" style="margin-bottom: 1.25rem;">
            <label class="form-label">New Password</label>
            <input type="password" name="password" class="form-input" placeholder="Min. 8 characters" required minlength="8" style="height: 52px;">
          </div>
          <div class="form-group" style="margin-bottom: 1.5rem;">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="confirm_password" class="form-input" placeholder="Repeat password" required style="height: 52px;">
          </div>
          <button type="submit" class="btn btn-primary" style="width:100%; padding:0.95rem; font-size: 1rem;">Reset Password</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>

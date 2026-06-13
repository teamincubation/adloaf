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
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:var(--bg-primary); }
    .auth-card { background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:20px; padding:2.5rem; width:100%; max-width:420px; }
    .auth-logo { text-align:center; margin-bottom:2rem; }
    .auth-logo a { font-size:2rem; font-weight:800; color:var(--text-primary); text-decoration:none; }
    .auth-logo span { color:var(--primary-color); }
    .form-error { background:rgba(239,68,68,0.1); color:#ef4444; padding:.75rem 1rem; border-radius:8px; margin-bottom:1rem; }
    .form-success { background:rgba(16,185,129,0.1); color:#10b981; padding:.75rem 1rem; border-radius:8px; margin-bottom:1rem; }
  </style>
</head>
<body>
  <div class="auth-card">
    <div class="auth-logo"><a href="../index.php">Adloaf<span>.</span></a></div>

    <?php if (!$valid && !$success): ?>
      <div class="form-error">This reset link is invalid or has expired. <a href="forgot_password.php" style="color:#EA580C;">Request a new one</a>.</div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="form-success"><?php echo htmlspecialchars($success); ?></div>
      <a href="login.php" class="btn btn-primary" style="display:block;text-align:center;padding:0.85rem;">Go to Login</a>
    <?php endif; ?>

    <?php if ($valid): ?>
      <h1 style="color:var(--text-primary);font-size:1.5rem;margin-bottom:1.5rem;">Set New Password</h1>
      <?php if ($error): ?><div class="form-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <div class="form-group">
          <label class="form-label">New Password</label>
          <input type="password" name="password" class="form-input" placeholder="Min. 8 characters" required minlength="8">
        </div>
        <div class="form-group">
          <label class="form-label">Confirm Password</label>
          <input type="password" name="confirm_password" class="form-input" placeholder="Repeat password" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;padding:0.85rem;">Reset Password</button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>

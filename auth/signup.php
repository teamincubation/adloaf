<?php
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/Mailer.php';

$error   = '';
$success = '';
$ip      = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) die("Invalid request.");

    if (!check_rate_limit($ip, 10, 3600)) {
        $error = "Too many signup attempts. Please try again in an hour.";
    } else {
        $name     = trim(htmlspecialchars(strip_tags($_POST['full_name']   ?? '')));
        $email    = strtolower(trim($_POST['email']    ?? ''));
        $wa       = preg_replace('/[^0-9+]/', '', $_POST['whatsapp'] ?? '');
        $pass     = $_POST['password']         ?? '';
        $passConf = $_POST['confirm_password'] ?? '';

        if (empty($name) || empty($email) || empty($wa) || empty($pass)) {
            $error = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } elseif (strlen($pass) < 8) {
            $error = "Password must be at least 8 characters.";
        } elseif ($pass !== $passConf) {
            $error = "Passwords do not match.";
        } else {
            // Check duplicate email
            $check = $pdo->prepare("SELECT id FROM users_public WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = "An account with this email already exists. <a href='login.php'>Login instead</a>.";
            } else {
                $hash  = password_hash($pass, PASSWORD_BCRYPT);
                $stmt  = $pdo->prepare("INSERT INTO users_public (full_name, email, whatsapp, password_hash) VALUES (?,?,?,?)");
                $stmt->execute([$name, $email, $wa, $hash]);
                $userId = $pdo->lastInsertId();

                // Log in immediately
                session_regenerate_id(true);
                $_SESSION['user_id']    = $userId;
                $_SESSION['user_name']  = $name;
                $_SESSION['user_email'] = $email;

                record_attempt($ip);

                // Redirect to bake page or intended destination
                $next = $_SESSION['redirect_after_login'] ?? '../bake.php';
                unset($_SESSION['redirect_after_login']);
                header("Location: $next");
                exit;
            }
        }
        record_attempt($ip);
    }
}

$next = $_GET['next'] ?? '../bake.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up | Adloaf</title>
  <link rel="stylesheet" href="../style.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:var(--bg-primary); }
    .auth-card { background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:20px; padding:2.5rem; width:100%; max-width:480px; }
    .auth-logo { text-align:center; margin-bottom:2rem; }
    .auth-logo a { font-size:2rem; font-weight:800; color:var(--text-primary); text-decoration:none; }
    .auth-logo span { color:var(--primary-color); }
    .auth-title { font-size:1.5rem; font-weight:700; color:var(--text-primary); margin-bottom:0.25rem; }
    .auth-subtitle { color:var(--text-secondary); margin-bottom:1.5rem; font-size:0.9rem; }
    .phone-wrap { display:flex; gap:0.5rem; }
    .phone-wrap select { flex:0 0 140px; height:53px; }
    .phone-wrap input { flex:1; }
    .auth-footer { text-align:center; margin-top:1rem; color:var(--text-secondary); font-size:0.9rem; }
    .auth-footer a { color:var(--primary-color); text-decoration:none; }
    .form-error { background:rgba(239,68,68,0.1); color:#ef4444; padding:0.75rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:0.9rem; }
    .divider { display:flex; align-items:center; gap:1rem; margin:1.5rem 0; color:var(--text-secondary); font-size:0.85rem; }
    .divider::before,.divider::after { content:''; flex:1; height:1px; background:var(--border-color); }
  </style>
</head>
<body>
  <div class="auth-card">
    <div class="auth-logo"><a href="../index.php">Adloaf<span>.</span></a></div>
    <h1 class="auth-title">Create your account</h1>
    <p class="auth-subtitle">Join the bakery to submit your creative project.</p>

    <?php if ($error): ?>
      <div class="form-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
      <input type="hidden" name="next"       value="<?php echo htmlspecialchars($next); ?>">

      <div class="form-group">
        <label class="form-label">Full Name</label>
        <input type="text" name="full_name" class="form-input" placeholder="Your full name" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
      </div>

      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-input" placeholder="you@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
      </div>

      <div class="form-group">
        <label class="form-label">WhatsApp Number <span style="color:#EA580C;">*</span> <small style="color:var(--text-secondary);font-weight:400;">(Required for smooth communication)</small></label>
        <div class="phone-wrap">
          <select name="country_code" class="form-input" id="country-code-select">
            <option value="+91" data-flag="🇮🇳" selected>🇮🇳 +91 (India)</option>
            <option value="+1"  data-flag="🇺🇸">🇺🇸 +1 (USA)</option>
            <option value="+44" data-flag="🇬🇧">🇬🇧 +44 (UK)</option>
            <option value="+971" data-flag="🇦🇪">🇦🇪 +971 (UAE)</option>
            <option value="+966" data-flag="🇸🇦">🇸🇦 +966 (Saudi)</option>
            <option value="+60"  data-flag="🇲🇾">🇲🇾 +60 (Malaysia)</option>
            <option value="+65"  data-flag="🇸🇬">🇸🇬 +65 (Singapore)</option>
            <option value="+61"  data-flag="🇦🇺">🇦🇺 +61 (Australia)</option>
            <option value="+49"  data-flag="🇩🇪">🇩🇪 +49 (Germany)</option>
            <option value="+33"  data-flag="🇫🇷">🇫🇷 +33 (France)</option>
            <option value="+974" data-flag="🇶🇦">🇶🇦 +974 (Qatar)</option>
            <option value="+965" data-flag="🇰🇼">🇰🇼 +965 (Kuwait)</option>
            <option value="+973" data-flag="🇧🇭">🇧🇭 +973 (Bahrain)</option>
          </select>
          <input type="tel" name="whatsapp_number" class="form-input" placeholder="9876543210" required value="<?php echo htmlspecialchars($_POST['whatsapp_number'] ?? ''); ?>">
        </div>
        <small style="color:var(--text-secondary);display:block;margin-top:4px;">Enter number without country code prefix</small>
      </div>
      <input type="hidden" name="whatsapp" id="whatsapp-full">

      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-input" placeholder="Min. 8 characters" required minlength="8">
      </div>

      <div class="form-group">
        <label class="form-label">Confirm Password</label>
        <input type="password" name="confirm_password" class="form-input" placeholder="Repeat password" required>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;padding:0.85rem;" onclick="combinePhone()">Create Account</button>
    </form>

    <div class="auth-footer">
      Already have an account? <a href="login.php?next=<?php echo urlencode($next); ?>">Sign in</a>
    </div>
  </div>

  <script>
    function combinePhone() {
      const code = document.getElementById('country-code-select').value;
      const num  = document.querySelector('[name="whatsapp_number"]').value.replace(/^0+/, '');
      document.getElementById('whatsapp-full').value = code + num;
    }
  </script>
</body>
</html>

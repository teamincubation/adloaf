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
        <h2 class="auth-sidebar-title">Freshly <span>Baked</span> Design Delivery.</h2>
        <p class="auth-sidebar-desc">Join our visual bakery! Create an account to cook custom project requests, estimate rates, and access real-time status updates.</p>
      </div>
      
      <div class="auth-sidebar-footer">
        &copy; 2026 Adloaf Creative. Freshly baked design assets.
      </div>
    </div>

    <div class="auth-form-pane" style="align-items: flex-start; padding-top: 5rem; padding-bottom: 5rem; overflow-y: auto; max-height: 100vh;">
      <div class="auth-form-inner">
        <h1 class="auth-title" style="margin-bottom: 0.5rem;">Create an Account</h1>
        <p class="auth-subtitle" style="margin-bottom: 2rem;">Join the bakery to submit your creative project.</p>

        <?php if ($error): ?>
          <div class="form-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="" onsubmit="combinePhone()">
          <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
          <input type="hidden" name="next"       value="<?php echo htmlspecialchars($next); ?>">

          <div class="form-group">
            <label class="form-label">Full Name</label>
            <input type="text" name="full_name" class="form-input" placeholder="Your full name" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" style="height: 52px;">
          </div>

          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-input" placeholder="you@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" style="height: 52px;">
          </div>

          <div class="form-group">
            <label class="form-label">WhatsApp Number <span style="color:#EA580C;">*</span> <small style="color:var(--text-secondary);font-weight:400;">(Required for smooth communication)</small></label>
            <div class="phone-select-wrap">
              <select name="country_code" class="phone-code-select" id="country-code-select">
                <option value="+91" data-flag="🇮🇳" selected>🇮🇳 +91</option>
                <option value="+1"  data-flag="🇺🇸">🇺🇸 +1</option>
                <option value="+44" data-flag="🇬🇧">🇬🇧 +44</option>
                <option value="+971" data-flag="🇦🇪">🇦🇪 +971</option>
                <option value="+966" data-flag="🇸🇦">🇸🇦 +966</option>
                <option value="+60"  data-flag="🇲🇾">🇲🇾 +60</option>
                <option value="+65"  data-flag="🇸🇬">🇸🇬 +65</option>
                <option value="+61"  data-flag="🇦🇺">🇦🇺 +61</option>
                <option value="+49"  data-flag="🇩🇪">🇩🇪 +49</option>
                <option value="+33"  data-flag="🇫🇷">🇫🇷 +33</option>
                <option value="+974" data-flag="🇶🇦">🇶🇦 +974</option>
                <option value="+965" data-flag="🇰🇼">🇰🇼 +965</option>
                <option value="+973" data-flag="🇧🇭">🇧🇭 +973</option>
              </select>
              <input type="tel" name="whatsapp_number" class="form-input phone-number-input" placeholder="9876543210" required value="<?php echo htmlspecialchars($_POST['whatsapp_number'] ?? ''); ?>">
            </div>
            <small style="color:var(--text-secondary); display:block; margin-top:4px;">Enter number without country code prefix</small>
          </div>
          <input type="hidden" name="whatsapp" id="whatsapp-full">

          <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-input" placeholder="Min. 8 characters" required minlength="8" style="height: 52px;">
          </div>

          <div class="form-group">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="confirm_password" class="form-input" placeholder="Repeat password" required style="height: 52px;">
          </div>

          <button type="submit" class="btn btn-primary" style="width:100%; padding:0.95rem; font-size: 1rem; margin-top: 1rem;">Create Account</button>
        </form>

        <div class="auth-footer" style="margin-top: 2rem;">
          Already have an account? <a href="login.php?next=<?php echo urlencode($next); ?>" style="font-weight: 700;">Sign in</a>
        </div>
      </div>
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

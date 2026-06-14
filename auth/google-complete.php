<?php
require_once __DIR__ . '/../lib/helpers.php';

if (empty($_SESSION['google_reg'])) {
    header("Location: login.php");
    exit;
}

$error = '';
$gData = $_SESSION['google_reg'];
$next  = $gData['next'] ?? '../bake.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) die("Invalid request.");

    $wa = preg_replace('/[^0-9+]/', '', $_POST['whatsapp'] ?? '');
    
    if (empty($wa)) {
        $error = "WhatsApp number is mandatory for smooth communication.";
    } else {
        // Insert new public user with random password hash (since they authenticate via Google)
        $randomPass = bin2hex(random_bytes(16));
        $hash = password_hash($randomPass, PASSWORD_BCRYPT);
        
        $stmt = $pdo->prepare("INSERT INTO users_public (full_name, email, whatsapp, password_hash, profile_photo) VALUES (?,?,?,?,?)");
        $stmt->execute([
            $gData['full_name'],
            $gData['email'],
            $wa,
            $hash,
            $gData['profile_photo'] ?: null
        ]);
        $userId = $pdo->lastInsertId();
        
        // Log in immediately
        session_regenerate_id(true);
        $_SESSION['user_id']    = $userId;
        $_SESSION['user_name']  = $gData['full_name'];
        $_SESSION['user_email'] = $gData['email'];
        
        // Clear session registration draft data
        unset($_SESSION['google_reg']);
        
        if (!empty($next) && strpos($next, 'http') === false && strpos($next, '/') === false && strpos($next, '..') === false) {
            $next = '../' . $next;
        }
        header("Location: " . $next);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Complete Sign Up | adloaf</title>
  <link rel="stylesheet" href="../style.css?v=<?php echo filemtime('../style.css'); ?>">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="../adloaf_logo.svg">
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
        <h2 class="auth-sidebar-title">Almost <span>Ready</span> to Bake.</h2>
        <p class="auth-sidebar-desc">We need your WhatsApp number to establish a direct communication thread to send mock ups, recipes, and status notes.</p>
      </div>
      <div class="auth-sidebar-footer">
        &copy; 2026 adloaf Creative. Freshly baked design assets.
      </div>
    </div>

    <div class="auth-form-pane">
      <div class="auth-form-inner">
        <h1 class="auth-title" style="margin-bottom: 0.5rem;">One Last Step</h1>
        <p class="auth-subtitle" style="margin-bottom: 2rem;">Please provide your WhatsApp details to complete Google signup.</p>

        <?php if ($error): ?>
          <div class="form-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="" onsubmit="combinePhone()">
          <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
          
          <div class="form-group" style="margin-bottom: 1.5rem;">
            <label class="form-label">Simulated Account</label>
            <div style="background:var(--bg-secondary); border:1.5px solid var(--border-medium); border-radius:var(--radius-sm); padding:1rem; font-size:0.9rem;">
                👤 <strong>Name:</strong> <?php echo htmlspecialchars($gData['full_name']); ?><br>
                ✉ <strong>Email:</strong> <?php echo htmlspecialchars($gData['email']); ?>
            </div>
          </div>

          <div class="form-group" style="margin-bottom: 2rem;">
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
              <input type="tel" name="whatsapp_number" class="form-input phone-number-input" placeholder="9876543210" required>
            </div>
            <small style="color:var(--text-secondary); display:block; margin-top:4px;">Enter number without country code prefix</small>
          </div>
          <input type="hidden" name="whatsapp" id="whatsapp-full">

          <button type="submit" class="btn btn-primary" style="width:100%; padding:0.95rem; font-size: 1rem;">Finalize Registration</button>
        </form>
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

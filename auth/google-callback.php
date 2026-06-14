<?php
require_once __DIR__ . '/../lib/helpers.php';

$clientId = site_setting('google_client_id');
$mockEnabled = empty($clientId);
$mock = $mockEnabled && (isset($_GET['mock']) || (isset($_SESSION['google_oauth_mock']) && $_SESSION['google_oauth_mock']));
$next = $_GET['state'] ?? $_SESSION['redirect_after_login'] ?? '../bake.php';

if (isset($_GET['code']) || isset($_GET['mock_select'])) {
    $email = '';
    $fullName = '';
    $photo = '';
    
    if (isset($_GET['mock_select'])) {
        if (!$mockEnabled) {
            die("Access Denied: Mock authentication is disabled because Google credentials are configured.");
        }
        $email = strtolower(trim($_GET['mock_email'] ?? ''));
        $fullName = trim($_GET['mock_name'] ?? '');
        $photo = '';
    } else {
        // Exchange authorization code for Google access token
        $clientId = site_setting('google_client_id');
        $clientSecret = site_setting('google_client_secret');
        $code = $_GET['code'];
        $redirectUri = SITE_URL . '/auth/google-callback.php';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'code'          => $code,
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri'  => $redirectUri,
            'grant_type'    => 'authorization_code'
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $tokenData = json_decode($response, true);
        if (isset($tokenData['access_token'])) {
            // Fetch User info using the access token
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v2/userinfo');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $tokenData['access_token']
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $userInfoResponse = curl_exec($ch);
            curl_close($ch);
            
            $userInfo = json_decode($userInfoResponse, true);
            $email = strtolower(trim($userInfo['email'] ?? ''));
            $fullName = trim($userInfo['name'] ?? '');
            $photo = $userInfo['picture'] ?? '';
        }
    }
    
    if ($email) {
        // Check if user exists in the database
        $stmt = $pdo->prepare("SELECT * FROM users_public WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Log in existing user
            session_regenerate_id(true);
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            header("Location: " . $next);
            exit;
        } else {
            // Google Sign-up complete details flow (WhatsApp completer)
            $_SESSION['google_reg'] = [
                'full_name'     => $fullName,
                'email'         => $email,
                'profile_photo' => $photo,
                'next'          => $next
            ];
            header("Location: google-complete.php");
            exit;
        }
    } else {
        die("Authentication failed. Google user email could not be retrieved.");
    }
}

// Render account simulator if mock query param is active
if ($mock) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <title>Google Sign-In Simulator | adloaf</title>
      <link rel="stylesheet" href="../style.css">
      <style>
        body { display:flex; align-items:center; justify-content:center; min-height:100vh; background: var(--bg-primary); font-family: 'Plus Jakarta Sans', sans-serif; }
        .sim-card { background:#fff; border: 1.5px solid var(--border-medium); border-radius:var(--radius-md); padding:2.5rem; max-width:420px; width:100%; box-shadow: var(--shadow-lg); text-align:center; }
      </style>
    </head>
    <body>
      <div class="sim-card">
        <h2 style="margin-bottom:1rem; color:var(--text-primary);">Google Account Simulator</h2>
        <p style="color:var(--text-secondary); font-size:0.9rem; margin-bottom:1.5rem;">Select a simulated account to test the complete Google OAuth2 signup/signin flows.</p>
        
        <form action="google-callback.php" method="GET">
            <input type="hidden" name="mock_select" value="1">
            <input type="hidden" name="state" value="<?php echo htmlspecialchars($next); ?>">
            <div style="text-align:left; margin-bottom:1rem;">
                <label style="font-size:0.85rem; font-weight:700; color:var(--text-secondary);">Google Profile Name</label>
                <input type="text" name="mock_name" class="form-input" style="height:46px; margin-top:4px;" value="Adnan Vellicheri" required>
            </div>
            <div style="text-align:left; margin-bottom:1.5rem;">
                <label style="font-size:0.85rem; font-weight:700; color:var(--text-secondary);">Google Account Email</label>
                <input type="email" name="mock_email" class="form-input" style="height:46px; margin-top:4px;" value="adnan@example.com" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%; padding:0.95rem;">Confirm simulated callback</button>
        </form>
      </div>
    </body>
    </html>
    <?php
    exit;
}
?>

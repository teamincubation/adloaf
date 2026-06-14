<?php
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/Mailer.php';

// Track visitor
try { track_visitor('/bake.php'); } catch(Exception $e) {}

$user   = current_user();
$error  = '';
$submitted = false;

// Restore draft if logged in and draft exists
$draft = $_SESSION['bake_draft'] ?? [];

// Handle AJAX Temp Uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_temp') {
    header('Content-Type: application/json');
    if (!is_logged_in()) { echo json_encode(['error' => 'Please sign in first.']); exit; }
    
    if (empty($_FILES['project_files'])) {
        echo json_encode(['error' => 'No files sent.']);
        exit;
    }
    
    $files = $_FILES['project_files'];
    $fileCount = is_array($files['name']) ? count($files['name']) : 1;
    
    if (empty($_SESSION['temp_files'])) {
        $_SESSION['temp_files'] = [];
    }
    
    if (count($_SESSION['temp_files']) + $fileCount > 5) {
         echo json_encode(['error' => 'You can upload a maximum of 5 files in total.']);
         exit;
    }
    
    $blacklist = ['php', 'phtml', 'php5', 'php7', 'phps', 'htaccess', 'exe', 'js', 'bat', 'cmd', 'sh', 'com', 'scr', 'msi', 'vbs'];
    $tempFolder = __DIR__ . '/assets/uploads/temp/';
    if (!is_dir($tempFolder)) {
        mkdir($tempFolder, 0755, true);
    }
    
    $totalSize = 0;
    for ($i = 0; $i < $fileCount; $i++) {
        $size = is_array($files['size']) ? $files['size'][$i] : $files['size'];
        $totalSize += $size;
    }
    
    foreach ($_SESSION['temp_files'] as $f) {
        $fullPath = __DIR__ . '/' . $f['path'];
        if (file_exists($fullPath)) {
            $totalSize += filesize($fullPath);
        }
    }
    
    if ($totalSize > 10 * 1024 * 1024) {
        echo json_encode(['error' => 'Total size of all files must not exceed 10MB.']);
        exit;
    }
    
    for ($i = 0; $i < $fileCount; $i++) {
        $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
        $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
        $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
        
        if ($error !== UPLOAD_ERR_OK) continue;
        
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (in_array($ext, $blacklist)) {
            echo json_encode(['error' => "Dangerous file extension (." . htmlspecialchars($ext) . ") is not allowed."]);
            exit;
        }
        
        $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($name));
        $uniqueName = time() . '_' . uniqid() . '_' . $sanitizedName;
        $destPath = $tempFolder . $uniqueName;
        
        if (move_uploaded_file($tmpName, $destPath)) {
            $_SESSION['temp_files'][] = [
                'name' => $name,
                'path' => 'assets/uploads/temp/' . $uniqueName
            ];
        }
    }
    
    echo json_encode(['success' => true, 'files' => $_SESSION['temp_files']]);
    exit;
}

// Handle AJAX Temp File Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_temp_file') {
    header('Content-Type: application/json');
    $path = $_POST['path'] ?? '';
    if (!empty($_SESSION['temp_files'])) {
        foreach ($_SESSION['temp_files'] as $key => $file) {
            if ($file['path'] === $path) {
                $fullPath = __DIR__ . '/' . $path;
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
                unset($_SESSION['temp_files'][$key]);
                break;
            }
        }
        $_SESSION['temp_files'] = array_values($_SESSION['temp_files']);
    }
    echo json_encode(['success' => true, 'files' => $_SESSION['temp_files'] ?? []]);
    exit;
}

// Handle AI Description Generator (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ai_generate') {
    header('Content-Type: application/json');
    if (!is_logged_in()) { echo json_encode(['error' => 'Not logged in']); exit; }
    $u      = current_user();
    $tempFiles = $_SESSION['temp_files'] ?? [];
    
    $result = ai_generate_detailed_concept(
        $_POST['service']     ?? '',
        $_POST['deadline']    ?? '',
        $_POST['description'] ?? '',
        $u['full_name'],
        $u['about_business'] ?? '',
        $tempFiles
    );
    echo json_encode(['text' => $result ?: 'Could not generate. Please try again.']);
    exit;
}

// Handle AI Market Analysis (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ai_market_analysis') {
    header('Content-Type: application/json');
    if (!is_logged_in()) { echo json_encode(['error' => 'Not logged in']); exit; }
    
    $service = $_POST['service'] ?? '';
    $description = $_POST['description'] ?? '';
    
    $result = ai_market_analysis($service, $description);
    if ($result) {
        echo json_encode($result);
    } else {
        echo json_encode(['error' => 'Market analysis failed. Please try again.']);
    }
    exit;
}

// Handle AI Cleanup (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ai_cleanup') {
    header('Content-Type: application/json');
    if (!empty($_SESSION['temp_files'])) {
        foreach ($_SESSION['temp_files'] as $file) {
            $fullPath = __DIR__ . '/' . $file['path'];
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }
        unset($_SESSION['temp_files']);
    }
    echo json_encode(['success' => true]);
    exit;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_bake') {
    $_SESSION['bake_draft'] = [
        'service_type'        => $_POST['service_type']        ?? '',
        'content_language'    => $_POST['content_language']    ?? 'english',
        'deadline'            => $_POST['deadline']            ?? '',
        'project_description' => $_POST['project_description'] ?? '',
    ];

    if (!is_logged_in()) {
        header("Location: auth/login.php?next=bake.php");
        exit;
    }
    if (!csrf_verify()) die("Invalid request.");

    $data = $_SESSION['bake_draft'];
    if (empty($data['service_type']) || empty($data['deadline']) || empty($data['project_description'])) {
        $error = "Please fill in all required fields.";
    } else {
        // Process temporary uploaded files from session and move them permanently
        $uploadedFiles = [];
        if (!empty($_SESSION['temp_files'])) {
            $ingFolder = __DIR__ . '/assets/uploads/ingredients/';
            if (!is_dir($ingFolder)) {
                mkdir($ingFolder, 0755, true);
            }
            foreach ($_SESSION['temp_files'] as $file) {
                $tempPath = __DIR__ . '/' . $file['path'];
                if (file_exists($tempPath)) {
                    $originalName = basename($file['name']);
                    $sanitizedName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                    $uniqueName = time() . '_' . uniqid() . '_' . $sanitizedName;
                    $destPath = $ingFolder . $uniqueName;
                    if (rename($tempPath, $destPath)) {
                        $uploadedFiles[] = [
                            'name' => $originalName,
                            'path' => 'assets/uploads/ingredients/' . $uniqueName
                        ];
                    }
                }
            }
            unset($_SESSION['temp_files']);
        }

        if (empty($error)) {
            $uploadedJson = empty($uploadedFiles) ? null : json_encode($uploadedFiles);

            $svc = $pdo->prepare("SELECT price_from_inr, market_price_inr FROM services WHERE title LIKE ? LIMIT 1");
            $svc->execute(['%' . $data['service_type'] . '%']);
            $svcData = $svc->fetch();

            $stmt = $pdo->prepare("INSERT INTO bake_requests 
                (user_id, service_type, content_language, deadline, project_description, ai_generated_desc, estimated_price_inr, market_price_inr, uploaded_files)
                VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $_SESSION['user_id'],
                $data['service_type'],
                $data['content_language'],
                $data['deadline'],
                $data['project_description'],
                $_POST['ai_description'] ?? null,
                $svcData['price_from_inr'] ?? 0,
                $svcData['market_price_inr'] ?? 0,
                $uploadedJson
            ]);

            try {
                $mailer = new Mailer();
                $mailer->sendBakeConfirmation($user['email'], $user['full_name'], $data['service_type'], $data['deadline']);
            } catch(Exception $e) {}

            unset($_SESSION['bake_draft']);
            $submitted = true;
        }
    }
}

// Get services for the form
$services   = $pdo->query("SELECT * FROM services ORDER BY sort_order ASC")->fetchAll();
$minDate    = date('Y-m-d', strtotime('+4 days'));

// Restore draft values
$draft = $_SESSION['bake_draft'] ?? [];
$selectedService = $_GET['service'] ?? ($draft['service_type'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bake a Project | Adloaf</title>
  <meta name="description" content="Submit your creative project request to Adloaf — the freshly baked design agency.">
  <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
  <style>
    @media (min-width: 992px) {
      .mobile-only {
        display: none !important;
      }
    }
  </style>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="adloaf_logo.svg">
</head>
<body>

<!-- Oven Loading Overlay Modal -->
<div class="oven-overlay-modal" id="oven-overlay">
  <div class="steam-emitter" style="margin-bottom: 2rem;">
    <div class="steam-puff"></div>
    <div class="steam-puff"></div>
    <div class="steam-puff"></div>
  </div>
  <svg width="130" height="130" viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg" class="oven-glow">
    <rect x="50" y="80" width="300" height="260" rx="36" fill="#2C2018" stroke="#E8DEC6" stroke-width="4"/>
    <path d="M50 116c0-20 16-36 36-36h228c20 0 36 16 36 36v14H50v-14Z" fill="#1E1611"/>
    <circle cx="90" cy="98" r="7" fill="#EA580C"/>
    <circle cx="115" cy="98" r="7" fill="#D97706"/>
    <rect x="75" y="145" width="250" height="165" rx="20" fill="#1E1611" stroke="#E8DEC6" stroke-width="3"/>
    <rect x="90" y="160" width="220" height="135" rx="12" fill="url(#ovenGlowAnim)" opacity="0.85"/>
    <path d="M140 250c0-25 20-35 60-35s60 10 60 35v15H140v-15Z" fill="url(#breadGrad)" stroke="#EA580C" stroke-width="2"/>
    <defs>
      <radialGradient id="ovenGlowAnim" cx="50%" cy="50%" r="50%">
        <stop offset="0%" stop-color="#EA580C" stop-opacity="0.55"/>
        <stop offset="100%" stop-color="#1E1611" stop-opacity="0"/>
        <animate attributeName="r" values="40%;55%;40%" dur="1.2s" repeatCount="indefinite"/>
      </radialGradient>
      <linearGradient id="breadGrad" x1="0" y1="0" x2="1" y2="1">
        <stop offset="0%" stop-color="#FFFDF9"/>
        <stop offset="60%" stop-color="#D97706"/>
        <stop offset="100%" stop-color="#5C4A3E"/>
      </linearGradient>
    </defs>
  </svg>
  <div class="oven-text" style="color: #fff; font-size: 1.5rem; font-weight: 800; margin-top: 1.5rem;">Baking your idea... 🔥</div>
  <div class="oven-sub" style="color: var(--text-light-muted); font-size: 0.95rem;">Placing your project in the creative oven</div>
</div>

<!-- Standardized Navigation -->
<header class="header scrolled" id="header">
  <div class="container nav-container">
    <a href="index.php" class="logo" aria-label="adloaf Home">
      <div class="logo-icon-wrap">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 15C3 13 4.5 10.5 7 10.5C9.5 10.5 10 12 12 12C14 12 14.5 10.5 17 10.5C19.5 10.5 21 13 21 15C21 18.5 18.5 20 12 20C5.5 20 3 18.5 3 15Z"/>
          <path d="M7 10.5C7 8 9 6.5 12 6.5C15 6.5 17 8 17 10.5" stroke-dasharray="1 1"/>
          <path d="M12 2V4M8 3.5l1.5 1.5M16 3.5L14.5 5" stroke="currentColor" stroke-width="2"/>
        </svg>
      </div>
      <span class="logo-text">adloaf<span class="logo-dot" style="color:var(--accent-orange);">.</span></span>
    </a>

    <nav aria-label="Main Navigation">
      <ul class="nav-menu" id="nav-menu">
        <li><a href="index.php" class="nav-link">Home</a></li>
        <li><a href="index.php#about" class="nav-link">About</a></li>
        <li><a href="index.php#services" class="nav-link">Services</a></li>
        <li><a href="works.php" class="nav-link">Works</a></li>
        <li><a href="index.php#process" class="nav-link">Process</a></li>
        <li><a href="pricing.php" class="nav-link">Pricing</a></li>
        <!-- Mobile-only links -->
        <?php if ($user): ?>
          <li class="mobile-only"><a href="profile.php" class="nav-link">Profile Dashboard</a></li>
          <li class="mobile-only"><a href="auth/logout.php" class="nav-link" style="color: #ef4444;">Logout</a></li>
        <?php else: ?>
          <li class="mobile-only"><a href="auth/login.php?next=bake.php" class="nav-link">Sign In</a></li>
        <?php endif; ?>
        <li class="mobile-only" style="margin-top: 1rem;"><a href="bake.php" class="btn btn-primary active" style="width: 100%; text-align: center; padding: 0.75rem;">Bake a Project</a></li>
      </ul>
    </nav>

    <div class="header-actions">
      <?php if ($user): ?>
        <div class="header-profile-menu">
          <div class="header-profile-trigger">
            <img src="<?php echo $user['profile_photo'] ? htmlspecialchars($user['profile_photo']) : 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($user['email']))) . '?d=mp'; ?>" alt="Profile" class="header-profile-avatar">
            <span class="header-profile-name"><?php echo htmlspecialchars(explode(' ', $user['full_name'])[0]); ?></span>
          </div>
          <div class="header-dropdown-card">
            <a href="profile.php" class="header-dropdown-link">👤 Profile Dashboard</a>
            <a href="auth/logout.php" class="header-dropdown-link" style="color: #ef4444;">🚪 Logout</a>
          </div>
        </div>
      <?php else: ?>
        <a href="auth/login.php?next=bake.php" class="btn btn-secondary btn-header" style="padding: 0.5rem 1.25rem; font-size: 0.9rem;">Sign In</a>
      <?php endif; ?>
      
      <a href="bake.php" class="btn btn-primary btn-header active" id="header-cta" style="margin-left: 0.5rem;">Bake a Project</a>
      <button class="menu-toggle" id="menu-toggle" aria-label="Toggle Navigation Menu" aria-controls="nav-menu" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</header>

<div class="bake-page">
  <div class="bake-container">
    <div class="bake-header">
      <span class="section-badge">The Oven</span>
      <h1 class="section-title" style="margin-bottom: 0.5rem;">Send a <span>Recipe Request</span></h1>
      <p class="section-subtitle">Fill in your project details below. Our team will review and reply on WhatsApp!</p>
    </div>

    <?php if ($submitted): ?>
    <!-- SUCCESS STATE -->
    <div class="bake-card bake-success" style="box-shadow: var(--shadow-lg);">
      <div class="bake-success-icon">🍞</div>
      <h2>Your Bake is in the Oven!</h2>
      <p style="margin-bottom: 1.5rem;">We've received your project request and will get back to you via WhatsApp very soon. Check your email for a confirmation receipt.</p>
      <div style="display:flex; gap:1.25rem; justify-content:center; flex-wrap:wrap; margin-top:2.5rem;">
        <a href="index.php" class="btn btn-secondary btn-loaf">← Back to Home</a>
        <a href="bake.php" class="btn btn-primary btn-loaf">Submit Another Request</a>
      </div>
    </div>
    <?php else: ?>

    <!-- USER STRIP -->
    <?php if ($user): ?>
    <div class="user-strip" style="box-shadow: var(--shadow-sm);">
      <div class="user-strip-info">Submitting as <strong><?php echo htmlspecialchars($user['full_name']); ?></strong> (<?php echo htmlspecialchars($user['email']); ?>)</div>
      <a href="auth/logout.php" style="color:var(--text-secondary); font-size:0.85rem; text-decoration:none; font-weight:700;">Not you? Logout</a>
    </div>
    <?php else: ?>
    <div class="user-strip" style="border-color:rgba(234,88,12,0.3); box-shadow: var(--shadow-sm);">
      <div class="user-strip-info" style="color:var(--text-secondary); line-height: 1.4;">⚠️ You are not signed in. Fill the form — your draft will be preserved and submitted once you sign in.</div>
      <a href="auth/login.php?next=bake.php" class="btn btn-primary" style="padding:0.4rem 1.25rem; font-size:0.85rem; border-radius: var(--radius-sm);">Sign In</a>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="form-error" style="background:rgba(239,68,68,0.1); color:#ef4444; padding:.75rem 1rem; border-radius:8px; margin-bottom:1.5rem; font-weight: 600; text-align: center; border: 1px solid rgba(239,68,68,0.2);"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="form-error" id="js-error-card" style="display:none; background:rgba(239,68,68,0.1); color:#ef4444; padding:.75rem 1rem; border-radius:8px; margin-bottom:1.5rem; font-weight: 600; text-align: center; border: 1px solid rgba(239,68,68,0.2);"></div>

    <form method="POST" action="bake.php" id="bake-form" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
      <input type="hidden" name="action" value="submit_bake">
      <input type="hidden" name="ai_description" id="ai-description-hidden">

      <div class="bake-card" style="box-shadow: var(--shadow-md);">
        <!-- Step Indicator -->
        <div class="bake-steps-indicator">
          <div class="bake-step-dot active" id="step-dot-1" title="Service Selection">1</div>
          <div class="bake-step-dot" id="step-dot-2" title="Deadline Selection">2</div>
          <div class="bake-step-dot" id="step-dot-3" title="Project Brief Description">3</div>
        </div>

        <!-- SECTION 1: Service -->
        <div class="bake-section-title"><span class="num">1</span> Recipe Type <span style="font-size:0.85rem; font-weight:400; color:var(--text-secondary); margin-left: 0.25rem;">(Service)</span></div>
        <div class="form-row-2">
          <div class="form-group">
            <label class="form-label">Select Service</label>
            <select name="service_type" id="service-select" class="form-input" style="height:53px;" required onchange="updateStepProgress()">
              <option value="" disabled <?php echo empty($selectedService) ? 'selected' : ''; ?>>Choose a recipe...</option>
              <?php foreach ($services as $svc): ?>
                <option value="<?php echo htmlspecialchars($svc['title']); ?>"
                  data-price="<?php echo $svc['price_from_inr'] ?? 0; ?>"
                  data-market="<?php echo $svc['market_price_inr'] ?? 0; ?>"
                  <?php echo $selectedService == $svc['title'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($svc['title']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" id="language-group" style="display:none;">
            <label class="form-label">Content Language</label>
            <select name="content_language" class="form-input" style="height:53px;">
              <option value="english" <?php echo ($draft['content_language'] ?? 'english') == 'english' ? 'selected' : ''; ?>>English</option>
              <option value="malayalam" <?php echo ($draft['content_language'] ?? '') == 'malayalam' ? 'selected' : ''; ?>>Malayalam</option>
            </select>
          </div>
        </div>

        <!-- SECTION 2: Deadline -->
        <div class="bake-section-title" style="margin-top:2.5rem;"><span class="num">2</span> Deadline</div>
        <div class="form-group">
          <label class="form-label">Project Deadline <small style="color:var(--text-secondary); font-weight:400;">(Minimum 4 days from today)</small></label>
          <input type="date" name="deadline" class="form-input" id="deadline-input" required min="<?php echo $minDate; ?>" value="<?php echo htmlspecialchars($draft['deadline'] ?? ''); ?>" style="height:53px;" onchange="updateStepProgress()">
        </div>

        <!-- SECTION 3: Project Ingredients -->
        <div class="bake-section-title" style="margin-top:2.5rem;"><span class="num">3</span> Project Ingredients</div>
        
        <div class="form-group" style="margin-bottom: 1.5rem;">
          <label class="form-label">Project Assets & Reference Files <small style="color:var(--text-secondary); font-weight:400;">(Max 5 files, total up to 10MB. Include logos, documents, or reference files)</small></label>
          <input type="file" id="project-files" class="form-input" multiple style="padding: 0.6rem 0.75rem; height: auto;" onchange="uploadFilesImmediately(event)">
          <div id="file-list-preview" style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--text-secondary); font-weight: 600; display:flex; flex-direction:column; gap:6px;">
            <?php if (!empty($_SESSION['temp_files'])): ?>
              <?php foreach ($_SESSION['temp_files'] as $f): ?>
                <div class="temp-file-row" style="display:flex; justify-content:space-between; align-items:center; background:var(--bg-secondary); padding:6px 12px; border-radius:6px; border:1px solid var(--border-medium);" data-path="<?php echo htmlspecialchars($f['path']); ?>">
                  <span>📁 <?php echo htmlspecialchars($f['name']); ?></span>
                  <button type="button" style="background:none; border:none; color:#ef4444; font-weight:700; cursor:pointer;" onclick="deleteTempFile('<?php echo htmlspecialchars($f['path']); ?>')">Delete</button>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          <div id="upload-status" style="display:none; color:var(--accent-orange); font-size:0.85rem; font-weight:700; margin-top:0.5rem;">Uploading files to oven... ⏳</div>
        </div>

        <div class="form-group">
          <div class="textarea-header">
            <label class="form-label" style="margin:0;">Describe your project</label>
            <?php if (is_logged_in()): ?>
              <button type="button" class="ai-shimmer-btn" id="ai-gen-btn" onclick="generateAI()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px; height:16px;"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                ✨ Generate with AI
              </button>
            <?php else: ?>
              <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted);">Sign in to use AI assistant ✨</span>
            <?php endif; ?>
          </div>
          <textarea name="project_description" id="description-textarea" class="form-textarea" rows="5" placeholder="Tell us about your project, target audience, goals, style preferences..." required style="margin-top:0.75rem;" oninput="updateStepProgress()"><?php echo htmlspecialchars($draft['project_description'] ?? ''); ?></textarea>
          <div id="ai-loading" style="display:none; color:var(--accent-orange); font-size:0.88rem; font-weight:700; margin-top:0.5rem; animation: pulse-glow 1.5s infinite;">🤖 AI is kneading your project description...</div>
        </div>

        <!-- AI Suggestions Panel -->
        <div id="ai-suggestions-panel" class="admin-card" style="display:none; background: rgba(234, 88, 12, 0.03); border: 1.5px dashed var(--accent-orange); margin-top: 1rem; padding: 1.25rem; border-radius: 8px;">
          <strong style="color:var(--text-primary); font-size:0.9rem; display: block; margin-bottom: 0.5rem;">🤖 AI Suggested Project Brief:</strong>
          <p id="ai-suggestion-text" style="color:var(--text-secondary); font-size:0.88rem; line-height:1.5; white-space:pre-line; margin-bottom: 1rem; background:#fff; padding:10px; border-radius:6px; border:1px solid var(--border-medium);"></p>
          <div style="display:flex; gap:10px;">
            <button type="button" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.85rem;" onclick="applyAISuggestion()">Apply Suggestion</button>
            <button type="button" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.85rem;" onclick="discardAISuggestion()">Discard</button>
          </div>
        </div>

        <!-- AI Market Analysis -->
        <div style="margin-top: 1.5rem; margin-bottom: 1.5rem;">
          <?php if (is_logged_in()): ?>
            <button type="button" class="btn btn-secondary" id="market-analysis-btn" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; border-color: var(--accent-orange); color: var(--accent-orange); background: transparent;" onclick="runMarketAnalysis()">
              📊 Run AI Market Value Analysis
            </button>
          <?php else: ?>
            <div style="text-align:center; padding: 0.5rem; background: var(--bg-secondary); border-radius: 6px; font-size: 0.85rem; font-weight: 700; color: var(--text-muted);">Sign in to unlock AI Market Analysis & pricing comparison 📊</div>
          <?php endif; ?>
          <div id="market-analysis-loading" style="display:none; color:var(--accent-orange); font-size:0.88rem; font-weight:700; text-align:center; margin-top:0.5rem;">🤖 AI is doing a comparative market analysis...</div>
          
          <div id="market-analysis-card" class="admin-card" style="display:none; border: 1px solid var(--border-medium); margin-top: 1rem; padding: 1.5rem; border-radius: 8px; background: var(--bg-secondary);">
            <h4 style="color:var(--text-primary); margin-top:0; margin-bottom:1rem; font-size: 1.05rem;">📊 Project Cost & Market Analysis</h4>
            
            <div class="pricing-compare" style="margin-bottom: 1.5rem; display: flex; gap: 1rem;">
              <div class="price-box" style="box-shadow: var(--shadow-sm); flex: 1; text-align: center; background: #fff; padding: 12px; border-radius: 8px; border: 1px solid var(--border-medium);">
                <div class="price-label" style="font-size:0.8rem; color:var(--text-secondary); text-transform:uppercase;">Agency Market Average</div>
                <div class="price-val" id="analysis-market-price" style="font-size: 1.5rem; font-weight: 800; color: var(--text-secondary); margin-top: 4px;">₹0</div>
              </div>
              <div class="price-box ours" style="box-shadow: var(--shadow-sm); flex: 1; text-align: center; background: var(--bg-dark); padding: 12px; border-radius: 8px; border: 1.5px solid var(--accent-orange);">
                <div class="price-label" style="font-size:0.8rem; color:#C4A882; text-transform:uppercase;">Adloaf Exclusive Rate</div>
                <div class="price-val" id="analysis-adloaf-price" style="font-size: 1.5rem; font-weight: 800; color: #fff; margin-top: 4px;">₹0</div>
                <div class="price-save" id="analysis-savings-pct" style="display:inline-block; background:var(--accent-orange); color:#fff; font-size:0.7rem; padding:2px 8px; border-radius:10px; margin-top:4px; font-weight:700;">Save 35%</div>
              </div>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
              <strong style="color:var(--text-primary); font-size:0.85rem; display:block; margin-bottom: 0.5rem; text-transform:uppercase; letter-spacing:0.5px;">Standard agency price breakdown:</strong>
              <div id="analysis-breakdown-list" style="display:flex; flex-direction:column; gap:6px;"></div>
            </div>
            
            <div>
              <strong style="color:var(--text-primary); font-size:0.85rem; display:block; margin-bottom: 0.4rem; text-transform:uppercase; letter-spacing:0.5px;">Why Adloaf is better:</strong>
              <p id="analysis-text-content" style="color:var(--text-secondary); font-size:0.88rem; line-height:1.5; margin:0;"></p>
            </div>
          </div>
        </div>

        <!-- SECTION 4: Pricing -->
        <div id="pricing-panel" class="pricing-panel" style="display:none; margin-top: 2rem;">
          <div class="currency-row">
            <label style="font-weight: 700; color: var(--text-primary); font-size: 0.9rem;">View pricing in:</label>
            <select id="currency-select" onchange="updatePricing()" style="height: 38px; border-radius: 8px;">
              <option value="INR">🇮🇳 INR ₹</option>
              <option value="USD">🇺🇸 USD $</option>
              <option value="EUR">🇪🇺 EUR €</option>
              <option value="GBP">🇬🇧 GBP £</option>
              <option value="AED">🇦🇪 AED د.إ</option>
              <option value="SAR">🇸🇦 SAR ﷼</option>
              <option value="MYR">🇲🇾 MYR RM</option>
            </select>
          </div>
          <p style="color:var(--text-secondary); font-size:0.85rem; margin:0.5rem 0 1rem; font-weight: 600;">Approximate pricing for selected service:</p>
          <div class="pricing-compare" style="display: flex; gap: 1rem;">
            <div class="price-box ours" style="box-shadow: var(--shadow-sm); flex: 1;">
              <div class="price-label">Adloaf — Starting From</div>
              <div class="price-val" id="adloaf-price">₹0</div>
              <div class="price-save">Best Value</div>
            </div>
            <div class="price-box" style="box-shadow: var(--shadow-sm); flex: 1;">
              <div class="price-label">Market Average</div>
              <div class="price-val" id="market-price" style="color:var(--text-secondary);">₹0</div>
              <div style="color:#10b981; font-size:0.8rem; font-weight:700; margin-top: 4px;" id="savings-text"></div>
            </div>
          </div>
        </div>

        <div style="margin-top:2.5rem; padding-top:2rem; border-top:1px solid var(--border-medium);">
          <button type="submit" class="btn btn-primary btn-loaf" style="width:100%; padding:1.1rem; font-size:1.1rem; box-shadow: 0 10px 24px rgba(234,88,12,0.2);" onclick="showOven(event)">
            🍞 Bake the Dough
          </button>
          <p style="color:var(--text-secondary); font-size:0.85rem; text-align:center; margin-top:1rem; font-weight: 600;">
            <?php if (!$user): ?>Submitting will ask you to sign in/up. Your description draft will be saved.<?php else: ?>Our team will review and reply on WhatsApp within 24 hours.<?php endif; ?>
          </p>
        </div>
      </div>
    </form>
    <?php endif; ?>
  </div>
</div>

<!-- Footer Section -->
<footer class="footer">
  <div class="container">
    <div class="footer-top">
      <div class="footer-brand">
        <a href="index.php" class="logo footer-logo">
          <div class="logo-icon-wrap">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
               <path d="M3 15C3 13 4.5 10.5 7 10.5C9.5 10.5 10 12 12 12C14 12 14.5 10.5 17 10.5C19.5 10.5 21 13 21 15C21 18.5 18.5 20 12 20C5.5 20 3 18.5 3 15Z"/>
            </svg>
          </div>
          <span class="logo-text">adloaf<span class="logo-dot">.</span></span>
        </a>
        <p class="footer-desc">Freshly baked marketing strategies, brand visual concepts, graphic assets, and dynamic web interfaces designed to help brands grow.</p>
      </div>
      <div class="footer-links-col">
        <h3 class="footer-col-title">Navigation</h3>
        <ul class="footer-links-list">
          <li><a href="index.php" class="footer-link">Home</a></li>
          <li><a href="works.php" class="footer-link">Our Works</a></li>
          <li><a href="pricing.php" class="footer-link">Menu Pricing</a></li>
          <li><a href="bake.php" class="footer-link">Bake a Request</a></li>
        </ul>
      </div>
      <div class="footer-meta-col">
        <h3 class="footer-col-title">Get In Touch</h3>
        <div class="footer-contact-item">
          <span>WhatsApp Chat</span>
          <a href="https://wa.me/<?php echo ADMIN_WA; ?>" style="color: #fff; font-weight: 600;">+91 6282 563 209</a>
        </div>
        <div class="footer-contact-item">
          <span>Official Email</span>
          <a href="mailto:hello@adloaf.com" style="color: #fff; font-weight: 600;">hello@adloaf.com</a>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; 2026 adloaf Creative Agency. All rights reserved.</p>
    </div>
  </div>
</footer>

<!-- WhatsApp Widget -->
<a href="https://wa.me/<?php echo ADMIN_WA; ?>?text=Hi%20Adloaf!%20I%20want%20to%20discuss%20a%20project." target="_blank" class="wa-float" id="wa-float" aria-label="Chat on WhatsApp">
  <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
</a>

<script>
// Hamburger toggle
const menuToggle = document.getElementById('menu-toggle');
const navMenu    = document.getElementById('nav-menu');

menuToggle.addEventListener('click', () => {
  const expanded = menuToggle.getAttribute('aria-expanded') === 'true';
  menuToggle.setAttribute('aria-expanded', !expanded);
  menuToggle.classList.toggle('active');
  navMenu.classList.toggle('open');
});

const exchangeRates = { INR:1, USD:0.012, EUR:0.011, GBP:0.0094, AED:0.044, SAR:0.045, MYR:0.056 };
const currencySymbols = { INR:'₹', USD:'$', EUR:'€', GBP:'£', AED:'د.إ', SAR:'﷼', MYR:'RM' };

// Language field toggle
const languageServices = ['Graphic Design','Brand Identity','Social Media Creatives'];
document.getElementById('service-select').addEventListener('change', function() {
  const lg = document.getElementById('language-group');
  if (lg) {
    lg.style.display = languageServices.includes(this.value) ? 'block' : 'none';
  }
});

function updateStepProgress() {
  const service = document.getElementById('service-select').value;
  const deadline = document.getElementById('deadline-input').value;
  const description = document.getElementById('description-textarea').value.trim();
  
  // Step 1 check
  if (service) {
    document.getElementById('step-dot-1').classList.add('active');
  } else {
    document.getElementById('step-dot-1').classList.add('active'); // Keep first one highlighted
  }
  
  // Step 2 check
  if (deadline) {
    document.getElementById('step-dot-2').classList.add('active');
  } else {
    document.getElementById('step-dot-2').classList.remove('active');
  }
  
  // Step 3 check
  if (description.length > 5) {
    document.getElementById('step-dot-3').classList.add('active');
  } else {
    document.getElementById('step-dot-3').classList.remove('active');
  }
}

function updatePricing() {
  const sel    = document.getElementById('service-select');
  const opt    = sel.options[sel.selectedIndex];
  const currSelect = document.getElementById('currency-select');
  if (!currSelect) return;
  const curr   = currSelect.value;
  const rate   = exchangeRates[curr] || 1;
  const sym    = currencySymbols[curr] || curr;
  const panel  = document.getElementById('pricing-panel');

  if (!opt || !opt.dataset.price || opt.value === '') { panel.style.display='none'; return; }

  const adloaf = parseFloat(opt.dataset.price || 0);
  const market = parseFloat(opt.dataset.market || 0);

  if (adloaf > 0) {
    panel.style.display = 'block';
    document.getElementById('adloaf-price').textContent = sym + Math.round(adloaf * rate).toLocaleString();
    document.getElementById('market-price').textContent = market > 0 ? sym + Math.round(market * rate).toLocaleString() : 'Varies';
    if (market > adloaf) {
      const saving = Math.round((market - adloaf) * rate);
      document.getElementById('savings-text').textContent = 'Save ' + sym + saving.toLocaleString() + ' with Adloaf!';
    } else {
      document.getElementById('savings-text').textContent = '';
    }
  } else {
    panel.style.display = 'none';
  }
  updateMarketAnalysisPricing();
}

function displayError(msg) {
  const errCard = document.getElementById('js-error-card');
  if (errCard) {
    errCard.textContent = msg;
    errCard.style.display = 'block';
    errCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
}

let lastMarketAnalysis = null;
let formSubmitted = false;

function updateMarketAnalysisPricing() {
  if (!lastMarketAnalysis) return;
  const currSelect = document.getElementById('currency-select');
  const curr   = currSelect ? currSelect.value : 'INR';
  const rate   = exchangeRates[curr] || 1;
  const sym    = currencySymbols[curr] || curr;
  
  const marketVal = parseFloat(lastMarketAnalysis.market_price);
  const adloafVal = parseFloat(lastMarketAnalysis.adloaf_price);
  
  document.getElementById('analysis-market-price').textContent = sym + Math.round(marketVal * rate).toLocaleString();
  document.getElementById('analysis-adloaf-price').textContent = sym + Math.round(adloafVal * rate).toLocaleString();
  
  const pct = Math.round(((marketVal - adloafVal) / marketVal) * 100);
  document.getElementById('analysis-savings-pct').textContent = `Save ${pct}%`;
  
  // Render breakdown list
  const list = document.getElementById('analysis-breakdown-list');
  list.innerHTML = '';
  if (lastMarketAnalysis.breakdown && lastMarketAnalysis.breakdown.length > 0) {
    lastMarketAnalysis.breakdown.forEach(item => {
      const itemRow = document.createElement('div');
      itemRow.style.cssText = 'display:flex; justify-content:space-between; font-size:0.85rem; border-bottom:1px solid var(--border-medium); padding: 4px 0; color:var(--text-secondary);';
      
      const nameSpan = document.createElement('span');
      nameSpan.textContent = item.item;
      
      const priceSpan = document.createElement('span');
      priceSpan.style.fontWeight = '700';
      priceSpan.textContent = sym + Math.round(item.price * rate).toLocaleString();
      
      itemRow.appendChild(nameSpan);
      itemRow.appendChild(priceSpan);
      list.appendChild(itemRow);
    });
  }
}

async function uploadFilesImmediately(event) {
  const input = event.target;
  if (!input.files || input.files.length === 0) return;
  
  const files = Array.from(input.files);
  const errCard = document.getElementById('js-error-card');
  if (errCard) errCard.style.display = 'none';
  
  // Client side validation
  if (files.length > 5) {
    displayError('You can upload a maximum of 5 files.');
    input.value = '';
    return;
  }
  
  const blacklist = ['php', 'phtml', 'php5', 'php7', 'phps', 'htaccess', 'exe', 'js', 'bat', 'cmd', 'sh', 'com', 'scr', 'msi', 'vbs'];
  let totalSize = 0;
  for (let file of files) {
    totalSize += file.size;
    const ext = file.name.split('.').pop().toLowerCase();
    if (blacklist.includes(ext)) {
      displayError('Dangerous file extension (.' + ext + ') is not allowed.');
      input.value = '';
      return;
    }
  }
  
  if (totalSize > 10 * 1024 * 1024) {
    displayError('Total size of all files must not exceed 10MB.');
    input.value = '';
    return;
  }
  
  const statusDiv = document.getElementById('upload-status');
  if (statusDiv) statusDiv.style.display = 'block';
  
  const formData = new FormData();
  formData.append('action', 'upload_temp');
  formData.append('csrf_token', document.querySelector('[name=csrf_token]').value);
  for (let i = 0; i < files.length; i++) {
    formData.append('project_files[]', files[i]);
  }
  
  try {
    const resp = await fetch('bake.php', {
      method: 'POST',
      body: formData
    });
    const data = await resp.json();
    if (data.success) {
      renderTempFilesList(data.files);
    } else if (data.error) {
      displayError(data.error);
    }
  } catch (e) {
    displayError('Failed to upload files. Please try again.');
  } finally {
    if (statusDiv) statusDiv.style.display = 'none';
    input.value = '';
  }
}

function renderTempFilesList(files) {
  const preview = document.getElementById('file-list-preview');
  if (!preview) return;
  preview.innerHTML = '';
  if (!files || files.length === 0) return;
  
  files.forEach(file => {
    const row = document.createElement('div');
    row.className = 'temp-file-row';
    row.style.cssText = 'display:flex; justify-content:space-between; align-items:center; background:var(--bg-secondary); padding:6px 12px; border-radius:6px; border:1px solid var(--border-medium);';
    row.setAttribute('data-path', file.path);
    
    const span = document.createElement('span');
    span.textContent = '📁 ' + file.name;
    
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.style.cssText = 'background:none; border:none; color:#ef4444; font-weight:700; cursor:pointer;';
    btn.textContent = 'Delete';
    btn.onclick = () => deleteTempFile(file.path);
    
    row.appendChild(span);
    row.appendChild(btn);
    preview.appendChild(row);
  });
}

async function deleteTempFile(path) {
  const errCard = document.getElementById('js-error-card');
  if (errCard) errCard.style.display = 'none';
  
  try {
    const resp = await fetch('bake.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: new URLSearchParams({
        action: 'delete_temp_file',
        path: path,
        csrf_token: document.querySelector('[name=csrf_token]').value
      })
    });
    const data = await resp.json();
    if (data.success) {
      renderTempFilesList(data.files);
    } else if (data.error) {
      displayError(data.error);
    }
  } catch (e) {
    displayError('Failed to delete the file.');
  }
}

async function generateAI() {
  const service = document.getElementById('service-select').value;
  const desc    = document.getElementById('description-textarea').value;
  const deadline= document.getElementById('deadline-input').value;
  const errCard = document.getElementById('js-error-card');
  
  if (errCard) errCard.style.display = 'none';

  if (!service) {
    displayError('Please select a service type first.');
    return;
  }
  if (!desc.trim()) {
    displayError('Please describe your project briefly in the textarea so the AI has context to generate.');
    return;
  }
  
  document.getElementById('ai-loading').style.display = 'block';
  const aiBtn = document.getElementById('ai-gen-btn');
  if (aiBtn) aiBtn.disabled = true;
  
  try {
    const resp = await fetch('bake.php', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body: new URLSearchParams({
        action:'ai_generate',
        service, deadline,
        description: desc,
        csrf_token: document.querySelector('[name=csrf_token]').value
      })
    });
    const data = await resp.json();
    if (data.text) {
      const suggestionsPanel = document.getElementById('ai-suggestions-panel');
      const suggestionText = document.getElementById('ai-suggestion-text');
      if (suggestionsPanel && suggestionText) {
        suggestionText.textContent = data.text;
        suggestionsPanel.style.display = 'block';
      }
    } else if (data.error) {
      displayError('AI Service Error: ' + data.error);
    }
  } catch(e) { 
    displayError('AI generation request failed. Please check your connection and try again.'); 
  } finally {
    document.getElementById('ai-loading').style.display = 'none';
    if (aiBtn) aiBtn.disabled = false;
  }
}

function applyAISuggestion() {
  const suggestionText = document.getElementById('ai-suggestion-text').textContent;
  document.getElementById('description-textarea').value = suggestionText;
  document.getElementById('ai-description-hidden').value = suggestionText;
  
  const suggestionsPanel = document.getElementById('ai-suggestions-panel');
  if (suggestionsPanel) {
    suggestionsPanel.style.display = 'none';
  }
  
  updateStepProgress();
  cleanupTempFilesSilently();
}

function discardAISuggestion() {
  const suggestionsPanel = document.getElementById('ai-suggestions-panel');
  if (suggestionsPanel) {
    suggestionsPanel.style.display = 'none';
  }
}

async function cleanupTempFilesSilently() {
  try {
    await fetch('bake.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: new URLSearchParams({
        action: 'ai_cleanup',
        csrf_token: document.querySelector('[name=csrf_token]').value
      })
    });
    renderTempFilesList([]);
  } catch (e) {
    console.error('Silently cleaning up temp files failed', e);
  }
}

async function runMarketAnalysis() {
  const service = document.getElementById('service-select').value;
  const desc    = document.getElementById('description-textarea').value;
  const errCard = document.getElementById('js-error-card');
  
  if (errCard) errCard.style.display = 'none';
  
  if (!service) {
    displayError('Please select a service type first.');
    return;
  }
  if (!desc.trim()) {
    displayError('Please describe your project first so the AI can analyze your project ingredients.');
    return;
  }
  
  const loadingDiv = document.getElementById('market-analysis-loading');
  const analysisCard = document.getElementById('market-analysis-card');
  const runBtn = document.getElementById('market-analysis-btn');
  
  if (loadingDiv) loadingDiv.style.display = 'block';
  if (analysisCard) analysisCard.style.display = 'none';
  if (runBtn) runBtn.disabled = true;
  
  try {
    const resp = await fetch('bake.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: new URLSearchParams({
        action: 'ai_market_analysis',
        service,
        description: desc,
        csrf_token: document.querySelector('[name=csrf_token]').value
      })
    });
    const data = await resp.json();
    if (data.error) {
      displayError('Market Analysis Error: ' + data.error);
    } else if (data.market_price) {
      lastMarketAnalysis = data;
      updateMarketAnalysisPricing();
      if (analysisCard) analysisCard.style.display = 'block';
    }
  } catch (e) {
    displayError('Failed to run market value analysis. Please try again.');
  } finally {
    if (loadingDiv) loadingDiv.style.display = 'none';
    if (runBtn) runBtn.disabled = false;
  }
}

function showOven(e) {
  const form = document.getElementById('bake-form');
  const errCard = document.getElementById('js-error-card');
  if (errCard) errCard.style.display = 'none';
  
  if (form.checkValidity()) {
    formSubmitted = true;
    document.getElementById('oven-overlay').classList.add('active');
  } else {
    const service = document.getElementById('service-select').value;
    const deadline = document.getElementById('deadline-input').value;
    const description = document.getElementById('description-textarea').value.trim();
    if (!service || !deadline || !description) {
      e.preventDefault();
      displayError('Please fill in all required fields (Service, Deadline, and Project Description) before baking.');
    }
  }
}

window.addEventListener('beforeunload', function() {
  if (!formSubmitted) {
    const formData = new FormData();
    formData.append('action', 'ai_cleanup');
    formData.append('csrf_token', document.querySelector('[name=csrf_token]').value);
    navigator.sendBeacon('bake.php', formData);
  }
});

// Initial triggers
document.getElementById('service-select').dispatchEvent(new Event('change'));
updateStepProgress();
updatePricing();
</script>
</body>
</html>

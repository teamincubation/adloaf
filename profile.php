<?php
require_once __DIR__ . '/lib/helpers.php';

// Force sign in
require_login('profile.php');

$user = current_user();
$currencySymbol = site_setting('base_currency_symbol', '₹');
$error = '';
$success = '';

// Handle Profile Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) die("Invalid request.");

    $whatsapp      = preg_replace('/[^0-9+]/', '', $_POST['whatsapp'] ?? '');
    $country       = htmlspecialchars(strip_tags($_POST['country'] ?? ''));
    $state         = htmlspecialchars(strip_tags($_POST['state'] ?? ''));
    $city          = htmlspecialchars(strip_tags($_POST['city'] ?? ''));
    $aboutBusiness = htmlspecialchars(strip_tags($_POST['about_business'] ?? ''));
    $photoUrl      = $user['profile_photo'];

    // Handle Profile Photo Upload
    if (isset($_FILES['profile_photo_file']) && $_FILES['profile_photo_file']['error'] === UPLOAD_ERR_OK) {
        try {
            $photoPath = handle_upload('profile_photo_file', 'profile/');
            if ($photoPath) {
                $photoUrl = $photoPath;
            }
        } catch(Exception $e) {
            $error = $e->getMessage();
        }
    }

    if (empty($error)) {
        $stmt = $pdo->prepare("UPDATE users_public SET 
            whatsapp = ?, 
            country = ?, 
            state = ?, 
            city = ?, 
            about_business = ?, 
            profile_photo = ? 
            WHERE id = ?");
        $stmt->execute([
            $whatsapp ?: $user['whatsapp'],
            $country ?: $user['country'],
            $state ?: $user['state'],
            $city ?: $user['city'],
            $aboutBusiness ?: $user['about_business'],
            $photoUrl,
            $user['id']
        ]);
        $success = "Profile updated successfully!";
        // Refresh local user data cache
        $_SESSION['user_name'] = $user['full_name'];
        $user = current_user();
    }
}

// Fetch user's bake requests
$reqStmt = $pdo->prepare("SELECT * FROM bake_requests WHERE user_id = ? ORDER BY created_at DESC");
$reqStmt->execute([$user['id']]);
$bakeRequests = $reqStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Account | Adloaf</title>
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
  
  <style>
    .status-pill { display:inline-block; padding:4px 10px; border-radius:20px; font-size:0.75rem; font-weight:700; }
    .status-Pending   { background:rgba(239,68,68,0.1); color:#ef4444; }
    .status-Accepted  { background:rgba(245,158,11,0.1); color:#f59e0b; }
    .status-Approved  { background:rgba(16,185,129,0.1); color:#10b981; }
    .status-Rejected  { background:rgba(107,114,128,0.1); color:#9ca3af; }
    .status-Completed { background:rgba(99,102,241,0.1); color:#818cf8; }
  </style>
</head>
<body>

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
          <li class="mobile-only"><a href="profile.php" class="nav-link active">Profile Dashboard</a></li>
          <li class="mobile-only"><a href="auth/logout.php" class="nav-link" style="color: #ef4444;">Logout</a></li>
        <?php else: ?>
          <li class="mobile-only"><a href="auth/login.php?next=profile.php" class="nav-link">Sign In</a></li>
        <?php endif; ?>
        <li class="mobile-only" style="margin-top: 1rem;"><a href="bake.php" class="btn btn-primary" style="width: 100%; text-align: center; padding: 0.75rem;">Bake a Project</a></li>
      </ul>
    </nav>

    <div class="header-actions">
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
      
      <a href="bake.php" class="btn btn-primary btn-header" id="header-cta" style="margin-left: 0.5rem;">Bake a Project</a>
      <button class="menu-toggle" id="menu-toggle" aria-label="Toggle Navigation Menu" aria-controls="nav-menu" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</header>

<div class="profile-page">
  <div class="container">
    <div class="profile-layout-grid">
      <!-- SIDEBAR CARD: Profile Summary -->
      <div class="auth-center-card" style="padding: 2.5rem 1.5rem; text-align: center; max-width: 100%;">
        <form method="POST" enctype="multipart/form-data" id="avatar-form">
          <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
          <div class="avatar-upload-container">
            <div class="avatar-preview-box">
              <img src="<?php echo $user['profile_photo'] ? htmlspecialchars($user['profile_photo']) : 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($user['email']))) . '?d=mp&s=150'; ?>" alt="Avatar" id="avatar-img">
              <label for="profile-photo-input" class="avatar-upload-overlay">
                Click to Upload Smiling Photo
              </label>
            </div>
            <input type="file" name="profile_photo_file" id="profile-photo-input" style="display:none;" accept="image/*" onchange="previewAvatar(event)">
          </div>
        </form>
        
        <h2 class="auth-title" style="font-size: 1.4rem; margin-bottom: 0.25rem;"><?php echo htmlspecialchars($user['full_name']); ?></h2>
        <p style="color:var(--text-secondary); font-size:0.85rem; margin-bottom: 1rem;"><?php echo htmlspecialchars($user['email']); ?></p>
        
        <a href="tel:<?php echo htmlspecialchars(site_setting('whatsapp_admin', '916282563209')); ?>" class="btn btn-primary" style="display: block; width: 100%; text-decoration: none; font-weight: 700; padding: 0.8rem 1rem; border-radius: var(--radius-sm); text-align: center; margin-top: 1.5rem;">
          📞 Call Admin Directly
        </a>
      </div>

      <!-- MAIN CONTENT: Edit Details & History -->
      <div>
        <?php if ($success): ?>
          <div class="success-toast" style="margin-bottom: 1.5rem;"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div style="background:rgba(239,68,68,0.1); color:#ef4444; padding:1rem; border-radius:8px; margin-bottom:1.5rem; font-weight:700; border: 1px solid rgba(239,68,68,0.2);"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="profile-nav-tabs">
          <button class="profile-tab-btn active" onclick="switchTab('details', event)">👤 Account Settings</button>
          <button class="profile-tab-btn" onclick="switchTab('history', event)">🍞 My Oven Requests (<?php echo count($bakeRequests); ?>)</button>
        </div>

        <!-- TAB CONTENT: Account Settings -->
        <div id="tab-details" class="profile-tab-content">
          <div class="auth-center-card" style="max-width: 100%; box-shadow: none;">
            <form method="POST" enctype="multipart/form-data">
              <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
              <input type="hidden" name="whatsapp" id="whatsapp-full-input" value="<?php echo htmlspecialchars($user['whatsapp']); ?>">

              <div class="form-row-2">
                <div class="form-group">
                  <label class="form-label">WhatsApp Number</label>
                  <input type="tel" name="whatsapp_display" class="form-input" value="<?php echo htmlspecialchars($user['whatsapp']); ?>" style="height: 52px;" required oninput="document.getElementById('whatsapp-full-input').value = this.value">
                </div>
                <div class="form-group">
                  <label class="form-label">Business / Brand Name</label>
                  <input type="text" name="business_display" class="form-input" value="<?php echo htmlspecialchars($user['full_name']); ?>" style="height: 52px;" disabled>
                </div>
              </div>

              <!-- Location Grid -->
              <div style="margin-top: 1rem; margin-bottom: 0.5rem; font-weight: 700; color: var(--text-primary); font-size: 0.95rem;">Location Settings</div>
              
              <div id="location-select-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                <div class="form-group">
                  <label class="form-label">Country</label>
                  <select name="country" id="country-select" class="form-input" style="height: 52px;" onchange="loadStates()">
                    <option value="">Choose Country...</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">State</label>
                  <select name="state" id="state-select" class="form-input" style="height: 52px;" onchange="loadCities()">
                    <option value="">Choose State...</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">City</label>
                  <select name="city" id="city-select" class="form-input" style="height: 52px;">
                    <option value="">Choose City...</option>
                  </select>
                </div>
              </div>

              <div style="margin-top: -0.5rem; margin-bottom: 1.5rem; text-align: right;">
                <button type="button" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.75rem; border-radius: 6px;" onclick="toggleLocationFields()">Type Location Manually</button>
              </div>

              <div id="location-manual-grid" style="display: none; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                <div class="form-group">
                  <label class="form-label">Country</label>
                  <input type="text" name="manual_country" id="manual-country" class="form-input" placeholder="e.g. India" style="height: 52px;" value="<?php echo htmlspecialchars($user['country'] ?? ''); ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">State</label>
                  <input type="text" name="manual_state" id="manual-state" class="form-input" placeholder="e.g. Kerala" style="height: 52px;" value="<?php echo htmlspecialchars($user['state'] ?? ''); ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">City / Place</label>
                  <input type="text" name="manual_city" id="manual-city" class="form-input" placeholder="e.g. Calicut" style="height: 52px;" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">About My Business</label>
                <textarea name="about_business" class="form-textarea" rows="4" placeholder="Briefly describe your business, niche, target customers, and details to help Adloaf bake better custom brand strategies for you..."><?php echo htmlspecialchars($user['about_business'] ?? ''); ?></textarea>
              </div>

              <button type="submit" class="btn btn-primary" style="padding: 0.9rem 2rem; font-size: 1rem; border-radius: var(--radius-sm); margin-top: 1.25rem;">Save Settings</button>
            </form>
          </div>
        </div>

        <!-- TAB CONTENT: Request History -->
        <div id="tab-history" class="profile-tab-content" style="display:none;">
          <?php if (empty($bakeRequests)): ?>
            <div class="auth-center-card" style="max-width: 100%; box-shadow: none; text-align: center; padding: 3rem 1.5rem;">
              <span style="font-size: 3rem;">🥐</span>
              <h3 style="margin-top:1rem; color: var(--text-primary);">Oven is Empty!</h3>
              <p style="color:var(--text-secondary); font-size:0.9rem; margin-bottom:1.5rem;">You haven't submitted any project recipe requests yet.</p>
              <a href="bake.php" class="btn btn-primary btn-loaf">Bake a Project Now</a>
            </div>
          <?php else: ?>
            <?php foreach ($bakeRequests as $req): ?>
              <div class="request-card-row">
                <div class="request-card-header">
                  <div>
                    <h3 style="font-size: 1.15rem; font-weight: 800; color:var(--text-primary);"><?php echo htmlspecialchars($req['service_type']); ?></h3>
                    <span style="font-size:0.82rem; color:var(--text-secondary);">Submitted: <?php echo date('M d, Y', strtotime($req['created_at'])); ?></span>
                  </div>
                  <div>
                    <span class="status-pill status-<?php echo $req['status']; ?>"><?php echo $req['status']; ?></span>
                  </div>
                </div>
                
                <div style="display: flex; gap: 2rem; margin-top: 1rem; font-size: 0.85rem; color: var(--text-secondary); border-top: 1px dashed var(--border-medium); padding-top: 0.75rem; flex-wrap: wrap;">
                  <div>📅 <strong>Deadline:</strong> <?php echo date('M d, Y', strtotime($req['deadline'])); ?></div>
                  <div>💰 <strong>Est. Price:</strong> <?php echo $currencySymbol; ?><?php echo number_format($req['estimated_price_inr'], 2); ?></div>
                  <?php if ($req['total_cost'] > 0): ?>
                    <div>💵 <strong>Final Cost:</strong> <span style="color:var(--accent-orange); font-weight:700;"><?php echo $currencySymbol; ?><?php echo number_format($req['total_cost'], 2); ?></span></div>
                  <?php endif; ?>
                  <div>🌎 <strong>Lang:</strong> <?php echo htmlspecialchars(ucfirst($req['content_language'] ?? 'English')); ?></div>
                </div>

                <?php if (!empty($req['uploaded_files'])): ?>
                  <div style="margin-top: 1rem; border-top: 1px dashed var(--border-medium); padding-top: 0.75rem;">
                    <strong style="color:var(--text-primary); font-size:0.85rem; display: block; margin-bottom: 0.4rem;">📁 Uploaded Reference Ingredients:</strong>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                      <?php foreach (json_decode($req['uploaded_files'], true) ?: [] as $f): ?>
                        <a href="download.php?request_id=<?php echo $req['id']; ?>&file=<?php echo urlencode(basename($f['path'])); ?>" style="background: var(--bg-secondary); border: 1px solid var(--border-medium); padding: 5px 10px; border-radius: 6px; font-size: 0.8rem; color: var(--accent-orange); font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                          📥 <?php echo htmlspecialchars($f['name']); ?>
                        </a>
                      <?php endforeach; ?>
                    </div>
                  </div>
                <?php endif; ?>

                <div style="margin-top: 1rem; text-align: right;">
                  <button type="button" class="btn btn-secondary" style="padding: 4px 12px; font-size: 0.8rem; border-radius: 6px;" onclick="toggleBrief(<?php echo $req['id']; ?>)">View Project Description ▾</button>
                </div>

                <div id="brief-<?php echo $req['id']; ?>" class="request-card-expander" style="display:none;">
                  <strong>Project Description:</strong>
                  <p style="margin-top: 0.25rem; white-space: pre-line; line-height: 1.5; font-size: 0.9rem; background: var(--bg-primary); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-medium);"><?php echo htmlspecialchars($req['project_description']); ?></p>
                  
                  <?php if ($req['admin_notes']): ?>
                    <div style="margin-top: 1rem; background: rgba(234, 88, 12, 0.05); border: 1px solid rgba(234, 88, 12, 0.15); padding: 1rem; border-radius: 8px;">
                      <strong style="color: var(--accent-orange);">Admin Response Note:</strong>
                      <p style="margin-top: 0.25rem; white-space: pre-line; line-height: 1.5; font-size: 0.9rem;"><?php echo htmlspecialchars($req['admin_notes']); ?></p>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
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

// Switch profile tabs
function switchTab(tabId, event) {
  document.querySelectorAll('.profile-tab-btn').forEach(btn => btn.classList.remove('active'));
  document.querySelectorAll('.profile-tab-content').forEach(tab => tab.style.display = 'none');
  
  event.target.classList.add('active');
  document.getElementById('tab-' + tabId).style.display = 'block';
}

function toggleBrief(requestId) {
  const element = document.getElementById('brief-' + requestId);
  element.style.display = element.style.display === 'none' ? 'block' : 'none';
}

function previewAvatar(event) {
  const input = event.target;
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      document.getElementById('avatar-img').src = e.target.result;
    };
    reader.readAsDataURL(input.files[0]);
    
    // Automatically submit photo upload form
    document.getElementById('avatar-form').submit();
  }
}

// Location lookup script
let countriesData = [];
let modeIsManual = false;

function toggleLocationFields() {
  const gridSelect = document.getElementById('location-select-grid');
  const gridManual = document.getElementById('location-manual-grid');
  
  if (modeIsManual) {
    gridSelect.style.display = 'grid';
    gridManual.style.display = 'none';
    modeIsManual = false;
  } else {
    gridSelect.style.display = 'none';
    gridManual.style.display = 'grid';
    modeIsManual = true;
    
    // Clear select inputs so manual data saves
    document.getElementById('country-select').value = '';
    document.getElementById('state-select').value = '';
    document.getElementById('city-select').value = '';
  }
}

async function loadCountries() {
  const select = document.getElementById('country-select');
  try {
    const res = await fetch('https://countriesnow.space/api/v0.1/countries');
    const data = await res.json();
    if (!data.error && data.data) {
      data.data.forEach(item => {
        const opt = document.createElement('option');
        opt.value = item.country;
        opt.textContent = item.country;
        if (opt.value === '<?php echo htmlspecialchars($user["country"] ?? ""); ?>') {
          opt.selected = true;
        }
        select.appendChild(opt);
      });
      // Load states based on initial country selection
      if (select.value) loadStates();
    }
  } catch(e) {
    // If API fails, default to manual input
    toggleLocationFields();
  }
}

async function loadStates() {
  const country = document.getElementById('country-select').value;
  const select = document.getElementById('state-select');
  select.innerHTML = '<option value="">Choose State...</option>';
  
  // Update manual fields in the background
  document.getElementById('manual-country').value = country;
  
  if (!country) return;
  try {
    const res = await fetch('https://countriesnow.space/api/v0.1/countries/states', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ country })
    });
    const data = await res.json();
    if (!data.error && data.data && data.data.states) {
      data.data.states.forEach(item => {
        const opt = document.createElement('option');
        opt.value = item.name;
        opt.textContent = item.name;
        if (opt.value === '<?php echo htmlspecialchars($user["state"] ?? ""); ?>') {
          opt.selected = true;
        }
        select.appendChild(opt);
      });
      if (select.value) loadCities();
    }
  } catch(e) {}
}

async function loadCities() {
  const country = document.getElementById('country-select').value;
  const state = document.getElementById('state-select').value;
  const select = document.getElementById('city-select');
  select.innerHTML = '<option value="">Choose City...</option>';
  
  // Update manual fields in the background
  document.getElementById('manual-state').value = state;

  if (!country || !state) return;
  try {
    const res = await fetch('https://countriesnow.space/api/v0.1/countries/state/cities', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ country, state })
    });
    const data = await res.json();
    if (!data.error && data.data) {
      data.data.forEach(cityName => {
        const opt = document.createElement('option');
        opt.value = cityName;
        opt.textContent = cityName;
        if (opt.value === '<?php echo htmlspecialchars($user["city"] ?? ""); ?>') {
          opt.selected = true;
        }
        select.appendChild(opt);
      });
    }
  } catch(e) {}
}

document.getElementById('city-select').addEventListener('change', function() {
  document.getElementById('manual-city').value = this.value;
});

// Initialize dynamic location selector
loadCountries();

// Handle manual locations if preset
<?php if ($user['country'] && !$user['state']): ?>
toggleLocationFields();
<?php endif; ?>
</script>
</body>
</html>

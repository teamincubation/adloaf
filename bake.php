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

// Handle AI Description Generator (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ai_generate') {
    header('Content-Type: application/json');
    if (!is_logged_in()) { echo json_encode(['error' => 'Not logged in']); exit; }
    $u      = current_user();
    $result = ai_generate_description(
        $_POST['service']     ?? '',
        $_POST['deadline']    ?? '',
        $_POST['description'] ?? '',
        $u['full_name'],
        $u['about_business'] ?? ''
    );
    echo json_encode(['text' => $result ?: 'Could not generate. Please try again.']);
    exit;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_bake') {
    // Save draft to session first (so data is preserved even if not logged in)
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
        // Get service pricing
        $svc = $pdo->prepare("SELECT price_from_inr, market_price_inr FROM services WHERE title LIKE ? LIMIT 1");
        $svc->execute(['%' . $data['service_type'] . '%']);
        $svcData = $svc->fetch();

        $stmt = $pdo->prepare("INSERT INTO bake_requests 
            (user_id, service_type, content_language, deadline, project_description, ai_generated_desc, estimated_price_inr, market_price_inr)
            VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $_SESSION['user_id'],
            $data['service_type'],
            $data['content_language'],
            $data['deadline'],
            $data['project_description'],
            $_POST['ai_description'] ?? null,
            $svcData['price_from_inr'] ?? 0,
            $svcData['market_price_inr'] ?? 0,
        ]);

        // Send confirmation email
        try {
            $mailer = new Mailer();
            $mailer->sendBakeConfirmation($user['email'], $user['full_name'], $data['service_type'], $data['deadline']);
        } catch(Exception $e) {}

        // Clear draft
        unset($_SESSION['bake_draft']);
        $submitted = true;
    }
}

// Get services for the form
$services   = $pdo->query("SELECT * FROM services ORDER BY sort_order ASC")->fetchAll();
$minDate    = date('Y-m-d', strtotime('+4 days'));

// Restore draft values
$draft = $_SESSION['bake_draft'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bake a Project | Adloaf</title>
  <meta name="description" content="Submit your creative project request to Adloaf — the freshly baked design agency.">
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root { --oven-orange:#EA580C; --oven-amber:#D97706; }
    .bake-page { min-height:100vh; background:var(--bg-primary); padding-top:100px; padding-bottom:60px; }
    .bake-container { max-width:800px; margin:0 auto; padding:0 1.5rem; }
    .bake-header { text-align:center; margin-bottom:3rem; }
    .bake-title { font-size:2.5rem; font-weight:800; color:var(--text-primary); margin-bottom:0.5rem; }
    .bake-title span { background:linear-gradient(135deg,#EA580C,#D97706); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
    .bake-subtitle { color:var(--text-secondary); font-size:1.05rem; }

    /* Auth Wall */
    .auth-wall { background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:20px; padding:2rem; margin-bottom:2rem; }
    .auth-wall-tabs { display:flex; gap:1rem; margin-bottom:1.5rem; border-bottom:1px solid var(--border-color); padding-bottom:1rem; }
    .auth-tab { background:none; border:none; color:var(--text-secondary); font-size:1rem; font-family:inherit; cursor:pointer; padding:0.5rem 1rem; border-radius:8px; font-weight:600; transition:all 0.2s; }
    .auth-tab.active { background:var(--primary-color); color:#fff; }

    /* Bake Form */
    .bake-card { background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:20px; padding:2.5rem; }
    .bake-section-title { font-size:1.15rem; font-weight:700; color:var(--text-primary); margin-bottom:1.25rem; padding-bottom:0.5rem; border-bottom:1px solid var(--border-color); display:flex; align-items:center; gap:0.5rem; }
    .bake-section-title .num { width:28px; height:28px; border-radius:50%; background:var(--primary-color); color:#fff; font-size:0.8rem; display:flex; align-items:center; justify-content:center; font-weight:700; flex-shrink:0; }
    .ai-btn { background:linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff; border:none; border-radius:8px; padding:0.5rem 1rem; cursor:pointer; font-size:0.85rem; font-family:inherit; font-weight:600; display:flex; align-items:center; gap:0.5rem; transition:opacity 0.2s; }
    .ai-btn:hover { opacity:0.85; }
    .ai-btn svg { width:16px; height:16px; }
    .textarea-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem; }

    /* Pricing Panel */
    .pricing-panel { background:linear-gradient(135deg,rgba(234,88,12,0.06),rgba(217,119,6,0.04)); border:1px solid rgba(234,88,12,0.2); border-radius:16px; padding:1.5rem; margin-top:1.5rem; }
    .pricing-compare { display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-top:1rem; }
    .price-box { background:var(--bg-secondary); border-radius:12px; padding:1.25rem; text-align:center; border:1px solid var(--border-color); }
    .price-box.ours { border-color:var(--primary-color); }
    .price-label { font-size:0.8rem; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.5px; }
    .price-val { font-size:1.8rem; font-weight:800; color:var(--text-primary); margin:0.25rem 0; }
    .price-box.ours .price-val { color:var(--primary-color); }
    .price-save { background:rgba(16,185,129,0.1); color:#10b981; padding:3px 10px; border-radius:20px; font-size:0.8rem; font-weight:700; display:inline-block; margin-top:4px; }

    /* Currency Selector */
    .currency-row { display:flex; align-items:center; gap:1rem; margin-bottom:1rem; }
    .currency-row label { color:var(--text-secondary); font-size:0.9rem; }
    .currency-row select { background:var(--bg-primary); border:1px solid var(--border-color); color:var(--text-primary); padding:0.35rem 0.75rem; border-radius:8px; font-family:inherit; }

    /* Oven Animation */
    .oven-overlay { position:fixed; inset:0; background:rgba(10,7,4,0.95); z-index:9999; display:none; flex-direction:column; align-items:center; justify-content:center; gap:1.5rem; }
    .oven-overlay.active { display:flex; }
    .oven-text { color:var(--text-primary); font-size:1.3rem; font-weight:600; }
    .oven-sub { color:var(--text-secondary); font-size:0.95rem; }
    @keyframes ovenFlicker { 0%,100%{opacity:0.7} 50%{opacity:1} }
    .oven-glow { animation: ovenFlicker 1.5s infinite; }

    /* Success */
    .bake-success { text-align:center; padding:3rem 2rem; }
    .bake-success-icon { font-size:4rem; margin-bottom:1rem; }
    .bake-success h2 { color:var(--text-primary); font-size:2rem; margin-bottom:0.5rem; }
    .bake-success p { color:var(--text-secondary); }

    .user-strip { background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:12px; padding:1rem 1.5rem; display:flex; align-items:center; justify-content:space-between; margin-bottom:2rem; }
    .user-strip-info { color:var(--text-secondary); font-size:0.9rem; }
    .user-strip-info strong { color:var(--text-primary); }

    @media(max-width:600px) {
      .bake-title { font-size:1.8rem; }
      .pricing-compare { grid-template-columns:1fr; }
      .bake-card { padding:1.5rem; }
    }
  </style>
</head>
<body>

<!-- Oven Loading Animation -->
<div class="oven-overlay" id="oven-overlay">
  <svg width="120" height="120" viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg" class="oven-glow">
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
  <div class="oven-text">Baking your idea... 🔥</div>
  <div class="oven-sub">Placing your project in the creative oven</div>
</div>

<!-- Navigation -->
<header class="header" id="header">
  <div class="container nav-container">
    <a href="index.php" class="logo" aria-label="Adloaf Home">
      <div class="logo-icon-wrap">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 15C3 13 4.5 10.5 7 10.5C9.5 10.5 10 12 12 12C14 12 14.5 10.5 17 10.5C19.5 10.5 21 13 21 15C21 18.5 18.5 20 12 20C5.5 20 3 18.5 3 15Z"/>
          <path d="M7 10.5C7 8 9 6.5 12 6.5C15 6.5 17 8 17 10.5" stroke-dasharray="1 1"/>
          <path d="M12 2V4M8 3.5l1.5 1.5M16 3.5L14.5 5" stroke-width="2"/>
        </svg>
      </div>
      <span class="logo-text">Adloaf<span class="logo-dot">.</span></span>
    </a>
    <div style="display:flex;gap:1rem;align-items:center;">
      <?php if ($user): ?>
        <span style="color:var(--text-secondary);font-size:0.9rem;">Hi, <strong style="color:var(--text-primary);"><?php echo htmlspecialchars(explode(' ', $user['full_name'])[0]); ?></strong></span>
        <a href="auth/logout.php" class="btn btn-secondary" style="padding:0.4rem 1rem;font-size:0.85rem;">Logout</a>
      <?php else: ?>
        <a href="auth/login.php?next=bake.php" class="btn btn-secondary" style="padding:0.4rem 1rem;font-size:0.85rem;">Sign In</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<div class="bake-page">
  <div class="bake-container">
    <div class="bake-header">
      <span class="section-badge">The Oven</span>
      <h1 class="bake-title">Send a <span>Recipe Request</span></h1>
      <p class="bake-subtitle">Fill in your project details below. Our team will review and reply on WhatsApp!</p>
    </div>

    <?php if ($submitted): ?>
    <!-- SUCCESS STATE -->
    <div class="bake-card bake-success">
      <div class="bake-success-icon">🍞</div>
      <h2>Your Bake is in the Oven!</h2>
      <p>We've received your project request and will get back to you via WhatsApp very soon. Check your email for a confirmation.</p>
      <div style="display:flex;gap:1rem;justify-content:center;margin-top:2rem;flex-wrap:wrap;">
        <a href="index.php" class="btn btn-secondary">← Back to Home</a>
        <a href="bake.php" class="btn btn-primary">Submit Another Request</a>
      </div>
    </div>
    <?php else: ?>

    <!-- USER STRIP -->
    <?php if ($user): ?>
    <div class="user-strip">
      <div class="user-strip-info">Submitting as <strong><?php echo htmlspecialchars($user['full_name']); ?></strong> (<?php echo htmlspecialchars($user['email']); ?>)</div>
      <a href="auth/logout.php" style="color:var(--text-secondary);font-size:0.85rem;text-decoration:none;">Not you? Logout</a>
    </div>
    <?php else: ?>
    <div class="user-strip" style="border-color:rgba(234,88,12,0.3);">
      <div class="user-strip-info" style="color:var(--text-secondary);">⚠️ You are not signed in. Fill the form — your data will be saved when you sign in before submitting.</div>
      <a href="auth/login.php?next=bake.php" class="btn btn-primary" style="padding:0.4rem 1rem;font-size:0.85rem;">Sign In</a>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div style="background:rgba(239,68,68,0.1);color:#ef4444;padding:.75rem 1rem;border-radius:8px;margin-bottom:1.5rem;"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="bake.php" id="bake-form">
      <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
      <input type="hidden" name="action" value="submit_bake">
      <input type="hidden" name="ai_description" id="ai-description-hidden">

      <div class="bake-card">
        <!-- SECTION 1: Service -->
        <div class="bake-section-title"><span class="num">1</span> Recipe Type <span style="font-size:0.85rem;font-weight:400;color:var(--text-secondary);">(Service)</span></div>
        <div class="form-row-2">
          <div class="form-group">
            <label class="form-label">Select Service</label>
            <select name="service_type" id="service-select" class="form-input" style="height:53px;" required onchange="updatePricing()">
              <option value="" disabled <?php echo empty($draft['service_type']) ? 'selected' : ''; ?>>Choose a recipe...</option>
              <?php foreach ($services as $svc): ?>
                <option value="<?php echo htmlspecialchars($svc['title']); ?>"
                  data-price="<?php echo $svc['price_from_inr'] ?? 0; ?>"
                  data-market="<?php echo $svc['market_price_inr'] ?? 0; ?>"
                  <?php echo ($draft['service_type'] ?? '') == $svc['title'] ? 'selected' : ''; ?>>
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
        <div class="bake-section-title" style="margin-top:1.5rem;"><span class="num">2</span> Deadline</div>
        <div class="form-group">
          <label class="form-label">Project Deadline <small style="color:var(--text-secondary);font-weight:400;">(Minimum 4 days from today)</small></label>
          <input type="date" name="deadline" class="form-input" id="deadline-input" required min="<?php echo $minDate; ?>" value="<?php echo htmlspecialchars($draft['deadline'] ?? ''); ?>" style="height:53px;">
        </div>

        <!-- SECTION 3: Project Description -->
        <div class="bake-section-title" style="margin-top:1.5rem;"><span class="num">3</span> Project Ingredients</div>
        <div class="form-group">
          <div class="textarea-header">
            <label class="form-label" style="margin:0;">Describe your project</label>
            <button type="button" class="ai-btn" id="ai-gen-btn" onclick="generateAI()">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
              ✨ Generate with AI
            </button>
          </div>
          <textarea name="project_description" id="description-textarea" class="form-textarea" rows="5" placeholder="Tell us about your project, target audience, goals, style preferences..." required style="margin-top:0.5rem;"><?php echo htmlspecialchars($draft['project_description'] ?? ''); ?></textarea>
          <div id="ai-loading" style="display:none;color:var(--text-secondary);font-size:0.85rem;margin-top:0.5rem;">🤖 AI is writing your description...</div>
        </div>

        <!-- SECTION 4: Pricing -->
        <div id="pricing-panel" class="pricing-panel" style="display:none;">
          <div class="currency-row">
            <label>View pricing in:</label>
            <select id="currency-select" onchange="updatePricing()">
              <option value="INR">🇮🇳 INR ₹</option>
              <option value="USD">🇺🇸 USD $</option>
              <option value="EUR">🇪🇺 EUR €</option>
              <option value="GBP">🇬🇧 GBP £</option>
              <option value="AED">🇦🇪 AED د.إ</option>
              <option value="SAR">🇸🇦 SAR ﷼</option>
              <option value="MYR">🇲🇾 MYR RM</option>
            </select>
          </div>
          <p style="color:var(--text-secondary);font-size:0.85rem;margin:0 0 0.75rem;">Approximate pricing for selected service:</p>
          <div class="pricing-compare">
            <div class="price-box ours">
              <div class="price-label">Adloaf — Starting From</div>
              <div class="price-val" id="adloaf-price">₹0</div>
              <div class="price-save">Best Value</div>
            </div>
            <div class="price-box">
              <div class="price-label">Market Average</div>
              <div class="price-val" id="market-price" style="color:var(--text-secondary);">₹0</div>
              <div style="color:#10b981;font-size:0.8rem;margin-top:4px;" id="savings-text"></div>
            </div>
          </div>
        </div>

        <div style="margin-top:2rem;padding-top:1.5rem;border-top:1px solid var(--border-color);">
          <button type="submit" class="btn btn-primary btn-loaf" style="width:100%;padding:1rem;font-size:1.05rem;" onclick="showOven()">
            🍞 Bake the Dough
          </button>
          <p style="color:var(--text-secondary);font-size:0.8rem;text-align:center;margin-top:0.75rem;">
            <?php if (!$user): ?>Submitting will ask you to sign in first. Your data is safe.<?php else: ?>Our team will review and reply on WhatsApp within 24 hours.<?php endif; ?>
          </p>
        </div>
      </div>
    </form>
    <?php endif; ?>
  </div>
</div>

<!-- WhatsApp Widget -->
<a href="https://wa.me/<?php echo ADMIN_WA; ?>?text=Hi%20Adloaf!%20I%20want%20to%20discuss%20a%20project." target="_blank" class="wa-float" id="wa-float" aria-label="Chat on WhatsApp">
  <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
</a>

<script>
const exchangeRates = { INR:1, USD:0.012, EUR:0.011, GBP:0.0094, AED:0.044, SAR:0.045, MYR:0.056 };
const currencySymbols = { INR:'₹', USD:'$', EUR:'€', GBP:'£', AED:'د.إ', SAR:'﷼', MYR:'RM' };

// Language field toggle
const languageServices = ['Graphic Design','Brand Identity','Social Media Creatives'];
document.getElementById('service-select').addEventListener('change', function() {
  const lg = document.getElementById('language-group');
  lg.style.display = languageServices.includes(this.value) ? 'block' : 'none';
});

function updatePricing() {
  const sel    = document.getElementById('service-select');
  const opt    = sel.options[sel.selectedIndex];
  const curr   = document.getElementById('currency-select').value;
  const rate   = exchangeRates[curr] || 1;
  const sym    = currencySymbols[curr] || curr;
  const panel  = document.getElementById('pricing-panel');

  if (!opt || !opt.dataset.price) { panel.style.display='none'; return; }

  const adloaf = parseFloat(opt.dataset.price || 0);
  const market = parseFloat(opt.dataset.market || 0);

  if (adloaf > 0) {
    panel.style.display = 'block';
    document.getElementById('adloaf-price').textContent = sym + Math.round(adloaf * rate).toLocaleString();
    document.getElementById('market-price').textContent = market > 0 ? sym + Math.round(market * rate).toLocaleString() : 'Varies';
    if (market > adloaf) {
      const saving = Math.round((market - adloaf) * rate);
      document.getElementById('savings-text').textContent = 'Save ' + sym + saving.toLocaleString() + ' with Adloaf!';
    }
  } else {
    panel.style.display = 'none';
  }
}

async function generateAI() {
  const service = document.getElementById('service-select').value;
  const desc    = document.getElementById('description-textarea').value;
  const deadline= document.getElementById('deadline-input').value;
  if (!service || !desc.trim()) {
    alert('Please select a service and write at least a short description first.');
    return;
  }
  document.getElementById('ai-loading').style.display = 'block';
  document.getElementById('ai-gen-btn').disabled = true;
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
      document.getElementById('description-textarea').value = data.text;
      document.getElementById('ai-description-hidden').value = data.text;
    }
  } catch(e) { alert('AI generation failed. Please try again.'); }
  document.getElementById('ai-loading').style.display = 'none';
  document.getElementById('ai-gen-btn').disabled = false;
}

function showOven() {
  // Only show if form is valid
  if (document.getElementById('bake-form').checkValidity()) {
    document.getElementById('oven-overlay').classList.add('active');
  }
}

// Restore draft values from session if present
document.getElementById('service-select').dispatchEvent(new Event('change'));
updatePricing();
</script>
</body>
</html>

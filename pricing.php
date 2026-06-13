<?php
require_once __DIR__ . '/lib/helpers.php';
try { track_visitor('/pricing.php'); } catch(Exception $e) {}

$user = current_user();
$services   = $pdo->query("SELECT * FROM services WHERE price_from_inr > 0 ORDER BY sort_order ASC")->fetchAll();
$baseCurrency = site_setting('base_currency', 'INR');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pricing | Adloaf</title>
  <meta name="description" content="Transparent pricing for Adloaf's creative services — website design, branding, social media, and more.">
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<!-- Standardized Navigation -->
<header class="header scrolled" id="header">
  <div class="container nav-container">
    <a href="index.php" class="logo" aria-label="Adloaf Home">
      <div class="logo-icon-wrap">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 15C3 13 4.5 10.5 7 10.5C9.5 10.5 10 12 12 12C14 12 14.5 10.5 17 10.5C19.5 10.5 21 13 21 15C21 18.5 18.5 20 12 20C5.5 20 3 18.5 3 15Z"/>
          <path d="M7 10.5C7 8 9 6.5 12 6.5C15 6.5 17 8 17 10.5" stroke-dasharray="1 1"/>
          <path d="M12 2V4M8 3.5l1.5 1.5M16 3.5L14.5 5" stroke="currentColor" stroke-width="2"/>
        </svg>
      </div>
      <span class="logo-text">Adloaf<span class="logo-dot" style="color:var(--accent-orange);">.</span></span>
    </a>

    <nav aria-label="Main Navigation">
      <ul class="nav-menu" id="nav-menu">
        <li><a href="index.php" class="nav-link">Home</a></li>
        <li><a href="index.php#about" class="nav-link">About</a></li>
        <li><a href="index.php#services" class="nav-link">Services</a></li>
        <li><a href="works.php" class="nav-link">Works</a></li>
        <li><a href="index.php#process" class="nav-link">Process</a></li>
        <li><a href="pricing.php" class="nav-link active">Pricing</a></li>
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
        <a href="auth/login.php?next=pricing.php" class="btn btn-secondary btn-header" style="padding: 0.5rem 1.25rem; font-size: 0.9rem;">Sign In</a>
      <?php endif; ?>
      
      <a href="bake.php" class="btn btn-primary btn-header" id="header-cta" style="margin-left: 0.5rem;">Bake a Project</a>
      <button class="menu-toggle" id="menu-toggle" aria-label="Toggle Navigation Menu" aria-controls="nav-menu" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</header>

<div class="pricing-page">
  <div class="container">
    <div class="pricing-hero">
      <span class="section-badge">Transparent Pricing</span>
      <h1 class="pricing-title">Freshly Baked <span>Pricing Menu</span></h1>
      <p class="section-subtitle" style="max-width:540px; margin:.5rem auto 0;">
        No hidden fees. No surprises. Just premium creative craftsmanship at rates that give you real business value.
      </p>
    </div>

    <div class="currency-toolbar">
      <label style="font-weight: 700; color: var(--text-primary);">Select base currency:</label>
      <select id="currency-select" onchange="renderPrices()">
        <option value="INR">🇮🇳 INR ₹</option>
        <option value="USD">🇺🇸 USD $</option>
        <option value="EUR">🇪🇺 EUR €</option>
        <option value="GBP">🇬🇧 GBP £</option>
        <option value="AED">🇦🇪 AED د.إ</option>
        <option value="SAR">🇸🇦 SAR ﷼</option>
        <option value="MYR">🇲🇾 MYR RM</option>
        <option value="SGD">🇸🇬 SGD S$</option>
        <option value="AUD">🇦🇺 AUD A$</option>
      </select>
    </div>

    <div class="pricing-grid" id="pricing-grid">
      <?php foreach ($services as $svc): ?>
      <div class="pricing-glass-card"
        data-price="<?php echo $svc['price_from_inr']; ?>"
        data-market="<?php echo $svc['market_price_inr']; ?>">
        <div>
          <div class="pricing-icon">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <?php echo $svc['icon_svg']; ?>
            </svg>
          </div>
          <div class="pricing-service"><?php echo htmlspecialchars($svc['title']); ?></div>
          <p class="pricing-desc"><?php echo htmlspecialchars($svc['description']); ?></p>
        </div>

        <div style="margin-top: 2rem;">
          <div class="price-from"><?php echo htmlspecialchars($svc['price_note'] ?: 'Starting from'); ?></div>
          <div class="price-amount adloaf-price">₹<?php echo number_format($svc['price_from_inr']); ?></div>
          
          <?php if ($svc['market_price_inr'] > $svc['price_from_inr']): 
            $savingsPercent = round((($svc['market_price_inr'] - $svc['price_from_inr']) / $svc['market_price_inr']) * 100);
          ?>
            <div class="market-row" style="margin-top: 1rem;">
              <span class="market-label">Market Avg:</span>
              <span class="market-val market-price">₹<?php echo number_format($svc['market_price_inr']); ?></span>
            </div>
            
            <div style="margin-top: 0.5rem;">
              <div style="display:flex; justify-content:space-between; font-size: 0.75rem; font-weight: 700; color: #10b981;">
                <span>Savings</span>
                <span class="savings-percent-txt"><?php echo $savingsPercent; ?>%</span>
              </div>
              <div class="pricing-compare-meter">
                <div class="pricing-compare-fill" style="width: <?php echo $savingsPercent; ?>%;"></div>
              </div>
            </div>
            
            <div style="color:#10b981; font-size:0.8rem; font-weight:700; margin-top: 4px;" class="save-amount">
              Save with Adloaf!
            </div>
          <?php endif; ?>

          <div style="margin-top:1.5rem;">
            <a href="bake.php" class="btn btn-primary" style="width:100%; text-align:center; padding:.85rem; font-size: 0.95rem;"><?php echo htmlspecialchars($svc['link_text'] ?: 'Get Started'); ?></a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <p class="note-text">All prices are estimated base values. Custom briefs determine final calculations. <a href="bake.php" style="color:var(--accent-orange); font-weight: 700;">Submit a request</a> for an exact quote.</p>

    <div class="cta-strip">
      <p style="color:var(--text-secondary); margin-bottom:1.5rem; font-weight: 600;">Need a custom combination of recipes?</p>
      <a href="https://wa.me/<?php echo ADMIN_WA; ?>?text=Hi%20Adloaf!%20I%20want%20to%20discuss%20a%20custom%20service." target="_blank" class="btn btn-primary btn-loaf" style="padding: 1rem 2.5rem;">Chat with Us on WhatsApp</a>
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
          <span class="logo-text">Adloaf<span class="logo-dot">.</span></span>
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
      <p>&copy; 2026 Adloaf Creative Agency. All rights reserved.</p>
    </div>
  </div>
</footer>

<!-- WhatsApp Widget -->
<a href="https://wa.me/<?php echo ADMIN_WA; ?>" target="_blank" class="wa-float" aria-label="Chat on WhatsApp">
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

const rates   = { INR:1, USD:0.012, EUR:0.011, GBP:0.0094, AED:0.044, SAR:0.045, MYR:0.056, SGD:0.016, AUD:0.019 };
const symbols = { INR:'₹', USD:'$', EUR:'€', GBP:'£', AED:'د.إ', SAR:'﷼', MYR:'RM', SGD:'S$', AUD:'A$' };

// Try to detect user's currency via timezone
const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
const tzMap = { 'Asia/Kolkata':'INR','Asia/Calcutta':'INR','America/New_York':'USD','Europe/London':'GBP','Europe/Paris':'EUR','Asia/Dubai':'AED','Asia/Riyadh':'SAR','Asia/Kuala_Lumpur':'MYR' };
const detectedCurrency = tzMap[tz] || 'INR';
document.getElementById('currency-select').value = detectedCurrency;

function renderPrices() {
  const curr = document.getElementById('currency-select').value;
  const rate = rates[curr] || 1;
  const sym  = symbols[curr] || curr;
  
  document.querySelectorAll('.pricing-glass-card').forEach(card => {
    const price  = parseFloat(card.dataset.price || 0);
    const market = parseFloat(card.dataset.market || 0);
    
    const priceEl  = card.querySelector('.adloaf-price');
    const marketEl = card.querySelector('.market-price');
    const saveEl   = card.querySelector('.save-amount');
    
    if (priceEl)  priceEl.textContent  = sym + Math.round(price * rate).toLocaleString();
    if (marketEl) marketEl.textContent = sym + Math.round(market * rate).toLocaleString();
    
    if (market > price) {
      const savingsVal = Math.round((market - price) * rate);
      if (saveEl) saveEl.textContent = 'Save ' + sym + savingsVal.toLocaleString() + ' with Adloaf!';
    }
  });
}
renderPrices();
</script>
</body>
</html>

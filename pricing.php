<?php
require_once __DIR__ . '/lib/helpers.php';
try { track_visitor('/pricing.php'); } catch(Exception $e) {}

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
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    .pricing-page { min-height:100vh; background:var(--bg-primary); padding-top:100px; padding-bottom:60px; }
    .pricing-hero { text-align:center; padding:0 1.5rem 3rem; }
    .pricing-title { font-size:2.5rem; font-weight:800; color:var(--text-primary); }
    .pricing-title span { background:linear-gradient(135deg,#EA580C,#D97706); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }

    .currency-toolbar { display:flex; align-items:center; justify-content:center; gap:1rem; margin-bottom:3rem; flex-wrap:wrap; }
    .currency-toolbar label { color:var(--text-secondary); font-size:.9rem; }
    .currency-toolbar select { background:var(--bg-secondary); border:1px solid var(--border-color); color:var(--text-primary); padding:.5rem 1rem; border-radius:10px; font-family:inherit; font-size:.9rem; cursor:pointer; }

    .pricing-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:1.5rem; max-width:1100px; margin:0 auto; padding:0 1.5rem; }
    .pricing-card { background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:20px; padding:2rem; transition:transform .3s,border-color .3s,box-shadow .3s; }
    .pricing-card:hover { transform:translateY(-6px); border-color:var(--primary-color); box-shadow:0 20px 60px rgba(234,88,12,.15); }
    .pricing-icon { width:52px; height:52px; background:rgba(234,88,12,0.1); border-radius:14px; display:flex; align-items:center; justify-content:center; margin-bottom:1.25rem; color:var(--primary-color); }
    .pricing-service { font-size:1.2rem; font-weight:700; color:var(--text-primary); margin-bottom:.5rem; }
    .pricing-desc { color:var(--text-secondary); font-size:.9rem; line-height:1.6; margin-bottom:1.5rem; }
    .price-from { font-size:.8rem; text-transform:uppercase; letter-spacing:.5px; color:var(--text-secondary); margin-bottom:.2rem; }
    .price-amount { font-size:2rem; font-weight:800; color:var(--primary-color); }
    .market-row { display:flex; align-items:center; gap:.5rem; margin-top:.5rem; }
    .market-label { font-size:.8rem; color:var(--text-secondary); }
    .market-val { font-size:.9rem; color:var(--text-secondary); text-decoration:line-through; }
    .save-pill { background:rgba(16,185,129,0.1); color:#10b981; font-size:.75rem; font-weight:700; padding:2px 8px; border-radius:20px; }
    .cta-strip { text-align:center; margin-top:4rem; }
    .note-text { color:var(--text-secondary); font-size:.85rem; margin-top:2rem; text-align:center; }
  </style>
</head>
<body>
<header class="header" id="header">
  <div class="container nav-container">
    <a href="index.php" class="logo">
      <div class="logo-icon-wrap">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 15C3 13 4.5 10.5 7 10.5C9.5 10.5 10 12 12 12C14 12 14.5 10.5 17 10.5C19.5 10.5 21 13 21 15C21 18.5 18.5 20 12 20C5.5 20 3 18.5 3 15Z"/>
          <path d="M7 10.5C7 8 9 6.5 12 6.5C15 6.5 17 8 17 10.5" stroke-dasharray="1 1"/>
        </svg>
      </div>
      <span class="logo-text">Adloaf<span class="logo-dot">.</span></span>
    </a>
    <nav><ul class="nav-menu">
      <li><a href="index.php" class="nav-link">Home</a></li>
      <li><a href="works.php" class="nav-link">Works</a></li>
      <li><a href="pricing.php" class="nav-link active">Pricing</a></li>
      <li><a href="bake.php" class="nav-link">Bake a Project</a></li>
    </ul></nav>
  </div>
</header>

<div class="pricing-page">
  <div class="pricing-hero">
    <span class="section-badge">Transparent Pricing</span>
    <h1 class="pricing-title">Freshly Baked <span>Pricing</span></h1>
    <p style="color:var(--text-secondary);max-width:540px;margin:.5rem auto 0;">
      No hidden costs. No surprises. Just premium creative work at prices that give you real value.
    </p>
  </div>

  <div class="currency-toolbar">
    <label>View prices in:</label>
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
    <div class="pricing-card"
      data-price="<?php echo $svc['price_from_inr']; ?>"
      data-market="<?php echo $svc['market_price_inr']; ?>"
      data-icon="<?php echo htmlspecialchars($svc['icon_svg']); ?>">
      <div class="pricing-icon">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <?php echo $svc['icon_svg']; ?>
        </svg>
      </div>
      <div class="pricing-service"><?php echo htmlspecialchars($svc['title']); ?></div>
      <p class="pricing-desc"><?php echo htmlspecialchars($svc['description']); ?></p>
      <div class="price-from"><?php echo htmlspecialchars($svc['price_note'] ?? 'Starting from'); ?></div>
      <div class="price-amount adloaf-price">₹<?php echo number_format($svc['price_from_inr']); ?></div>
      <?php if ($svc['market_price_inr'] > $svc['price_from_inr']): ?>
      <div class="market-row">
        <span class="market-label">Market avg:</span>
        <span class="market-val market-price">₹<?php echo number_format($svc['market_price_inr']); ?></span>
        <span class="save-pill save-amount">Save ₹<?php echo number_format($svc['market_price_inr'] - $svc['price_from_inr']); ?></span>
      </div>
      <?php endif; ?>
      <div style="margin-top:1.5rem;">
        <a href="bake.php" class="btn btn-primary" style="width:100%;text-align:center;padding:.75rem;"><?php echo htmlspecialchars($svc['link_text'] ?? 'Get Started'); ?></a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <p class="note-text">All prices are starting estimates in INR. Final price depends on project scope. <a href="bake.php" style="color:var(--primary-color);">Submit your brief</a> for a custom quote.</p>

  <div class="cta-strip">
    <p style="color:var(--text-secondary);margin-bottom:1.5rem;">Not sure which recipe suits you best?</p>
    <a href="https://wa.me/<?php echo ADMIN_WA; ?>?text=Hi%20Adloaf!%20I%20need%20help%20choosing%20a%20service." target="_blank" class="btn btn-primary btn-loaf">Chat with Us on WhatsApp</a>
  </div>
</div>

<a href="https://wa.me/<?php echo ADMIN_WA; ?>" target="_blank" class="wa-float" aria-label="Chat on WhatsApp">
  <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
</a>

<script>
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
  document.querySelectorAll('.pricing-card').forEach(card => {
    const price  = parseFloat(card.dataset.price || 0);
    const market = parseFloat(card.dataset.market || 0);
    const priceEl  = card.querySelector('.adloaf-price');
    const marketEl = card.querySelector('.market-price');
    const saveEl   = card.querySelector('.save-amount');
    if (priceEl)  priceEl.textContent  = sym + Math.round(price * rate).toLocaleString();
    if (marketEl) marketEl.textContent = sym + Math.round(market * rate).toLocaleString();
    if (saveEl && market > price) saveEl.textContent = 'Save ' + sym + Math.round((market - price) * rate).toLocaleString();
  });
}
renderPrices();
</script>
</body>
</html>

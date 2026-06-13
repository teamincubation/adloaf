<?php
require_once __DIR__ . '/lib/helpers.php';
try { track_visitor('/works.php'); } catch(Exception $e) {}

$user = current_user();
$portfolio = $pdo->query("SELECT * FROM portfolio_items ORDER BY sort_order ASC, id ASC")->fetchAll();
$categories = array_unique(array_column($portfolio, 'category'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Our Works | Adloaf</title>
  <meta name="description" content="Explore Adloaf's portfolio — websites, branding, posters, social media creatives, and UI concepts.">
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
        <li><a href="works.php" class="nav-link active">Works</a></li>
        <li><a href="index.php#process" class="nav-link">Process</a></li>
        <li><a href="pricing.php" class="nav-link">Pricing</a></li>
        <!-- Mobile-only links -->
        <?php if ($user): ?>
          <li class="mobile-only"><a href="profile.php" class="nav-link">Profile Dashboard</a></li>
          <li class="mobile-only"><a href="auth/logout.php" class="nav-link" style="color: #ef4444;">Logout</a></li>
        <?php else: ?>
          <li class="mobile-only"><a href="auth/login.php?next=works.php" class="nav-link">Sign In</a></li>
        <?php endif; ?>
        <li class="mobile-only" style="margin-top: 1rem;"><a href="bake.php" class="btn btn-primary" style="width: 100%; text-align: center; padding: 0.75rem;">Bake a Project</a></li>
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
        <a href="auth/login.php?next=works.php" class="btn btn-secondary btn-header" style="padding: 0.5rem 1.25rem; font-size: 0.9rem;">Sign In</a>
      <?php endif; ?>
      
      <a href="bake.php" class="btn btn-primary btn-header" id="header-cta" style="margin-left: 0.5rem;">Bake a Project</a>
      <button class="menu-toggle" id="menu-toggle" aria-label="Toggle Navigation Menu" aria-controls="nav-menu" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>
</header>

<div class="works-page">
  <div class="container">
    <div style="text-align:center; margin-bottom: 3.5rem;" class="works-header">
      <span class="section-badge">Display Case</span>
      <h1 class="section-title">Our <span>Oven-Fresh</span> Works</h1>
      <p class="section-subtitle" style="max-width:540px; margin:0 auto;">
        Browse our full creative portfolio — every piece freshly kneaded and baked to perfection.
      </p>
    </div>

    <div class="works-filters">
      <button class="filter-pill active" data-filter="all">All Bakes</button>
      <?php foreach ($categories as $cat): ?>
        <button class="filter-pill" data-filter="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars(ucfirst($cat)); ?></button>
      <?php endforeach; ?>
    </div>

    <div class="works-grid-layout" id="works-grid">
      <?php foreach ($portfolio as $item): ?>
      <div class="works-card-item" data-category="<?php echo htmlspecialchars($item['category']); ?>">
        <div class="works-card-img-wrap">
          <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="works-card-img" loading="lazy">
          <div class="works-card-overlay">
            <div class="works-overlay-cat"><?php echo htmlspecialchars(ucfirst($item['category'])); ?></div>
            <h3 class="works-overlay-title"><?php echo htmlspecialchars($item['title']); ?></h3>
            <p class="works-overlay-desc"><?php echo htmlspecialchars($item['description']); ?></p>
          </div>
        </div>
        <div class="works-card-details">
          <div class="works-card-cat"><?php echo htmlspecialchars(ucfirst($item['category'])); ?></div>
          <h3 class="works-card-title"><?php echo htmlspecialchars($item['title']); ?></h3>
          <p class="works-card-desc"><?php echo htmlspecialchars($item['description']); ?></p>
        </div>
      </div>
      <?php endforeach; ?>
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
<a href="https://wa.me/<?php echo ADMIN_WA; ?>?text=Hi%20Adloaf!%20I'm%20visiting%20your%20works%20page." target="_blank" class="wa-float" aria-label="Chat on WhatsApp">
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

// Category filtering with animations
const filters = document.querySelectorAll('.filter-pill');
const items   = document.querySelectorAll('.works-card-item');

filters.forEach(btn => {
  btn.addEventListener('click', function() {
    filters.forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    
    const filter = this.dataset.filter;
    
    items.forEach(item => {
      item.style.transition = 'all 0.4s ease';
      if (filter === 'all' || item.dataset.category === filter) {
        item.classList.remove('hidden');
        setTimeout(() => {
          item.style.opacity = '1';
          item.style.transform = 'scale(1)';
        }, 50);
      } else {
        item.style.opacity = '0';
        item.style.transform = 'scale(0.95)';
        setTimeout(() => {
          item.classList.add('hidden');
        }, 400);
      }
    });
  });
});
</script>
</body>
</html>

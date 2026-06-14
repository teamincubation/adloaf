<?php
require_once 'lib/helpers.php';
try { track_visitor('/'); } catch(Exception $e) {}
$user = current_user();

// Fetch site settings
$settingsQuery = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$settingsData  = $settingsQuery->fetchAll(PDO::FETCH_KEY_PAIR);

function get_setting($key, $default = '') {
    global $settingsData;
    return isset($settingsData[$key]) ? $settingsData[$key] : $default;
}

$services    = $pdo->query("SELECT * FROM services ORDER BY sort_order ASC")->fetchAll();
$portfolio   = $pdo->query("SELECT * FROM portfolio_items ORDER BY sort_order ASC")->fetchAll();
$processSteps= $pdo->query("SELECT * FROM process_steps ORDER BY sort_order ASC")->fetchAll();
$bentoCards  = $pdo->query("SELECT * FROM bento_cards ORDER BY sort_order ASC")->fetchAll();

// Stats & Clients (safe fallback if tables don't exist yet)
try {
    $statsRaw = $pdo->query("SELECT stat_key, stat_value FROM site_stats")->fetchAll(PDO::FETCH_KEY_PAIR);
    $popularClients = $pdo->query("SELECT * FROM popular_clients ORDER BY sort_order ASC")->fetchAll();
} catch(PDOException $e) {
    $statsRaw = [];
    $popularClients = [];
}
$siteStats = [
    'clients'   => $statsRaw['total_clients']       ?? 0,
    'projects'  => $statsRaw['completed_projects']  ?? 0,
    'years'     => $statsRaw['active_years']        ?? 1,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Adloaf - Freshly Baked Creative Ideas for Brands. Portfolio showcasing website design, branding, posters, social media, and digital campaigns.">
  <meta name="keywords" content="Adloaf, design portfolio, website design, branding, graphic design, posters, social media creatives, digital campaigns, creative agency">
  <meta name="author" content="Adloaf">
  <meta property="og:title" content="Adloaf | Freshly Baked Creative Ideas for Brands">
  <meta property="og:description" content="A creative showcase of websites, graphic designs, brand visuals, and digital ideas crafted to help brands look better, communicate smarter, and grow faster.">
  <meta property="og:image" content="assets/portfolio_branding.png">
  <meta property="og:url" content="https://adloaf.com">
  <meta name="twitter:card" content="summary_large_image">
  
  <title>Adloaf | Freshly Baked Creative Ideas for Brands</title>
  
  <!-- CSS Stylesheet -->
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <!-- HEADER & NAVIGATION -->
  <header class="header" id="header">
    <div class="container nav-container">
      <a href="#" class="logo" id="nav-logo" aria-label="adloaf Home">
        <div class="logo-icon-wrap">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 15C3 13 4.5 10.5 7 10.5C9.5 10.5 10 12 12 12C14 12 14.5 10.5 17 10.5C19.5 10.5 21 13 21 15C21 18.5 18.5 20 12 20C5.5 20 3 18.5 3 15Z"/>
            <path d="M7 10.5C7 8 9 6.5 12 6.5C15 6.5 17 8 17 10.5" stroke-dasharray="1 1"/>
            <path d="M12 2V4M8 3.5l1.5 1.5M16 3.5L14.5 5" stroke="currentColor" stroke-width="2"/>
          </svg>
        </div>
        <span class="logo-text">adloaf<span class="logo-dot">.</span></span>
      </a>

      <nav aria-label="Main Navigation">
        <ul class="nav-menu" id="nav-menu">
          <li><a href="#home" class="nav-link active">Home</a></li>
          <li><a href="#about" class="nav-link">About</a></li>
          <li><a href="#services" class="nav-link">Services</a></li>
          <li><a href="works.php" class="nav-link">Works</a></li>
          <li><a href="#process" class="nav-link">Process</a></li>
          <li><a href="pricing.php" class="nav-link">Pricing</a></li>
          <!-- Mobile-only links -->
          <?php if ($user): ?>
            <li class="mobile-only"><a href="profile.php" class="nav-link">Profile Dashboard</a></li>
            <li class="mobile-only"><a href="auth/logout.php" class="nav-link" style="color: #ef4444;">Logout</a></li>
          <?php else: ?>
            <li class="mobile-only"><a href="auth/login.php?next=index.php" class="nav-link">Sign In</a></li>
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
          <a href="auth/login.php?next=index.php" class="btn btn-secondary btn-header" style="padding: 0.5rem 1.25rem; font-size: 0.9rem;">Sign In</a>
        <?php endif; ?>

        <a href="bake.php" class="btn btn-primary btn-header" id="header-cta" style="margin-left: 0.5rem;">Bake a Project</a>
        <button class="menu-toggle" id="menu-toggle" aria-label="Toggle Navigation Menu" aria-controls="nav-menu" aria-expanded="false">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>
  </header>

  <!-- HERO SECTION -->
  <section class="hero" id="home">
    <div class="container hero-grid">
      <div class="hero-text-wrap reveal">
        <span class="hero-title-prefix">Welcome to the Creative Bakery</span>
        <h1 class="hero-title">
          <?php echo get_setting('hero_title', 'Freshly Baked <span class="glow">Creative Ideas</span> for Brands.'); ?>
        </h1>
        <p class="hero-desc">
          <?php echo get_setting('hero_desc', 'A creative showcase of websites, graphic designs, brand visuals, and digital ideas crafted to help brands look better, communicate smarter, and grow faster.'); ?>
        </p>
        <div class="hero-ctas">
          <a href="works.php" class="btn btn-primary btn-loaf" id="hero-cta-works">View Works</a>
          <a href="bake.php" class="btn btn-secondary btn-loaf" id="hero-cta-contact">Contact Me</a>
        </div>
        <div class="hero-features">
          <div class="hero-feat-item">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/>
              <path d="m9 12 2 2 4-4"/>
            </svg>
            <span class="hero-feat-title">100% Unique Design</span>
          </div>
          <div class="hero-feat-item">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/>
              <path d="m9 12 2 2 4-4"/>
            </svg>
            <span class="hero-feat-title">Served Fast & Fresh</span>
          </div>
        </div>
      </div>

      <div class="hero-visual reveal reveal-delay-2">
        <div class="hero-glow-back"></div>
        <div class="dough-shape-1"></div>
        <div class="dough-shape-2"></div>
        
        <svg class="oven-illustration" viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="50" y="80" width="300" height="260" rx="36" fill="#2C2018" stroke="#E8DEC6" stroke-width="4"/>
          <path d="M50 116c0-20 16-36 36-36h228c20 0 36 16 36 36v14H50v-14Z" fill="#1E1611"/>
          <circle cx="90" cy="98" r="7" fill="#EA580C"/>
          <circle cx="115" cy="98" r="7" fill="#D97706"/>
          <rect x="260" y="88" width="60" height="20" rx="6" fill="#2C2018"/>
          <text x="272" y="103" fill="#EA580C" font-family="'Outfit', sans-serif" font-size="12" font-weight="800" letter-spacing="1">08:00</text>
          
          <rect x="75" y="145" width="250" height="165" rx="20" fill="#1E1611" stroke="#E8DEC6" stroke-width="3"/>
          <rect x="90" y="160" width="220" height="135" rx="12" fill="url(#ovenGlow)" opacity="0.85"/>
          
          <path d="M140 250c0-25 20-35 60-35s60 10 60 35v15H140v-15Z" fill="url(#breadGradient)" stroke="#EA580C" stroke-width="2"/>
          <path d="M170 230l10-15M200 227l5-18M225 231l-8-16" stroke="#FAF7F2" stroke-width="2" stroke-linecap="round"/>
          <path d="M165 190c-5-8 5-15 0-22M200 185c5-8-5-15 0-22M235 190c-5-8 5-15 0-22" stroke="#EA580C" stroke-width="2" stroke-linecap="round" opacity="0.6"/>
          
          <rect x="110" y="130" width="180" height="10" rx="5" fill="#FAF7F2"/>
          <rect x="130" y="125" width="15" height="15" rx="3" fill="#E8DEC6"/>
          <rect x="255" y="125" width="15" height="15" rx="3" fill="#E8DEC6"/>
          
          <rect x="80" y="340" width="30" height="15" rx="5" fill="#2C2018"/>
          <rect x="290" y="340" width="30" height="15" rx="5" fill="#2C2018"/>
          
          <defs>
            <radialGradient id="ovenGlow" cx="50%" cy="50%" r="50%" fx="50%" fy="50%">
              <stop offset="0%" stop-color="#EA580C" stop-opacity="0.45"/>
              <stop offset="70%" stop-color="#D97706" stop-opacity="0.15"/>
              <stop offset="100%" stop-color="#1E1611" stop-opacity="0"/>
            </radialGradient>
            <linearGradient id="breadGradient" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="#FFFDF9"/>
              <stop offset="60%" stop-color="#D97706"/>
              <stop offset="100%" stop-color="#5C4A3E"/>
            </linearGradient>
          </defs>
        </svg>
      </div>
    </div>
  </section>

  <!-- STATS COUNTER SECTION -->
  <?php if ($siteStats['clients'] > 0 || $siteStats['projects'] > 0 || $siteStats['years'] > 0): ?>
  <section class="stats-section">
    <div class="container">
      <div class="stats-grid">
        <?php if ($siteStats['clients'] > 0): ?>
        <div class="stat-item reveal">
          <div class="stat-num" data-count="<?php echo $siteStats['clients']; ?>">0</div>
          <div class="stat-label">Happy Clients</div>
        </div>
        <?php endif; ?>
        <?php if ($siteStats['projects'] > 0): ?>
        <div class="stat-item reveal reveal-delay-1">
          <div class="stat-num" data-count="<?php echo $siteStats['projects']; ?>">0</div>
          <div class="stat-label">Projects Completed</div>
        </div>
        <?php endif; ?>
        <?php if ($siteStats['years'] > 0): ?>
        <div class="stat-item reveal reveal-delay-2">
          <div class="stat-num" data-count="<?php echo $siteStats['years']; ?>">0</div>
          <div class="stat-label">Years of Excellence</div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- POPULAR CLIENTS MARQUEE -->
  <?php if (!empty($popularClients)): ?>
  <section class="clients-section">
    <div class="container">
      <div class="section-header reveal">
        <span class="section-badge">Our Clients</span>
        <h2 class="section-title">Trusted By Great Brands</h2>
      </div>
    </div>
    <div class="marquee-outer">
      <div class="marquee-track">
        <?php foreach (array_merge($popularClients, $popularClients) as $client): ?>
        <div class="marquee-item">
          <?php if (!empty($client['website_url'])): ?>
          <a href="<?php echo htmlspecialchars($client['website_url']); ?>" target="_blank" rel="noopener">
          <?php endif; ?>
            <img src="<?php echo htmlspecialchars($client['logo_path']); ?>" alt="<?php echo htmlspecialchars($client['client_name']); ?>" class="client-logo">
          <?php if (!empty($client['website_url'])): ?>
          </a>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ABOUT SECTION -->
  <section class="about" id="about">
    <div class="container about-grid">
      <div class="about-image-side reveal">
        <div class="about-card-stack">
          <div class="about-stack-card about-card-1">
            <div class="about-card-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="18" x="3" y="3" rx="2"/><path d="M12 8v8M8 12h8"/>
              </svg>
            </div>
            <div>
              <h3 class="about-card-title">Fresh Concept</h3>
              <p class="about-card-text">Combining marketing strategies (Ad) with warm, organic design ideas (Loaf).</p>
            </div>
          </div>
          <div class="about-stack-card about-card-2">
            <div class="about-card-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m12 3-1.912 5.813a2 2 0 0 1-1.9 1.375H2.03c-1.87 0-2.65 2.41-1.13 3.515l4.945 3.59a2 2 0 0 1 .69 2.124L4.623 21.23c-.58 1.766 1.444 3.237 2.964 2.138L12 19.78l4.413 3.588c1.52 1.1 3.544-.37 2.964-2.138l-1.912-5.813a2 2 0 0 1 .69-2.124l4.945-3.59c1.52-1.1.74-3.515-1.13-3.515h-6.158a2 2 0 0 1-1.9-1.375L12 3z"/>
              </svg>
            </div>
            <div>
              <h3 class="about-card-title">Crafted Premium</h3>
              <p class="about-card-text">Every detail is hand-kneaded, verified, and served to visual perfection.</p>
            </div>
          </div>
          <div class="about-stack-card about-card-3">
            <div class="about-card-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
              </svg>
            </div>
            <div>
              <h3 class="about-card-title">Bred for Growth</h3>
              <p class="about-card-text">Optimized to help businesses attract customers and build real authority.</p>
            </div>
          </div>
        </div>
      </div>

      <div class="about-text-side reveal reveal-delay-1">
        <div class="section-badge">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
          </svg>
          Who is Adloaf?
        </div>
        <h2 class="section-title"><?php echo get_setting('about_title'); ?></h2>
        <p class="about-desc"><?php echo get_setting('about_desc'); ?></p>
        
        <div class="about-highlights">
          <div class="highlight-item">
            <div class="highlight-title-wrap">
              <div class="highlight-dot"></div>
              <h3 class="highlight-title">The Strategy (Ad)</h3>
            </div>
            <p class="highlight-desc">Identifying brand goals, creating clear communication plans, and driving conversions.</p>
          </div>
          <div class="highlight-item">
            <div class="highlight-title-wrap">
              <div class="highlight-dot"></div>
              <h3 class="highlight-title">The Craft (Loaf)</h3>
            </div>
            <p class="highlight-desc">Developing bespoke visual elements, elegant layouts, custom micro-interactions, and premium aesthetics.</p>
          </div>
        </div>

        <a href="#contact" class="btn btn-primary" id="about-cta-contact">Let's Bake Together</a>
      </div>
    </div>
  </section>

  <!-- SERVICES SECTION -->
  <section class="services" id="services">
    <div class="container">
      <div class="section-header reveal">
        <span class="section-badge">Our Bakery Menu</span>
        <h2 class="section-title">What We Bake Best</h2>
        <p class="section-subtitle">Explore our core recipe cards. We specialize in producing sleek layouts and consistent styling across all visual platforms.</p>
      </div>

      <div class="services-grid">
        <?php 
        $delay = 0;
        foreach ($services as $service): 
          $delayClass = $delay > 0 ? "reveal-delay-$delay" : "";
        ?>
        <div class="service-card reveal <?php echo $delayClass; ?>">
          <div class="service-icon-wrap">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <?php echo $service['icon_svg']; ?>
            </svg>
          </div>
          <h3 class="service-card-title"><?php echo htmlspecialchars($service['title']); ?></h3>
          <p class="service-card-desc"><?php echo htmlspecialchars($service['description']); ?></p>
          <a href="bake.php?service=<?php echo urlencode($service['title']); ?>" class="service-link">
            <?php echo htmlspecialchars($service['link_text']); ?>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M5 12h14M12 5l7 7-7 7"/>
            </svg>
          </a>
        </div>
        <?php 
          $delay = ($delay + 1) % 3;
        endforeach; 
        ?>
      </div>
    </div>
  </section>



  <!-- CREATIVE PROCESS SECTION -->
  <section class="process" id="process">
    <div class="container">
      <div class="section-header reveal">
        <span class="section-badge">How We Rise</span>
        <h2 class="section-title">The Creative Bake Cycle</h2>
        <p class="section-subtitle">We follow a strict recipe list for brand success. Every step of our workflow ensures the best outcome.</p>
      </div>

      <div class="process-timeline">
        <?php 
        $delay = 0;
        foreach ($processSteps as $step): 
          $delayClass = $delay > 0 ? "reveal-delay-$delay" : "";
        ?>
        <div class="process-step reveal <?php echo $delayClass; ?>">
          <div class="process-node">
            <span class="process-step-num"><?php echo htmlspecialchars($step['step_number']); ?></span>
            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <?php echo $step['icon_svg']; ?>
            </svg>
          </div>
          <h3 class="process-step-title"><?php echo htmlspecialchars($step['title']); ?></h3>
          <p class="process-step-desc"><?php echo htmlspecialchars($step['description']); ?></p>
        </div>
        <?php 
          $delay++;
        endforeach; 
        ?>
      </div>
    </div>
  </section>

  <!-- BENTO GRID -->
  <section class="why-us">
    <div class="container">
      <div class="section-header reveal">
        <span class="section-badge">Why Adloaf?</span>
        <h2 class="section-title">Baked with Purpose</h2>
        <p class="section-subtitle">We combine premium design with strategic reasoning. Explore the core ingredients behind our project approach.</p>
      </div>

      <div class="bento-grid">
        <?php 
        $delay = 0;
        foreach ($bentoCards as $card): 
          $delayClass = $delay > 0 ? "reveal-delay-$delay" : "";
        ?>
        <div class="bento-item <?php echo htmlspecialchars($card['card_class']); ?> reveal <?php echo $delayClass; ?>">
          <div>
            <?php if (!empty($card['icon_svg'])): ?>
            <div class="bento-icon">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <?php echo $card['icon_svg']; ?>
              </svg>
            </div>
            <?php endif; ?>
            <?php if (!empty($card['stat_num'])): ?>
            <span class="bento-stat-num"><?php echo htmlspecialchars($card['stat_num']); ?></span>
            <?php endif; ?>
            <h3 class="bento-title" <?php echo !empty($card['stat_num']) ? 'style="margin-top: 0.5rem;"' : ''; ?>><?php echo htmlspecialchars($card['title']); ?></h3>
            <p class="bento-desc"><?php echo htmlspecialchars($card['description']); ?></p>
          </div>
          <?php if (!empty($card['stat_label'])): ?>
          <div>
            <span class="bento-stat-label"><?php echo htmlspecialchars($card['stat_label']); ?></span>
          </div>
          <?php endif; ?>
        </div>
        <?php 
          $delay = ($delay + 1) % 3;
        endforeach; 
        ?>
      </div>
    </div>
  </section>

  <!-- CTA SECTION -->
  <section class="cta-section">
    <div class="container">
      <div class="cta-card reveal">
        <span class="section-badge">Start a Project</span>
        <h2 class="cta-title">Ready to Bake Something Fresh?</h2>
        <p class="cta-desc">Fill in your project details and let us bake a brilliant creative outcome for your brand.</p>
        <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
          <a href="bake.php" class="btn btn-primary btn-loaf">Bake a Project</a>
          <a href="pricing.php" class="btn btn-secondary btn-loaf">View Pricing</a>
        </div>
      </div>
    </div>
  </section>


  <!-- FOOTER -->
  <footer class="footer">
    <div class="container footer-top">
      <div class="footer-brand">
        <a href="#" class="logo footer-logo" aria-label="Adloaf Footer Home">
          <div class="logo-icon-wrap">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 15C3 13 4.5 10.5 7 10.5C9.5 10.5 10 12 12 12C14 12 14.5 10.5 17 10.5C19.5 10.5 21 13 21 15C21 18.5 18.5 20 12 20C5.5 20 3 18.5 3 15Z"/>
              <path d="M7 10.5C7 8 9 6.5 12 6.5C15 6.5 17 8 17 10.5" stroke-dasharray="1 1"/>
              <path d="M12 2V4M8 3.5l1.5 1.5M16 3.5L14.5 5" stroke="currentColor" stroke-width="2"/>
            </svg>
          </div>
          <span class="logo-text">Adloaf<span class="logo-dot">.</span></span>
        </a>
        <p class="footer-desc">
          Artisanal design thinking combined with professional marketing execution. Delivering fresh layouts that connect brands to consumers.
        </p>
      </div>

      <div class="footer-links-col">
        <h4 class="footer-col-title">Navigation</h4>
        <ul class="footer-links-list">
          <li><a href="#home" class="footer-link">Home</a></li>
          <li><a href="#about" class="footer-link">About Adloaf</a></li>
          <li><a href="#services" class="footer-link">Bakery Menu</a></li>
          <li><a href="#portfolio" class="footer-link">Portfolio Display</a></li>
          <li><a href="#process" class="footer-link">Bake Cycle</a></li>
        </ul>
      </div>

      <div class="footer-meta-col">
        <h4 class="footer-col-title">Oven Hours</h4>
        <div class="footer-contact-item">
          <span>Collaboration email</span>
          <?php echo htmlspecialchars(get_setting('contact_email')); ?>
        </div>
        <div class="footer-contact-item">
          <span>Global hours</span>
          Monday — Friday, 9:00 AM — 6:00 PM EST
        </div>
      </div>
    </div>

    <div class="container footer-bottom">
      <p class="copyright">&copy; <?php echo date('Y'); ?> Adloaf. All rights reserved.</p>
      <div class="footer-nav">
        <a href="#home" class="footer-nav-link">Privacy Policy</a>
        <a href="#home" class="footer-nav-link">Terms of Service</a>
      </div>
    </div>
  </footer>

  <!-- WhatsApp Float Widget -->
  <a href="https://wa.me/<?php echo ADMIN_WA; ?>?text=Hi%20Adloaf!%20I%20want%20to%20discuss%20a%20project." target="_blank" class="wa-float" id="wa-float" aria-label="Chat on WhatsApp">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
  </a>

  <!-- Core JavaScript -->
  <script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>

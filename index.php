<?php
require_once 'config.php';

// Fetch site settings
$settingsQuery = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$settingsData = $settingsQuery->fetchAll(PDO::FETCH_KEY_PAIR);

// Helper function for settings
function get_setting($key, $default = '') {
    global $settingsData;
    return isset($settingsData[$key]) ? $settingsData[$key] : $default;
}

// Fetch Services
$servicesQuery = $pdo->query("SELECT * FROM services ORDER BY sort_order ASC");
$services = $servicesQuery->fetchAll();

// Fetch Portfolio
$portfolioQuery = $pdo->query("SELECT * FROM portfolio_items ORDER BY sort_order ASC");
$portfolio = $portfolioQuery->fetchAll();

// Fetch Process Steps
$processQuery = $pdo->query("SELECT * FROM process_steps ORDER BY sort_order ASC");
$processSteps = $processQuery->fetchAll();

// Fetch Bento Cards
$bentoQuery = $pdo->query("SELECT * FROM bento_cards ORDER BY sort_order ASC");
$bentoCards = $bentoQuery->fetchAll();
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
      <a href="#" class="logo" id="nav-logo" aria-label="Adloaf Home">
        <div class="logo-icon-wrap">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 15C3 13 4.5 10.5 7 10.5C9.5 10.5 10 12 12 12C14 12 14.5 10.5 17 10.5C19.5 10.5 21 13 21 15C21 18.5 18.5 20 12 20C5.5 20 3 18.5 3 15Z"/>
            <path d="M7 10.5C7 8 9 6.5 12 6.5C15 6.5 17 8 17 10.5" stroke-dasharray="1 1"/>
            <path d="M12 2V4M8 3.5l1.5 1.5M16 3.5L14.5 5" stroke="currentColor" stroke-width="2"/>
          </svg>
        </div>
        <span class="logo-text">Adloaf<span class="logo-dot">.</span></span>
      </a>

      <nav aria-label="Main Navigation">
        <ul class="nav-menu" id="nav-menu">
          <li><a href="#home" class="nav-link active">Home</a></li>
          <li><a href="#about" class="nav-link">About</a></li>
          <li><a href="#services" class="nav-link">Services</a></li>
          <li><a href="#portfolio" class="nav-link">Works</a></li>
          <li><a href="#process" class="nav-link">Process</a></li>
          <li><a href="#contact" class="nav-link">Contact</a></li>
        </ul>
      </nav>

      <div class="header-actions">
        <a href="#contact" class="btn btn-primary btn-header" id="header-cta">Bake a Project</a>
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
          <a href="#portfolio" class="btn btn-primary btn-loaf" id="hero-cta-works">View Works</a>
          <a href="#contact" class="btn btn-secondary btn-loaf" id="hero-cta-contact">Contact Me</a>
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
          <a href="#contact" class="service-link">
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

  <!-- PORTFOLIO SECTION -->
  <section class="portfolio" id="portfolio">
    <div class="container">
      <div class="section-header reveal">
        <span class="section-badge">The Display Case</span>
        <h2 class="section-title">Oven-Fresh Works</h2>
        <p class="section-subtitle">Take a look at our showcase of active digital bakes. Filter categories using the tabs below to explore specific recipe results.</p>
      </div>

      <div class="portfolio-filters reveal">
        <button class="filter-btn active" data-filter="all">All Bakes</button>
        <button class="filter-btn" data-filter="websites">Websites</button>
        <button class="filter-btn" data-filter="posters">Posters</button>
        <button class="filter-btn" data-filter="branding">Branding</button>
        <button class="filter-btn" data-filter="social">Social Media</button>
        <button class="filter-btn" data-filter="uiconcepts">UI Concepts</button>
      </div>

      <div class="portfolio-grid" id="portfolio-grid">
        <?php 
        $delay = 0;
        foreach ($portfolio as $item): 
          $delayClass = $delay > 0 ? "reveal-delay-1" : "";
        ?>
        <div class="portfolio-item reveal <?php echo $delayClass; ?>" data-category="<?php echo htmlspecialchars($item['category']); ?>">
          <div class="portfolio-img-wrap">
            <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" loading="lazy">
            <div class="portfolio-overlay">
              <div class="portfolio-overlay-text">
                <span class="portfolio-tag"><?php echo htmlspecialchars(ucfirst($item['category'])); ?></span>
                <h4 class="portfolio-overlay-title"><?php echo htmlspecialchars($item['title']); ?></h4>
              </div>
            </div>
          </div>
          <div class="portfolio-info">
            <span class="portfolio-category"><?php echo htmlspecialchars(ucfirst($item['category'])); ?></span>
            <h3 class="portfolio-title"><?php echo htmlspecialchars($item['title']); ?></h3>
            <p class="portfolio-desc"><?php echo htmlspecialchars($item['description']); ?></p>
          </div>
        </div>
        <?php 
          $delay = ($delay + 1) % 2;
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

  <!-- CONTACT SECTION -->
  <section class="contact" id="contact">
    <div class="container contact-grid">
      <div class="contact-info-wrap reveal">
        <span class="section-badge">Bake with Us</span>
        <h2 class="section-title">Send a Recipe Request</h2>
        <p class="contact-header-subtitle">
          Have an idea or brand project waiting to be shaped? Drop us a line below or reach out via our social links. We serve hot creative inputs directly to your inbox.
        </p>

        <div class="contact-channels">
          <div class="contact-item">
            <div class="contact-icon-box">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
              </svg>
            </div>
            <div>
              <div class="contact-item-title">E-mail Address</div>
              <a href="mailto:<?php echo htmlspecialchars(get_setting('contact_email')); ?>" class="contact-item-link" id="contact-email"><?php echo htmlspecialchars(get_setting('contact_email')); ?></a>
            </div>
          </div>

          <div class="contact-item">
            <div class="contact-icon-box">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
              </svg>
            </div>
            <div>
              <div class="contact-item-title">WhatsApp Chat</div>
              <a href="<?php echo htmlspecialchars(get_setting('contact_whatsapp')); ?>" target="_blank" rel="noopener" class="contact-item-link" id="contact-whatsapp">Chat on WhatsApp</a>
            </div>
          </div>
        </div>

        <div class="social-links">
          <a href="<?php echo htmlspecialchars(get_setting('social_dribbble')); ?>" class="social-btn" target="_blank" rel="noopener" aria-label="Dribbble Account Link">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/>
              <path d="M8.56 2.75c4.37 6.03 6.02 9.42 8.03 17.72m2.54-15.38c-3.72 4.35-8.94 5.66-16.88 5.85m19.5 1.9c-3.5-.49-11.05 1-11.6 8.56"/>
            </svg>
          </a>
          <a href="<?php echo htmlspecialchars(get_setting('social_behance')); ?>" class="social-btn" target="_blank" rel="noopener" aria-label="Behance Account Link">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 19a5 5 0 0 0 5-5v-1a5 5 0 0 0-5-5M2 5h7a4 4 0 0 1 0 8H2V5Zm0 8h8a4 4 0 0 1 0 8H2v-8ZM14 6h7"/>
            </svg>
          </a>
          <a href="<?php echo htmlspecialchars(get_setting('social_linkedin')); ?>" class="social-btn" target="_blank" rel="noopener" aria-label="LinkedIn Account Link">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6zM2 9h4v12H2z"/><circle cx="4" cy="4" r="2"/>
            </svg>
          </a>
          <a href="<?php echo htmlspecialchars(get_setting('social_instagram')); ?>" class="social-btn" target="_blank" rel="noopener" aria-label="Instagram Account Link">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37zM17.5 6.5h.01"/>
            </svg>
          </a>
        </div>
      </div>

      <div class="contact-card reveal reveal-delay-1">
        <form class="contact-form" id="contact-form" novalidate>
          <div class="form-row-2">
            <div class="form-group">
              <label for="form-name" class="form-label">Full Name</label>
              <input type="text" id="form-name" class="form-input" placeholder="Your name" required>
            </div>
            <div class="form-group">
              <label for="form-email" class="form-label">Email Address</label>
              <input type="email" id="form-email" class="form-input" placeholder="you@example.com" required>
            </div>
          </div>

          <div class="form-row-2">
            <div class="form-group">
              <label for="form-subject" class="form-label">Subject</label>
              <input type="text" id="form-subject" class="form-input" placeholder="Project name / query" required>
            </div>
            <div class="form-group">
              <label for="form-service" class="form-label">Recipe Type</label>
              <select id="form-service" class="form-input" style="height: 53px;" required>
                <option value="" disabled selected>Select a Service</option>
                <option value="websites">Website Design</option>
                <option value="landing">Landing Pages</option>
                <option value="graphics">Graphic Design</option>
                <option value="branding">Brand Identity</option>
                <option value="social">Social Creatives</option>
                <option value="campaigns">Digital Campaigns</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label for="form-message" class="form-label">Project Ingredients</label>
            <textarea id="form-message" class="form-textarea" placeholder="Outline your project goals, timelines, and strategy details..." required></textarea>
          </div>

          <div class="form-message" id="form-success-box">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>
            </svg>
            <span>Your request has been placed in the oven! We will reply soon.</span>
          </div>

          <button type="submit" class="btn btn-primary btn-loaf" id="form-submit-btn" style="width: 100%;">Bake the Message</button>
        </form>
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

  <!-- Core JavaScript -->
  <script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>

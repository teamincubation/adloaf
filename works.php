<?php
require_once __DIR__ . '/lib/helpers.php';
try { track_visitor('/works.php'); } catch(Exception $e) {}

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
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    .works-page { min-height:100vh; background:var(--bg-primary); padding-top:100px; padding-bottom:60px; }
    .works-header { text-align:center; margin-bottom:3rem; }
    .works-title { font-size:2.5rem; font-weight:800; color:var(--text-primary); }
    .works-title span { background:linear-gradient(135deg,#EA580C,#D97706); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
    .works-filters { display:flex; flex-wrap:wrap; gap:.75rem; justify-content:center; margin-bottom:2.5rem; }
    .works-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:1.5rem; max-width:1200px; margin:0 auto; padding:0 1.5rem; }
    .works-item { border-radius:16px; overflow:hidden; background:var(--bg-secondary); border:1px solid var(--border-color); transition:transform .3s,box-shadow .3s; }
    .works-item:hover { transform:translateY(-6px); box-shadow:0 20px 60px rgba(234,88,12,0.15); }
    .works-img { width:100%; aspect-ratio:4/3; object-fit:cover; display:block; }
    .works-info { padding:1.25rem; }
    .works-cat { font-size:0.75rem; text-transform:uppercase; letter-spacing:1px; color:var(--primary-color); font-weight:600; margin-bottom:.4rem; }
    .works-name { font-size:1.1rem; font-weight:700; color:var(--text-primary); margin-bottom:.4rem; }
    .works-desc { color:var(--text-secondary); font-size:.85rem; line-height:1.6; }
    .hidden { display:none !important; }
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
    <nav>
      <ul class="nav-menu">
        <li><a href="index.php" class="nav-link">Home</a></li>
        <li><a href="works.php" class="nav-link active">Works</a></li>
        <li><a href="pricing.php" class="nav-link">Pricing</a></li>
        <li><a href="bake.php" class="nav-link">Bake a Project</a></li>
      </ul>
    </nav>
  </div>
</header>

<div class="works-page">
  <div style="text-align:center;padding:0 1.5rem 2rem;" class="works-header">
    <span class="section-badge">Display Case</span>
    <h1 class="works-title">Our <span>Oven-Fresh</span> Works</h1>
    <p style="color:var(--text-secondary);max-width:500px;margin:0 auto;">Browse our full portfolio — every piece freshly baked with strategy, creativity, and craft.</p>
  </div>

  <div class="works-filters">
    <button class="filter-btn active" data-filter="all">All Bakes</button>
    <?php foreach ($categories as $cat): ?>
      <button class="filter-btn" data-filter="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars(ucfirst($cat)); ?></button>
    <?php endforeach; ?>
  </div>

  <div class="works-grid" id="works-grid">
    <?php foreach ($portfolio as $item): ?>
    <div class="works-item" data-category="<?php echo htmlspecialchars($item['category']); ?>">
      <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="works-img" loading="lazy">
      <div class="works-info">
        <div class="works-cat"><?php echo htmlspecialchars(ucfirst($item['category'])); ?></div>
        <h3 class="works-name"><?php echo htmlspecialchars($item['title']); ?></h3>
        <p class="works-desc"><?php echo htmlspecialchars($item['description']); ?></p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- WhatsApp Widget -->
<a href="https://wa.me/<?php echo ADMIN_WA; ?>?text=Hi%20Adloaf!" target="_blank" class="wa-float" aria-label="Chat on WhatsApp">
  <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
</a>

<script>
document.querySelectorAll('.filter-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    const filter = this.dataset.filter;
    document.querySelectorAll('.works-item').forEach(item => {
      if (filter === 'all' || item.dataset.category === filter) {
        item.classList.remove('hidden');
      } else {
        item.classList.add('hidden');
      }
    });
  });
});
</script>
</body>
</html>

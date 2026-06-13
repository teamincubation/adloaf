<?php
require_once 'header.php';

$success  = '';
$errorMsg = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("SELECT logo_path FROM popular_clients WHERE id=?");
    $stmt->execute([$_GET['delete']]);
    $cl = $stmt->fetch();
    if ($cl && file_exists('../' . $cl['logo_path'])) @unlink('../' . $cl['logo_path']);
    $pdo->prepare("DELETE FROM popular_clients WHERE id=?")->execute([$_GET['delete']]);
    $success = "Client logo removed.";
}

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = htmlspecialchars(strip_tags($_POST['client_name'] ?? ''));
    $url    = htmlspecialchars(strip_tags($_POST['website_url'] ?? ''));
    $order  = intval($_POST['sort_order'] ?? 0);
    $logo   = null;

    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $ext  = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $dest = '../assets/uploads/clients/';
        if (!file_exists($dest)) mkdir($dest, 0755, true);
        $fname = bin2hex(random_bytes(12)) . '.' . strtolower($ext);
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $dest . $fname)) {
            $logo = 'assets/uploads/clients/' . $fname;
        }
    }

    if ($name && $logo) {
        $pdo->prepare("INSERT INTO popular_clients (client_name, logo_path, website_url, sort_order) VALUES (?,?,?,?)")
            ->execute([$name, $logo, $url, $order]);
        $success = "Client brand added successfully.";
    } else {
        $errorMsg = "Client name and logo file are required.";
    }
}

try {
    $clients = $pdo->query("SELECT * FROM popular_clients ORDER BY sort_order ASC")->fetchAll();
} catch (PDOException $e) {
    $clients  = [];
    $errorMsg = "Please run schema_v2.sql first.";
}
?>

<div class="admin-header">
    <h1 class="admin-page-title">Popular Clients Marquee</h1>
</div>

<?php if ($success): ?>
    <div class="success-toast"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
    <div style="background:rgba(239,68,68,0.1); color:#ef4444; padding:1rem; border-radius:8px; margin-bottom:1rem; font-weight:700;"><?php echo htmlspecialchars($errorMsg); ?></div>
<?php endif; ?>

<div class="admin-card" style="margin-bottom: 2rem;">
    <h3 style="color:var(--text-primary); margin-bottom: 1.5rem; font-size: 1.2rem; font-weight: 700;">Add Client Logo</h3>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-row-2">
            <div class="form-group">
                <label class="form-label">Client / Brand Name</label>
                <input type="text" name="client_name" class="form-input" placeholder="e.g. Acme Corp" required style="height: 50px;">
            </div>
            <div class="form-group">
                <label class="form-label">Website URL (optional)</label>
                <input type="url" name="website_url" class="form-input" placeholder="https://example.com" style="height: 50px;">
            </div>
        </div>
        <div class="form-row-2" style="margin-top: 1rem;">
            <div class="form-group">
                <label class="form-label">Client Logo (PNG/SVG recommended)</label>
                <input type="file" name="logo" class="form-input" accept="image/*" required style="padding-top:11px; height: 50px; cursor: pointer;">
            </div>
            <div class="form-group">
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order" class="form-input" value="0" style="height: 50px;">
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="padding:0.75rem 2rem; border-radius: 6px; margin-top: 1.5rem;">Add Brand</button>
    </form>
</div>

<div class="admin-card">
    <h3 style="color:var(--text-primary); margin-bottom: 1.5rem; font-size: 1.2rem; font-weight: 700;">Current Active Marquee Logos (<?php echo count($clients); ?>)</h3>
    
    <?php if (empty($clients)): ?>
        <p style="color:var(--text-secondary); text-align:center; padding:2rem;">No logo marquee items added yet.</p>
    <?php else: ?>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(180px, 1fr)); gap:1.5rem;">
            <?php foreach ($clients as $cl): ?>
            <div style="background:var(--bg-tertiary); border-radius:12px; padding:1.5rem 1rem; text-align:center; border:1.5px solid var(--border-color); display: flex; flex-direction: column; justify-content: space-between; align-items: center; min-height: 160px; transition: border-color 0.2s;">
                <div style="flex: 1; display: flex; align-items: center; justify-content: center; width: 100%;">
                    <img src="../<?php echo htmlspecialchars($cl['logo_path']); ?>" alt="<?php echo htmlspecialchars($cl['client_name']); ?>" style="max-height:50px; max-width:140px; object-fit:contain; filter:brightness(1.5);">
                </div>
                <div style="margin-top: 1rem; width: 100%;">
                    <p style="color:var(--text-primary); font-size:0.88rem; font-weight:700; margin-bottom: 0.25rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($cl['client_name']); ?></p>
                    <a href="clients_brand.php?delete=<?php echo $cl['id']; ?>" onclick="return confirm('Are you sure you want to remove this client brand logo from the marquee?');" style="color:#ef4444; font-size:0.8rem; font-weight: 700; text-decoration: none;">Remove Brand</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>

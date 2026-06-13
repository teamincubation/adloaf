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
    $success = "Client removed.";
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
        $success = "Client added.";
    } else {
        $errorMsg = "Name and logo are required.";
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

<?php if ($success): ?><div class="success-toast"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
<?php if ($errorMsg): ?><div style="background:rgba(239,68,68,0.1);color:#ef4444;padding:1rem;border-radius:8px;margin-bottom:1rem;"><?php echo htmlspecialchars($errorMsg); ?></div><?php endif; ?>

<div class="admin-card">
    <h3 style="color:var(--text-primary);margin-bottom:1rem;">Add Client Logo</h3>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-row-2">
            <div class="form-group">
                <label class="form-label">Client / Brand Name</label>
                <input type="text" name="client_name" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Website URL (optional)</label>
                <input type="url" name="website_url" class="form-input" placeholder="https://...">
            </div>
        </div>
        <div class="form-row-2">
            <div class="form-group">
                <label class="form-label">Client Logo (PNG/SVG recommended)</label>
                <input type="file" name="logo" class="form-input" accept="image/*" required style="padding-top:13px;">
            </div>
            <div class="form-group">
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order" class="form-input" value="0">
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="padding:0.5rem 1.5rem;">Add Client</button>
    </form>
</div>

<div class="admin-card">
    <h3 style="color:var(--text-primary);margin-bottom:1rem;">Current Clients</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1.5rem;">
        <?php foreach ($clients as $cl): ?>
        <div style="background:var(--bg-tertiary);border-radius:12px;padding:1rem;text-align:center;border:1px solid var(--border-color);">
            <img src="../<?php echo htmlspecialchars($cl['logo_path']); ?>" alt="<?php echo htmlspecialchars($cl['client_name']); ?>" style="max-height:60px;max-width:130px;object-fit:contain;filter:brightness(1.5);">
            <p style="color:var(--text-primary);font-size:0.85rem;margin-top:0.5rem;font-weight:600;"><?php echo htmlspecialchars($cl['client_name']); ?></p>
            <a href="clients_brand.php?delete=<?php echo $cl['id']; ?>" onclick="return confirm('Remove?');" style="color:#ef4444;font-size:0.8rem;">Remove</a>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>

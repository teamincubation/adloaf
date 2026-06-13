<?php
require_once 'header.php';

$success = '';
$errorMsg = '';

// Handle Status Change
if (isset($_GET['status']) && isset($_GET['id'])) {
    $allowed = ['Accepted','Rejected','Approved','Completed'];
    if (in_array($_GET['status'], $allowed)) {
        $pdo->prepare("UPDATE bake_requests SET status=? WHERE id=?")->execute([$_GET['status'], $_GET['id']]);
        $success = "Request status updated to " . $_GET['status'];
    }
}

// Handle Admin Note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_note'])) {
    $pdo->prepare("UPDATE bake_requests SET admin_notes=? WHERE id=?")->execute([$_POST['admin_note'], $_POST['request_id']]);
    $success = "Note saved.";
}

// Fetch all bake requests
try {
    $requests = $pdo->query("
        SELECT br.*, u.full_name, u.email, u.whatsapp 
        FROM bake_requests br 
        JOIN users_public u ON br.user_id = u.id 
        ORDER BY br.created_at DESC
    ")->fetchAll();
} catch (PDOException $e) {
    $requests = [];
    $errorMsg = "Please run schema_v2.sql — tables don't exist yet.";
}

$adminWA = ADMIN_WA;
?>

<div class="admin-header">
    <h1 class="admin-page-title">Bake Requests</h1>
</div>

<?php if ($success): ?>
    <div class="success-toast"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
    <div style="background:rgba(239,68,68,0.1);color:#ef4444;padding:1rem;border-radius:8px;margin-bottom:1rem;"><?php echo htmlspecialchars($errorMsg); ?></div>
<?php endif; ?>

<style>
    .status-badge { display:inline-block; padding:4px 10px; border-radius:20px; font-size:0.78rem; font-weight:700; }
    .status-Pending   { background:rgba(239,68,68,0.12); color:#ef4444; }
    .status-Accepted  { background:rgba(245,158,11,0.12); color:#f59e0b; }
    .status-Approved  { background:rgba(16,185,129,0.12); color:#10b981; }
    .status-Rejected  { background:rgba(107,114,128,0.12); color:#9ca3af; }
    .status-Completed { background:rgba(99,102,241,0.12); color:#818cf8; }
    .action-dropdown { position:relative; display:inline-block; }
    .action-dropdown:hover .dropdown-menu { display:block; }
    .dropdown-menu { display:none; position:absolute; right:0; top:100%; background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:8px; min-width:150px; z-index:100; box-shadow:0 8px 20px rgba(0,0,0,0.3); }
    .dropdown-menu a { display:block; padding:0.6rem 1rem; color:var(--text-secondary); font-size:0.85rem; transition:background 0.2s; }
    .dropdown-menu a:hover { background:rgba(234,88,12,0.1); color:var(--primary-color); }
    .wa-btn { background:#25D366; color:#fff; padding:4px 10px; border-radius:6px; font-size:0.8rem; text-decoration:none; display:inline-flex; align-items:center; gap:4px; }
    .wa-btn:hover { background:#128C7E; }
</style>

<div class="admin-card">
    <?php if (empty($requests)): ?>
        <p style="color:var(--text-secondary);text-align:center;padding:2rem;">No bake requests yet. 🍞</p>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table class="admin-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Client</th>
                <th>Service</th>
                <th>Deadline</th>
                <th>Price (INR)</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($requests as $req): ?>
            <tr>
                <td><?php echo $req['id']; ?></td>
                <td>
                    <strong><?php echo htmlspecialchars($req['full_name']); ?></strong><br>
                    <small style="color:var(--text-secondary);"><?php echo htmlspecialchars($req['email']); ?></small><br>
                    <?php if ($req['whatsapp']): ?>
                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $req['whatsapp']); ?>?text=<?php echo urlencode("Hi " . $req['full_name'] . "! Your Adloaf project request has been received. Let's discuss your " . $req['service_type'] . " project!"); ?>" target="_blank" class="wa-btn">
                        WA Chat
                    </a>
                    <?php endif; ?>
                </td>
                <td>
                    <strong><?php echo htmlspecialchars($req['service_type']); ?></strong><br>
                    <small style="color:var(--text-secondary);"><?php echo htmlspecialchars($req['content_language'] ?? 'English'); ?></small>
                </td>
                <td><?php echo date('M d, Y', strtotime($req['deadline'])); ?></td>
                <td>
                    <small>Est: ₹<?php echo number_format($req['estimated_price_inr']); ?></small><br>
                    <small style="color:var(--text-secondary);">Market: ₹<?php echo number_format($req['market_price_inr']); ?></small>
                </td>
                <td>
                    <span class="status-badge status-<?php echo $req['status']; ?>"><?php echo $req['status']; ?></span>
                </td>
                <td>
                    <div class="action-dropdown">
                        <button style="background:rgba(234,88,12,0.1);color:var(--primary-color);border:none;border-radius:6px;padding:5px 10px;cursor:pointer;font-size:0.85rem;">
                            Actions ▾
                        </button>
                        <div class="dropdown-menu">
                            <a href="bake_requests.php?id=<?php echo $req['id']; ?>&status=Accepted">✅ Accept</a>
                            <a href="bake_requests.php?id=<?php echo $req['id']; ?>&status=Approved">🎉 Approve</a>
                            <a href="bake_requests.php?id=<?php echo $req['id']; ?>&status=Rejected">❌ Reject</a>
                            <a href="bake_requests.php?id=<?php echo $req['id']; ?>&status=Completed">✔ Complete</a>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="7" style="background:rgba(0,0,0,0.1);padding:1rem;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div>
                            <strong style="color:var(--text-primary);font-size:0.85rem;">Project Description:</strong>
                            <p style="color:var(--text-secondary);font-size:0.85rem;margin-top:4px;"><?php echo nl2br(htmlspecialchars($req['project_description'])); ?></p>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                            <label style="color:var(--text-primary);font-size:0.85rem;font-weight:600;">Admin Note:</label>
                            <textarea name="admin_note" style="width:100%;margin-top:4px;padding:0.5rem;background:var(--bg-tertiary);border:1px solid var(--border-color);color:var(--text-primary);border-radius:6px;font-family:inherit;font-size:0.85rem;" rows="2"><?php echo htmlspecialchars($req['admin_notes'] ?? ''); ?></textarea>
                            <button type="submit" style="background:var(--primary-color);color:#fff;border:none;border-radius:6px;padding:4px 12px;cursor:pointer;font-size:0.82rem;margin-top:4px;">Save Note</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>

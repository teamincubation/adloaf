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
    <h1 class="admin-page-title">Bake Requests Manager</h1>
</div>

<?php if ($success): ?>
    <div class="success-toast"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
    <div style="background:rgba(239,68,68,0.1); color:#ef4444; padding:1rem; border-radius:8px; margin-bottom:1rem; font-weight:700;"><?php echo htmlspecialchars($errorMsg); ?></div>
<?php endif; ?>

<style>
    .status-badge { display:inline-block; padding:4px 10px; border-radius:20px; font-size:0.75rem; font-weight:700; text-transform: uppercase; letter-spacing: 0.5px; }
    .status-Pending   { background:rgba(239,68,68,0.15); color:#ef4444; }
    .status-Accepted  { background:rgba(245,158,11,0.15); color:#f59e0b; }
    .status-Approved  { background:rgba(16,185,129,0.15); color:#10b981; }
    .status-Rejected  { background:rgba(107,114,128,0.15); color:#9ca3af; }
    .status-Completed { background:rgba(99,102,241,0.15); color:#818cf8; }
    .action-dropdown { position:relative; display:inline-block; }
    .action-dropdown:hover .dropdown-menu { display:block; }
    .dropdown-menu { display:none; position:absolute; right:0; top:100%; background:var(--bg-tertiary); border:1.5px solid var(--border-color); border-radius:8px; min-width:150px; z-index:100; box-shadow:0 8px 24px rgba(0,0,0,0.4); }
    .dropdown-menu a { display:block; padding:0.65rem 1rem; color:var(--text-secondary); font-size:0.85rem; transition:all 0.2s; text-decoration:none; }
    .dropdown-menu a:hover { background:rgba(234,88,12,0.15); color:var(--primary-color); }
    .wa-btn { background:#25D366; color:#fff; padding:6px 12px; border-radius:6px; font-size:0.8rem; text-decoration:none; display:inline-flex; align-items:center; gap:6px; font-weight: 700; transition: background 0.2s; }
    .wa-btn:hover { background:#128C7E; }
</style>

<div class="admin-card">
    <?php if (empty($requests)): ?>
        <p style="color:var(--text-secondary); text-align:center; padding:3rem; font-size:1.1rem;">No bake requests yet. 🍞</p>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table class="admin-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Client Details</th>
                <th>Selected Service</th>
                <th>Requested Deadline</th>
                <th>Estimated Pricing</th>
                <th>Current Status</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($requests as $req): ?>
            <tr style="border-bottom: none;">
                <td style="font-weight: 700; color: var(--primary-color);"><?php echo $req['id']; ?></td>
                <td>
                    <strong style="font-size: 0.95rem; color: var(--text-primary);"><?php echo htmlspecialchars($req['full_name']); ?></strong><br>
                    <span style="color:var(--text-secondary); font-size: 0.85rem;"><?php echo htmlspecialchars($req['email']); ?></span><br>
                    <div style="margin-top: 0.4rem;">
                        <?php if ($req['whatsapp']): ?>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $req['whatsapp']); ?>?text=<?php echo urlencode("Hi " . $req['full_name'] . "! Your Adloaf project request has been received. Let's discuss your " . $req['service_type'] . " project!"); ?>" target="_blank" class="wa-btn">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="display:inline-block;"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            WhatsApp Client
                        </a>
                        <?php endif; ?>
                    </div>
                </td>
                <td>
                    <strong style="color: var(--text-primary);"><?php echo htmlspecialchars($req['service_type']); ?></strong><br>
                    <span style="color:var(--text-secondary); font-size: 0.85rem;">Lang: <?php echo htmlspecialchars(ucfirst($req['content_language'] ?? 'English')); ?></span>
                </td>
                <td style="font-weight: 600;"><?php echo date('M d, Y', strtotime($req['deadline'])); ?></td>
                <td>
                    <span style="font-weight:700; color: var(--primary-color);">₹<?php echo number_format($req['estimated_price_inr']); ?></span><br>
                    <small style="color:var(--text-secondary); font-size: 0.8rem;">Market: ₹<?php echo number_format($req['market_price_inr']); ?></small>
                </td>
                <td>
                    <span class="status-badge status-<?php echo $req['status']; ?>"><?php echo $req['status']; ?></span>
                </td>
                <td style="text-align: right;">
                    <div class="action-dropdown">
                        <button class="btn btn-primary" style="padding: 6px 12px; font-size: 0.85rem; border-radius: 6px;">
                            Change Status ▾
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
            <tr style="border-bottom: 2px solid var(--border-color);">
                <td colspan="7" style="background:rgba(234, 88, 12, 0.02); padding: 1.5rem; border-radius: 8px;">
                    <div style="display:grid; grid-template-columns: 1.2fr 0.8fr; gap: 2rem;">
                        <div>
                            <strong style="color:var(--text-primary); font-size:0.9rem; display: block; margin-bottom: 0.4rem;">Project Ingredients Brief:</strong>
                            <p style="color:var(--text-secondary); font-size:0.88rem; line-height: 1.5; white-space: pre-line; background: var(--bg-tertiary); padding: 1rem; border-radius: 6px; border: 1px solid var(--border-color);"><?php echo htmlspecialchars($req['project_description']); ?></p>
                            
                            <?php if ($req['ai_generated_desc']): ?>
                                <strong style="color:var(--text-primary); font-size:0.9rem; display: block; margin-top: 1rem; margin-bottom: 0.4rem;">AI Enhanced Output:</strong>
                                <p style="color:var(--text-secondary); font-size:0.88rem; line-height: 1.5; white-space: pre-line; background: rgba(99, 102, 241, 0.05); padding: 1rem; border-radius: 6px; border: 1px solid rgba(99, 102, 241, 0.2);"><?php echo htmlspecialchars($req['ai_generated_desc']); ?></p>
                            <?php endif; ?>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                            <label style="color:var(--text-primary); font-size:0.9rem; font-weight:600; display: block; margin-bottom: 0.4rem;">Admin Notes / Client Instructions:</label>
                            <textarea name="admin_note" class="form-textarea" style="width:100%; margin-bottom: 0.75rem;" rows="5" placeholder="These notes will be displayed to the client on their profile dashboard."><?php echo htmlspecialchars($req['admin_notes'] ?? ''); ?></textarea>
                            <button type="submit" class="btn btn-primary" style="padding: 6px 16px; font-size: 0.85rem; border-radius: 6px;">Save Client Notes</button>
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

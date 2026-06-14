<?php
require_once 'header.php';
require_once '../lib/Mailer.php';

$success = '';
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
$errorMsg = '';

// Load currency symbol
$currencySymbol = site_setting('base_currency_symbol', '₹');

// Handle Attachment Deletion
if (isset($_GET['delete_file_path']) && isset($_GET['request_id'])) {
    $reqId = intval($_GET['request_id']);
    $filePath = $_GET['delete_file_path'];
    
    $stmt = $pdo->prepare("SELECT uploaded_files FROM bake_requests WHERE id = ?");
    $stmt->execute([$reqId]);
    $uploadedJson = $stmt->fetchColumn();
    if ($uploadedJson) {
        $filesArray = json_decode($uploadedJson, true);
        if (is_array($filesArray)) {
            $updatedArray = [];
            foreach ($filesArray as $f) {
                if ($f['path'] === $filePath) {
                    $fullDiskPath = __DIR__ . '/../' . $f['path'];
                    if (file_exists($fullDiskPath)) {
                        @unlink($fullDiskPath);
                    }
                } else {
                    $updatedArray[] = $f;
                }
            }
            $newJson = empty($updatedArray) ? null : json_encode($updatedArray);
            $pdo->prepare("UPDATE bake_requests SET uploaded_files = ? WHERE id = ?")->execute([$newJson, $reqId]);
            $_SESSION['success_message'] = "Attachment deleted successfully.";
            header("Location: bake_requests.php" . (isset($_GET['archive']) && $_GET['archive'] == 1 ? "?archive=1" : ""));
            exit;
        }
    }
}

// Handle Status or Cost/Notes Change via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_request') {
    $reqId = intval($_POST['request_id']);
    $status = $_POST['status'] ?? 'Pending';
    $totalCost = floatval($_POST['total_cost'] ?? 0.00);
    $adminNote = $_POST['admin_note'] ?? '';

    $pdo->prepare("UPDATE bake_requests SET status=?, total_cost=?, admin_notes=? WHERE id=?")
        ->execute([$status, $totalCost, $adminNote, $reqId]);
    
    // Send email notification with status, cost, notes, and payment details
    $reqDetails = $pdo->prepare("SELECT br.*, u.full_name, u.email, u.whatsapp FROM bake_requests br JOIN users_public u ON br.user_id = u.id WHERE br.id = ?");
    $reqDetails->execute([$reqId]);
    $reqData = $reqDetails->fetch();

    if ($reqData) {
        // Automatically sync Accepted/Approved/Completed requests to projects table
        if (in_array($status, ['Accepted', 'Approved', 'Completed'])) {
            $chkProj = $pdo->prepare("SELECT id FROM projects WHERE bake_request_id = ?");
            $chkProj->execute([$reqId]);
            if (!$chkProj->fetchColumn()) {
                $projStatus = 'Pending';
                if ($status === 'Completed') {
                    $projStatus = 'Completed';
                } else if ($status === 'Approved' || $status === 'Accepted') {
                    $projStatus = 'Ongoing';
                }
                
                $insProj = $pdo->prepare("INSERT INTO projects (client_id, title, description, price, paid_amount, status, due_date, bake_request_id) VALUES (?, ?, ?, ?, 0.00, ?, ?, ?)");
                $insProj->execute([
                    $reqData['user_id'],
                    $reqData['service_type'],
                    $reqData['project_description'],
                    $totalCost,
                    $projStatus,
                    $reqData['deadline'],
                    $reqId
                ]);
            }
        }

        $bankDetails = site_setting('payment_bank_details');
        $upiNum = site_setting('payment_upi_number');
        $upiId = site_setting('payment_upi_id');
        $qrImage = site_setting('payment_qr');

        $payInfo = [
            'bank'       => $bankDetails,
            'upi_number' => $upiNum,
            'upi_id'     => $upiId,
            'qr'         => $qrImage
        ];

        $formattedCost = $currencySymbol . number_format($reqData['total_cost'], 2);

        try {
            $mailer = new Mailer();
            $mailer->sendRequestStatusUpdate(
                $reqData['email'],
                $reqData['full_name'],
                $reqData['service_type'],
                $reqData['status'],
                $formattedCost,
                $reqData['admin_notes'],
                $payInfo
            );
        } catch (Exception $e) {}
    }
    
    $_SESSION['success_message'] = "Bake request updated successfully and client notified.";
    header("Location: bake_requests.php" . (isset($_GET['archive']) && $_GET['archive'] == 1 ? "?archive=1" : ""));
    exit;
}

// Handle status updates from links
if (isset($_GET['status']) && isset($_GET['id'])) {
    $reqId = intval($_GET['id']);
    $status = $_GET['status'];
    $allowed = ['Pending','Accepted','Rejected','Approved','Completed'];
    if (in_array($status, $allowed)) {
        $pdo->prepare("UPDATE bake_requests SET status=? WHERE id=?")->execute([$status, $reqId]);
        
        // Auto email client on status update link
        $reqDetails = $pdo->prepare("SELECT br.*, u.full_name, u.email FROM bake_requests br JOIN users_public u ON br.user_id = u.id WHERE br.id = ?");
        $reqDetails->execute([$reqId]);
        $reqData = $reqDetails->fetch();
        if ($reqData) {
            // Automatically sync to projects table
            if (in_array($status, ['Accepted', 'Approved', 'Completed'])) {
                $chkProj = $pdo->prepare("SELECT id FROM projects WHERE bake_request_id = ?");
                $chkProj->execute([$reqId]);
                if (!$chkProj->fetchColumn()) {
                    $projStatus = 'Pending';
                    if ($status === 'Completed') {
                        $projStatus = 'Completed';
                    } else if ($status === 'Approved' || $status === 'Accepted') {
                        $projStatus = 'Ongoing';
                    }
                    
                    $insProj = $pdo->prepare("INSERT INTO projects (client_id, title, description, price, paid_amount, status, due_date, bake_request_id) VALUES (?, ?, ?, ?, 0.00, ?, ?, ?)");
                    $insProj->execute([
                        $reqData['user_id'],
                        $reqData['service_type'],
                        $reqData['project_description'],
                        $reqData['total_cost'] ?: $reqData['estimated_price_inr'],
                        $projStatus,
                        $reqData['deadline'],
                        $reqId
                    ]);
                }
            }

            $formattedCost = $currencySymbol . number_format($reqData['total_cost'], 2);
            $payInfo = [
                'bank'       => site_setting('payment_bank_details'),
                'upi_number' => site_setting('payment_upi_number'),
                'upi_id'     => site_setting('payment_upi_id'),
                'qr'         => site_setting('payment_qr')
            ];
            try {
                $mailer = new Mailer();
                $mailer->sendRequestStatusUpdate($reqData['email'], $reqData['full_name'], $reqData['service_type'], $reqData['status'], $formattedCost, $reqData['admin_notes'], $payInfo);
            } catch (Exception $e) {}
        }
        
        $_SESSION['success_message'] = "Request status updated to " . $status . " and client notified.";
        header("Location: bake_requests.php" . (isset($_GET['archive']) && $_GET['archive'] == 1 ? "?archive=1" : ""));
        exit;
    }
}

// Filter Active vs Archived requests
$showArchive = isset($_GET['archive']) && $_GET['archive'] == 1;
if ($showArchive) {
    $whereClause = "WHERE br.status IN ('Completed', 'Rejected')";
} else {
    $whereClause = "WHERE br.status NOT IN ('Completed', 'Rejected') OR br.status IS NULL";
}

// Fetch all bake requests
try {
    $requests = $pdo->query("
        SELECT br.*, u.full_name, u.email, u.whatsapp 
        FROM bake_requests br 
        JOIN users_public u ON br.user_id = u.id 
        $whereClause
        ORDER BY br.created_at DESC
    ")->fetchAll();
} catch (PDOException $e) {
    $requests = [];
    $errorMsg = "Database error. Please run the schema setup scripts.";
}

$adminWA = ADMIN_WA;
?>

<div class="admin-header">
    <h1 class="admin-page-title">Bake Requests Manager</h1>
</div>

<div class="profile-nav-tabs" style="margin-bottom: 1.5rem; display: flex; gap: 1rem; border-bottom: 1px solid var(--border-medium); padding-bottom: 0.5rem;">
    <a href="bake_requests.php" class="btn <?php echo !$showArchive ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 0.5rem 1.25rem; font-size: 0.9rem; text-decoration: none;">🥐 Active Requests</a>
    <a href="bake_requests.php?archive=1" class="btn <?php echo $showArchive ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 0.5rem 1.25rem; font-size: 0.9rem; text-decoration: none;">📦 Completed & Rejected</a>
</div>

<?php if ($success): ?>
    <div class="success-toast"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
    <div style="background:rgba(239,68,68,0.1); color:#ef4444; padding:1rem; border-radius:8px; margin-bottom:1rem; font-weight:700; border: 1px solid rgba(239,68,68,0.2);"><?php echo htmlspecialchars($errorMsg); ?></div>
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
    .dropdown-menu { display:none; position:absolute; right:0; top:100%; background:var(--bg-secondary); border:1.5px solid var(--border-medium); border-radius:8px; min-width:150px; z-index:100; box-shadow:0 8px 24px rgba(0,0,0,0.15); }
    .dropdown-menu a { display:block; padding:0.65rem 1rem; color:var(--text-secondary); font-size:0.85rem; transition:all 0.2s; text-decoration:none; }
    .dropdown-menu a:hover { background:rgba(234,88,12,0.08); color:var(--accent-orange); }
    
    .wa-btn { background:#25D366; color:#fff; padding:6px 12px; border-radius:6px; font-size:0.8rem; text-decoration:none; display:inline-flex; align-items:center; gap:6px; font-weight: 700; transition: background 0.2s; border: none; cursor: pointer; }
    .wa-btn:hover { background:#128C7E; }
    .call-btn { background:#3b82f6; color:#fff; padding:6px 12px; border-radius:6px; font-size:0.8rem; text-decoration:none; display:inline-flex; align-items:center; gap:6px; font-weight: 700; transition: background 0.2s; }
    .call-btn:hover { background:#2563eb; }
    .file-del-link { color:#ef4444; font-size:0.8rem; font-weight:700; text-decoration:none; margin-left:10px; }
    .file-del-link:hover { text-decoration:underline; }
</style>

<div class="admin-card">
    <?php if (empty($requests)): ?>
        <p style="color:var(--text-secondary); text-align:center; padding:3rem; font-size:1.1rem;">No bake requests found in this tab. 🍞</p>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table class="admin-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Client Details</th>
                <th>Selected Service</th>
                <th>Requested Deadline</th>
                <th>Quotation Cost</th>
                <th>Current Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($requests as $req): ?>
            <tr style="border-bottom: none;">
                <td style="font-weight: 700; color: var(--accent-orange);"><?php echo $req['id']; ?></td>
                <td>
                    <strong style="font-size: 0.95rem; color: var(--text-primary);"><?php echo htmlspecialchars($req['full_name']); ?></strong><br>
                    <span style="color:var(--text-secondary); font-size: 0.85rem;"><?php echo htmlspecialchars($req['email']); ?></span><br>
                    <div style="margin-top: 0.5rem; display: flex; gap: 6px; flex-wrap: wrap;">
                        <?php if ($req['whatsapp']): ?>
                        <a href="tel:<?php echo htmlspecialchars($req['whatsapp']); ?>" class="call-btn">
                            📞 Call Client
                        </a>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $req['whatsapp']); ?>?text=<?php echo urlencode("Hi " . $req['full_name'] . "! Your adloaf project request has been received. Let's discuss details!"); ?>" target="_blank" class="wa-btn">
                            💬 Chat on WA
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
                    <span style="font-weight:700; color: var(--accent-orange);"><?php echo $currencySymbol . number_format($req['total_cost'] ?: $req['estimated_price_inr'], 2); ?></span><br>
                    <small style="color:var(--text-muted); font-size: 0.8rem;">Est: <?php echo $currencySymbol . number_format($req['estimated_price_inr'], 2); ?></small>
                </td>
                <td>
                    <span class="status-badge status-<?php echo $req['status']; ?>"><?php echo $req['status']; ?></span>
                </td>
            </tr>
            <tr style="border-bottom: 2px solid var(--border-medium);">
                <td colspan="6" style="background:rgba(234, 88, 12, 0.01); padding: 1.5rem; border-radius: 8px;">
                    <div style="display:grid; grid-template-columns: 1.2fr 0.8fr; gap: 2rem;">
                        <div>
                            <strong style="color:var(--text-primary); font-size:0.9rem; display: block; margin-bottom: 0.4rem;">Project Ingredients Brief:</strong>
                            <p style="color:var(--text-secondary); font-size:0.88rem; line-height: 1.5; white-space: pre-line; background: var(--bg-primary); padding: 1rem; border-radius: 6px; border: 1px solid var(--border-medium);"><?php echo htmlspecialchars($req['project_description']); ?></p>
                            
                            <?php if ($req['ai_generated_desc']): ?>
                                <strong style="color:var(--text-primary); font-size:0.9rem; display: block; margin-top: 1rem; margin-bottom: 0.4rem;">AI Enhanced Output:</strong>
                                <p style="color:var(--text-secondary); font-size:0.88rem; line-height: 1.5; white-space: pre-line; background: rgba(99, 102, 241, 0.03); padding: 1rem; border-radius: 6px; border: 1px solid rgba(99, 102, 241, 0.15);"><?php echo htmlspecialchars($req['ai_generated_desc']); ?></p>
                            <?php endif; ?>

                            <!-- Uploaded Files -->
                            <?php if (!empty($req['uploaded_files'])): ?>
                                <strong style="color:var(--text-primary); font-size:0.9rem; display: block; margin-top: 1rem; margin-bottom: 0.4rem;">📁 Client Uploaded Ingredients:</strong>
                                <div style="background:var(--bg-primary); border: 1px solid var(--border-medium); padding: 0.75rem; border-radius: 6px; display: flex; flex-direction: column; gap: 6px;">
                                <?php foreach (json_decode($req['uploaded_files'], true) ?: [] as $f): ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem;">
                                        <a href="../download.php?request_id=<?php echo $req['id']; ?>&file=<?php echo urlencode(basename($f['path'])); ?>" style="color:var(--accent-orange); font-weight:700; text-decoration:none;">📥 <?php echo htmlspecialchars($f['name']); ?></a>
                                        <a href="bake_requests.php?delete_file_path=<?php echo urlencode($f['path']); ?>&request_id=<?php echo $req['id']; ?><?php echo $showArchive ? '&archive=1' : ''; ?>" class="file-del-link" onclick="return confirm('Delete this file from server?');">❌ Delete</a>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div>
                            <form method="POST">
                                <input type="hidden" name="action" value="update_request">
                                <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                
                                <div class="form-group" style="margin-bottom: 0.75rem;">
                                    <label style="color:var(--text-primary); font-size:0.9rem; font-weight:600; display: block; margin-bottom: 0.4rem;">Total Project Cost (<?php echo $currencySymbol; ?>):</label>
                                    <input type="number" step="0.01" name="total_cost" class="form-input" style="width:100%; height:42px;" value="<?php echo htmlspecialchars($req['total_cost'] ?: $req['estimated_price_inr']); ?>">
                                </div>

                                <div class="form-group" style="margin-bottom: 0.75rem;">
                                    <label style="color:var(--text-primary); font-size:0.9rem; font-weight:600; display: block; margin-bottom: 0.4rem;">Set Status:</label>
                                    <select name="status" class="form-input" style="width:100%; height:42px;">
                                        <option value="Pending" <?php echo $req['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Accepted" <?php echo $req['status'] == 'Accepted' ? 'selected' : ''; ?>>Accepted</option>
                                        <option value="Approved" <?php echo $req['status'] == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                        <option value="Completed" <?php echo $req['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="Rejected" <?php echo $req['status'] == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    </select>
                                </div>

                                <div class="form-group" style="margin-bottom: 1rem;">
                                    <label style="color:var(--text-primary); font-size:0.9rem; font-weight:600; display: block; margin-bottom: 0.4rem;">Admin Notes / Client Instructions:</label>
                                    <textarea name="admin_note" class="form-textarea" style="width:100%;" rows="4" placeholder="These notes will be visible to the client in their profile dashboard."><?php echo htmlspecialchars($req['admin_notes'] ?? ''); ?></textarea>
                                </div>

                                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                    <button type="submit" class="btn btn-primary" style="padding: 8px 18px; font-size: 0.85rem; border-radius: 6px;">Update Details</button>
                                    
                                    <?php
                                    // Generate custom WhatsApp message share link
                                    $bankDetails = site_setting('payment_bank_details');
                                    $upiNum = site_setting('payment_upi_number');
                                    $upiId = site_setting('payment_upi_id');
                                    
                                    $waMsg = "Hi " . $req['full_name'] . "! Here are your adloaf project details:\n\n" .
                                             "🍞 Project ID: #" . $req['id'] . "\n" .
                                             "🛠 Service: " . $req['service_type'] . "\n" .
                                             "📅 Deadline: " . date('M d, Y', strtotime($req['deadline'])) . "\n" .
                                             "💰 Total Cost: " . $currencySymbol . number_format($req['total_cost'] ?: $req['estimated_price_inr'], 2) . "\n" .
                                             "📝 Status: " . $req['status'] . "\n" .
                                             "📝 Note: " . ($req['admin_notes'] ?: 'No notes added yet.') . "\n\n" .
                                             "💳 Payment Details:\n" .
                                             ($upiId ? "UPI ID: " . $upiId . "\n" : "") .
                                             ($upiNum ? "UPI Number: " . $upiNum . "\n" : "") .
                                             ($bankDetails ? "Bank: " . str_replace("\r\n", " ", $bankDetails) . "\n" : "");
                                             
                                    $waShareUrl = "https://wa.me/" . preg_replace('/[^0-9]/', '', $req['whatsapp']) . "?text=" . urlencode($waMsg);
                                    ?>
                                    <a href="<?php echo $waShareUrl; ?>" target="_blank" class="wa-btn" style="padding: 8px 18px; font-size: 0.85rem; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center;">
                                        📲 Share Details to WA
                                    </a>
                                </div>
                            </form>
                        </div>
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

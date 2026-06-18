<?php
require_once 'header.php';

$success = '';
$errorMsg = '';

try {
    // Handle Generate/Regenerate Invoice
    if (isset($_GET['generate_invoice_for_project'])) {
        $projId = intval($_GET['generate_invoice_for_project']);
        $projStmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
        $projStmt->execute([$projId]);
        $projectData = $projStmt->fetch();
        if ($projectData) {
            // Check if invoice already exists for this project
            $invStmt = $pdo->prepare("SELECT id, invoice_number FROM invoices WHERE project_id = ? LIMIT 1");
            $invStmt->execute([$projId]);
            $existingInvoice = $invStmt->fetch();
            
            if ($existingInvoice) {
                // Re-generate: Update the values (amount, paid_amount, status) keeping the same invoice number
                $updInv = $pdo->prepare("UPDATE invoices SET amount = ?, paid_amount = ?, status = ? WHERE id = ?");
                $updInv->execute([$projectData['price'], $projectData['paid_amount'], $projectData['status'], $existingInvoice['id']]);
                $success = "Invoice " . $existingInvoice['invoice_number'] . " re-generated successfully.";
            } else {
                // Generate new invoice
                $count = $pdo->query("SELECT COUNT(*) FROM invoices")->fetchColumn();
                $seq = 1029 + $count;
                $invoiceNumber = "INV/" . date('dmy') . "/" . $seq;
                
                $insInv = $pdo->prepare("INSERT INTO invoices (project_id, invoice_number, amount, paid_amount, status) VALUES (?, ?, ?, ?, ?)");
                $insInv->execute([$projId, $invoiceNumber, $projectData['price'], $projectData['paid_amount'], $projectData['status']]);
                $success = "Invoice " . $invoiceNumber . " generated successfully.";
            }
        }
    }

    // Handle Delete Invoice
    if (isset($_GET['delete_invoice'])) {
        $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ?");
        $stmt->execute([$_GET['delete_invoice']]);
        $success = "Invoice deleted successfully.";
    }

    // Handle Send Invoice via Email
    if (isset($_GET['send_invoice_email'])) {
        $invId = intval($_GET['send_invoice_email']);
        $stmt = $pdo->prepare("
            SELECT i.*, p.title as project_title, p.price as total_price, p.paid_amount as project_paid, p.status as project_status, p.description as project_description,
                   c.full_name as client_name, c.email as client_email
            FROM invoices i
            JOIN projects p ON i.project_id = p.id
            JOIN users_public c ON p.client_id = c.id
            WHERE i.id = ?
        ");
        $stmt->execute([$invId]);
        $invoiceData = $stmt->fetch();
        if ($invoiceData) {
            require_once __DIR__ . '/../lib/Mailer.php';
            
            $status = $invoiceData['project_status'];
            $totalPrice = floatval($invoiceData['total_price']);
            $paidAmount = floatval($invoiceData['project_paid']);
            
            $amountRequested = 0;
            $balanceDue = 0;
            
            if ($status === 'Accepted') {
                $amountRequested = round($totalPrice * 0.35, 2);
                $balanceDue = round($totalPrice - $amountRequested, 2);
            } else {
                $amountRequested = round($totalPrice - $paidAmount, 2);
                $balanceDue = $amountRequested;
            }
            
            // Check if customized payment params were passed
            if (isset($_GET['pay_amount'])) {
                $amountRequested = floatval($_GET['pay_amount']);
            }
            $customRemarks = isset($_GET['pay_remarks']) ? trim($_GET['pay_remarks']) : '';
            $remarks = $customRemarks ?: ("Payment for " . $invoiceData['invoice_number'] . " - " . ($status === 'Accepted' ? 'Advance' : 'Balance'));
            
            $payLink = SITE_URL . "/pay.php?inv=" . urlencode($invoiceData['invoice_number']) . "&am=" . $amountRequested . "&tn=" . urlencode($remarks);
            
            $isAdvance = false;
            if (!empty($customRemarks)) {
                if (stripos($customRemarks, 'advance') !== false) {
                    $isAdvance = true;
                }
            } else {
                if ($status === 'Accepted') {
                    $isAdvance = true;
                }
            }
            
            $mailer = new Mailer();
            $mailSent = $mailer->sendInvoice(
                $invoiceData['client_email'],
                $invoiceData['client_name'],
                $invoiceData['invoice_number'],
                $invoiceData['project_title'],
                number_format($totalPrice, 2),
                number_format($amountRequested, 2),
                number_format($balanceDue, 2),
                $payLink,
                $isAdvance
            );
            
            if ($mailSent) {
                $success = "Invoice emailed to " . htmlspecialchars($invoiceData['client_email']) . " successfully.";
            } else {
                $errorMsg = "Failed to send email. Check SMTP settings.";
            }
        }
    }

    // Fetch Services for title dropdown
    $servicesListQuery = $pdo->query("SELECT title FROM services ORDER BY sort_order ASC");
    $servicesList = $servicesListQuery->fetchAll(PDO::FETCH_COLUMN);
    if (empty($servicesList)) {
        $servicesList = ['Website Design', 'Landing Pages', 'Graphic Design', 'Brand Identity', 'Social Media Creatives', 'Digital Campaigns'];
    }

    // Handle Delete Client (deletes from users_public, cascading to projects)
    if (isset($_GET['delete_client'])) {
        $stmt = $pdo->prepare("DELETE FROM users_public WHERE id = ?");
        $stmt->execute([$_GET['delete_client']]);
        $success = "Client user deleted successfully.";
    }

    // Handle Delete Project
    if (isset($_GET['delete_project'])) {
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$_GET['delete_project']]);
        $success = "Project deleted successfully.";
    }

    // Handle Add/Edit Client (saving directly to users_public)
    if (isset($_POST['action']) && $_POST['action'] == 'save_client') {
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        
        if (!empty($_POST['client_id'])) {
            $stmt = $pdo->prepare("UPDATE users_public SET full_name=?, email=?, whatsapp=? WHERE id=?");
            $stmt->execute([$name, $email, $phone, $_POST['client_id']]);
            $success = "Client user updated successfully.";
        } else {
            // Check if email already exists
            $chk = $pdo->prepare("SELECT id FROM users_public WHERE email = ?");
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $errorMsg = "A client user with this email address already exists.";
            } else {
                $randomPass = bin2hex(random_bytes(16));
                $hash = password_hash($randomPass, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users_public (full_name, email, whatsapp, password_hash) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $phone, $hash]);
                $success = "Client user registered successfully.";
            }
        }
    }

    // Handle Add/Edit Project
    if (isset($_POST['action']) && $_POST['action'] == 'save_project') {
        if (!empty($_POST['project_id'])) {
            $stmt = $pdo->prepare("UPDATE projects SET client_id=?, title=?, description=?, price=?, paid_amount=?, status=?, due_date=? WHERE id=?");
            $stmt->execute([$_POST['client_id'], $_POST['title'], $_POST['description'], $_POST['price'], $_POST['paid_amount'], $_POST['status'], $_POST['due_date'] ?: null, $_POST['project_id']]);
            $success = "Project updated.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO projects (client_id, title, description, price, paid_amount, status, due_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['client_id'], $_POST['title'], $_POST['description'], $_POST['price'], $_POST['paid_amount'], $_POST['status'], $_POST['due_date'] ?: null]);
            $success = "Project added.";
        }
    }

    // Fetch Clients (from users_public)
    $clientsQuery = $pdo->query("SELECT id, full_name as name, email, whatsapp as phone FROM users_public ORDER BY full_name ASC");
    $clients = $clientsQuery->fetchAll();

    // Fetch Projects (joining users_public)
    $projectsQuery = $pdo->query("
        SELECT p.*, c.full_name as client_name 
        FROM projects p 
        JOIN users_public c ON p.client_id = c.id 
        ORDER BY p.status DESC, p.created_at DESC
    ");
    $projects = $projectsQuery->fetchAll();

    // Fetch Invoices
    $invoicesQuery = $pdo->query("
        SELECT i.*, p.title as project_title, p.price as total_price, p.paid_amount as project_paid, p.status as project_status,
               c.full_name as client_name, c.whatsapp as client_phone
        FROM invoices i
        JOIN projects p ON i.project_id = p.id
        JOIN users_public c ON p.client_id = c.id
        ORDER BY i.created_at DESC
    ");
    $invoicesListDB = $invoicesQuery->fetchAll();
    $currencySym = site_setting('base_currency_symbol', '₹');

    // Check if editing Client
    $editClient = null;
    if (isset($_GET['edit_client'])) {
        $stmt = $pdo->prepare("SELECT id, full_name as name, email, whatsapp as phone FROM users_public WHERE id = ?");
        $stmt->execute([$_GET['edit_client']]);
        $editClient = $stmt->fetch();
    }

    // Check if editing Project
    $editProject = null;
    if (isset($_GET['edit_project'])) {
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$_GET['edit_project']]);
        $editProject = $stmt->fetch();
        
        if ($editProject && !in_array($editProject['title'], $servicesList)) {
            array_unshift($servicesList, $editProject['title']);
        }
    }

} catch (PDOException $e) {
    $errorMsg = "Database error. Please run migration and DB updates.";
    $clients = [];
    $projects = [];
}
?>

<div class="admin-header">
    <h1 class="admin-page-title">Manage Clients & Works</h1>
</div>

<?php if ($success): ?>
    <div class="success-toast"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
    <div class="error-msg" style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
        <?php echo htmlspecialchars($errorMsg); ?>
    </div>
<?php endif; ?>

<style>
    .status-badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
    .status-Pending   { background:rgba(239,68,68,0.15); color:#ef4444; }
    .status-Accepted  { background:rgba(245,158,11,0.15); color:#f59e0b; }
    .status-Approved  { background:rgba(16,185,129,0.15); color:#10b981; }
    .status-Rejected  { background:rgba(107,114,128,0.15); color:#9ca3af; }
    .status-Completed { background:rgba(99,102,241,0.15); color:#818cf8; }
</style>

<div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 2rem; margin-bottom: 2rem;">
    <!-- CLIENTS COLUMN -->
    <div>
        <div class="admin-card">
            <h3 style="color: var(--text-primary); margin-bottom: 1rem;"><?php echo $editClient ? 'Edit Client' : 'Add New Client'; ?></h3>
            <form method="POST" action="works.php">
                <input type="hidden" name="action" value="save_client">
                <?php if ($editClient): ?>
                    <input type="hidden" name="client_id" value="<?php echo $editClient['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label">Client Name</label>
                    <input type="text" name="name" class="form-input" required value="<?php echo $editClient ? htmlspecialchars($editClient['name']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" required value="<?php echo $editClient ? htmlspecialchars($editClient['email']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone / WhatsApp</label>
                    <input type="text" name="phone" class="form-input" value="<?php echo $editClient ? htmlspecialchars($editClient['phone']) : ''; ?>">
                </div>
                <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;"><?php echo $editClient ? 'Update Client' : 'Save Client'; ?></button>
                <?php if ($editClient): ?>
                    <a href="works.php" class="btn btn-secondary" style="padding: 0.5rem 1rem; margin-left: 10px;">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="admin-card">
            <h3 style="color: var(--text-primary); margin-bottom: 1rem;">Client List</h3>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($client['name']); ?></strong></td>
                        <td><small><?php echo htmlspecialchars($client['email']); ?><br><?php echo htmlspecialchars($client['phone']); ?></small></td>
                        <td class="action-links">
                            <a href="works.php?edit_client=<?php echo $client['id']; ?>">Edit</a>
                            <a href="works.php?delete_client=<?php echo $client['id']; ?>" onclick="return confirm('Delete client and all their projects?');">Del</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PROJECTS COLUMN -->
    <div>
        <div class="admin-card">
            <h3 style="color: var(--text-primary); margin-bottom: 1rem;"><?php echo $editProject ? 'Edit Project' : 'Add New Project'; ?></h3>
            <?php if (count($clients) === 0): ?>
                <p style="color: var(--text-secondary);">Please add a client first.</p>
            <?php else: ?>
            <form method="POST" action="works.php">
                <input type="hidden" name="action" value="save_project">
                <?php if ($editProject): ?>
                    <input type="hidden" name="project_id" value="<?php echo $editProject['id']; ?>">
                <?php endif; ?>
                
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Client</label>
                        <select name="client_id" class="form-input" style="height: 53px;" required>
                            <?php foreach ($clients as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo ($editProject && $editProject['client_id'] == $c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Service Type (Project Title)</label>
                        <select name="title" class="form-input" style="height: 53px;" required>
                            <?php foreach ($servicesList as $srv): ?>
                                <option value="<?php echo htmlspecialchars($srv); ?>" <?php echo ($editProject && $editProject['title'] === $srv) ? 'selected' : ''; ?>><?php echo htmlspecialchars($srv); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-input" style="height: 53px;">
                            <option value="Pending" <?php echo ($editProject && $editProject['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="Accepted" <?php echo ($editProject && $editProject['status'] == 'Accepted') ? 'selected' : ''; ?>>Accepted</option>
                            <option value="Approved" <?php echo ($editProject && $editProject['status'] == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                            <option value="Completed" <?php echo ($editProject && $editProject['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="Rejected" <?php echo ($editProject && $editProject['status'] == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-input" style="height: 53px;" value="<?php echo $editProject ? $editProject['due_date'] : ''; ?>">
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Total Quotation (<?php echo site_setting('base_currency_symbol', '₹'); ?>)</label>
                        <input type="number" step="0.01" name="price" class="form-input" required value="<?php echo $editProject ? $editProject['price'] : '0.00'; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Paid Amount (<?php echo site_setting('base_currency_symbol', '₹'); ?>)</label>
                        <input type="number" step="0.01" name="paid_amount" class="form-input" required value="<?php echo $editProject ? $editProject['paid_amount'] : '0.00'; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Project Details</label>
                    <textarea name="description" class="form-textarea" rows="2"><?php echo $editProject ? htmlspecialchars($editProject['description']) : ''; ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;"><?php echo $editProject ? 'Update Project' : 'Add Project'; ?></button>
                <?php if ($editProject): ?>
                    <a href="works.php" class="btn btn-secondary" style="padding: 0.5rem 1rem; margin-left: 10px;">Cancel</a>
                <?php endif; ?>
            </form>
            <?php endif; ?>
        </div>

        <div class="admin-card">
            <h3 style="color: var(--text-primary); margin-bottom: 1rem;">Project List</h3>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Title & Client</th>
                        <th>Status</th>
                        <th>Financials</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $project): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($project['title']); ?></strong><br>
                            <small style="color: var(--text-secondary);"><?php echo htmlspecialchars($project['client_name']); ?></small>
                        </td>
                        <td><span class="status-badge status-<?php echo $project['status']; ?>"><?php echo $project['status']; ?></span></td>
                        <td>
                            <small>
                                Total: <?php echo site_setting('base_currency_symbol', '₹') . number_format($project['price'], 2); ?><br>
                                <span style="color: <?php echo $project['paid_amount'] < $project['price'] ? '#ef4444' : '#10b981'; ?>">
                                    Paid: <?php echo site_setting('base_currency_symbol', '₹') . number_format($project['paid_amount'], 2); ?>
                                </span>
                            </small>
                        </td>
                        <td class="action-links">
                            <a href="works.php?edit_project=<?php echo $project['id']; ?>">Edit</a>
                            <a href="works.php?delete_project=<?php echo $project['id']; ?>" onclick="return confirm('Delete this project?');">Del</a>
                            <?php if (in_array($project['status'], ['Accepted', 'Approved', 'Completed'])): ?>
                                <br>
                                <?php
                                $projId = $project['id'];
                                $existingInv = $pdo->query("SELECT id, invoice_number FROM invoices WHERE project_id = $projId LIMIT 1")->fetch();
                                if ($existingInv):
                                ?>
                                    <a href="invoice_print.php?id=<?php echo $existingInv['id']; ?>" target="_blank" style="color:#10b981; font-weight:700;">INV: <?php echo htmlspecialchars($existingInv['invoice_number']); ?></a>
                                    <?php if ($project['status'] === 'Approved' || $project['status'] === 'Completed'): ?>
                                        <br><a href="works.php?generate_invoice_for_project=<?php echo $project['id']; ?>" style="color:#f59e0b;" onclick="return confirm('Re-generate invoice to match current details?');">Regen</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="works.php?generate_invoice_for_project=<?php echo $project['id']; ?>" style="color:#ea580c; font-weight:700;">Gen Invoice</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- INVOICES & PAYMENT LINKS SECTION -->
<div class="admin-card" style="margin-top: 2rem;">
    <h3 style="color: var(--text-primary); margin-bottom: 1.5rem;">🧾 Active Invoices & Custom Payment Links</h3>
    <?php if (empty($invoicesListDB)): ?>
        <p style="color: var(--text-secondary);">No invoices generated yet. Generate an invoice for accepted/approved/completed projects above.</p>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Invoice Number</th>
                        <th>Project & Client</th>
                        <th>Status</th>
                        <th>Financials</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoicesListDB as $invItem): ?>
                    <tr>
                        <td><strong style="color:var(--accent-orange);"><?php echo htmlspecialchars($invItem['invoice_number']); ?></strong></td>
                        <td>
                            <strong><?php echo htmlspecialchars($invItem['project_title']); ?></strong><br>
                            <small style="color: var(--text-secondary);"><?php echo htmlspecialchars($invItem['client_name']); ?></small>
                        </td>
                        <td><span class="status-badge status-<?php echo $invItem['project_status']; ?>"><?php echo $invItem['project_status']; ?></span></td>
                        <td>
                            <small>
                                Total: <?php echo $currencySym . number_format($invItem['total_price'], 2); ?><br>
                                Paid: <?php echo $currencySym . number_format($invItem['project_paid'], 2); ?>
                            </small>
                        </td>
                        <td class="action-links">
                            <a href="invoice_print.php?id=<?php echo $invItem['id']; ?>" target="_blank">Print View</a>
                            <a href="works.php?send_invoice_email=<?php echo $invItem['id']; ?>" onclick="return confirm('Send formatted invoice to client email?');">Email Client</a>
                            <a href="works.php?create_pay_link=<?php echo $invItem['id']; ?>#payment-generator" style="color:var(--accent-orange); font-weight:700;">Pay Link</a>
                            <a href="works.php?delete_invoice=<?php echo $invItem['id']; ?>" onclick="return confirm('Delete this invoice record?');" style="color:#ef4444;">Del</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- PAYMENT LINK GENERATOR FORM -->
<?php
$payInvoiceId = intval($_GET['create_pay_link'] ?? 0);
$payInvoice = null;
if ($payInvoiceId) {
    $payStmt = $pdo->prepare("
        SELECT i.*, p.title as project_title, p.price as total_price, p.paid_amount as project_paid, p.status as project_status,
               c.full_name as client_name, c.whatsapp as client_phone
        FROM invoices i
        JOIN projects p ON i.project_id = p.id
        JOIN users_public c ON p.client_id = c.id
        WHERE i.id = ?
    ");
    $payStmt->execute([$payInvoiceId]);
    $payInvoice = $payStmt->fetch();
}

if ($payInvoice):
    $invNum = $payInvoice['invoice_number'];
    $total = floatval($payInvoice['total_price']);
    $paid = floatval($payInvoice['project_paid']);
    $balance = $total - $paid;
    $advance = round($total * 0.35, 2);
    
    // Default selected values
    $payType = $_GET['pay_type'] ?? 'advance';
    $customAmount = $advance;
    if ($payType === 'full') {
        $customAmount = $total;
    } elseif ($payType === 'balance') {
        $customAmount = $balance;
    }
    
    $customRemarks = $_GET['pay_remarks'] ?? "Payment for {$invNum} - " . ucfirst($payType);
    
    // Build UPI links
    $generatedLink = SITE_URL . "/pay.php?inv=" . urlencode($invNum) . "&am=" . $customAmount . "&tn=" . urlencode($customRemarks);
    
    // Whatsapp text
    if ($payType === 'advance') {
        $whatsappText = "Hi *" . htmlspecialchars($payInvoice['client_name']) . "* , here is the invoice " . htmlspecialchars($invNum) . " for your project \"" . htmlspecialchars($payInvoice['project_title']) . "\". The `total project cost is " . $currencySym . number_format($total, 2) . "` and the advance payment amount is *" . $currencySym . number_format($customAmount, 2) . " (Advance)* .\n\n" .
                        "> Note: The balance payment should be paid after completed the work.\n\n" .
                        "Kindly complete the payment using this link: \n" .
                        $generatedLink . "\n\n" .
                        "-Thank you! \n" .
                        "adloaf.com";
    } else {
        $whatsappText = "Hi *" . htmlspecialchars($payInvoice['client_name']) . "* , here is the invoice " . htmlspecialchars($invNum) . " for your project \"" . htmlspecialchars($payInvoice['project_title']) . "\". The total requested amount is *" . $currencySym . number_format($customAmount, 2) . " (" . ucfirst($payType) . ")* .\n\n" .
                        "Kindly complete the payment using this link: \n" .
                        $generatedLink . "\n\n" .
                        "-Thank you! \n" .
                        "adloaf.com";
    }
    $whatsappUrl = "https://wa.me/" . preg_replace('/[^0-9]/', '', $payInvoice['client_phone']) . "?text=" . urlencode($whatsappText);
    
    // Direct Email trigger url
    $emailSendUrl = "works.php?send_invoice_email=" . $payInvoiceId . "&pay_amount=" . $customAmount . "&pay_remarks=" . urlencode($customRemarks);
?>
<div class="admin-card" id="payment-generator" style="margin-top: 2rem; border: 1.5px solid var(--accent-orange); background: rgba(234, 88, 12, 0.02);">
    <h3 style="color: var(--text-primary); margin-bottom: 1.5rem;">🔗 Custom Payment Link Generator for <?php echo htmlspecialchars($invNum); ?></h3>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <div>
            <form method="GET" action="works.php#payment-generator" id="pay-link-form">
                <input type="hidden" name="create_pay_link" value="<?php echo $payInvoiceId; ?>">
                
                <div class="form-group">
                    <label class="form-label">Payment Type</label>
                    <select name="pay_type" class="form-input" style="height: 53px;" onchange="updateGeneratedAmount(this.value)">
                        <option value="advance" <?php echo $payType === 'advance' ? 'selected' : ''; ?>>Advance Payment Request (35% - <?php echo $currencySym . number_format($advance, 2); ?>)</option>
                        <option value="full" <?php echo $payType === 'full' ? 'selected' : ''; ?>>Full Project Cost (100% - <?php echo $currencySym . number_format($total, 2); ?>)</option>
                        <option value="balance" <?php echo $payType === 'balance' ? 'selected' : ''; ?>>Remaining Balance (<?php echo $currencySym . number_format($balance, 2); ?>)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Requested Payment Amount (<?php echo $currencySym; ?>)</label>
                    <input type="number" step="0.01" name="pay_amount" id="pay-amount-input" class="form-input" readonly value="<?php echo $customAmount; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Payment Transaction Note (Remarks)</label>
                    <input type="text" name="pay_remarks" id="pay-remarks-input" class="form-input" value="<?php echo htmlspecialchars($customRemarks); ?>">
                </div>
                
                <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;">Generate Link Details</button>
                <a href="works.php" class="btn btn-secondary" style="padding: 0.5rem 1rem; margin-left: 10px;">Clear</a>
            </form>
        </div>
        
        <div>
            <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-medium); padding: 20px; border-radius: 10px;">
                <h4 style="color:var(--text-primary); margin-top:0; margin-bottom:10px;">Generated Payment Output:</h4>
                <p style="font-size:0.85rem; color:var(--text-secondary); margin-bottom:15px;">You can directly copy the link, send it via email, or share to the client's WhatsApp.</p>
                
                <div class="form-group">
                    <label class="form-label">Link Address</label>
                    <input type="text" id="generated-link-url" class="form-input" readonly value="<?php echo htmlspecialchars($generatedLink); ?>">
                </div>
                
                <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:15px;">
                    <button type="button" class="btn btn-secondary" onclick="copyGeneratedLink()" style="font-size:0.85rem; padding: 8px 16px;">📋 Copy Link</button>
                    <a href="<?php echo $whatsappUrl; ?>" target="_blank" class="btn btn-primary" style="font-size:0.85rem; padding: 8px 16px; background:#25d366; box-shadow:none; border:none;">💬 Share on WhatsApp</a>
                    <a href="<?php echo $emailSendUrl; ?>" class="btn btn-primary" style="font-size:0.85rem; padding: 8px 16px; background:#3b82f6; box-shadow:none; border:none;">📧 Email Client</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateGeneratedAmount(type) {
    const total = <?php echo $total; ?>;
    const paid = <?php echo $paid; ?>;
    const balance = total - paid;
    const advance = Math.round((total * 0.35) * 100) / 100;
    
    const amountInput = document.getElementById('pay-amount-input');
    const remarksInput = document.getElementById('pay-remarks-input');
    const invNum = "<?php echo $invNum; ?>";
    
    if (type === 'advance') {
      amountInput.value = advance;
      remarksInput.value = "Payment for " + invNum + " - Advance";
    } else if (type === 'full') {
      amountInput.value = total;
      remarksInput.value = "Payment for " + invNum + " - Full";
    } else if (type === 'balance') {
      amountInput.value = balance;
      remarksInput.value = "Payment for " + invNum + " - Balance";
    }
}

function copyGeneratedLink() {
    const copyText = document.getElementById("generated-link-url");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value);
    alert("Payment link copied to clipboard!");
}
</script>
<?php endif; ?>

<?php require_once 'footer.php'; ?>

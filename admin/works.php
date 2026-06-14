<?php
require_once 'header.php';

$success = '';
$errorMsg = '';

try {
    // Handle Delete Client
    if (isset($_GET['delete_client'])) {
        $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
        $stmt->execute([$_GET['delete_client']]);
        $success = "Client deleted successfully.";
    }

    // Handle Delete Project
    if (isset($_GET['delete_project'])) {
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$_GET['delete_project']]);
        $success = "Project deleted successfully.";
    }

    // Handle Add/Edit Client
    if (isset($_POST['action']) && $_POST['action'] == 'save_client') {
        if (!empty($_POST['client_id'])) {
            $stmt = $pdo->prepare("UPDATE clients SET name=?, email=?, phone=? WHERE id=?");
            $stmt->execute([$_POST['name'], $_POST['email'], $_POST['phone'], $_POST['client_id']]);
            $success = "Client updated.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO clients (name, email, phone) VALUES (?, ?, ?)");
            $stmt->execute([$_POST['name'], $_POST['email'], $_POST['phone']]);
            $success = "Client added.";
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

    // Fetch Clients
    $clientsQuery = $pdo->query("SELECT * FROM clients ORDER BY name ASC");
    $clients = $clientsQuery->fetchAll();

    // Fetch Projects
    $projectsQuery = $pdo->query("
        SELECT p.*, c.name as client_name 
        FROM projects p 
        JOIN clients c ON p.client_id = c.id 
        ORDER BY p.status DESC, p.created_at DESC
    ");
    $projects = $projectsQuery->fetchAll();

    // Check if editing Client
    $editClient = null;
    if (isset($_GET['edit_client'])) {
        $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$_GET['edit_client']]);
        $editClient = $stmt->fetch();
    }

    // Check if editing Project
    $editProject = null;
    if (isset($_GET['edit_project'])) {
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$_GET['edit_project']]);
        $editProject = $stmt->fetch();
    }

} catch (PDOException $e) {
    $errorMsg = "Database error. Did you run the admin_upgrades.sql script?";
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
    .status-Pending { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
    .status-Ongoing { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .status-Completed { background: rgba(16, 185, 129, 0.1); color: #10b981; }
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
                        <label class="form-label">Project Title</label>
                        <input type="text" name="title" class="form-input" required value="<?php echo $editProject ? htmlspecialchars($editProject['title']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-input" style="height: 53px;">
                            <option value="Pending" <?php echo ($editProject && $editProject['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="Ongoing" <?php echo ($editProject && $editProject['status'] == 'Ongoing') ? 'selected' : ''; ?>>Ongoing</option>
                            <option value="Completed" <?php echo ($editProject && $editProject['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
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
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

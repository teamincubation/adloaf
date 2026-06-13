<?php
require_once 'header.php';

// Calculate Analytics
try {
    // 1. Total number of clients
    $clientsQuery = $pdo->query("SELECT COUNT(*) as count FROM clients");
    $totalClients = $clientsQuery->fetch()['count'];

    // 2. Project counts by status
    $projectsQuery = $pdo->query("SELECT status, COUNT(*) as count FROM projects GROUP BY status");
    $projectStats = $projectsQuery->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $pendingTasks = $projectStats['Pending'] ?? 0;
    $ongoingWorks = $projectStats['Ongoing'] ?? 0;
    $completedWorks = $projectStats['Completed'] ?? 0;

    // 3. Financials
    $financeQuery = $pdo->query("SELECT SUM(price) as total_price, SUM(paid_amount) as total_paid FROM projects");
    $financeData = $financeQuery->fetch();
    
    $totalRevenue = $financeData['total_paid'] ?? 0;
    $pendingPayment = ($financeData['total_price'] ?? 0) - $totalRevenue;

    // Fetch 5 most recent active projects
    $recentProjectsQuery = $pdo->query("
        SELECT p.*, c.name as client_name 
        FROM projects p 
        JOIN clients c ON p.client_id = c.id 
        WHERE p.status != 'Completed'
        ORDER BY p.due_date ASC, p.created_at DESC 
        LIMIT 5
    ");
    $recentProjects = $recentProjectsQuery->fetchAll();

} catch (PDOException $e) {
    // Fallback if tables don't exist yet
    $totalClients = $pendingTasks = $ongoingWorks = $completedWorks = 0;
    $totalRevenue = $pendingPayment = 0;
    $recentProjects = [];
    $error = "Please run admin_upgrades.sql in your database to enable the Dashboard.";
}
?>

<div class="admin-header">
    <h1 class="admin-page-title">Dashboard Overview</h1>
</div>

<?php if (isset($error)): ?>
    <div class="error-msg" style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<style>
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    .kpi-card {
        background-color: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
    }
    .kpi-title {
        color: var(--text-secondary);
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }
    .kpi-value {
        color: var(--text-primary);
        font-size: 2rem;
        font-weight: 700;
    }
    .kpi-value.revenue { color: #10b981; }
    .kpi-value.pending-payment { color: #ef4444; }
    
    .status-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: bold;
    }
    .status-Pending { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
    .status-Ongoing { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .status-Completed { background: rgba(16, 185, 129, 0.1); color: #10b981; }
</style>

<div class="kpi-grid">
    <div class="kpi-card">
        <span class="kpi-title">Total Revenue</span>
        <span class="kpi-value revenue">$<?php echo number_format($totalRevenue, 2); ?></span>
    </div>
    <div class="kpi-card">
        <span class="kpi-title">Pending Payment</span>
        <span class="kpi-value pending-payment">$<?php echo number_format($pendingPayment, 2); ?></span>
    </div>
    <div class="kpi-card">
        <span class="kpi-title">Total Clients</span>
        <span class="kpi-value"><?php echo $totalClients; ?></span>
    </div>
    <div class="kpi-card">
        <span class="kpi-title">Works Done</span>
        <span class="kpi-value"><?php echo $completedWorks; ?></span>
    </div>
    <div class="kpi-card">
        <span class="kpi-title">Ongoing Works</span>
        <span class="kpi-value"><?php echo $ongoingWorks; ?></span>
    </div>
    <div class="kpi-card">
        <span class="kpi-title">Pending Tasks</span>
        <span class="kpi-value"><?php echo $pendingTasks; ?></span>
    </div>
</div>

<div class="admin-card">
    <h3 style="color: var(--text-primary); margin-bottom: 1rem;">Recent Active Works</h3>
    <?php if (count($recentProjects) > 0): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Client</th>
                    <th>Due Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentProjects as $p): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($p['title']); ?></strong></td>
                    <td><?php echo htmlspecialchars($p['client_name']); ?></td>
                    <td><?php echo $p['due_date'] ? date('M d, Y', strtotime($p['due_date'])) : 'No deadline'; ?></td>
                    <td><span class="status-badge status-<?php echo $p['status']; ?>"><?php echo $p['status']; ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="color: var(--text-secondary); text-align: center; padding: 1rem;">No active projects right now. Time to bake!</p>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>

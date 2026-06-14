<?php
require_once 'header.php';

// Pagination variables
$limit = 50;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Fetch total count of visitors
$countQuery = $pdo->query("SELECT COUNT(*) FROM visitors");
$totalVisitors = $countQuery->fetchColumn();
$totalPages = ceil($totalVisitors / $limit);

// Fetch visitors
$stmt = $pdo->prepare("SELECT * FROM visitors ORDER BY visited_at DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$visitors = $stmt->fetchAll();
?>

<div class="admin-header">
    <h1 class="admin-page-title">🌐 Site Visitors History</h1>
</div>

<div class="admin-card">
    <h3 style="color:var(--text-primary); margin-bottom: 1rem;">Total Visitors Tracked: <?php echo number_format($totalVisitors); ?></h3>
    <p style="color:var(--text-secondary); font-size:0.85rem; margin-bottom:1.5rem;">Below is the real-time record of all traffic landing on the public website pages.</p>
    
    <div style="overflow-x:auto;">
    <table class="admin-table">
        <thead>
            <tr>
                <th>IP Address</th>
                <th>Location Details</th>
                <th>ISP / Provider</th>
                <th>Page Visited</th>
                <th>Referrer</th>
                <th>Visited At</th>
                <th>User Agent</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($visitors)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-secondary);">No visitors tracked yet.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($visitors as $v): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($v['ip_address']); ?></strong></td>
                    <td>
                        <small>
                            🌍 <?php echo htmlspecialchars($v['country'] ?: 'Unknown Country'); ?> 
                            <?php if ($v['country_code']): ?> (<?php echo htmlspecialchars($v['country_code']); ?>)<?php endif; ?><br>
                            🏙 <?php echo htmlspecialchars($v['city'] ?: 'Unknown City'); ?><br>
                            🕒 <?php echo htmlspecialchars($v['timezone'] ?: 'Unknown Timezone'); ?>
                        </small>
                    </td>
                    <td><small style="color: var(--text-secondary);"><?php echo htmlspecialchars($v['isp'] ?: 'N/A'); ?></small></td>
                    <td><code style="background: rgba(234, 88, 12, 0.05); padding: 2px 6px; border-radius: 4px; color: var(--accent-orange); font-size: 0.85rem;"><?php echo htmlspecialchars($v['page_visited']); ?></code></td>
                    <td><small style="word-break: break-all; color: var(--text-secondary);"><?php echo htmlspecialchars($v['referrer'] ?: 'Direct / Search'); ?></small></td>
                    <td><small style="font-weight: 600;"><?php echo date('M d, Y H:i:s', strtotime($v['visited_at'])); ?></small></td>
                    <td>
                        <small title="<?php echo htmlspecialchars($v['user_agent']); ?>" style="display: inline-block; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; cursor: help; border-bottom: 1px dotted var(--text-secondary);">
                            <?php echo htmlspecialchars($v['user_agent']); ?>
                        </small>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div style="margin-top: 1.5rem; display: flex; justify-content: center; gap: 5px;">
        <?php if ($page > 1): ?>
            <a href="visitors.php?page=<?php echo $page - 1; ?>" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.85rem; text-decoration: none;">&laquo; Prev</a>
        <?php endif; ?>
        
        <?php
        $startPage = max(1, $page - 3);
        $endPage = min($totalPages, $page + 3);
        for ($i = $startPage; $i <= $endPage; $i++):
        ?>
            <a href="visitors.php?page=<?php echo $i; ?>" class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?>" style="padding: 4px 10px; font-size: 0.85rem; text-decoration: none;"><?php echo $i; ?></a>
        <?php endfor; ?>
        
        <?php if ($page < $totalPages): ?>
            <a href="visitors.php?page=<?php echo $page + 1; ?>" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.85rem; text-decoration: none;">Next &raquo;</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php
require_once 'footer.php';
?>

<?php
require_once 'header.php';

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $success = "Message deleted successfully.";
}

// Fetch all messages
$query = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
$messages = $query->fetchAll();

?>

<div class="admin-header">
    <h1 class="admin-page-title">Contact Messages</h1>
</div>

<?php if (isset($success)): ?>
    <div class="success-toast"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="admin-card">
    <?php if (count($messages) > 0): ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Name</th>
                <th>Email</th>
                <th>Service</th>
                <th>Subject & Message</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($messages as $msg): ?>
            <tr>
                <td style="white-space: nowrap;"><?php echo date('M d, Y H:i', strtotime($msg['created_at'])); ?></td>
                <td><?php echo htmlspecialchars($msg['name']); ?></td>
                <td><a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>" style="color: var(--primary-color);"><?php echo htmlspecialchars($msg['email']); ?></a></td>
                <td><span style="background: rgba(234, 88, 12, 0.1); color: var(--primary-color); padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold;"><?php echo htmlspecialchars($msg['service']); ?></span></td>
                <td>
                    <strong><?php echo htmlspecialchars($msg['subject']); ?></strong><br>
                    <small style="color: var(--text-secondary);"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></small>
                </td>
                <td class="action-links">
                    <a href="messages.php?delete=<?php echo $msg['id']; ?>" onclick="return confirm('Delete this message?');">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p style="color: var(--text-secondary); text-align: center; padding: 2rem;">No messages in the inbox yet.</p>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>

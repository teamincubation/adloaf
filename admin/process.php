<?php
require_once 'header.php';

$success = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM process_steps WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $success = "Process step deleted successfully.";
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step_number = $_POST['step_number'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $icon_svg = $_POST['icon_svg'];
    $sort_order = $_POST['sort_order'] ?: 0;

    if (!empty($_POST['id'])) {
        // Edit
        $stmt = $pdo->prepare("UPDATE process_steps SET step_number=?, title=?, description=?, icon_svg=?, sort_order=? WHERE id=?");
        $stmt->execute([$step_number, $title, $description, $icon_svg, $sort_order, $_POST['id']]);
        $success = "Process step updated successfully.";
    } else {
        // Add
        $stmt = $pdo->prepare("INSERT INTO process_steps (step_number, title, description, icon_svg, sort_order) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$step_number, $title, $description, $icon_svg, $sort_order]);
        $success = "Process step added successfully.";
    }
}

// Fetch all
$query = $pdo->query("SELECT * FROM process_steps ORDER BY sort_order ASC, id ASC");
$items = $query->fetchAll();

// Check if editing
$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM process_steps WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editItem = $stmt->fetch();
}
?>

<div class="admin-header">
    <h1 class="admin-page-title">Manage Process Steps</h1>
</div>

<?php if ($success): ?>
    <div class="success-toast"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="admin-card">
    <h3 style="color: var(--text-primary); margin-bottom: 1rem;"><?php echo $editItem ? 'Edit Process Step' : 'Add Process Step'; ?></h3>
    <form method="POST" action="process.php">
        <?php if ($editItem): ?>
            <input type="hidden" name="id" value="<?php echo $editItem['id']; ?>">
        <?php endif; ?>
        
        <div class="form-row-2">
            <div class="form-group">
                <label class="form-label">Step Number (e.g., 01)</label>
                <input type="text" name="step_number" class="form-input" required value="<?php echo $editItem ? htmlspecialchars($editItem['step_number']) : ''; ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-input" required value="<?php echo $editItem ? htmlspecialchars($editItem['title']) : ''; ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-textarea" rows="3" required><?php echo $editItem ? htmlspecialchars($editItem['description']) : ''; ?></textarea>
        </div>

        <div class="form-group">
            <label class="form-label">Icon SVG (Inner path/circle only)</label>
            <textarea name="icon_svg" class="form-textarea" rows="2"><?php echo $editItem ? htmlspecialchars($editItem['icon_svg']) : ''; ?></textarea>
        </div>
        
        <div class="form-group">
            <label class="form-label">Sort Order</label>
            <input type="number" name="sort_order" class="form-input" value="<?php echo $editItem ? $editItem['sort_order'] : '0'; ?>">
        </div>

        <button type="submit" class="btn btn-primary"><?php echo $editItem ? 'Update Step' : 'Add Step'; ?></button>
        <?php if ($editItem): ?>
            <a href="process.php" class="btn btn-secondary" style="margin-left: 10px;">Cancel</a>
        <?php endif; ?>
    </form>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Step</th>
                <th>Title</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['step_number']); ?></td>
                <td><?php echo htmlspecialchars($item['title']); ?></td>
                <td><?php echo htmlspecialchars(substr($item['description'], 0, 50)) . '...'; ?></td>
                <td class="action-links">
                    <a href="process.php?edit=<?php echo $item['id']; ?>">Edit</a>
                    <a href="process.php?delete=<?php echo $item['id']; ?>" onclick="return confirm('Are you sure?');">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'footer.php'; ?>

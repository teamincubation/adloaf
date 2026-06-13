<?php
require_once 'header.php';

$success = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $success = "Service deleted successfully.";
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $icon_svg = $_POST['icon_svg'];
    $link_text = $_POST['link_text'];
    $sort_order = $_POST['sort_order'] ?: 0;

    if (!empty($_POST['id'])) {
        // Edit
        $stmt = $pdo->prepare("UPDATE services SET title=?, description=?, icon_svg=?, link_text=?, sort_order=? WHERE id=?");
        $stmt->execute([$title, $description, $icon_svg, $link_text, $sort_order, $_POST['id']]);
        $success = "Service updated successfully.";
    } else {
        // Add
        $stmt = $pdo->prepare("INSERT INTO services (title, description, icon_svg, link_text, sort_order) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $icon_svg, $link_text, $sort_order]);
        $success = "Service added successfully.";
    }
}

// Fetch all
$query = $pdo->query("SELECT * FROM services ORDER BY sort_order ASC, id ASC");
$services = $query->fetchAll();

// Check if editing
$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editItem = $stmt->fetch();
}
?>

<div class="admin-header">
    <h1 class="admin-page-title">Manage Services</h1>
</div>

<?php if ($success): ?>
    <div class="success-toast"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="admin-card">
    <h3 style="color: var(--text-primary); margin-bottom: 1rem;"><?php echo $editItem ? 'Edit Service' : 'Add New Service'; ?></h3>
    <form method="POST" action="services.php">
        <?php if ($editItem): ?>
            <input type="hidden" name="id" value="<?php echo $editItem['id']; ?>">
        <?php endif; ?>
        
        <div class="form-row-2">
            <div class="form-group">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-input" required value="<?php echo $editItem ? htmlspecialchars($editItem['title']) : ''; ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Link Text</label>
                <input type="text" name="link_text" class="form-input" value="<?php echo $editItem ? htmlspecialchars($editItem['link_text']) : 'Order Now'; ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-textarea" rows="3" required><?php echo $editItem ? htmlspecialchars($editItem['description']) : ''; ?></textarea>
        </div>

        <div class="form-group">
            <label class="form-label">Icon SVG (Inner path/rect only)</label>
            <textarea name="icon_svg" class="form-textarea" rows="2"><?php echo $editItem ? htmlspecialchars($editItem['icon_svg']) : ''; ?></textarea>
        </div>
        
        <div class="form-group">
            <label class="form-label">Sort Order</label>
            <input type="number" name="sort_order" class="form-input" value="<?php echo $editItem ? $editItem['sort_order'] : '0'; ?>">
        </div>

        <button type="submit" class="btn btn-primary"><?php echo $editItem ? 'Update Service' : 'Add Service'; ?></button>
        <?php if ($editItem): ?>
            <a href="services.php" class="btn btn-secondary" style="margin-left: 10px;">Cancel</a>
        <?php endif; ?>
    </form>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Order</th>
                <th>Title</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($services as $service): ?>
            <tr>
                <td><?php echo $service['sort_order']; ?></td>
                <td><?php echo htmlspecialchars($service['title']); ?></td>
                <td><?php echo htmlspecialchars(substr($service['description'], 0, 50)) . '...'; ?></td>
                <td class="action-links">
                    <a href="services.php?edit=<?php echo $service['id']; ?>">Edit</a>
                    <a href="services.php?delete=<?php echo $service['id']; ?>" onclick="return confirm('Are you sure?');">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'footer.php'; ?>

<?php
require_once 'header.php';

$success = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM portfolio_items WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $success = "Portfolio item deleted successfully.";
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = $_POST['category'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $image_path = $_POST['image_path'];
    $sort_order = $_POST['sort_order'] ?: 0;

    if (!empty($_POST['id'])) {
        // Edit
        $stmt = $pdo->prepare("UPDATE portfolio_items SET category=?, title=?, description=?, image_path=?, sort_order=? WHERE id=?");
        $stmt->execute([$category, $title, $description, $image_path, $sort_order, $_POST['id']]);
        $success = "Portfolio item updated successfully.";
    } else {
        // Add
        $stmt = $pdo->prepare("INSERT INTO portfolio_items (category, title, description, image_path, sort_order) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$category, $title, $description, $image_path, $sort_order]);
        $success = "Portfolio item added successfully.";
    }
}

// Fetch all
$query = $pdo->query("SELECT * FROM portfolio_items ORDER BY sort_order ASC, id ASC");
$items = $query->fetchAll();

// Check if editing
$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM portfolio_items WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editItem = $stmt->fetch();
}
?>

<div class="admin-header">
    <h1 class="admin-page-title">Manage Portfolio Works</h1>
</div>

<?php if ($success): ?>
    <div class="success-toast"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="admin-card">
    <h3 style="color: var(--text-primary); margin-bottom: 1rem;"><?php echo $editItem ? 'Edit Portfolio Item' : 'Add New Portfolio Item'; ?></h3>
    <form method="POST" action="portfolio.php">
        <?php if ($editItem): ?>
            <input type="hidden" name="id" value="<?php echo $editItem['id']; ?>">
        <?php endif; ?>
        
        <div class="form-row-2">
            <div class="form-group">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-input" required value="<?php echo $editItem ? htmlspecialchars($editItem['title']) : ''; ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Category</label>
                <select name="category" class="form-input" style="height: 53px;" required>
                    <option value="websites" <?php echo ($editItem && $editItem['category'] == 'websites') ? 'selected' : ''; ?>>Websites</option>
                    <option value="posters" <?php echo ($editItem && $editItem['category'] == 'posters') ? 'selected' : ''; ?>>Posters</option>
                    <option value="branding" <?php echo ($editItem && $editItem['category'] == 'branding') ? 'selected' : ''; ?>>Branding</option>
                    <option value="social" <?php echo ($editItem && $editItem['category'] == 'social') ? 'selected' : ''; ?>>Social Media</option>
                    <option value="uiconcepts" <?php echo ($editItem && $editItem['category'] == 'uiconcepts') ? 'selected' : ''; ?>>UI Concepts</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-textarea" rows="3" required><?php echo $editItem ? htmlspecialchars($editItem['description']) : ''; ?></textarea>
        </div>

        <div class="form-group">
            <label class="form-label">Image Path (e.g., assets/portfolio_websites.png)</label>
            <input type="text" name="image_path" class="form-input" required value="<?php echo $editItem ? htmlspecialchars($editItem['image_path']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">Sort Order</label>
            <input type="number" name="sort_order" class="form-input" value="<?php echo $editItem ? $editItem['sort_order'] : '0'; ?>">
        </div>

        <button type="submit" class="btn btn-primary"><?php echo $editItem ? 'Update Portfolio Item' : 'Add Portfolio Item'; ?></button>
        <?php if ($editItem): ?>
            <a href="portfolio.php" class="btn btn-secondary" style="margin-left: 10px;">Cancel</a>
        <?php endif; ?>
    </form>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Title</th>
                <th>Category</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><img src="../<?php echo htmlspecialchars($item['image_path']); ?>" alt="preview" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;"></td>
                <td><?php echo htmlspecialchars($item['title']); ?></td>
                <td><?php echo htmlspecialchars($item['category']); ?></td>
                <td class="action-links">
                    <a href="portfolio.php?edit=<?php echo $item['id']; ?>">Edit</a>
                    <a href="portfolio.php?delete=<?php echo $item['id']; ?>" onclick="return confirm('Are you sure?');">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'footer.php'; ?>

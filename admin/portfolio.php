<?php
require_once 'header.php';

$success = '';
$errorMsg = '';
$uploadDir = '../assets/uploads/';

// Ensure upload directory exists
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Handle Delete
if (isset($_GET['delete'])) {
    // Optionally delete the file as well
    $stmt = $pdo->prepare("SELECT image_path FROM portfolio_items WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $item = $stmt->fetch();
    if ($item && file_exists('../' . $item['image_path']) && strpos($item['image_path'], 'uploads/') !== false) {
        unlink('../' . $item['image_path']);
    }

    $stmt = $pdo->prepare("DELETE FROM portfolio_items WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $success = "Portfolio item deleted successfully.";
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = $_POST['category'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $sort_order = $_POST['sort_order'] ?: 0;
    
    // File upload logic
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        if (in_array($fileExtension, $allowedExts)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $destPath = $uploadDir . $newFileName;
            
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $image_path = 'assets/uploads/' . $newFileName;
            } else {
                $errorMsg = "Error moving uploaded file. Check directory permissions.";
            }
        } else {
            $errorMsg = "Invalid file extension.";
        }
    }

    if (!$errorMsg) {
        if (!empty($_POST['id'])) {
            // Edit
            if ($image_path) {
                $stmt = $pdo->prepare("UPDATE portfolio_items SET category=?, title=?, description=?, image_path=?, sort_order=? WHERE id=?");
                $stmt->execute([$category, $title, $description, $image_path, $sort_order, $_POST['id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE portfolio_items SET category=?, title=?, description=?, sort_order=? WHERE id=?");
                $stmt->execute([$category, $title, $description, $sort_order, $_POST['id']]);
            }
            $success = "Portfolio item updated successfully.";
        } else {
            // Add
            if (!$image_path) {
                // Try to use fallback text input if provided (for backwards compatibility/easy default testing)
                $image_path = $_POST['existing_image_path'] ?? 'assets/portfolio_websites.png';
            }
            
            $stmt = $pdo->prepare("INSERT INTO portfolio_items (category, title, description, image_path, sort_order) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$category, $title, $description, $image_path, $sort_order]);
            $success = "Portfolio item added successfully.";
        }
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
<?php if ($errorMsg): ?>
    <div class="error-msg" style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
        <?php echo htmlspecialchars($errorMsg); ?>
    </div>
<?php endif; ?>

<div class="admin-card">
    <h3 style="color: var(--text-primary); margin-bottom: 1rem;"><?php echo $editItem ? 'Edit Portfolio Item' : 'Add New Portfolio Item'; ?></h3>
    <form method="POST" action="portfolio.php" enctype="multipart/form-data">
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

        <div class="form-row-2">
            <div class="form-group">
                <label class="form-label">Upload Image</label>
                <input type="file" name="image" class="form-input" accept="image/*" style="padding-top: 13px;">
                <?php if ($editItem): ?>
                    <small style="color: var(--text-secondary); display: block; margin-top: 5px;">Leave empty to keep current image: <?php echo htmlspecialchars($editItem['image_path']); ?></small>
                <?php else: ?>
                    <small style="color: var(--text-secondary); display: block; margin-top: 5px;">Supported formats: JPG, PNG, WEBP, SVG</small>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order" class="form-input" value="<?php echo $editItem ? $editItem['sort_order'] : '0'; ?>">
            </div>
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

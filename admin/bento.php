<?php
require_once 'header.php';

$success = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM bento_cards WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $success = "Bento card deleted successfully.";
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $icon_svg = $_POST['icon_svg'];
    $card_class = $_POST['card_class'];
    $stat_num = $_POST['stat_num'];
    $stat_label = $_POST['stat_label'];
    $sort_order = $_POST['sort_order'] ?: 0;

    if (!empty($_POST['id'])) {
        // Edit
        $stmt = $pdo->prepare("UPDATE bento_cards SET title=?, description=?, icon_svg=?, card_class=?, stat_num=?, stat_label=?, sort_order=? WHERE id=?");
        $stmt->execute([$title, $description, $icon_svg, $card_class, $stat_num, $stat_label, $sort_order, $_POST['id']]);
        $success = "Bento card updated successfully.";
    } else {
        // Add
        $stmt = $pdo->prepare("INSERT INTO bento_cards (title, description, icon_svg, card_class, stat_num, stat_label, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $icon_svg, $card_class, $stat_num, $stat_label, $sort_order]);
        $success = "Bento card added successfully.";
    }
}

// Fetch all
$query = $pdo->query("SELECT * FROM bento_cards ORDER BY sort_order ASC, id ASC");
$items = $query->fetchAll();

// Check if editing
$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM bento_cards WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editItem = $stmt->fetch();
}
?>

<div class="admin-header">
    <h1 class="admin-page-title">Manage Bento Grid</h1>
</div>

<?php if ($success): ?>
    <div class="success-toast"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="admin-card">
    <h3 style="color: var(--text-primary); margin-bottom: 1rem;"><?php echo $editItem ? 'Edit Bento Card' : 'Add Bento Card'; ?></h3>
    <form method="POST" action="bento.php">
        <?php if ($editItem): ?>
            <input type="hidden" name="id" value="<?php echo $editItem['id']; ?>">
        <?php endif; ?>
        
        <div class="form-row-2">
            <div class="form-group">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-input" required value="<?php echo $editItem ? htmlspecialchars($editItem['title']) : ''; ?>">
            </div>
            <div class="form-group">
                <label class="form-label">CSS Class (e.g., bento-col-2 bento-row-2 bento-item-dark)</label>
                <input type="text" name="card_class" class="form-input" value="<?php echo $editItem ? htmlspecialchars($editItem['card_class']) : ''; ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-textarea" rows="3" required><?php echo $editItem ? htmlspecialchars($editItem['description']) : ''; ?></textarea>
        </div>

        <div class="form-row-2">
            <div class="form-group">
                <label class="form-label">Stat Number (Optional)</label>
                <input type="text" name="stat_num" class="form-input" value="<?php echo $editItem ? htmlspecialchars($editItem['stat_num']) : ''; ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Stat Label (Optional)</label>
                <input type="text" name="stat_label" class="form-input" value="<?php echo $editItem ? htmlspecialchars($editItem['stat_label']) : ''; ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Icon SVG (Inner path only)</label>
            <textarea name="icon_svg" class="form-textarea" rows="2"><?php echo $editItem ? htmlspecialchars($editItem['icon_svg']) : ''; ?></textarea>
        </div>
        
        <div class="form-group">
            <label class="form-label">Sort Order</label>
            <input type="number" name="sort_order" class="form-input" value="<?php echo $editItem ? $editItem['sort_order'] : '0'; ?>">
        </div>

        <button type="submit" class="btn btn-primary"><?php echo $editItem ? 'Update Card' : 'Add Card'; ?></button>
        <?php if ($editItem): ?>
            <a href="bento.php" class="btn btn-secondary" style="margin-left: 10px;">Cancel</a>
        <?php endif; ?>
    </form>
</div>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Classes</th>
                <th>Stats</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['title']); ?></td>
                <td><small><?php echo htmlspecialchars($item['card_class']); ?></small></td>
                <td><?php echo htmlspecialchars($item['stat_num']); ?> <?php echo htmlspecialchars($item['stat_label']); ?></td>
                <td class="action-links">
                    <a href="bento.php?edit=<?php echo $item['id']; ?>">Edit</a>
                    <a href="bento.php?delete=<?php echo $item['id']; ?>" onclick="return confirm('Are you sure?');">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'footer.php'; ?>

<?php
require_once 'header.php';

$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
    foreach ($_POST as $key => $value) {
        $stmt->execute([$value, $key]);
    }
    $success = true;
}

$query = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$settings = $query->fetchAll(PDO::FETCH_KEY_PAIR);

function get_setting($key, $settings) {
    return htmlspecialchars($settings[$key] ?? '');
}
?>

<div class="admin-header">
    <h1 class="admin-page-title">Site Settings</h1>
</div>

<?php if ($success): ?>
    <div class="success-toast">Settings saved successfully!</div>
<?php endif; ?>

<div class="admin-card">
    <form method="POST" action="settings.php">
        <h3 style="color: var(--text-primary); margin-bottom: 1rem;">Hero Section</h3>
        <div class="form-group">
            <label class="form-label">Hero Title (HTML allowed)</label>
            <input type="text" name="hero_title" class="form-input" value="<?php echo get_setting('hero_title', $settings); ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Hero Description</label>
            <textarea name="hero_desc" class="form-textarea" rows="3"><?php echo get_setting('hero_desc', $settings); ?></textarea>
        </div>

        <h3 style="color: var(--text-primary); margin-top: 2rem; margin-bottom: 1rem;">About Section</h3>
        <div class="form-group">
            <label class="form-label">About Title</label>
            <input type="text" name="about_title" class="form-input" value="<?php echo get_setting('about_title', $settings); ?>">
        </div>
        <div class="form-group">
            <label class="form-label">About Description (HTML allowed)</label>
            <textarea name="about_desc" class="form-textarea" rows="5"><?php echo get_setting('about_desc', $settings); ?></textarea>
        </div>

        <h3 style="color: var(--text-primary); margin-top: 2rem; margin-bottom: 1rem;">Contact & Social Links</h3>
        <div class="form-row-2">
            <div class="form-group">
                <label class="form-label">Contact Email</label>
                <input type="email" name="contact_email" class="form-input" value="<?php echo get_setting('contact_email', $settings); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">WhatsApp Link</label>
                <input type="text" name="contact_whatsapp" class="form-input" value="<?php echo get_setting('contact_whatsapp', $settings); ?>">
            </div>
        </div>
        
        <div class="form-row-2">
            <div class="form-group">
                <label class="form-label">Dribbble Link</label>
                <input type="text" name="social_dribbble" class="form-input" value="<?php echo get_setting('social_dribbble', $settings); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Behance Link</label>
                <input type="text" name="social_behance" class="form-input" value="<?php echo get_setting('social_behance', $settings); ?>">
            </div>
        </div>
        
        <div class="form-row-2">
            <div class="form-group">
                <label class="form-label">LinkedIn Link</label>
                <input type="text" name="social_linkedin" class="form-input" value="<?php echo get_setting('social_linkedin', $settings); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Instagram Link</label>
                <input type="text" name="social_instagram" class="form-input" value="<?php echo get_setting('social_instagram', $settings); ?>">
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
</div>

<?php require_once 'footer.php'; ?>

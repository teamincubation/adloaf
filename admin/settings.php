<?php
require_once 'header.php';

$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Site settings text inputs
    $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?");
    $skip = ['csrf_token','stats_clients','stats_projects','stats_years'];
    foreach ($_POST as $key => $value) {
        if (in_array($key, $skip)) continue;
        $stmt->execute([$key, $value, $value]);
    }

    // Handle QR code file upload
    if (isset($_FILES['payment_qr']) && $_FILES['payment_qr']['error'] === UPLOAD_ERR_OK) {
        $dest = __DIR__ . '/../assets/uploads/payment_qr.png';
        if (!is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0755, true);
        }
        if (move_uploaded_file($_FILES['payment_qr']['tmp_name'], $dest)) {
            $qrPath = 'assets/uploads/payment_qr.png';
            $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('payment_qr', ?) ON DUPLICATE KEY UPDATE setting_value=?")
                ->execute([$qrPath, $qrPath]);
        }
    }

    // Stats
    if (isset($_POST['stats_clients'])) {
        $pdo->prepare("INSERT INTO site_stats (stat_key,stat_value) VALUES ('total_clients',?) ON DUPLICATE KEY UPDATE stat_value=?")
            ->execute([$_POST['stats_clients'], $_POST['stats_clients']]);
        $pdo->prepare("INSERT INTO site_stats (stat_key,stat_value) VALUES ('completed_projects',?) ON DUPLICATE KEY UPDATE stat_value=?")
            ->execute([$_POST['stats_projects'] ?? 0, $_POST['stats_projects'] ?? 0]);
        $pdo->prepare("INSERT INTO site_stats (stat_key,stat_value) VALUES ('active_years',?) ON DUPLICATE KEY UPDATE stat_value=?")
            ->execute([$_POST['stats_years'] ?? 1, $_POST['stats_years'] ?? 1]);
    }

    $success = true;
}

$query    = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$settings = $query->fetchAll(PDO::FETCH_KEY_PAIR);

// Fetch current stats
try {
    $statsRaw = $pdo->query("SELECT stat_key, stat_value FROM site_stats")->fetchAll(PDO::FETCH_KEY_PAIR);
} catch(PDOException $e) { $statsRaw = []; }

function gs($key, $settings) {
    return htmlspecialchars($settings[$key] ?? '');
}
?>

<div class="admin-header">
    <h1 class="admin-page-title">Site Settings</h1>
</div>

<?php if ($success): ?>
    <div class="success-toast">All settings saved successfully!</div>
<?php endif; ?>

<form method="POST" action="settings.php" enctype="multipart/form-data">
<!-- Currency & Pricing -->
<div class="admin-card">
    <h3 style="color:var(--text-primary);margin-bottom:1rem;">💱 Global Currency</h3>
    <div class="form-row-2">
        <div class="form-group">
            <label class="form-label">Base Currency Code (default: INR)</label>
            <select name="base_currency" class="form-input" style="height:53px;" onchange="updateCurrencySymbol(this.value)">
                <?php
                $currencies = ['INR'=>'₹ Indian Rupee','USD'=>'$ US Dollar','EUR'=>'€ Euro','GBP'=>'£ Pound Sterling','AED'=>'AED UAE Dirham','SAR'=>'SAR Saudi Riyal','MYR'=>'RM Malaysian Ringgit'];
                $currentCurrency = $settings['base_currency'] ?? 'INR';
                foreach ($currencies as $code => $label):
                ?>
                <option value="<?php echo $code; ?>" <?php echo $currentCurrency == $code ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Admin WhatsApp Number (with country code)</label>
            <input type="text" name="whatsapp_admin" class="form-input" value="<?php echo gs('whatsapp_admin', $settings); ?>" placeholder="916282563209">
        </div>
    </div>
    <input type="hidden" name="base_currency_symbol" id="base_currency_symbol" value="<?php echo gs('base_currency_symbol', $settings) ?: '₹'; ?>">
</div>

<!-- Payment Details -->
<div class="admin-card">
    <h3 style="color:var(--text-primary);margin-bottom:1rem;">💰 Payment Details</h3>
    <p style="color:var(--text-secondary);font-size:0.85rem;margin-bottom:1rem;">Configure the payment methods clients can use. These details will be shared on their dashboards and during WhatsApp sharing.</p>
    <div class="form-group">
        <label class="form-label">Bank Details (Bank, Account Name, Account No, IFSC, etc.)</label>
        <textarea name="payment_bank_details" class="form-textarea" rows="3" placeholder="Bank Name: ... Account No: ... IFSC: ..."><?php echo gs('payment_bank_details', $settings); ?></textarea>
    </div>
    <div class="form-row-2">
        <div class="form-group">
            <label class="form-label">UPI Number</label>
            <input type="text" name="payment_upi_number" class="form-input" value="<?php echo gs('payment_upi_number', $settings); ?>" placeholder="e.g. 9876543210@upi">
        </div>
        <div class="form-group">
            <label class="form-label">UPI ID</label>
            <input type="text" name="payment_upi_id" class="form-input" value="<?php echo gs('payment_upi_id', $settings); ?>" placeholder="e.g. adloaf@upi">
        </div>
    </div>
    <div class="form-group" style="margin-top: 1rem;">
        <label class="form-label">Payment QR Code Image</label>
        <input type="file" name="payment_qr" class="form-input" style="padding: 0.5rem; height: auto;">
        <?php if (!empty($settings['payment_qr'])): ?>
            <div style="margin-top: 0.5rem;">
                <span style="font-size: 0.85rem; color: var(--text-secondary);">Current QR Code:</span><br>
                <img src="../<?php echo htmlspecialchars($settings['payment_qr']); ?>?v=<?php echo time(); ?>" alt="QR Code" style="max-width: 150px; border: 1px solid var(--border-medium); border-radius: 8px; margin-top: 4px;">
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Google Credentials -->
<div class="admin-card">
    <h3 style="color:var(--text-primary);margin-bottom:1rem;">🔑 Google OAuth Credentials</h3>
    <p style="color:var(--text-secondary);font-size:0.85rem;margin-bottom:1rem;">Configure client credentials to enable real Google Sign-In. If empty, the Google simulator will be active.</p>
    <div class="form-row-2">
        <div class="form-group">
            <label class="form-label">Google Client ID</label>
            <input type="text" name="google_client_id" class="form-input" value="<?php echo gs('google_client_id', $settings); ?>" placeholder="client-id.apps.googleusercontent.com">
        </div>
        <div class="form-group">
            <label class="form-label">Google Client Secret</label>
            <input type="text" name="google_client_secret" class="form-input" value="<?php echo gs('google_client_secret', $settings); ?>" placeholder="GOCSPX-...">
        </div>
    </div>
</div>

<!-- Site Stats -->
<div class="admin-card">
    <h3 style="color:var(--text-primary);margin-bottom:1rem;">📊 Homepage Statistics</h3>
    <p style="color:var(--text-secondary);font-size:0.85rem;margin-bottom:1rem;">These numbers appear as animated counters on the homepage when set to values > 0.</p>
    <div class="form-row-3">
        <div class="form-group">
            <label class="form-label">Total Clients (base number)</label>
            <input type="number" name="stats_clients" class="form-input" value="<?php echo $statsRaw['total_clients'] ?? 0; ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Completed Projects</label>
            <input type="number" name="stats_projects" class="form-input" value="<?php echo $statsRaw['completed_projects'] ?? 0; ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Active Years</label>
            <input type="number" name="stats_years" class="form-input" value="<?php echo $statsRaw['active_years'] ?? 1; ?>">
        </div>
    </div>
</div>

<!-- Hero & About -->
<div class="admin-card">
    <h3 style="color:var(--text-primary);margin-bottom:1rem;">🦸 Hero & About Section</h3>
    <div class="form-group">
        <label class="form-label">Hero Title (HTML allowed)</label>
        <input type="text" name="hero_title" class="form-input" value="<?php echo gs('hero_title', $settings); ?>">
    </div>
    <div class="form-group">
        <label class="form-label">Hero Description</label>
        <textarea name="hero_desc" class="form-textarea" rows="3"><?php echo gs('hero_desc', $settings); ?></textarea>
    </div>
    <div class="form-group">
        <label class="form-label">About Title</label>
        <input type="text" name="about_title" class="form-input" value="<?php echo gs('about_title', $settings); ?>">
    </div>
    <div class="form-group">
        <label class="form-label">About Description (HTML allowed)</label>
        <textarea name="about_desc" class="form-textarea" rows="5"><?php echo gs('about_desc', $settings); ?></textarea>
    </div>
</div>

<!-- Contact & Social -->
<div class="admin-card">
    <h3 style="color:var(--text-primary);margin-bottom:1rem;">📬 Contact & Social Links</h3>
    <div class="form-row-2">
        <div class="form-group">
            <label class="form-label">Contact Email</label>
            <input type="email" name="contact_email" class="form-input" value="<?php echo gs('contact_email', $settings); ?>">
        </div>
        <div class="form-group">
            <label class="form-label">WhatsApp Link</label>
            <input type="text" name="contact_whatsapp" class="form-input" value="<?php echo gs('contact_whatsapp', $settings); ?>">
        </div>
    </div>
    <div class="form-row-2">
        <div class="form-group">
            <label class="form-label">Dribbble</label>
            <input type="text" name="social_dribbble" class="form-input" value="<?php echo gs('social_dribbble', $settings); ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Behance</label>
            <input type="text" name="social_behance" class="form-input" value="<?php echo gs('social_behance', $settings); ?>">
        </div>
        <div class="form-group">
            <label class="form-label">LinkedIn</label>
            <input type="text" name="social_linkedin" class="form-input" value="<?php echo gs('social_linkedin', $settings); ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Instagram</label>
            <input type="text" name="social_instagram" class="form-input" value="<?php echo gs('social_instagram', $settings); ?>">
        </div>
    </div>
</div>

<div style="margin-bottom:2rem;">
    <button type="submit" class="btn btn-primary">Save All Settings</button>
</div>
</form>

<script>
function updateCurrencySymbol(val) {
    const symbols = {'INR':'₹', 'USD':'$', 'EUR':'€', 'GBP':'£', 'AED':'د.إ', 'SAR':'﷼', 'MYR':'RM'};
    document.getElementById('base_currency_symbol').value = symbols[val] || '$';
}
</script>

<?php require_once 'footer.php'; ?>

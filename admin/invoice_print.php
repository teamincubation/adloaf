<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config.php';

$invoiceId = intval($_GET['id'] ?? 0);
if (!$invoiceId) {
    die("Invoice ID is required.");
}

$stmt = $pdo->prepare("
    SELECT i.*, p.title as project_title, p.price as total_price, p.paid_amount as project_paid, p.status as project_status, p.description as project_description,
           c.full_name as client_name, c.email as client_email, c.whatsapp as client_phone
    FROM invoices i
    JOIN projects p ON i.project_id = p.id
    JOIN users_public c ON p.client_id = c.id
    WHERE i.id = ?
");
$stmt->execute([$invoiceId]);
$inv = $stmt->fetch();

if (!$inv) {
    die("Invoice not found.");
}

$currencySym = site_setting('base_currency_symbol', '₹');

// Calculate invoice pricing details based on project status
$status = $inv['project_status'];
$totalPrice = floatval($inv['total_price']);
$paidAmount = floatval($inv['project_paid']);

$invoiceType = "";
$advanceRequested = 0;
$amountRequested = 0;
$previousPayments = 0;
$balanceDue = 0;

if ($status === 'Accepted') {
    $invoiceType = "Advance Payment Request (35%)";
    $amountRequested = round($totalPrice * 0.35, 2);
    $balanceDue = round($totalPrice - $amountRequested, 2);
} elseif ($status === 'Approved') {
    $invoiceType = "Work In Progress / Balance Request";
    $previousPayments = $paidAmount;
    $amountRequested = round($totalPrice - $paidAmount, 2);
    $balanceDue = $amountRequested;
} elseif ($status === 'Completed') {
    $invoiceType = "Final Invoice";
    $previousPayments = $paidAmount;
    $amountRequested = round($totalPrice - $paidAmount, 2);
    $balanceDue = $amountRequested;
} else {
    $invoiceType = "Project Request Invoice";
    $amountRequested = $totalPrice;
    $balanceDue = round($totalPrice - $paidAmount, 2);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Invoice - <?php echo htmlspecialchars($inv['invoice_number']); ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #ea580c;
      --text-dark: #1e1611;
      --text-muted: #6b7280;
      --bg-light: #FAF7F2;
      --border: #e8dec6;
    }
    body {
      font-family: 'Outfit', Arial, sans-serif;
      color: var(--text-dark);
      background: #fff;
      margin: 0;
      padding: 0;
      font-size: 14px;
      line-height: 1.5;
    }
    .print-actions {
      background: #f3f4f6;
      padding: 10px 20px;
      border-bottom: 1px solid #d1d5db;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .btn {
      display: inline-block;
      padding: 8px 16px;
      font-size: 0.9rem;
      font-weight: 600;
      text-decoration: none;
      border-radius: 6px;
      cursor: pointer;
      border: none;
    }
    .btn-primary { background: var(--primary); color: #fff; }
    .btn-secondary { background: #e5e7eb; color: #374151; }
    
    .invoice-container {
      max-width: 800px;
      margin: 30px auto;
      padding: 40px;
      border: 1px solid var(--border);
      border-radius: 12px;
      background: #fff;
      box-shadow: 0 4px 6px rgba(0,0,0,0.02);
    }
    
    .invoice-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      border-bottom: 2px solid var(--primary);
      padding-bottom: 25px;
      margin-bottom: 30px;
    }
    
    .logo-section h1 {
      margin: 0;
      font-size: 32px;
      font-weight: 800;
      color: var(--primary);
    }
    .logo-section p {
      margin: 4px 0 0;
      font-size: 12px;
      color: var(--text-muted);
      letter-spacing: 1px;
      text-transform: uppercase;
    }
    
    .inv-details {
      text-align: right;
    }
    .inv-details h2 {
      margin: 0;
      font-size: 24px;
      font-weight: 700;
      color: var(--text-dark);
    }
    .inv-details p {
      margin: 4px 0;
      color: var(--text-muted);
    }
    
    .address-section {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 40px;
      margin-bottom: 40px;
    }
    .address-block h4 {
      margin: 0 0 8px;
      color: var(--text-muted);
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .address-block p {
      margin: 2px 0;
      font-size: 13px;
    }
    
    .table-section {
      margin-bottom: 40px;
    }
    .invoice-table {
      width: 100%;
      border-collapse: collapse;
    }
    .invoice-table th {
      background: var(--bg-light);
      padding: 12px;
      text-align: left;
      font-weight: 600;
      border-bottom: 2px solid var(--border);
      color: var(--text-dark);
    }
    .invoice-table td {
      padding: 16px 12px;
      border-bottom: 1px solid var(--border);
      vertical-align: top;
    }
    
    .totals-section {
      display: flex;
      justify-content: flex-end;
      margin-bottom: 40px;
    }
    .totals-table {
      width: 320px;
      border-collapse: collapse;
    }
    .totals-table td {
      padding: 8px 12px;
    }
    .totals-table tr.highlight td {
      background: var(--bg-light);
      font-weight: 700;
      color: var(--primary);
      border-top: 2px solid var(--primary);
      border-bottom: 2px solid var(--primary);
      font-size: 16px;
    }
    
    .notes-section {
      border-top: 1px solid var(--border);
      padding-top: 25px;
      font-size: 12px;
      color: var(--text-muted);
    }
    .notes-section h5 {
      margin: 0 0 6px;
      color: var(--text-dark);
      font-size: 13px;
    }
    
    @media print {
      .print-actions { display: none !important; }
      body { background: #fff; }
      .invoice-container {
        border: none;
        box-shadow: none;
        padding: 0;
        margin: 0;
        max-width: 100%;
      }
    }
  </style>
</head>
<body>

<div class="print-actions">
  <div>
    <a href="works.php" class="btn btn-secondary">← Back to Works</a>
  </div>
  <div>
    <button class="btn btn-primary" onclick="window.print()">🖨️ Print / Save PDF</button>
  </div>
</div>

<div class="invoice-container">
  <!-- Invoice Header -->
  <div class="invoice-header">
    <div class="logo-section">
      <h1>adloaf<span>.</span></h1>
      <p>Freshly Baked Digital Studio</p>
    </div>
    <div class="inv-details">
      <h2>INVOICE</h2>
      <p style="font-weight:700; color:var(--primary);"><?php echo htmlspecialchars($inv['invoice_number']); ?></p>
      <p>Date: <?php echo date('d M Y', strtotime($inv['created_at'])); ?></p>
      <p>Type: <strong><?php echo $invoiceType; ?></strong></p>
    </div>
  </div>

  <!-- Address Info -->
  <div class="address-section">
    <div class="address-block">
      <h4>Billed From:</h4>
      <p style="font-weight:600;">adloaf.com</p>
      <p>Adnan Vellicheri,</p>
      <p>#11, 004, 12th A Cross,</p>
      <p>BTM 2nd Stage, Bangalore, 560076</p>
      <p>Email: hello@adloaf.com</p>
      <p>Website: www.adloaf.com</p>
    </div>
    <div class="address-block" style="text-align: right;">
      <h4>Billed To:</h4>
      <p style="font-weight:600;"><?php echo htmlspecialchars($inv['client_name']); ?></p>
      <p>Email: <?php echo htmlspecialchars($inv['client_email']); ?></p>
      <?php if (!empty($inv['client_phone'])): ?>
        <p>WhatsApp: <?php echo htmlspecialchars($inv['client_phone']); ?></p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Invoice Table -->
  <div class="table-section">
    <table class="invoice-table">
      <thead>
        <tr>
          <th style="width: 70%;">Project Description & Deliverables</th>
          <th style="text-align: right;">Total Price</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>
            <strong style="font-size: 15px; color: var(--text-dark);"><?php echo htmlspecialchars($inv['project_title']); ?></strong>
            <p style="margin: 6px 0 0; color: var(--text-muted); font-size: 13px;">
              <?php echo nl2br(htmlspecialchars($inv['project_description'] ?: 'Creative project services as agreed.')); ?>
            </p>
          </td>
          <td style="text-align: right; font-weight: 600; font-size: 15px;">
            <?php echo $currencySym . number_format($totalPrice, 2); ?>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- Totals Section -->
  <div class="totals-section">
    <table class="totals-table">
      <tr>
        <td style="color: var(--text-muted);">Total Project Cost:</td>
        <td style="text-align: right; font-weight: 600;"><?php echo $currencySym . number_format($totalPrice, 2); ?></td>
      </tr>
      
      <?php if ($status === 'Accepted'): ?>
        <tr>
          <td style="color: var(--text-muted);">Advance Requested (35%):</td>
          <td style="text-align: right; font-weight: 600; color: var(--primary);"><?php echo $currencySym . number_format($amountRequested, 2); ?></td>
        </tr>
        <tr class="highlight">
          <td>Amount Due Now:</td>
          <td style="text-align: right;"><?php echo $currencySym . number_format($amountRequested, 2); ?></td>
        </tr>
        <tr>
          <td style="color: var(--text-muted); font-size: 12px;">Remaining Balance (65%):</td>
          <td style="text-align: right; font-size: 12px; color: var(--text-muted);"><?php echo $currencySym . number_format($balanceDue, 2); ?></td>
        </tr>
      <?php else: ?>
        <?php if ($previousPayments > 0): ?>
          <tr>
            <td style="color: var(--text-muted);">Previous Payments Received:</td>
            <td style="text-align: right; font-weight: 600; color: #10b981;">-<?php echo $currencySym . number_format($previousPayments, 2); ?></td>
          </tr>
        <?php endif; ?>
        <tr class="highlight">
          <td>Balance Due:</td>
          <td style="text-align: right;"><?php echo $currencySym . number_format(max(0, $balanceDue), 2); ?></td>
        </tr>
      <?php endif; ?>
    </table>
  </div>

  <!-- Footnote -->
  <div class="notes-section">
    <h5>Payment Information</h5>
    <p>To settle this invoice, please use the custom payment link shared with you or transfer the amount due to the bank details listed in your Adloaf dashboard. Thank you for baking your project with us!</p>
  </div>
</div>

</body>
</html>

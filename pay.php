<?php
require_once __DIR__ . '/config.php';

$invoiceNum = trim($_GET['inv'] ?? '');
$amount     = floatval($_GET['am'] ?? 0);
$remarks    = trim($_GET['tn'] ?? '');

$baseUpi = site_setting('payment_upi_link', 'upi://pay?pa=adnanmongam@ybl&pn=Adnan%20Vellicheri');

// Build customized UPI URL
// Ensure it matches format upi://pay?pa=...
$upiUrl = $baseUpi;
if (strpos($upiUrl, '?') !== false) {
    // strip out existing am or tn to avoid duplication
    $parts = parse_url($upiUrl);
    parse_str($parts['query'] ?? '', $query);
    unset($query['am']);
    unset($query['tn']);
    unset($query['cu']);
    
    $query['am'] = $amount;
    $query['tn'] = $remarks;
    $query['cu'] = 'INR';
    
    $upiUrl = "upi://pay?" . http_build_query($query);
} else {
    $upiUrl .= "?am={$amount}&tn=" . urlencode($remarks) . "&cu=INR";
}

$currencySym = site_setting('base_currency_symbol', '₹');
$bankDetails = site_setting('payment_bank_details', '');

// Quick mobile detection
$userAgent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
$isMobile = (strpos($userAgent, 'mobile') !== false || strpos($userAgent, 'android') !== false || strpos($userAgent, 'iphone') !== false);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Complete Your Payment | Adloaf</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    :root {
      --accent-orange: #ea580c;
      --bg-dark: #0d0a07;
      --bg-card: #16100a;
      --border-medium: #302518;
      --text-primary: #FAF7F2;
      --text-secondary: #C4A882;
      --text-muted: #8b7355;
    }
    body {
      background: var(--bg-dark);
      color: var(--text-primary);
      font-family: 'Outfit', Arial, sans-serif;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      box-sizing: border-box;
    }
    .payment-container {
      max-width: 480px;
      width: 100%;
      padding: 20px;
    }
    .payment-card {
      background: var(--bg-card);
      border: 1.5px solid var(--border-medium);
      border-radius: 18px;
      padding: 30px;
      box-shadow: 0 15px 35px rgba(0,0,0,0.5);
      text-align: center;
    }
    .logo {
      font-size: 28px;
      font-weight: 800;
      color: var(--accent-orange);
      margin-bottom: 25px;
      display: inline-block;
      text-decoration: none;
    }
    .logo span {
      color: var(--text-primary);
    }
    .invoice-info {
      font-size: 0.95rem;
      color: var(--text-secondary);
      margin-bottom: 10px;
    }
    .amount-display {
      font-size: 2.5rem;
      font-weight: 800;
      color: var(--text-primary);
      margin: 15px 0 25px;
    }
    .remarks-box {
      background: rgba(234, 88, 12, 0.05);
      border: 1px dashed var(--border-medium);
      padding: 12px;
      border-radius: 8px;
      font-size: 0.9rem;
      color: var(--text-secondary);
      margin-bottom: 25px;
    }
    .btn {
      display: block;
      width: 100%;
      padding: 16px;
      font-size: 1.05rem;
      font-weight: 700;
      text-align: center;
      color: #fff;
      background: linear-gradient(135deg, #EA580C, #D97706);
      border: none;
      border-radius: 10px;
      text-decoration: none;
      cursor: pointer;
      box-shadow: 0 8px 20px rgba(234, 88, 12, 0.25);
      transition: all 0.2s;
    }
    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 24px rgba(234, 88, 12, 0.35);
    }
    .qr-container {
      margin: 25px 0;
      padding: 15px;
      background: #fff;
      display: inline-block;
      border-radius: 12px;
      border: 1px solid var(--border-medium);
    }
    .qr-code {
      display: block;
      max-width: 200px;
      margin: 0 auto;
    }
    .divider {
      display: flex;
      align-items: center;
      text-align: center;
      margin: 30px 0;
      color: var(--text-muted);
      font-size: 0.85rem;
    }
    .divider::before, .divider::after {
      content: '';
      flex: 1;
      border-bottom: 1px solid var(--border-medium);
    }
    .divider:not(:empty)::before { margin-right: .5em; }
    .divider:not(:empty)::after { margin-left: .5em; }
    
    .bank-card {
      text-align: left;
      background: rgba(255,255,255,0.02);
      border: 1px solid var(--border-medium);
      border-radius: 10px;
      padding: 16px;
      font-size: 0.88rem;
    }
    .bank-title {
      font-weight: 700;
      color: var(--text-primary);
      margin-bottom: 8px;
    }
    .bank-details {
      color: var(--text-secondary);
      line-height: 1.6;
      white-space: pre-wrap;
    }
  </style>
</head>
<body>

<div class="payment-container">
  <div class="payment-card">
    <a href="index.php" class="logo">adloaf<span>.</span></a>
    
    <div class="invoice-info">
      <?php if (!empty($invoiceNum)): ?>
        Invoice: <strong><?php echo htmlspecialchars($invoiceNum); ?></strong>
      <?php else: ?>
        Creative Project Request
      <?php endif; ?>
    </div>
    
    <div class="amount-display">
      <?php echo $currencySym . number_format($amount, 2); ?>
    </div>
    
    <?php if (!empty($remarks)): ?>
      <div class="remarks-box">
        📝 Remarks: <strong><?php echo htmlspecialchars($remarks); ?></strong>
      </div>
    <?php endif; ?>

    <?php if ($isMobile): ?>
      <!-- Display deep link button prominently on mobile -->
      <a href="<?php echo htmlspecialchars($upiUrl); ?>" class="btn">🚀 Pay with UPI App</a>
      <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 10px;">Opens Google Pay, PhonePe, Paytm, etc.</p>
    <?php else: ?>
      <!-- Display QR code prominently on desktop -->
      <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 15px;">Scan this QR code with any UPI app to pay</p>
      <div class="qr-container">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode($upiUrl); ?>" alt="Scan to Pay" class="qr-code">
      </div>
      <p style="font-size: 0.8rem; color: var(--text-muted);">Compatible with BHIM, GPay, PhonePe, Paytm, and mobile banking apps.</p>
    <?php endif; ?>

    <?php if (!empty($bankDetails)): ?>
      <div class="divider">OR BANK TRANSFER</div>
      <div class="bank-card">
        <div class="bank-title">🏛️ Direct Bank Transfer Details:</div>
        <div class="bank-details"><?php echo htmlspecialchars($bankDetails); ?></div>
      </div>
    <?php endif; ?>
  </div>
</div>

</body>
</html>

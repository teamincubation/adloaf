<?php
/**
 * Adloaf Simple SMTP Mailer
 * Uses PHP's socket functions to send SMTP emails via Hostinger
 */
class Mailer {
    private $host, $port, $user, $pass, $fromName;

    public function __construct() {
        $this->host     = SMTP_HOST;
        $this->port     = SMTP_PORT;
        $this->user     = SMTP_USER;
        $this->pass     = SMTP_PASS;
        $this->fromName = SMTP_FROM_NAME;
    }

    public function send($to, $subject, $htmlBody, $toName = '') {
        $boundary = md5(time());
        $headers  = [
            "MIME-Version: 1.0",
            "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
            "From: {$this->fromName} <{$this->user}>",
            "Reply-To: {$this->user}",
            "X-Mailer: PHP/" . phpversion(),
        ];
        if ($toName) {
            $to = "{$toName} <{$to}>";
        }

        $plainText = strip_tags($htmlBody);
        $body  = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n{$plainText}\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n\r\n{$htmlBody}\r\n";
        $body .= "--{$boundary}--";

        // Use PHP mail() with SMTP config via Hostinger (php.ini typically handles this)
        $extra = '-f ' . $this->user;
        return mail($to, $subject, $body, implode("\r\n", $headers), $extra);
    }

    /**
     * Send password reset email
     */
    public function sendPasswordReset($to, $toName, $resetLink) {
        $subject = "Reset Your Adloaf Password";
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: 'Outfit', Arial, sans-serif; background: #0d0a07; margin:0; padding:0;">
  <div style="max-width:560px; margin:40px auto; background:#1A1309; border-radius:16px; overflow:hidden; border:1px solid #3D2E1E;">
    <div style="background: linear-gradient(135deg, #EA580C, #D97706); padding:32px; text-align:center;">
      <h1 style="color:#fff; margin:0; font-size:28px;">Adloaf<span style="opacity:0.7;">.</span></h1>
    </div>
    <div style="padding:32px;">
      <h2 style="color:#FAF7F2; margin-top:0;">Password Reset Request</h2>
      <p style="color:#C4A882;">Hi {$toName},</p>
      <p style="color:#C4A882;">We received a request to reset your Adloaf account password. Click the button below to reset it. This link expires in <strong style="color:#EA580C;">1 hour</strong>.</p>
      <div style="text-align:center; margin:32px 0;">
        <a href="{$resetLink}" style="background: linear-gradient(135deg, #EA580C, #D97706); color:#fff; padding:14px 32px; border-radius:8px; text-decoration:none; font-weight:700; font-size:16px; display:inline-block;">Reset My Password</a>
      </div>
      <p style="color:#8B7355; font-size:13px;">If you didn't request this, you can safely ignore this email. Your password will remain unchanged.</p>
      <hr style="border:1px solid #3D2E1E; margin: 24px 0;">
      <p style="color:#8B7355; font-size:12px; text-align:center;">© 2025 Adloaf. Freshly Baked Creative Ideas.</p>
    </div>
  </div>
</body>
</html>
HTML;
        return $this->send($to, $subject, $html, $toName);
    }

    /**
     * Send bake request confirmation to user
     */
    public function sendBakeConfirmation($to, $toName, $service, $deadline) {
        $subject = "Your Bake Request Has Been Placed in the Oven! 🍞";
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; background:#0d0a07; margin:0; padding:0;">
  <div style="max-width:560px; margin:40px auto; background:#1A1309; border-radius:16px; overflow:hidden; border:1px solid #3D2E1E;">
    <div style="background:linear-gradient(135deg,#EA580C,#D97706); padding:32px; text-align:center;">
      <h1 style="color:#fff; margin:0; font-size:28px;">Adloaf.</h1>
      <p style="color:rgba(255,255,255,0.8); margin:8px 0 0;">Your Creative Bakery</p>
    </div>
    <div style="padding:32px;">
      <h2 style="color:#FAF7F2;">Your Bake is in the Oven! 🔥</h2>
      <p style="color:#C4A882;">Hi {$toName},</p>
      <p style="color:#C4A882;">We've received your <strong style="color:#EA580C;">{$service}</strong> request with a deadline of <strong style="color:#EA580C;">{$deadline}</strong>. Our team will review it shortly and get back to you on WhatsApp!</p>
      <p style="color:#8B7355; font-size:13px; margin-top:24px;">Stay tuned — fresh ideas take just the right amount of time to bake. 🍞</p>
      <hr style="border:1px solid #3D2E1E; margin:24px 0;">
      <p style="color:#8B7355; font-size:12px; text-align:center;">© 2025 Adloaf. Freshly Baked Creative Ideas.</p>
    </div>
  </div>
</body>
</html>
HTML;
        return $this->send($to, $subject, $html, $toName);
    }

    /**
     * Send bake request status update notification to user
     */
    public function sendRequestStatusUpdate($to, $toName, $service, $status, $cost, $notes, $payInfo) {
        $subject = "Update on your adloaf Bake Request: {$service} [{$status}]";
        
        $statusColors = [
            'Pending'   => '#ef4444',
            'Accepted'  => '#f59e0b',
            'Rejected'  => '#ef4444',
            'Approved'  => '#10b981',
            'Completed' => '#10b981',
        ];
        $color = $statusColors[$status] ?? '#EA580C';
        
        $paymentHtml = '';
        if (in_array($status, ['Accepted', 'Approved']) && (!empty($payInfo['bank']) || !empty($payInfo['upi_id']) || !empty($payInfo['upi_number']))) {
            $paymentHtml = <<<HTML
            <div style="margin-top: 24px; padding: 20px; background: #231B13; border: 1px dashed #EA580C; border-radius: 8px;">
                <h3 style="color: #FAF7F2; margin-top: 0; font-size: 16px;">Payment details to start proofing:</h3>
HTML;
            if (!empty($payInfo['bank'])) {
                $paymentHtml .= "<p style='color:#C4A882; margin: 4px 0; font-size: 14px;'><strong>Bank Transfer:</strong> " . nl2br(htmlspecialchars($payInfo['bank'])) . "</p>";
            }
            if (!empty($payInfo['upi_id'])) {
                $paymentHtml .= "<p style='color:#C4A882; margin: 4px 0; font-size: 14px;'><strong>UPI ID:</strong> " . htmlspecialchars($payInfo['upi_id']) . "</p>";
            }
            if (!empty($payInfo['upi_number'])) {
                $paymentHtml .= "<p style='color:#C4A882; margin: 4px 0; font-size: 14px;'><strong>UPI Phone:</strong> " . htmlspecialchars($payInfo['upi_number']) . "</p>";
            }
            $paymentHtml .= "</div>";
        }

        $notesHtml = '';
        if (!empty($notes)) {
            $notesHtml = <<<HTML
            <div style="margin-top: 20px; padding: 15px; background: #1C150E; border-left: 4px solid #EA580C; border-radius: 4px; color: #FAF7F2;">
                <strong style="font-size:14px;">Oven Notes:</strong><br>
                <span style="color:#C4A882; font-size:14px;">{$notes}</span>
            </div>
HTML;
        }

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; background:#0d0a07; margin:0; padding:0;">
  <div style="max-width:560px; margin:40px auto; background:#1A1309; border-radius:16px; overflow:hidden; border:1px solid #3D2E1E;">
    <div style="background:linear-gradient(135deg,#EA580C,#D97706); padding:32px; text-align:center;">
      <h1 style="color:#fff; margin:0; font-size:28px;">adloaf.</h1>
      <p style="color:rgba(255,255,255,0.8); margin:8px 0 0;">Status Update Notification</p>
    </div>
    <div style="padding:32px;">
      <h2 style="color:#FAF7F2; margin-top:0;">Your request status is now <span style="color:{$color};">{$status}</span></h2>
      <p style="color:#C4A882;">Hi {$toName},</p>
      <p style="color:#C4A882;">Here are the update details for your bake request:</p>
      
      <table style="width:100%; border-collapse:collapse; margin-top:16px;">
        <tr>
          <td style="color:#8B7355; padding:8px 0; border-bottom:1px solid #3D2E1E; width:40%; font-size: 14px;">Service</td>
          <td style="color:#FAF7F2; padding:8px 0; border-bottom:1px solid #3D2E1E; font-weight:700; font-size: 14px;">{$service}</td>
        </tr>
        <tr>
          <td style="color:#8B7355; padding:8px 0; border-bottom:1px solid #3D2E1E; font-size: 14px;">Status</td>
          <td style="color:{$color}; padding:8px 0; border-bottom:1px solid #3D2E1E; font-weight:700; font-size: 14px;">{$status}</td>
        </tr>
        <tr>
          <td style="color:#8B7355; padding:8px 0; border-bottom:1px solid #3D2E1E; font-size: 14px;">Estimated Cost</td>
          <td style="color:#EA580C; padding:8px 0; border-bottom:1px solid #3D2E1E; font-weight:700; font-size: 14px;">{$cost}</td>
        </tr>
      </table>
      
      {$notesHtml}
      {$paymentHtml}
      
      <p style="color:#8B7355; font-size:13px; margin-top:24px;">If you have any questions, feel free to reply directly to this email or chat with us on WhatsApp.</p>
      <hr style="border:1px solid #3D2E1E; margin:24px 0;">
      <p style="color:#8B7355; font-size:12px; text-align:center;">© 2026 adloaf Creative. Freshly Baked Design Ideas.</p>
    </div>
  </div>
</body>
</html>
HTML;
        return $this->send($to, $subject, $html, $toName);
    }

    /**
     * Send bake request notification to admin
     */
    public function sendAdminNotification($service, $clientName, $clientEmail, $deadline, $description) {
        $subject = "🚨 Urgent: New Bake Request Placed! [{$service}]";
        $adminLoginLink = SITE_URL . "/admin/bake_requests.php";
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; background:#0d0a07; margin:0; padding:0;">
  <div style="max-width:560px; margin:40px auto; background:#1A1309; border-radius:16px; overflow:hidden; border:1px solid #3D2E1E;">
    <div style="background:linear-gradient(135deg,#ef4444,#ea580c); padding:32px; text-align:center;">
      <h1 style="color:#fff; margin:0; font-size:28px;">adloaf. admin</h1>
      <p style="color:rgba(255,255,255,0.8); margin:8px 0 0;">New Project Request Notification</p>
    </div>
    <div style="padding:32px;">
      <h2 style="color:#FAF7F2; margin-top:0;">Urgent Bake Request Received 🔥</h2>
      <p style="color:#C4A882;">A client has submitted a new project request. Review the details below:</p>
      
      <table style="width:100%; border-collapse:collapse; margin-top:16px;">
        <tr>
          <td style="color:#8B7355; padding:8px 0; border-bottom:1px solid #3D2E1E; width:40%; font-size: 14px;">Client Name</td>
          <td style="color:#FAF7F2; padding:8px 0; border-bottom:1px solid #3D2E1E; font-weight:700; font-size: 14px;">{$clientName}</td>
        </tr>
        <tr>
          <td style="color:#8B7355; padding:8px 0; border-bottom:1px solid #3D2E1E; font-size: 14px;">Client Email</td>
          <td style="color:#FAF7F2; padding:8px 0; border-bottom:1px solid #3D2E1E; font-size: 14px;"><a href="mailto:{$clientEmail}" style="color:#EA580C;">{$clientEmail}</a></td>
        </tr>
        <tr>
          <td style="color:#8B7355; padding:8px 0; border-bottom:1px solid #3D2E1E; font-size: 14px;">Service</td>
          <td style="color:#FAF7F2; padding:8px 0; border-bottom:1px solid #3D2E1E; font-weight:700; font-size: 14px;">{$service}</td>
        </tr>
        <tr>
          <td style="color:#8B7355; padding:8px 0; border-bottom:1px solid #3D2E1E; font-size: 14px;">Deadline</td>
          <td style="color:#FAF7F2; padding:8px 0; border-bottom:1px solid #3D2E1E; font-weight:700; font-size: 14px;">{$deadline}</td>
        </tr>
      </table>
      
      <div style="margin-top: 20px; padding: 15px; background: #1C150E; border-left: 4px solid #ef4444; border-radius: 4px; color: #FAF7F2;">
        <strong style="font-size:14px;">Project Description:</strong><br>
        <span style="color:#C4A882; font-size:14px;">{$description}</span>
      </div>
      
      <div style="text-align:center; margin:32px 0;">
        <a href="{$adminLoginLink}" style="background: linear-gradient(135deg, #ef4444, #EA580C); color:#fff; padding:14px 32px; border-radius:8px; text-decoration:none; font-weight:700; font-size:16px; display:inline-block;">Manage Request in Admin Panel</a>
      </div>
      
      <hr style="border:1px solid #3D2E1E; margin:24px 0;">
      <p style="color:#8B7355; font-size:12px; text-align:center;">© 2026 adloaf Creative. Admin Notifications.</p>
    </div>
  </div>
</body>
</html>
HTML;
        $r1 = $this->send('developers@adloaf.com', $subject, $html, 'Adloaf Developers');
        $r2 = $this->send('adnanmongam@gmail.com', $subject, $html, 'Adnan Mongam');
        return $r1 || $r2;
    }

    /**
     * Send formatted invoice to client with Pay Now link
     */
    public function sendInvoice($to, $toName, $invoiceNumber, $service, $total, $requested, $balance, $payLink) {
        $subject = "Invoice from Adloaf: {$invoiceNumber} [{$service}]";
        $currencySym = site_setting('base_currency_symbol', '₹');
        
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; background:#0d0a07; margin:0; padding:0;">
  <div style="max-width:560px; margin:40px auto; background:#1A1309; border-radius:16px; overflow:hidden; border:1px solid #3D2E1E;">
    <div style="background:linear-gradient(135deg,#EA580C,#D97706); padding:32px; text-align:center;">
      <h1 style="color:#fff; margin:0; font-size:28px;">adloaf.</h1>
      <p style="color:rgba(255,255,255,0.8); margin:8px 0 0;">Project Invoice Details</p>
    </div>
    <div style="padding:32px;">
      <h2 style="color:#FAF7F2; margin-top:0;">Invoice Reference: {$invoiceNumber}</h2>
      <p style="color:#C4A882;">Hi {$toName},</p>
      <p style="color:#C4A882;">Please find the payment request details for your project <strong>{$service}</strong> below:</p>
      
      <table style="width:100%; border-collapse:collapse; margin-top:16px;">
        <tr>
          <td style="color:#8B7355; padding:8px 0; border-bottom:1px solid #3D2E1E; width:50%; font-size: 14px;">Total Project Cost</td>
          <td style="color:#FAF7F2; padding:8px 0; border-bottom:1px solid #3D2E1E; font-weight:700; font-size: 14px;">{$currencySym}{$total}</td>
        </tr>
        <tr>
          <td style="color:#8B7355; padding:8px 0; border-bottom:1px solid #3D2E1E; font-size: 14px;">Amount Requested</td>
          <td style="color:#EA580C; padding:8px 0; border-bottom:1px solid #3D2E1E; font-weight:700; font-size: 14px;">{$currencySym}{$requested}</td>
        </tr>
        <tr>
          <td style="color:#8B7355; padding:8px 0; border-bottom:1px solid #3D2E1E; font-size: 14px;">Remaining Balance Due</td>
          <td style="color:#FAF7F2; padding:8px 0; border-bottom:1px solid #3D2E1E; font-weight:700; font-size: 14px;">{$currencySym}{$balance}</td>
        </tr>
      </table>
      
      <div style="text-align:center; margin:32px 0;">
        <a href="{$payLink}" style="background: linear-gradient(135deg, #EA580C, #D97706); color:#fff; padding:14px 32px; border-radius:8px; text-decoration:none; font-weight:700; font-size:16px; display:inline-block;">Pay Now via UPI</a>
      </div>
      
      <p style="color:#8B7355; font-size:13px; margin-top:24px;">Click the button above to view your printable invoice and complete your payment via UPI immediately.</p>
      <hr style="border:1px solid #3D2E1E; margin:24px 0;">
      <p style="color:#8B7355; font-size:12px; text-align:center;">© 2026 adloaf Creative. All rights reserved.</p>
    </div>
  </div>
</body>
</html>
HTML;
        return $this->send($to, $subject, $html, $toName);
    }
}

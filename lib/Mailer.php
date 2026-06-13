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
}

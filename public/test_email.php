<?php
// public/test_email.php — Interactive SMTP Diagnostics & Test Tool
require_once dirname(__DIR__) . '/config/app.php';

$recipient = $_GET['to'] ?? $_POST['to'] ?? 'admin@lagunavibe.com';
$customHost = $_POST['host'] ?? null;
$customPort = $_POST['port'] ?? null;
$customUser = $_POST['user'] ?? null;
$customPass = $_POST['pass'] ?? null;
$customEnc  = $_POST['enc'] ?? null;

$isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');
$result = null;
$debugLog = [];

if ($isPost) {
    if ($customHost) putenv("MAIL_HOST={$customHost}");
    if ($customPort) putenv("MAIL_PORT={$customPort}");
    if ($customUser) putenv("MAIL_USERNAME={$customUser}");
    if ($customPass) putenv("MAIL_PASSWORD={$customPass}");
    if ($customEnc)  putenv("MAIL_ENCRYPTION={$customEnc}");

    $subject = "Laguna Vibe SMTP Test — " . date('M j, Y g:i A');
    $htmlBody = "
    <div style='font-family:sans-serif;padding:24px;background:#f8fafc;border-radius:12px;max-width:550px;margin:auto;'>
        <h2 style='color:#0f4c5c;margin:0 0 10px;'>Laguna Vibe SMTP Test ✓</h2>
        <p style='color:#334155;line-height:1.6;'>This is a live test email sent from your Laguna Vibe store at <strong>" . date('Y-m-d H:i:s') . "</strong>.</p>
        <div style='background:#ffffff;padding:16px;border-radius:8px;border:1px solid #e2e8f0;font-size:13px;'>
            <strong>SMTP Host:</strong> " . htmlspecialchars(env('MAIL_HOST')) . "<br>
            <strong>Port:</strong> " . htmlspecialchars(env('MAIL_PORT')) . "<br>
            <strong>User:</strong> " . htmlspecialchars(env('MAIL_USERNAME')) . "<br>
            <strong>Recipient:</strong> " . htmlspecialchars($recipient) . "
        </div>
    </div>";

    $success = send_mail($recipient, $subject, $htmlBody, $debugLog);
    $result = $success ? 'success' : 'error';
}

$currentHost = env('MAIL_HOST', 'mail.lagunavibe.com');
$pop3Host    = env('POP3_HOST', 'mail.lagunavibe.com');
$currentPort = env('MAIL_PORT', '465');
$currentUser = env('MAIL_USERNAME', 'noreply@lagunavibe.com');
$currentPass = env('MAIL_PASSWORD', '=xQHc%KEN3!@ol96');
$currentEnc  = env('MAIL_ENCRYPTION', 'ssl');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SMTP Email Tester — Laguna Vibe</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f8fafc; padding: 40px 20px; }
        .card { max-width: 680px; margin: 0 auto; background: #1e293b; border-radius: 16px; padding: 32px; box-shadow: 0 20px 40px rgba(0,0,0,0.4); }
        h1 { color: #38bdf8; font-size: 24px; margin-top: 0; }
        label { display: block; font-size: 13px; font-weight: 600; color: #94a3b8; margin: 14px 0 6px; }
        input, select { width: 100%; box-sizing: border-box; background: #0f172a; border: 1px solid #334155; color: #ffffff; padding: 10px 14px; border-radius: 8px; font-size: 14px; }
        .btn { background: #0f4c5c; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; cursor: pointer; margin-top: 20px; width: 100%; font-size: 15px; transition: background 0.2s; }
        .btn:hover { background: #0c3d4a; }
        .alert { padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background: #064e3b; border: 1px solid #059669; color: #a7f3d0; }
        .alert-error { background: #7f1d1d; border: 1px solid #dc2626; color: #fecaca; }
        .log-box { background: #020617; border: 1px solid #1e293b; padding: 16px; border-radius: 8px; font-family: monospace; font-size: 12px; color: #cbd5e1; max-height: 250px; overflow-y: auto; white-space: pre-wrap; margin-top: 20px; }
        .quick-fill { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 15px; }
        .quick-btn { background: #334155; color: #e2e8f0; border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; cursor: pointer; }
        .quick-btn:hover { background: #475569; }
        .host-info { background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 12px 16px; font-size: 13px; color: #94a3b8; margin-bottom: 18px; }
        .host-info strong { color: #38bdf8; }
    </style>
</head>
<body>
<div class="card">
    <h1>📧 Laguna Vibe SMTP Tester & Diagnostic</h1>
    <p style="color: #94a3b8; font-size: 14px; line-height: 1.5;">
        Use this tool to test email sending when an order is completed and verify your SMTP credentials with real-time debug logs.
    </p>

    <div class="host-info">
        <div><strong>POP3 Host:</strong> <?= htmlspecialchars($pop3Host) ?> (Port 995 SSL / 110)</div>
        <div style="margin-top: 4px;"><strong>SMTP Host:</strong> <?= htmlspecialchars($currentHost) ?> (Port 465 SSL / 587 TLS)</div>
    </div>

    <?php if ($result === 'success'): ?>
        <div class="alert alert-success">
            <strong>✓ Email Sent Successfully!</strong> Check the inbox/spam of <u><?= htmlspecialchars($recipient) ?></u>.
        </div>
    <?php elseif ($result === 'error'): ?>
        <div class="alert alert-error">
            <strong>✕ Failed to Send Email.</strong> Review the diagnostic logs below to see why authentication/connection failed.
        </div>
    <?php endif; ?>

    <div class="quick-fill">
        <span style="font-size: 12px; color: #94a3b8; line-height: 28px;">Presets:</span>
        <button type="button" class="quick-btn" onclick="fillLagunaSSL()">Laguna Vibe (SSL 465)</button>
        <button type="button" class="quick-btn" onclick="fillLagunaTLS()">Laguna Vibe (TLS 587)</button>
        <button type="button" class="quick-btn" onclick="fillO365()">Office 365</button>
        <button type="button" class="quick-btn" onclick="fillGmail()">Gmail SMTP</button>
        <button type="button" class="quick-btn" onclick="fillBrevo()">Brevo Relay</button>
    </div>

    <form method="POST">
        <label>Recipient Email (Where to send test):</label>
        <input type="email" name="to" value="<?= htmlspecialchars($recipient) ?>" required>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
            <div>
                <label>SMTP Host:</label>
                <input type="text" name="host" id="fHost" value="<?= htmlspecialchars($currentHost) ?>" required>
            </div>
            <div>
                <label>Port:</label>
                <input type="number" name="port" id="fPort" value="<?= htmlspecialchars($currentPort) ?>" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div>
                <label>SMTP Username / Email:</label>
                <input type="text" name="user" id="fUser" value="<?= htmlspecialchars($currentUser) ?>" required>
            </div>
            <div>
                <label>Encryption:</label>
                <select name="enc" id="fEnc">
                    <option value="ssl" <?= $currentEnc === 'ssl' ? 'selected' : '' ?>>SSL (Port 465)</option>
                    <option value="tls" <?= $currentEnc === 'tls' ? 'selected' : '' ?>>TLS (STARTTLS - Port 587)</option>
                    <option value="none" <?= $currentEnc === 'none' ? 'selected' : '' ?>>None (Port 25)</option>
                </select>
            </div>
        </div>

        <label>SMTP Password / App Password:</label>
        <input type="password" name="pass" id="fPass" value="<?= htmlspecialchars($currentPass) ?>" required>

        <button type="submit" class="btn">🚀 Send Test Email Now</button>
    </form>

    <?php if (!empty($debugLog)): ?>
        <label style="margin-top: 24px;">Detailed SMTP Debug Log:</label>
        <div class="log-box"><?= htmlspecialchars(implode("\n", $debugLog)) ?></div>
    <?php endif; ?>
</div>

<script>
function fillLagunaSSL() {
    document.getElementById('fHost').value = 'mail.lagunavibe.com';
    document.getElementById('fPort').value = '465';
    document.getElementById('fEnc').value = 'ssl';
    document.getElementById('fUser').value = 'noreply@lagunavibe.com';
    document.getElementById('fPass').value = '=xQHc%KEN3!@ol96';
}
function fillLagunaTLS() {
    document.getElementById('fHost').value = 'mail.lagunavibe.com';
    document.getElementById('fPort').value = '587';
    document.getElementById('fEnc').value = 'tls';
    document.getElementById('fUser').value = 'noreply@lagunavibe.com';
    document.getElementById('fPass').value = '=xQHc%KEN3!@ol96';
}
function fillO365() {
    document.getElementById('fHost').value = 'smtp.office365.com';
    document.getElementById('fPort').value = '587';
    document.getElementById('fEnc').value = 'tls';
    document.getElementById('fUser').value = 'noreply@lagunavibe.com';
}
function fillGmail() {
    document.getElementById('fHost').value = 'smtp.gmail.com';
    document.getElementById('fPort').value = '587';
    document.getElementById('fEnc').value = 'tls';
    document.getElementById('fUser').value = 'your.email@gmail.com';
    document.getElementById('fPass').value = '';
    alert('For Gmail, generate a 16-letter App Password at https://myaccount.google.com/apppasswords');
}
function fillBrevo() {
    document.getElementById('fHost').value = 'smtp-relay.brevo.com';
    document.getElementById('fPort').value = '587';
    document.getElementById('fEnc').value = 'tls';
}
</script>
</body>
</html>

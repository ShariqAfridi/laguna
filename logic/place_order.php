<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require '../vendor/autoload.php';
include "../db.php";

// ====================== VALIDATE REQUEST ======================
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['place_order'])) {
    die("No order to process. Please go back and try again.");
}

// ====================== CART DATA ======================
$cart = [];
if (isset($_POST['cart_data']) && !empty($_POST['cart_data'])) {
    $decoded = json_decode($_POST['cart_data'], true);
    if (is_array($decoded)) $cart = $decoded;
}
if (empty($cart) && isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $cart = array_values($_SESSION['cart']);
}
if (empty($cart)) {
    die("Your cart is empty. Please add items before checking out.");
}

// ====================== CUSTOMER DATA ======================
$first_name     = trim($_POST['first_name'] ?? '');
$last_name      = trim($_POST['last_name'] ?? '');
$full_name      = trim($_POST['full_name'] ?? ($first_name . ' ' . $last_name));
$email          = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$phone          = trim($_POST['phone'] ?? '');
$address        = trim($_POST['address'] ?? '');
$apartment      = trim($_POST['apartment'] ?? '');
$address_full   = !empty($apartment) ? $address . ', ' . $apartment : $address;
$city           = trim($_POST['city'] ?? '');
$state          = trim($_POST['state'] ?? '');
$zip            = trim($_POST['zip'] ?? '');
$country        = trim($_POST['country'] ?? 'US');
$notes          = trim($_POST['notes'] ?? '');
$promo_code     = strtoupper(trim($_POST['promo_code'] ?? ''));
$payment_method = trim($_POST['payment_method'] ?? 'stripe');

// Billing fields
$same_billing   = isset($_POST['same_billing']) ? 1 : 0;
$billing_name   = trim(($_POST['billing_first_name'] ?? '') . ' ' . ($_POST['billing_last_name'] ?? ''));
$billing_address = $same_billing ? $address_full : trim($_POST['billing_address'] ?? '');
$billing_city   = $same_billing ? $city : trim($_POST['billing_city'] ?? '');
$billing_state  = $same_billing ? $state : trim($_POST['billing_state'] ?? '');
$billing_zip    = $same_billing ? $zip : trim($_POST['billing_zip'] ?? '');
$billing_country = $same_billing ? $country : trim($_POST['billing_country'] ?? 'US');

// ====================== VALIDATION ======================
if (!$email) {
    die("Invalid email address. Please go back and check your email.");
}
if (empty($full_name) || empty($address) || empty($city) || empty($state) || empty($zip)) {
    die("Please fill in all required shipping fields.");
}

// ====================== CALCULATE TOTALS ======================
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += floatval($item['price'] ?? 0) * intval($item['qty'] ?? 1);
}

// Delivery type
$delivery_type = trim($_POST['delivery_type'] ?? 'standard');
if ($delivery_type === 'express') {
    $shipping = 18.00;
} else {
    $shipping = ($subtotal >= 50) ? 0.00 : 9.00;
}

$tax_rate = 0.08;
$tax      = round($subtotal * $tax_rate, 2);

// Promo code discount
$discount = 0;
if (!empty($promo_code)) {
    // Example: hardcoded promos — replace with DB lookup as needed
    $promos = [
        'WELCOME10' => ['type' => 'fixed',   'value' => 10.00],
        'SAVE20'    => ['type' => 'percent', 'value' => 20],
    ];
    if (isset($promos[$promo_code])) {
        $p = $promos[$promo_code];
        if ($p['type'] === 'fixed') {
            $discount = min($p['value'], $subtotal);
        } elseif ($p['type'] === 'percent') {
            $discount = round(($subtotal * $p['value']) / 100, 2);
        }
    }
}

$total = max(0, $subtotal + $shipping + $tax - $discount);

// ====================== GENERATE ORDER NUMBER ======================
$order_number = 'LVB-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));

// Status
$status = 'pending';
if ($payment_method === 'stripe')  $status = 'pending_payment';
if ($payment_method === 'paypal')  $status = 'pending_payment';

// ====================== SAVE ORDER ======================
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

$stmt = $conn->prepare("
    INSERT INTO orders
        (user_id, order_number, name, email, phone, address, city, state, zip, country,
         notes, promo_code, subtotal, shipping, discount, total,
         payment_method, stripe_payment_intent_id, status, created_at)
    VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, NOW())
");

$stmt->bind_param(
    "issssssssssdddddss",
    $user_id,
    $order_number,
    $full_name,
    $email,
    $phone,
    $address_full,
    $city,
    $state,
    $zip,
    $country,
    $notes,
    $promo_code,
    $subtotal,
    $shipping,
    $discount,
    $total,
    $payment_method,
    $status
);

if (!$stmt->execute()) {
    die("Failed to save order: " . $conn->error);
}
$order_id = $stmt->insert_id;
$stmt->close();

// ====================== SAVE ORDER ITEMS ======================
$stmt_item = $conn->prepare("
    INSERT INTO order_items (order_id, product_id, product_name, scent, quantity, price, subtotal)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$items_html = '';
$items_text = '';

foreach ($cart as $item) {
    $product_id   = intval($item['product_id'] ?? $item['id'] ?? 0);
    $product_name = $item['name'] ?? 'Product';
    $scent        = $item['scent'] ?? $item['fragrance_name'] ?? $item['size_name'] ?? 'Standard';
    $price        = floatval($item['price'] ?? 0);
    $qty          = intval($item['qty'] ?? 1);
    $item_total   = $price * $qty;

    $stmt_item->bind_param("iissidd",
        $order_id, $product_id, $product_name, $scent, $qty, $price, $item_total
    );
    $stmt_item->execute();

    $items_html .= "
        <tr>
            <td style='padding:10px 12px;border-bottom:1px solid #eee;'>
                <strong>" . htmlspecialchars($product_name) . "</strong>
                <br><span style='color:#6b7280;font-size:12px;'>Variant: " . htmlspecialchars($scent) . "</span>
            </td>
            <td style='padding:10px 12px;border-bottom:1px solid #eee;text-align:center;'>" . $qty . "</td>
            <td style='padding:10px 12px;border-bottom:1px solid #eee;text-align:right;'>$" . number_format($price, 2) . "</td>
            <td style='padding:10px 12px;border-bottom:1px solid #eee;text-align:right;font-weight:600;'>$" . number_format($item_total, 2) . "</td>
        </tr>";
    $items_text .= "• " . $product_name . " (" . $scent . ") × " . $qty . " = $" . number_format($item_total, 2) . "\n";
}
$stmt_item->close();

// Clear session
unset($_SESSION['cart']);

// ====================== EMAIL HELPER ======================
function sendMail(string $to, string $subject, string $htmlBody): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'mail.lagunavibe.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'noreply@lagunavibe.com';
        $mail->Password   = 'Xx=WUW0&i+ckjZfW';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom('noreply@lagunavibe.com', 'LVB Atelier');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</tr>', '</p>'], "\n", $htmlBody));
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail error to {$to}: " . $e->getMessage());
        return false;
    }
}

// ====================== BUILD SHARED SNIPPETS ======================
$order_date     = date('F j, Y \a\t g:i a');
$payment_label  = match ($payment_method) {
    'stripe' => 'Credit / Debit Card (Stripe)',
    'paypal' => 'PayPal',
    'cod'    => 'Cash on Delivery',
    default  => ucfirst($payment_method),
};

$discount_row_html = $discount > 0
    ? "<tr><td colspan='3' style='padding:8px 12px;text-align:right;color:#6b7280;'>Promo ({$promo_code}):</td><td style='padding:8px 12px;text-align:right;color:#059669;font-weight:600;'>−$" . number_format($discount, 2) . "</td></tr>"
    : '';

$totals_html = "
    <tr style='background:#f9fafb;'>
        <td colspan='3' style='padding:8px 12px;text-align:right;color:#6b7280;'>Subtotal:</td>
        <td style='padding:8px 12px;text-align:right;'>$" . number_format($subtotal, 2) . "</td>
    </tr>
    <tr style='background:#f9fafb;'>
        <td colspan='3' style='padding:8px 12px;text-align:right;color:#6b7280;'>Shipping:</td>
        <td style='padding:8px 12px;text-align:right;'>" . ($shipping == 0 ? '<span style=\"color:#059669;font-weight:600;\">FREE</span>' : '$' . number_format($shipping, 2)) . "</td>
    </tr>
    <tr style='background:#f9fafb;'>
        <td colspan='3' style='padding:8px 12px;text-align:right;color:#6b7280;'>Tax (8%):</td>
        <td style='padding:8px 12px;text-align:right;'>$" . number_format($tax, 2) . "</td>
    </tr>
    {$discount_row_html}
    <tr style='background:#0f4c5c;color:#fff;'>
        <td colspan='3' style='padding:12px;text-align:right;font-weight:700;'>TOTAL:</td>
        <td style='padding:12px;text-align:right;font-weight:700;font-size:16px;'>$" . number_format($total, 2) . "</td>
    </tr>";

// ====================== CUSTOMER EMAIL ======================
$customer_email_html = "
<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;background:#f4f4f5;font-family:\"DM Sans\",Arial,sans-serif;'>
<table width='100%' cellpadding='0' cellspacing='0'>
<tr><td align='center' style='padding:40px 20px;'>
<table width='600' cellpadding='0' cellspacing='0' style='background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);'>

  <!-- Header -->
  <tr>
    <td style='background:#0f4c5c;padding:40px 40px 30px;text-align:center;'>
      <h1 style='margin:0;color:#ffffff;font-size:28px;font-family:Georgia,serif;letter-spacing:0.04em;'>LVB Atelier</h1>
      <p style='margin:8px 0 0;color:rgba(255,255,255,0.75);font-size:13px;letter-spacing:0.08em;text-transform:uppercase;'>Order Confirmed</p>
    </td>
  </tr>

  <!-- Greeting -->
  <tr>
    <td style='padding:36px 40px 24px;'>
      <h2 style='margin:0 0 12px;font-size:22px;color:#1a1a1a;'>Thank you, " . htmlspecialchars($first_name ?: $full_name) . "! 🎉</h2>
      <p style='margin:0;color:#6b7280;font-size:15px;line-height:1.7;'>
        Your order has been received and is being prepared. We'll send you a shipping notification with tracking details once your package is on its way.
      </p>
    </td>
  </tr>

  <!-- Order Meta -->
  <tr>
    <td style='padding:0 40px 24px;'>
      <table width='100%' cellpadding='0' cellspacing='0' style='background:#f0f6f8;border-radius:10px;'>
        <tr>
          <td style='padding:16px 20px;'>
            <table width='100%'>
              <tr>
                <td style='color:#6b7280;font-size:12px;text-transform:uppercase;letter-spacing:0.08em;padding-bottom:4px;'>Order Number</td>
                <td style='color:#6b7280;font-size:12px;text-transform:uppercase;letter-spacing:0.08em;padding-bottom:4px;text-align:right;'>Date</td>
              </tr>
              <tr>
                <td style='color:#0f4c5c;font-size:16px;font-weight:700;'>" . htmlspecialchars($order_number) . "</td>
                <td style='color:#1a1a1a;font-size:14px;text-align:right;'>" . htmlspecialchars($order_date) . "</td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <!-- Items -->
  <tr>
    <td style='padding:0 40px 24px;'>
      <h3 style='margin:0 0 14px;font-size:16px;color:#1a1a1a;border-bottom:2px solid #e5e7eb;padding-bottom:10px;'>Items Ordered</h3>
      <table width='100%' cellpadding='0' cellspacing='0'>
        <thead>
          <tr style='background:#f9fafb;'>
            <th style='padding:10px 12px;text-align:left;font-size:11px;letter-spacing:0.08em;color:#6b7280;text-transform:uppercase;'>Product</th>
            <th style='padding:10px 12px;text-align:center;font-size:11px;letter-spacing:0.08em;color:#6b7280;text-transform:uppercase;'>Qty</th>
            <th style='padding:10px 12px;text-align:right;font-size:11px;letter-spacing:0.08em;color:#6b7280;text-transform:uppercase;'>Price</th>
            <th style='padding:10px 12px;text-align:right;font-size:11px;letter-spacing:0.08em;color:#6b7280;text-transform:uppercase;'>Total</th>
          </tr>
        </thead>
        <tbody>" . $items_html . "</tbody>
        <tfoot>" . $totals_html . "</tfoot>
      </table>
    </td>
  </tr>

  <!-- Shipping & Payment -->
  <tr>
    <td style='padding:0 40px 24px;'>
      <table width='100%' cellpadding='0' cellspacing='0'>
        <tr>
          <td width='48%' valign='top' style='padding-right:16px;'>
            <h3 style='margin:0 0 10px;font-size:14px;color:#1a1a1a;'>Shipping To</h3>
            <p style='margin:0;color:#6b7280;font-size:14px;line-height:1.8;'>
              " . htmlspecialchars($full_name) . "<br>
              " . htmlspecialchars($address_full) . "<br>
              " . htmlspecialchars($city) . ", " . htmlspecialchars($state) . " " . htmlspecialchars($zip) . "<br>
              " . htmlspecialchars($country) . "
            </p>
          </td>
          <td width='4%'></td>
          <td width='48%' valign='top'>
            <h3 style='margin:0 0 10px;font-size:14px;color:#1a1a1a;'>Payment</h3>
            <p style='margin:0;color:#6b7280;font-size:14px;line-height:1.8;'>
              " . htmlspecialchars($payment_label) . "<br>
              " . ($phone ? "Phone: " . htmlspecialchars($phone) . "<br>" : "") . "
              " . htmlspecialchars($email) . "
            </p>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  " . (!empty($notes) ? "
  <tr>
    <td style='padding:0 40px 24px;'>
      <div style='background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:14px 16px;'>
        <p style='margin:0;font-size:13px;color:#92400e;'><strong>Order Note:</strong> " . htmlspecialchars($notes) . "</p>
      </div>
    </td>
  </tr>" : "") . "

  <!-- Support -->
  <tr>
    <td style='padding:0 40px 36px;'>
      <div style='border-top:1px solid #e5e7eb;padding-top:24px;'>
        <p style='margin:0;color:#6b7280;font-size:13px;line-height:1.7;'>
          Have questions? Reply to this email or contact us at
          <a href='mailto:support@lagunavibe.com' style='color:#0f4c5c;font-weight:600;'>support@lagunavibe.com</a>
        </p>
      </div>
    </td>
  </tr>

  <!-- Footer -->
  <tr>
    <td style='background:#f9fafb;padding:24px 40px;text-align:center;border-top:1px solid #e5e7eb;'>
      <p style='margin:0 0 6px;font-size:13px;color:#6b7280;'>LVB Atelier — Crafted with care, delivered with love.</p>
      <p style='margin:0;font-size:11px;color:#9ca3af;'>A portion of every purchase supports charitable causes. ❤️</p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>";

// ====================== ADMIN EMAIL ======================
$admin_email_html = "
<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;background:#f4f4f5;font-family:Arial,sans-serif;'>
<table width='100%' cellpadding='0' cellspacing='0'>
<tr><td align='center' style='padding:30px 20px;'>
<table width='640' cellpadding='0' cellspacing='0' style='background:#fff;border-radius:12px;overflow:hidden;'>

  <!-- Alert Banner -->
  <tr>
    <td style='background:#0f4c5c;padding:20px 30px;'>
      <table width='100%'>
        <tr>
          <td>
            <h2 style='margin:0;color:#fff;font-size:20px;'>🛒 New Order Received</h2>
            <p style='margin:4px 0 0;color:rgba(255,255,255,0.7);font-size:13px;'>" . $order_date . "</p>
          </td>
          <td style='text-align:right;'>
            <div style='background:rgba(255,255,255,0.15);border-radius:8px;padding:8px 14px;display:inline-block;'>
              <div style='color:rgba(255,255,255,0.7);font-size:10px;text-transform:uppercase;letter-spacing:0.08em;'>Order #</div>
              <div style='color:#fff;font-weight:700;font-size:16px;'>" . htmlspecialchars($order_number) . "</div>
            </div>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <!-- Customer Info -->
  <tr>
    <td style='padding:24px 30px 12px;'>
      <h3 style='margin:0 0 14px;font-size:14px;color:#1a1a1a;text-transform:uppercase;letter-spacing:0.06em;'>Customer Details</h3>
      <table width='100%' cellpadding='0' cellspacing='0' style='border:1px solid #e5e7eb;border-radius:8px;'>
        <tr style='background:#f9fafb;'>
          <td style='padding:10px 14px;font-size:12px;color:#6b7280;font-weight:600;width:30%;'>Name</td>
          <td style='padding:10px 14px;font-size:14px;color:#1a1a1a;'>" . htmlspecialchars($full_name) . "</td>
        </tr>
        <tr>
          <td style='padding:10px 14px;font-size:12px;color:#6b7280;font-weight:600;border-top:1px solid #e5e7eb;'>Email</td>
          <td style='padding:10px 14px;font-size:14px;border-top:1px solid #e5e7eb;'><a href='mailto:" . htmlspecialchars($email) . "' style='color:#0f4c5c;'>" . htmlspecialchars($email) . "</a></td>
        </tr>
        " . ($phone ? "
        <tr style='background:#f9fafb;'>
          <td style='padding:10px 14px;font-size:12px;color:#6b7280;font-weight:600;border-top:1px solid #e5e7eb;'>Phone</td>
          <td style='padding:10px 14px;font-size:14px;border-top:1px solid #e5e7eb;'>" . htmlspecialchars($phone) . "</td>
        </tr>" : "") . "
        <tr" . (!$phone ? "" : " style='background:#f9fafb;'") . ">
          <td style='padding:10px 14px;font-size:12px;color:#6b7280;font-weight:600;border-top:1px solid #e5e7eb;'>Address</td>
          <td style='padding:10px 14px;font-size:14px;border-top:1px solid #e5e7eb;'>
            " . htmlspecialchars($address_full) . "<br>
            " . htmlspecialchars($city) . ", " . htmlspecialchars($state) . " " . htmlspecialchars($zip) . ", " . htmlspecialchars($country) . "
          </td>
        </tr>
        <tr style='background:#f9fafb;'>
          <td style='padding:10px 14px;font-size:12px;color:#6b7280;font-weight:600;border-top:1px solid #e5e7eb;'>Payment</td>
          <td style='padding:10px 14px;font-size:14px;border-top:1px solid #e5e7eb;'>
            <span style='background:" . ($payment_method === 'stripe' ? '#ede9fe' : ($payment_method === 'paypal' ? '#dbeafe' : '#f0fdf4')) . ";
                         color:" . ($payment_method === 'stripe' ? '#6d28d9' : ($payment_method === 'paypal' ? '#1e40af' : '#065f46')) . ";
                         padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;'>
              " . htmlspecialchars($payment_label) . "
            </span>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <!-- Items Table -->
  <tr>
    <td style='padding:12px 30px;'>
      <h3 style='margin:0 0 14px;font-size:14px;color:#1a1a1a;text-transform:uppercase;letter-spacing:0.06em;'>Order Items</h3>
      <table width='100%' cellpadding='0' cellspacing='0' style='border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;'>
        <thead>
          <tr style='background:#0f4c5c;'>
            <th style='padding:10px 12px;text-align:left;font-size:11px;color:rgba(255,255,255,0.8);text-transform:uppercase;letter-spacing:0.06em;font-weight:600;'>Product</th>
            <th style='padding:10px 12px;text-align:left;font-size:11px;color:rgba(255,255,255,0.8);text-transform:uppercase;letter-spacing:0.06em;font-weight:600;'>Variant</th>
            <th style='padding:10px 12px;text-align:center;font-size:11px;color:rgba(255,255,255,0.8);text-transform:uppercase;letter-spacing:0.06em;font-weight:600;'>Qty</th>
            <th style='padding:10px 12px;text-align:right;font-size:11px;color:rgba(255,255,255,0.8);text-transform:uppercase;letter-spacing:0.06em;font-weight:600;'>Price</th>
            <th style='padding:10px 12px;text-align:right;font-size:11px;color:rgba(255,255,255,0.8);text-transform:uppercase;letter-spacing:0.06em;font-weight:600;'>Total</th>
          </tr>
        </thead>
        <tbody>";

foreach ($cart as $idx => $item) {
    $bg = ($idx % 2 === 0) ? '#ffffff' : '#f9fafb';
    $product_name = $item['name'] ?? 'Product';
    $scent  = $item['scent'] ?? $item['fragrance_name'] ?? $item['size_name'] ?? 'Standard';
    $qty    = intval($item['qty'] ?? 1);
    $price  = floatval($item['price'] ?? 0);
    $itotal = $price * $qty;

    $admin_email_html .= "
            <tr style='background:{$bg};'>
                <td style='padding:10px 12px;font-size:13px;border-top:1px solid #e5e7eb;'><strong>" . htmlspecialchars($product_name) . "</strong></td>
                <td style='padding:10px 12px;font-size:13px;border-top:1px solid #e5e7eb;color:#6b7280;'>" . htmlspecialchars($scent) . "</td>
                <td style='padding:10px 12px;font-size:13px;border-top:1px solid #e5e7eb;text-align:center;'>{$qty}</td>
                <td style='padding:10px 12px;font-size:13px;border-top:1px solid #e5e7eb;text-align:right;'>$" . number_format($price, 2) . "</td>
                <td style='padding:10px 12px;font-size:13px;border-top:1px solid #e5e7eb;text-align:right;font-weight:600;'>$" . number_format($itotal, 2) . "</td>
            </tr>";
}

$admin_email_html .= "
        </tbody>
        <tfoot>" . $totals_html . "</tfoot>
      </table>
    </td>
  </tr>

  " . (!empty($notes) ? "
  <tr>
    <td style='padding:0 30px 12px;'>
      <div style='background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:14px;'>
        <strong style='font-size:13px;color:#92400e;'>Customer Note:</strong>
        <p style='margin:4px 0 0;font-size:13px;color:#92400e;'>" . htmlspecialchars($notes) . "</p>
      </div>
    </td>
  </tr>" : "") . "

  <!-- Footer -->
  <tr>
    <td style='background:#f9fafb;border-top:1px solid #e5e7eb;padding:16px 30px;'>
      <p style='margin:0;font-size:11px;color:#9ca3af;'>
        Order placed by IP: " . htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " · Order ID: {$order_id}
      </p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>";

// ====================== SEND EMAILS ======================
sendMail($email, "Order Confirmed #{$order_number} — LVB Atelier", $customer_email_html);
sendMail('admin@lagunavibe.com', "New Order #{$order_number} — LVB Atelier", $admin_email_html);

// ====================== CLEAR CLIENT CART ======================
echo "<script>sessionStorage.removeItem('lvb_cart');sessionStorage.removeItem('cart_synced');</script>";

// ====================== REDIRECT ======================
if ($payment_method === 'stripe') {
    header('Location: /stripe/create-checkout-session.php?order_id=' . $order_id . '&order_number=' . urlencode($order_number));
} elseif ($payment_method === 'paypal') {
    // Hardcoded PayPal redirect — replace with real PayPal SDK integration
    header('Location: https://www.paypal.com/checkoutnow?token=ORDER_TOKEN_HERE');
} else {
    header('Location: /thankyou?order_id=' . $order_id . '&order_number=' . urlencode($order_number));
}
exit;
?>
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/app.php';

// PHPMailer for emails
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Stripe API key (TEST)
\Stripe\Stripe::setApiKey('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');

// Validate session
if (!isset($_GET['session_id'])) {
    die("Invalid session. Please contact support.");
}

function sendMail($to, $subject, $htmlBody) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'mail.lagunavibe.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'noreply@lagunavibe.com';
        $mail->Password   = '=xQHc%KEN3!@ol96';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom('noreply@lagunavibe.com', 'LVB Atelier');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '<tr>', '</p>'], "\n", $htmlBody));
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail error to {$to}: " . $e->getMessage());
        return false;
    }
}

try {
    // Retrieve Stripe session
    $session = \Stripe\Checkout\Session::retrieve($_GET['session_id']);
    $paymentIntent = \Stripe\PaymentIntent::retrieve($session->payment_intent);
    
    $amount = $paymentIntent->amount_received / 100;
    $stripeStatus = $paymentIntent->status;
    $stripe_payment_intent_id = $paymentIntent->id;
    
    // Only process if payment succeeded
    if ($stripeStatus !== 'succeeded') {
        error_log("Payment not succeeded. Status: " . $stripeStatus);
        header("Location: /shop?payment=error");
        exit;
    }
    
    // Check if order already created (e.g. by Webhook or previous attempt)
    $stmtExisting = $conn->prepare("SELECT id, order_number FROM orders WHERE stripe_payment_intent_id = ?");
    if ($stmtExisting) {
        $stmtExisting->bind_param("s", $stripe_payment_intent_id);
        $stmtExisting->execute();
        $resExisting = $stmtExisting->get_result();
        if ($resExisting && $existingOrder = $resExisting->fetch_assoc()) {
            unset($_SESSION['cart']);
            unset($_SESSION['pending_order']);
            header('Location: /thankyou?order_id=' . $existingOrder['id'] . '&order_number=' . urlencode($existingOrder['order_number']));
            exit;
        }
        $stmtExisting->close();
    }

    // Get pending order data from session
    $pendingOrder = $_SESSION['pending_order'] ?? null;
    
    if (!$pendingOrder) {
        error_log("No pending order data found in session for Intent: " . $stripe_payment_intent_id);
        header("Location: /shop?payment=error");
        exit;
    }
    
    // Generate final order number
    $order_number = 'LVB-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));
    
    // Prepare order data
    $full_name = $pendingOrder['full_name'];
    $email = $pendingOrder['email'];
    $phone = $pendingOrder['phone'] ?? '';
    $address_full = $pendingOrder['address_full'];
    $city = $pendingOrder['city'];
    $state = $pendingOrder['state'];
    $zip = $pendingOrder['zip'];
    $country = $pendingOrder['country'];
    $notes = $pendingOrder['notes'] ?? '';
    $promo_code = $pendingOrder['promo_code'] ?? '';
    $subtotal = $pendingOrder['subtotal'];
    $shipping = $pendingOrder['shipping'];
    $discount = $pendingOrder['discount'];
    $total = $pendingOrder['total'];
    $cart = $pendingOrder['cart'];
    $payment_method = 'stripe';
    $status = 'paid';
    
    // Save order to database - MATCHING YOUR EXACT TABLE STRUCTURE
    $stmt = $conn->prepare("INSERT INTO orders 
        (order_number, name, email, phone, address, city, state, zip, country, notes, promo_code, 
         subtotal, shipping, discount, total, payment_method, stripe_payment_intent_id, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    $stmt->bind_param(
        "sssssssssssddddsss",
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
        $stripe_payment_intent_id,
        $status
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to save order: " . $conn->error);
    }
    
    $order_id = $stmt->insert_id;
    $stmt->close();
    
    // Save order items
    $stmt_item = $conn->prepare(
        "INSERT INTO order_items (order_id, product_id, product_name, scent, quantity, price, subtotal)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    
    foreach ($cart as $item) {
        $product_id = $item['product_id'] ?? $item['id'] ?? 0;
        $product_name = $item['name'] ?? 'Unknown Product';
        $price = floatval($item['price'] ?? 0);
        $qty = intval($item['qty'] ?? 1);
        $scent = $item['scent'] ?? '';
        $subtotal_item = $price * $qty;
        
        $stmt_item->bind_param(
            "iissidd",
            $order_id,
            $product_id,
            $product_name,
            $scent,
            $qty,
            $price,
            $subtotal_item
        );
        $stmt_item->execute();
    }
    $stmt_item->close();
    
    // Save payment record - MATCHING YOUR EXACT PAYMENTS TABLE
    $stmt = $conn->prepare("INSERT INTO payments (order_id, amount, status, stripe_payment_intent_id, created_at) 
        VALUES (?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("idss", $order_id, $amount, $status, $stripe_payment_intent_id);
        if (!$stmt->execute()) {
            error_log("Failed to save payment record: " . $stmt->error);
        }
        $stmt->close();
    } else {
        error_log("Failed to prepare payment statement: " . $conn->error);
    }
    
    // Build items HTML for email
    $items_html = '';
    $items_text = '';
    foreach ($cart as $item) {
        $product_name = $item['name'] ?? 'Product';
        $scent = $item['scent'] ?? 'Standard';
        $qty = intval($item['qty'] ?? 1);
        $price = floatval($item['price'] ?? 0);
        $item_total = $price * $qty;
        
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
    
    $shipping_display = ($shipping == 0) ? '<span style="color:#059669;font-weight:600;">FREE</span>' : '$' . number_format($shipping, 2);
    
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
            <td style='padding:8px 12px;text-align:right;'>" . $shipping_display . "</td>
         </tr>
        " . $discount_row_html . "
        <tr style='background:#0f4c5c;color:#fff;'>
            <td colspan='3' style='padding:12px;text-align:right;font-weight:700;'>TOTAL:</td>
            <td style='padding:12px;text-align:right;font-weight:700;font-size:16px;'>$" . number_format($total, 2) . "</td>
         </tr>";
    
    $order_date = date('F j, Y \a\t g:i a');
    
    // CUSTOMER EMAIL
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
      <p style='margin:8px 0 0;color:rgba(255,255,255,0.75);font-size:13px;letter-spacing:0.08em;text-transform:uppercase;'>Order Confirmed ✓</p>
    </td>
  </tr>

  <!-- Greeting -->
  <tr>
    <td style='padding:36px 40px 24px;'>
      <h2 style='margin:0 0 12px;font-size:22px;color:#1a1a1a;'>Thank you, " . htmlspecialchars($full_name) . "! 🎉</h2>
      <p style='margin:0;color:#6b7280;font-size:15px;line-height:1.7;'>
        Your payment has been successfully processed. Your order is being prepared and will ship soon.
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
              <strong>Credit / Debit Card (Stripe)</strong><br>
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

    // Send emails
    sendMail($email, "Order Confirmed #{$order_number} — LVB Atelier", $customer_email_html);
    sendMail('admin@lagunavibe.com', "New Paid Order #{$order_number} — LVB Atelier", $customer_email_html);
    
    // Clear cart from session
    unset($_SESSION['cart']);
    unset($_SESSION['pending_order']);
    
    // Redirect to thank you page
    header('Location: /thankyou?order_id=' . $order_id . '&order_number=' . urlencode($order_number));
    exit;
    
} catch (Exception $e) {
    error_log("Stripe success error: " . $e->getMessage());
    header("Location: /shop?payment=error");
    exit;
}
?>
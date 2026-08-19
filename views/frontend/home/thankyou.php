<?php
// thankyou.php - Fixed path and error handling
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../../db.php';

// Get order parameters from URL
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$order_number = isset($_GET['order_number']) ? trim($_GET['order_number']) : '';

// Redirect if no valid order reference
if ($order_id <= 0 && empty($order_number)) {
    $redirectUrl = base_url('/shop');
    if (!headers_sent()) {
        header('Location: ' . $redirectUrl);
    } else {
        echo "<script>window.location.href='" . addslashes($redirectUrl) . "';</script>";
    }
    exit;
}

// Fetch order from database
$order = null;
$order_items = [];

if ($order_id > 0) {
    // Fetch by ID
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
    }
} elseif (!empty($order_number)) {
    // Fetch by order number
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_number = ?");
    if ($stmt) {
        $stmt->bind_param("s", $order_number);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
    }
}

// If order not found, show graceful message
if (!$order) {
    $order_not_found = true;
} else {
    // Fetch order items with SKU
    $stmt_items = $conn->prepare("SELECT oi.*, p.sku AS product_sku FROM order_items oi LEFT JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ?");
    if ($stmt_items) {
        $stmt_items->bind_param("i", $order['id']);
        $stmt_items->execute();
        $items_result = $stmt_items->get_result();
        while ($item = $items_result->fetch_assoc()) {
            $order_items[] = $item;
        }
        $stmt_items->close();
    }
}

// Format address for display
$full_address = $order['address'] ?? '';
if (!empty($order['city'])) $full_address .= ', ' . $order['city'];
if (!empty($order['state'])) $full_address .= ', ' . $order['state'];
if (!empty($order['zip'])) $full_address .= ' ' . $order['zip'];
if (!empty($order['country'])) $full_address .= ', ' . $order['country'];
if (empty($full_address)) $full_address = 'Saved address at checkout';

// Format payment method for display
$payment_display = 'Bank of America® (Credit / Debit Card)';
if (!empty($order['payment_method'])) {
    switch ($order['payment_method']) {
        case 'bank_of_america':
            $payment_display = 'Bank of America® (Card / Merchant Gateway)';
            break;
        case 'stripe':
            $payment_display = 'Bank of America® (Credit / Debit Card)';
            break;
        case 'cod':
            $payment_display = 'Cash on Delivery';
            break;
        case 'paypal':
            $payment_display = 'PayPal';
            break;
        default:
            $payment_display = 'Bank of America® (' . ucfirst($order['payment_method']) . ')';
    }
}

// Calculate total from DB (already stored, but ensure format)
$order_total = floatval($order['total'] ?? 0);
$customer_email = $order['email'] ?? 'your email';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Thank You | Laguna Vibe</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base; ?>/public/assets/css/ada-compliance.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: 
                radial-gradient(circle at 15% 20%, rgba(15, 76, 92, 0.45) 0%, transparent 45%),
                radial-gradient(circle at 85% 80%, rgba(15, 76, 92, 0.35) 0%, transparent 45%),
                linear-gradient(135deg, rgba(7, 25, 36, 0.94) 0%, rgba(15, 76, 92, 0.88) 100%),
                url('https://images.pexels.com/photos/37162119/pexels-photo-37162119.jpeg') center/cover no-repeat fixed;
            background-color: #071924;
            min-height: 100vh;
        }

        .page-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2.5rem 1.5rem;
            background: rgba(7, 25, 36, 0.65);
            backdrop-filter: blur(20px) saturate(140%);
            -webkit-backdrop-filter: blur(20px) saturate(140%);
        }

        .thankyou-card {
            max-width: 620px;
            width: 100%;
            background: #ffffff;
            border-radius: 28px;
            box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .card-header {
            background: #0F4C5C;
            padding: 2rem 1.5rem 1.5rem;
            text-align: center;
            color: white;
        }

        .card-header i {
            font-size: 3rem;
            color: #e9c46a;
            margin-bottom: 0.75rem;
        }

        .card-header h1 {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.2rem;
            font-weight: 600;
            margin: 0.25rem 0 0.5rem;
        }

        .order-badge {
            background: rgba(255,245,215,0.2);
            display: inline-block;
            padding: 0.3rem 1.2rem;
            border-radius: 40px;
            font-size: 0.75rem;
            font-weight: 500;
            letter-spacing: 0.5px;
            margin-top: 0.25rem;
        }

        .card-body {
            padding: 1.8rem 2rem 2rem;
        }

        .thankyou-message {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .thankyou-message p {
            color: #2d3e35;
            font-size: 0.95rem;
            line-height: 1.45;
            margin-top: 0.3rem;
        }

        .order-details {
            background: #f9f7f3;
            border-radius: 20px;
            padding: 1rem 1.25rem;
            margin: 1.5rem 0;
            border: 1px solid #ede5d8;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.6rem 0;
            border-bottom: 1px solid #e7dfd1;
            font-size: 0.85rem;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #4a6741;
        }

        .detail-value {
            color: #1c2e28;
            font-weight: 500;
            text-align: right;
        }

        .highlight {
            color: #c4692c;
            font-weight: 700;
        }

        .items-preview {
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px dashed #e0d5c5;
        }

        .items-preview summary {
            font-size: 0.75rem;
            font-weight: 600;
            color: #6f8f72;
            cursor: pointer;
            margin: 0.5rem 0;
        }

        .items-list {
            font-size: 0.75rem;
            padding-left: 0.5rem;
        }

        .items-list p {
            margin: 0.3rem 0;
            display: flex;
            justify-content: space-between;
        }

        .email-sent {
            background: #ecf6ef;
            border-radius: 1rem;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.7rem;
            margin: 1rem 0;
            font-size: 0.8rem;
            color: #2b5e3b;
        }

        .email-sent i {
            font-size: 1.2rem;
        }

        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            justify-content: center;
            margin: 1.5rem 0 1rem;
        }

        .btn {
            display: inline-block;
            padding: 0.7rem 1.4rem;
            border-radius: 40px;
            font-weight: 600;
            text-decoration: none;
            font-size: 0.8rem;
            transition: 0.2s;
            background: white;
            border: 1px solid #cbdcd0;
            color: #2d5a47;
        }

        .btn-primary {
            background: #1f5446;
            border: none;
            color: white;
        }

        .btn-primary:hover {
            background: #154237;
        }

        .btn-outline:hover {
            background: #f5f1ea;
        }

        .support-links {
            text-align: center;
            font-size: 0.7rem;
            color: #6f7e72;
            border-top: 1px solid #ede3d6;
            padding-top: 1.2rem;
            margin-top: 0.5rem;
        }

        .support-links a {
            color: #3a6b58;
            text-decoration: none;
            font-weight: 500;
        }

        .support-links a:hover {
            text-decoration: underline;
        }

        .not-found {
            text-align: center;
            padding: 2rem;
        }

        @media (max-width: 550px) {
            .page-wrapper { padding: 1rem; }
            .card-body { padding: 1.3rem 1.2rem 1.5rem; }
            .card-header h1 { font-size: 1.8rem; }
            .action-buttons { flex-direction: column; align-items: stretch; }
            .btn { text-align: center; }
        }
    </style>
</head>
<body>
<div class="page-wrapper">
    <div class="thankyou-card">
        <?php if (isset($order_not_found) && $order_not_found): ?>
            <div class="card-header">
                <i class="fa-regular fa-circle-question"></i>
                <h1>Order Not Found</h1>
            </div>
            <div class="card-body not-found">
                <p>We couldn't locate your order details.</p>
                <p style="margin-top: 0.5rem;">Please contact support if you have any questions.</p>
                <div class="action-buttons" style="margin-top: 1.5rem;">
                    <a href="<?php echo $base; ?>/shop" class="btn btn-primary">Continue Shopping</a>
                    <a href="<?php echo $base; ?>/contact" class="btn btn-outline">Contact Support</a>
                </div>
            </div>
        <?php else: ?>
            <div class="card-header">
                <i class="fa-regular fa-circle-check"></i>
                <h1>You're all set!</h1>
                <div class="order-badge">
                    ORDER #<?php echo htmlspecialchars($order['order_number']); ?>
                </div>
            </div>

            <div class="card-body">
                <div class="thankyou-message">
                    <p><strong>Thank you for shopping with Laguna Vibe.</strong></p>
                    <p>We've received your order and it's being prepared with care. A confirmation email has been sent to your inbox.</p>
                </div>

                <!-- ORDER DETAILS FROM DATABASE -->
                <div class="order-details">
                    <div class="detail-row">
                        <span class="detail-label">Order total</span>
                        <span class="detail-value highlight">$<?php echo number_format($order_total, 2); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Payment method</span>
                        <span class="detail-value"><?php echo htmlspecialchars($payment_display); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Shipping method</span>
                        <span class="detail-value">
                            <i class="fa-solid fa-truck-fast" style="color:#0F4C5C; margin-right:4px;"></i>
                            <?php echo htmlspecialchars($order['shipping_method'] ?? 'FedEx Home Delivery®'); ?>
                            (<?php echo ((float)($order['shipping'] ?? 0) > 0) ? '$' . number_format((float)$order['shipping'], 2) : '<span style="color:#16a34a; font-weight:700;">FREE</span>'; ?>)
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Shipping to</span>
                        <span class="detail-value"><?php echo htmlspecialchars($full_address); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Estimated delivery</span>
                        <span class="detail-value"><?php echo htmlspecialchars(!empty($order['delivery_estimate']) ? $order['delivery_estimate'] : '3–5 business days ✨'); ?></span>
                    </div>
                    
                    <!-- Show ordered items if available -->
                    <?php if (!empty($order_items)): ?>
                    <details class="items-preview" open>
                        <summary><i class="fa-regular fa-receipt"></i> Ordered Items (<?php echo count($order_items); ?>)</summary>
                        <div class="items-list" style="margin-top: 8px;">
                            <?php foreach ($order_items as $item): ?>
                            <?php $itemSku = !empty($item['sku']) ? $item['sku'] : (!empty($item['product_sku']) ? $item['product_sku'] : ''); ?>
                            <p style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px; padding-bottom:6px; border-bottom:1px dashed #e2e8f0;">
                                <span>
                                    <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                    <?php if (!empty($itemSku)): ?>
                                        <br><span style="display:inline-flex; align-items:center; gap:3px; color:#1e293b; font-size:10.5px; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; background:#f1f5f9; border:1px solid #cbd5e1; padding:1px 6px; border-radius:4px; font-weight:600; margin-top:2px;"><i class="fas fa-barcode" style="font-size:9.5px; color:#64748b;"></i> SKU: <?php echo htmlspecialchars($itemSku); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($item['scent'])): ?>
                                        <br><span style="color:#0F4C5C; font-weight:600; font-size:12px;">Scent: <?php echo htmlspecialchars($item['scent']); ?></span>
                                    <?php endif; ?>
                                    <span style="color:#64748b; font-size:12px;"> × <?php echo $item['quantity']; ?></span>
                                </span>
                                <span style="font-weight:600; color:#1E2F3A;">$<?php echo number_format($item['subtotal'], 2); ?></span>
                            </p>
                            <?php endforeach; ?>
                        </div>
                    </details>
                    <?php endif; ?>
                </div>

                <div class="pci-notice" style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:0.8rem; padding:0.7rem 1rem; display:flex; align-items:center; gap:0.7rem; margin:1rem 0; font-size:0.78rem; color:#166534;">
                    <i class="fa-solid fa-shield-check" style="font-size:1.2rem; color:#16a34a;"></i>
                    <span><strong>Bank of America Secure Transaction</strong> — Processed via Bank of America Merchant Services Gateway. Card details are fully encrypted and zero card data is retained on our servers.</span>
                </div>

                <div class="email-sent">
                    <i class="fa-regular fa-envelope-open"></i>
                    <span><strong>Instant confirmation</strong> — We've emailed your order details & receipt.<br> (Customer & admin copies sent to <?php echo htmlspecialchars($customer_email); ?>)</span>
                </div>

                <div class="action-buttons">
                    <a href="<?php echo $base; ?>/shop" class="btn btn-primary"><i class="fa-solid fa-shop"></i> Continue shopping</a>
                    <a href="<?php echo $base; ?>/contact" class="btn btn-outline"><i class="fa-regular fa-message"></i> Support</a>
                </div>

                <div class="support-links">
                    Need help? Email <a href="mailto:support@lagunavibe.com">support@lagunavibe.com</a> or call +1 (888) 420-1965<br>
                    <span style="font-size: 0.7rem;">A portion of every order supports sustainable artisans. © Laguna Vibe <?php echo date('Y'); ?> | ethical fragrance</span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../layouts/ada_widget.php'; ?>
</body>
</html>
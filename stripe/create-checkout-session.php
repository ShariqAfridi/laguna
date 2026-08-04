<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/app.php';

header('Content-Type: application/json');
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");

$stripeSecret = env('STRIPE_SECRET_KEY', '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');
\Stripe\Stripe::setApiKey($stripeSecret);


// Get cart data from POST
$cartData = $_POST['cart_data'] ?? $_POST['cart'] ?? '';
$cart = json_decode($cartData, true);

// If no cart in POST, check session
if (empty($cart) && isset($_SESSION['cart'])) {
    $cart = $_SESSION['cart'];
}

// Convert associative array to indexed if needed
if (!empty($cart) && !isset($cart[0])) {
    $indexedCart = [];
    foreach ($cart as $item) {
        $indexedCart[] = $item;
    }
    $cart = $indexedCart;
}

if (empty($cart)) {
    echo json_encode(['error' => 'Cart is empty']);
    exit;
}

// Get customer data from POST
$full_name = $_POST['full_name'] ?? '';
$first_name = $_POST['first_name'] ?? '';
$last_name = $_POST['last_name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$address = $_POST['address'] ?? '';
$apartment = $_POST['apartment'] ?? '';
$address_full = !empty($apartment) ? $address . ', ' . $apartment : $address;
$city = $_POST['city'] ?? '';
$state = $_POST['state'] ?? '';
$zip = $_POST['zip'] ?? '';
$country = $_POST['country'] ?? 'US';
$notes = $_POST['notes'] ?? '';
$promo_code = $_POST['promo_code'] ?? '';
$delivery_type = $_POST['delivery_type'] ?? 'standard';

// If full_name not provided, combine first and last
if (empty($full_name) && !empty($first_name)) {
    $full_name = trim($first_name . ' ' . $last_name);
}

// Calculate totals
$calc = \App\Models\Order::calculateTotals($cart, $promo_code, $delivery_type);
$subtotal = $calc['subtotal'];
$shipping = $calc['shipping'];
$tax      = $calc['tax'];
$discount = $calc['discount'];
$total    = $calc['total'];

try {
    // Prepare line items for Stripe
    $line_items = [];
    foreach ($cart as $item) {
        $product_name = $item['name'] ?? 'Product';
        $scent = $item['scent'] ?? '';
        $price = floatval($item['price'] ?? 0);
        $qty = intval($item['qty'] ?? 1);
        
        if ($scent) {
            $product_name .= ' - ' . $scent;
        }
        
        $line_items[] = [
            'price_data' => [
                'currency' => 'usd',
                'product_data' => ['name' => $product_name],
                'unit_amount' => round($price * 100),
            ],
            'quantity' => $qty,
        ];
    }
    
    // Add shipping as line item if applicable
    if ($shipping > 0) {
        $line_items[] = [
            'price_data' => [
                'currency' => 'usd',
                'product_data' => ['name' => 'Shipping (' . ($delivery_type === 'express' ? 'Express' : 'Standard') . ')'],
                'unit_amount' => round($shipping * 100),
            ],
            'quantity' => 1,
        ];
    }
    
    // Add tax as line item
    if ($tax > 0) {
        $line_items[] = [
            'price_data' => [
                'currency' => 'usd',
                'product_data' => ['name' => 'Tax (8%)'],
                'unit_amount' => round($tax * 100),
            ],
            'quantity' => 1,
        ];
    }
    
    // Add discount as negative line item if applicable
    if ($discount > 0) {
        $line_items[] = [
            'price_data' => [
                'currency' => 'usd',
                'product_data' => ['name' => 'Discount (' . $promo_code . ')'],
                'unit_amount' => -round($discount * 100),
            ],
            'quantity' => 1,
        ];
    }
    
    // Generate unique order number (temporary)
    $temp_order_number = 'TMP-' . time() . '-' . rand(1000, 9999);
    
    // Store order data in session for later use when payment succeeds

$_SESSION['pending_order'] = [
    'cart' => $cart,
    'full_name' => $full_name,
    'email' => $email,
    'phone' => $phone,
    'address_full' => $address_full,
    'city' => $city,
    'state' => $state,
    'zip' => $zip,
    'country' => $country,
    'notes' => $notes,
    'promo_code' => $promo_code,
    'subtotal' => $subtotal,
    'shipping' => $shipping,
    'discount' => $discount,
    'total' => $total,
    'temp_order_number' => $temp_order_number
];
    
    // Create Stripe Checkout Session
    $checkout_session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'mode' => 'payment',
        'line_items' => $line_items,
        'metadata' => [
            'temp_order_number' => $temp_order_number,
            'user_email' => $email,
            'user_name' => $full_name
        ],
        'success_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/stripe/success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/checkout',
        'customer_email' => $email,
    ]);
    
    echo json_encode(['id' => $checkout_session->id]);
    
} catch (Exception $e) {
    error_log("Stripe checkout error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
?>
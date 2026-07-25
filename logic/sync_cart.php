<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['cart'])) {
    echo json_encode(['success' => false, 'error' => 'No cart data provided']);
    exit;
}

$cart = $input['cart'];

// Check if we already have the same cart in session to avoid unnecessary updates
$currentSessionCart = $_SESSION['cart'] ?? [];
$currentCount = count($currentSessionCart);
$newCount = count($cart);

// Convert cart array to session format
$sessionCart = [];
if (is_array($cart)) {
    foreach ($cart as $item) {
        if (isset($item['id'])) {
            $sessionCart[$item['id']] = [
                'product_id' => $item['product_id'] ?? $item['id'],
                'id' => $item['id'],
                'name' => $item['name'],
                'price' => floatval($item['price']),
                'qty' => intval($item['qty']),
                'image' => $item['image'] ?? '',
                'scent' => $item['scent'] ?? ''
            ];
        }
    }
}

// Only update session if cart has changed
$updateNeeded = ($currentCount !== $newCount) || (json_encode($currentSessionCart) !== json_encode($sessionCart));

if ($updateNeeded) {
    $_SESSION['cart'] = $sessionCart;
    error_log("Cart updated in session. New count: " . count($sessionCart));
} else {
    error_log("Cart unchanged, no update needed");
}

echo json_encode([
    'success' => true,
    'cart_count' => count($sessionCart),
    'cart_empty' => empty($sessionCart),
    'updated' => $updateNeeded
]);
?>
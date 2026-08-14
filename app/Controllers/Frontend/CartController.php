<?php
namespace App\Controllers\Frontend;

class CartController {
    public static function sync() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['cart'])) {
            echo json_encode(['success' => false, 'error' => 'No cart data provided']);
            return;
        }

        $cart = $input['cart'];
        $currentSessionCart = $_SESSION['cart'] ?? [];
        $currentCount = count($currentSessionCart);
        $newCount = is_array($cart) ? count($cart) : 0;

        $sessionCart = [];
        if (is_array($cart)) {
            foreach ($cart as $item) {
                if (isset($item['id'])) {
                    $scent = !empty($item['scent']) ? $item['scent'] : (!empty($item['fragrance_name']) ? $item['fragrance_name'] : 'Standard');
                    $sessionCart[$item['id']] = [
                        'product_id'     => $item['product_id'] ?? $item['id'],
                        'id'             => $item['id'],
                        'name'           => $item['name'] ?? 'Product',
                        'price'          => floatval($item['price'] ?? 0),
                        'qty'            => intval($item['qty'] ?? 1),
                        'image'          => $item['image'] ?? '',
                        'scent'          => $scent,
                        'fragrance_name' => $item['fragrance_name'] ?? $scent,
                        'fragrance_id'   => $item['fragrance_id'] ?? null,
                        'size_id'        => $item['size_id'] ?? null,
                        'size_name'      => $item['size_name'] ?? null,
                        'box_id'         => $item['box_id'] ?? null,
                        'box_name'       => $item['box_name'] ?? null,
                        'color_name'     => $item['color_name'] ?? null,
                        'sku'            => $item['sku'] ?? ''
                    ];
                }
            }
        }

        $updateNeeded = ($currentCount !== $newCount) || (json_encode($currentSessionCart) !== json_encode($sessionCart));

        if ($updateNeeded) {
            $_SESSION['cart'] = $sessionCart;
            error_log("Cart updated in session. New count: " . count($sessionCart));
        }

        echo json_encode([
            'success'    => true,
            'cart_count' => count($sessionCart),
            'cart_empty' => empty($sessionCart),
            'updated'    => $updateNeeded
        ]);
    }
}
?>

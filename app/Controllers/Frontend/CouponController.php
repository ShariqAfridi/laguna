<?php
namespace App\Controllers\Frontend;

use App\Models\Coupon;

class CouponController {
    public static function validate() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['valid' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $code = $_POST['code'] ?? $_GET['code'] ?? '';
        $subtotal = floatval($_POST['subtotal'] ?? $_GET['subtotal'] ?? 0);

        if (empty($code)) {
            echo json_encode(['valid' => false, 'message' => 'Please enter a coupon code.', 'discount' => 0.00]);
            exit;
        }

        // If subtotal wasn't passed directly, try calculating from session cart
        if ($subtotal <= 0 && !empty($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                $subtotal += floatval($item['price'] ?? 0) * intval($item['qty'] ?? 1);
            }
        }

        $result = Coupon::validate($code, $subtotal);
        echo json_encode($result);
        exit;
    }
}

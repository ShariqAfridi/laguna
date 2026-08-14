<?php
namespace App\Controllers\Frontend;

use App\Models\Order;

class CheckoutController {
    public static function index() {
        view('frontend/checkout', [], false);
    }



    public static function createStripeSession() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        header('Content-Type: application/json');

        $stripeSecret = env('STRIPE_SECRET_KEY', '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');
        if (class_exists('\Stripe\Stripe')) {
            \Stripe\Stripe::setApiKey($stripeSecret);
        }

        $cartData = $_POST['cart_data'] ?? $_POST['cart'] ?? '';
        $cart = json_decode($cartData, true);

        if (empty($cart) && isset($_SESSION['cart'])) {
            $cart = $_SESSION['cart'];
        }

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

        require_once dirname(__DIR__, 3) . '/stripe/create-checkout-session.php';
        exit;
    }

    public static function placeOrder() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        require_once dirname(__DIR__, 3) . '/logic/place_order.php';
        exit;
    }

    public static function stripeWebhook() {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        require_once dirname(__DIR__, 3) . '/stripe/webhook.php';
        exit;
    }
}
?>


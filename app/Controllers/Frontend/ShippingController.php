<?php
// app/Controllers/Frontend/ShippingController.php — FedEx Shipping Rates Controller
namespace App\Controllers\Frontend;

use App\Services\FedExService;

class ShippingController
{
    public static function calculateRates()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $input = $_POST;
        if (empty($input) && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $input = $_GET;
        }

        // Also support raw JSON body
        $rawBody = file_get_contents('php://input');
        if (!empty($rawBody)) {
            $jsonInput = json_decode($rawBody, true);
            if (is_array($jsonInput)) {
                $input = array_merge($input, $jsonInput);
            }
        }

        $recipient = [
            'address' => trim($input['address'] ?? ''),
            'city'    => trim($input['city'] ?? ''),
            'state'   => trim($input['state'] ?? ''),
            'zip'     => trim($input['zip'] ?? ''),
            'country' => trim($input['country'] ?? 'US'),
        ];

        // Parse Cart Items
        $cart = [];
        if (!empty($input['cart_data'])) {
            if (is_string($input['cart_data'])) {
                $cart = json_decode($input['cart_data'], true) ?: [];
            } elseif (is_array($input['cart_data'])) {
                $cart = $input['cart_data'];
            }
        }

        if (empty($cart) && !empty($_SESSION['cart'])) {
            $cart = array_values($_SESSION['cart']);
        }

        // Calculate Subtotal
        $subtotal = 0.0;
        if (isset($input['subtotal']) && is_numeric($input['subtotal'])) {
            $subtotal = (float)$input['subtotal'];
        } else {
            foreach ($cart as $item) {
                $subtotal += ((float)($item['price'] ?? 0)) * max(1, (int)($item['qty'] ?? 1));
            }
        }

        // Request rates through FedExService
        $result = FedExService::getRates($recipient, $cart, $subtotal);

        echo json_encode([
            'success'                 => true,
            'source'                  => $result['source'] ?? 'fallback',
            'package_weight'          => $result['package_weight'] ?? 1.0,
            'qualifies_free_shipping' => $result['qualifies_free_shipping'] ?? false,
            'free_shipping_threshold' => $result['free_shipping_threshold'] ?? 75.00,
            'rates'                   => $result['rates'] ?? [],
        ]);
        exit;
    }
}

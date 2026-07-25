<?php
class CheckoutController {
    public static function index() {
        require_once __DIR__ . '/../../../views/frontend/checkout.php';
    }
}
CheckoutController::index();
?>
